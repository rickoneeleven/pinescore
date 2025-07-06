<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   ActionICMP extends CI_model {

    public function noPreviousEmailStatus($data_db, $last_result, $row) {
        $data2 = array(
            'last_email_status'  => $data_db['result'],
            'last_online_toggle' => date('Y-m-d H:i:s')
        );
        $this->db->where('ip', $last_result['ip']);
        $this->db->update('ping_ip_table', $data2);

        $data_db8 = array(
            'ip'       => $last_result['ip'],
            'score'    => 0,
            'datetime' => date('Y-m-d H:i:s'),
        );
        $exists = $this->db->get_where('stats_total', array('ip' => $row->ip));
        if($exists->num_rows() < 1) {
            $this->db->insert('stats_total', $data_db8);
        }
    }
} 
