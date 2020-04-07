Worlds number one ICMP monitoring solution
CodeIgniter 2.2.0 Project

--------------------------------------------------
Database
mysqladmin -u root -p create novascore
mysql -u root -p novascore < database_structure.sql

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
--------------------------------------------------
crontabs
#the star of the show, check if nodes are online/offline. We have a deamon for each state - "Online" / "Offline".
* * * * * lynx --dump https://novascore.test/api_ping/ > /dev/null 2>&1
* * * * * lynx --dump https://novascore.test/daemon/proc2d/ > /dev/null 2>&1

#daily clean up tasks and general db maintenance
26 04 * * * lynx --dump https://novascore.test/api_nightly/onceAday > /dev/null 2>&1

#Think this deletes old ping results, we need to keep this table as small as we can, so adding/updating nodes is quick
24 * * * * lynx --dump https://novascore.test/api_nightly/ > /dev/null 2>&1                                            

#delete files from novascore.test/111 older than 30 days - and touches the index file to stop it being deleted
10 09 * * * touch /home/novascore/pinescore.git/111/index.php && find /home/novascore/pinescore.git/111/* -mtime +30 -type f -delete

#more touching of files we want to remain
11 11 11 * * touch /home/novascore/pinescore.git/111/ns_*

#calculate the "novaScore" and update database for each IP
* * * * * lynx --dump https://novascore.test/daemon/bitsNbobs/updateNovaScore > /dev/null 2>&1

#daily average ms update and alert
31 08 * * * lynx --dump https://novascore.test/daemon/bitsNbobs/updateDailyAverageMs > /dev/null 2>&1

#monthly average ms update (updates every 4 hours)
35 00,06,12,18 * * * lynx --dump https://novascore.test/daemon/average30days > /dev/null 2>&1