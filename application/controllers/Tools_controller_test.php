<?php

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__FILE__) . '/../../system/');
}
if (!defined('APPPATH')) {
    define('APPPATH', dirname(__FILE__) . '/../');
}

if (!class_exists('CI_Controller')) {
    class CI_Controller {
        public function __get($key) { 
            if (!isset($this->$key)) {
                $this->$key = new stdClass();
            }
            return $this->$key;
        }
    }
}

if (!function_exists('base_url')) {
    function base_url() {
        return 'http://example.com/';
    }
}

require_once(APPPATH . 'controllers/tools.php');

class Tools_controller_test
{
    private $CI;
    public $truncation_timestamp_passed = false;
    public $header_view_called = false;
    public $expected_timestamp;

    public function setUp()
    {
        $this->CI = new Tools();
        $this->expected_timestamp = date('Y-m-d H:i:s');
        
        $test = $this;
        
        $this->CI->db = new class($test) {
            private $test;
            private $current_table;
            
            public function __construct($test) {
                $this->test = $test;
            }
            
            public function where($condition, $value = null) {
                return $this;
            }
            
            public function get($table) {
                $this->current_table = $table;
                return new class($this->test, $table) {
                    private $test;
                    private $table;
                    
                    public function __construct($test, $table) {
                        $this->test = $test;
                        $this->table = $table;
                    }
                    
                    public function row() {
                        if ($this->table === 'health_dashboard') {
                            return (object)['result' => $this->test->expected_timestamp];
                        }
                        return (object)['result' => 'ok'];
                    }
                };
            }
        };
        
        $this->CI->load = new class($test) {
            private $test;
            
            public function __construct($test) {
                $this->test = $test;
            }
            
            public function view($view_name, $data = []) {
                if ($view_name === 'header_view') {
                    $this->test->header_view_called = true;
                    if (isset($data['last_truncation_timestamp']) && 
                        $data['last_truncation_timestamp'] === $this->test->expected_timestamp) {
                        $this->test->truncation_timestamp_passed = true;
                    }
                }
            }
            
            public function model($model_name) {
                // Mock model loading
            }
        };
        
        $this->CI->session = new class() {
            public function userdata($key) {
                return 'test_user';
            }
        };
    }

    public function test_popOut_method_fetches_truncation_date()
    {
        $this->CI->popOut();
        
        if (!$this->header_view_called) {
            echo "FAIL: header_view was not called\n";
            return false;
        }
        
        if (!$this->truncation_timestamp_passed) {
            echo "FAIL: last_truncation_timestamp was not passed to header_view\n";
            return false;
        }
        
        echo "PASS: popOut method fetches truncation date and passes it to header_view\n";
        return true;
    }
    
    public function run()
    {
        $this->setUp();
        return $this->test_popOut_method_fetches_truncation_date();
    }
}

if (basename($_SERVER['PHP_SELF']) === 'Tools_controller_test.php' || php_sapi_name() === 'cli') {
    $test = new Tools_controller_test();
    $result = $test->run();
    exit($result ? 0 : 1);
}