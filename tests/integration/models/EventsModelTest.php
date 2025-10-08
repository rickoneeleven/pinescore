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
        $this->assertEquals([150, null], $this->db->history[0]['limit']);

        $conditions = array_map(function ($w) {
            return $w[0];
        }, $this->db->history[0]['wheres']);
        $this->assertTrue(in_array('pit.owner', $conditions));
        $this->assertTrue(in_array('ga.group_id', $conditions));
    }

    public function testFetchRecentEventsFiltersTwoPlus()
    {
        $this->db->pushResult([
            (object) [
                'id' => 60,
                'ip' => '10.0.0.1',
                'note' => 'Core',
                'datetime' => '2025-10-08 10:00:00',
                'email_sent' => 'Node is now <strong>Offline</strong>',
                'result' => '0',
                'node_id' => 101,
            ],
            (object) [
                'id' => 59,
                'ip' => '10.0.0.2',
                'note' => 'Edge',
                'datetime' => '2025-10-08 09:59:00',
                'email_sent' => 'Dropped (2/10) packets detected',
                'result' => '2',
                'node_id' => 102,
            ],
            (object) [
                'id' => 58,
                'ip' => '10.0.0.3',
                'note' => 'Relay',
                'datetime' => '2025-10-08 09:58:30',
                'email_sent' => 'Dropped (1/10) packets detected',
                'result' => '2',
                'node_id' => 103,
            ],
            (object) [
                'id' => 57,
                'ip' => '10.0.0.4',
                'note' => 'Backup',
                'datetime' => '2025-10-08 09:58:00',
                'email_sent' => 'Responding (8/10) - service recovering',
                'result' => '2',
                'node_id' => 104,
            ],
            (object) [
                'id' => 56,
                'ip' => '10.0.0.5',
                'note' => 'Aux',
                'datetime' => '2025-10-08 09:57:30',
                'email_sent' => 'Dropped (3/10) packets detected',
                'result' => '2',
                'node_id' => 105,
            ],
            (object) [
                'id' => 55,
                'ip' => '10.0.0.6',
                'note' => 'Spare',
                'datetime' => '2025-10-08 09:57:00',
                'email_sent' => 'Dropped (4/10) packets detected',
                'result' => '2',
                'node_id' => 106,
            ],
        ]);

        $items = $this->model->fetch_recent_events(4, null, 5, 'twoPlus');

        $this->assertEquals([40, null], $this->db->history[0]['limit']);
        $this->assertEquals(5, count($items));
        $ids = array_column($items, 'id');
        $this->assertEquals([60, 59, 57, 56, 55], $ids);
        $this->assertFalse(in_array(58, $ids), 'Events with 1/10 progress should be excluded for twoPlus filter');
    }

    public function testFetchRecentEventsFiltersTenPlus()
    {
        $this->db->pushResult([
            (object) [
                'id' => 70,
                'ip' => '10.0.1.1',
                'note' => 'Gateway',
                'datetime' => '2025-10-08 11:00:00',
                'email_sent' => 'Node is now <strong>Offline</strong>',
                'result' => '0',
                'node_id' => 201,
            ],
            (object) [
                'id' => 69,
                'ip' => '10.0.1.2',
                'note' => 'Aggregation',
                'datetime' => '2025-10-08 10:59:30',
                'email_sent' => 'Dropped (10/10) packets detected',
                'result' => '2',
                'node_id' => 202,
            ],
            (object) [
                'id' => 68,
                'ip' => '10.0.1.3',
                'note' => 'Responder',
                'datetime' => '2025-10-08 10:59:00',
                'email_sent' => 'Responding (10/10) - service restored',
                'result' => '2',
                'node_id' => 203,
            ],
            (object) [
                'id' => 67,
                'ip' => '10.0.1.4',
                'note' => 'Relay',
                'datetime' => '2025-10-08 10:58:30',
                'email_sent' => 'Responding (9/10) - nearing recovery',
                'result' => '2',
                'node_id' => 204,
            ],
            (object) [
                'id' => 66,
                'ip' => '10.0.1.5',
                'note' => 'Edge',
                'datetime' => '2025-10-08 10:58:00',
                'email_sent' => 'Dropped (2/10) packets detected',
                'result' => '2',
                'node_id' => 205,
            ],
        ]);

        $items = $this->model->fetch_recent_events(4, null, 5, 'tenPlus');

        $this->assertEquals([60, null], $this->db->history[0]['limit']);
        $this->assertEquals(3, count($items));
        $ids = array_column($items, 'id');
        $this->assertEquals([70, 69, 68], $ids);
        $this->assertFalse(in_array(67, $ids), 'Responding 9/10 should be filtered out for tenPlus');
        $this->assertFalse(in_array(66, $ids), 'Low drop counts should be filtered out for tenPlus');
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
