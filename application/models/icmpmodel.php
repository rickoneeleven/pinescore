<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class IcmpModel extends CI_model
{

    public function getIPs($filter = null, $searchTerm = null)
    {
        $this->load->model('group_association');
        $this->load->model('group');

        $order_by = "(CASE WHEN last_email_status='Offline' THEN last_online_toggle END) DESC, count DESC, last_email_status, lta_difference_algo, pinescore, note";
        if (isset($filter['order_alpha'])) {
            $this->db->order_by('note');
        }
        if (!isset($filter['group_id'])) {
            $this->db->group_by('ip');

            $this->db->order_by($order_by);
        }
        if (isset($filter['owner'])) {
            $query = $this->ownerSet($filter, $searchTerm);
        } elseif (isset($filter['group_id'])) {
            $filter_grp_id = $filter['group_id'];
            $this->db->where('group_id', $filter_grp_id);
            $grouped_report_row = $this->db->get('group_associations');
            if ($grouped_report_row->num_rows() < 1) {
                die('error code: if you need a cab just let Davey boy know');
            }
            $ping_ids_array = $this->group_association->return_array_pingIpIds_from_group_id(
                [
                    'group_id' => $filter_grp_id,
                ]);
            $securty_check = $this->group->readGroupByID([
                'group_id' => $filter_grp_id,
            ]);

            $ping_ids_in_grp = implode(', ', $ping_ids_array);
            if ($this->session->userdata('hideOffline') == 1) {
                $query_request = "last_online_toggle > (NOW() - INTERVAL 72 HOUR) AND id IN ($ping_ids_in_grp) 
                    OR last_email_status = 'Online' AND id IN ('$ping_ids_in_grp')";
                $this->db->where($query_request);
            } else {
                $this->db->where_in('id', $ping_ids_array);
            }
            $this->db->order_by($order_by);
            
            $this->addSearchFilter($searchTerm);
            
            $query = $this->db->get('ping_ip_table');
        } elseif (isset($filter['status'])) {
            $status = $filter['status'];
            
            if (!empty($searchTerm)) {
                $this->db->where('last_email_status', $status);
                $this->addSearchFilter($searchTerm);
                $query = $this->db->get('ping_ip_table');
            } else {
                $query = $this->db->get_where('ping_ip_table', ['last_email_status' => $status]);
            }
        } elseif (!isset($filter['single_ip'])) {
            
            $this->addSearchFilter($searchTerm);
            
            $query = $this->db->get('ping_ip_table');
        } else {
            $ip = $filter['single_ip'];
            $query = $this->db->get_where('ping_ip_table', ['ip' => $ip], 1, 0);
        }

        return $query;
    }

    private function addSearchFilter($searchTerm)
    {
        if (!empty($searchTerm)) {
            $this->db->where("(LOWER(note) LIKE '%" . $this->db->escape_like_str(strtolower($searchTerm)) . "%' OR ip LIKE '%" . $this->db->escape_like_str($searchTerm) . "%')");
        }
    }

    private function ownerSet($filter, $searchTerm = null)
    {
        $owner = $filter['owner'];
        $owner_escaped = $this->db->escape($owner);
        if ($this->session->userdata('hideOffline') == 1 && !isset($filter['group_creation'])) {
            $query_request = 'last_online_toggle > (NOW() - INTERVAL 72 HOUR) AND owner = '.$owner_escaped." OR last_email_status = 'Online' AND owner = ".$owner_escaped;
            if (!empty($searchTerm)) {
                $this->db->where($query_request);
                $this->addSearchFilter($searchTerm);
                $query = $this->db->get('ping_ip_table');
            } else {
                $query = $this->db->get_where('ping_ip_table', $query_request);
            }
        } else {
            
            if (!empty($searchTerm)) {
                $this->db->where('owner', $owner);
                $this->addSearchFilter($searchTerm);
                $query = $this->db->get('ping_ip_table');
            } else {
                $query = $this->db->get_where('ping_ip_table', ['owner' => $owner]);
            }
        }

        return $query;
    }

    public function report($request, $change)
    {
        $ip_id = $this->db->escape($request['ip_id']);
        $owner = $request['owner'];

        $query['pi'] = $this->db->query("SELECT * FROM ping_ip_table WHERE id = $ip_id AND owner = $owner");

        if ($query['pi']->num_rows() < 1) {
            $query['pi'] = $this->db->query("SELECT * FROM ping_ip_table WHERE id = $ip_id");
            if($query['pi']->num_rows() < 1) {
                echo 'Node has probably been deleted from the syste, sad face';
                die();
            }
            $public_check = $query['pi']->row();
            if ($public_check->public != 1) {
                echo 'Please '.anchor('', 'login').' to see this report. It has not been configured for public access.';
                die();
            }
        }

        $row = $query['pi']->row();
        $ip = $row->ip;

        $this->db->order_by('datetime', 'desc');
        if ($change == 1) {
            $query['report'] = $this->db->get_where('ping_result_table', ['ip' => $ip,
                                 'change'                                      => $change, ]);
        } else {
            $query['report'] = $this->db->get_where('ping_result_table', ['ip' => $ip]);
        }

        return $query;
    }

    public function lastResultResult($ip)
    {
        $this->db->order_by('datetime', 'desc');
        $query = $this->db->get_where('ping_result_table', ['ip' => $ip], 11, 0);
        $average = [];
        foreach ($query->result() as $row) {
            if (!isset($data['result'])) {
                $data['result'] = $row->result;
            }
            $average[] = $row->ms;
        }

        $average = $this->remove_element($average, 0);
        if (empty($average)) {
            $data['average'] = 0;
        } else {
            $data['average'] = array_sum($average) / count($average);
        }

        return $data;
    }

    public function emailAlert($data, $ip, $id)
    {
        $this->load->model('html_email');
        $this->db->order_by('datetime', 'desc');
        $report = $this->db->get_where('ping_result_table', ['ip' => $ip, 'change' => 1], 10, 0);
        $last10 = '';
        foreach ($report->result() as $row) {
            $datetime = strtotime($row->datetime);
            $datetime = date('d-m-Y - H:i:s', $datetime);
            $last10 = $last10.'<strong>'.$datetime.'</strong> | ';
            $last10 = $last10.'<strong>'.$row->result.'</strong> | ';
            if ($row->email_sent == 1) {
                $last10 = $last10.' - In this state for over a minute*';
            }
            $last10 = $last10.'<br>';
        }

        foreach ($data['alert'] as $alert) {
            $this->email->from(from_email, 'pinescore');
            $this->email->to($alert['email']);
            $this->email->subject($data['note'].' is now '.$data['current']);
            $this->email->set_mailtype('html');
            $this->load->model('email_dev_or_no');
            $array['body'] = 'You are receiving this email because you have been setup to receive alerts when the online status of "'.$data['note'].'"'.' changes. <br><br><strong>'.$data['note'].' is now '.$data['current'].'</strong><br>
        <br><br>Recent Activity <br>'.$last10.'<br><br>*You may see the online/offline status switches much more than the number of email alerts you receive. This is because we will only email you when the host has been down for a period of time rather than each dropped request. You can see a full report: <a href="'.base_url().'tools/report/'.$id.'">here</a>
        <br><br><br><br><a href="'.base_url().'unsubscribe_alert/go/'.$alert['ping_ip_id'].'/'.$alert['unsub_ref'].'">Remove me from this node alert</a><br><br>process parent id: '.$data['process_id_parent'].' child id: '.$data['process_id'];
            $this->email->message($this->html_email->htmlFormatted($array));
            $email_dev_array = [
                'from_class__method' => 'icmpmodel__emailAlert',
            ];
            if ($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) {
                $this->email->send();
            }
            echo 'email sent to: '.$alert['email'];
            echo $this->email->print_debugger();
        }
    }

    public function onOrOff($result)
    {
        if ($result == 0) {
            return 'Offline';
        } else {
            return 'Online';
        }
    }

    public function ipExists($ip, $owner)
    {
        $query = $this->db->get_where('ping_ip_table', ['ip' => $ip,
                    'owner'                                  => $owner, ]
                      );

        return $query;
    }

    public function monitorCount($owner)
    {
        $query = $this->db->get_where('ping_ip_table', ['owner' => $owner]);

        return $query;
    }

    public function getUserID()
    {
        // Prefer explicit user_id from session to avoid false fallbacks
        $user_id = $this->session->userdata('user_id');
        if (!empty($user_id)) {
            return $user_id;
        }

        // Legacy fallback: some flows used user_email as a proxy for auth
        $user_email = $this->session->userdata('user_email');
        if (!empty($user_email)) {
            $user_id = $this->session->userdata('user_id');
            if (!empty($user_id)) {
                return $user_id;
            }
        }

        // Demo user when no authenticated session
        return 13;
    }

    private function remove_element($array, $value)
    {
        foreach (array_keys($array, $value) as $key) {
            unset($array[$key]);
        }

        return $array;
    }
}
