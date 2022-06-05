<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   ActionICMP extends CI_model {

    public function noPreviousEmailStatus($data_db, $last_result, $row) {
        $data2 = array( //update table status
            'last_email_status'  => $data_db['result'],
            'last_online_toggle' => date('Y-m-d H:i:s')
        );//if the staus is null, add it as it must be a new IP and we want a status. we run the if as we don't want to be updating this table every single time the the same status when things are okay
        $this->db->where('ip', $last_result['ip']);
        $this->db->update('ping_ip_table', $data2);

        $data_db8 = array( //no stats for this ip yet, create a new one and set it to zero
            'ip'       => $last_result['ip'],
            'score'    => 0,
            'datetime' => date('Y-m-d H:i:s'),
        );
        $exists = $this->db->get_where('stats_total', array('ip' => $row->ip)); //could have already been added by another client
        if($exists->num_rows() < 1) {
            $this->db->insert('stats_total', $data_db8); //if not stas already exists
        }
    }
} 
