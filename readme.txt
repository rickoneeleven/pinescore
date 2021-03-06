Worlds number one ICMP monitoring solution
CodeIgniter 2.2.0 Project

--------------------------------------------------
Database
mysqladmin -u root -p create pinescore
mysql -u root -p pinescore < database_structure.sql

--------------------------------------------------
Setup
cp ./application/config/config.php.example ./application/config/config.php
cp ./application/config/database.php.example ./application/config/database.php

vim ./application/config/config.php
$dev_domain_tld = ".test";

$config['encryption_key'] = 'breasts';
:wq

vim application/models/email_dev_or_no.php
	update the various email bits to decide if you get alerted in your development environment

vim ./application/config/database.php
	'username' => 'db_user',
	'password' => 'harrylikesherchainberofsecrets',
	'database' => 'pinescore',
:wq

sudo vim /etc/mysql/my.cnf
paste the below at the bottom
[mysqld]
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
sudo service mysql restart
otherwise you get this error: https://stackoverflow.com/questions/34115174/error-related-to-only-full-group-by-when-executing-a-query-in-mysql

OTHER:
the pine engine wasn't quite working on webadmin Server Configuration, Website options until I changed:
PHP script execution mode: CGI Wrapper
Maximum PHP script run time: 3600
this all speaks to the fact the ping engine needs to be rewritten. 
--------------------------------------------------
crontabs
#creates score stats for offset and baseline, only needs to run once daily
0 0 * * * lynx --dump http://pinescore.com/api_ping/ > /dev/null 2>&1

#daily clean up tasks and general db maintenance
26 04 * * * lynx --dump https://pinescore.com/api_nightly/onceAday > /dev/null 2>&1

#colate shortterm group scores into long term and flush shortterm rows. runs a few times to make sure it runs, duplicate protection built in
23,33,44 01 * * * lynx --dump http://pinescore.com/api_ping/longTermGroupScores > /dev/null 2>&1

#Cleans up tables and logs shortterm group scores
24 * * * * lynx --dump https://pinescore.com/api_nightly/ > /dev/null 2>&1
*/5 * * * * lynx --dump https://pinescore.com/api_nightly/flushPingResultTable > /dev/null 2>&1

#delete files from pinescore.com/111 older than 30 days
10 09 * * * find /home/pinescore/public_html/111/* -mtime +30 -type f -delete

#more touching of files we want to remain
11 11 11 * * touch /home/pinescore/public_html/111/ns_*

#calculate the "pinescore" and update database for each IP
* * * * * lynx --dump https://pinescore.com/daemon/bitsNbobs/updatepinescore > /dev/null 2>&1

#daily average ms update and alert
31 08 * * * lynx --dump https://pinescore.com/daemon/bitsNbobs/updateDailyAverageMs > /dev/null 2>&1

#monthly average ms update (updates every 4 hours)
35 00,06,12,18 * * * lynx --dump https://pinescore.com/daemon/average30days > /dev/null 2>&1

#engine.pinescore.com
* * * * * cd /home/pinescore/domains/engine.pinescore.com/public_html && php artisan schedule:run >> /dev/null 2>&1

#other notes
when exporting DB structure, remove the failed_jobs table if it's in export, as that gets created as part migration
in the engine project, and if it already exists, for some reason something fails, even though migrations should
drop tables first...