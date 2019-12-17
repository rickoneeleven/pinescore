<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class IcmpModel extends CI_model {
    
    /**
     * we have to add "' '" to the end of $ping_ids_in_grp, otherwise it ends ", " and causes a db failure when
     * passing to the where clause.
     */
    public function getIPs($filter=null) {
        $order_by = "(CASE WHEN last_email_status='Offline' THEN last_online_toggle END) DESC, count DESC, last_email_status, lta_difference_algo, novaScore, note";
        if(isset($filter['order_alpha'])) $this->db->order_by('note');
        if(!isset($filter['group_id'])) {
            $this->db->group_by('ip'); //if two customers monitor the same ip, ive already tested and they get
                //the note name of whatever they assigned, so no problem
            $this->db->order_by($order_by);
        }
		if(isset($filter['owner'])) {
            $query = $this->ownerSet($filter);
		} else if(isset($filter['group_id'])) {
            $filter_grp_id = $filter['group_id']; 
            $this->db->where('id', $filter_grp_id);
            $grouped_report_row = $this->db->get('grouped_reports');
            if($grouped_report_row->num_rows() < 1) {
                die('error code: if you need a cab just let Davey boy know');
            }
            $ping_ids_in_grp = $grouped_report_row->row('ping_ip_ids');
            $ping_ids_in_grp = $ping_ids_in_grp."' '";
            $ping_ids_array = explode(',', $ping_ids_in_grp);

            if($this->session->userdata('hideOffline') == 1) {
                $query_request = "last_online_toggle > (NOW() - INTERVAL 72 HOUR) AND id IN ($ping_ids_in_grp) 
                    OR last_email_status = 'Online' AND id IN ('$ping_ids_in_grp')";
                $this->db->where($query_request);
            } else {
                $this->db->where_in('id', $ping_ids_array);
            }
            $this->db->order_by($order_by);
            $query = $this->db->get('ping_ip_table');
            //echo $this->db->last_query();
        } else if(isset($filter['status'])) {
            $status = $filter['status'];
            $query = $this->db->get_where('ping_ip_table', array('last_email_status' => $status));
		} else if (!isset($filter['single_ip'])) {
			$query = $this->db->get('ping_ip_table');
		} else {
			$ip = $filter['single_ip'];
			$query = $this->db->get_where('ping_ip_table', array('ip' => $ip), 1, 0);
		}
		return $query;
    }

    private function ownerSet($filter) {
        $owner = $filter['owner'];
        if($this->session->userdata('hideOffline') == 1 && !isset($filter['group_creation'])) {
            $query_request = "last_online_toggle > (NOW() - INTERVAL 72 HOUR) AND owner = ".$owner." OR last_email_status = 'Online' AND owner = ".$owner;
            $query = $this->db->get_where('ping_ip_table', $query_request);
        } else {
            $query = $this->db->get_where('ping_ip_table', array('owner' => $owner));
        }

        return $query;
    }
	
    public function report($request, $change) {
	$ip_id = $this->db->escape($request['ip_id']); //don't like using array option in query, not sure if works
	$owner = $request['owner'];
	
    $query['pi'] = $this->db->query("SELECT * FROM ping_ip_table WHERE id = $ip_id AND owner = $owner"); //make sure your user ID has acceess to report for this IP
	
	if ($query['pi']->num_rows() < 1) //never returned any rows so user does not have access or is not logged in
	{
	    $query['pi'] = $this->db->query("SELECT * FROM ping_ip_table WHERE id = $ip_id");
	    $public_check = $query['pi']->row();
	    if($public_check->public!=1) { //if the report is not set as public and you don't have access
		echo "Please " .anchor('', 'login')." to see this report. It has not been configured for public access.";
		die();
	    }
	}
	
	$row = $query['pi']->row(); 
	$ip = $row->ip;
	
        $this->db->order_by("datetime", "desc");
	if($change==1) {
	    $query['report'] = $this->db->get_where('ping_result_table', array('ip' => $ip,
								 'change' => $change));    
	} else {
	    $query['report'] = $this->db->get_where('ping_result_table', array('ip' => $ip));
	}
        
        return $query;
    }

    public function lastResult($ip) {
        $this->load->model('get_emailalerts');
        $query = $this->db->get_where('ping_ip_table', array('ip' => $ip));
        foreach ($query->result() as $row) {
            $data[$row->owner] = array(
                'last_email_status' => $row->last_email_status,
                'note' => $row->note,
                'alert' => $this->get_emailalerts->alertsFromIDasArray($row->id),
                'count' => $row->count,
                'owner' => $row->owner,
                'ip' => $row->ip,
                'average_longterm_ms' => $row->average_longterm_ms,
            );
        }
        return $data;
    }

    /**
     * we remove any pings that are 0, as these are dropped icmps and i don't want them screwing with average. if they are
     * all 0, we then define 0 and return, rather than trying to work out average of zero.
     */
    public function lastResultResult($ip) {
	    $this->db->order_by("datetime", "desc");
        $query = $this->db->get_where('ping_result_table', array('ip' => $ip), 11, 0); //limit and offset
	    $average = array();
        foreach ($query->result() as $row)
        {
            if(!isset($data['result'])) {$data['result'] = $row->result;} //only set it for the first result which is the most recent, as we're pulling 11 for the average
	    $average[] = $row->ms;
        }

        $average = $this->remove_element($average,0);
        if(empty($average)) {
            $data['average'] = 0;
        } else {
            $data['average'] = array_sum($average) / count($average);
        }
	    return $data;
    }
    
    public function emailAlert($data, $ip, $id)
    {
        $this->load->model('html_email');
        $this->db->order_by("datetime", "desc");
        $report = $this->db->get_where('ping_result_table', array('ip' => $ip, 'change' => 1), 10, 0); //lim,off
        $last10 = "";
        foreach ($report->result() as $row) {
            $datetime = strtotime($row->datetime);
            $datetime = date( 'd-m-Y - H:i:s', $datetime );
            $last10 = $last10 . "<strong>" . $datetime . "</strong> | ";
            $last10 = $last10 . "<strong>" . $row->result . "</strong> | ";
            if ($row->email_sent == 1) {
                $last10 = $last10 . " - In this state for over a minute*";
            }
            $last10 = $last10 . "<br>";
        }

        foreach ($data['alert'] as $alert) {
            $this->email->from('noreply@novascore.io', 'novascore');
            $this->email->to($alert['email']);
            $this->email->subject($data['note'] . " is now " . $data['current']);
            $this->email->set_mailtype("html");
            $this->load->model('email_dev_or_no');
            $array['body'] = 'You are receiving this email because you have been setup to receive alerts when the online status of "' . $data['note'] . '"' . " changes. <br><br><strong>".$data['note'] . " is now " . $data['current'] . "</strong><br>
        <br><br>Recent Activity <br>" . $last10 . "<br><br>*You may see the online/offline status switches much more than the number of email alerts you receive. This is because we will only email you when the host has been down for a period of time rather than each dropped request. You can see a full report: <a href=\"" . base_url() . "tools/report/" . $id . "\">here</a>
        <br><br><br><br><a href=\"" . base_url() . "unsubscribe_alert/go/".$alert['ping_ip_id']."/".$alert['unsub_ref']."\">Stop email alerts</a><br><br>process parent id: ".$data['process_id_parent']." child id: ".$data['process_id'];
            $this->email->message($this->html_email->htmlFormatted($array));
            $email_dev_array = array(
                'from_class__method'            => 'icmpmodel__emailAlert'
            );
            if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
            echo "email sent to: ".$alert['email'];
            echo $this->email->print_debugger();
            unset($import); //good practice
            $import = array(
                'owner' => $data['owner'],
                'note' => $data['note'],
                'datetime' => $data['time'],
                'status' => $data['current']
            );
            $this->db->insert('history_email_alerts', $import);
        }
    }
    
    public function onOrOff($result) {
        if($result == 0) {
            return "Offline";
        } else {
            return "Online";
        }
    }
    
    public function ipExists($ip, $owner) {
        $query = $this->db->get_where('ping_ip_table', array('ip' => $ip,
					'owner' => $owner)
				      );
        return $query;    
    }
    
    public function monitorCount($owner) {
        $query = $this->db->get_where('ping_ip_table', array('owner' => $owner));
        return $query;  
    }
	
    public function getUserID() {
	if(($this->session->userdata('user_email')!="")) {//is user lgged in check [true] else
	    $user = $this->session->userdata('user_id');
	} else {
	    $user = 13;
    	}
        return $user;
    }

    private function remove_element($array, $value) {
        foreach (array_keys($array, $value) as $key) {
            unset($array[$key]);
        }
        return $array;
    }
}
