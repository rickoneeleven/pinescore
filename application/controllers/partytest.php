<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class Partytest extends CI_Controller {

        function index() {

            $this->load->model('locks');
            vdebug($this->locks->checkForLock("2.2.2.2"));
        }
    }