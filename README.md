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