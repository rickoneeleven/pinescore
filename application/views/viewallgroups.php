<?php
?>
<div class="full_width">
<br>
<table class="no" cellpadding="5">
        <?php
        foreach($myReports->result() as $row) {
                  echo "<tr><td>$row->name</td>";
                  echo '<td><a href="'.base_url().'nc/viewGroup/'.$row->id.'">View</a></td>';
                  echo '<td><a href="'.base_url().'nc/deleteGroup/'.$row->id.'">Delete</a></td></tr>';
        }
        ?>
</table>
<br>
</div>
