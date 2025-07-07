<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';
require_once APPPATH . 'models/table_maintenance_model.php';

class TableMaintenanceModelTest extends TestCase
{
    private $model;
    private $db_mock;

    public function setUp()
    {
        $this->db_mock = new class {
            public $query_expectations = [];
            public $query_results = [];
            public $update_called = false;
            public $where_called = false;
            public $insert_called = false;
            
            public function query($sql) {
                $this->query_expectations[] = $sql;
                return array_shift($this->query_results);
            }
            
            public function where($field, $value) {
                $this->where_called = ['field' => $field, 'value' => $value];
                return $this;
            }
            
            public function update($table, $data) {
                $this->update_called = ['table' => $table, 'data' => $data];
                return true;
            }
            
            public function insert($table, $data) {
                $this->insert_called = ['table' => $table, 'data' => $data];
                return true;
            }
            
            public function affected_rows() {
                return $this->update_called ? 1 : 0;
            }
        };
        
        $CI_mock = new class {
            public $db;
        };
        $CI_mock->db = $this->db_mock;
        
        $GLOBALS['CI_TEST'] = $CI_mock;
        
        $this->model = new Table_maintenance_model();
    }

    public function testTruncationIsNotTriggeredWhenBelowThreshold()
    {
        $query_result = new class {
            public function row() {
                return (object)['Auto_increment' => 500000];
            }
        };
        
        $this->db_mock->query_results[] = $query_result;
        
        $result = $this->model->check_and_truncate_ping_table();
        
        $this->assertEquals('NOT_REQUIRED', $result);
        $this->assertEquals(["SHOW TABLE STATUS LIKE 'ping_result_table'"], $this->db_mock->query_expectations);
    }

    public function testTruncationIsTriggeredWhenAboveThreshold()
    {
        $query_result = new class {
            public function row() {
                return (object)['Auto_increment' => 1500000000];
            }
        };
        
        $this->db_mock->query_results[] = $query_result;
        $this->db_mock->query_results[] = true;
        
        $result = $this->model->check_and_truncate_ping_table();
        
        $this->assertEquals('TRUNCATED', $result);
        $this->assertEquals(
            ["SHOW TABLE STATUS LIKE 'ping_result_table'", 'TRUNCATE TABLE ping_result_table'], 
            $this->db_mock->query_expectations
        );
        $this->assertEquals(['field' => 'metric', 'value' => 'ping_table_last_truncation'], $this->db_mock->where_called);
        $this->assertNotNull($this->db_mock->update_called);
        $this->assertEquals('health_dashboard', $this->db_mock->update_called['table']);
    }

    public function testTruncationHandlesDatabaseError()
    {
        $this->db_mock->query_results[] = false;
        
        $result = $this->model->check_and_truncate_ping_table();
        
        $this->assertEquals('ERROR', $result);
        $this->assertEquals(["SHOW TABLE STATUS LIKE 'ping_result_table'"], $this->db_mock->query_expectations);
    }
}