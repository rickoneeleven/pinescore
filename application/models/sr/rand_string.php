<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rand_string extends CI_model {

    public function go() {

        return substr(md5(rand()), 0, 7);

    }
}