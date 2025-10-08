<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';
require_once APPPATH . 'controllers/events.php';

class EventsTest extends TestCase
{
    private $controller;
    private $previousErrorLevel;
    public $views = [];
    private $eventsModel;
    private $sessionStub;
    private $inputStub;
    public $recentArgs;
    public $recentReturn = [];
    public $windowArgs;
    public $windowReturn = [];
    public $groupRows = [];

    public function setUp()
    {
        $test = $this;
        $this->previousErrorLevel = error_reporting();
        error_reporting($this->previousErrorLevel & ~E_WARNING);

        $this->controller = new class($test) extends Events {
            private $test;

            public function __construct($test)
            {
                $this->test = $test;
            }
        };

        $this->eventsModel = new class($this) {
            private $test;

            public function __construct($test)
            {
                $this->test = $test;
            }

            public function fetch_recent_events($owner_id, $group_id = null, $limit = 5, $filter = 'onePlus')
            {
                $this->test->recentArgs = [$owner_id, $group_id, $limit, $filter];
                return $this->test->recentReturn;
            }

            public function fetch_events_window($owner_id, $group_id, $window, $cursor, $limit, $search)
            {
                $this->test->windowArgs = [$owner_id, $group_id, $window, $cursor, $limit, $search];
                return $this->test->windowReturn;
            }
        };
        $this->controller->events_model = $this->eventsModel;

        $this->sessionStub = new class {
            public $userId = null;

            public function userdata($key)
            {
                if ($key === 'user_id') {
                    return $this->userId;
                }
                return null;
            }
        };
        $this->controller->session = $this->sessionStub;

        $this->inputStub = new class {
            public $params = [];

            public function set($key, $value)
            {
                if ($value === null) {
                    unset($this->params[$key]);
                } else {
                    $this->params[$key] = $value;
                }
            }

            public function get($key)
            {
                return array_key_exists($key, $this->params) ? $this->params[$key] : null;
            }
        };
        $this->controller->input = $this->inputStub;

        $this->controller->load = new class($this, $this->controller) {
            private $test;
            private $controller;
    private $previousErrorLevel;

            public function __construct($test, $controller)
            {
                $this->test = $test;
                $this->controller = $controller;
            }

            public function view($view, $data = [])
            {
                $this->test->views[] = ['view' => $view, 'data' => $data];
            }

            public function model($name)
            {
                if ($name === 'group') {
                    $this->controller->group = new class($this->test) {
                        private $test;

                        public function __construct($test)
                        {
                            $this->test = $test;
                        }

                        public function readSpecificGroup($params)
                        {
                            return $this->test->makeGroupQuery();
                        }
                    };
                }
            }
        };

        $this->views = [];
        $this->recentArgs = null;
        $this->windowArgs = null;
        $this->recentReturn = [];
        $this->windowReturn = [];
        $this->groupRows = [];
        http_response_code(200);
    }

    public function tearDown()
    {
        if ($this->previousErrorLevel !== null) {
            error_reporting($this->previousErrorLevel);
        }
        unset($GLOBALS['CI_TEST']);
    }

    public function makeGroupQuery()
    {
        $rows = $this->groupRows;
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

    public function testIndexRendersTimelineView()
    {
        $this->sessionStub->userId = 7;
        ob_start();
        $this->controller->index();
        ob_end_clean();

        $this->assertTrue(count($this->views) >= 3, 'Expected timeline view to be rendered');
        $timeline = $this->views[2];
        $this->assertEquals('reports/events_view', $timeline['view']);
        $this->assertEquals(null, $timeline['data']['group_id']);
        $this->assertEquals('24h', $timeline['data']['default_window']);
    }

    public function testBarReturnsRecentEvents()
    {
        $this->sessionStub->userId = 11;
        $this->recentReturn = [
            ['id' => 1, 'ip' => '1.1.1.1', 'note' => 'Edge', 'datetime' => '2025-09-24 09:00:00', 'email_sent' => '0', 'status' => 'Online']
        ];

        ob_start();
        $this->controller->bar();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals([11, null, 5, 'onePlus'], $this->recentArgs);
        $this->assertNotNull($response);
        $this->assertEquals('Edge', $response[0]['note']);
    }

    public function testBarUsesLimitParameterWhenProvided()
    {
        $this->sessionStub->userId = 18;
        $this->inputStub->set('limit', '25');
        $this->recentReturn = [];

        ob_start();
        $this->controller->bar();
        ob_end_clean();

        $this->assertEquals([18, null, 25, 'onePlus'], $this->recentArgs);
    }

    public function testBarPassesFilterParameterWhenProvided()
    {
        $this->sessionStub->userId = 3;
        $this->inputStub->set('filter', 'twoPlus');

        ob_start();
        $this->controller->bar();
        ob_end_clean();

        $this->assertEquals([3, null, 5, 'twoPlus'], $this->recentArgs);
    }

    public function testJsonHonorsParametersAndCursor()
    {
        $this->sessionStub->userId = 21;
        $this->inputStub->set('group', '12');
        $this->inputStub->set('window', 'all');
        $this->inputStub->set('cursor', 'abc');
        $this->inputStub->set('limit', '150');
        $this->inputStub->set('q', 'router');
        $this->groupRows = [['name' => 'Ops']];
        $this->windowReturn = [
            'items' => [['id' => 9, 'ip' => '2.2.2.2', 'status' => 'Offline']],
            'next_cursor' => 'next123'
        ];

        ob_start();
        $this->controller->json();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals([21, 12, 'all', 'abc', '150', 'router'], $this->windowArgs);
        $this->assertEquals('next123', $response['next_cursor']);
        $this->assertEquals('Offline', $response['items'][0]['status']);
    }

    public function testBarRejectsUnknownGroup()
    {
        $this->sessionStub->userId = 5;
        $this->inputStub->set('group', '77');
        $this->groupRows = [];

        ob_start();
        $this->controller->bar();
        $response = json_decode(ob_get_clean(), true);

        $this->assertEquals('Group not found', $response['error']);
        $this->assertEquals(404, http_response_code());
    }
}
