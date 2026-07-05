#!/usr/bin/env swift
//
// Apply adjustments from bulk-adjust-photos.html to photos at full resolution.
// Preserves iPhone HDR gain maps (the gain map gets the identical warp).
//
// Serve mode (default, used by the Apply button in the viewer):
//   ./apply-photo-adjustments.swift
//     Serves bulk-adjust-photos.html at http://127.0.0.1:8787/ and handles
//     Apply requests. Outputs go to an "adjusted" folder next to the source
//     photos; originals are untouched. The source folder is auto-located by
//     searching ../projects/*/screenshots for the dropped filenames (or use
//     the folder field in the page).
//
// CLI mode:
//   ./apply-photo-adjustments.swift [--quality 0-1] "<values from Copy values>" photo1.jpg [photo2.jpg ...]
//     e.g. "rotate +0.35, skew X -0.2, skew Y 0, persp V +1.2, persp H 0, crop w 92.0%, crop h 92.0%, crop x +1.5%, crop y 0%"
//     Legacy "crop 96.4%" (centered) is also accepted. Photos are modified in
//     place; untouched copies are saved to a backup directory printed at the end.
//
// The transform replicates the viewer's CSS around the image center:
//   perspective(1.5*h) rotateX(perspV) rotateY(perspH) skew(skewX, skewY) rotate(rot)
// followed by the crop rect previewed by the frame.

import Foundation
import CoreImage
import ImageIO
import CoreGraphics
import UniformTypeIdentifiers
import Network
import AppKit

func fail(_ msg: String) -> Never {
    fputs(msg + "\n", stderr)
    exit(1)
}

// ---- Adjustment values ----

struct Adjust {
    var rot = 0.0, skewX = 0.0, skewY = 0.0, perspV = 0.0, perspH = 0.0
    var cropW = 1.0, cropH = 1.0, cropX = 0.0, cropY = 0.0
    var quality = 0.85

    static func parse(_ values: String, quality: Double) -> Adjust? {
        func find(_ label: String) -> Double? {
            let pattern = NSRegularExpression.escapedPattern(for: label) + #" ([-+]?[0-9.]+)"#
            guard let re = try? NSRegularExpression(pattern: pattern),
                  let m = re.firstMatch(in: values, range: NSRange(values.startIndex..., in: values)),
                  let r = Range(m.range(at: 1), in: values) else { return nil }
            return Double(values[r])
        }
        guard let rot = find("rotate"), let skewX = find("skew X"), let skewY = find("skew Y"),
              let perspV = find("persp V"), let perspH = find("persp H") else { return nil }
        var a = Adjust()
        a.rot = rot; a.skewX = skewX; a.skewY = skewY; a.perspV = perspV; a.perspH = perspH
        a.quality = quality
        if let cw = find("crop w"), let ch = find("crop h") {
            a.cropW = cw / 100; a.cropH = ch / 100
            a.cropX = (find("crop x") ?? 0) / 100
            a.cropY = (find("crop y") ?? 0) / 100
        } else if let s = find("crop") {
            a.cropW = s / 100; a.cropH = s / 100
        } else {
            return nil
        }
        return a
    }
}

// ---- Transform math (mirrors the CSS in bulk-adjust-photos.html) ----

typealias Mat = [[Double]]
func mul(_ A: Mat, _ B: Mat) -> Mat {
    var R = Array(repeating: Array(repeating: 0.0, count: 4), count: 4)
    for i in 0..<4 { for j in 0..<4 { for k in 0..<4 { R[i][j] += A[i][k] * B[k][j] } } }
    return R
}

let deg = Double.pi / 180.0

