<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Flipflop_node_alert extends CI_controller
{
    public function index()
    {
        $this->load->model("cron_protect");
        $this->cron_protect->AllowedIPs();
        
        $this->db->where('ping_ip_id', 181);
        $alertsTable = $this->db->get('alerts');
        if($alertsTable->num_rows() < 1) {
            $ping_id_181 = array(
                'email' => "r_181_1@pinescore.com",
                'ping_ip_id' => 181,
                'unsub_ref' => "AsdaSSF",
                'updated' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('alerts', $ping_id_181);

        }

        $update_array = array(
            'count'     =>9,
            'last_email_status'     =>"Offline",
        );

        $this->db->where('ip', "8.8.8.8");
        $this->db->update('ping_ip_table', $update_array);
    }

}