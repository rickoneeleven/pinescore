<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Group extends CI_model {

    //'name'
    //'user_id'
    //RETURN group_id
    public function create($array) {
        $group = array(
            'name' => $array['name'],
            'user_id' => $array['user_id'],
        ); 
        $this->db->insert('groups', $group);

        return $this->db->insert_id();
    }

    //'group_id'
    //'user_id'
    //RETURN name or FALSE
    public function read($array) {
        
    }

    //'group_id'
    //'name'
    //'user_id'
    //RETURN TRUE or FALSE
    public function update($array) {
        
    }

    //'group_id'
    //'user_id'
    //RETURN TRUE or FALSE
    public function delete($array) {
        
    }

    
}
