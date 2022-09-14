<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class get_emailalerts extends CI_model {

    public function returnAlertsFromIDasArray($id) {
        $this->db->where('ping_ip_id', $id);
        $alertsTable = $this->db->get('alerts');
        $email_addresses = array();
        $count = 0;
        foreach($alertsTable->result() as $row) {
            $email_addresses[$count]['unsub_ref'] = $row->unsub_ref;
            $email_addresses[$count]['ping_ip_id'] = $row->ping_ip_id;
            $email_addresses[$count]['email'] = $row->email;
            $count++;
        }
        return $email_addresses;
    }

    //substr to remove first two chars ", " from returned string
    public function returnAlertsFromIDasString($id) {
        $this->db->where('ping_ip_id', $id);
        $alertsTable = $this->db->get('alerts');
        $email_addresses = "";
        foreach($alertsTable->result() as $row) {
            $email_addresses = $email_addresses.", ".$row->email;
        }
        $email_addresses = substr($email_addresses, 2);
        return $email_addresses;
    }
    
    public function recentAlert($id) {
        $lastLTAalertDate_older_than_week = "lastLTAalertDate > (NOW() - INTERVAL 1 WEEK)";
        $this->db->where($lastLTAalertDate_older_than_week);
        $this->db->where('ping_ip_id', $id);
        $alertsTable = $this->db->get('alerts');
        echo "<br>num rows: ".$alertsTable->num_rows()." (id: $id)<br>";
        if($alertsTable->num_rows() > 0) return TRUE;
        
        $update['lastLTAalertDate'] = date('Y:m:d H:i:s');
        $this->db->where('ping_ip_id', $id);
        $this->db->update('alerts', $update);
        return FALSE;
    }
}
