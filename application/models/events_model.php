<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Events_model extends CI_Model
{
    public function fetch_recent_events($owner_id, $group_id = null, $limit = 5, $filter = 'onePlus')
    {
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 5;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $filterKey = $this->normalise_filter($filter);
        $fetchLimit = $this->determine_fetch_window($limit, $filterKey);
        $maxFetch = 1000;

        // Fetch, and progressively expand window if we do not have enough items after filtering
        $items = [];
        $attempts = 0;
        $lastRowCount = -1;
        do {
            $this->apply_scope($owner_id, $group_id);
            $this->db->where('prt.datetime >=', $this->twenty_four_hours_ago());
            $this->order_scope();
            $this->db->limit($fetchLimit);

            $query = $this->db->get();
            $rows = $query->result();
            $rowCount = is_array($rows) ? count($rows) : 0;

            // If no additional rows are available, stop and keep current items
            if ($rowCount <= $lastRowCount) {
                break;
            }

            $lastRowCount = $rowCount;
            $items = $this->map_rows($rows);
            $items = $this->filter_recent_items($items, $filterKey);

            if (count($items) >= $limit || $fetchLimit >= $maxFetch) {
                break;
            }

            $attempts++;
            $fetchLimit = min($maxFetch, $fetchLimit * 2);
        } while ($attempts < 5);

        if (count($items) > $limit) {
            $items = array_slice($items, 0, $limit);
        }

        return $items;
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

        $this->apply_search_filter($search);

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

    public function fetch_all_events($owner_id, $group_id = null, $window = '24h', $search = null)
    {
        $window = $window === 'all' ? 'all' : '24h';

        $this->apply_scope($owner_id, $group_id);

        if ($window === '24h') {
            $this->db->where('prt.datetime >=', $this->twenty_four_hours_ago());
        }

        $this->apply_search_filter($search);

        $this->order_scope();

        $query = $this->db->get();

        return $this->map_rows($query->result());
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

    private function apply_search_filter($search)
    {
        if ($search === null || $search === '') {
            return;
        }

        $escaped = $this->db->escape_like_str($search);
        $lower = $this->db->escape_like_str(strtolower($search));
        $this->db->where("(LOWER(IFNULL(pit.note, '')) LIKE '%{$lower}%' OR prt.ip LIKE '%{$escaped}%')");
    }

    private function map_rows($rows)
    {
        $items = [];
        foreach ($rows as $row) {
            $status = $this->derive_status($row->result);
            if ($status === 'Drop' && isset($row->email_sent)) {
                $clean = strtolower(strip_tags((string) $row->email_sent));
                if (
                    strpos($clean, 'ping received') !== false ||
                    strpos($clean, 'responding') !== false ||
                    strpos($clean, 'status confirmed: online') !== false
                ) {
                    $status = 'Respond';
                }
            }
            $items[] = [
                'id' => (int) $row->id,
                'ip' => $row->ip,
                'note' => $row->note,
                'datetime' => $row->datetime,
                'email_sent' => $row->email_sent,
                'status' => $status,
                'node_id' => isset($row->node_id) ? (int) $row->node_id : null,
                'progress' => $this->extract_progress($row->email_sent),
            ];
        }

        return $items;
    }

    private function derive_status($result)
    {
        // Normalise common representations for result
        $val = is_string($result) ? strtolower(trim($result)) : $result;

        if ($val === '1' || $val === 1 || $val === true || $val === 'online') {
            return 'Online';
        }

        if ($val === '0' || $val === 0 || $val === false || $val === 'offline') {
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

    private function normalise_filter($filter)
    {
        if (!is_string($filter)) {
            return 'onePlus';
        }

        $filter = strtolower(trim($filter));
        if ($filter === 'twoplus' || $filter === 'two-plus' || $filter === '2+' || $filter === 'twoPlus') {
            return 'twoPlus';
        }
        if ($filter === 'tenplus' || $filter === 'ten-plus' || $filter === '10+' || $filter === 'tenPlus') {
            return 'tenPlus';
        }

        return 'onePlus';
    }

    private function determine_fetch_window($limit, $filter)
    {
        $limit = (int) $limit;
        if ($filter === 'tenPlus') {
            return min(200, max($limit * 12, 60));
        }
        if ($filter === 'twoPlus') {
            return min(200, max($limit * 8, 40));
        }
        return min(200, max($limit * 3, 15));
    }

    private function filter_recent_items(array $items, $filter)
    {
        if ($filter === 'onePlus' || empty($items)) {
            return $items;
        }

        $filtered = [];
        foreach ($items as $item) {
            if ($this->matches_filter($item, $filter)) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

    private function matches_filter(array $item, $filter)
    {
        if ($filter === 'onePlus') {
            return true;
        }

        if ($this->is_transition_item($item)) {
            return true;
        }

        $count = $this->progress_count(isset($item['progress']) ? $item['progress'] : null);

        if ($filter === 'twoPlus') {
            return $count !== null && $count >= 2;
        }

        if ($filter === 'tenPlus') {
            return $count !== null && $count >= 10;
        }

        return true;
    }

    private function progress_count($progress)
    {
        if (!is_string($progress) || $progress === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\\s*\\/\\s*10$/', $progress, $matches)) {
            $value = (int) $matches[1];
            if ($value >= 0 && $value <= 10) {
                return $value;
            }
        }

        return null;
    }

    private function is_transition_item(array $item)
    {
        $email = '';
        if (isset($item['email_sent']) && $item['email_sent'] !== null) {
            $email = strtolower(strip_tags((string) $item['email_sent']));
        }

        // Phrase-based transitions always survive
        if ($email !== '') {
            if (strpos($email, 'status confirmed') !== false) {
                return true;
            }
            if (strpos($email, 'node is now') !== false) {
                return true;
            }
            if (strpos($email, 'service restored') !== false) {
                return true;
            }
            if (strpos($email, 'back online') !== false) {
                return true;
            }
            // Common variants seen in the wild
            if (strpos($email, 'went offline') !== false || strpos($email, 'went online') !== false) {
                return true;
            }
            if (strpos($email, ' is offline') !== false || strpos($email, ' is online') !== false) {
                return true;
            }
        }

        // If classified as a terminal status, treat as transition only
        // when there is no progressive count like "x/10" present.
        $hasProgress = false;
        if ($email !== '') {
            $hasProgress = preg_match('/\b\d{1,2}\s*\/\s*10\b/', $email) === 1;
        }

        if (isset($item['status']) && ($item['status'] === 'Offline' || $item['status'] === 'Online')) {
            return $hasProgress === false;
        }

        return false;
    }

    private function extract_progress($email_sent)
    {
        if ($email_sent === null || $email_sent === '') {
            return null;
        }

        if (preg_match('/(\d{1,2}\s*\/\s*10)/', $email_sent, $matches)) {
            return str_replace(' ', '', $matches[1]);
        }

        $clean = trim(strip_tags($email_sent));
        if ($clean === '' || $clean === '0' || $clean === '1') {
            return null;
        }

        return $clean;
    }
}
