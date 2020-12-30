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
        $this->LTduplicateCheck();
        $groupsTable = $this->db->get('groups');
        foreach ($groupsTable->result() as $row) {
            $this->db->where('group_id', $row->id);
            $yesterday = "datetime < (CURDATE())"; //The date is returned as "YYYY-MM-DD" without time
            $this->db->where($yesterday);
            $group_shortterm_scoresTable = $this->db->get('group_shortterm_scores');
            if($group_shortterm_scoresTable->num_rows() < 1) die('no data to process');
            
            foreach($group_shortterm_scoresTable->result() as $gssrow) {
                $array_of_scores[] = $gssrow->score;
            }
            $number_of_scores = array_count_values($array_of_scores);
            arsort($number_of_scores);
            $middle_number = key($number_of_scores);
            
            $this->db->insert('group_longterm_scores', [
                'group_id' => $row->id,
                'score'    => $middle_number,
                'datetime' => date('Y-m-d H:i:s')
            ]);
            unset($array_of_scores);
        }
    }
    
    private function LTduplicateCheck() {
        $today = "datetime > (CURDATE())"; //The date is returned as "YYYY-MM-DD" without time
        $this->db->where($today);
        $group_longterm_scoresTable = $this->db->get('group_longterm_scores');
        if($group_longterm_scoresTable->num_rows() > 0) {
            $yesterday = "datetime < (CURDATE())"; //The date is returned as "YYYY-MM-DD" without time
            $this->db->where($yesterday);
            $this->db->delete('group_shortterm_scores');//remove shortterm scores from yesterday as we've 
            //already added to long term table
            die('RIP father - already successfully added data for today');
        }        
    }
    
    public function getTodayGroupScore($group_id) {
        $this->db->where('group_id', $group_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $group_shortterm_scoresTable = $this->db->get('group_shortterm_scores');
        return $group_shortterm_scoresTable->row('score');
    }
    
}
