---
name: push
description: Push changes to remote. Use this whenever the user wants to push, deploy, or publish changes.
argument-hint: [commit message]
---

Use the project's push script to commit and deploy changes.

Run: `./push "$ARGUMENTS"`

If no commit message argument is provided, generate a short commit message based on the changes made and pass it as the argument.

The push script first runs `tools/compress-screenshots`, which losslessly compresses any new or changed PNG files under `projects/*/screenshots/` with oxipng (`-o max --strip safe`). Pixels are never altered, so there is no need to compress PNGs by hand before pushing. Resizing (2300px long edge, or 851×1677 for framed phone screenshots) is still a manual step.
