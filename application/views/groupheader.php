<div style="float:left; clear: left;">
<?php 
$public = "(Private)";
if($group_details->row('public')) $public = "(Public)";
echo "<h3>Group Name: ".$group_details->row('name')." $public</h3>";
?>
</div>
