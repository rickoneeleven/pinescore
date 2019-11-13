<?php
    echo form_open('user_options/optionsForm');
    if($this->session->userdata('hideOffline')==1) {
        echo'<input type="checkbox" checked="checked" name="hideOffline" value="1">Hide Offline Nodes > 72 Hours<br><br>';
    } else {
        echo'<input type="checkbox" name="hideOffline" value="1">Hide Offline Nodes > 72 Hours<br><br>';
    }
    echo "<hr>";
    echo "<h3>Change Email Address</h3>";
    echo '<input type="text" id="email" name="email" value="'.$this->session->userdata('user_email').'"/>';
    echo "<h3>Change Password</h3>";
    echo '<input type="password" id="password" name="password" value=""/>';
    echo " Confirm: ";
    echo '<input type="password" id="password_confirm" name="password_confirm" value=""/>';
    echo "<br>";
    echo "<br>";
    echo "<hr>";
    echo "<br>";
    echo '<table class ="no">';
        echo "<tr>";
            echo '<td>Default setting for (<a href="'.base_url().'nc/externalAccess">Public access</a>) 
                when adding new nodes</td>';         
            echo '<td><input type="radio" name="default_EA" value="1" '.($this->session->userdata('default_EA') == 1 ? 'checked' : '').'>Yes 
                <input type="radio" name="default_EA" value="0" '.($this->session->userdata('default_EA') == 0 ? 'checked' : '').'>No</td>';
        echo "</tr>";
    echo "</table>";

    echo "</br></br><hr></br>";
    echo '<input type="submit" name="mysubmit" onClick="this.form.submit(); this.disabled=true; 
        this.value=\'Processing…\'; " value="Process Changes"/>';
    echo "<br><br>".$this->session->flashdata('message');
    echo '<font color="red"/>'.validation_errors().'</font>';

?>
