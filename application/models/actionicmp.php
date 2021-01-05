<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   ActionICMP extends CI_model {

    public function noPreviousEmailStatus($data_db, $last_result, $row) {
        $data2 = array( //update table status
            'last_email_status'  => $data_db['result'],
            'last_online_toggle' => date('Y-m-d H:i:s')
        );//if the staus is null, add it as it must be a new IP and we want a status. we run the if as we don't want to be updating this table every single time the the same status when things are okay
        $this->db->where('ip', $last_result['ip']);
        $this->db->update('ping_ip_table', $data2);

        $data_db8 = array( //no stats for this ip yet, create a new one and set it to zero
            'ip'       => $last_result['ip'],
            'score'    => 0,
            'datetime' => date('Y-m-d H:i:s'),
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
                    'count'             => 0
                );
                $this->db->where('ip', $row->ip);
                $this->db->update('ping_ip_table', $data2); 

            } else if($last_result['last_email_status'] != $data_db['result']) { //if the status is different, increate the failure count
                $data_db2 = array( //increment the count
                    'count'           => ++$last_result['count'],
                    'count_direction' => "Up"
                );
                $this->db->where('ip', $last_result['ip']);
                $this->db->update('ping_ip_table', $data_db2);  
            } else { //otherwise decrease it until we hit zero on the count and stop this whole loop thing
                $data_db2 = array( //increment the count
                    'count'           => --$last_result['count'],
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
