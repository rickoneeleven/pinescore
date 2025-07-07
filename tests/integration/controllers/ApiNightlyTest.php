<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';

if (!defined('ENVIRONMENTv2')) {
    define('ENVIRONMENTv2', 'testing');
}

require_once APPPATH . 'config/config.php';

require_once APPPATH . 'controllers/api_nightly.php';

class ApiNightlyTest extends TestCase
{
    private $controller;
    public $model_loaded = false;
    public $maintenance_method_called = false;

    public function setUp()
    {
        $test = $this;
        
        $this->controller = new api_nightly();
        
        $this->controller->load = new class($test, $this->controller) {
            private $test;
            private $controller;
            
            public function __construct($test, $controller) {
                $this->test = $test;
                $this->controller = $controller;
            }
            
            public function model($model_name) {
                if ($model_name === 'table_maintenance_model') {
                    $this->test->model_loaded = true;
                    $this->controller->table_maintenance_model = new class($this->test) {
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
                    $this->controller->{$model_class_name} = new stdClass();
                    
                    if ($model_name === 'cron_protect') {
                        $this->controller->cron_protect = new class() {
                            public function AllowedIPs() {}
                        };
                    }
                    if ($model_name === 'groupscore') {
                        $this->controller->groupscore = new class() {
                            public function CalulateShortTermGroupScore() {}
                        };
                    }
                    if ($model_name === 'cellblock7') {
                        $this->controller->cellblock7 = new class() {
                            public function getOwnerEmail($owner) { return 'test@example.com'; }
                        };
                    }
                    if ($model_name === 'email_dev_or_no') {
                        $this->controller->email_dev_or_no = new class() {
                            public function amIonAproductionServer($arr) { return false; }
                        };
                    }
                }
            }
        };

        $this->controller->db = new class() {
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

        $this->controller->email = new class() {
            public function from($from, $name) { return $this; }
            public function to($to) { return $this; }
            public function bcc($bcc) { return $this; }
            public function subject($subject) { return $this; }
            public function message($message) { return $this; }
            public function send() { return true; }
            public function print_debugger() { return ""; }
        };

        $this->controller->session = new class() {
            public function userdata($key) { return null; }
        };
    }

    public function testIndexMethodCallsTableMaintenanceModel()
    {
        $this->controller->index();
        
        $this->assertTrue($this->model_loaded, 'table_maintenance_model was not loaded');
        $this->assertTrue($this->maintenance_method_called, 'check_and_truncate_ping_table() was not called');
    }
}