<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Cellblock7 extends CI_model
{
    public function getMyReports($userid)
    {
        $this->db->where('user_id', $userid);
        $this->db->order_by('name', 'ASC');

        return $this->db->get('groups');
    }

    public function getOwnerEmail($ownerid)
    {
        $this->db->where('id', $ownerid);
        $userDetails = $this->db->get('user');
        foreach ($userDetails->result() as $row) {
            return $row->email;
        }
    }

    public function addNodeToGroup($group_id, $ping_ip_id)
    {
        $this->load->model('group_association');
        $this->group_association->create([
            'group_id' => $group_id,
            'ping_ip_id' => $ping_ip_id,
            'user_id' => $this->session->userdata('user_id'),
            ]);
    }

    /**
     * array(
     * 'user_id'
     * 'group_id'
     * ).
     */
    public function groupPublicCheck($array)
    {
        $this->db->where('id', $array['group_id']);
        $groupsTable = $this->db->get('groups');

        if ($array['group_id'] != null) {
            if ($groupsTable->row('public') == '1') {
                return true;
            } elseif ($groupsTable->row('user_id') == $array['user_id']) {
                return true;
            }

            return false;
        }

        return true;
    }

    public function icmpTableData($group_id = null)
    {
        $this->load->model('icmpmodel');
        $this->load->model('lemon');
        $this->load->model('get_emailalerts');
        $user = $this->icmpmodel->getUserID();

        if (isset($group_id)) {
            $group_id_filter = ['group_id' => $group_id];
            $ips = $this->icmpmodel->getIPs($group_id_filter);
        } else {
            $data3 = ['owner' => $user];
            $ips = $this->icmpmodel->getIPs($data3); //get ips from ip table
        }
        $data2 = []; //if no results the array wont get created below so we have to declare here otherwise customers with no tables will get erros as it will try to return data2, but it does not exist
        foreach ($ips->result() as $row) {
            $data2[$row->ip]['note'] = $row->note;
            $data2[$row->ip]['alert'] = $this->get_emailalerts->returnAlertsFromIDasString($row->id);
            $data2[$row->ip]['id'] = $row->id;
            $data2[$row->ip]['score'] = $row->pinescore;
            $data2[$row->ip]['pinescore_change'] = $row->pinescore_change;
            $data2[$row->ip]['count'] = $row->count;
            $data2[$row->ip]['last_email_status'] = $row->last_email_status;
            $data2[$row->ip]['lastcheck'] = $row->last_ran;
            $data2[$row->ip]['ms'] = $row->last_ms;
            $data2[$row->ip]['public'] = $row->public;
            $data2[$row->ip]['average_longterm_ms'] = $row->average_longterm_ms;
            $data2[$row->ip]['lta_difference_algo'] = $row->lta_difference_algo;
            $data2[$row->ip]['last_online_toggle'] = $row->last_online_toggle;
            $data2[$row->ip]['count_direction'] = $row->count_direction;
        }

        return $data2;
    }

    public function getGroupName($id)
    {
        $this->db->where('id', $id);
        $grouped_reportsTable = $this->db->get('grouped_reports');

        return $grouped_reportsTable->row('name');
    }
}
