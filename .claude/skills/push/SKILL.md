---
name: push
description: Push changes to remote. Use this whenever the user wants to push, deploy, or publish changes.
argument-hint: [commit message]
---

Use the project's push script to commit and deploy changes. Site code is committed and pushed with git (GitHub and leomancini.net); the `projects/` folder is not a git repo and is mirrored to leomancini.net with rsync (`tools/sync-projects push`), which also deletes files on the server that were removed locally. To preview what a push would change in `projects/`, run `./tools/sync-projects push --dry-run`.

Run: `./push "$ARGUMENTS"`

If no commit message argument is provided, generate a short commit message based on the changes made and pass it as the argument.

The push script first runs `tools/compress-screenshots`, which losslessly compresses any PNG files added or changed since its last run under `projects/*/screenshots/` with oxipng (`-o max --strip safe`). Pixels are never altered, so there is no need to compress PNGs by hand before pushing. Resizing (2300px long edge, or 851×1677 for framed phone screenshots) is still a manual step.
