<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Group extends CI_model
{

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

    public function delete($array)
    {
        $this->db->where('user_id', $array['user_id']);
        $this->db->where('id', $array['group_id']);
        $this->db->delete('groups');
    }

    public function deleteEmptyGroups()
    {
        $this->load->model('group_association');

        $all_groups = $this->db->get('groups');
        foreach ($all_groups->result() as $row) {
            $associations = $this->group_association->read([
                'group_id' => $row->id,
                ]);
            if ($associations->num_rows() < 1) {
                $this->db->where('id', $row->id);
                $this->db->delete('groups');
            }
        }
    }
}
