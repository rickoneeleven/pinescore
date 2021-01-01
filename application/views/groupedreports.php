<div class="full_width">
<br>
<?php
echo '<font color="red">'.validation_errors().'</font>';

$group_name = '';
$public = 0;
if (isset($groupsTable)) {
    $public = $groupsTable->row('public');
}

if (isset($groupsTable)) {
    $group_name = $groupsTable->row('name'); //wip1 there is no name row
    echo form_open('nc/editgroupform');
    echo form_hidden('group_id', $groupsTable->row('id'));
} else {
    echo form_open('nc/CreateGroupForm');
}

if (validation_errors() != false) {
    $group_name = set_value('groupname');
    $public = set_value('public');
}

echo '<a href="javascript:history.back()"><strong>Back without changes</strong></a>';

$groupname_form_attributes = [
    'name'      => 'groupname',
    'value'     => $group_name,
    'maxlength' => '16',
];
echo '<p><table>
    <tr  class="darker">
        <td>Group Name: </td>
        <td>'.form_input($groupname_form_attributes).'</td>
        <td>'.form_submit('Submit', 'Process').'</td>
    </tr>';
    
if (isset($groupsTable)) {
echo "<tr><td>Update all group email addresses to: (will replace existing)</td>";
    $data = array("name" => "email_addresses",
        "id"             => "email_addresses",
        'style'          => 'height:12px',
    );
    echo "<td>".form_input($data)."</td>";
    echo "<td>comma seperated</td>";
    
    echo "<tr><td>Tick to clear all alert email addresses for nodes in this group</td>";
    echo "<td>".form_checkbox('clear_email_addresses', 1)."</td>";
}

echo "<tr><td>&nbsp;</td><td></td></tr>";
    
    echo '
    <tr>
        <td>Make group <a href="'.base_url().'nc/externalAccess">Public</a>?</td>
        <td>'.form_checkbox('public', 1, $public).'</td>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2">Select the items below to include in this Group</td>
    </tr>
        ';

//vdebug($cleaned_ip_ids);
foreach ($monitors->result() as $row) {
    if ($this->session->userdata('hideOffline') == 1 &&
        date($row->last_online_toggle) < date('Y-m-d H:i:s', strtotime('-72 hours'))) {
        $row->note = $row->note.' <font color="#e8640c">[Offline > 72h Hours]</font> <a class="underlined" href="'.
            base_url().'nc/whyHidden">?</a></a>';
    }
    /**
     * set $default_checkbox to null. then if this page has been reloaded from a validation failure then see the comment below as i don't understand how this works. if we're editing a group then $group_details will be set and we need to show already selected members so there is a little if statement to get that working.
     */
    $default_checkbox = '';
    if (validation_errors() != false) {
        $default_checkbox = set_value($row->id); //i don't understand how this works but it does. how does loading the ID as the default checkbox value return 1?
    } elseif (isset($groupsTable)) {
        if (in_array($row->id, $cleaned_ip_ids)) {
            $default_checkbox = 1;
        }
    }

    echo "<tr>
        <td>{$row->note}</td>
        <td>".form_checkbox($row->id, 1, $default_checkbox).'</td>
    </tr>';
}

echo '</table></p>';
echo form_close();
?>
</div>
