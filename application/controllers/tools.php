<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tools extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->pingAdd();
    }

    public function export_csv($group_id = null)
    {
        try {
            $this->load->model('cellblock7');
            $this->load->model('icmpmodel');
            $this->load->model('securitychecks');

            if ($group_id) {
                $array = [
                    'user_id'       => $this->session->userdata('user_id'),
                    'group_id'      => $group_id,
                    'error_message' => 'Access denied: This group is private'
                ];
                
                if (!$this->cellblock7->groupPublicCheck($array)) {
                    echo $array['error_message'];
                    return;
                }
            }

            if ($group_id) {
                $ips = $this->cellblock7->icmpTableData($group_id);
            } else {
                $user = $this->icmpmodel->getUserID();
                $data3 = ['owner' => $user];
                $ips = $this->cellblock7->icmpTableData(); 
            }

            $csv_content = "Note,Status,IP\n";

            foreach ($ips as $ip => $latest) {

                $note = str_replace('"', '""', $latest['note']);
                if (strpos($note, ',') !== false) {
                    $note = '"' . $note . '"';
                }
                
                $csv_content .= $note . ',' . 
                                $latest['last_email_status'] . ',' . 
                                $ip . "\n";
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="pinescore_export_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $csv_content;
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'CSV Export Error: ' . $e->getMessage());

            header('Content-Type: text/plain');
            echo "An error occurred while generating CSV. Please try again.";
        }
    }

    public function getIcmpDataJson($group_id = null)
    {
        $this->load->model('cellblock7');
        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');
        $this->load->model('average30days_model');
        $this->load->model('group');
        $this->load->model('group_monthly_scores');
        $this->load->model('groupscore');
        
        header('Content-Type: application/json');
        
        if ($group_id) {
            $array = [
                'user_id' => $this->session->userdata('user_id'),
                'group_id' => $group_id,
                'error_message' => 'Access denied: This group is private'
            ];
            
            if (!$this->cellblock7->groupPublicCheck($array)) {
                echo json_encode(['error' => $array['error_message']]);
                return;
            }
        }
        
        $response = [];
        
        if ($group_id) {
            $response['ips'] = $this->cellblock7->icmpTableData($group_id);
            $response['groupscore'] = $this->groupscore->getTodayGroupScore($group_id);
            $response['group_monthly_scores'] = $this->group_monthly_scores->get($group_id);
            $grouprow = $this->group->readGroupByID(['group_id' => $group_id]);
            $response['group_name'] = $grouprow->row('name');
            $response['group_id'] = $group_id;
        } else {
            $response['ips'] = $this->cellblock7->icmpTableData();
        }
        
        $response['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('node');
        $response['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();
        
        $this->db->where('metric', 'jobs_per_minute');
        $jobs_query = $this->db->get('health_dashboard');
        $response['jobs_per_minute'] = $jobs_query->row()->result;
        
        $this->db->where('metric', 'failed_jobs_past_day');
        $failed_query = $this->db->get('health_dashboard');
        $response['failed_jobs_past_day'] = $failed_query->row()->result;
        
        $this->db->where('metric', 'engine_status');
        $engine_query = $this->db->get('health_dashboard');
        $response['engine_status'] = $engine_query->row()->result;
        
        echo json_encode($response);
    }

    public function searchNodes()
    {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        try {
            $searchTerm = $this->input->get('term', TRUE);
            $groupId = $this->input->get('group_id', TRUE);
            
            // Ensure empty group_id is treated as null
            if (empty($groupId)) {
                $groupId = null;
            }

            $this->load->model('cellblock7');
            $this->load->model('securitychecks');
            $this->load->model('average30days_model');

            $ips = $this->cellblock7->icmpTableData($groupId, $searchTerm);

            $this->db->where('metric', 'jobs_per_minute');
            $jobs_query = $this->db->get('health_dashboard');
            $jobs_per_minute = $jobs_query->row()->result;
            
            $this->db->where('metric', 'failed_jobs_past_day');
            $failed_query = $this->db->get('health_dashboard');
            $failed_jobs_past_day = $failed_query->row()->result;
            
            $this->db->where('metric', 'engine_status');
            $engine_query = $this->db->get('health_dashboard');
            $engine_status = $engine_query->row()->result;

            $response = [
                'ips' => $ips,
                'owner_matches_table' => $this->securitychecks->ownerMatchesLoggedIn('node'),
                'diffPercentAndMs' => $this->average30days_model->getPercentAndMsForDiff(),
                'jobs_per_minute' => $jobs_per_minute,
                'failed_jobs_past_day' => $failed_jobs_past_day,
                'engine_status' => $engine_status,
            ];

            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (Exception $e) {
            log_message('error', 'Search Nodes Error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Search failed. Please try again.']);
        }
    }

    public function telnet($data = null)
    {
        if ($data == null) {
            $data['start'] = 0;
        }
        $this->load->model('techbits_model');
        $this->load->library('form_validation');

        $data_meta = ['title'            => 'Blacklist and automatic Telnet email test with full debug output',
                           'description' => 'Just type in an email address and we will automatically look up all associated mx records and send a test email to each, providing the full output from the communication from us to the server allowing you to spot any potential issues. You can also run a quick blacklist lookup on any extenral IP.',
                           'keywords'    => 'telnet,DNS,PTR,email,troubleshoot,port 25',
        ];
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);

        $domain_of_email = substr(strrchr($this->input->post('to'), '@'), 1);
        $mx_lookup = $this->techbits_model->lookup($domain_of_email, 'MX');

        $data['server'] = $mx_lookup;
        $user_ip = $this->techbits_model->userIP();
        $data['user_ip'] = $user_ip;
        $this->load->view('telnetBody_view', $data);

        $this->load->view('footer_view');
    }

    public function telnet_formTelnet()
    {
        $this->load->model('techbits_model');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('to', 'To', 'trim|xss_clean|valid_email|required');
        $this->form_validation->set_rules('verify', 'Verify', 'required|matches[image]');

        if ($this->form_validation->run() == false) {
            $data = ['captcha_requested' => 'yes',
                          'message'      => '',
                          'cap_img'      => $this->techbits_model->captcha111(),
            ];
            $data['start'] = 0;
            $this->telnet($data);
        } else {
            $data['start'] = 1;
            $this->telnet($data);
        }
    }

    public function telnet_formDNSBL()
    {
        $data['start'] = 0;
        $this->load->model('techbits_model');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ip', 'IP', 'trim|required|valid_ip');
        if ($this->form_validation->run() == true) {
            $data['dnsme'] = 1;
            $this->telnet($data);
        } else {
            $this->telnet($data);
        }
    }

    public function dns($data = null)
    {
        $this->load->model('techbits_model');
        $this->load->library('form_validation');

        $data_meta = ['title'            => 'DNS All in One (MX, PTR, A, NS, TXT and WHOIS)',
                           'description' => 'Run one DNS query and get results for all MX, PTR, A, NS, TXT and WHOIS records. Simple. Easy.',
                           'keywords'    => 'all,in,one,dns,whois',
        ];
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);

        $host = $this->input->post('host');
        if ($host == '') {
            $data['dom_bestguess'] = $this->techbits_model->getDomain();
        } else {
            $data['dom_bestguess'] = $host;
        }
        $data['user_ip'] = $this->techbits_model->userIP();
        $this->load->view('dns_view', $data);

        $this->load->view('footer_view');
    }

    public function dns_form()
    {
        $this->load->model('techbits_model');
        $this->load->model('whoisclass');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('host', 'Hostname', 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $this->dns();
        } else {
            $this->form_validation->set_rules('host', 'Hostname', 'valid_ip');
            if ($this->form_validation->run() == true) {
                $data['PTR'] = gethostbyaddr($this->input->post('host'));
            }
            $data['dns'] = 1;
            $data['A'] = $this->techbits_model->lookup($this->input->post('host'), 'A');
            $data['MX'] = $this->techbits_model->lookup($this->input->post('host'), 'MX');
            $data['TXT'] = $this->techbits_model->lookup($this->input->post('host'), 'TXT');
            $data['NS'] = $this->techbits_model->lookup($this->input->post('host'), 'NS');
            $data['whois'] = $this->whoisclass->whoislookup($this->input->post('host'));
            if ($data['MX']['1']['ec'] < 1) {
                foreach ($data['MX'] as $arr => $key) {
                    $data['MX_A'][$arr] = $this->techbits_model->lookup($data['MX'][$arr]['Target'], 'A');

                    if ($data['MX_A'][$arr]['1']['ec'] < 1) {
                        $data['MX_PTR'][$arr] = gethostbyaddr($data['MX_A'][$arr]['1']['IP']);
                    }
                }

            }
            $this->dns($data);
        }
    }

    public function pingAdd($data = null)
    {

        $this->load->model('techbits_model');
        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
        $this->load->model('securitychecks');
        $this->load->model('average30days_model');
        $this->load->model('group');
        $this->group->deleteEmptyGroups();

        $this->db->where('metric', 'ping_table_last_truncation');
        $truncation_query = $this->db->get('health_dashboard');
        $last_truncation_timestamp = ($truncation_query->num_rows() > 0) ? $truncation_query->row()->result : null;

        $data_meta = [
            'title'       => 'pinescore.com | internet monitoring',
            'description' => 'Rate your connection with our unique algorithm. pinescore [90-100 = Solid], [50-89 = Good], [0-49 Suboptimal], [< 0 = ...]',
            'keywords'    => 'ip,ping,monitoring,report,online,offline,alert',
            'last_truncation_timestamp' => $last_truncation_timestamp,
        ];

        $data['ips'] = $this->cellblock7->icmpTableData();
        $data['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $data['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('node');
        $data['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();

        $user_ip = $this->techbits_model->userIP();

        $data['user_ip'] = $user_ip;

        $data['refresh'] = '';

        $data_meta['refresh_content'] = 10;
        $data_meta['owner_matches_table'] = $data['owner_matches_table'];
        $data_meta['group_id'] = null;

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('pingAdd_view', $data);
        $this->load->view('sub/events_bar_view', ['group_id' => null]);
        $this->load->view('icmpTable_view', $data);
        $this->load->view('footer_view');

    }

    public function pingAdd_formProcess()
    {

        $this->load->model('icmpmodel');
        $this->load->model('actionicmp');
        $this->load->model('techbits_model');
        $this->load->model('sqlqu');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ip', 'IP or Hostname', 'trim|required|xss_clean');
        $this->form_validation->set_rules('note', 'Note', 'required|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'xss_clean|valid_emails');
        if ($this->form_validation->run() == false) {
            $this->pingAdd();
        } else {
            $duplicate_check = $this->icmpmodel->ipExists($this->input->post('ip'), $this->session->userdata('user_id'));
            if ($duplicate_check->num_rows() > 0) {
                $this->session->set_flashdata('message', '<span class="b"><font color="red"><strong>IP or Hostname 
                    is already being monitored. Maybe not in this group? <a href="'.base_url().'tools/pingAdd/">View All
                    </a></strong></font></span>');
                redirect(base_url().$this->session->userdata('breadcrumbs'));
            }
            $count_check = $this->icmpmodel->monitorCount($this->session->userdata('user_id'));
            if ($count_check->num_rows() > 999) {
                $this->session->set_flashdata('message', '300 Monitors reached, please edit/delete you existing monitors or upgrade your account.<br><br>');
                redirect(current_url());
            }

            $ping_ip_table_data = [
                'ip'                 => $this->input->post('ip'),
                'last_ran'           => '2000-08-08 09:30:10',
                'note'               => $this->input->post('note'),
                'public'             => 0,
                'owner'              => $this->session->userdata('user_id'),
                'last_online_toggle' => date('Y-m-d H:i:s'),
                'count'              => 0,
                'last_email_status'  => 'New',
                'count_direction'    => '-',
            ];

            $this->db->insert('ping_ip_table', $ping_ip_table_data);
            $last_insert_id = $this->db->insert_id();

            $insertEmailAlert_data = [
                'ping_ip_id' => $last_insert_id,
                'alert'      => $this->input->post('email'),
            ];
            $this->sqlqu->insertEmailAlert($insertEmailAlert_data);

            if ($this->input->post('viewGroup') !== false) {

                $this->load->model('cellblock7');
                $this->cellblock7->addNodeToGroup($this->input->post('viewGroup'), $last_insert_id);
            }
            $this->session->set_flashdata('message', '<font color="red">IP added and checked, please review it in the table below.</font>');
            redirect(base_url().$this->session->userdata('breadcrumbs'));
        }
    }

    public function popOut($refresh = null, $filter_group = null) 
    {
        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
        $this->load->model('securitychecks');
        $this->load->model('average30days_model');
        $this->load->model('group');
        $this->load->model('group_monthly_scores');
        $this->load->model('groupscore');

        $this->db->where('metric', 'jobs_per_minute');
        $jobs_query = $this->db->get('health_dashboard');
        $jobs_per_minute = $jobs_query->row()->result;
    
        $this->db->where('metric', 'failed_jobs_past_day');
        $failed_query = $this->db->get('health_dashboard');
        $failed_jobs = $failed_query->row()->result;
    
        $this->db->where('metric', 'engine_status');
        $engine_query = $this->db->get('health_dashboard');
        $engine_status = $engine_query->row()->result;
        
        $this->db->where('metric', 'ping_table_last_truncation');
        $truncation_query = $this->db->get('health_dashboard');
        $last_truncation_timestamp = ($truncation_query->num_rows() > 0) ? $truncation_query->row()->result : null;
        
        $data_meta = [
            'title' => 'ICMP Monitor (Table pop out)',
            'description' => 'auto refresh webpage that displays your live ICMP monitors',
            'keywords' => 'ip,monitoring,report,online',
            'last_truncation_timestamp' => $last_truncation_timestamp,
        ];
    
        $array = [
            'user_id' => $this->session->userdata('user_id'),
            'group_id' => $filter_group,
            'error_message' => 'This group is private, please <a href="'.base_url().'">Login</a> to view, or request the group owner makes it public. ptfoh',
        ];
        
        if (!$this->cellblock7->groupPublicCheck($array)) {
            $this->load->view('error_view', $array);
            return false;
        }
        unset($array);
    
        if ($refresh == 'stop') {
            $data_meta['refresh_content'] = '';
            $button = ['action' => 'stop'];
        } else {
            $data_meta['refresh_content'] = 10;
            $button = ['action' => 'refresh'];
        }

        $button['jobs_per_minute'] = $jobs_per_minute;
        $button['failed_jobs_past_day'] = $failed_jobs;
        $button['engine_status'] = $engine_status;

        $data['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('node');
        $data['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();

        $data_meta['owner_matches_table'] = $data['owner_matches_table'];
        $data_meta['group_id'] = $filter_group;

        $events_bar_data = ['group_id' => $filter_group ? (int) $filter_group : null];
        if ($filter_group && isset($button['group_name'])) {
            $events_bar_data['group_name'] = $button['group_name'];
        }
    
        $this->load->view('header_view', $data_meta);
    
        if (!$filter_group) {
            $data['ips'] = $this->cellblock7->icmpTableData();
        } else {
            $data['ips'] = $this->cellblock7->icmpTableData($filter_group);
            $button['groupscore'] = $this->groupscore->getTodayGroupScore($filter_group);
            $button['group_monthly_scores'] = $this->group_monthly_scores->get($filter_group);
            $button['group_id'] = $filter_group;
            $grouprow = $this->group->readGroupByID(['group_id' => $filter_group]);
            $button['group_name'] = $grouprow->row('name');
        }
    
        $this->load->view('sub/countDown_view', $button);
        $this->load->view('group_scores_view', $button);
        $this->load->view('sub/events_bar_view', $events_bar_data);
        $this->load->view('icmpTable_view', $data);
        $this->load->view('footer_view');
    }

    public function icmpEdit()
    {
        $this->load->model('icmpmodel');
        $this->load->model('actionicmp');
        $this->load->model('sqlqu');
        $this->load->model('securitychecks');
        $this->load->library('form_validation');
        $this->load->model('group');
        $this->load->model('group_association');

        if ($_POST['action'] == 'Edit') {
            $data['edit'] = 1;
            if ($this->input->post('group_id') == 0) {
                $this->pingAdd($data);
            } else {
                $this->load->library('../controllers/nc');
                $this->nc->viewGroup($this->input->post('group_id'), $data);
            }
        }

        if ($_POST['action'] == 'Update') {
            $this->form_validation->set_rules('alert', 'Alert', 'xss_clean|valid_emails');
            $this->form_validation->set_rules('note', 'Note', 'xss_clean|required');
            $this->form_validation->set_rules('ip', 'ip', 'xss_clean|required');
            $this->form_validation->set_rules('public', 'public', 'xss_clean|boolean');

            if ($this->form_validation->run() == false) {
                die('datas validation failed, please restart your modem and try again.');
            }

            $duplicate_check = $this->icmpmodel->ipExists($this->input->post('ip'), $this->session->userdata('user_id'));
            if ($this->input->post('ip') !== $this->input->post('ip_existing')) {
                if ($duplicate_check->num_rows() > 0) {
                    $this->session->set_flashdata('message', '<span class="b"><font color="red"><strong>( '.$this->input->post('ip').' 
                    )  is already being monitored. Maybe not in this group? <a href="'.base_url().'tools/pingAdd/">View All
                    </a></strong></font></span>');
                    $redirect_scroll_top = substr($this->session->userdata('breadcrumbs'), 0,
                        strpos($this->session->userdata('breadcrumbs'), '#'));
                    $redirect_scroll_top = $redirect_scroll_top.'#first';
                    redirect(base_url().$redirect_scroll_top);
                }
            }
            $update_ping_ip_table = [
                'note'   => $this->input->post('note'),
                'ip'     => $this->input->post('ip'),
                'public' => $this->input->post('ea'),
            ];
            $this->db->where('owner', $this->session->userdata('user_id'));
            $this->db->where('id', $this->input->post('id'));
            $this->db->update('ping_ip_table', $update_ping_ip_table);

            $insertEmailAlert = [
                'ping_ip_id' => $this->input->post('id'),
                'alert'      => $this->input->post('alert'),
            ];
            $this->sqlqu->insertEmailAlert($insertEmailAlert);

            redirect(base_url().$this->session->userdata('breadcrumbs'));
        }

        if ($_POST['action'] == 'Reset') {
            redirect(base_url().$this->session->userdata('breadcrumbs'));
        }

        if ($_POST['action'] == 'Delete') {
            $data['delete'] = 1;
            if (strpos($this->session->userdata('breadcrumbs'), 'viewGroup') !== false) {
                $this->load->library('../controllers/nc');
                $this->nc->viewGroup($this->input->post('group_id'), $data);
            } else {
                $this->pingAdd($data);
            }
        }

        if ($_POST['action'] == 'Confirm Delete') {
            $this->securitychecks->ownerCheckRedirect($this->session->userdata('user_id'));

            $this->db->where('id', $this->input->post('id'));
            $this->db->where('owner', $this->session->userdata('user_id'));
            $this->db->delete('ping_ip_table');

            $removeFromGroups = ([
                'user_id'    => $this->session->userdata('user_id'),
                'ping_ip_id' => $this->input->post('id'),
            ]);
            $this->group_association->delete_all_associations_based_on_ping_ip_id($removeFromGroups);

            $this->db->where('ping_ip_id', $this->input->post('id'));
            $this->db->delete('alerts');
            redirect(base_url().$this->session->userdata('breadcrumbs'));
        }
    }

    public function report($ip_id, $change = 1)
    {
        $this->load->library('table');
        $this->load->model('icmpmodel');
        $report_request = ['ip_id'      => $ip_id,
                                'owner' => $this->icmpmodel->getUserID(), ];
        $result = $this->icmpmodel->report($report_request, $change);

        $data_meta = ['title'            => 'Activity Report',
                           'description' => "Dropped requests over the last <strong><u>week</u></strong>. You'll only receive an email when a node has been down for a minute, not for each dropped request.",
                           'keywords'    => 'icmp,report,activity',

        ];
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/icmp_view', $result);
        $this->load->view('footer_view');
    }

}