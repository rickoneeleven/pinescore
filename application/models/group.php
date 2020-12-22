<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Group extends CI_model
{
    //'name'
    //'user_id'
    //'public'
    //RETURN group_id
    public function create($array)
    {
        $group = [
            'name' => $array['name'],
            'user_id' => $array['user_id'],
            'public' => $array['public'],
        ];
        $this->db->insert('groups', $group);

        return $this->db->insert_id();
    }

    //'user_id'
    //RETURN Table Object
    public function read($array)
    {
        $this->db->where('user_id', $array['user_id']);

        return $this->db->get('groups');
    }

    public function readSpecificGroup($array)
    {
        $this->db->where('user_id', $array['user_id']);
        $this->db->where('id', $array['group_id']);

        return $this->db->get('groups');
    }

    public function readGroupByID($array)
    {
        $this->db->where('id', $array['group_id']);

        return $this->db->get('groups');
    }

    //'group_id'
    //'name'
    //'user_id'
    //'public'
    public function update($array)
    {
        $update = [
            'name' => $array['name'],
            'public' => $array['public'],
        ];
        $this->db->where('user_id', $array['user_id']);
        $this->db->where('group_id', $array['group_id']);
        $this->db->update('groups', $update);
    }

    //'group_id'
    //'user_id'
    public function delete($array)
    {
        $this->db->where('user_id', $array['user_id']);
        $this->db->where('group_id', $array['group_id']);
        $this->db->delete('groups');
    }
}