// Column-vector convention in the CSS coordinate space (x right, y down).
func cssMatrix(height: Double, _ a: Adjust) -> Mat {
    let d = 1.5 * height
    let r = a.rot * deg, ax = a.skewX * deg, ay = a.skewY * deg, pv = a.perspV * deg, ph = a.perspH * deg
    let R: Mat = [[cos(r), -sin(r), 0, 0], [sin(r), cos(r), 0, 0], [0, 0, 1, 0], [0, 0, 0, 1]]
    let Sk: Mat = [[1, tan(ax), 0, 0], [tan(ay), 1, 0, 0], [0, 0, 1, 0], [0, 0, 0, 1]]
    let Ry: Mat = [[cos(ph), 0, sin(ph), 0], [0, 1, 0, 0], [-sin(ph), 0, cos(ph), 0], [0, 0, 0, 1]]
    let Rx: Mat = [[1, 0, 0, 0], [0, cos(pv), -sin(pv), 0], [0, sin(pv), cos(pv), 0], [0, 0, 0, 1]]
    let P: Mat = [[1, 0, 0, 0], [0, 1, 0, 0], [0, 0, 1, 0], [0, 0, -1 / d, 1]]
    return mul(P, mul(Rx, mul(Ry, mul(Sk, R))))
}

// Project CSS-space corners (center origin, y down) to CI coords (origin bottom-left, y up).
func corners(width w: Double, height h: Double, _ a: Adjust) -> [CGPoint] {
    let M = cssMatrix(height: h, a)
    func proj(_ x: Double, _ y: Double) -> CGPoint {
        let X = M[0][0] * x + M[0][1] * y + M[0][3]
        let Y = M[1][0] * x + M[1][1] * y + M[1][3]
        let W = M[3][0] * x + M[3][1] * y + M[3][3]
        return CGPoint(x: w / 2 + X / W, y: h / 2 - Y / W)
    }
    // CSS top-left, top-right, bottom-right, bottom-left
    return [proj(-w / 2, -h / 2), proj(w / 2, -h / 2), proj(w / 2, h / 2), proj(-w / 2, h / 2)]
}

func warp(_ img: CIImage, width w: Double, height h: Double, _ a: Adjust) -> CIImage {
    let c = corners(width: w, height: h, a)
    let f = CIFilter(name: "CIPerspectiveTransform")!
    f.setValue(img, forKey: kCIInputImageKey)
    f.setValue(CIVector(cgPoint: c[0]), forKey: "inputTopLeft")
    f.setValue(CIVector(cgPoint: c[1]), forKey: "inputTopRight")
    f.setValue(CIVector(cgPoint: c[2]), forKey: "inputBottomRight")
    f.setValue(CIVector(cgPoint: c[3]), forKey: "inputBottomLeft")
    return f.outputImage!
}

func cropRect(width w: Double, height h: Double, _ a: Adjust) -> CGRect {
    let cw = (w * a.cropW).rounded(.down), ch = (h * a.cropH).rounded(.down)
    // CSS y-down offset -> CI y-up
    let cx = w / 2 + a.cropX * w, cy = h / 2 - a.cropY * h
    return CGRect(x: (cx - cw / 2).rounded(), y: (cy - ch / 2).rounded(), width: cw, height: ch)
}

// ---- Processing ----

let ciContext = CIContext()

