<div class="full_width">
<br>
<?php
echo "<font color=\"red\">".validation_errors()."</font>"; 

$group_name = "";
$public = 0;
if(isset($group_details)) $public = $group_details->row('public');

if(isset($group_details)) { 
    $group_name = $group_details->row('name'); 
    echo form_open('nc/editgroupform');
    echo form_hidden('group_id', $group_details->row('id'));
} else {
    echo form_open('nc/CreateGroupForm');
}

if(validation_errors() != false) {
    $group_name = set_value('groupname');
    $public = set_value('public');
}

echo '<a href="javascript:history.back()"><strong>Back without changes</strong></a>';
    
$groupname_form_attributes = array(
    'name'  => 'groupname',
    'value' => $group_name,
    'maxlength'     => '16',
);
echo '<p><table>
    <tr  class="darker">
        <td>Group Name: </td>
        <td>'.form_input($groupname_form_attributes).'</td>
        <td>'.form_submit('Submit', 'Process').'</td>
    </tr>
    <tr>
        <td>Make group <a href="'.base_url().'nc/externalAccess">Public</a>?</td>
        <td>'.form_checkbox('public', 1, $public).'</td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="3">Select the items below to include in this Group</td>
    </tr>
        ';

//vdebug($cleaned_ip_ids);
foreach($monitors->result() as $row) {
    if($this->session->userdata('hideOffline') == 1 && 
        date($row->last_online_toggle) < date("Y-m-d H:i:s", strtotime('-72 hours'))) {
    $row->note = $row->note. " <font color=\"#e8640c\">[Offline > 72h Hours]</font> <a class=\"underlined\" href=\"".
            base_url()."nc/whyHidden\">?</a></a>";
    }
    /**
     * set $default_checkbox to null. then if this page has been reloaded from a validation failure then see the comment below as i don't understand how this works. if we're editing a group then $group_details will be set and we need to show already selected members so there is a little if statement to get that working. 
     */
    $default_checkbox = "";
    if(validation_errors() != false) {
        $default_checkbox = set_value($row->id); //i don't understand how this works but it does. how does loading the ID as the default checkbox value return 1?
    } else if(isset($group_details)) { 
        if(in_array($row->id, $cleaned_ip_ids)) {
            $default_checkbox = 1;
        }
    }

    echo "<tr>
        <td>{$row->note}</td>
        <td>".form_checkbox($row->id, 1, $default_checkbox)."</td>
    </tr>";
} 

echo '</table></p>';
echo form_close();
?>
</div>
