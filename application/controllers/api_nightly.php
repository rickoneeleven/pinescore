<?php
    if ( ! defined('BASEPATH')) exit('No direct script access allowed');

        class api_nightly extends CI_Controller {
    
            public function index()
            {
                $this->load->model('table_maintenance_model');
                $maintenance_result = $this->table_maintenance_model->check_and_truncate_ping_table();
                log_message('info', 'Nightly cron: Table maintenance check completed with status: ' . $maintenance_result);

                $this->load->model('icmpmodel');
                $this->load->model('cellblock7');
                $this->load->model('email_dev_or_no');
                $this->load->model("cron_protect");            
                $this->load->model('groupscore');
                
                $this->cron_protect->AllowedIPs();        

                $old = "datetime < (NOW() - INTERVAL 48 HOUR)";
                $old_3year = "logged < (NOW() - INTERVAL 3 YEAR)";
                $old_1week = "datetime < (NOW() - INTERVAL 1 WEEK)";
                $stop_monitoring = "last_online_toggle < (NOW() - INTERVAL 1 MONTH)";
                
                $this->db->where($stop_monitoring);
                $expired = $this->db->get('ping_ip_table');
                foreach ($expired->result() as $row) {
                    $message = "";
                    if($this->session->userdata('hideOffline') == 1)
                        $message = "Note: You have 'Hide Offline Nodes > 72 Hours' ticked in your options.\r\n\r\n";
                    $message = $message.'You are receiving this email because "'.$row->note.'" ('.$row->ip.')'.
                        " has not been online in the last month. We automatically delete inactive nodes to keep our system optimum.\r\n\r\nYou can setup the alert again if you like at ".base_url()."\r\n\r\nThanks\nRyan";
                    $owner_email = $this->cellblock7->getOwnerEmail($row->owner);
                    $this->email->from(from_email, 'pinescore');
                    $this->email->to($owner_email); 	    
                    $this->email->bcc("ryan@pinescore.com"); 	    
                    $this->email->subject('Expired Alert: '.$row->note);
                    $this->email->message($message);
                    
                    $email_dev_array = array(
                        'from_class__method'            => 'api_nightly__index'
                    );
                    if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
                    echo $this->email->print_debugger();
                    
                    $this->db->where('id', $row->id);
                    $this->db->delete('ping_ip_table');

                    $this->db->where('ping_ip_id', $row->id);
                    $this->db->delete('alerts');
                }
                
                $this->db->where($old);
                $this->db->delete('verify_email');
                log_message('debug', 'Deleted old verify_email records: ' . $this->db->last_query());
                
                $this->db->where($old);
                $this->db->delete('stats');
                log_message('debug', 'Deleted old stats records: ' . $this->db->last_query());
                
                $this->db->where($old);
                $this->db->delete('stats_total');
                log_message('debug', 'Deleted old stats_total records: ' . $this->db->last_query());

                $this->db->where($old_3year);
                $this->db->delete('historic_pinescore');
                log_message('debug', 'Deleted old historic_pinescore records: ' . $this->db->last_query());

                $this->db->where("datetime < (NOW() - INTERVAL 7 DAY)");
                $this->db->delete('ping_result_table');
                log_message('debug', 'Deleted old ping_result_table records: ' . $this->db->last_query());
                
                $this->db->where("created_at < (NOW() - INTERVAL 90 DAY)");
                $this->db->delete('traceroutes');
                log_message('debug', 'Deleted old traceroutes records: ' . $this->db->last_query());
                
                $this->groupscore->CalulateShortTermGroupScore();
            }

            public function flushPingResultTable() {
                $old_24hours = 'datetime < (NOW() - INTERVAL 24 HOUR)';
                $this->db->where($old_24hours);
                $this->db->where('change', "0");
                $this->db->delete('ping_result_table');
                log_message('debug', 'Flushed ping_result_table: ' . $this->db->last_query());
            }

            public function onceAday() {
                $this->db->update('ping_ip_table', array('count_direction' => '-'));
            }
    }

?>
