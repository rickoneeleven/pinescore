<?php
$link_space = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    echo	'<p>';
				foreach($pi->result() as $rowpi) {
                    $public = "Private";
                    if($rowpi->public) $public = "Public";
                    echo '<strong>'.$rowpi->note." ($public)</strong> - ".$rowpi->last_email_status."</br></br>".
                        "<span style=\"letter-spacing: 1.1px; font-weight:bold; color: red;\">pinescore: </span>".
                        "<span style=\"letter-spacing: 2px; font-weight:bold; color: red;\">$rowpi->pinescore</span>";
                    $more_history_link = anchor(base_url()."nc/storyTimeNode/$rowpi->id", '3 Year Log');
				}
	echo	'</p>';
    if($report->num_rows < 1) {
        echo "<hr>";
        echo '<p>No packets dropped in the last 48 hours.   &#128077;</br>
            </br>
            We hold some '.anchor(base_url()."nc/storyTimeNode/$rowpi->id", 'historical').' data that may help?</p>
        <hr>
        <p><button onclick="history.go(-1);">Return </button></p>'.$link_space.$more_history_link;
    } else {
           
        echo '
        <a href="#last">Jump Bottom</a>'.$link_space.$more_history_link.'
        <p><table>
        <tr>
            <td width="200"><strong>IP / Hostname</strong></td>
            <td width="160"><strong>Time</strong></td>
            <td width="75"><strong>Last alert status</strong></td>
            <td width="100"><strong>New update</strong></td>
            <td width="150px"><strong>Day of Week</strong></td>
        </tr>';
        
        foreach($report->result() as $row) {
            //if($row->result == "Offline") $row->result = "<strong>Offline</strong>";
            $tr = '<tr>';
            if($row->result == "Offline") $tr = '<tr bgcolor="#F38E78">';
            echo $tr;
            echo "<td>".$row->ip."</td>";
            echo "<td>".$row->datetime."</td>";
            echo "<td>".$row->result."</td>";
		    echo "<td>".$row->email_sent."</td>";
            echo "<td>".date( "l", strtotime($row->datetime))."</td>";
            echo "</tr>";
        }
        echo "</table></p>";
        echo '<a href="#first">Jump Top</a>'.$link_space.$more_history_link;
	echo '<p>You may see the online/offline status switches much more than the number of emails sent. This is because we will only email you when the host has been down for a period of time rather than each dropped request.</p>';
    }
?>
