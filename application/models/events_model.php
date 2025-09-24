<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Events_model extends CI_Model
{
    public function fetch_recent_events($owner_id, $group_id = null, $limit = 5)
    {
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 5;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $this->apply_scope($owner_id, $group_id);
        $this->db->where('prt.datetime >=', $this->twenty_four_hours_ago());
        $this->order_scope();
        $this->db->limit($limit);

        $query = $this->db->get();

        return $this->map_rows($query->result());
    }

    public function fetch_events_window($owner_id, $group_id = null, $window = '24h', $cursor = null, $limit = 100, $search = null)
    {
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 100;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $window = $window === 'all' ? 'all' : '24h';
        $paginate = $window === 'all';
        $query_limit = $paginate ? $limit + 1 : $limit;

        $this->apply_scope($owner_id, $group_id);

        if ($window === '24h') {
            $this->db->where('prt.datetime >=', $this->twenty_four_hours_ago());
        }

        if ($paginate) {
            $cursor_data = $this->decode_cursor($cursor);
            if ($cursor_data) {
                $dt = $this->db->escape($cursor_data['datetime']);
                $id = (int) $cursor_data['id'];
                $this->db->where("(prt.datetime < {$dt} OR (prt.datetime = {$dt} AND prt.id < {$id}))");
            }
        }

        if (!empty($search)) {
            $escaped = $this->db->escape_like_str($search);
            $lower = $this->db->escape_like_str(strtolower($search));
            $this->db->where("(LOWER(IFNULL(pit.note, '')) LIKE '%{$lower}%' OR prt.ip LIKE '%{$escaped}%')");
        }

        $this->order_scope();
        $this->db->limit($query_limit);

        $query = $this->db->get();
        $rows = $query->result();

        $has_more = $paginate && count($rows) > $limit;
        if ($has_more) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = $this->map_rows($rows);

        $next_cursor = null;
        if ($has_more && !empty($rows)) {
            $last = end($rows);
            $next_cursor = $this->encode_cursor($last->datetime, $last->id);
        }

        return [
            'items' => $items,
            'next_cursor' => $next_cursor,
        ];
    }

    private function apply_scope($owner_id, $group_id)
    {
        $this->db->select('prt.id, prt.ip, pit.note, prt.datetime, prt.email_sent, prt.result, pit.id AS node_id');
        $this->db->from('ping_result_table prt');
        $this->db->join('ping_ip_table pit', 'pit.ip = prt.ip');
        $this->db->where('prt.change', 1);
        $this->db->where('pit.owner', $owner_id);

        if ($group_id !== null) {
            $this->db->join('group_associations ga', 'ga.ping_ip_id = pit.id');
            $this->db->where('ga.group_id', $group_id);
            $this->db->where('ga.user_id', $owner_id);
        }
    }

    private function order_scope()
    {
        $this->db->order_by('prt.datetime', 'DESC');
        $this->db->order_by('prt.id', 'DESC');
    }

    private function map_rows($rows)
    {
        $items = [];
        foreach ($rows as $row) {
            $status = $this->derive_status($row->result);
            $items[] = [
                'id' => (int) $row->id,
                'ip' => $row->ip,
                'note' => $row->note,
                'datetime' => $row->datetime,
                'email_sent' => $row->email_sent,
                'status' => $status,
                'node_id' => isset($row->node_id) ? (int) $row->node_id : null,
            ];
        }

        return $items;
    }

    private function derive_status($result)
    {
        if ($result === '1') {
            return 'Online';
        }
        if ($result === '0') {
            return 'Offline';
        }

        log_message('debug', 'Events_model status fallback for result: ' . $result);

        return 'Drop';
    }

    private function twenty_four_hours_ago()
    {
        return date('Y-m-d H:i:s', time() - 86400);
    }

    private function encode_cursor($datetime, $id)
    {
        $payload = $datetime . '|' . (int) $id;
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    private function decode_cursor($cursor)
    {
        if (!$cursor) {
            return null;
        }
        $cursor = strtr($cursor, '-_', '+/');
        $padding = strlen($cursor) % 4;
        if ($padding) {
            $cursor .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($cursor, true);
        if ($decoded === false) {
            return null;
        }
        $parts = explode('|', $decoded);
        if (count($parts) !== 2) {
            return null;
        }
        if (!is_numeric($parts[1])) {
            return null;
        }

        return [
            'datetime' => $parts[0],
            'id' => (int) $parts[1],
        ];
    }
}
