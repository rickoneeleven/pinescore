<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

    class Partytest extends CI_Controller
    {
        public function index()
        {
            echo 'test';
            echo 'wank';
            phpinfo();
        }

        //wip1.1 okay xdebug seems to be loaded in phpinfo now, but the breakpoint in partytest isn't stopping
        //double check your loading partytest from dev server where xdeug is configured, and check vsc xdebug config is
        //on same server
    }
