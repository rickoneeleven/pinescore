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
        $groupsTable = $this->db->get('groups');
        foreach ($groupsTable->result() as $row) {
            $this->db->where('group_id', $row->id);
            $yesterday = "datetime < (NOW() - INTERVAL 1 DAY)";
            $this->db->where($yesterday);
            $group_shortterm_scoresTable = $this->db->get('group_shortterm_scores');
            foreach($group_shortterm_scoresTable->result() as $row) {
                if(!isset($groupData[$row->group_id])) 
                {
                    $groupData[$row->group_id] = [];
                    $groupData[$row->group_id]['scores'] = [];
                }
                array_push($groupData[$row->group_id]['scores'],$row->score);
                    vdebug($row);
            }
        }
        vdebug($groupData);
        $counted = array_count_values($array);
        arsort($counted);
        return(key($counted));
            //get all group scores for the day
            //use pinescore algo to pick out the one most occuring for the day
            //insert into the longterm table against groupid
    }
    
    public function algoForLongTermGroupScore($group_id) {

        $group_associationsTable = $this->group_association->read([
            'group_id' => $group_id,
        ]);
    }
}
