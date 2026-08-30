#!/usr/bin/env python3
"""Build the Claude Chat Widgets mosaic from the project's full-page screenshots.

For each widget it crops the card out of its screenshot, trims to the exact card
box, fades the bottom edge of any widget whose content runs past the crop, then
packs the cards into a balanced masonry grid with identical gaps in both
directions. Everything is composed at native resolution and resampled once on
export, which is what keeps the UI text crisp.

Writes:
  screenshots/hidden/<label>.png      the cropped widgets (hidden/ is skipped by the site)
  screenshots/000-widgets-mosaic@2x.jpg   the mosaic used on the project page
  ./mosaic-master.jpg                 full-resolution master for marketing use

Requires ImageMagick 7 (`magick`).
"""

import os
import random
import subprocess

HERE = os.path.dirname(os.path.abspath(__file__))
SHOTS = os.path.join(HERE, "..", "projects", "claude-chat-widgets", "screenshots")
TILES = os.path.join(SHOTS, "hidden")
SITE_OUT = os.path.join(SHOTS, "000-widgets-mosaic@2x.jpg")
MASTER_OUT = "mosaic-master.jpg"

X, W = 722, 1466          # the message column in a 2880x2048 screenshot
GAP = 60                  # identical horizontal and vertical spacing
NCOL = 3
BG = "#FCFCFA"            # the page background behind the cards
FADE = 240                # height of the fade on a widget that overflows its crop
SITE_WIDTH = 2880         # matches the other screenshots in this project

# (screenshot, crop top, crop bottom limit, label) - one shot per widget, chosen
# for photos and interaction states rather than walls of text.
SPECS = [
    ("001-place-lists@2x.jpg",      440, 1662, "places"),  # both place cards; stops above the floating scroll button
    # 002-place-carousel is left out: its only slide is Linea Caffe, already in "places"
    ("003-itinerary-1@2x.jpg",      356, 1490, "itinerary"),
    ("004-tutorial-1@2x.jpg",       362,  920, "tutorial"),
    ("005-recipe-1@2x.jpg",         428, 1655, "recipe"),
    ("006-product-hero@2x.jpg",     418, 1288, "product"),
    ("007-product-carousel@2x.jpg", 328, 1238, "product-carousel"),
    ("008-comparison-1@2x.jpg",     370, 1620, "comparison"),
    ("009-health-info-1@2x.jpg",    644, 1094, "health"),
    ("010-images@2x.jpg",           356, 1194, "images"),
    ("011-quiz-4@2x.jpg",           428, 1524, "quiz"),
    ("012-translation@2x.jpg",      484,  910, "translation"),
    ("013-charts-1@2x.jpg",         516, 1212, "charts"),
]

PHOTO = {"places", "recipe", "product", "product-carousel", "images"}
PAIRS = [("product", "product-carousel")]  # same subject twice

# Widgets pinned to a slot: (column, row, label). A negative row counts from the bottom.
PINS = [
    (0,  1, "charts"),
    (1,  0, "quiz"),
    (1,  1, "translation"),
    (1,  2, "images"),
    (1, -1, "comparison"),
    (2,  1, "health"),
    (2,  2, "itinerary"),
    (2, -1, "recipe"),
]


def magick(*args):
    subprocess.run(["magick", *args], check=True)


def identify(path, fmt):
    return subprocess.run(["magick", "identify", "-format", fmt, path],
                          capture_output=True, text=True).stdout


def read_gray(args):
    """Run a magick pipeline that ends in pgm:- and return (width, height, pixels)."""
    out = subprocess.run(["magick", *args, "pgm:-"], capture_output=True).stdout
    parts, i = [], 0
    while len(parts) < 4:
        while out[i:i + 1].isspace():
            i += 1
        if out[i:i + 1] == b"#":                 # ffmpeg-written frames carry a comment line
            while out[i:i + 1] != b"\n":
                i += 1
            continue
        start = i
        while not out[i:i + 1].isspace():
            i += 1
        parts.append(out[start:i])
    i += 1
    w, h = int(parts[1]), int(parts[2])
    return w, h, out[i:i + w * h]


