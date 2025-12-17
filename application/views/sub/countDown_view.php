<!-- Old countdown timer removed - AJAX handles updates now -->
<?php
if(!isset($group_id)) $group_id = "";
$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "</br>";
$week_day_number = date('N');
$smiles = "";
while($week_day_number > 0) {
    $smiles = $smiles.":) ";
    $week_day_number--;
}

echo "<div style='display: flex; justify-content: space-between; align-items: center; width: 100%;'>";

echo "<div>";
echo "<strong>Happy ".date('l')." ".$smiles."</strong> $spaces";

echo "</div>";

echo "<div id='healthMetrics' style='text-align: right; margin-right: 20px;'>";
$cycles = isset($cycles_per_minute) ? $cycles_per_minute : 0;
echo "<span style='color: " . ($cycles >= 5 ? "green" : ($cycles >= 2 ? "orange" : "red")) . "'>";
echo "cycles/min: " . $cycles . "</span> | ";
echo "<span style='color: " . ($jobs_per_minute < 1000 ? "orange" : "green") . "'>";
echo "pings/min: " . number_format($jobs_per_minute) . "</span> | ";
echo "<span style='color: " . ($failed_jobs_past_day == 0 ? "green" : "red") . "'>";
echo "failed jobs (24h): " . $failed_jobs_past_day . "</span> | ";
echo "<span style='color: " . ($engine_status == 'active' ? "green" : "red") . "'>";
echo "engine: " . $engine_status . "</span>";
echo "</div>";

echo "</div>";
?>