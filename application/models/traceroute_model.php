<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class traceroute_model extends CI_model {
  
    public function getTraceroutes($ip) {
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get_where('traceroutes', array('node' => $ip)); //limit, offset last two params
        return $query;
    }
    
}

?>
