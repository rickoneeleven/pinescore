<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class Partytest extends CI_Controller {

        function index() {
            $this->load->model('email_dev_or_no');

            $array = array(
                'from_class__method'            => 'bitsNbobs__alertDifference'
            );

            vdebug($this->email_dev_or_no->amIonAproductionServer($array));
        }
    }