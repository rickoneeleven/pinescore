<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lemon extends CI_model {

    public function tallyScore(){
        $this->load->model('icmpmodel');
        $all_ips = $this->icmpmodel->getIPs();
        foreach ($all_ips->result() as $row)
        {
            $score = $this->db->get_where('stats', array('ip' => $row->ip));
            $count = $score->num_rows();
            $data_static = array(
                'score'    => $count,
                'datetime' => date('Y-m-d H:i:s')
            );
            $this->db->where('ip', $row->ip);
            $result = $this->db->get("stats_total");
            if($result->num_rows() > 0 ) {
                $this->db->where('ip', $row->ip);
                $this->db->update('stats_total', $data_static);
            } else {
                $data_static['ip'] = $row->ip;
                $this->db->insert('stats_total', $data_static);
            }
        }
    }

    public function scoreBaseline() {
        $this->db->order_by('score', 'asc');
        $query = $this->db->get('stats_total');
        $offset = round($query->num_rows() / 100 * 25);

        $this->db->order_by('score', 'asc');
        $query = $this->db->get('stats_total', 1, $offset);
        foreach ($query->result() as $row) {
            $data_static = array(
                'score'    => $row->score,
                'datetime' => date('Y-m-d H:i:s')
            );
            $this->db->where('ip', "baseline");
            $this->db->update('stats_total', $data_static);
            return $this->db->last_query();
        }
    }

    public function myScore($ip) {
        $baseline_query = $this->db->get_where('stats_total', array('ip' => "baseline"),1,0);
        $baseline = 0;
        foreach ($baseline_query->result() as $row) {
            $baseline = $row->score;
        }
        $baseline = 100 + $baseline;
        $mine = $this->db->get_where('stats', array('ip' => $ip));
        $return = $baseline - $mine->num_rows;
        if($return > 100) {$return = 100;}
        return $return;
    }

    public function pinescoreDaemon() {
        $this->load->model('sqlqu');
        $array['request_type'] = 'distinct_ips';
        $ping_ip_tableTable = $this->sqlqu->getPingIpTable($array);
        foreach($ping_ip_tableTable->result() as $row) {
            $new_score = $this->myScore($row->ip);
            $update_data = array(
                'pinescore'     => $new_score,
            );
            if($new_score < $row->pinescore) {

                $update_data['pinescore_change'] = date('Y-m-d H:i:00');
            } else if($new_score > $row->pinescore) {

                $update_data['pinescore_change'] = date('Y-m-d H:i:01');
            }
            $this->db->where('ip', $row->ip);
            $this->db->update('ping_ip_table', $update_data);
            unset($update_data);

            $hour_and_minute = date("i");
            if($hour_and_minute == "00") {
                $log_for_history = array(
                    'logged'        => date('Y-m-d H:i:s'),
                    'ms'            => $row->last_ms,
                    'pinescore'     => $new_score,
                    'ip'            => $row->ip,
                );
                $this->db->insert('historic_pinescore', $log_for_history);
                unset($log_for_history);
            }
        }
    }
}
