<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lemon extends CI_model {

    public function score($ip) {
        $data_db = array(
            'ip' => $ip,
            'datetime' => date('Y-m-d H:i:s'),
            'score' => -1
        );
        $this->db->insert('stats', $data_db);
    }

    public function tallyScore(){ //all this is used for is generating a list of scores so when we use scoreBaseline() and look set the offset to 10% deep, it has numbers to work with
        $this->load->model('icmpmodel');
        $all_ips = $this->icmpmodel->getIPs();
        foreach ($all_ips->result() as $row)
        {
            $score = $this->db->get_where('stats', array('ip' => $row->ip));
            $count = $score->num_rows();
            $data_static = array( //to stop the users/auto refresh table talking to results table, we store it here
                'score' => $count,
                'datetime' => date('Y-m-d H:i:s')
            );
            $this->db->where('ip', $row->ip);
            $this->db->update('stats_total', $data_static);
        }
    }

    public function scoreBaseline() {
        $this->db->order_by('score', 'asc');
        $query = $this->db->get('stats_total'); //limit, offset
        $offset = round($query->num_rows() /100*25); //25% best score offset

        $this->db->order_by('score', 'asc'); //you have to set this again as it resets after db query
        $query = $this->db->get('stats_total', 1, $offset); //limit, offset
        foreach ($query->result() as $row) {
            $data_static = array( //to stop the users/auto refresh table talking to results table, we store it here
                'score' => $row->score
            );
            $this->db->where('ip', "baseline"); //where we store the baseline for all compares
            $this->db->update('stats_total', $data_static); //insert into quick table
            return $this->db->last_query();
        }
    }

    public function myScore($ip) {
        $baseline = $this->db->get_where('stats_total', array('ip' => "baseline"),1,0); //limit offset
        foreach ($baseline->result() as $row) {
            $baseline = $row->score;
        }
        $baseline = 100+$baseline; //how many failures are counted as acceptable because my server may have caused a few bad returns
        $mine = $this->db->get_where('stats', array('ip' => $ip));
        $return = $baseline - $mine->num_rows();
        if($return >100) {$return =100;} //baseline may be 5 failures, this client has had 4, giving it a score of 101
        return $return;
    }

    /**
     * changed control to hit opendns servers, as they are further away, higher ms. was finding when linode were
     * having routing issues, they'd cause all the nodes to fail, and as google control was still returning
     * results as it was so close, confirmed the other circuits had gone offline. when in fact, they were online,
     * it was just linode having issues hitting further out nodes
     */
    public function icmpControl() {
        $this->load->model('techbits_model');
        $opendns = $this->techbits_model->pingv2('opendns.com',1);

        if($opendns > 1) {
            echo "<p>control passed</p>";
        } else {
            $usatoday = $this->techbits_model->pingv2('usatoday.com', 1);
            if($usatoday > 1) {
                 echo "<p>control passed</p>";
            } else {
                $data = array('datetime' => date('Y-m-d H:i:s'),
                    'status' => "Failed");
                $this->db->insert('control', $data);
                $this->email->from('noreply@novascore.io', 'Watch Dog');
                $this->email->to('workforward@novascore.io');
                $this->email->subject('Control Failed');
                $this->email->message('You are receiving this email because your server was just about to fail a client but failed the control check.');

                $this->email->send();
                die("<p>control failed</p>");
            }
        }
    }

    public function controlResults() {
        $this->db->Order_By('datetime', 'DESC');
        return $this->db->get('control');
    }

    //calculates the novaScore for all monitored IPs and updates database with score. This used to be done on the
    //fly, each time a user refreshed a page/group that contained their node. now this is just done once a
    //minute.
    //we also try and rand which should hit about once a day, and store score, ip, time and ms for a historical
    //record.
    public function novaScoreDaemon() {
        $this->load->model('sqlqu');
        $array['request_type'] = 'distinct_ips';
        $ping_ip_tableTable = $this->sqlqu->getPingIpTable($array);
        foreach($ping_ip_tableTable->result() as $row) {
            $novaScore = $this->myScore($row->ip);
            $update_data = array(
                'novaScore'     => $novaScore,
            );
            if($this->myScore($row->ip) < $row->novaScore) {
                $update_data['novaScore_change'] = date('Y-m-d H:i:s');
            }
            $this->db->where('ip', $row->ip);
            $this->db->update('ping_ip_table', $update_data);
            unset($update_data);

            $hour_and_minute = date("Hi");
            if($hour_and_minute == "0030" || $hour_and_minute == "0630" || $hour_and_minute == "1230" ||
            $hour_and_minute == "1830") {
                $log_for_history = array(
                    'logged'        => date('Y-m-d H:i:s'),
                    'ms'            => $row->last_ms,
                    'novaScore'     => $novaScore,
                    'ip'            => $row->ip,
                );
                $this->db->insert('historic_novaScore', $log_for_history);
                unset($log_for_history);
            }
        }
    }
}
