# pinescore
Worlds number one ICMP monitoring solution

+++++++++++++TODO
++Stuff required before checking out novascore from git repo for prod server
-add to readme all files and steps required for databases connections

-need to add crons to readme, and get the relevant ones running locally
-export SQL structure and add to git as a file, and part of this read me to restore
-tracking database is 60MB, that needs trimming automatically or removing all together prefrably
-did you need the tracking database to start the local devleopment? probs, delete creds if you want and see if it breaks, still think you should just remove the tracking
-add the fix for https://stackoverflow.com/questions/34115174/error-related-to-only-full-group-by-when-executing-a-query-in-mysql to readme, longer term we'd wanna do it correct way, the first answer
-add trackerlib.php example file to setup for readme
-add C:\laragon\www\pinescore\application\views\hits_view.php.example to readme setup
-set email alerts so if they run from local dev server, they don't send out emails
-do a git clone after updating all the readme and follow instructions to make sure you can clone from fresh and get it working good