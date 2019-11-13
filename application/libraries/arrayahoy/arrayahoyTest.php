<?php

class ArrayAhoyTest extends PHPUnit_Framework_TestCase {

    public function testRemoveWhiteAndEmpty() {
        if(!defined('BASEPATH')) {
            define('BASEPATH', 1);
        }
        require_once('arrayahoylib.php');

        $array_with_whiteshite = array(
            1 => "one",
            2 => " two with space at start",
            3 => ""
        );
        $classUnderTest = new ArrayAhoyLib();
        $array_with_whiteshite = $classUnderTest->removeWhiteAndEmpty($array_with_whiteshite);

        $this->assertArrayNotHasKey(3, $array_with_whiteshite);
        $this->assertStringStartsNotWith(' ', $array_with_whiteshite[2]);
    }
}
