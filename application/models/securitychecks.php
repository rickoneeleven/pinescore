<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SecurityChecks extends CI_model
{
    public function loggedIn()
    {
        if (($this->session->userdata('user_email') != '')) {
            return true;
        } else {
            return false;
        }
    }

    public function ownerCheckRedirect($owner)
    {
        if ($this->session->userdata('user_id') !== $owner) {
            echo 'error code: dave has no pajamas';

            header('Refresh: 3;url=https://www.google.co.uk/search?num=20&safe=strict&site=&source=hp&q=sausage+butty+time');
            die();
        } else {
            return true;
        }
    }

    public function ownerMatchesLoggedIn($group_or_node)
    {
        $owner_field_name = 'owner';
        if ($group_or_node === 'group') {
            $table = 'groups';
            $owner_field_name = 'user_id';
        }
        if ($group_or_node === 'node') {
            $table = 'ping_ip_table';
        }
        $this->db->limit(1);
        $this->db->where($owner_field_name, $this->session->userdata('user_id'));
        $Table = $this->db->get($table);
        if ($Table->num_rows() > 0) {
            return true;
        }

        return false;
    }
}
