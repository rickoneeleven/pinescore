<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Nc extends CI_Controller {

    public function externalAccess() {
        $data_meta = array(
            'title' => "Public",
            'description' => "When a node is configured for public access, anyone with the URL to the 
                report can see recent activity. Good for sharing with people when troubleshooting.",
            'keywords' => "share,report,public",
        );
        $this->trackerlib->trackMe();

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        //$this->load->view('speedTest_view');
        $this->load->view('footer_view');
    }

    public function createOrModifyGroup($option=null, $group_id=null) {
        $this->load->library('arrayahoy/arrayahoylib');
        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
	    $this->load->model('techbits_model');
        
        $user = $this->icmpmodel->getUserID();
        $logged_in_user = array(
            'owner'             => $user,
            'order_alpha'       => 1,
            'group_creation'    => 1,
        );
        switch($option) {

            case "modify": {
                $data_meta = array(
                    'title' => "Modify Group",
                    'description' => "Edit which nodes belong to this group." ,
                    'keywords' => "icmp,groups,share",
                );
                $this->db->where('id', $group_id);
                $group_details = $this->db->get('grouped_reports');
                if($group_details->num_rows() < 1) {
                    die('fredrick, is that you?');
                }

                $data_for_view['group_details'] = $group_details;
                $ip_ids_messy_after_explode = explode(',', $group_details->row('ping_ip_ids'));
                $data_for_view['cleaned_ip_ids'] = $this->arrayahoylib->removeWhiteAndEmpty($ip_ids_messy_after_explode);

                $data_for_view['monitors'] = $this->icmpmodel->getIPs($logged_in_user);

                $view = "groupedreports";
                break;
            }
            case "create": {
                $data_meta = array(
                    'title' => "Create new Group",
                    'description' => "Create a new Group. Groups can be used to easily view and share a list of your active monitors with others." ,
                    'keywords' => "icmp,groups,share"
                );
                $data_for_view['monitors'] = $this->icmpmodel->getIPs($logged_in_user);
                $view = "groupedreports";
                break;
            }
            case "help_public": {
                $data_meta = array(
                    'title' => "Public Access Help",
                    'description' => "If you enable public access, anyone who you send the report link to will be able to see the online/offline status of all your nodes, regardless if they are logged into the site.",
                    'breadcrumbs' => '<a href="javascript:history.back();">[Go Back]</a>',
                    'keywords' => "help,groups,share"
                );
                $data_for_view = "";
                $view = "userConfirmation_view";
                break;
            }
        }
        $data['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $this->trackerlib->trackMe(); 
	    $user_ip = $this->techbits_model->userIP();
	    $data['user_ip'] = $user_ip;

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
	    //$this->load->view('pingAdd_view', $data); //otherwises when creting a new group there is also a form input loaded for creating a new monitor above it. the user may not know whar they need to fill out
        $this->load->view($view, $data_for_view);
        $this->load->view('footer_view');
    }

    public function createGroupForm() {
        /**
         * $form_label_id = the method that passes form data here dynamically creates form lables for each ping_id, the value is then either 0 or 1 as it's a checkbox.
         * switch = set the switch as the dynamic form id and if the value of that form id = 1, which means it's been ticked then add it to an array to go into database 
         * form_validation = one rule is nested in the foreach as it has to grab the dynamic values to make sure they've not been changed/hacked during POST. fake validation message to throw hack attempts off
         */
        $ids_for_new_group = '';
        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');
        $this->load->library('form_validation');

        $logged_in_user = array(
            'owner' => $this->icmpmodel->getUserID(),
        );
    
        $this->form_validation->set_rules('groupname', 'Group Name', 'required|max_length[16]|xss_clean');
		$this->form_validation->set_rules('public', 'Public Access', 'xss_clean');
        
        $data_for_view['possible_ids'] = $this->icmpmodel->getIPs($logged_in_user);
        foreach($data_for_view['possible_ids']->result() as $row){
            $form_label_id = $row->id;
            $is_id_checked = $this->input->post($form_label_id);
            $this->form_validation->set_rules($form_label_id, 'Internal Error 707', 'integer|xss_clean');
            
            switch($is_id_checked) {
                case 1: {
                    $ids_for_new_group = $ids_for_new_group.$form_label_id.", ";
                }
            }
        }

		if ($this->form_validation->run() == FALSE)
		{
            $this->createOrModifyGroup('create');
		}
		else
		{
            $this->securitychecks->ownerCheckRedirect($logged_in_user['owner']);
            $new_monitor_group = array(
                'name' => $this->input->post('groupname'),
                'datetime' => date('Y-m-d H:i:s'),
                'ping_ip_ids' => $ids_for_new_group,
                'owner_id' => $logged_in_user['owner'],
                'public'        => $this->input->post('public'),
            );
            $this->db->insert('grouped_reports', $new_monitor_group);
            //vdebug($ids_for_new_group);
            redirect(base_url('nc/viewGroup/'.$this->db->insert_id()));
		}
    }

    public function editGroupForm() {
        /**
         * originally copied from function createGroupForm()
         */
        $ids_for_updated_group = '';
        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');
        $this->load->library('form_validation');

        $logged_in_user = array(
            'owner' => $this->icmpmodel->getUserID(),
        );
    
        $this->form_validation->set_rules('groupname', 'Group Name', 'required|max_length[16]|xss_clean');
		$this->form_validation->set_rules('public_access', 'Public Access', 'xss_clean');
        
        $data_for_view['possible_ids'] = $this->icmpmodel->getIPs($logged_in_user);
        foreach($data_for_view['possible_ids']->result() as $row){
            $form_label_id = $row->id;
            $is_id_checked = $this->input->post($form_label_id);
            $this->form_validation->set_rules($form_label_id, 'Internal Error 707', 'integer|xss_clean');
            
            switch($is_id_checked) {
                case 1: {
                    $ids_for_updated_group = $ids_for_updated_group.$form_label_id.", ";
                }
            }
        }

		if ($this->form_validation->run() == FALSE)
		{
            $this->createOrModifyGroup('modify', $this->input->post('group_id') );
		}
		else
		{
            $this->db->where('id', $this->input->post('group_id'));
            $group_to_be_updated = $this->db->get('grouped_reports');
            $this->securitychecks->ownerCheckRedirect($group_to_be_updated->row('owner_id'));

            $update_monitor_group = array(
                'name' => $this->input->post('groupname'),
                'datetime' => date('Y-m-d H:i:s'),
                'ping_ip_ids' => $ids_for_updated_group,
                'public'        => $this->input->post('public'),
            );
            $this->db->where('id', $this->input->post('group_id'));
            $this->db->update('grouped_reports', $update_monitor_group);
            //vdebug($ids_for_updated_group);
            redirect(base_url('nc/viewGroup/'.$this->input->post('group_id')));
		}
    }

    public function deleteGroup($id, $confirm="no") {
        //echo "confirm: ".$confirm;
        /**
         * originally copied from function createGroupForm()
         */
        $this->load->model('icmpmodel');
        $this->load->model('securitychecks');

        $this->db->where('id', $id);
        $group_to_be_deleted = $this->db->get('grouped_reports'); 

        $this->securitychecks->ownerCheckRedirect($group_to_be_deleted->row('owner_id'));

        if($confirm == "no") {
            $data_meta = array(
                'title' => "Think Twice",
                'description' => "You're about to walk a path that allows no return.",
                'keywords' => "confirm,delete",
               // 'refresh_content' => "2",
            );
            $confirmation_view['breadcrumbs'] = 'Are you sure you want to delete the group known as <strong>'.$group_to_be_deleted->row('name').'?</strong>
                <br><br><table><tr>
                <td><a href="'.base_url('nc/deleteGroup/'.$id.'/walkingthepath').'">&nbsp;&nbsp; Please Proceed &nbsp;&nbsp;</a></td>
                </tr></table>';

            $this->load->view('header_view', $data_meta);
            $this->load->view('navTop_view', $data_meta);
            $this->load->view('userConfirmation_view', $confirmation_view);
            $this->load->view('footer_view');
        } else {
            $this->db->where('id', $id);
            $this->db->delete('grouped_reports');
            redirect(base_url());
        }
    }

    /**
     * $data['group_id'] = $group_id is because we need the group_id in the view to pass the ID to delete or modify group URL
     * $data['user_ip'] = next to the hostname/ip form input box on pingAdd it gives you your IP in case you want to use that 
     */
    public function viewGroup($group_id, $data = null) {
	    $this->load->model('techbits_model');
        $this->load->model(array('icmpmodel','securitychecks','cellblock7'));
        $this->load->model('average30days_model');
        $array = array(
            'owner_id'              => $this->session->userdata('user_id'),
            'group_id'              => $group_id,
            'error_message' => "This group is private, please <a href=\"".base_url()."\">Login</a> to view, or
                request the group owner makes it public.",
        );
        if(!$this->cellblock7->groupPublicCheck($array)) {
            $this->load->view('error_view', $array);
            return FALSE;
        }
        unset($array);

        $data_meta = array(
            'title' => "Groups",
            'description' => "Create groups and select which nodes to include.",
            'keywords' => "custom,reports",
           // 'refresh_content' => "2",
        );
        
	    $this->trackerlib->trackMe();

        $this->db->where('id', $group_id);
        $data['group_details'] = $this->db->get('grouped_reports');
        $data['ips'] = $this->cellblock7->icmpTableData($group_id);
        $data['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $data['group_id'] = $group_id;
        $data['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('group');
        $data['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();

	    $user_ip = $this->techbits_model->userIP();
	    $data['user_ip'] = $user_ip;
	    
	    // $data['refresh'] = ""; deleteme 29.06.15
	    $this->load->view('header_view', $data_meta);
	    $this->load->view('navTop_view', $data_meta);
	    $this->load->view('pingAdd_view', $data);
        if($this->securitychecks->loggedIn() == TRUE) { $this->load->view('viewgroupmenu', $data); }
	    $this->load->view('groupheader', $data);
	    $this->load->view('icmpTable_view', $data);
	    $this->load->view('footer_view');
    }

    public function storyTimeNode($id) {
        $this->load->model('sqlqu');
        $pingiptableSQLrequest = array(
            'request_type'      => 'ip_by_id',
            'id'                => $id,            
            );
        $ping_ip_TableTable = $this->sqlqu->getPingIpTable($pingiptableSQLrequest);
        $historicSQLrequest = array(
            'request_type'      => 'single_ip',
            'ip'                => $ping_ip_TableTable->row('ip'),
        );
        $view['historic_novaScoreTable'] = $this->sqlqu->getHistoricNovascore($historicSQLrequest);
        $data_meta = array('title' => "3 Year Log [ ".$ping_ip_TableTable->row('ip')." ]",
            'description' => "We save some limited data for a period of 3 years." ,
            'keywords' => "long, term, behaviour, novascore"
        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/novaScore_history_view', $view);
        $this->load->view('footer_view');
    }

    public function whatIsNovascore() {
        $data_meta = array(
            'title' => "NovaScore defined",
            'description' => "NovaScore is our unique method for rating the stability of a node. A score of 90-100 is top rated, 50-89 is good, 0-49 is suboptimal with potential issue, anything less than 0 usual indicates a problem with the node, or very high load over a sustained period of time.",
            'keywords' => "what,is,novascore",
        );
        $this->trackerlib->trackMe();

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('footer_view');
    }

        public function whatIsLongTermAverage() {
        $data_meta = array(
            'title' => "Long Term Average | hello",
            'description' => "We take a months worth of response times and average them out. We can then use this number to compare your current response times against the longer term average to help identify any performance improvements or drops.",
            'keywords' => "novascore,longtermaverage",
        );
        $this->trackerlib->trackMe();

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('footer_view');
    }

    public function whyHidden() {
        $data_meta = array(
            'title' => "What does this mean?",
            'description' => "In your option, you've chosen to hide nodes that are offline for over 72 hours. So, if you choose to add this node to the group, be aware it won't actually be displayed within the group, as this node is hidden as per your option.",
            'keywords' => "what,does,this,mean",
        );
        $this->trackerlib->trackMe();
        $confirmation_view['breadcrumbs'] = '<a href="'.base_url().'user_options/options">Change your options here</a>';

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('userConfirmation_view', $confirmation_view);
        $this->load->view('footer_view');
    }
}
?>
