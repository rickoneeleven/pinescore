<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tools extends CI_Controller
{

    function __construct()
    {
        parent::__construct(); //otherwise codeigniter breaks when using construct
    }

    public function index()
    {
        $this->pingAdd();
    }

    public function test()
    {
        $data = array(
            'name'    => 'newsletter',
            'id'      => 'newsletter',
            'value'   => 'accept',
            'checked' => TRUE,
            'style'   => 'margin:10px',
        );
        $data2 = array(
            'name'    => 'newsletter',
            'id'      => 'newsletter',
            'value'   => 'accept',
            'checked' => FALSE,
            'style'   => 'margin:10px',
        );

        echo form_radio($data);
        echo form_radio($data2);

    }

    public function telnet($data = null)
    {
        if ($data == null) {
            $data['start'] = 0;
        }
        $this->load->model('techbits_model');
        $this->load->library('form_validation');

        $data_meta = array('title'       => "Blacklist and automatic Telnet email test with full debug output",
                           'description' => "Just type in an email address and we will automatically look up all associated mx records and send a test email to each, providing the full output from the communication from us to the server allowing you to spot any potential issues. You can also run a quick blacklist lookup on any extenral IP.",
                           'keywords'    => "telnet,DNS,PTR,email,troubleshoot,port 25"

        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);

        $domain_of_email = substr(strrchr($this->input->post('to'), "@"), 1);
        $mx_lookup = $this->techbits_model->lookup($domain_of_email, "MX");
        //print_r($mx_lookup);
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
        //print_r($mx_lookup); 
        //$data['domain_telnet'];
        $this->form_validation->set_rules('to', 'To', 'trim|xss_clean|valid_email|required');
        $this->form_validation->set_rules('verify', 'Verify', 'required|matches[image]');
        //echo $this->input->post('to');
        if ($this->form_validation->run() == FALSE) {

            $data = array('captcha_requested' => "yes",
                          'message'           => "",
                          'cap_img'           => $this->techbits_model->captcha111()
            );
            $data['start'] = 0;
            $this->telnet($data);
        } else {
            $data['start'] = 1; //load the telnet page and start the test
            $this->telnet($data);
        }

    }

    public function telnet_formDNSBL()
    {
        $data['start'] = 0; //regardless of validation we don't want to $start the telnet test as we've loaded then blaclist form
        $this->load->model('techbits_model');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ip', 'IP', 'trim|required|valid_ip');
        if ($this->form_validation->run() == TRUE) {
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


        $data_meta = array('title'       => "DNS All in One (MX, PTR, A, NS, TXT and WHOIS)",
                           'description' => "Run one DNS query and get results for all MX, PTR, A, NS, TXT and WHOIS records. Simple. Easy.",
                           'keywords'    => "all,in,one,dns,whois"
        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);

        $host = $this->input->post('host');
        if ($host == "") {
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
        if ($this->form_validation->run() == FALSE) {
            $this->dns();
        } else {
            $this->form_validation->set_rules('host', 'Hostname', 'valid_ip');
            if ($this->form_validation->run() == TRUE) { //if a valid IP, get PTR else nothing
                $data['PTR'] = gethostbyaddr($this->input->post('host'));
            }
            $data['dns'] = 1;
            $data['A'] = $this->techbits_model->lookup($this->input->post('host'), "A");
            $data['MX'] = $this->techbits_model->lookup($this->input->post('host'), "MX");
            $data['TXT'] = $this->techbits_model->lookup($this->input->post('host'), "TXT");
            $data['NS'] = $this->techbits_model->lookup($this->input->post('host'), "NS");
            $data['whois'] = $this->whoisclass->whoislookup($this->input->post('host'));
            if ($data['MX']['1']['ec'] < 1) { //at least 1 mx record exists
                foreach ($data['MX'] as $arr => $key) {
                    $data['MX_A'][$arr] = $this->techbits_model->lookup($data['MX'][$arr]['Target'], "A");
                    //echo "<pre>"; print_r($data['MX_A'][$arr]); echo "</pre>";
                    if ($data['MX_A'][$arr]['1']['ec'] < 1) { //if there is an A record setup for MX record
                        $data['MX_PTR'][$arr] = gethostbyaddr($data['MX_A'][$arr]['1']['IP']);////because the dns lookup function returns $data['record#'] but for the PTR lookup it will always be returning record#1 for each seperate lookup
                    }
                }//echo "<pre>";print_r($data['MX_A']);echo"</pre>";die();
                //
                //$data['PTR_A'] = $this->techbits_model->lookup($data['MX_PTR'],"A");
                //print_r($data['MX_A']); die();
            }
            $this->dns($data);
        }

    }


    public function speedTest()
    {
        $data_meta = array('title'       => "Flash Based Speed Test",
                           'description' => "Basic speed test with no extra spam, flash player required.",
                           'keywords'    => "speedtest,flash"

        );

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('speedTest_view');
        $this->load->view('footer_view');
    }

    public function pingAdd($data = null)
    {
        //$this->load->database();
        $this->load->model('techbits_model');
        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
        $this->load->model('securitychecks');
        $this->load->model('average30days_model');

        $data_meta = array(
            'title'       => "novascore.io | internet monitoring",
            'description' => "Rate your connection with our unique algorithm. NovaScore [90-100 = Solid], [50-89 = Good], [0-49 Suboptimal], [< 0 = ...]",
            'keywords'    => "ip,ping,monitoring,report,online,offline,alert",
        );

        $data['ips'] = $this->cellblock7->icmpTableData();
        $data['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $data['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('node');
        $data['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();

        $user_ip = $this->techbits_model->userIP();

        $data['user_ip'] = $user_ip;

        $data['refresh'] = "";

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('pingAdd_view', $data);
        $this->load->view('icmpTable_view', $data);
        $this->load->view('footer_view');

        //echo $this->techbits_model->captcha111();

    }

    /**
     *
     */
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
        //$this->form_validation->set_rules('verify','Verify','required|matches[image]');
        if ($this->form_validation->run() == FALSE) {

            /*$data = array('captcha_requested' => "yes",
                          'message' => "",
                          'cap_img' => $this->techbits_model->captcha111()
            );*/
            $this->pingAdd();
        } else {
            $perf_started = microtime(true);
            $duplicate_check = $this->icmpmodel->ipExists($this->input->post('ip'), $this->session->userdata('user_id'));
            if ($duplicate_check->num_rows() > 0) {
                $this->session->set_flashdata('message', '<span class="b"><font color="red"><strong>IP or Hostname 
                    is already being monitored. Maybe not in this group? <a href="' . base_url() . 'tools/pingAdd/">View All
                    </a></strong></font></span>');
                redirect(base_url() . $this->session->userdata('breadcrumbs'));
            }
            $count_check = $this->icmpmodel->monitorCount($this->session->userdata('user_id'));
            if ($count_check->num_rows() > 199) {
                $this->session->set_flashdata('message', '200 Monitors reached, please edit/delete you existing monitors or upgrade your account.<br><br>');
                redirect(current_url()); //reloads the page and uses the session message to pass error (how does for repop?)
            }
            $perf_dupComplete = number_format(microtime(true) - $perf_started,0);
            $this->db->where('id', 4);
            $this->db->update('other', array('value' => date('Y-m-d H:i:s') . " | query took $perf_dupComplete seconds"));

            $ping_ip_table_data = array(
                'ip'                    => $this->input->post('ip'),
                'last_ran'              => '2000-08-08 09:30:10',
                'note'                  => $this->input->post('note'),
                'public'                => 0,
                'owner'                 => $this->session->userdata('user_id'),
                'last_online_toggle'    => date('Y-m-d H:i:s'),
            );

            $this->db->insert('ping_ip_table', $ping_ip_table_data);
            $last_insert_id = $this->db->insert_id();

            $insertEmailAlert_data = array(
                'ping_ip_id' => $last_insert_id,
                'alert'      => $this->input->post('email'),
            );
            $this->sqlqu->insertEmailAlert($insertEmailAlert_data);

            $data = array('single_ip' => $this->input->post('ip')); //new array as we don't want to send 'owner' details to object as it does a isset for something else
            //print_r($data); die();
            $single_ip = $this->icmpmodel->getIPs($data); //so the return is in the correct format for the $this->checkICMP but with filter on just this IP

            $perf_preCheckIP = number_format(microtime(true) - $perf_started,0);
            $this->db->where('id', 5);
            $this->db->update('other', array('value' => date('Y-m-d H:i:s') . " | query took $perf_preCheckIP seconds"));

            $this->actionicmp->checkICMP($single_ip); //to get this IP monitored before we refresh the view, but don't have to wait for all of them to be checked we pass the

            $perf_checkICMP = number_format(microtime(true) - $perf_started,0);
            $this->db->where('id', 6);
            $this->db->update('other', array('value' => date('Y-m-d H:i:s') . " | query took $perf_checkICMP seconds"));

            if ($this->input->post('viewGroup') !== FALSE) { //user was viewing a group when adding node, so we're
                //going to auto add that node to the group
                $this->load->model('cellblock7');
                $this->cellblock7->addNodeToGroup($this->input->post('viewGroup'), $last_insert_id);
            }
            $this->session->set_flashdata('message', '<font color="red">IP added and checked, please review it in the table below.</font>');
            redirect(base_url() . $this->session->userdata('breadcrumbs'));

        }

    }

    public function hits($para1 = null)
    {
        $data = array("yesterday" => $para1);

        $data_meta = array('title'       => "Visitor Tracking",
                           'description' => "View recent activity on our website",
                           'keywords'    => "page,tracker,novascore"

        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('hits_view', $data);
        $this->load->view('footer_view');
    }

    public function popOut($refresh = null, $filter_group = null)
    {
        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
        $this->load->model('securitychecks');
        $this->load->model('average30days_model');
        $data_meta = array('title'       => "ICMP Monitor (Table pop out)",
                           'description' => "auto refresh webpage that displays your live ICMP monitors",
                           'keywords'    => "ip,monitoring,report,online"

        );

        $array = array(
            'owner_id'      => $this->session->userdata('user_id'),
            'group_id'      => $filter_group,
            'error_message' => "This group is private, please <a href=\"" . base_url() . "\">Login</a> to view, or
            request the group owner makes it public. ptfoh",
        );
        if (!$this->cellblock7->groupPublicCheck($array)) {
            $this->load->view('error_view', $array);
            return FALSE;
        }
        unset($array);

        if ($refresh == "stop") {
            $data_meta['refresh_content'] = "";
            $button = array('action' => 'stop');
        } else {
            $data_meta['refresh_content'] = 10;
            $button = array('action' => 'refresh');
        }

        $this->load->view('header_view', $data_meta);

        if (!$filter_group) {
            $data['ips'] = $this->cellblock7->icmpTableData();
        } else {
            $data['ips'] = $this->cellblock7->icmpTableData($filter_group);
            $button['group_name'] = $this->cellblock7->getGroupName($filter_group);
        }
        $data['owner_matches_table'] = $this->securitychecks->ownerMatchesLoggedIn('node');
        $data['diffPercentAndMs'] = $this->average30days_model->getPercentAndMsForDiff();

        $this->load->view('sub/countDown_view', $button);
        $this->load->view('icmpTable_view', $data);
        $this->load->view('footer_view');

    }

    public function icmpEdit()
    {
        $this->load->model('icmpmodel');
        $this->load->model('actionicmp');
        $this->load->model('sqlqu');
        $this->load->model('securitychecks');

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
            $duplicate_check = $this->icmpmodel->ipExists($this->input->post('ip'), $this->session->userdata('user_id'));
            if ($this->input->post('ip') !== $this->input->post('ip_existing')) {
                if ($duplicate_check->num_rows() > 0) {
                    $this->session->set_flashdata('message', '<span class="b"><font color="red"><strong>( ' . $this->input->post('ip') . ' 
                    )  is already being monitored. Maybe not in this group? <a href="' . base_url() . 'tools/pingAdd/">View All
                    </a></strong></font></span>');
                    $redirect_scroll_top = substr($this->session->userdata('breadcrumbs'), 0,
                        strpos($this->session->userdata('breadcrumbs'), "#"));
                    $redirect_scroll_top = $redirect_scroll_top . "#first";
                    redirect(base_url() . $redirect_scroll_top);
                }
            }
            $update_ping_ip_table = array(
                'note'   => $this->input->post('note'),
                'ip'     => $this->input->post('ip'),
                'public' => $this->input->post('ea'),
            );
            $this->db->where('owner', $this->session->userdata('user_id'));
            $this->db->where('id', $this->input->post('id'));
            $this->db->update('ping_ip_table', $update_ping_ip_table);

            $insertEmailAlert = array(
                'ping_ip_id' => $this->input->post('id'),
                'alert'      => $this->input->post('alert'),
            );
            $this->sqlqu->insertEmailAlert($insertEmailAlert);

            $data2 = array('single_ip' => $this->input->post('ip')); //new array as we don't want to send 'owner' details to object as it does an isset for something else
            $single_ip = $this->icmpmodel->getIPs($data2); //so the return is in the correct format for the $this->checkICMP but with filter on just this IP

            $this->actionicmp->checkICMP($single_ip);
            redirect(base_url() . $this->session->userdata('breadcrumbs')); //reloads the page as to refresh the form on successful submission
        }

        if ($_POST['action'] == 'Reset') {
            redirect(base_url() . $this->session->userdata('breadcrumbs'));

        }

        if ($_POST['action'] == 'Delete') { //need confrm delete before we action
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

            $this->db->where('ping_ip_id', $this->input->post('id'));
            $this->db->delete('alerts');
            redirect(base_url() . $this->session->userdata('breadcrumbs')); //reloads the page as to refresh the form on successful submission
        }
    }

    public function report($ip_id, $change = 1)
    {
        $this->load->library('table');
        $this->load->model('icmpmodel');
        $report_request = array("ip_id" => $ip_id,
                                "owner" => $this->icmpmodel->getUserID());
        $result = $this->icmpmodel->report($report_request, $change);


        $data_meta = array('title'       => "Activity Report",
                           'description' => "Dropped requests over the last <strong><u>week</u></strong>. You'll only receive an email when a node has been down for a minute, not for each dropped request.",
                           'keywords'    => "icmp,report,activity"
            //It's also the metric used for the novascore ICMP score which is (100 minus the number of sensitive status changes).
            //A low score compared to your other monitors (or those you see on the front page when not logged in) could indicate a poor line/host.
        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/icmp_view', $result);
        $this->load->view('footer_view');
    }

    public function controlResults()
    {
        $this->load->library('table');
        $this->load->model('icmpmodel');
        $this->load->model('lemon');
        $control['results'] = $this->lemon->controlResults();


        $data_meta = array('title'       => "Control Results",
                           'description' => "See when our server has failed the stability test. When we are not happy that the server is performing at maximum capacity it will not mark any nodes as failed.",
                           'keywords'    => "control,check,report"
            //It's also the metric used for the novascore ICMP score which is (100 minus the number of sensitive status changes).
            //A low score compared to your other monitors (or those you see on the front page when not logged in) could indicate a poor line/host.
        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/control_view', $control);
        $this->load->view('footer_view');
    }

    public function emailAlertHistory()
    {
        $this->load->library('table');
        $this->db->order_by('datetime', 'ASC');
        $michaeljordan['alerts'] = $this->db->get('history_email_alerts');

        $data_meta = array('title'       => "Email Alert History",
                           'description' => "Take a trip down memory lane and see what alerts have been sent out over the last couple of days.",
                           'keywords'    => "memory,lane"
            //It's also the metric used for the novascore ICMP score which is (100 minus the number of sensitive status changes).
            //A low score compared to your other monitors (or those you see on the front page when not logged in) could indicate a poor line/host.
        );
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/email_history_view', $michaeljordan);
        $this->load->view('footer_view');

    }


}

?>
