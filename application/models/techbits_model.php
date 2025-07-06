<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class techBits_model extends CI_model {
  
    public function userIP() {
        $client  = $this->input->server('HTTP_CLIENT_IP');
        $forward = $this->input->server('HTTP_X_FORWARDED_FOR');
        $remote  = $this->input->server('REMOTE_ADDR');

        if(filter_var($client, FILTER_VALIDATE_IP))
        {
                $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
                $ip = $forward;
        }
        else
        {
                $ip = $remote;
        }
        return $ip;
        
    }
    
    function pingPort($host,$port=80,$timeout=6) {
        $fsock = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ( ! $fsock )
        {
                return "errorcode: jizz | error number:$errno | $errstr";
        }
        else
        {
                return TRUE;
        }
    }
    
    function captcha111() {
        $this->load->helper('captcha');
        
        $vals = array(
        'word'	=> '111   ',
        'img_path'	=> './112/captcha/',
        'img_url'	=> base_url().'112/captcha/',

        'img_width'	=> '136',
        'img_height' => 25,
        'expiration' => 7200
        );
        
        $cap = create_captcha($vals);
        return $cap['image'];

    }

    function pingv2($host, $timeout = 2) {
        for ($k = 0 ; $k < 2; $k++) {
            $output = array();
            if(PHP_OS == "WINNT") {
                $com = 'ping -w ' . $timeout . '000 -n 1 ' . escapeshellarg($host);
            } else {
                $com = 'ping -n -w ' . $timeout . ' -c 1 ' . escapeshellarg($host);
            }
            
            $exitcode = 0;
            exec($com, $output, $exitcode);
            
            if ($exitcode == 0 || $exitcode == 1)
            { 
                foreach($output as $cline)
                {
                    if (strpos($cline, 'time') !== FALSE)
                    {
                        $out = (int)ceil(floatval(substr($cline, strpos($cline, 'time=') + 5)));
                        return $out;
                    }
                }
            }
            unset($output);
        }
        
        return FALSE;
    }
    
    public function accountExist($email) {
        $query = $this->db->get_where('user', array('email' => $email), 1, 0);
        return $query;
    }

    public function lookup($host, $type) {
        error_reporting(0);
        if($host==FALSE) $host = "google.co.uk";
        $record_count = 1;
        $type_ = $type;
        $host = str_replace(' ', '', $host);
        $validtypes=array("A","MX","NS","TXT");

        if(!defined("DNS_" . $type) or !in_array($type,$validtypes)){
            $record['ec'] = 1;
            $record['em'] = "Invalid DNS Type!";
        }else{
           $type = constant("DNS_" . $type);
           $rec = dns_get_record($host, $type);
           $values_for_this_type_dns = $this->valuesForType($type);
            if (empty($rec)) {
                $record[$record_count]['ec'] = 1;
                $record[$record_count]['em'] = "No $type_ record setup for ".$host;
            } else {
                foreach ($rec as $num){
                    $record[$record_count]['ec'] = 0;
                    foreach ($values_for_this_type_dns as $title => $value){
                        $record[$record_count][$title] = $num[$value];

                    }$record_count++;
                } 
            }
        }
        return $record;
    }
    
    public function getDomain() {
        $domain = gethostbyaddr($this->userIP());
        if($domain == "localhost") {
            $domain = "localhost.local";
        }
	if($domain==false) {echo "<br><br><br><br><br>MUST BE AN IP ADDRESS, NOT A HOST NAME"; die();}
        $urlMap = array('com', 'co.uk');
		
        $host = "";
        $url = "http://".$domain;
        
        $urlData = parse_url($url);
        $hostData = explode('.', $urlData['host']);
        $hostData = array_reverse($hostData);

        if(array_search($hostData[1] . '.' . $hostData[0], $urlMap) !== FALSE) {
          $host = $hostData[2] . '.' . $hostData[1] . '.' . $hostData[0];
        } elseif(array_search($hostData[0], $urlMap) !== FALSE) {
          $host = $hostData[1] . '.' . $hostData[0];
        }
        
        return $host;
    }
    
    function smtpParse($socket, $message, $response)
    {
        global $mailSettings;
        if($message !== null) fputs($socket, $message . $mailSettings['newLine']);
        
        echo('Message => ' . htmlspecialchars($message) . $mailSettings['newLine'] . '<br />');
        $server_response = '';
        while(substr($server_response, 3, 1) != ' ')
        {
                if(!($server_response = fgets($socket, 256))) return false;
        }
        
        echo('Response => ' . $server_response . '<br /><br />');
        if($response == substr($server_response, 0, 3)) return true;
        else return false;
    }
    
    function smtpMail($smtp_server, $user_ip, $count, $total)
    {
        global $mailSettings;
        $send_to = set_value('to');
        $mailSettings = array('host' => $smtp_server,
                                                    'to' => set_value('to'),
                                                    'from' => 'workforward@pinescore.com',
                                                    'from_domain' => ('pinescore.com'),
                                                    'port' => '25',
                                                    'timeout' => '2',
                                                    'newLine' => "\r\n",
                                                    'ip_from' => $user_ip,
                                                    'return' => 'workforward@pinescore.com');
        
        if(!($socket = @fsockopen($mailSettings['host'], $mailSettings['port'], $errno, $errstr, $mailSettings['timeout'])))
        {
            echo '<font color="red"><strong>Failed to connect to '.$smtp_server." <br>
                error code:$errno<br>
               $errstr </strong></font><br>";

            $body = "Your server has a misconfigured MX record which may be causing emails to go missing. Please contact your IT provider and get them to run a test for any email account on your domain at: ".base_url()."tools/telnet/ for further information.\r\n\r\n This is an automated email generated from ".base_url()." when someone was running tests againt your email address.";
            mail($mailSettings['to'], "MX Record mis-configuration", $body); 
            return false;
        }
        
        if(!$this->smtpParse($socket, null, '220')) return false;
        if($this->smtpParse($socket, 'EHLO ' . $mailSettings['from_domain'], '250'))
        {

                                $this->smtpParse($socket, 'MAIL FROM: <'.$mailSettings['from'].'>', '250');
                                $this->smtpParse($socket, 'RCPT TO: <' . $mailSettings['to'].'>', '250');
                                $this->smtpParse($socket, 'DATA', '354');
                
                $headers = "MIME-Version: 1.0" . $mailSettings['newLine'];
                $headers .= "Connect-Type: text/html; charset=iso-8859-1";
                $headers .= "To: " . $mailSettings['to']  . $mailSettings['newLine'];
                $headers .= "From: " . $mailSettings['from'] .  $mailSettings['newLine'];
                $headers .= "Subject: Test Email #".$count." of ".$total." mx: $smtp_server" . $mailSettings['newLine'];
                $body = "MX Record: $smtp_server \r\n\r\ntest email from pinescore.com to $send_to \r\n\r\ngenerated: ".date('Y-m-d H:i:s')."\r\nfrom: ".$mailSettings['ip_from'].""."\r\n"."\r\n".".";
                $headers .= $body;
        
                $this->smtpParse($socket, $headers, '250');
                $this->smtpParse($socket, 'QUIT', null);
                fclose($socket);

        }
        return false;
    }
    
    function dnsbllookup($ip) {
        $ip = str_replace(' ', '', $ip);
        $dnsbl_lookup=array(
        "b.barracudacentral.org",
        "bl.spamcop.net",
        "bl.spameatingmonkey.net",
        "cbl.abuseat.org",
        "ips.backscatterer.org",
        "pbl.spamhaus.org",
        "sbl.spamhaus.org",
        "xbl.spamhaus.org",
        "zen.spamhaus.org",
        );

        $AllCount = count($dnsbl_lookup);
        $BadCount = 0;
        if($ip)
        {
                echo "<br>";
                $reverse_ip = implode(".", array_reverse(explode(".", $ip)));
                foreach($dnsbl_lookup as $host)
                {
                        if(checkdnsrr($reverse_ip.".".$host.".", "A"))
                        {
                                echo "<span color='#339933'>Listed on ".$reverse_ip.'.'.$host."</span><br/>";
                                $BadCount++;
                        }
                        else
                        {
                                echo "Not listed on ".$reverse_ip.'.'.$host."<br/>";
                        }
                }
        }
        else {
            echo "Empty ip!<br/>";
        }
        echo "<br><strong>This IP has ".$BadCount." bad listings of ".$AllCount."</strong><br><br>";
    }
    
    private function valuesForType($type) {
        switch($type){
            case DNS_A:
                $values_for_this_type_dns=array(
                    "Hostname" => "host",
                    "Type" => "type", 
                    "IP" => "ip"
                );
                break;

            case DNS_MX:
                $values_for_this_type_dns=array(
                    "Hostname" => "host",
                    "Type" => "type", 
                    "Target" => "target", 
                    "Priority" => "pri"
                );
                break;

            case DNS_NS:
                $values_for_this_type_dns=array(
                    "Hostname" => "host",
                    "Type" => "type", 
                    "Target" => "target"
                );
                break;

            case DNS_TXT:
                $values_for_this_type_dns=array(
                    "Hostname" => "host",
                    "Type" => "type", 
                    "Record" => "txt"
                );
                break;
        }
        return $values_for_this_type_dns;
    }

}

?>
