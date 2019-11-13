<?php
echo '<a href="'. base_url().'tools/hits/" target="_self">Today</a>';
echo "<br>";
echo '<a href="'. base_url().'tools/hits/yesterday" target="_self">Yesterday</a>';
echo "<br>";
$con = mysqli_connect('localhost', 'track_man77', '8=,:%Rza>KXVZeGX', 'tracker');

$today = date("Y-m-d");
if(isset($yesterday)) {
  $today = strtotime ( '-1 day' , strtotime ( $today ) ) ;
  $today = date ( 'Y-m-j' , $today );
}


//$result = mysql_query("SELECT * FROM tbl_tracking WHERE YMD='$today' ORDER BY time DESC") or die("fish"); //this would show all the results, including refer's with no data
$result = mysqli_query($con, "SELECT * FROM tbl_tracking WHERE YMD='$today' AND refer NOT IN ('no data' ) AND hostname NOT IN ('ukspider4.wise-guys.nl') ORDER BY time DESC") or die("trying to retrieve results from table failed, error code: cheese crackers");

$result2 = mysqli_query($con, "SELECT DISTINCT ip FROM tbl_tracking WHERE YMD='$today' AND refer NOT IN ('no data' ) AND hostname NOT IN ('ukspider4.wise-guys.nl')") or die("fish");
$num_rows2 = mysqli_num_rows($result2);

echo "Hits: $num_rows2 \n";

echo "<table width='100%' cellpadding='0' cellspacing='0' border='1'>
<tr>
<th>Time</th>
<th>IP</th>
<th>Hostname</th>
<th>URL</th>
</tr>";

while($row = mysqli_fetch_array($result))
  {
$ip = $row['ip'];
$colour = mysqli_query($con, "SELECT * FROM tbl_colour WHERE ip='$ip'") or die("fish123");
$row2 = mysqli_fetch_array($colour);
$andthecolouris = $row2['colour'];
  echo "<tr bgcolor='$andthecolouris'>";
  echo "<td>" . $row['time'] . "</td>";
  echo "<td>" . $row['ip'] . "</td>";
  echo "<td>" . $row['hostname'] . "</td>";
      echo '<td><a href="' . $row['refer'] . '">' . $row['refer'] . '</a></td>';
  echo "</tr>";
  }
echo "</table></p></p>";


mysqli_close($con);
?>
