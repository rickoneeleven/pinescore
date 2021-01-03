<div style="float:left; clear: left;">
<?php
if(empty($groupscore)) $groupscore = "<font color=\"red\">new group, currently calculating. 
process can take up to an hour.</font>";
$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$public = '(Private)';
if ($groupsTable->row('public')) {
    $public = '(Public)';
}
echo '<h3>Group Name: '.$groupsTable->row('name')." $public $spaces
Group Score: $groupscore</h3>";
?>
</div>
