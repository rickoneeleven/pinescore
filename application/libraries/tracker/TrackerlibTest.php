<?php

class TrackerlibTest extends PHPUnit_Framework_TestCase
{    
    /**
     *
     */

   /** public function setUp()
    {
        $this->CI->load->library('tracker/TrackerLib');
    }**/

    /**public function testRandColorCode()
    {
        /**
         * test for hex code format, # (hash) first and then 6 hex chars
         */
       /** $random_hex_code = $this->CI->trackerlib->createColour();
        //$random_hex_code = "dave";
        $this->assertStringMatchesFormat('#%x%x%x%x%x%x', $random_hex_code);
    }**/

    public function testRandColorCode() {

        if(!defined('BASEPATH')) {
            define('BASEPATH', 1);
        }
        require_once('Trackerlib.php');

        $ClassUnderTest = new Trackerlib();
        $random_hex_code = $ClassUnderTest->createColour();
        echo "Random text color today is: ".$random_hex_code;
        $this->assertStringMatchesFormat('#%x%x%x%x%x%x', $random_hex_code);

    }
}
