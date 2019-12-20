<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   ActionICMP extends CI_model {

    /**
     * $ip = returned get table statement from ping_ip_table
     */
    public function checkICMP($ip) {
        $this->load->model('techbits_model');
        $this->load->model('icmpmodel');
        $this->load->model('lemon');
        $this->load->model('average30days_model');
        $this->load->model('locks');
        $this->lemon->icmpControl();
        $process_id_parent = uniqid();
        $perf_started = microtime(true);
        foreach ($ip->result() as $row)
        {
            $perf_started_inforeach = microtime(true);
            if($this->locks->checkForLock($row->ip)) break 1;
            $this->locks->lockHost($row->ip);
            $last_result = $this->icmpmodel->lastResult($row->ip);
            $last_result_result = $this->icmpmodel->lastResultResult($row->ip);

            $ping_ms = $this->techbits_model->pingv2($row->ip); //up or down
            $perf_mon2 = number_format(microtime(true) - $perf_started_inforeach,0);
            $this->db->insert('perfmon', array(
                'name'                      => "checkICMP, in loop",
                'datetime'                  => date('Y-m-d H:i:s'),
                'seconds'                   => $perf_mon2,
                'other'                     => "proc id: ".$process_id_parent,
                )
            );
            
            $result = $this->icmpmodel->onOrOff($ping_ms); //convert to Online or Offline word from number
            echo $row->ip.":".$result."<br>";
            if($result != $last_result_result['result']) {
                $change = 1;
                $this->lemon->score($row->ip);
            } else {
                $change = 0;
            }

            $data_db = array(
                'ip' => $row->ip ,
                'datetime' => date('Y-m-d H:i:s'),
                'ms' => $ping_ms,
                'result' => $result,
                'change' => $change,
                'email_sent' => 0,
            );
            $this->db->insert('ping_result_table', $data_db); //insert into big results table

            $data_static = array( //to stop the users/auto refresh table talking to results table, we store it here
                'last_ran' => date('Y-m-d H:i:s'),
                'last_ms' => $last_result_result['average']
            );
            $this->db->where('ip', $row->ip);
            $this->db->update('ping_ip_table', $data_static); //insert into quick table

            if($data_db['result']=="Online") { //update last online date toggle so we can filter for stuff that is offline with a 72hour+ online toggle and then it can be hidden 
                $data2 = array( //update table status
                    'last_online_toggle' => date('Y-m-d H:i:s'),
                );
                $this->db->where('ip', $row->ip);
                $this->db->update('ping_ip_table', $data2); 
            }
            $arrayForAlgo = array(
                    'last_ms'               => $last_result_result['average'],
                    'average_longterm_ms'   => $row->average_longterm_ms,
                );
            $lta_difference_algo = $this->average30days_model->ltaCurrentMsDifference($arrayForAlgo);
            $this->db->where('ip', $row->ip);
            $this->db->update('ping_ip_table', array('lta_difference_algo' => $lta_difference_algo));

            foreach($last_result as $last_result) {
                $last_result['process_id_parent'] = $process_id_parent;
                $last_result['process_id'] = uniqid();
                $this->hasStatusChanged($last_result, $data_db, $row);
            }
            $this->locks->releaseHost($row->ip);
        }
        $perf_mon1 = number_format(microtime(true) - $perf_started,0);
        $this->db->where('id', 7);
        $this->db->insert('perfmon', array(
            'name'                      => "checkICMP, out of loop",
            'datetime'                  => date('Y-m-d H:i:s'),
            'seconds'                   => $perf_mon1,
            'other'                     => "proc id: ".$process_id_parent,
            )
        );
        $this->locks->removeOldLocks();
    }

    public function noPreviousEmailStatus($data_db, $last_result, $row) {
        $data2 = array( //update table status
            'last_email_status' => $data_db['result'],
            'last_online_toggle' => date('Y-m-d H:i:s')
        );//if the staus is null, add it as it must be a new IP and we want a status. we run the if as we don't want to be updating this table every single time the the same status when things are okay
        $this->db->where('ip', $last_result['ip']);
        $this->db->update('ping_ip_table', $data2);

        $data_db8 = array( //no stats for this ip yet, create a new one and set it to zero
            'ip' => $last_result['ip'],
            'score' => 0
        );
        $exists = $this->db->get_where('stats_total', array('ip' => $row->ip)); //could have already been added by another client
        if($exists->num_rows() < 1) {
            $this->db->insert('stats_total', $data_db8); //if not stas already exists
        }
    }

    /**
     * $last_result = array(
     *  'last_email_status'     => string,
     *  'note'                  => string,
     *  'alert'                 => array('emailaddress' => 'unsubref'),
     *  'count' => int,
     *  'owner' => int,
     *  'ip' => string,
     * )
     * if stored status is different from current status go into IF, or if count is bigger than 0, go into IF, as we need to resolve absolute state
     */
    private function hasStatusChanged($last_result, $data_db, $row) {
        if($last_result['last_email_status'] != $data_db['result'] || $last_result['count'] > 0) {
            $last_result['current'] = $data_db['result'];

            if ($last_result['count'] > 9 && $last_result['last_email_status'] != $data_db['result']) {
                if ($last_result['alert'] != "") {
                    $last_result['time'] = date('Y-m-d H:i:s'); //time for subjet of email

                    $started = microtime(true);

                    $data_static2 = array(
                        'email_sent' => 1
                    );
                    $this->db->order_by('id', 'desc');
                    $this->db->where('change', 1);
                    $this->db->where('ip', $row->ip);
                    $this->db->limit(1);
                    $this->db->update('ping_result_table', $data_static2);

                    $end = microtime(true);
                    $difference = $end - $started;
                    $queryTime = number_format($difference, 0);
                    $this->db->where('id', 3);

                    $this->db->update('other', array('value' => date('Y-m-d H:i:s') . " | query took $queryTime seconds | updated to: ".$data_db['result']." | IP: ".$row->ip,));
                    $this->icmpmodel->emailAlert($last_result, $row->ip, $row->id); //email status change

                    unset($last_result['email']);
                    unset($last_result['unsub_ref']);
                }

                $data2 = array( //update table status
                    'last_email_status' => $data_db['result'],
                    'count' => 0
                );
                $this->db->where('ip', $row->ip);
                $this->db->update('ping_ip_table', $data2); 

            } else if($last_result['last_email_status'] != $data_db['result']) { //if the status is different, increate the failure count
                $data_db2 = array( //increment the count
                    'count' => ++$last_result['count'],
                    'count_direction' => "Up"
                );
                $this->db->where('ip', $last_result['ip']);
                $this->db->update('ping_ip_table', $data_db2);  
            } else { //otherwise decrease it until we hit zero on the count and stop this whole loop thing
                $data_db2 = array( //increment the count
                    'count' => --$last_result['count'],
                    'count_direction' => "Down"
                );
                $this->db->where('ip', $last_result['ip']);
                $this->db->update('ping_ip_table', $data_db2);
            }
        }

        if($last_result['last_email_status'] == "") { //fix for if no email status exists make sure it's created
            $this->noPreviousEmailStatus($data_db, $last_result, $row);
        }
    }
} 
