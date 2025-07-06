<?php

if ($this->session->flashdata('message')) {
    echo '<div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px;">'
        . $this->session->flashdata('message')
        . '</div>';
}

if ($this->session->flashdata('error_message')) {
    echo '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">'
        . $this->session->flashdata('error_message')
        . '</div>';
}

if (validation_errors()) {
     echo '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">'
        . validation_errors()
        . '</div>';
}

echo form_open('user_options/optionsForm');
?>

<h3>User Preferences</h3>
<table class="no">
    <tr>
        <td>Hide Offline Nodes > 72 Hours</td>
        <td>
            <?php

            $hideOfflineChecked = ($this->session->userdata('hideOffline') == 1 || $this->session->userdata('hideOffline') === true);
            echo form_checkbox('hideOffline', '1', $hideOfflineChecked);
            ?>
        </td>
        <td><small>(Hides monitors that have been offline for more than 3 days from the main dashboard)</small></td>
    </tr>
    <tr>
        <td>Default setting for (<a href="<?php echo base_url('nc/externalAccess'); ?>">Public access</a>) when adding new nodes</td>
        <td>
            <?php

            $defaultEaChecked = ($this->session->userdata('default_EA') == 1 || $this->session->userdata('default_EA') === true);
            echo form_radio('default_EA', '1', $defaultEaChecked) . ' Yes ';
            echo form_radio('default_EA', '0', !$defaultEaChecked) . ' No';
            ?>
        </td>
        <td><small>(Sets the initial Public Access state when you add a new monitor)</small></td>
    </tr>
</table>

<hr style="margin: 20px 0;">

<h3>Account Security</h3>
<table class="no">
    <tr>
        <td style="width: 150px;">Change Email Address</td>
        <td>
            <?php

            $email_value = set_value('email') ? set_value('email') : $this->session->userdata('user_email');
            $email_input_data = [
                'type'  => 'email',
                'id'    => 'email',
                'name'  => 'email',
                'value' => $email_value
            ];
            echo form_input($email_input_data);
            ?>
        </td>
    </tr>
    <tr>
        <td>Change Password</td>
        <td>
            <?php
            $password_input_data = [
                'type' => 'password',
                'id'   => 'password',
                'name' => 'password',
                'value' => '',
                'placeholder' => 'Leave blank to keep current password'
            ];
             echo form_input($password_input_data);
            ?>
        </td>
    </tr>
    <tr>
        <td>Confirm New Password</td>
        <td>
             <?php
            $password_confirm_data = [
                'type' => 'password',
                'id'   => 'password_confirm',
                'name' => 'password_confirm',
                'value' => ''
            ];
             echo form_input($password_confirm_data);
            ?>
        </td>
    </tr>
</table>

<br>
<?php

$submit_button_data = [
    'name'    => 'mysubmit',
    'value'   => 'Save Changes',
    'class'   => 'greenButton',
    'onclick' => "this.form.submit(); this.disabled=true; this.value='Processing...';"
];
echo form_submit($submit_button_data);

echo form_close();
?>

<hr style="margin: 20px 0;">

<!-- --- Alert Disabling Section --- -->
<h3>Email Alert Preferences</h3>
<div style="margin-bottom: 15px;">
    <?php

    $alerts_disabled = (isset($alerts_are_currently_disabled) && $alerts_are_currently_disabled === true);
    $disable_status_text = isset($alert_disable_status) ? htmlspecialchars($alert_disable_status) : 'N/A';

    if ($alerts_disabled) {

        echo '<p style="color: orange;"><strong>Email alerts are currently disabled until: ' . $disable_status_text . '</strong></p>';

        echo '<a href="' . base_url('user_options/enable_alerts_now') . '" class="greenButton" onclick="return confirm(\'Are you sure you want to re-enable email alerts now?\');">Re-enable Alerts Now</a>';
    } else {

        echo '<p style="color: green;"><strong>Email alerts are currently enabled.</strong></p>';

        $disable_duration = 2;
        echo '<a href="' . base_url('user_options/disable_alerts_temporarily?duration=' . $disable_duration) . '" class="redButton" onclick="return confirm(\'Are you sure you want to disable email alerts for ' . $disable_duration . ' hours?\');">Disable Alerts for ' . $disable_duration . ' Hours</a>';
        echo '<br><small>(Useful during planned maintenance)</small>';
    }
    ?>
</div>

<hr style="margin: 20px 0;">