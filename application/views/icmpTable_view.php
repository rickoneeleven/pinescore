<?php
$count = 0;
$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo '<div id="icmp_table" data-group-id="'.(isset($group_id) ? $group_id : '').'">';
echo '<br><table class="nowrap">
    <thead>
    <tr class="darker">
    <td></td>
    <td>Currently being monitored: '."$spaces".'<a class="powerful" href="'.base_url().'tools/pingAdd/">[Home]</a></td>
    <td><a class="powerful" href="'.current_url().'">[Refresh]</a></td>';
    echo "<td></td>";
if ($this->uri->slash_segment(2) == 'popOut/') {
    if ($this->uri->slash_segment(3) == 'sapiens/' || $this->uri->slash_segment(3) == 'stop/' && $this->uri->slash_segment(4) != '/') {
        echo '<td><a class="powerful" href="'.base_url().'nc/viewGroup/'.$this->uri->slash_segment(4).'">[Back to Group]</a> </td>';
    }
} elseif (isset($group_id)) {
    echo '<td><a id="autoRefreshToggle" class="powerful" href="#" style="color: red;">[Auto Refresh]</a> </td>';
} else {
    echo '<td><a id="autoRefreshToggle" class="powerful" href="#" style="color: red;">[Auto Refresh]</a> </td>';
}
echo '
    <td><a id="fullscreenToggle" class="powerful" href="#" style="color: red;">[Full Screen]</a></td>
    <td></td>
    <td></td>
    <td></td>';
    if ($owner_matches_table) { //last td for darker formatting, only see this column if you're owner
        echo '<td></td>';
    }
echo '
    <td></td>';
if ($owner_matches_table) { //last td for darker formatting, only see this column if you're owner
    echo '<td><a class="powerful" href="'.base_url().'tools/export_csv/'.(isset($group_id) ? $group_id : '').'" target="_blank">[CSV]</a></td>';
}
echo '
    </tr>
    <tr>
    <td>#</td>
    <td width="180px" ><strong>Note</strong></td>
    <td width="100px" ><strong>Status</strong></td>
    <td width="85px" ><strong>pinescore</strong> <a class="underlined" title="[90-100 = Solid], [50-89 = Good], [0-49 Suboptimal], [< 0 = ...]" 
        href="'.base_url().'nc/whatIspinescore">?</a> </td>
    <td width="70px" ><strong>Recent ms/Last online</strong></td>
    <td width="85px" ><strong>LTA</strong> <a class="underlined" title="Longer Term Average. Response time averaged from over a months worth of data" href="'.base_url().'nc/whatIsLongTermAverage">?</a> </td>
    <td width="145px" ><strong>Trace</strong></td>
    <td width="145px" ><strong>Last Checked</strong></td>
    <td width="180px" ><strong>IP</strong></td>';
    if ($owner_matches_table) { //only show alert column header if logged in
        echo '
        <td width="200px" ><strong>Email Add. Alert</strong> (comma, separated)</td>';
    }
    echo '
    <td width="80px" ><strong><a title="Public" href="'.base_url().'nc/externalAccess">Public</a></strong></td>';
if ($owner_matches_table) { //only show action button column header if logged in
    echo '<td width="155px" ><strong>Actions</strong></td>';
}
echo '</tr>
    </thead>
    <tbody id="icmpTableBody">';
