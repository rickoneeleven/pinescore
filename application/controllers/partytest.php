<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class Partytest extends CI_Controller {

        function index() {
            $this->input->ip_address();
            
        }
    }