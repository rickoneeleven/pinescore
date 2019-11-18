<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class api_ping extends CI_Controller {

    //running doing ther exact same thing, other than one is filtering for offline nodes! the code's just too god
    //damn SMELLY
    public function index()
    {
        $start = strtotime('now');
        $end = $start + 60;
        $timeleft = $end - strtotime('now');

        while($timeleft > 5) {
                
            $this->benchmark->mark('code_start');
            
            $this->load->model('techbits_model');
            $this->load->model('icmpmodel');
            $this->load->model('actionicmp');
            $this->load->model('lemon');
            
            $filter['status'] = "Online"; //this process_api checks through online hosts only
            $ips = $this->icmpmodel->getIPs($filter);
    
            $this->actionicmp->checkICMP($ips);
            
            $this->benchmark->mark('code_end');
    
            $completion_time = substr($this->benchmark->elapsed_time('code_start', 'code_end'),0,-3);
            echo "<br>Script took: ".$completion_time." seconds to complete";
            
            $rand = rand(0,101); //lazy man implementation
            if($completion_time > 60 && $rand > 99) {
                    $this->email->from('script@novascore.io', 'Script');
                    $this->email->to('workforward@pinescore.com'); 	    
                    $this->email->subject('ICMP Script Exceeds 60 seconds');
                    $this->email->message('Script took: '.$completion_time.' to complete.');	
                    
                    $this->email->send();
                    echo $this->email->print_debugger();
            }
            
            sleep(2);
            $timeleft = $end - strtotime('now');
        }
        $this->lemon->tallyScore(); //sets the number of failures for each client so when the baseline command below runs its gets the correct offset
        $this->lemon->scoreBaseline(); //update baseline in those last 5 secs of the minute, can also add some other tasks here
    }
}
