<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Email_string_to_array extends CI_model {

    public function go($string) {

        $myArray = explode(',', $string);
        $trimmed_myArray = array_map('trim',$myArray);

        return $trimmed_myArray;
    }
}