func process(inPath: String, outPath: String, _ adj: Adjust) throws {
    let inURL = URL(fileURLWithPath: inPath) as CFURL
    let outURL = URL(fileURLWithPath: outPath) as CFURL

    guard let src = CGImageSourceCreateWithURL(inURL, nil),
          var props = CGImageSourceCopyPropertiesAtIndex(src, 0, nil) as? [CFString: Any],
          let cg = CGImageSourceCreateImageAtIndex(src, 0, [kCGImageSourceShouldCacheImmediately: true] as CFDictionary) else {
        throw NSError(domain: "adjust", code: 2, userInfo: [NSLocalizedDescriptionKey: "cannot read \(inPath)"])
    }
    let orientation = (props[kCGImagePropertyOrientation] as? UInt32) ?? 1
    guard orientation == 1 else {
        throw NSError(domain: "adjust", code: 4, userInfo: [NSLocalizedDescriptionKey: "orientation \(orientation) unsupported: \(inPath)"])
    }

    let w = Double(cg.width), h = Double(cg.height)
    let crop = cropRect(width: w, height: h, adj)
    let warped = warp(CIImage(cgImage: cg), width: w, height: h, adj).cropped(to: crop)
    let colorSpace = cg.colorSpace ?? CGColorSpace(name: CGColorSpace.displayP3)!
    guard let outCG = ciContext.createCGImage(warped, from: crop, format: .RGBA8, colorSpace: colorSpace) else {
        throw NSError(domain: "adjust", code: 3, userInfo: [NSLocalizedDescriptionKey: "render failed: \(inPath)"])
    }

    // Gain map: identical warp at its own resolution (the transform is size-relative)
    var outAux: [CFString: Any]? = nil
    if var aux = CGImageSourceCopyAuxiliaryDataInfoAtIndex(src, 0, kCGImageAuxiliaryDataTypeHDRGainMap) as? [CFString: Any],
       let gmData = aux[kCGImageAuxiliaryDataInfoData] as? Data,
       var desc = aux[kCGImageAuxiliaryDataInfoDataDescription] as? [String: Any],
       let gmW = desc["Width"] as? Int, let gmH = desc["Height"] as? Int,
       let gmBPR = desc["BytesPerRow"] as? Int {
        let gray = CGColorSpaceCreateDeviceGray()
        if let provider = CGDataProvider(data: gmData as CFData),
           let gmImage = CGImage(width: gmW, height: gmH, bitsPerComponent: 8, bitsPerPixel: 8,
                                 bytesPerRow: gmBPR, space: gray, bitmapInfo: CGBitmapInfo(rawValue: 0),
                                 provider: provider, decode: nil, shouldInterpolate: true, intent: .defaultIntent) {
            let gw = Double(gmW), gh = Double(gmH)
            let gmCrop = cropRect(width: gw, height: gh, adj)
            let gmWarped = warp(CIImage(cgImage: gmImage), width: gw, height: gh, adj).cropped(to: gmCrop)
            let ow = Int(gmCrop.width), oh = Int(gmCrop.height)
            var buf = Data(count: ow * oh)
            buf.withUnsafeMutableBytes { raw in
                ciContext.render(gmWarped, toBitmap: raw.baseAddress!, rowBytes: ow,
                                 bounds: gmCrop, format: .L8, colorSpace: gray)
            }
            desc["Width"] = ow
            desc["Height"] = oh
            desc["BytesPerRow"] = ow
            aux[kCGImageAuxiliaryDataInfoData] = buf
            aux[kCGImageAuxiliaryDataInfoDataDescription] = desc
            outAux = aux
        }
    }

    props[kCGImageDestinationLossyCompressionQuality] = adj.quality
    props.removeValue(forKey: kCGImagePropertyPixelWidth)
    props.removeValue(forKey: kCGImagePropertyPixelHeight)

    guard let dest = CGImageDestinationCreateWithURL(outURL, UTType.jpeg.identifier as CFString, 1, nil) else {
        throw NSError(domain: "adjust", code: 5, userInfo: [NSLocalizedDescriptionKey: "cannot create \(outPath)"])
    }
    CGImageDestinationAddImage(dest, outCG, props as CFDictionary)
    if let outAux { CGImageDestinationAddAuxiliaryDataInfo(dest, kCGImageAuxiliaryDataTypeHDRGainMap, outAux as CFDictionary) }
    guard CGImageDestinationFinalize(dest) else {
        throw NSError(domain: "adjust", code: 6, userInfo: [NSLocalizedDescriptionKey: "write failed: \(outPath)"])
    }
}

// ---- Shared paths ----

let fm = FileManager.default
let scriptDir = URL(fileURLWithPath: CommandLine.arguments[0]).resolvingSymlinksInPath().deletingLastPathComponent()
let htmlURL = scriptDir.appendingPathComponent("bulk-adjust-photos.html")
let projectsDir = scriptDir.deletingLastPathComponent().appendingPathComponent("projects")

// ---- Serve mode ----

func httpResponse(_ status: String, contentType: String, body: Data) -> Data {
    var head = "HTTP/1.1 \(status)\r\n"
    head += "Content-Type: \(contentType)\r\n"
    head += "Content-Length: \(body.count)\r\n"
    head += "Access-Control-Allow-Origin: *\r\n"
    head += "Access-Control-Allow-Headers: Content-Type\r\n"
    head += "Cache-Control: no-store\r\n"
    head += "Connection: close\r\n\r\n"
    return head.data(using: .utf8)! + body
}

func jsonResponse(_ obj: [String: Any], status: String = "200 OK") -> Data {
    let data = (try? JSONSerialization.data(withJSONObject: obj)) ?? Data("{}".utf8)
    return httpResponse(status, contentType: "application/json", body: data)
}

struct LocateError: Error { let message: String }

