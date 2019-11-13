<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   Locks extends CI_model {

    public function checkForLock($ip) {
        $this->db->where('ip', $ip);
        $node_locksTable = $this->db->get('node_locks');
        if($node_locksTable->num_rows() > 0) return true;
        return false;
    }

    public function lockHost($ip)
    {
        $this->db->insert('node_locks', array('ip' => $ip, 'locked' => '1', 'datetime' => date('Y-m-d H:i:s')));
    }

    public function releaseHost($ip)
    {
        $this->db->where('ip', $ip);
        $this->db->delete('node_locks');
    }

    public function removeOldLocks()
    {
        $lock_expires = "datetime < NOW() - INTERVAL 5 MINUTE";
        $this->db->where($lock_expires);
        $this->db->delete('node_locks');
    }

} 
