<?php

define('ENVIRONMENT', 'testing');
define('BASEPATH', dirname(__DIR__) . '/system/');
define('APPPATH', dirname(__DIR__) . '/application/');
define('VIEWPATH', APPPATH . 'views/');
define('FCPATH', dirname(__DIR__) . '/');

require_once BASEPATH . 'core/Common.php';

if (!function_exists('log_message')) {
    function log_message($level, $message) {
        echo "[{$level}] {$message}\n";
    }
}

if (!function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://test.local/' . $uri;
    }
}

if (!function_exists('get_instance')) {
    function &get_instance() {
        if (isset($GLOBALS['CI_TEST'])) {
            return $GLOBALS['CI_TEST'];
        }
        static $instance;
        if (!$instance) {
            $instance = new stdClass();
        }
        return $instance;
    }
}

if (!function_exists('show_error')) {
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered') {
        throw new Exception($message);
    }
}

class CI_Model {
    public function __construct() {}
}

class CI_Controller {
    public function __construct() {}
}

abstract class TestCase {
    protected $passed = 0;
    protected $failed = 0;
    protected $testResults = [];
    
    public function setUp() {}
    
    public function tearDown() {}
    
    public function run() {
        $class = get_class($this);
        $methods = get_class_methods($this);
        
        echo "\n{$class}:\n";
        
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                try {
                    $this->setUp();
                    $this->$method();
                    $this->passed++;
                    $this->testResults[] = ['test' => $method, 'status' => 'PASS', 'class' => $class];
                    echo "✓ {$method}\n";
                } catch (Exception $e) {
                    $this->failed++;
                    $this->testResults[] = ['test' => $method, 'status' => 'FAIL', 'class' => $class, 'error' => $e->getMessage()];
                    echo "✗ {$method}: {$e->getMessage()}\n";
                } finally {
                    $this->tearDown();
                }
            }
        }
        
        return $this->testResults;
    }
    
    protected function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception($message ?: 'Assertion failed: expected true');
        }
    }
    
    protected function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception($message ?: 'Assertion failed: expected false');
        }
    }
    
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Assertion failed: expected '{$expected}', got '{$actual}'");
        }
    }
    
    protected function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception($message ?: 'Assertion failed: expected non-null value');
        }
    }
    
    protected function assertNull($value, $message = '') {
        if ($value !== null) {
            throw new Exception($message ?: 'Assertion failed: expected null');
        }
    }
    
    protected function assertInstanceOf($expected, $actual, $message = '') {
        if (!($actual instanceof $expected)) {
            throw new Exception($message ?: "Assertion failed: expected instance of {$expected}");
        }
    }
}