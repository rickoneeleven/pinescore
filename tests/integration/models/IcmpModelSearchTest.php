<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';
require_once APPPATH . 'models/icmpmodel.php';

class IcmpModelSearchTest extends TestCase
{
    private $model;
    private $dbMock;
    private $sessionMock;
    private $loadMock;

    public function setUp()
    {
        $this->model = new IcmpModel();

        $this->dbMock = new class {
            public $wheres = [];
            public $groupBys = [];
            public $orderBys = [];

            public function group_by($field) { $this->groupBys[] = $field; return $this; }
            public function order_by($expr) { $this->orderBys[] = $expr; return $this; }
            public function where($cond, $val = null, $escape = null) { $this->wheres[] = [$cond, $val, $escape]; return $this; }
            public function get($table) { return new class { public function result(){ return []; } }; }
            public function get_where($table, $where) { $this->wheres[] = [$where, null, null]; return new class { public function result(){ return []; } }; }
            public function escape($v) { return is_numeric($v) ? $v : "'" . addslashes($v) . "'"; }
            public function escape_like_str($v) { return addslashes($v); }
        };

        $this->sessionMock = new class {
            private $data = [ 'hideOffline' => 1 ];
            public function userdata($key) { return $this->data[$key] ?? null; }
        };

        $this->loadMock = new class {
            public function model($name) { /* no-op for this test */ }
        };

        // Inject mocks
        $this->model->db = $this->dbMock;
        $this->model->session = $this->sessionMock;
        $this->model->load = $this->loadMock;
    }

    public function testOwnerSearchAddsParenthesisedOrAndSearch()
    {
        $filter = ['owner' => 123];
        $term = 'XG';
        $this->model->getIPs($filter, $term);

        $whereStrings = array_map(function($w){ return (string)$w[0]; }, $this->dbMock->wheres);
        $joined = implode(' \n ', $whereStrings);

        $this->assertTrue(strpos($joined, '(last_online_toggle') !== false, 'OR clause should be wrapped in parentheses');
        $this->assertTrue(stripos($joined, "lower(note) like '%xg%'") !== false, 'Search filter on note should be applied');
    }

    public function testOwnerSearchWithoutHideOfflineAddsOwnerAndSearch()
    {
        // Flip hideOffline off
        $this->model->session = new class { public function userdata($k){ return 0; } };
        // Reset DB mock
        $this->dbMock->wheres = [];

        $filter = ['owner' => 123];
        $term = 'router';
        $this->model->getIPs($filter, $term);

        $whereStrings = array_map(function($w){ return (string)$w[0]; }, $this->dbMock->wheres);
        $joined = implode(' \n ', $whereStrings);

        $this->assertTrue(strpos($joined, "owner") !== false, 'Owner predicate should be present');
        $this->assertTrue(stripos($joined, "lower(note) like '%router%'") !== false, 'Search filter should be present');
    }
}
