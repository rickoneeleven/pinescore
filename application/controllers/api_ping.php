<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class api_ping extends CI_Controller {

    //used to be the old ping engine, now just runs to do some score offset stuff
    public function index()
    {
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();
        $this->load->model('lemon');

        $this->lemon->tallyScore(); //sets the number of failures for each client so when the baseline command below runs its gets the correct offset
        $this->lemon->scoreBaseline(); //update baseline in those last 5 secs of the minute, can also add some other tasks here
    }
}
