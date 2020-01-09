<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cellblock7 extends CI_model {

    public function getMyReports($userid) {
        $this->db->where('owner_id', $userid);
        $this->db->order_by('name', 'ASC');
        return $this->db->get('grouped_reports');
    }

    public function getOwnerEmail($ownerid) {
        $this->db->where('id', $ownerid);
        $userDetails = $this->db->get('user');
        foreach ($userDetails->result() as $row)
        {
            return $row->email;
        }
    }

    public function addNodeToGroup($group_id, $ping_ip_id) {
        $groupedReportsRow = $this->db->get_where('grouped_reports', array('id' => $group_id));
        $update_data = array(
            'ping_ip_ids'   => $groupedReportsRow->row('ping_ip_ids')."$ping_ip_id,",
        );
        $this->db->where('id', $group_id);
        $this->db->update('grouped_reports', $update_data);
    }

    /**
     * array(
     * 'owner_id'
     * 'group_id'
     * )
     */
    public function groupPublicCheck($array) {
        $this->db->where('id', $array['group_id']);
        $grouped_reportsTable = $this->db->get('grouped_reports');

        if($array['group_id'] != null) {
            if($grouped_reportsTable->row('public') == "1") return TRUE;
            else if($grouped_reportsTable->row('owner_id') == $array['owner_id']) return TRUE;
            return FALSE;
        }
        return TRUE;
    }

    public function icmpTableData($group_id=null) {
        $this->load->model('icmpmodel');
        $this->load->model('lemon');
        $this->load->model('get_emailalerts');
        $user = $this->icmpmodel->getUserID();

        if(isset($group_id)) {
            $group_id_filter = array('group_id' => $group_id);
            $ips = $this->icmpmodel->getIPs($group_id_filter);
        } else {
            $data3 = array('owner' => $user);
            $ips = $this->icmpmodel->getIPs($data3); //get ips from ip table
        }
        $data2 = array(); //if no results the array wont get created below so we have to declare here otherwise customers with no tables will get erros as it will try to return data2, but it does not exist
        foreach ($ips->result() as $row) {
            $data2[$row->ip]['note'] = $row->note;
            $data2[$row->ip]['alert'] = $this->get_emailalerts->returnAlertsFromIDasString($row->id);
            $data2[$row->ip]['id'] = $row->id;
            $data2[$row->ip]['score'] = $row->novaScore;
            $data2[$row->ip]['score_change_date'] = $row->novaScore_change;
            $data2[$row->ip]['count'] = $row->count;
            $data2[$row->ip]['last_email_status'] = $row->last_email_status;
            $data2[$row->ip]['lastcheck'] = $row->last_ran;
            $data2[$row->ip]['ms'] = $row->last_ms;
            $data2[$row->ip]['public'] = $row->public;
            $data2[$row->ip]['average_daily_ms'] = $row->average_daily_ms;
            $data2[$row->ip]['average_longterm_ms'] = $row->average_longterm_ms;
            $data2[$row->ip]['lta_difference_algo'] = $row->lta_difference_algo;
            $data2[$row->ip]['last_online_toggle'] = $row->last_online_toggle;
            $data2[$row->ip]['count_direction'] = $row->count_direction;
        }
        return $data2;
    }

    public function getGroupName($id) {
        $this->db->where('id', $id);
        $grouped_reportsTable = $this->db->get('grouped_reports');
        return $grouped_reportsTable->row('name');
    }
}