def column_gray(path, y, h, sw=180):
    return read_gray([path, "-crop", f"{W - 80}x{h}+{X + 40}+{y}", "+repage",
                      "-colorspace", "gray", "-depth", "8", "-resize", f"{sw}x{h}!"])


def snap_bottom(path, y0, cap, search=260):
    """Walk up from cap to the nearest clean horizontal gap so a text line is never sliced."""
    y = max(y0 + 120, cap - search)
    w, h, d = column_gray(path, y, cap - y)
    ink = [sum(1 for p in d[r * w:(r + 1) * w] if p < 205) for r in range(h)]
    run = 0
    for r in range(h - 1, -1, -1):
        if ink[r] == 0:
            run += 1
            if run >= 8:
                return y + r + run
        else:
            run = 0
    return cap


def is_cut(path, y1):
    """True when the crop ends inside the card: no full-width bottom border above the cut."""
    w, h, d = column_gray(path, y1 - 40, 40)
    for r in range(h):
        row = d[r * w:(r + 1) * w]
        if sum(1 for p in row if p < 238) / w > 0.9:    # the card's own bottom rule
            return False
    return True


def card_box(path, faded, margin=3):
    """Bounding box of the card inside a tile, plus a margin so the border is never clipped."""
    w, h, d = read_gray([path, "-colorspace", "gray", "-depth", "8"])

    def notcream(p):
        return p < 250 or p > 254                       # page bg is 252; card 255, border 226

    # a couple of stray JPEG specks are not the card edge - require a real run of pixels
    rows = [sum(1 for p in d[r * w:(r + 1) * w] if notcream(p)) > w * 0.02 for r in range(h)]
    cols = [sum(1 for r in range(h) if notcream(d[r * w + c])) > h * 0.02 for c in range(w)]
    top, bot = rows.index(True), h - 1 - rows[::-1].index(True)
    left, right = cols.index(True), w - 1 - cols[::-1].index(True)
    if faded:
        bot = h - 1                                     # a faded edge runs to the tile edge
    else:
        bot = min(h - 1, bot + margin)
    return max(0, left - margin), max(0, top - margin), min(w - 1, right + margin), bot


def build_tiles():
    os.makedirs(TILES, exist_ok=True)
    tiles = []
    for shot, y0, cap, label in SPECS:
        src = os.path.join(SHOTS, shot)
        y1 = snap_bottom(src, y0, cap)
        cut = is_cut(src, y1)
        out = os.path.join(TILES, f"{label}.png")
        magick(src, "-crop", f"{W}x{y1 - y0}+{X}+{y0}", "+repage", out)
        if cut:  # overflowing widget: fade the cut edge out to the background
            h = int(identify(out, "%h"))
            magick(out,
                   "(", "-size", f"{W}x{h - FADE}", "xc:white",
                   "(", "-size", f"{W}x{FADE}", "gradient:white-black", ")", "-append", ")",
                   "-alpha", "off", "-compose", "CopyOpacity", "-composite", out)
        left, top, right, bot = card_box(out, cut)
        magick(out, "-crop", f"{right - left + 1}x{bot - top + 1}+{left}+{top}", "+repage", out)
        tiles.append((label, out, int(identify(out, "%h")), cut))
        print(f"{label}: y {y0}-{y1}{'  (faded)' if cut else ''}")

    # the widgets differ slightly in native width; scale to one width (<1%) so that
    # every visible gap is identical horizontally and vertically
    cardw = max(int(identify(p, "%w")) for _, p, _, _ in tiles)
    for _, p, _, _ in tiles:
        magick(p, "-filter", "Lanczos", "-resize", f"{cardw}x", p)
    return cardw, [(l, p, int(identify(p, "%h")), c) for l, p, _, c in tiles]


