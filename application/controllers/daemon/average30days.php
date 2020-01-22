<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class average30days extends CI_Controller {
    
    public function index() {
        $this->load->model('average30days_model');
        $this->load->model('icmpmodel');
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();

        $all_IPs = $this->icmpmodel->getIPs();
        foreach($all_IPs->result() as $row) {
            $array = array(
                'request_type'              => 'single_ip_one_month',
                'host'                      => $row->ip,
            );
            $months_worth_of_ms = $this->average30days_model->oneMonthsWorth($array);
            $average_for_node = $this->average30days_model->returnMostOccured($months_worth_of_ms);
            if($average_for_node) {
                $array['average_longterm_ms'] = $average_for_node;
                $this->average30days_model->updateAverage($array);
            }
            unset($array);
        }
    }
}