// Locate the folder containing all the given filenames: explicit dir if
// provided, else search projects/*/screenshots for a unique home.
func locateFolder(files: [String], dir: String) -> Result<URL, LocateError> {
    if !dir.isEmpty {
        let base = URL(fileURLWithPath: (dir as NSString).expandingTildeInPath)
        guard files.allSatisfy({ fm.fileExists(atPath: base.appendingPathComponent($0).path) }) else {
            return .failure(LocateError(message: "Not all files exist in \(base.path)"))
        }
        return .success(base)
    }
    guard let projects = try? fm.contentsOfDirectory(at: projectsDir, includingPropertiesForKeys: nil) else {
        return .failure(LocateError(message: "Cannot list \(projectsDir.path)"))
    }
    var homes: [URL] = []
    for p in projects {
        let shots = p.appendingPathComponent("screenshots")
        if files.allSatisfy({ fm.fileExists(atPath: shots.appendingPathComponent($0).path) }) {
            homes.append(shots)
        }
    }
    if homes.count == 1 { return .success(homes[0]) }
    if homes.isEmpty { return .failure(LocateError(message: "Could not find a screenshots folder containing all \(files.count) files; fill in the folder field")) }
    return .failure(LocateError(message: "Files exist in multiple folders (\(homes.map { $0.path }.joined(separator: ", "))); fill in the folder field"))
}

func handleApply(_ body: Data) -> Data {
    guard let json = (try? JSONSerialization.jsonObject(with: body)) as? [String: Any],
          let values = json["values"] as? String,
          let files = json["files"] as? [String], !files.isEmpty else {
        return jsonResponse(["error": "Bad request"], status: "400 Bad Request")
    }
    let quality = (json["quality"] as? Double) ?? 0.85
    guard let adj = Adjust.parse(values, quality: quality) else {
        return jsonResponse(["error": "Could not parse values: \(values)"], status: "400 Bad Request")
    }
    let dir = (json["dir"] as? String) ?? ""
    switch locateFolder(files: files, dir: dir) {
    case .failure(let err):
        return jsonResponse(["error": err.message], status: "404 Not Found")
    case .success(let base):
        let outDir = base.appendingPathComponent("adjusted")
        try? fm.createDirectory(at: outDir, withIntermediateDirectories: true)
        var done: [String] = [], errors: [String] = []
        for f in files {
            do {
                try process(inPath: base.appendingPathComponent(f).path,
                            outPath: outDir.appendingPathComponent(f).path, adj)
                done.append(f)
            } catch {
                errors.append("\(f): \(error.localizedDescription)")
            }
        }
        return jsonResponse(["outDir": outDir.path, "done": done, "errors": errors])
    }
}

func handleRequest(_ request: Data) -> Data {
    guard let headerEnd = request.range(of: Data("\r\n\r\n".utf8)),
          let head = String(data: request[..<headerEnd.lowerBound], encoding: .utf8),
          let requestLine = head.split(separator: "\r\n").first else {
        return httpResponse("400 Bad Request", contentType: "text/plain", body: Data("bad request".utf8))
    }
    let parts = requestLine.split(separator: " ")
    let method = parts.count > 0 ? String(parts[0]) : ""
    let path = parts.count > 1 ? String(parts[1]) : "/"

    if method == "OPTIONS" {
        return httpResponse("204 No Content", contentType: "text/plain", body: Data())
    }
    if method == "GET" && (path == "/" || path == "/index.html") {
        guard let html = try? Data(contentsOf: htmlURL) else {
            return httpResponse("500 Internal Server Error", contentType: "text/plain", body: Data("missing html".utf8))
        }
        return httpResponse("200 OK", contentType: "text/html; charset=utf-8", body: html)
    }
    if method == "POST" && path == "/apply" {
        return handleApply(request[headerEnd.upperBound...])
    }
    return httpResponse("404 Not Found", contentType: "text/plain", body: Data("not found".utf8))
}

func contentLength(_ head: String) -> Int {
    for line in head.split(separator: "\r\n") {
        let kv = line.split(separator: ":", maxSplits: 1)
        if kv.count == 2, kv[0].lowercased() == "content-length" {
            return Int(kv[1].trimmingCharacters(in: .whitespaces)) ?? 0
        }
    }
    return 0
}

