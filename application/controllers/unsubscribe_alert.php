<?php
    if ( ! defined('BASEPATH')) exit('No direct script access allowed');

        class unsubscribe_alert extends CI_Controller {

            public function go() {
                $this->load->model('unsubscribe_alert_model');
                $array = array(
                    'ping_ip_id'            => $this->uri->segment(3),
                    'unsub_ref'             => $this->uri->segment(4),
                );
                //pass id and unsub ref to model/method for a true false return for match
                if($this->unsubscribe_alert_model->idMatchUnsubrRef($array)) {
                    //write remove alert entry from db and echo success message
                    $this->unsubscribe_alert_model->unsub($array);
                    echo "You've been successfully unsubscibed from this alert. Have nice day.";
                } else {
                    echo "no u";
                    return false;
                }
            }
    

    }