<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class Partytest extends CI_Controller {

        function index() {
            $this->load->model("cron_protect");
            $this->cron_protect->AllowedIPs();
            
        }
    }