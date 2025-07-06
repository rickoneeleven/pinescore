<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BitsNbobs extends CI_Controller {

    public function updatepinescore() {
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();

        $this->load->model('lemon');
        $this->lemon->pinescoreDaemon();
    }

    public function checkChangeOfCurrentMsAgainstLTA() {        
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();
        
        $this->db->distinct();
        $this->db->group_by('ip');
        $ping_ip_tableTable =  $this->db->get('ping_ip_table'); 
        foreach($ping_ip_tableTable->result() as $row) {
            $ms_now = $this->getRecentAverage($row->ip);
            if($ms_now && $row->last_email_status == "Online") {
                $difference = round((1 - $row->average_longterm_ms/$ms_now)*100,0);
                echo "<br>IP: ".$row->ip."  LTA:".$row->average_longterm_ms." now:".$ms_now
                    ." difference: $difference";
                $difference_ms = $row->average_longterm_ms - $ms_now;
                if($difference <="-25" || $difference >="25") {
                    if($difference_ms <="-5" || $difference_ms >="5") {
                        $data_for_alertDifference[$row->ip]['difference'] = $difference;
                        $data_for_alertDifference[$row->ip]['difference_new'] = $difference;
                        $data_for_alertDifference[$row->ip]['ip'] = $row->ip;
                        $data_for_alertDifference[$row->ip]['difference_ms'] = $difference_ms;
                        $data_for_alertDifference[$row->ip]['ms_now'] = $ms_now;
                        $this->alertDifference($data_for_alertDifference[$row->ip]);
                    }
                }
            }
        }
    }

    private function alertDifference($IPAndAverage) {
        $this->load->model('html_email');
        $this->load->model('email_dev_or_no');
        $this->load->model('get_emailalerts');

        $this->db->where('ip', $IPAndAverage['ip']);
        $ping_ip_tableTable = $this->db->get('ping_ip_table');
        foreach($ping_ip_tableTable->result() as $row) {

            $recentAlert = $this->get_emailalerts->recentAlert($row->id);
            $email_addresses_set_for_alerts = $this->get_emailalerts->returnAlertsFromIDasString($row->id);
            if($email_addresses_set_for_alerts && !$recentAlert) {
                $this->email->from(from_email, 'pinescore');
                $state = "BETTER";
                $ms_cleaned = number_format(abs($IPAndAverage['difference_ms']),0);
                if($IPAndAverage['difference'] > "1") $state = "WORSE";
                $this->email->to($email_addresses_set_for_alerts); 	    
                    $this->email->subject("$state: ".$row->note);
                $this->email->set_mailtype("html");
                $array['body'] = $row->note."
                    <br><br>
                    Long Term Average ms: ".$row->average_longterm_ms."<br>
                    Current average ms: ".$IPAndAverage['ms_now']."<br><br> 
                    3 Year Log <a href=\"".base_url()."nc/storyTimeNode/".$row->id."\">here</a>
                    <br>";
                $this->email->message($this->html_email->htmlFormatted($array));
                $email_dev_array = array(
                    'from_class__method'            => 'bitsNbobs__alertDifference'
                );
                if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
                echo "<br>EMAIL SENT";
                echo "<br>alert set for: ".$row->ip." | ".$email_addresses_set_for_alerts;
                
            } else {
                echo "<br>no alert for: $row->ip";
            }
        }
    }

    private function getRecentAverage($ip) {

        $this->db->where('ip', $ip);
        $this->db->order_by('id', 'DESC');
        $ping_result_tableTable = $this->db->get('ping_result_table');
        $average_array = array();
        $mode = FALSE;
        foreach($ping_result_tableTable->result() as $row) {
            array_push($average_array, $row->ms);
        }
        $average_array = array_filter($average_array);
        $median = $this->getMedian($average_array);
        return $median;
    }

    private function getMedian($arr) {

        if(!is_array($arr)){
            throw new Exception('$arr must be an array!');
        }

        if(empty($arr)){
            return false;
        }

        asort($arr);

        $arr = array_values($arr);

        $num = count($arr);

        $middleVal = floor(($num - 1) / 2);

        if($num % 2) { 
            return $arr[$middleVal];
        } 

        else {

            $lowMid = $arr[$middleVal];
            $highMid = $arr[$middleVal + 1];

            return (($lowMid + $highMid) / 2);
        }
    }
}
