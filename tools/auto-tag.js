const fs = require('fs');
const path = require('path');

const BASE = path.join(__dirname, '..', 'projects');

// Tags that are auto-applied by the site PHP (getProject.php)
// and should NOT be manually added to manifest.json
const AUTO_APPLIED_TAGS = new Set(['github', '3d', '3D', 'audio', 'video', 'live-site', 'archived-tweet', 'patent', 'youtube']);

// ── Group prefix → tag ───────────────────────────────────────────────
// If a project folder starts with a prefix, it should have that tag
const GROUP_PREFIXES = [
    { prefix: 'play-machine', tag: 'play-machine' },
    { prefix: 'pixel-city', tag: 'pixel-city' },
    { prefix: 'sound-machine', tag: 'sound-machine' },
    { prefix: 'sound-bath', tag: 'sound-bath' },
    { prefix: 'this-or-that-machine', tag: 'this-or-that-machine' },
    { prefix: 'braid-', tag: 'braid' },
    { prefix: 'byt-', tag: 'byt' },
    { prefix: 'fb-', tag: 'facebook' },
    { prefix: 'sq-', tag: 'square' },
    { prefix: 'propel-', tag: 'propel' },
    { prefix: 'sunset-quality', tag: 'sunset quality predictor' },
    { prefix: 'sunset-', tag: 'sunset' },
    { prefix: 'covid-risk', tag: 'covid' },
    { prefix: 'crochet-frame', tag: 'ai' },
    { prefix: 'kiosk-', tag: 'kiosk' },
    { prefix: 'art-of-laura-mancini', tag: 'mom' },
    { prefix: 'imac-g4', tag: 'imac g4' },
    { prefix: 'christmas-menu', tag: 'family' },
    { prefix: 'christmas-menu', tag: 'menu' },
    { prefix: 'cartridge-machine', tag: 'fcc' },
];

// ── Link type → tag ─────────────────────────────────────────────────
// If a project has a link of a certain type, it should have that tag
const LINK_TYPE_TAGS = [
    { type: 'gallery-sign', tags: ['fcc-gallery-show-fall-2025', 'fcc'] },
];

// ── Folder exact match → tag ────────────────────────────────────────
// For folders that match a pattern but aren't covered by prefixes
const FOLDER_PATTERNS = [
    { match: folder => folder.startsWith('fcc-'), tag: 'fcc' },
];

// ── Main ─────────────────────────────────────────────────────────────
const dirs = fs.readdirSync(BASE).filter(d => {
    try {
        return fs.statSync(path.join(BASE, d)).isDirectory()
            && d !== 'TEMPLATE'
            && d !== '.git'
            && d !== 'node_modules';
    } catch { return false; }
});

let totalAdded = 0;
let totalRemoved = 0;
let projectsChanged = 0;

for (const folder of dirs.sort()) {
    const manifestPath = path.join(BASE, folder, 'manifest.json');
    if (!fs.existsSync(manifestPath)) continue;

    let raw;
    try { raw = fs.readFileSync(manifestPath, 'utf8'); } catch { continue; }

    let data;
    try { data = JSON.parse(raw); } catch { continue; }

    const originalTags = [...(data.tags || [])];
    const existingTags = new Set(originalTags);
    const tagsToAdd = new Set();
    const tagsToRemove = new Set();

    // 1. Remove auto-applied tags that shouldn't be in manifest
    for (const tag of existingTags) {
        if (AUTO_APPLIED_TAGS.has(tag) || AUTO_APPLIED_TAGS.has(tag.toLowerCase())) {
            tagsToRemove.add(tag);
        }
    }

    // 2. Group prefix rules
    for (const { prefix, tag } of GROUP_PREFIXES) {
        if (folder.startsWith(prefix) && !existingTags.has(tag)) {
            tagsToAdd.add(tag);
        }
    }

    // 3. Link type rules
    const links = data.links || [];
    for (const rule of LINK_TYPE_TAGS) {
        if (links.some(l => l.type === rule.type)) {
            for (const tag of rule.tags) {
                if (!existingTags.has(tag)) tagsToAdd.add(tag);
            }
        }
    }

    // 4. Folder pattern rules
    for (const { match, tag } of FOLDER_PATTERNS) {
        if (match(folder) && !existingTags.has(tag)) {
            tagsToAdd.add(tag);
        }
    }

    // Don't add tags that are auto-applied
    for (const tag of tagsToAdd) {
        if (AUTO_APPLIED_TAGS.has(tag) || AUTO_APPLIED_TAGS.has(tag.toLowerCase())) {
            tagsToAdd.delete(tag);
        }
    }

    if (tagsToAdd.size === 0 && tagsToRemove.size === 0) continue;

    // Apply changes
    let updatedTags = originalTags.filter(t => !tagsToRemove.has(t));
    updatedTags = [...updatedTags, ...tagsToAdd];
    data.tags = updatedTags;

    const output = JSON.stringify(data, null, 4);
    fs.writeFileSync(manifestPath, output + '\n');

    const parts = [];
    if (tagsToAdd.size > 0) parts.push(`+${[...tagsToAdd].join(', +')}`);
    if (tagsToRemove.size > 0) parts.push(`-${[...tagsToRemove].join(', -')}`);
    console.log(`  ${folder}: ${parts.join('  ')}`);

    totalAdded += tagsToAdd.size;
    totalRemoved += tagsToRemove.size;
    projectsChanged++;
}

if (projectsChanged > 0) {
    console.log(`\n  ${totalAdded} tags added, ${totalRemoved} tags removed across ${projectsChanged} projects.`);
} else {
    console.log('  No tag changes needed.');
}
