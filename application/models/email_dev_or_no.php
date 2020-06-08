<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class email_dev_or_no extends CI_model {

    // array(
    //      'from_class__method'               =>
    //          api_nightly -> index
    //          api_ping -> index
    //          user -> mailConfirmation
    //          user -> forgotFormProcess
    //          bitsNbobs -> alertDifference
    //          proc2d -> index
    //          sausage -> smtpAuthTestFORM
    //          icmpmodel -> emailAlert
    //          lemon -> icmpControl
    //          telnetBody_view //this code is commented out
    //      '
    // )
    public function amIonAproductionServer($array) {
        if(ENVIRONMENTv2 == "production") {
            return true; 
        } else {
            return $this->shouldDevSiteEmail($array);
        }
    }

    private function shouldDevSiteEmail($array) {
        switch($array['from_class__method'])
        {
            case "icmpmodel__emailAlert":
                return true;
            break;
            case "bitsNbobs__alertDifference":
                return false;
            break;
            case "api_ping__index":
                return false;
            break;
            case "proc2d__index":
                return false;
            break;
            case "api_nightly__index":
                return true;
            break;
            case "user__mailConfirmation":
                return true;
            break;
            case "user__forgotFormProcess":
                return true;
            break;
            case "sausage__smtpAuthTestFORM":
                return true;
            break;
            case "lemon__icmpControl":
                return false;
            break;

            default;
                return false;
        }
    }

}
