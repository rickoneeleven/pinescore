<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ping_ip_id extends CI_model
{
    public function getPingIpID($ping_ip_id)
    {
        $this->db->where('id', $ping_ip_id);

        return $this->db->get('ping_ip_table');
    }
}