def pack(tiles, attempts=200000, seed=7):
    """Masonry packing: honor the pinned slots, then balance the columns."""
    random.seed(seed)
    by_label = {t[0]: t for t in tiles}
    pinned = {label: (col, row) for col, row, label in PINS}
    free = [t for t in tiles if t[0] not in pinned]
    best = None

    for _ in range(attempts):
        buckets = [[] for _ in range(NCOL)]
        for t in free:
            buckets[random.randrange(NCOL)].append(t)

        cols, ok = [], True
        for ci in range(NCOL):
            pins = [(row, label) for label, (col, row) in pinned.items() if col == ci]
            n = len(pins) + len(buckets[ci])
            slots = [None] * n
            for row, label in pins:
                i = row if row >= 0 else n + row
                if not 0 <= i < n or slots[i] is not None:
                    ok = False
                    break
                slots[i] = by_label[label]
            if not ok:
                break
            rest = buckets[ci][:]
            random.shuffle(rest)
            for i in range(n):
                if slots[i] is None:
                    slots[i] = rest.pop()
            cols.append(slots)
        if not ok:
            continue

        led = {col for col, row, _ in PINS if row == 0}
        if any(c[0][0] not in PHOTO for ci, c in enumerate(cols) if ci not in led):
            continue                          # unpinned columns still lead with a photo widget
        where = {t[0]: ci for ci, c in enumerate(cols) for t in c}
        if any(abs(where[a] - where[b]) < 2 for a, b in PAIRS):
            continue                                    # keep look-alikes out of neighboring columns

        heights = [sum(t[2] for t in c) + GAP * (len(c) - 1) for c in cols]
        spread = max(heights) - min(heights)
        if best is None or spread < best[0]:
            best = (spread, [list(c) for c in cols], heights)

    if best is None:
        raise SystemExit("no layout satisfied the pinned slots and constraints")
    return best


def main():
    cardw, tiles = build_tiles()
    spread, cols, heights = pack(tiles)

    # a column that runs long and ends in a faded widget gets that fade trimmed,
    # so every column bottoms out on the same line
    target = min(heights)
    for ci, col in enumerate(cols):
        over = heights[ci] - target
        label, path, h, faded = col[-1]
        if over > 0 and faded and h - over > FADE + 200:
            magick(path, "-crop", f"{cardw}x{h - over}+0+0", "+repage",
                   "(", "-size", f"{cardw}x{h - over - FADE}", "xc:white",
                   "(", "-size", f"{cardw}x{FADE}", "gradient:white-black", ")", "-append", ")",
                   "-alpha", "off", "-compose", "CopyOpacity", "-composite", path)
            col[-1] = (label, path, h - over, faded)
            heights[ci] = target

    canvas_w = NCOL * cardw + (NCOL + 1) * GAP
    canvas_h = max(heights) + 2 * GAP
    args = ["-size", f"{canvas_w}x{canvas_h}", "xc:" + BG]
    for ci, col in enumerate(cols):
        x, y = GAP + ci * (cardw + GAP), GAP
        for _, path, h, _ in col:
            args += ["(", path, ")", "-geometry", f"+{x}+{y}", "-composite"]
            y += h + GAP
    magick(*args, "master.png")
    print(f"master {canvas_w}x{canvas_h}, column spread {spread}px")

    # one resample per output; two would visibly soften the UI text
    for width, quality, dest in [(canvas_w, 96, MASTER_OUT), (SITE_WIDTH, 98, SITE_OUT)]:
        magick("master.png", "-filter", "Lanczos", "-resize", f"{width}x",
               "-unsharp", "0x0.6+0.6+0.01", "-quality", str(quality), dest)
        print(dest, identify(dest, "%wx%h %b"))
    os.remove("master.png")


if __name__ == "__main__":
    main()
