<div style="float:left; clear: left;">
<?php
$public = '(Private)';
if ($groupsTable->row('public')) {
    $public = '(Public)';
}
echo '<h3>Group Name: '.$groupsTable->row('name')." $public</h3>";
?>
</div>
