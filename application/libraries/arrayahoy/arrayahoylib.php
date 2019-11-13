<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ArrayAhoyLib {
    
    public function removeWhiteAndEmpty($data) {
        $data = array_filter(array_map('trim', $data));
        return $data;
    }
}

