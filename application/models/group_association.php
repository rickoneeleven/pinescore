<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Group_Association extends CI_model {

    //'group_id'
    //'ping_ip_id'
    //'user_id'
    public function create($array) {
        $insert = array(
            'group_id'          => $array['group_id'],
            'ping_ip_id'          => $array['ping_ip_id'],
            'user_id'          => $array['user_id'],
        );

        $this->db->insert('group_associations', $insert);

    }

    //'group_id'
    //'user_id'
    //RETURN all rows
    public function read() {
        
    }

    //'group_id'
    //'ping_ip_id'
    //'user_id'
    //wrapper for delete_all_associations_based_on_group_id() and create()?
    public function update() {
        
    }

    //'group_id'
    //'user_id'
    public function delete_all_associations_based_on_group_id() {
        
    }

    //'ping_ip_id'
    //'user_id'
    public function delete_all_associations_based_on_ping_ip_id() {
        
    }

    
} 
