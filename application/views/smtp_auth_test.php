<div class="full_width">
<br>
<?php
echo validation_errors(); 

echo '<a href="javascript:history.back()">Back</a>';

if(!set_value('port')) {
    $port = 25;
} else {
    $port = set_value('port');
}

$server_address_form_data = array(
'name'        => 'server_address',
'value'       => set_value('server_address'),
'placeholder' => 'required',
);

$email_to_form_data = array(
'name'        => 'email_to',
'value'       => set_value('email_to'),
'placeholder' => 'required',
);

$port_form_data = array(
'name'        => 'port',
'value'       => $port,
'placeholder' => 'optional',
);

$username_form_data = array(
'name'        => 'username',
'value'       => set_value('username'),
'placeholder' => 'optional',
);

$password_form_data = array(
'name'        => 'password',
'value'       => set_value('password'),
'placeholder' => 'optional',
'type'          => 'password',
);

$email_from_form_data = array(
'name'        => 'email_from',
'value'       => set_value('email_from'),
'placeholder' => 'optional',
);

echo form_open('sausage/smtpAuthTestFORM');
echo '<p><table>
    <tr  class="darker">
        <td>Server Address: </td>
        <td>'.form_input($server_address_form_data).'</td>
    </tr>

    <tr  class="darker">
        <td>Email To: </td>
        <td>'.form_input($email_to_form_data).'</td>
    </tr>

    <tr  class="darker">
        <td>Encryption: </td>
        <td>
        None<input type="radio" name="crypto" value="null_fix"'. set_radio('crypto', 'null_fix', TRUE).' /> | 
        TLS<input type="radio" name="crypto" value="tls"'. set_radio('crypto', 'tls').' /> | 
        SSL<input type="radio" name="crypto" value="ssl"'. set_radio('crypto', 'ssl').' /></td>
    </tr>

    <tr  class="darker">
        <td>Port: </td>
        <td>'.form_input($port_form_data).'</td>
    </tr>
 
    <tr  class="darker">
        <td>Username: </td>
        <td>'.form_input($username_form_data).'</td>
    </tr>

    <tr  class="darker">
        <td>Password: </td>
        <td>'.form_input($password_form_data).'</td>
    </tr>

    <tr  class="darker">
        <td>Email From: </td>
        <td>'.form_input($email_from_form_data).'</td>
    </tr>

    <tr>
        <td align="center" colspan="2"><input align="center" type="submit" name="mysubmit" onClick="this.form.submit(); this.disabled=true; this.value=\'Processing…\'; " value="Email"/></td>
    </tr>
        ';


echo '</table></p>';
echo form_close();
if($returned_output) {
    echo "<br>What went down:<br>";
    echo "<code>";
    echo $returned_output;
    echo "</code>";
}
?>
</div>
