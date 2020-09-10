<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proc2d extends CI_Controller {
    
    public function index() {
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();
        
        $this->load->model('email_dev_or_no');
        $start = strtotime('now');
        $end = $start + 60;
        $timeleft = $end - strtotime('now');

        while($timeleft > 5) {
                
            $this->benchmark->mark('code_start');
            
            $this->load->model('techbits_model');
            $this->load->model('icmpmodel');
            $this->load->model('actionicmp');
            
            $filter['status'] = "Offline"; //this process checks through offline hosts
            $ips = $this->icmpmodel->getIPs($filter);
    
            $this->actionicmp->checkICMP($ips);
            
            $this->benchmark->mark('code_end');
    
            $start_time = date('H:i:s');
            $message = "<br>Script start: $start_time || Script took: ".$completion_time." seconds to complete";
            echo $message;
            
            //$rand = rand(0,101); //lazy man implementation
            //if($completion_time > 60 && $rand > 99) {
            if($completion_time > 60 ) {
                    $this->email->from(from_email, 'Script');
                    $this->email->to('workforward@pinescore.com'); 	    
                    $this->email->subject('ICMP Script Exceeds 60 seconds');
                    $this->email->message($message);
                    $email_dev_array = array(
                        'from_class__method'            => 'proc2d__index'
                    );
                    if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
                    echo $this->email->print_debugger();
            }
            
            //sleep(2); removed sleep for offline nodes, as the process is typically delayed waiting for offline nodes
            //to time out during pings
            $timeleft = $end - strtotime('now');
        }
        
        //stuff to do in that last 5 sec gap. Can be used here and in api_ping controller
    }
}
