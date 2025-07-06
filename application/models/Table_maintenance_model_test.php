<?php

// Define BASEPATH and APPPATH if not defined
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__FILE__) . '/../../system/');
}
if (!defined('APPPATH')) {
    define('APPPATH', dirname(__FILE__) . '/../');
}

// Mock the CI_Model class and other dependencies if they don't exist in the test environment.
if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {
            // Empty constructor
        }
    }
}

// Store reference to CI instance
$GLOBALS['CI_TEST'] = null;

// Mock get_instance function
if (!function_exists('get_instance')) {
    function &get_instance() {
        return $GLOBALS['CI_TEST'];
    }
}

// Mock log_message function
if (!function_exists('log_message')) {
    function log_message($level, $message) {
        // Mock logging - do nothing
    }
}

// Include the model file to be tested.
require_once(APPPATH . 'models/table_maintenance_model.php');

use PHPUnit\Framework\TestCase;

class Table_maintenance_model_test extends TestCase
{
    private $CI;
    private $db_mock;
    private $model;

    protected function setUp(): void
    {
        // Mock the database library
        $this->db_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['query', 'update', 'where', 'affected_rows', 'insert'])
            ->getMock();
        
        // Mock the CodeIgniter super-object
        $this->CI = new stdClass();
        $this->CI->db = $this->db_mock;
        
        // Set the global CI instance reference for get_instance()
        $GLOBALS['CI_TEST'] = $this->CI;

        // Instantiate the model
        $this->model = new Table_maintenance_model();
    }

    public function test_truncation_is_not_triggered_when_below_threshold()
    {
        // Arrange: Simulate the DB returning a status with a low AUTO_INCREMENT
        $table_status_mock = (object)['Auto_increment' => 500000];
        $query_result_mock = $this->getMockBuilder('stdClass')->addMethods(['row'])->getMock();
        $query_result_mock->method('row')->willReturn($table_status_mock);
        
        // Set up the expectation for the SHOW TABLE STATUS query
        $this->db_mock->expects($this->once())
            ->method('query')
            ->with("SHOW TABLE STATUS LIKE 'ping_result_table'")
            ->willReturn($query_result_mock);

        // Act: Run the method under test
        $result = $this->model->check_and_truncate_ping_table();

        // Assert: The method should return 'NOT_REQUIRED'
        $this->assertEquals('NOT_REQUIRED', $result);
    }

    public function test_truncation_is_triggered_when_above_threshold()
    {
        // Arrange: Simulate the DB returning a status with a high AUTO_INCREMENT
        $table_status_mock = (object)['Auto_increment' => 1500000000];
        $query_result_mock = $this->getMockBuilder('stdClass')->addMethods(['row'])->getMock();
        $query_result_mock->method('row')->willReturn($table_status_mock);
        
        // Set up expectations for both queries
        $this->db_mock->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                ["SHOW TABLE STATUS LIKE 'ping_result_table'"],
                ['TRUNCATE TABLE ping_result_table']
            )
            ->willReturnOnConsecutiveCalls($query_result_mock, true);

        // Expectation: The update method for health_dashboard should be called
        $this->db_mock->expects($this->once())->method('where')->with('metric', 'ping_table_last_truncation');
        $this->db_mock->expects($this->once())->method('update')->with('health_dashboard', $this->anything());
        $this->db_mock->expects($this->once())->method('affected_rows')->willReturn(1);

        // Act
        $result = $this->model->check_and_truncate_ping_table();

        // Assert
        $this->assertEquals('TRUNCATED', $result);
    }

    public function test_truncation_handles_database_error()
    {
        // Arrange: Simulate the DB returning false on the status check
        $this->db_mock->expects($this->once())
            ->method('query')
            ->with("SHOW TABLE STATUS LIKE 'ping_result_table'")
            ->willReturn(false);

        // Act
        $result = $this->model->check_and_truncate_ping_table();

        // Assert
        $this->assertEquals('ERROR', $result);
    }
}