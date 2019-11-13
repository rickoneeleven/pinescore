<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class unsubscribe_alert_model extends CI_model {

    public function idMatchUnsubrRef($array) {
        $this->db->where($array);
        $alertsTable = $this->db->get('alerts');
        if($alertsTable->num_rows() < 1) return false;
        return true;
    }

    public function unsub($array) {
        $this->db->where($array);
        $this->db->delete('alerts');
    }
}
