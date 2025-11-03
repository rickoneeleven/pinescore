<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';
require_once APPPATH . 'controllers/events.php';
require_once APPPATH . 'models/events_model.php';
require_once __DIR__ . '/../../support/FakeQueryBuilder.php';

class EventsFlowTest extends TestCase
{
    private $controller;
    private $db;
    private $session;
    private $input;
    public $views = [];
    public $eventsModel;
    private $groupRows = [];

    public function setUp()
    {
        $this->db = new FakeQueryBuilder();
        $ci = new stdClass();
        $ci->db = $this->db;
        $GLOBALS['CI_TEST'] = $ci;

        $this->eventsModel = new Events_model();
        $this->eventsModel->db = $this->db;

        $test = $this;
        $this->controller = new class($test) extends Events {
            private $test;

            public function __construct($test)
            {
                $this->test = $test;
                CI_Controller::__construct();
                $this->load = new class($this->test, $this) {
                    private $test;
                    private $controller;

                    public function __construct($test, $controller)
                    {
                        $this->test = $test;
                        $this->controller = $controller;
                    }

                    public function model($name)
                    {
                        if ($name === 'events_model') {
                            $this->controller->events_model = $this->test->eventsModel;
                            return;
                        }
                        if ($name === 'group') {
                            $this->controller->group = $this->test->makeGroupModel();
                        }
                    }

                    public function view($view, $data = [])
                    {
                        $this->test->views[] = ['view' => $view, 'data' => $data];
                    }
                };
                $this->events_model = $this->test->eventsModel;
            }
        };

        $this->session = new class {
            public $userId = null;

            public function userdata($key)
            {
                return $key === 'user_id' ? $this->userId : null;
            }
        };
        $this->controller->session = $this->session;

        $this->input = new class {
            public $params = [];

            public function set($key, $value)
            {
                if ($value === null) {
                    unset($this->params[$key]);
                    return;
                }
                $this->params[$key] = $value;
            }

            public function get($key)
            {
                return array_key_exists($key, $this->params) ? $this->params[$key] : null;
            }
        };
        $this->controller->input = $this->input;

        $this->views = [];
        $this->groupRows = [];
        http_response_code(200);
    }

    public function tearDown()
    {
        unset($GLOBALS['CI_TEST']);
    }

    public function makeGroupModel()
    {
        $rows = $this->groupRows;
        return new class($rows) {
            private $rows;

            public function __construct($rows)
            {
                $this->rows = $rows;
            }

            public function readSpecificGroup($params)
            {
                $rows = $this->rows;
                return new class($rows) {
                    private $rows;

                    public function __construct($rows)
                    {
                        $this->rows = $rows;
                    }

                    public function num_rows()
                    {
                        return count($this->rows);
                    }

                    public function row($column = null)
                    {
                        if (empty($this->rows)) {
                            return null;
                        }
                        $row = (object) $this->rows[0];
                        if ($column) {
                            return isset($row->$column) ? $row->$column : null;
                        }
                        return $row;
                    }
                };
            }
        };
    }

    public function testBarRequiresAuthentication()
    {
        // Enforce strict auth behavior for the events bar in this test
        $this->input->set('strict', '1');
        ob_start();
        $this->controller->bar();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals(401, http_response_code());
        $this->assertEquals('Authentication required', $response['error']);
    }

    public function testBarReturnsScopedGroupEvents()
    {
        $this->session->userId = 9;
        $this->input->set('group', '7');
        $this->groupRows = [['name' => 'Core']];

        $this->db->pushResult([
            (object) [
                'id' => 10,
                'ip' => '10.0.0.1',
                'note' => 'Edge',
                'datetime' => '2025-09-24 08:00:00',
                'email_sent' => '0',
                'result' => '1',
            ],
        ]);

        ob_start();
        $this->controller->bar();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals('Edge', $response[0]['note']);
        $this->assertEquals('Online', $response[0]['status']);

        $query = $this->db->history[0];
        $conditions = array_map(function ($w) {
            return $w[0];
        }, $query['wheres']);
        $this->assertTrue(in_array('pit.owner', $conditions));
        $this->assertTrue(in_array('ga.group_id', $conditions));
        $this->assertTrue(in_array('ga.user_id', $conditions));
        $this->assertEquals([5, null], $query['limit']);
    }

    public function testJsonProvidesNextCursorForWindowAll()
    {
        $this->session->userId = 4;
        $this->input->set('group', '3');
        $this->input->set('window', 'all');
        $this->input->set('limit', '2');
        $this->input->set('cursor', '');
        $this->input->set('q', 'router');
        $this->groupRows = [['name' => 'Ops']];

        $this->db->pushResult([
            (object) [
                'id' => 30,
                'ip' => '192.0.2.1',
                'note' => 'Core router',
                'datetime' => '2025-09-24 10:00:00',
                'email_sent' => '1',
                'result' => '1',
            ],
            (object) [
                'id' => 29,
                'ip' => '192.0.2.2',
                'note' => 'Edge router',
                'datetime' => '2025-09-23 23:59:00',
                'email_sent' => '0',
                'result' => '0',
            ],
            (object) [
                'id' => 28,
                'ip' => '192.0.2.3',
                'note' => 'Backup router',
                'datetime' => '2025-09-23 23:58:59',
                'email_sent' => '0',
                'result' => '1',
            ],
        ]);

        ob_start();
        $this->controller->json();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals('Edge router', $response['items'][1]['note']);
        $this->assertEquals('Offline', $response['items'][1]['status']);
        $this->assertNotNull($response['next_cursor']);

        $expectedCursor = rtrim(strtr(base64_encode('2025-09-23 23:59:00|29'), '+/', '-_'), '=');
        $this->assertEquals($expectedCursor, $response['next_cursor']);

        $query = $this->db->history[0];
        $this->assertEquals([3, null], $query['limit']);
        $conditions = array_map(function ($w) {
            return $w[0];
        }, $query['wheres']);
        $this->assertTrue(in_array('pit.owner', $conditions));
        $this->assertTrue(in_array('ga.group_id', $conditions));
        $this->assertTrue(in_array('ga.user_id', $conditions));

        $rawConditions = array_map(function ($w) {
            return $w[0];
        }, $query['wheres']);
        $searchCondition = end($rawConditions);
        $this->assertTrue(strpos($searchCondition, 'router') !== false);
    }

    public function testJsonRejectsUnknownGroup()
    {
        $this->session->userId = 5;
        $this->input->set('group', '77');
        $this->groupRows = [];

        ob_start();
        $this->controller->json();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals(404, http_response_code());
        $this->assertEquals('Group not found', $response['error']);
    }
}
