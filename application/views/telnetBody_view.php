<?php     
    echo '<font size="5" color="green">Your IP: '.$user_ip."</font><br><br>";
    if(isset($_POST['ip'])) { //we have specified an IP, rather than using the one getUserIP() has found
	    $user_ip = $_POST['ip'];
    }
    
    echo form_open(base_url().'tools/telnet_formDNSBL').
	    'E-mail Blacklist Lookup<br>'.
            form_label('IP:', 'ip').'<input type="text" name="ip" value="'.$user_ip.'"> <input type="submit"> <a href=".">Refresh</a><br><br>
	    
	    </form>
    ';
    
    if(isset($dnsme)) {
	    $this->techbits_model->dnsbllookup($_POST['ip']);
    }
    
    echo '<font color="red">'.validation_errors().'</font>';
    echo 'Telnet Test with full debug output<br>';
            if($start ==1) {
                echo "<br>Test results below, please click here to run <a href=".base_url().'tools/telnet/'." style=\"color:red; text-decoration: underline;\">another test</a>.";
                if(!isset($server['1']['Type'])) {
                    echo "<br>Sorry, no MX records setup for this domain.<br><br>";
                } else {
                    $count = 0;
                    $total = count($server);
                    foreach($server as $record_num => $record) {
                        echo "<br>++++++++++++++++++++++++++++++++++++++++++++++++++++<br>";
                        echo 'Starting debug @ '.date('Y-m-d H:i:s').'<br>';
                        echo "<strong>".$record['Type']." -> ".$record['Target']." [Priority: ". $record['Priority']."]</strong><br>";
                        print_r($this->techbits_model->smtpMail($record['Target'], $user_ip, $count+1, $total));
                        $count ++;
                        echo 'Finished debug @ '.date('Y-m-d H:i:s');
                    }
                    echo "<br>We've sent a test email to each mx record setup for this domain to confirm they are all receiving correctly. A total of <strong>".$count."</strong> test email(s) have been sent. Please review the output above for any issues.<br><br>Test results above, please click here to run <a href=".base_url().'tools/telnet/'." style=\"color:red; text-decoration: underline;\">another test</a>.<br><br><br>";
                }
            } else {
                echo form_open(base_url().'tools/telnet_formTelnet').
                form_label('E-mail Address to test:', 'email') .'<input type="text" name="to" value="'.set_value('to').'">
                <input type="hidden" name="ip_telnet" value="'.$user_ip.'">';
                if(isset($captcha_requested)) {
                 
                    echo form_label("Verify Image Below: ", "verify");
                    $data = array("name" => "verify",
                                  "id" => "verify",
                                  "value" => ""
                                  );
                    echo form_input($data);
                    echo form_hidden('image', '111');
                }
                echo '<input type="submit" name="mysubmit" onClick="this.form.submit(); this.disabled=true; this.value=\'Processing…\'; " value="Email"/>';
                //echo ' '.form_submit('mysubmit', 'Email');
                if(isset($captcha_requested)) {
                    echo "<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$cap_img;
                }
                echo '</form>';
            }
    
    function getDomainFromEmail($email)
    {
    // Get the data after the @ sign
    $this->load->model('email_dev_or_no');
    $domain = substr(strrchr($email, "@"), 1);
    
    return $domain;
    }
    
    /*$this->email->from(from_email, 'novascore telnet');
			    $this->email->to('rick1_11@hotmail.com'); 	    
			    $this->email->subject('telnet abuse check');
			    $this->email->message('to: '.$mailSettings['to'].' from: '.$mailSettings['from']." body: test email from novascore.io \r\n\r\ngenerated: ".date('Y-m-d H:i:s')."\r\nfrom: ".$mailSettings['ip_from'].""."\r\n"."\r\n".".");	
			    $email_dev_array = array(
                        'from_class__method'            => 'telnetBody_view__index'
                    );
			    if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();*/
    

    
    
?>
