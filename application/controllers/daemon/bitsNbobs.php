<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BitsNbobs extends CI_Controller {

    public function updatepinescore() {
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();

        $this->load->model('lemon');
        $this->lemon->pinescoreDaemon();
    }

    /**
     * as well as percent diff, we check for an ms diff, as if the ms in 1 one day, and 2 the next, that's only
     * an increase of 1ms, but it's 100% different
     * we only process and update if existing ping is not 0, if it is, we don't need to alert or update
     * existing ms, and we want to compare it to the ms once the node is back online
     */
    public function checkChangeOfCurrentMsAgainstLTA() {        
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();
        
        $this->db->distinct();
        $this->db->group_by('ip');
        $ping_ip_tableTable =  $this->db->get('ping_ip_table'); 
        foreach($ping_ip_tableTable->result() as $row) {
            $ms_now = $this->getRecentAverage($row->ip);
            if($ms_now) { //if returns zero, it means the node is offline, we don't need an alert or division by zero errors
                $difference = round((1 - $row->average_longterm_ms/$ms_now)*100,0);
                echo "<br>IP: ".$row->ip."  LTA:".$row->average_longterm_ms." now:".$ms_now
                    ." difference: $difference";
                $difference_ms = $row->average_longterm_ms - $ms_now;
                if($difference <="-25" || $difference >="25") {
                //if($difference <="-1" || $difference >="1") {
                    if($difference_ms <="-5" || $difference_ms >="5") {
                    //if($difference_ms <="-1" || $difference_ms >="1") {
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

    /**
     * we've passed IPAndAverage (non-plural), not IP(s)AndAverage, so it contacts only one result
     * we clean the $ms_cleaned so no minus sign is shown on email subjet or it doesn't read right
     */
    private function alertDifference($IPAndAverage) {
        $this->load->model('html_email');
        $this->load->model('email_dev_or_no');
        $this->load->model('get_emailalerts');
        //vdebug($IPAndAverage);

        $this->db->where('ip', $IPAndAverage['ip']);
        $ping_ip_tableTable = $this->db->get('ping_ip_table');
        foreach($ping_ip_tableTable->result() as $row) {
            $email_addresses_set_for_alerts = $this->get_emailalerts->returnAlertsFromIDasString($row->id);
            if($email_addresses_set_for_alerts) {
                $this->email->from(from_email, 'pinescore');
                $state = "DECREASED";
                $ms_cleaned = number_format(abs($IPAndAverage['difference_ms']),0);
                if($IPAndAverage['difference'] > "1") $state = "INCREASED";
                $this->email->to($email_addresses_set_for_alerts); 	    
                    $this->email->subject($row->note." round time (ping ms) has $state by [$ms_cleaned]ms");
                $this->email->set_mailtype("html");
                $array['body'] = "You have chosen to receive alerts for: ".$row->note."
                    <br><br>In addition to receiving alerts when the node's online status change, we alert you when the response time changes by a certain percent. This allows you to review any work you may have complete recently to affect this change. Typically the lower the ms, the better.
                    <br>
                    <br>
                    We check this value once daily.<br><br>
                    Long Term Average ms: ".$row->average_longterm_ms."<br>
                    Current average ms: ".$IPAndAverage['ms_now']."<br><br> 
                    To get a sense of how things have been historically and how they have developed, you'd benefit from checking our 3 Year Log <a href=\"".base_url()."nc/storyTimeNode/".$row->id."\">here</a>
                    <br>
                    <br>Good luck commander.";
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

    /**
     * array_filter() - remove empty values to make average better
     * changed average to actually give "mode" of results, the result which returns most often
     */
    private function getRecentAverage($ip) {
        //ping_result_table is quite pruned these days, so we don't need a datetime filter
        $this->db->where('ip', $ip);
        $this->db->order_by('id', 'DESC');
        $ping_result_tableTable = $this->db->get('ping_result_table');
        $average_array = array();
        $mode = FALSE;
        foreach($ping_result_tableTable->result() as $row) {
            array_push($average_array, $row->ms);
        }
        $average_array = array_filter($average_array); //filter out zeros (failed pings)
        $median = $this->getMedian($average_array);
        return $median;
    }
    
    /**
 * A PHP function that will calculate the median value
 * of an array
 * 
 * @param array $arr The array that you want to get the median value of.
 * @return boolean|float|int
 * @throws Exception If it's not an array
 */
    private function getMedian($arr) {
        //Make sure it's an array.
        if(!is_array($arr)){
            throw new Exception('$arr must be an array!');
        }
        //If it's an empty array, return FALSE.
        if(empty($arr)){
            return false;
        }
        //sort the array
        asort($arr);
        
        //reset keys to match new order
        $arr = array_values($arr);
        
        //Count how many elements are in the array.
        $num = count($arr);
        //Determine the middle value of the array.
        $middleVal = floor(($num - 1) / 2);
        //If the size of the array is an odd number,
        //then the middle value is the median.
        if($num % 2) { 
            return $arr[$middleVal];
        } 
        //If the size of the array is an even number, then we
        //have to get the two middle values and get their
        //average
        else {
            //The $middleVal var will be the low
            //end of the middle
            $lowMid = $arr[$middleVal];
            $highMid = $arr[$middleVal + 1];
            //Return the average of the low and high.
            return (($lowMid + $highMid) / 2);
        }
    }
}
