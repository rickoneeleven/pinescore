<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   Cron_protect extends CI_model {

    public function AllowedIPs() {
        $allowedIPs[] = array();
        $allowedIPs[] = "127.0.0.1";
        $allowedIPs[] = "192.168.1.5"; //ukraine home vm
        $allowedIPs[] = "80.229.138.2"; //home external IP
        $allowedIPs[] = "81.174.163.10"; //vtl plusnet
        $allowedIPs[] = "84.21.150.125"; //vtl Inf
        $allowedIPs[] = "80.229.138.2"; //novascore's "local IP" - this is where it's requests come from, not 127.

        if(in_array($this->input->ip_address(), $allowedIPs)) return TRUE;
        die("who dis new fone?");
    }

}