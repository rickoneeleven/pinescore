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

    //'user_id'
    //RETURN Table Object
    public function read($array) {
        $this->db->where('user_id', $array['user_id']);
        $groupsTable = $this->db->get('groups');
        
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
