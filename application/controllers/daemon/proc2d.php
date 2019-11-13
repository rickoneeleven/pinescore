<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proc2d extends CI_Controller {
    
    public function index() {
        /**
         *     //todo: you need to bring this back inline with the api_ping controller, I can't be having two controller/daemons
         * this daemon is just used for nodes that are OFFLINE, see api_ping in the directory above (I don't know why) for the script that pings IPs that are online
         */
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
    
            $completion_time = substr($this->benchmark->elapsed_time('code_start', 'code_end'),0,-3);
            echo "<br>Script took: ".$completion_time." seconds to complete";
            
            $rand = rand(0,101); //lazy man implementation
            if($completion_time > 60 && $rand > 99) {
                    $this->email->from('script@novascore.io', 'Script');
                    $this->email->to('workforward@novascore.io'); 	    
                    $this->email->subject('ICMP Script Exceeds 60 seconds');
                    $this->email->message('Script took: '.$completion_time.' to complete.');	
                    
                    $this->email->send();
                    echo $this->email->print_debugger();
            }
            
            //sleep(2); removed sleep for offline nodes, as the process is typically delayed waiting for offline nodes
            //to time out during pings
            $timeleft = $end - strtotime('now');
        }
        
        //stuff to do in that last 5 sec gap. Can be used here and in api_ping controller
    }
}
