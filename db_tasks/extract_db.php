<?php
$BACKUP_NAME = 'mysql_backup';
# Absolute paths
$filepath = "/home/easyremote/public_html/db_tasks/flat_db_files/" . $BACKUP_NAME . ".tar";
$folderpath = "/home/easyremote/public_html/db_tasks/flat_db_files";

# Check if folder exist
if(!is_dir($folderpath)) {
    echo('Folder does not exist');
}

# Check if folder is writable
if(!is_writable($folderpath)) {
    echo('Folder is not writable');
}

# Check if file exist
if(!file_exists($filepath)) {
    echo('File does not exist');
}
chmod($filepath, 0777);
exec("tar xf $filepath -C $folderpath");
exec("bzip2 -d ".$folderpath."/householdgifts.sql.bz2");
echo "Extraction of householdgifts.sql from .bz2 file complete";

?>