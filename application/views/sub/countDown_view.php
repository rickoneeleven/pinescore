<script type = "text/javascript">
/*author Philip M. 2010*/

var timeInSecs;
var ticker;

function startTimer(secs){
timeInSecs = parseInt(secs)-1;
ticker = setInterval("tick()",1000);   // every second
}

function tick() {
var secs = timeInSecs;
if (secs>0) {
timeInSecs--;
}
else {
clearInterval(ticker); // stop counting at zero
// startTimer(60);  // remove forward slashes in front of startTimer to repeat if required
}

document.getElementById("countdown").innerHTML = secs;
}

startTimer(10);  // 60 seconds

</script>
<?php
if(!isset($group_id)) $group_id = "";
$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "</br>";
echo "</br>";
$week_day_number = date('N');
$smiles = "";
while($week_day_number > 0) {
    $smiles = $smiles.":) ";
    $week_day_number--;
}
echo "<strong>Happy ".date('l')." ".$smiles."</strong> $spaces";

if($action == 'refresh') {
    echo '<span id="countdown" style="font-weight: bold;">10</span>&nbsp;
    <a href="'.base_url().'tools/popOut/stop/'.$group_id.'">Stop Auto Refresh</a>';
    echo "
        <script>
            setTimeout(function(){alert(\"Please manually refresh the page, to resume Auto Refresh\")},20000);
        </script>
        ";
} elseif($group_id != "")  {
    echo '<a href="'.base_url().'tools/popOut/sapiens/'.$group_id.'">Resume Auto Refresh</a>';
} else {
    echo '<a href="'.base_url().'tools/popOut/">Resume Auto Refresh</a>';
}
?>
