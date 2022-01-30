<?php
echo "</br>";
if(isset($group_id))  {
    if($groupscore > 89) {
        $groupicon = "&#128512;";
    } else {
        $groupicon = "&#128566";
    }
    echo "<strong>$group_name</strong> | Group Scores: "; 
    //echo "<span style='font-size:25px;'>$groupicon</span>$spaces $spaces";
    foreach ($group_monthly_scores->result() as $row)
    {
        $newDate = date('M', strtotime($row->created_at));
        echo $newDate."(".$row->score."), ";
    }
    echo "Today($groupscore)";
}
?>