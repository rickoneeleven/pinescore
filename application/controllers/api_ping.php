<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class api_ping extends CI_Controller {

    public function index()
    {
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();
        $this->load->model('lemon');

        $this->lemon->tallyScore();
        $this->lemon->scoreBaseline();
    }
    
    public function longTermGroupScores() {
        $this->load->model("cron_protect");
        $this->load->model('groupscore');

        $this->cron_protect->AllowedIPs();
        $this->groupscore->CalulateLongTermGroupScore();
    }
}
