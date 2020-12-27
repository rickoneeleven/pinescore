<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Group_Association extends CI_model
{
    //'group_id'
    //'ping_ip_id'
    //'user_id'
    public function create($array)
    {
        $insert = [
            'group_id' => $array['group_id'],
            'ping_ip_id' => $array['ping_ip_id'],
            'user_id' => $array['user_id'],
            
        ];

        $this->db->insert('group_associations', $insert);
    }

    //'group_id'
    //RETURN all rows
    public function read($array)
    {
        $this->db->where('group_id', $array['group_id']);

        return $this->db->get('group_associations');
    }

    //'group_id'
    //'ping_ip_id'
    //'user_id'
    //wrapper for delete_all_associations_based_on_group_id() and create()?
    public function update($array)
    {
        $this->delete_all_associations_based_on_group_id($array);
        $this->create($array);
    }

    //'group_id'
    //'user_id'
    public function delete_all_associations_based_on_group_id($array)
    {
        $this->db->where('user_id', $array['user_id']);
        $this->db->where('group_id', $array['group_id']);
        $this->db->delete('group_associations');
    }

    //'ping_ip_id'
    //'user_id'
    public function delete_all_associations_based_on_ping_ip_id($array)
    {
        $this->db->where('user_id', $array['user_id']);
        $this->db->where('ping_ip_id', $array['ping_ip_id']);
        $this->db->delete('group_associations');
    }

    //'ping_ip_id'
    //'user_id'
    public function return_array_pingIpIds_from_group_id($array)
    {
        $rows = $this->read($array);

        $ping_ip_ids = [];
        foreach ($rows->result() as $row) {
            $ping_ip_ids[] = $row->ping_ip_id;
        }

        return $ping_ip_ids;
    }
}