func serve(port: UInt16, openBrowser: Bool) -> Never {
    let listener: NWListener
    do {
        let params = NWParameters.tcp
        params.allowLocalEndpointReuse = true
        listener = try NWListener(using: params, on: NWEndpoint.Port(rawValue: port)!)
    } catch {
        fail("Cannot listen on port \(port): \(error.localizedDescription)")
    }
    let queue = DispatchQueue(label: "http")
    listener.newConnectionHandler = { conn in
        conn.start(queue: queue)
        var buffer = Data()
        func readMore() {
            conn.receive(minimumIncompleteLength: 1, maximumLength: 1 << 20) { data, _, isDone, err in
                if let data { buffer.append(data) }
                if err != nil { conn.cancel(); return }
                let complete: Bool
                if let headerEnd = buffer.range(of: Data("\r\n\r\n".utf8)),
                   let head = String(data: buffer[..<headerEnd.lowerBound], encoding: .utf8) {
                    complete = buffer.count - headerEnd.upperBound + buffer.distance(from: buffer.startIndex, to: buffer.startIndex) >= 0
                        && buffer.count >= buffer.distance(from: buffer.startIndex, to: headerEnd.upperBound) + contentLength(head)
                } else {
                    complete = false
                }
                if complete {
                    let response = handleRequest(buffer)
                    conn.send(content: response, completion: .contentProcessed { _ in conn.cancel() })
                } else if isDone {
                    conn.cancel()
                } else {
                    readMore()
                }
            }
        }
        readMore()
    }
    listener.start(queue: queue)
    print("Serving photo adjuster on http://127.0.0.1:\(port)/  (Ctrl-C to stop)")
    if openBrowser {
        NSWorkspace.shared.open(URL(string: "http://127.0.0.1:\(port)/")!)
    }
    dispatchMain()
}

// ---- CLI entry ----

var args = Array(CommandLine.arguments.dropFirst())

if args.isEmpty || args[0] == "--serve" {
    var port: UInt16 = 8787
    if args.count >= 2, let p = UInt16(args[1]) { port = p }
    serve(port: port, openBrowser: !args.contains("--no-open"))
}

var quality = 0.85
if let i = args.firstIndex(of: "--quality") {
    guard i + 1 < args.count, let q = Double(args[i + 1]) else { fail("--quality needs a 0-1 value") }
    quality = q
    args.removeSubrange(i...(i + 1))
}
guard args.count >= 2 else {
    fail("usage: apply-photo-adjustments.swift [--serve [port] | [--quality 0-1] \"<values>\" photo1.jpg ...]")
}
let values = args.removeFirst()
let files = args
guard let adj = Adjust.parse(values, quality: quality) else {
    fail("Could not parse values string: \(values)")
}

let backupDir = fm.temporaryDirectory.appendingPathComponent("photo-adjust-backup-\(UUID().uuidString.prefix(8))")
try! fm.createDirectory(at: backupDir, withIntermediateDirectories: true)

print("rotate \(adj.rot), skew \(adj.skewX)/\(adj.skewY), persp \(adj.perspV)/\(adj.perspH), " +
      "crop \(adj.cropW * 100)x\(adj.cropH * 100)% @ \(adj.cropX * 100)/\(adj.cropY * 100)%, quality \(adj.quality)")

var failures = 0
for f in files {
    guard fm.fileExists(atPath: f) else { print("skip (not a file): \(f)"); continue }
    let url = URL(fileURLWithPath: f)
    let tmp = url.deletingLastPathComponent().appendingPathComponent(".adjust-tmp-\(url.lastPathComponent)")
    do {
        try? fm.removeItem(at: backupDir.appendingPathComponent(url.lastPathComponent))
        try fm.copyItem(at: url, to: backupDir.appendingPathComponent(url.lastPathComponent))
        try process(inPath: f, outPath: tmp.path, adj)
        _ = try fm.replaceItemAt(url, withItemAt: tmp)
        print("done: \(f)")
    } catch {
        failures += 1
        try? fm.removeItem(at: tmp)
        fputs("FAILED \(f): \(error.localizedDescription)\n", stderr)
    }
}

print("Done. Originals backed up to: \(backupDir.path)")
exit(failures == 0 ? 0 : 1)
