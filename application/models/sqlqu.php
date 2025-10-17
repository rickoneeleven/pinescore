<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sqlqu extends CI_model
{
    public function getPingIpTable($array)
    {
        if ($array['request_type'] === 'ip_by_id') {
            $this->db->where('id', $array['id']);

            return $this->db->get('ping_ip_table');
        }
        if ($array['request_type'] === 'distinct_ips') {
            $this->db->distinct();
            $this->db->group_by('ip');

            return $this->db->get('ping_ip_table');
        }
    }

    public function getHistoricpinescore($array)
    {
        if ($array['request_type'] === 'single_ip') {
            $this->db->where('ip', $array['ip']);

            if (!empty($array['from'])) {
                $this->db->where('logged >=', $array['from'] . ' 00:00:00');
            }
            if (!empty($array['to'])) {
                $this->db->where('logged <=', $array['to'] . ' 23:59:59');
            }

            $order = (isset($array['order']) && strtoupper($array['order']) === 'ASC') ? 'ASC' : 'DESC';
            if ($order === 'DESC' && !empty($array['before_id'])) {
                $this->db->where('id <', (int) $array['before_id']);
            }
            if ($order === 'ASC' && !empty($array['after_id'])) {
                $this->db->where('id >', (int) $array['after_id']);
            }

            $this->db->order_by('id', $order);

            if (!empty($array['limit'])) {
                $this->db->limit((int) $array['limit']);
            }
        }
        if ($array['request_type'] === 'single_ip_one_month') {
            $one_month_ago = 'logged > (NOW() - INTERVAL 1 MONTH)';
            $this->db->where('ip', $array['host']);
            $this->db->where($one_month_ago);
            $this->db->order_by('id', 'DESC');
        }

        return $this->db->get('historic_pinescore');
    }

    public function insertEmailAlert($data_db)
    {
        $this->load->model('sr/email_string_to_array');
        $this->load->model('sr/rand_string');

        $this->db->where('ping_ip_id', $data_db['ping_ip_id']);
        $this->db->delete('alerts');

        $email_addresses = $this->email_string_to_array->go($data_db['alert']);
        foreach ($email_addresses as $email_address) {
            if ($email_address) {
                $sanitized = [
                    'ping_ip_id' => $data_db['ping_ip_id'],
                    'updated' => date('Y-m-d H:i:s'),
                    'email' => $email_address,
                    'unsub_ref' => $this->rand_string->go(),
                ];

                $this->db->insert('alerts', $sanitized);
            }
        }
    }
}
