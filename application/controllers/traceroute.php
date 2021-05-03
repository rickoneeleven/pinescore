<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

    class Traceroute extends CI_Controller
    {
        public function routesthathavebeentraced()
        {
            $this->load->model('traceroute_model');
            $ip = rtrim($this->uri->slash_segment(3),"/");
            $view['tracerouteReports'] = $this->traceroute_model->getTraceroutes($ip);
            
            $this->load->view('reports/traceroute_report', $view);
        }
    }
