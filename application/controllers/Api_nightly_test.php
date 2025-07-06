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
            if ($key === 'load') {
                return $this->load;
            }
            return null;
        }
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message) {
        echo "[{$level}] {$message}\n";
    }
}

if (!function_exists('base_url')) {
    function base_url() {
        return 'http://example.com/';
    }
}

if (!defined('from_email')) {
    define('from_email', 'test@example.com');
}

require_once(APPPATH . 'controllers/api_nightly.php');

class Api_nightly_test
{
    private $CI;
    private $load_mock;
    private $maintenance_model_mock;
    public $model_loaded = false;
    public $maintenance_method_called = false;

    public function setUp()
    {
        $this->CI = new api_nightly();
        
        $test = $this;
        
        $this->CI->load = new class($test, $this->CI) {
            private $test;
            private $CI;
            
            public function __construct($test, $CI) {
                $this->test = $test;
                $this->CI = $CI;
            }
            
            public function model($model_name) {
                if ($model_name === 'table_maintenance_model') {
                    $this->test->model_loaded = true;
                    $this->CI->table_maintenance_model = new class($this->test) {
                        private $test;
                        public function __construct($test) {
                            $this->test = $test;
                        }
                        public function check_and_truncate_ping_table() {
                            $this->test->maintenance_method_called = true;
                            return 'NOT_REQUIRED';
                        }
                    };
                } else {
                    $model_parts = explode('/', $model_name);
                    $model_class_name = end($model_parts);
                    $this->CI->{$model_class_name} = new stdClass();
                    if ($model_name === 'cron_protect') {
                        $this->CI->cron_protect = new class() {
                            public function AllowedIPs() {}
                        };
                    }
                    if ($model_name === 'groupscore') {
                        $this->CI->groupscore = new class() {
                            public function CalulateShortTermGroupScore() {}
                        };
                    }
                    if ($model_name === 'cellblock7') {
                        $this->CI->cellblock7 = new class() {
                            public function getOwnerEmail($owner) { return 'test@example.com'; }
                        };
                    }
                    if ($model_name === 'email_dev_or_no') {
                        $this->CI->email_dev_or_no = new class() {
                            public function amIonAproductionServer($arr) { return false; }
                        };
                    }
                }
            }
        };

        $this->CI->db = new class() {
            public function where($condition) { return $this; }
            public function get($table) {
                return new class() {
                    public function result() { return []; }
                };
            }
            public function delete($table) { return $this; }
            public function update($table, $data) { return $this; }
            public function last_query() { return "mock query"; }
        };

        $this->CI->email = new class() {
            public function from($from, $name) { return $this; }
            public function to($to) { return $this; }
            public function bcc($bcc) { return $this; }
            public function subject($subject) { return $this; }
            public function message($message) { return $this; }
            public function send() { return true; }
            public function print_debugger() { return ""; }
        };

        $this->CI->session = new class() {
            public function userdata($key) { return null; }
        };
    }

    public function test_index_method_calls_table_maintenance_model()
    {
        $this->CI->index();
        
        if (!$this->model_loaded) {
            echo "FAIL: table_maintenance_model was not loaded\n";
            return false;
        }
        
        if (!$this->maintenance_method_called) {
            echo "FAIL: check_and_truncate_ping_table() was not called\n";
            return false;
        }
        
        echo "PASS: table_maintenance_model was loaded and check_and_truncate_ping_table() was called\n";
        return true;
    }

    public function run()
    {
        $this->setUp();
        return $this->test_index_method_calls_table_maintenance_model();
    }
}

if (basename($_SERVER['PHP_SELF']) === 'Api_nightly_test.php' || php_sapi_name() === 'cli') {
    $test = new Api_nightly_test();
    $result = $test->run();
    exit($result ? 0 : 1);
}