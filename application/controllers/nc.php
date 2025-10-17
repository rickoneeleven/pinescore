<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Nc extends CI_Controller
{
    public function externalAccess()
    {
        $data_meta = [
            'title'       => 'Public',
            'description' => 'When a node is configured for public access, anyone with the URL to the 
                report can see recent activity. Good for sharing with people when troubleshooting.',
            'keywords' => 'share,report,public',
        ];

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);

        $this->load->view('footer_view');
    }

    public function createOrModifyGroup($option = null, $group_id = null)
    {
        $this->load->library('arrayahoy/arrayahoylib');
        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
        $this->load->model('techbits_model');
        $this->load->model('group');
        $this->load->model('group_association');

        $user = $this->icmpmodel->getUserID();
        $logged_in_user = [
            'owner'          => $user,
            'order_alpha'    => 1,
            'group_creation' => 1,
        ];
        $user_and_group = [
            'user_id'  => $this->session->userdata('user_id'),
            'group_id' => $group_id,
        ];
        switch ($option) {
            case 'modify':
                $data_meta = [
                    'title'       => 'Modify Group',
                    'description' => 'Edit which nodes belong to this group.',
                    'keywords'    => 'icmp,groups,share',
                ];
                $group_associationsTable = $this->group_association->read($user_and_group);
                if ($group_associationsTable->num_rows() < 1) {
                    die('fredrick, is that you?');
                }
                $groupsTable = $this->group->readSpecificGroup($user_and_group);

                $data_for_view['group_associationTable'] = $group_associationsTable;
                $data_for_view['groupsTable'] = $groupsTable;
                $data_for_view['cleaned_ip_ids'] = $this->group_association->
                return_array_pingIpIds_from_group_id($user_and_group);

                $data_for_view['monitors'] = $this->icmpmodel->getIPs($logged_in_user);

                $view = 'groupedreports';
                break;

            case 'create':
                $data_meta = [
                    'title'       => 'Create new Group',
                    'description' => 'Create a new Group. Groups can be used to easily view and share a list of your active monitors with others.',
                    'keywords'    => 'icmp,groups,share',
                ];
                $data_for_view['monitors'] = $this->icmpmodel->getIPs($logged_in_user);
                $view = 'groupedreports';
                break;

            case 'help_public':
                $data_meta = [
                    'title'       => 'Public Access Help',
                    'description' => 'If you enable public access, anyone who you send the report link to will be able to see the online/offline status of all your nodes, regardless if they are logged into the site.',
                    'breadcrumbs' => '<a href="javascript:history.back();">[Go Back]</a>',
                    'keywords'    => 'help,groups,share',
                ];
                $data_for_view = '';
                $view = 'userConfirmation_view';
                break;
        }
        $data['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $user_ip = $this->techbits_model->userIP();
        $data['user_ip'] = $user_ip;

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);

        $this->load->view($view, $data_for_view);
        $this->load->view('footer_view');
    }

    public function createGroupForm()
    {

        $ids_for_new_group = '';
        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');
        $this->load->model('group');
        $this->load->model('group_association');
        $this->load->library('form_validation');

        $logged_in_user = [
            'owner' => $this->icmpmodel->getUserID(),
        ];

        $this->form_validation->set_rules('groupname', 'Group Name', 'required|max_length[16]|xss_clean');
        $this->form_validation->set_rules('public', 'Public Access', 'xss_clean');

        $data_for_view['possible_ids'] = $this->icmpmodel->getIPs($logged_in_user);
        $ids_for_new_group = [];
        foreach ($data_for_view['possible_ids']->result() as $row) {
            $form_label_id = $row->id;
            $is_id_checked = $this->input->post($form_label_id);
            $this->form_validation->set_rules($form_label_id, 'Internal Error 707', 'integer|xss_clean');

            switch ($is_id_checked) {
                case 1:
                    $ids_for_new_group[] = $form_label_id;
            }
        }
        
        if(empty($ids_for_new_group)) {
            die('u no create empty group');
        }

        if ($this->form_validation->run() == false) {
            $this->createOrModifyGroup('create');
        } else {
            $this->securitychecks->ownerCheckRedirect($logged_in_user['owner']);

            $new_group = [
                'name'    => $this->input->post('groupname'),
                'user_id' => $logged_in_user['owner'],
                'public'  => $this->input->post('public'),
            ];
            $insert_id = $this->group->create($new_group);

            foreach ($ids_for_new_group as $ping_ip_id) {
                $new_associations = [
                    'group_id'   => $insert_id,
                    'user_id'    => $logged_in_user['owner'],
                    'ping_ip_id' => $ping_ip_id,
                ];
                $this->group_association->create($new_associations);
            }

            redirect(base_url('nc/viewGroup/'.$insert_id));
        }
    }

    public function editGroupForm()
    {

        $ids_for_updated_group = '';
        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');
        $this->load->library('form_validation');
        $this->load->model('group');
        $this->load->model('group_association');
        $this->load->model('sqlqu');

        $logged_in_user = [
            'owner' => $this->icmpmodel->getUserID(),
        ];

        $this->form_validation->set_rules('groupname', 'Group Name', 'required|max_length[16]|xss_clean');
        $this->form_validation->set_rules('public_access', 'Public Access', 'xss_clean|boolean');
        $this->form_validation->set_rules('clear_email_addresses', 'Clear Email Addresses', 'xss_clean|boolean');
        $this->form_validation->set_rules('email_addresses', 'Email Addresses', 'xss_clean|valid_emails');

        $data_for_view['possible_ids'] = $this->icmpmodel->getIPs($logged_in_user);
        foreach ($data_for_view['possible_ids']->result() as $row) {
            $form_label_id = $row->id;
            $is_id_checked = $this->input->post($form_label_id);
            $this->form_validation->set_rules($form_label_id, 'Internal Error 707', 'integer|xss_clean');

            switch ($is_id_checked) {
                case 1:
                    $ids_for_updated_group = $ids_for_updated_group.$form_label_id.', ';
            }
        }

        if ($this->form_validation->run() == false) {
            $this->createOrModifyGroup('modify', $this->input->post('group_id'));
        } else {
            $group_to_be_updated = $this->group->readSpecificGroup([
                'group_id' => $this->input->post('group_id'),
                'user_id'  => $this->session->userdata('user_id'),
                ]);
            if ($group_to_be_updated->num_rows() < 1) {
                die('who whom it may concern');
            }

            $update_monitor_group = [
                'name' => $this->input->post('groupname'),

                'public' => $this->input->post('public'),
            ];
            $this->db->where('id', $this->input->post('group_id'));
            $this->db->update('groups', $update_monitor_group);

            $ping_ip_ids_to_add_to_group_as_array = array_filter(array_map('trim', (explode(',', $ids_for_updated_group))));
            $this->group_association->delete_all_associations_based_on_group_id([
                    'user_id'  => $this->session->userdata('user_id'),
                    'group_id' => $this->input->post('group_id'),
                ]);
            foreach ($ping_ip_ids_to_add_to_group_as_array as $ping_ip_id) {
                $this->group_association->create([
                    'ping_ip_id' => $ping_ip_id,
                    'user_id'    => $this->session->userdata('user_id'),
                    'group_id'   => $this->input->post('group_id'),
                ]);
                
                if($this->input->post('email_addresses') != "") {
                    $insertEmailAlert = [
                        'ping_ip_id' => $ping_ip_id,
                        'alert'      => $this->input->post('email_addresses'),
                    ];
                    $this->sqlqu->insertEmailAlert($insertEmailAlert);
                }
                
                if($this->input->post('clear_email_addresses') == 1) {
                    $deleteEmailAlert = [
                        'ping_ip_id' => $ping_ip_id,
                        'alert'      => $this->input->post('email_addresses'),
                    ];
                    $this->sqlqu->insertEmailAlert($deleteEmailAlert);
                }
            }
            redirect(base_url('nc/viewGroup/'.$this->input->post('group_id')));
        }
    }

    public function deleteGroup($id, $confirm = 'no')
    {

        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');
        $this->load->model('group');
        $this->load->model('group_association');
        $this->load->model('groupscore');

        $group_to_be_deleted = $this->group->readGroupByID(['group_id' => $id]);
        $group_data = [
            'group_id' => $id,
            'user_id'  => $group_to_be_deleted->row('user_id'),
        ];

        $this->securitychecks->ownerCheckRedirect($group_to_be_deleted->row('user_id'));

        if ($confirm == 'no') {
            $data_meta = [
                'title'       => 'Think Twice',
                'description' => "You're about to walk a path that allows no return.",
                'keywords'    => 'confirm,delete',

            ];
            $confirmation_view['breadcrumbs'] = 'Are you sure you want to delete the group known as <strong>'.$group_to_be_deleted->row('name').'?</strong>
                <br><br><table><tr>
                <td><a href="'.base_url('nc/deleteGroup/'.$id.'/walkingthepath').'">&nbsp;&nbsp; Please Proceed &nbsp;&nbsp;</a></td>
                </tr></table>';

            $this->load->view('header_view', $data_meta);
            $this->load->view('navTop_view', $data_meta);
            $this->load->view('userConfirmation_view', $confirmation_view);
            $this->load->view('footer_view');
        } else {
            $this->group->delete($group_data);
            $this->group_association->delete_all_associations_based_on_group_id($group_data);
            $this->groupscore->deleteGroupScores($id);
            redirect(base_url());
        }
    }

    public function viewGroup($group_id, $data = null)
    {
        $this->load->model('techbits_model');
        $this->load->model(['icmpmodel', 'securitychecks', 'cellblock7']);
        $this->load->model('average30days_model');
        $this->load->model('groupscore');
        $this->load->model('group_monthly_scores');
        $array = [
            'user_id'       => $this->session->userdata('user_id'),
            'group_id'      => $group_id,
            'error_message' => 'This group is private, please <a href="'.base_url().'">Login</a> to view, or
                request the group owner makes it public.',
        ];
        if (!$this->cellblock7->groupPublicCheck($array)) {
            $this->load->view('error_view', $array);

            return false;
        }
        unset($array);

        $data_meta = [
            'title'       => 'Groups',
            'description' => 'Create groups and select which nodes to include.',
            'keywords'    => 'custom,reports',
            'refresh_content' => 10,
        ];

        $this->db->where('id', $group_id);
        $data['groupsTable'] = $this->db->get('groups');
        $data['ips'] = $this->cellblock7->icmpTableData($group_id);
        $data['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $data['group_id'] = $group_id;
        $data['groupscore'] = $this->groupscore->getTodayGroupScore($group_id);
        $data['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('group');
        $data['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();
        $data['group_name'] = $data['groupsTable']->row('name');
        $data['group_monthly_scores'] = $this->group_monthly_scores->get($group_id);

        $user_ip = $this->techbits_model->userIP();
        $data['user_ip'] = $user_ip;

        $data_meta['owner_matches_table'] = $data['owner_matches_table'];
        $data_meta['group_id'] = $group_id;

        $events_bar_data = [
            'group_id' => $group_id,
            'group_name' => $data['group_name'],
        ];

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('pingAdd_view', $data);
        if ($this->securitychecks->loggedIn() == true) {
            $this->load->view('viewgroupmenu', $data);
        }
        $this->load->view('groupheader', $data);
        $this->load->view('group_scores_view', $data);
        $this->load->view('sub/events_bar_view', $events_bar_data);
        $this->load->view('icmpTable_view', $data);
        $this->load->view('footer_view');
        if (strpos(uri_string(), 'icmpEdit') === false) {
            $this->session->set_userdata('breadcrumbs', uri_string());
        }
    }

    public function storyTimeNode($id)
    {
        $this->load->model('sqlqu');

        $pingiptableSQLrequest = [
            'request_type' => 'ip_by_id',
            'id'           => $id,
        ];
        $ping_ip_TableTable = $this->sqlqu->getPingIpTable($pingiptableSQLrequest);
        if ($ping_ip_TableTable->num_rows() === 0) {
            die('one onion per bargy');
        }

        $ip = $ping_ip_TableTable->row('ip');

        // Inputs with safe defaults
        $limit = (int) $this->input->get('limit');
        if ($limit < 1) { $limit = 500; }
        if ($limit > 1000) { $limit = 1000; }

        $order = strtolower((string) $this->input->get('order')) === 'asc' ? 'asc' : 'desc';
        $before_id = (int) $this->input->get('before_id');
        $after_id  = (int) $this->input->get('after_id');

        $from = $this->input->get('from');
        $to   = $this->input->get('to');
        $month = $this->input->get('month');

        $user_range = false;
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $from = date('Y-m-01', strtotime($month . '-01'));
            $to   = date('Y-m-t', strtotime($month . '-01'));
            $user_range = true;
        } elseif ($from && $to) {
            $user_range = true;
        }

        // Explicit flags to clear any date filters
        $oldest_flag = $this->input->get('oldest');
        $newest_flag = $this->input->get('newest');

        // Apply query date window only if explicitly requested by user (month or from/to)
        $query_from = $user_range ? $from : null;
        $query_to   = $user_range ? $to   : null;

        // Force-clear date filters when jumping to Oldest/Newest
        if ($oldest_flag) {
            $order = 'asc';
            $query_from = null;
            $query_to = null;
            $after_id = 0; // start from absolute oldest
        }
        if ($newest_flag) {
            $order = 'desc';
            $query_from = null;
            $query_to = null;
            $before_id = 0; // start from absolute newest
        }

        // When paging with before_id/after_id, ignore any date filters to allow seamless cross-month paging
        if (($order === 'desc' && $before_id > 0) || ($order === 'asc' && $after_id > 0)) {
            $query_from = null;
            $query_to = null;
            $user_range = false;
        }

        $historicSQLrequest = [
            'request_type' => 'single_ip',
            'ip'           => $ip,
            'limit'        => $limit,
            'order'        => strtoupper($order),
            'from'         => $query_from,
            'to'           => $query_to,
        ];
        if ($order === 'desc' && $before_id > 0) { $historicSQLrequest['before_id'] = $before_id; }
        if ($order === 'asc'  && $after_id  > 0) { $historicSQLrequest['after_id']  = $after_id; }

        $view['historic_pinescoreTable'] = $this->sqlqu->getHistoricpinescore($historicSQLrequest);

        $data_meta = [
            'title'       => '3 Year Log [ ' . $ip . ' ]',
            'description' => 'We save some limited data for a period of 3 years.',
            'keywords'    => 'long, term, behaviour, pinescore',
        ];

        // Provide simple navigation context
        $view['nav'] = [
            'limit' => $limit,
            'order' => $order,
            'from'  => $from,
            'to'    => $to,
            'month' => $month,
            'user_range' => $user_range,
            'base'  => site_url('nc/storyTimeNode/' . $id),
        ];

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/pinescore_history_view', $view);
        $this->load->view('footer_view');
    }

    public function whatIspinescore()
    {
        $data_meta = [
            'title'       => 'pinescore defined',
            'description' => 'pinescore is our unique method for rating the stability of a node. A score of 90-100 is top rated, 50-89 is good, 0-49 is suboptimal with potential issue, anything less than 0 usual indicates a problem with the node, or very high load over a sustained period of time.',
            'keywords'    => 'what,is,pinescore',
        ];

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('footer_view');
    }

    public function whatIsLongTermAverage()
    {
        $data_meta = [
            'title'       => 'Long Term Average | hello',
            'description' => 'We take a months worth of response times and average them out. We can then use this number to compare your current response times against the longer term average to help identify any performance improvements or drops.',
            'keywords'    => 'pinescore,longtermaverage',
        ];

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('footer_view');
    }

    public function whyHidden()
    {
        $data_meta = [
            'title'       => 'What does this mean?',
            'description' => "In your option, you've chosen to hide nodes that are offline for over 72 hours. So, if you choose to add this node to the group, be aware it won't actually be displayed within the group, as this node is hidden as per your option.",
            'keywords'    => 'what,does,this,mean',
        ];
        $confirmation_view['breadcrumbs'] = '<a href="'.base_url().'user_options/options">Change your options here</a>';

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('userConfirmation_view', $confirmation_view);
        $this->load->view('footer_view');
    }
}
