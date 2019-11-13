<?php
$color_nscore = "black";
$color_ms = "black";
$link_space = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
?>
<?php
echo '<a href="#last">Jump Bottom</a>'.$link_space;
?>
<p>
<table>
<tr>
    <th width="150px">Node</th>
    <th width="100px">NovaScore</th>
    <th width="100px">Rount Time</th>
    <th width="150px">Logged</th>
    <th width="150px">Day of Week</th>
</tr>
<?php
foreach($historic_novaScoreTable->result() as $row) {
    $color_nscore = "black";
    $color_ms = "black";
    if($row->novaScore < 50) $color_nscore = "#664400";
    if($row->novaScore < 0) $color_nscore = "red";
    if($row->ms > 100) $color_ms = "#664400";
    if($row->ms > 1000) $color_ms = "red";
    if($row->ms == 0) $row->ms = "Offline";
    echo "<tr>
            <td>$row->ip</td>
            <td><font color=\"$color_nscore\">$row->novaScore</font></td>
            <td><font color=\"$color_ms\">$row->ms</font></td>
            <td>$row->logged</td>
            <td>".date( "l", strtotime($row->logged))."
        </tr>";
}
?>
</table>
<?php
echo '<p><a href="#first">Jump Top</a>'.$link_space;
