#### site/
* Site viewer code (searchable list, projects page, etc)
* Serves from /projects
* Git repo ignores the /projects folder
* Should be pushed to leomancini.net and GitHub 

#### projects/
* Contains project metadata, text content, and media
* Not a git repo (media is too heavy for git) and ignored by the parent site/ git repo
* Synced to leomancini.net with rsync via `tools/sync-projects` (`./push` and `./pull` call it)
* `./push` mirrors the folder, so files deleted locally are deleted on the server; `./pull` never deletes or overwrites newer local files

<br>

`To push changes to GitHub & leomancini.net, run ./push from top level directory`

`./push` first rebuilds `site/cache/projectsList.json` by running `tools/update-cache`, which uses the PHP CLI (`brew install php`). No local web server is needed. If the cache build fails, nothing is pushed.