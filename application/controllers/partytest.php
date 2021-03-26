<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

    class Partytest extends CI_Controller
    {
        public function index()
        {
            $this->load->model('groupscore');
            echo $this->groupscore->getTodayGroupScore('1');
        }

        //wip1 work on editing a group
    }
