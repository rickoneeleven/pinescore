<?php

$link_space = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo	'<p>';
                foreach ($pi->result() as $rowpi) {
                    $public = 'Private';
                    if ($rowpi->public) {
                        $public = 'Public';
                    }
                    echo '<strong>'.$rowpi->note." ($public)</strong> $link_space $link_space <font size =\"22\" color=\"green\">".$rowpi->last_email_status.'</font></br></br>'.
                        '<span style="letter-spacing: 1.1px; font-weight:bold; color: red;">pinescore: </span>'.
                        "<span style=\"letter-spacing: 2px; font-weight:bold; color: red;\">$rowpi->pinescore</span>";
                    $more_history_link = anchor(base_url()."nc/storyTimeNode/$rowpi->id", '3 Year Log');
                }
    echo	'</p>';
    if ($report->num_rows < 1) {
        echo '<hr>';
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
            <td width="100px"><strong>Day of Week</strong></td>
            <td width="160"><strong>Time</strong></td>
            <td width="600"><strong>Ping result</strong></td>
        </tr>';

        foreach ($report->result() as $row) {
            $tr = '<tr>';
            $last_state = 'Offline';
            if (
                strpos($row->email_sent, 'dropped (2/10') ||
                strpos($row->email_sent, 'dropped (3/10') ||
                strpos($row->email_sent, 'dropped (4/10') ||
                strpos($row->email_sent, 'dropped (5/10') ||
                strpos($row->email_sent, 'dropped (6/10') ||
                strpos($row->email_sent, 'dropped (7/10') ||
                strpos($row->email_sent, 'dropped (8/10') ||
                strpos($row->email_sent, 'dropped (9/10') ||
                strpos($row->email_sent, 'dropped (10/10') ||
                $row->email_sent == 'Node is now <strong>Offline</strong>') {
                $tr = '<tr bgcolor="#F38E78">';
                $last_state = 'Online';
            }
            echo $tr;
            echo '<td>'.$row->ip.'</td>';
            echo '<td>'.date('l', strtotime($row->datetime)).'</td>';
            echo '<td>'.$row->datetime.'</td>';
            echo '<td>'.$row->email_sent.'</td>';
            echo '</tr>';
        }
        echo '</table></p>';
        echo '<a href="#first">Jump Top</a>'.$link_space.$more_history_link;
    }
