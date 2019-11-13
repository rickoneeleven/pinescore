<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Trackerlib {
    
    public function trackMe() {
        /**
         * $CI class does not extend CI, so this allows us to use CI super object and use normal CI functions
         */
        $CI =& get_instance(); 
        $colour = $this->createColour();
        
        $mysqli = new mysqli('localhost', 'track_man77', '8=,:%Rza>KXVZeGX', "tracker");
        /* check connection */
        if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                exit();
        }

		if ($CI->input->server('REMOTE_ADDR') !== null) { //http://forums.devshed.com/php-development-5/fatal-error-isset-result-function-call-957892.html
            $ip2 = $CI->input->server('REMOTE_ADDR') . ' '; 
            } else if ($CI->input->server('HTTP_X_FORWARDED_FOR') !==null )    { 
            $ip2 = $CI->input->server('HTTP_X_FORWARDED_FOR') . ' '; 
            } else if ($CI->input->server('HTTP_CLIENT_IP') !==null )    { 
            $ip2 = $CI->input->server('HTTP_CLIENT_IP') . ' '; 
		} 
		
		$hostname = gethostbyaddr($CI->input->server('REMOTE_ADDR'));
		$dmy = date("Y-m-d");
		$time = date("H:i:s");
		$url = $CI->input->server('REQUEST_URI');

        if($CI->input->server('HTTP_REFERER') !==null ) {
            $refer = $CI->input->server('HTTP_REFERER');
        }

        $check_empty_referer = $CI->input->server('HTTP_REFERER');
		if (empty($check_empty_referer)) {
            $refer = "no data";
		}
		
		if($refer != "no data") {
            $mysqli->query("INSERT INTO tbl_tracking (id, ip, hostname, YMD, time, URL, refer)
            VALUES (NULL, '$ip2', '$hostname', '$dmy', '$time', '$url', '$refer')");
            $mysqli->query("INSERT INTO tbl_colour (id, ip, colour)
            VALUES (NULL, '$ip2', '$colour')");
		}
	}

    function createColour() {
        //http://php.net/manual/en/function.mt-rand.php
        $num = mt_rand ( 0, 0xffffff ); // trust the library, love the library...
        $output = sprintf ( "%06x" , $num ); // muchas smoochas to you, PHP!
        return "#$output";
    }
}

?>
