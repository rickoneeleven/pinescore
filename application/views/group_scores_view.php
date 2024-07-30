<?php
echo "</br>";
if(isset($group_id))  {
    if(!is_string($groupscore)) $groupscore = '<span style="color: red;">.. new group created, group score is still being calulated, please allow 24 hours</span>';
    echo "<strong>$group_name</strong> | Group Scores: Today($groupscore), "; 
    foreach ($group_monthly_scores->result() as $row)
    {
        $newDate = date('M-y', strtotime('-1 day', strtotime($row->created_at)));
        echo $newDate."(".$row->score."), ";
    }
}
?>