foreach ($ips as $ip => $latest) {
    $difference_percent = 0;
    $ms = $latest['ms'].'ms';
    $now = new DateTime();
    $last_online_toggle = new DateTime($latest['last_online_toggle']); // or e.g. 2016-01-01 21:00:02
    $wentoffline = strtotime($latest['last_online_toggle']);
    $wentoffline = date('d-m-Y - H:i:s', $wentoffline);
    $difference_ms = $latest['average_longterm_ms'] - $latest['ms'];
    if ($latest['ms'] > '0') {
        $difference_percent = round((1 - $latest['average_longterm_ms'] / $latest['ms']) * 100, 0);
    }
    if (!$latest['average_longterm_ms']) {
        $latest['average_longterm_ms'] = '??';
    } //no LTA defined yet, new node must have been added. so using current_ms

    $original_ip = $ip;
    echo form_open(base_url().'tools/icmpEdit#'.$latest['id']);
    if ($latest['public'] == 1) {
        $latest['public'] = 'Yes';
        $ea_enabled = true;
        $ea_disabled = false;
    } else {
        $latest['public'] = 'No';
        $ea_enabled = false;
        $ea_disabled = true;
    }
    ++$count;
    
    // Check if lastcheck is older than 5 minutes
    $lastcheck_time = new DateTime($latest['lastcheck']);
    $time_difference = $now->diff($lastcheck_time);
    $minutes_difference = $time_difference->days * 24 * 60 + $time_difference->h * 60 + $time_difference->i;
    
    if ($minutes_difference > 5) {
        $tr = '<tr style="background-color: yellow; color: black;">'; // Set row to black if older than 5 minutes
    } else {
        if ($latest['last_email_status'] == 'Online' && $latest['count'] == 1) {
            $tr = '<tr class="orange">';
        } else {
            if ($latest['count'] > 1) {
                $tr = '<tr class="transition">';
            } else {
                if ($latest['last_email_status'] == 'Offline' && $now->diff($last_online_toggle)->days === 0) {
                    $tr = '<tr class="red">';
                } elseif ($latest['last_email_status'] == 'Offline' && $now->diff($last_online_toggle)->days === 1) {
                    $tr = '<tr class="onedayred">';
                } elseif ($latest['last_email_status'] == 'Offline' && $now->diff($last_online_toggle)->days > 1) {
                    $tr = '<tr class="overonedayred">';
                } elseif ($latest['lta_difference_algo'] != 0 && $latest['lta_difference_algo'] < -100) {
                    $tr = '<tr class="orange">';
                } elseif ($latest['lta_difference_algo'] != 0 && $latest['lta_difference_algo'] >= -100 && $latest['lta_difference_algo'] < 0) {
                    $tr = '<tr class="green">';
                } elseif ($latest['score'] < 50 && $latest['score'] > -1) {
                    $tr = '<tr class="pink">';
                } elseif ($latest['score'] < 0) {
                    $tr = '<tr class="darkerpink">';
                } else {
                    $tr = '<tr class="hover">';
                }
            }
        }
    }

    if (isset($edit) && $latest['id'] == $this->input->post('id_edit')) { //because in foreach loop and we onloy want to edit one record
        $data = ['name' => 'note',
            'id'        => 'note',
            'value'     => $latest['note'],
        ];
        $latest['note'] = form_input($data);

        $data = ['name' => 'ip',
            'id'        => 'ip',
            'value'     => $ip,
        ];
        $ip = form_input($data);

        $data = ['name' => 'alert',
            'id'        => 'alert',
            'value'     => $latest['alert'],
        ];
        $latest['alert'] = form_input($data);

        $data = [
            'name'    => 'ea',
            'id'      => 'ea',
            'value'   => 1,
            'checked' => $ea_enabled,
            'style'   => 'margin:5px',
        ];
        $data2 = [
            'name'    => 'ea',
            'id'      => 'ea',
            'value'   => 0,
            'checked' => $ea_disabled,
            'style'   => 'margin:5px',
        ];

        $latest['public'] = 'Yes:'.form_radio($data);
        $latest['public'] = $latest['public'].' No:'.form_radio($data2);
    } else {
        $num_email_alerts = substr_count($latest['alert'], ',') + 1;
        if ($num_email_alerts > 1) {
            $latest['alert'] = $num_email_alerts.' configured alerts';
        }
    }

    $report = 'tools/report/'.$latest['id'];
    echo $tr;
    echo "<td>$count</td>";

    if (!isset($edit)) { //only want the report hyperlink to be active if we're not editing, otherwise it clicks through
        echo '<td> '.anchor($report, $latest['note']).'</td>';
    } else {
        echo '<td> '.$latest['note'].'</td>';
    }

    $count_with_color = $latest['count'];
    if ($count_with_color) {
        if ($latest['last_email_status'] == 'Offline' && $latest['count_direction'] == 'Up') {
            $count_with_color = '<font color="green"><strong>'.$count_with_color.'</strong></font>';
        } elseif ($latest['last_email_status'] == 'Offline' && $latest['count_direction'] == 'Down') {
            $count_with_color = '<font color="red"><strong>'.$count_with_color.'</strong></font>';
        } elseif ($latest['last_email_status'] == 'Online' && $latest['count_direction'] == 'Down') {
            $count_with_color = '<font color="green"><strong>'.$count_with_color.'</strong></font>';
        } elseif ($latest['last_email_status'] == 'Online' && $latest['count_direction'] == 'Up') {
            $count_with_color = '<font color="red"><strong>'.$count_with_color.'</strong></font>';
        }
    }

    echo'<td> '.$latest['last_email_status'].' ['.$count_with_color.']&nbsp;</td>
        <td> ';
    $fifteenminsago = date('Y-m-d H:i:s', strtotime('-15 minute'));

    if (strtotime($latest['pinescore_change']) > strtotime($fifteenminsago)) {
        echo '<font color="red"><strong>'.$latest['score'].'</strong></font>';
    } else {
        echo $latest['score'];
    }
    echo '<a name="'.$latest['id'].'"></td> <!--<a name tag so the href link when editing a field auto jumps down when pressing the edit and confirm button, rather than manually having the scroll-->
        <td> ';
    if ($latest['last_email_status'] == 'Offline') {
        echo ' '.$wentoffline;
    } else {
        echo $ms;
    }
    echo '</td>
        <td> '.anchor(base_url().'nc/storyTimeNode/'.$latest['id'], $latest['average_longterm_ms'].'ms').
        '</td>
        <td>'.anchor(base_url().'traceroute/routesthathavebeentraced/'.$ip, " (tr) ".'&nbsp;').'</td>
        <td> '.$latest['lastcheck'].'&nbsp;</td>
        <td>'.$ip.'</td>';
    if ($owner_matches_table) { //only show action buttons if logged in
        echo '
            <td> '.$latest['alert'].'</td>';
    }
    echo '
        <td> '.$latest['public'].'</td>';

    if ($owner_matches_table) { //only show action buttons if logged in
        echo'<td>';

        if (isset($edit) && $this->input->post('id_edit') == $latest['id']) { //think we pay an overhead for this, seems to slow form load down
            echo form_hidden('id', $this->input->post('id_edit')).
                form_hidden('group_id', $this->input->post('group_id')).
                form_hidden('ip_existing', $original_ip).
                form_submit('action', 'Update')
                .form_submit('action', 'Delete');
        }

        if (isset($delete) && $this->input->post('id') == $latest['id']) { //think we pay an overhead for this, seems to slow form load down
            echo form_hidden('id', $this->input->post('id')).
                form_submit('action', 'Confirm Delete');
        }

        if (!isset($edit) && !isset($delete)) {
            $this->session->set_userdata('breadcrumbs', uri_string());
            if (!isset($group_id)) { //$group_id comes from array passed to this view
                if ($this->uri->segment(3) === 'sapiens') {
                    $group_id = $this->uri->segment(4);
                } else {
                    $group_id = 0;
                }
            }
            echo form_hidden('note_edit', $latest['note'])
                .form_hidden('id_edit', $latest['id'])
                .form_hidden('ip_edit', $ip)
                .form_hidden('group_id', $group_id)
                .form_submit('action', 'Edit');
        } else {
            echo form_submit('action', 'Reset');
        }

        echo form_close().'</td>';
    }
    echo '</tr>';
}
echo '</tbody>
</table>';
?>
<br>
Key:
<br>
<table class="nowrap">
    <tr class="darker">
    <td>Standard column color, node is in a normal state</td>
    </tr>
    <tr class="red">
    <td>Node stopped responding to requests within 24 hours</td>
    </tr>
    <tr class="onedayred">
    <td>Node stopped responding to requests 24-48 hours ago</td>
    </tr>
    <tr class="overonedayred">
    <td>Node stopped responding to requests more than 48 hours ago</td>
    </tr>
        <tr class="orange">
    <td>We take an average on the node response times over the last month, and we've found this node is currently responding <strong>slower</strong> than normal. Or it has dropped a single request.</td>
    </tr>
        <tr class="green">
    <td>We take an average on the node response times over the last month, and we've found this node is currently responding <strong>quicker</strong> than normal</td>
    </tr>
    <tr class="transition">
    <td>Node has been online, and is starting to drop packets. Or - Node was offline and has just started responding. It's in limbo.</td>
    </tr>
    <tr class="pink">
    <td>pinescore is below 50, node appears to be suffering intermittent drops. Worth investigating.</td>
    </tr>
    <tr class="darkerpink">
    <td>pinescore is below 0, node is dropping many packets, indicative of a problem.</td>
    </tr>
    <tr>
        <td>pinescore (the score of the node), <strong>bold</strong> and <font color="red"><strong>Red</strong></font>: 
            score has dropped in last 15 minutes</td>
    </tr>
        <tr>
        <td>[0] (the number in the box) <br>
            if [><font color="red"><strong>0</strong></font>]: node is moving towards an offline state<br>
            if [><font color="green"><strong>0</strong></font>]: node is moving towards an online state<br>
        </td>
    </tr>
    <tr>
        <td>Group Score: percent of nodes with a score higher than 50. If 90 or above you'll see a smily face too. 
        You'll only see a group score if you're in the live view of one of your groups.</td>
    </tr>
</table>
<p>[ ] The brackets at the end of the <strong>Status</strong> show how many times the ping has returned a different result from the current status.
Once this is > 10 it will be considered a change in <strong>Status</strong> rather than a temporary hiccup.</p>
<p><strong>pinescore</strong> is our score up to 100 for the stability of the host over the last 48 hours.</p>
</div>