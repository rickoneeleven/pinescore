<?php
echo "<div id=\"ping_add_container\" style=\"float:left;\">";
echo "<div id=\"pa_left\">";
echo $this->session->flashdata('message'); //this gets set with a successful form submission

?>
   <form action="<?php echo base_url()?>tools/pingAdd_formProcess" method="post" accept-charset="utf-8" onsubmit="this.elements['ipSubmit'].disabled=true;">
<?php
if(($this->session->userdata('user_email')!="")) {//is user logged in check [true] else
    echo "<table>";
    echo "<tr class=\"darker\"><td colspan=\"2\">New hostname or IP to be monitored:"; if(empty($ips)) {echo "<span class=\"b\"> <font color=\"red\">Start Here</font> </span>";} echo "</td></tr>";

    echo form_label("<tr><td>Name of Node*: </td> <td>", "note");
    $data = array("name" => "note",
        "id" => "note",
        "value" => set_value('note'),
        'style'       => 'height:12px',
    );
    echo form_input($data);
    echo " (Friendly Destination Name)";
    echo "</td></tr>";

    echo form_label("<tr><td>IP or Hostname*: </td> <td>", "ip");
    $data = array("name" => "ip",
        "id" => "ip",
        "value" => set_value('ip'),
        'style'       => 'height:12px',
    );
    echo form_input($data);
    echo " Your IP: <font color=\"red\">".$user_ip."</font>";
    echo "</td></tr>";

    echo form_label("<tr><td>Email to alert: </td> <td>", "email");
    $data = array("name" => "email",
        "id" => "email",
        'style'       => 'height:12px',
        "value" => set_value('email'),
    );
    echo form_input($data);
    echo " (multiple addresses seperated with a comma)";
    echo "</td></tr>";

    if(isset($captcha_requested)) {

        echo form_label("<tr><td>Verify Image: </td> <td>", "verify");
        $data = array("name" => "verify",
            "id" => "verify",
            "value" => ""
        );
        echo form_input($data);

        echo "</td></tr>";

        echo "<tr><td>Image: </td> <td>".$cap_img."</td></tr>";

        echo form_hidden('image', '111');
    }

    echo form_hidden('public', $this->session->userdata('default_EA'));
    if($this->uri->segment(2) === "viewGroup") {
        //we're viewing a group so we need to pass a hidden form field, so in the form processing, we can add
        //this node directly to this group
        echo form_hidden('viewGroup', $this->uri->segment(3));
    }
    echo "<tr><td>";
    echo '<input type="submit" name="ipSubmit" onClick="this.form.submit(); this.disabled=true; this.value=\'Processing…\'; " value="Add"/>';
    echo "</td>";

    echo '<td>If your IP keeps changing (dynamic), use <a target="_blank" href="http://www.noip.com/remote-access">Free DDNS</a> and add the hostname</td>';

    echo"</tr></table>";
    echo form_close();
} else {
    echo "<p>".anchor('auth/user/register', 'Register')." to find out your NovaScore and get alerts when your node drops.
        <font color=\"red\">Your IP: $user_ip </font></p>";
    $gitExcludes111Folder = "https://novascore.io/";
    if(base_url() != "https://dev.novascore.io/") $gitExcludes111Folder = base_url();
    echo '<div>';
    echo '<div class="screenshots"><a href="'.$gitExcludes111Folder.'111/ns_homepage1.png"><img src="'.$gitExcludes111Folder.'111/ns_homepage1.png" border="1" width="283px"></a></div>';
    echo '<div class="screenshots"><a href="'.$gitExcludes111Folder.'111/ns_homepage2.png"><img src="'.$gitExcludes111Folder.'111/ns_homepage2.png" border="1" width="283px"></a></div>';
    echo '<div class="screenshots"><a href="'.$gitExcludes111Folder.'111/ns_homepage3.png"><img src="'.$gitExcludes111Folder.'111/ns_homepage3.png" border="1" width="283px"></a></div>';
    echo '</div>';
    //<p>Your IP:".$user_ip. "</p>";
}
echo "</div>"; //left
if(($this->session->userdata('user_email')!="")) {//is user lgged in check [true] else
    $count_for_table = 0;
    if(isset($myReports)) {
        foreach ($myReports->result() as $row) {
            $count_for_table++;
            $id_and_name[$count_for_table]['id'] = $row->id;
            $id_and_name[$count_for_table]['name'] = $row->name;
        }
    }
    echo "<div id=\"pa_right\">
        <table width=\"100%\">
        <tr class=\"darker\">
            <td width=\"25%\">Grouped Monitors</td>
            <td width=\"25%\"><a class=\"powerful\" href=\"".base_url()."nc/createOrModifyGroup/create\">New Group</a></td>
            <td width=\"25%\"><a href=\"".base_url()."tools/pingAdd/\">Show All Nodes</a></td>
            <td width=\"25%\"></td>
        </tr>";

    if(isset($id_and_name[1])) {
        echo "<tr>
                <td>"; if(isset($id_and_name[1])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[1]['id'].'">'.$id_and_name[1]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[5])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[5]['id'].'">'.$id_and_name[5]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[9])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[9]['id'].'">'.$id_and_name[9]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[13])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[13]['id'].'">'.$id_and_name[13]['name'].'</a>';} echo"</td>";
        echo "</tr>";
    }

    if(isset($id_and_name[2])) {
        echo "<tr>
                <td>"; if(isset($id_and_name[2])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[2]['id'].'">'.$id_and_name[2]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[6])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[6]['id'].'">'.$id_and_name[6]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[10])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[10]['id'].'">'.$id_and_name[10]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[14])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[14]['id'].'">'.$id_and_name[14]['name'].'</a>';} echo"</td>";
        echo "</tr>";
    }

    if(isset($id_and_name[3])) {
        echo "<tr>
                <td>"; if(isset($id_and_name[3])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[3]['id'].'">'.$id_and_name[3]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[7])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[7]['id'].'">'.$id_and_name[7]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[11])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[11]['id'].'">'.$id_and_name[11]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[15])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[15]['id'].'">'.$id_and_name[15]['name'].'</a>';} echo"</td>";
        echo "</tr>";
    }

    if(isset($id_and_name[4])) {
        echo "<tr>
                <td>"; if(isset($id_and_name[4])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[4]['id'].'">'.$id_and_name[4]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[8])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[8]['id'].'">'.$id_and_name[8]['name'].'</a>';} echo"</td>
                <td>"; if(isset($id_and_name[12])) { echo '<a href="'.base_url().'nc/viewGroup/'.$id_and_name[12]['id'].'">'.$id_and_name[12]['name'].'</a>';} echo"</td>
                <td>"; if($count_for_table > 15) { echo '<a href="'.base_url().'sausage/viewAllGroups">more...</a>';} echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
    echo "</div>"; //right
}

echo "</div>"; //main
echo "<br><br><br><br><br>".validation_errors(); 


?>
