<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   Group_monthly_scores extends CI_model {
    
    public function get($group_id) {
        
        $this->db->where("group_id", $group_id);
        $this->db->limit(12);
        $all = $this->db->get("group_monthly_scores");
    }
} 
