<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class GroupScore extends CI_model
{
    public function calulateShortTermGroupScore()
    {
        $this->load->model('group');
        
        $groupsTable = $this->db->get('groups');
        foreach ($groupsTable->result() as $row) {
            $group_score = $this->algoForShortTermGroupScore($row->id);
            $insert = [
                'group_id' => $row->id,
                'score'    => $group_score,
                'datetime' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('group_shortterm_scores', $insert);
        }
    }

    public function algoForShortTermGroupScore($group_id)
    {
        $this->load->model('group_association');

        $group_associationsTable = $this->group_association->read([
            'group_id' => $group_id,
        ]);
        $total_node_count = $group_associationsTable->num_rows();
        $lower_than_score_50_count = 0;

        foreach ($group_associationsTable->result() as $row) {
            $this->load->model('ping_ip_id');

            $ping_ip_idRow = $this->ping_ip_id->getPingIpID($row->ping_ip_id);
            if ($ping_ip_idRow->row('pinescore') < 50) {
                ++$lower_than_score_50_count;
            }
        }
        $decrease = $total_node_count - $lower_than_score_50_count;
        $decrease_percent = $decrease / $total_node_count * 100;
        $decrease_from_100percent = 100 - $decrease_percent;
        $rounded = round($decrease_from_100percent);
        $group_score = 100 - $rounded;
        return $group_score; 
    }

    public function calulateLongTermGroupScore()
    {
        //get all group_ids
        //foreach group id
            //get all group scores for the day
            //use pinescore algo to pick out the one most occuring for the day
            //insert into the longterm table against groupid
    }
}
