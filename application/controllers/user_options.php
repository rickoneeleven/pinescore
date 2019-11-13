<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	class User_options extends CI_Controller{
		
		public function options($data=null) { //the nature of the code i took that this has ended as the form/page and 'registrtion' is validation
			$this->load->helper('form');
			$data['title']= 'Account Options';
			$data['description'] = "You can manage your account settings here.";
			$data['keywords'] = "account,manage,settings";
			
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view("auth/options_view.php");
			$this->load->view('footer_view');
		}
		
		public function optionsForm() {
            /**
             * we only want to perform the email validation, if the user is trying to change their email address
             */
            $this->load->library('form_validation');
            if($this->input->post('password')) {
                $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[4]|max_length[32]');
                $this->form_validation->set_rules('password_confirm', 'Password Confirmation', 'trim|required|matches[password]');
            }
            if($this->session->userdata('user_email') != $this->input->post('email')) {
                $this->form_validation->set_rules('email', 'Email Address', 'valid_email|trim|required|xss_clean|callback_checkEmailExists');
            }
            $this->form_validation->set_rules('hideOffline', 'Hide Offline Nodes', 'xss_clean');
            $this->form_validation->set_rules('default_EA', 'Default setting for EA', 'xss_clean');

            if($this->form_validation->run() == FALSE) {
                $this->options();
            } else {
                $data = array(
                    'email'         => $this->input->post('email'),
                    'hideOffline'   => $this->input->post('hideOffline'),
                    'default_EA'    => $this->input->post('default_EA'),
                );
                if($this->input->post('password')) $data['password'] = md5($this->input->post('password')); 
                $this->db->where('id', $this->session->userdata('user_id'));
                $this->db->update('user', $data); 
                
                $new_session_data = array(
                    'hideOffline'   => $this->input->post('hideOffline'),
                    'user_email'    => $this->input->post('email'),
                    'default_EA'    => $this->input->post('default_EA'),
                ); 
                $this->session->set_userdata($new_session_data);
                $this->session->set_flashdata('message', 'Configuration saved at '.date('H:i:s'));
                redirect(base_url()."user_options/options"); //if you just load $this->options the flashmessage is like one refresh behind
            }
			
		}

        public function checkEmailExists() {
            $this->db->where('email', $this->input->post('email'));
            $emailCheckExist = $this->db->get('user');
            if($emailCheckExist->num_rows() > 0) {
                $this->form_validation->set_message('checkEmailExists', 'Email address already in use by another account.');
                return FALSE;
            } else {
                return TRUE;
            }
        }
	}
?>
