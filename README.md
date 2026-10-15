#### site/
* Site viewer code (searchable list, projects page, etc)
* Serves from /projects
* Git repo ignores the /projects folder
* Should be pushed to leomancini.net and GitHub 

#### projects/
* Contains project metadata, text content, and media
* Separate git repo that is ignored by the parent site/ git repo
* Should only be pushed to leomancini.net

<br>

`To push changes to GitHub & leomancini.net, run ./push from top level directory`

`./push` first rebuilds `site/cache/projectsList.json` by running `tools/update-cache`, which uses the PHP CLI (`brew install php`). No local web server is needed. If the cache build fails, nothing is pushed.