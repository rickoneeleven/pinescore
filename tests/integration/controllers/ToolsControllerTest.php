<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';
require_once APPPATH . 'controllers/tools.php';

class ToolsControllerTest extends TestCase
{
    private $controller;
    public $truncation_timestamp_passed = false;
    public $header_view_called = false;
    public $expected_timestamp;

    public function setUp()
    {
        $this->controller = new Tools();
        $this->expected_timestamp = date('Y-m-d H:i:s');
        
        $test = $this;
        
        $this->controller->db = new class($test) {
            private $test;
            
            public function __construct($test) {
                $this->test = $test;
            }
            
            public function where($condition, $value = null) {
                return $this;
            }
            
            public function get($table) {
                return new class($this->test, $table) {
                    private $test;
                    private $table;
                    
                    public function __construct($test, $table) {
                        $this->test = $test;
                        $this->table = $table;
                    }
                    
                    public function num_rows() {
                        return 1;
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
        
        $this->controller->load = new class($test, $this->controller) {
            private $test;
            private $controller;
            
            public function __construct($test, $controller) {
                $this->test = $test;
                $this->controller = $controller;
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
                $model_parts = explode('/', $model_name);
                $model_class_name = end($model_parts);
                
                if ($model_name === 'cellblock7') {
                    $this->controller->cellblock7 = new class() {
                        public function groupPublicCheck($owner) {
                            return 'public';
                        }
                        public function icmpTableData($group = null) {
                            return ['data' => []];
                        }
                        public function getMyReports($user_id) {
                            return [];
                        }
                    };
                } else if ($model_name === 'securitychecks') {
                    $this->controller->securitychecks = new class() {
                        public function ownerMatchesLoggedIn($owner) {
                            return;
                        }
                    };
                } else {
                    $this->controller->{$model_class_name} = new class() {
                        public function __call($method, $args) {
                            // Default return for any method call
                            if (strpos($method, 'get') === 0) {
                                return ['data' => []];
                            }
                            return null;
                        }
                    };
                }
            }
        };
        
        $this->controller->session = new class() {
            public function userdata($key) {
                return 'test_user';
            }
        };
    }

    public function testPopOutMethodFetchesTruncationDate()
    {
        $this->controller->popOut();
        
        $this->assertTrue($this->header_view_called, 'header_view was not called');
        $this->assertTrue($this->truncation_timestamp_passed, 'last_truncation_timestamp was not passed to header_view');
    }
    
    public function testPingAddMethodFetchesTruncationDate() 
    {
        $this->controller->session->userdata = function($key) {
            if ($key === 'sessionOwner') return 'test_owner';
            return 'test_user';
        };
        
        $this->controller->input = new class() {
            public function get($key) {
                if ($key === 'targetURL') return 'http://example.com';
                if ($key === 'numberOfPings') return '5';
                if ($key === 'owner') return 'test_owner';
                return null;
            }
        };
        
        $this->controller->pingAdd();
        
        $this->assertTrue($this->header_view_called, 'header_view was not called in pingAdd');
        $this->assertTrue($this->truncation_timestamp_passed, 'last_truncation_timestamp was not passed to header_view in pingAdd');
    }
}