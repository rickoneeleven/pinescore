<?php

require_once dirname(dirname(dirname(__DIR__))) . '/tests/bootstrap.php';
require_once APPPATH . 'models/events_model.php';
require_once __DIR__ . '/../../support/FakeQueryBuilder.php';

class EventsModelTest extends TestCase
{
    private $model;
    private $db;

    public function setUp()
    {
        $this->db = new FakeQueryBuilder();
        $ci = new stdClass();
        $ci->db = $this->db;
        $GLOBALS['CI_TEST'] = $ci;
        $this->model = new Events_model();
        $this->model->db = $this->db;
    }

    public function tearDown()
    {
        unset($GLOBALS['CI_TEST']);
    }

    public function testFetchRecentEventsClampsLimitAndMapsRows()
    {
        $this->db->pushResult([
            (object) [
                'id' => 5,
                'ip' => '1.1.1.1',
                'note' => 'Primary',
                'datetime' => '2025-09-24 09:00:00',
                'email_sent' => '1',
                'result' => '0',
            ],
        ]);

        $items = $this->model->fetch_recent_events(12, 6, 500);

        $this->assertEquals('Offline', $items[0]['status']);
        $this->assertEquals([50, null], $this->db->history[0]['limit']);

        $conditions = array_map(function ($w) {
            return $w[0];
        }, $this->db->history[0]['wheres']);
        $this->assertTrue(in_array('pit.owner', $conditions));
        $this->assertTrue(in_array('ga.group_id', $conditions));
    }

    public function testFetchEventsWindowAppliesCursorAndReturnsNextCursor()
    {
        $cursor = rtrim(strtr(base64_encode('2025-09-24 12:00:00|20'), '+/', '-_'), '=');

        $this->db->pushResult([
            (object) [
                'id' => 19,
                'ip' => '2.2.2.2',
                'note' => 'Edge',
                'datetime' => '2025-09-24 11:00:00',
                'email_sent' => '0',
                'result' => '1',
            ],
            (object) [
                'id' => 18,
                'ip' => '2.2.2.3',
                'note' => 'Backup',
                'datetime' => '2025-09-24 10:59:00',
                'email_sent' => '0',
                'result' => '2',
            ],
            (object) [
                'id' => 17,
                'ip' => '2.2.2.4',
                'note' => 'Spare',
                'datetime' => '2025-09-24 10:58:00',
                'email_sent' => '0',
                'result' => '0',
            ],
        ]);

        $result = $this->model->fetch_events_window(9, null, 'all', $cursor, 2, null);

        $this->assertEquals('Online', $result['items'][0]['status']);
        $this->assertEquals('Drop', $result['items'][1]['status']);
        $this->assertEquals(rtrim(strtr(base64_encode('2025-09-24 10:59:00|18'), '+/', '-_'), '='), $result['next_cursor']);

        $conditions = array_map(function ($w) {
            return $w[0];
        }, $this->db->history[0]['wheres']);

        $matched = false;
        foreach ($conditions as $condition) {
            if (strpos($condition, 'prt.datetime <') !== false && strpos($condition, '2025-09-24 12:00:00') !== false) {
                $matched = true;
                break;
            }
        }
        $this->assertTrue($matched);
        $this->assertEquals([3, null], $this->db->history[0]['limit']);
    }

    public function testFetchEventsWindowAppliesWindowAndSearch()
    {
        $this->db->pushResult([
            (object) [
                'id' => 31,
                'ip' => '3.3.3.3',
                'note' => 'Edge host',
                'datetime' => '2025-09-24 13:00:00',
                'email_sent' => '1',
                'result' => '1',
            ],
        ]);

        $result = $this->model->fetch_events_window(5, 3, '24h', null, -5, 'edge');

        $this->assertEquals('Online', $result['items'][0]['status']);
        $this->assertNull($result['next_cursor']);

        $conditions = array_map(function ($w) {
            return $w[0];
        }, $this->db->history[0]['wheres']);

        $windowApplied = false;
        $searchApplied = false;
        foreach ($conditions as $condition) {
            if (strpos($condition, 'prt.datetime >=') !== false) {
                $windowApplied = true;
            }
            if (strpos($condition, 'edge') !== false) {
                $searchApplied = true;
            }
        }

        $this->assertTrue($windowApplied);
        $this->assertTrue($searchApplied);
        $this->assertEquals([100, null], $this->db->history[0]['limit']);
    }
}
