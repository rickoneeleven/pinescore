<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BitsNbobs extends CI_Controller {

    public function updateNovaScore() {
        $this->load->model('lemon');
        $this->lemon->novaScoreDaemon();
    }

    /**
     * as well as percent diff, we check for an ms diff, as if the ms in 1 one day, and 2 the next, that's only
     * an increase of 1ms, but it's 100% different
     * we only process and update if existing ping is not 0, if it is, we don't need to alert or update
     * existing ms, and we want to compare it to the ms once the node is back online
     */
    public function updateDailyAverageMs() {
        $IPsAndAverage = $this->getIPsAndAverage();
        $this->db->distinct();
        $this->db->group_by('ip');
        $ping_ip_tableTable =  $this->db->get('ping_ip_table'); 
        foreach($ping_ip_tableTable->result() as $row) {
            if($IPsAndAverage[$row->ip]['todayAverageMs'] != "0") {
                $IPsAndAverage[$row->ip]['yesterdaysAverageMs'] = $row->average_daily_ms;
                $difference = round((1 - $IPsAndAverage[$row->ip]['yesterdaysAverageMs']/$IPsAndAverage[$row->ip]['todayAverageMs'])*100,0);
                echo "<br>yest:".$IPsAndAverage[$row->ip]['yesterdaysAverageMs']." today:".$IPsAndAverage[$row->ip]['todayAverageMs']
                    ." difference: $difference";
                $difference_ms = $IPsAndAverage[$row->ip]['yesterdaysAverageMs'] - $IPsAndAverage[$row->ip]['todayAverageMs'];
                if($difference <="-25" || $difference >="25") {
                    if($difference_ms <="-5" || $difference_ms >="5") {
                        $IPsAndAverage[$row->ip]['difference'] = $difference;
                        $IPsAndAverage[$row->ip]['difference_new'] = $difference;
                        $IPsAndAverage[$row->ip]['ip'] = $row->ip;
                        $IPsAndAverage[$row->ip]['difference_ms'] = $difference_ms;
                        $this->alertDifference($IPsAndAverage[$row->ip]);
                    }
                }
                $update_array = array(
                    'average_daily_ms'  => $IPsAndAverage[$row->ip]['todayAverageMs'],
                );
                $this->db->where('ip', $row->ip);
                $this->db->update('ping_ip_table', $update_array);
            }
        }
        //store new daily average
        //remove "testing new feature, no one other than Ryan should be getting this| //for TESTING ONLY
    }

    /**
     * we've passed IPAndAverage (non-plural), not IP(s)AndAverage, so it contacts only one result
     * we clean the $ms_cleaned so no minus sign is shown on email subjet or it doesn't read right
     */
    private function alertDifference($IPAndAverage) {
        $this->load->model('html_email');
        vdebug($IPAndAverage);
        //THE PROBLEM IS THE DEAMON DOES NOT RUN AS THE LOGGED IN USER
        $this->db->where('ip', $IPAndAverage['ip']);
        $ping_ip_tableTable = $this->db->get('ping_ip_table');
        foreach($ping_ip_tableTable->result() as $row) {
            if($row->alert) {
                $this->email->from('noreply@novascore.io', 'novascore');
                $state = "DECREASED";
                $ms_cleaned = number_format(abs($IPAndAverage['difference_ms']),0);
                if($IPAndAverage['difference'] > "1") $state = "INCREASED";
                $this->email->to($row->alert); 	    
                    $this->email->subject($row->note." round time (ping ms) has $state by [$ms_cleaned]ms");
                $this->email->set_mailtype("html");
                $array['body'] = "You have chosen to receive alerts for: ".$row->note."
                    <br><br>In addition to receiving alerts when the node's online status change, we alert you when the response time changes by a certain percent. This allows you to review any work you may have complete recently to affect this change. Typically the lower the ms, the better.
                    <br>
                    <br>
                    We check this value once daily.<br><br>
                    Yesterdays average ms: ".$IPAndAverage['yesterdaysAverageMs']."<br>
                    Current average ms: ".$IPAndAverage['todayAverageMs']."<br><br> 
                    To get a sense of how things have been historically and how they have developed, you'd benefit from checking our 3 Year Log <a href=\"".base_url()."nc/storyTimeNode/".$row->id."\">here</a>
                    <br>
                    <br>Good luck commander.";
                $this->email->message($this->html_email->htmlFormatted($array));
                $this->email->send();
                echo "<br>EMAIL SENT";
                echo "<br>alert set for: ".$row->ip." | ".$row->alert;
            } else {
                echo "<br>no alert for: $row->ip";
            }
        }
    }

    private function getIPsAndAverage() {
        $this->db->distinct();
        $this->db->group_by('ip');
        $ping_ip_tableTable =  $this->db->get('ping_ip_table'); 
        foreach($ping_ip_tableTable->result() as $row) {
            $todays_average = $this->getTodaysAverageMs($row->ip);
            $array[$row->ip]['todayAverageMs'] = $todays_average;
        }
        return $array;
    }

    /**
     * array_filter() - remove empty values to make average better
     * changed average to actually give "mode" of results, the result which returns most often
     */
    private function getTodaysAverageMs($ip) {
        //get all ms's for today for this IP
        $last_x_hours = "datetime > NOW() - INTERVAL 1 HOUR";
        $this->db->where('ip', $ip);
        $this->db->where($last_x_hours);
        $this->db->order_by('id', 'DESC');
        $ping_result_tableTable = $this->db->get('ping_result_table');
        $average_array = array();
        $mode = FALSE;
        foreach($ping_result_tableTable->result() as $row) {
            array_push($average_array, $row->ms);
            $average_array = array_filter($average_array);

            $values = array_count_values($average_array);
            $mode = array_search(max($values), $values);

            //$average = array_sum($average_array)/count($average_array);
            //$average = round($average,0);
        }
        //return $average;
        return $mode;
    }
}
