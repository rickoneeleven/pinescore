<div style="float:left; clear: left;">
<?php
if(empty($groupscore)) $groupscore = "<font color=\"red\">new group, currently calculating. 
process can take up to an hour.</font>";
$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$public = '(Private)';
if ($groupsTable->row('public')) {
    $public = '(Public)';
}
echo "<h3> Visibility: $public $spaces</h3>";
?>
</div>
</br></br>
