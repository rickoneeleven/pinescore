<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sausage extends CI_Controller {

    public function viewAllGroups() {

        $this->load->model('icmpmodel');
        $this->load->model('cellblock7');
        $data_meta = array(
            'title' => "View All Groups",
            'description' => "View all your groups in one easy to manage place. Hi.",
            'breadcrumbs' => '<a href="javascript:history.back();">[Go Back]</a>',
            'keywords' => "view,all,groups"
        );

        $data_for_view['myReports'] = $this->cellblock7->getMyReports($this->session->userdata('user_id'));
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('viewallgroups', $data_for_view);
        $this->load->view('footer_view');
    }

    public function smtpAuthTest($returned_output = null) {
        $data_meta = array(
            'title' => "SMTP Authentication and Relay Test",
            'description' => "Use our form to test smtp authentication and relaying on your server.",
            'breadcrumbs' => '<a href="javascript:history.back();">[Go Back]</a>',
            'keywords' => "smtp, auth, authentication, relay"
        );

        $data_for_view['returned_output'] = $returned_output;
        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('smtp_auth_test', $data_for_view);
        $this->load->view('footer_view');
    }

    public function smtpAuthTestFORM() {
        /**
         * if validation passes, we turn off error_reporting as the CI email helper doesn't check to see if fsock connects and catches it gracefully it just goes full steam ahead and throws loads of PHP errors. We can see the problem in the mail output anyway, so we don't need PHP errors here.
         * null_fix is because the form validation fails if the form value is null, but it's null that you have to pass to CI if you want no auth. Also, if the value is null, it won't auto select it on reload when validation fails as it was null rather than a value.
         */
        $this->load->model('email_dev_or_no');
        $this->form_validation->set_rules('server_address', 'Server Address', 'trim|required|xss_clean');
        $this->form_validation->set_rules('email_to', 'Email To Address', 'valid_email|required|trim|xss_clean');
        $this->form_validation->set_rules('port', 'Port', 'trim|required|xss_clean');
        $this->form_validation->set_rules('username', 'Username', 'trim|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'trim|xss_clean');
        $this->form_validation->set_rules('email_from', 'Email From Address', 'valid_email|trim|xss_clean');
        $this->form_validation->set_rules('crypto', 'Encryption', 'required|xss_clean');

        if(!$this->input->post('email_from')) {
            $email_from = "testing@pinescore.com";
        } else {
            $email_from = $this->input->post('email_from');
        }

        $encryption = $this->input->post('crypto');
        if($encryption == "null_fix") {
            $encryption = null;
        }
      
        if($this->form_validation->run() == FALSE)
        {
            $this->smtpAuthTest();
        }
        else {
            error_reporting(0);
            $config = array(
                'protocol' =>  'smtp',
                'smtp_host' => $this->input->post('server_address'),
                'smtp_port' => $this->input->post('port'),
                'smtp_user' => $this->input->post('username'), 
                'smtp_pass' => $this->input->post('password'),
                'mailtype'  => 'text',
                'charset'   => 'utf-8',
                'wordwrap'  => true,
                'wrapchars' => 50,        
                'crlf'      => "\r\n",
                'newline'   => "\r\n",
                'smtp_crypto' => $this->input->post('crypto'),
            );

            $this->load->library('email');
            $this->email->initialize($config);

            $this->email->from($email_from);
            $this->email->to($this->input->post('email_to'));
            $this->email->reply_to($email_from, 'pinescore.com');
            $this->email->subject('SMTP Authentication test from '.base_url());
            $this->email->message('You can now delete this message.');
            $email_dev_array = array(
                'from_class__method'            => 'sausage__smtpAuthTestFORM'
            );
            if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
            $this->smtpAuthTest($this->email->print_debugger(), true); 
        }
    }
}
