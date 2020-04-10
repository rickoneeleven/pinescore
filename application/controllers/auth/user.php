<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	class User extends CI_Controller{
		
		public function __construct()
		{
			parent::__construct();
			$this->load->model('auth/usermodel');
		}
		 
		public function login() {
			$email=$this->input->post('email');
			$password=md5($this->input->post('pass'));
		  
			$result=$this->usermodel->login($email,$password);
			if($result) redirect(base_url()."tools/pingAdd/");
			else        $this->failedLogin();
		}
		
		public function thank($message) {
			$data['title']= 'Thank You';
			$data['description'] = $message;
			$data['keywords'] = "registration, successful, password, reset";
		  
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view('auth/thank_view', $data);
			$this->load->view('footer_view',$data);
		}
		
		public function fail() {
			$data['title']= 'The failboat has arrived';
			$data['description'] = "Activation failed, please email the contact address at the bottom on this page if you think it is an error with our site.";
			$data['keywords'] = "registration, failed";
		  
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view('auth/thank_view', $data);
			$this->load->view('footer_view',$data);
		}
		
		public function mailConfirmation() {
			$this->load->model('email_dev_or_no');

			$data['title']= 'Are you human?';
			$data['description'] = "Please check your email and click the link to activate your account. The link is only valid for two days, after that you'll need to register again.";
			$data['keywords'] = "registration, confirmation";
		  
			$insert['code'] = md5(uniqid(rand(), true));
			$insert['email'] = set_value('email_address');
			$insert['datetime'] = date('Y:m:d H:i:s');
			$insert['password'] = md5(set_value('password'));			
			$this->db->insert('verify_email', $insert);
			
			$this->email->from(from_email, 'Activation');
			$this->email->to(set_value('email_address'));
			$this->email->bcc("r@novascore.io");	    
			$this->email->subject('novascore.io activation');
			$this->email->message("Please activate your account with the link below. You have two days. \r\n\r\n ".base_url()."auth/user/activate/".$this->db->insert_id()."/".$insert['code']);	
			$email_dev_array = array(
				'from_class__method'            => 'user__mailConfirmation'
			);
			if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
			
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view('auth/thank_view', $data);
			$this->load->view('footer_view',$data);
		}
		
		public function activate($id, $code) {
			$this->load->model('auth/emailvalidationmodel');			
			$result = $this->emailvalidationmodel->go($id, $code);
			if($result['validation'] == 1) {
				$data=array(
				  'email'=>$result['email'],
				  'password'=>$result['password'],
				);
				$this->db->insert('user',$data);
				$this->db->where('id', $id);
				$this->db->delete('verify_email'); 
				$this->thank('Your account has been successfully activated. Please login at the top right of this page to proceed.');
			} else {
				$this->fail();
			}
			
		}
		
		public function failedLogin() {
			$data['title']= 'Bad Username/Password';
            $data['description'] = "The login request has failed.";
			$data['keywords'] = "failed,login";
		  
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view('auth/failedLogin_view', $data);
			$this->load->view('footer_view',$data);
		}
		
		public function register($data=null) { //the nature of the code i took that this has ended as the form/page and 'registrtion' is validation
			$data['title']= 'New user registration';
			$data['description'] = "Register to start monitoring your nodes for free.";
			$data['keywords'] = "register,novascore.io,monitor";
			
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view("auth/registration_view.php");
			$this->load->view('footer_view');
		}
		
		public function forgot($data=null) { 
			$data['title']= 'Password Reset';
			$data['description'] = "If you have forgot your password, please enter the email address you registered with below and we will send you a new one.";
			$data['keywords'] = "password,forgot";
			
			$this->load->view('header_view',$data);
			$this->load->view('navTop_view');
			$this->load->view("auth/forgot_view.php");
			$this->load->view('footer_view');
		}
		
		public function forgotFormProcess() {
			$this->load->model('techbits_model');
			$this->load->model('email_dev_or_no');
			$this->load->library('form_validation');
			$this->load->helper('string');

			// field name, error message, validation rules
			$this->form_validation->set_rules('email', 'Email Address', 'valid_email|trim|required|xss_clean');
			$this->form_validation->set_rules('verify','Verify','required|matches[image]');

		  
			if($this->form_validation->run() == FALSE)
			{
				$data = array('captcha_requested' => "yes",
							  'message' => "",
							  'cap_img' => $this->techbits_model->captcha111()
							  );
				$this->forgot($data);
			}
			else {
				$existing_check = $this->techbits_model->accountExist(set_value('email'));
				if($existing_check->num_rows() > 0){
					foreach($existing_check->result() as $row) {
						$insert['code'] = md5(uniqid(rand(), true));
						$insert['email'] = $row->email;
						$insert['datetime'] = date('Y:m:d H:i:s');
						$unhashedPw = random_string('alnum', 6);
						$insert['password'] = md5($unhashedPw);			
						$this->db->insert('verify_email', $insert);
						
						$this->email->from(from_email, 'Password Reset Request');
						$this->email->to(set_value('email'));
						$this->email->bcc("r@novascore.io");	    
						$this->email->subject('novascore.io password reset request');
						$this->email->message("Please use the link below to confirm your password reset request. You have two days. \r\n\r\n".base_url()."auth/user/forgotFinal/".$this->db->insert_id()."/".$insert['code']."\r\n\r\nOnce the above has been clicked you're new password will be: ".$unhashedPw."\r\n\r\nIf you did not make this request, please delete this email and do not click the link. If you continue to receive this email it may mean someone is trying to compromise your account. Please contact us using the link at the bottom of the site for further assistance.\r\n\r\nThanks\r\nthe novascore.io team\r\n".base_url());	
						$email_dev_array = array(
							'from_class__method'            => 'user__forgotFormProcess'
						);
						if($this->email_dev_or_no->amIonAproductionServer($email_dev_array)) $this->email->send();
					}
				}
				$data['sent'] = 1;
				
				$this->forgot($data);
				//$this->usermodel->add_user();
				//$this->thank();
				 			
			}
		}
		
		public function forgotFinal($id, $code) {
			$this->load->model('auth/emailvalidationmodel');			
			$result = $this->emailvalidationmodel->go($id, $code);
			if($result['validation'] == 1) {
				$data=array(
				  'password'=>$result['password'],
				);
				$this->db->where('email', $result['email']);
				$this->db->update('user',$data);
				
				$this->db->where('id', $id);
				$this->db->delete('verify_email'); 
				$this->thank('Your password has successfully been reset, please use the new password you received to login at the top right. You can then change the password in options if required.');
			} else {
				$this->fail();
			}
			
		}
		
		public function registration() {
			$this->load->library('form_validation');
			$this->load->model('techbits_model');

			// field name, error message, validation rules
			$this->form_validation->set_rules('email_address', 'Your Email', 'trim|required|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[4]|max_length[32]');
			$this->form_validation->set_rules('con_password', 'Password Confirmation', 'trim|required|matches[password]');
			$this->form_validation->set_rules('verify','Verify','required|matches[image]');

		  
			if($this->form_validation->run() == FALSE)
			{
				$data = array('captcha_requested' => "yes",
							  'message' => "",
							  'cap_img' => $this->techbits_model->captcha111()
							  );
				$this->register($data);
			}
			else { 
				$duplicate_check = $this->techbits_model->accountExist(set_value('email_address'));
				if($duplicate_check->num_rows() > 0){
					$this->session->set_flashdata('message', '<p class="error">Account already registed with that email address.</p>');
					redirect(current_url()); //reloads the page and uses the session message to pass error (how does for repop?)
				}
				
				$this->mailConfirmation();
				//$this->usermodel->add_user();
				//$this->thank();
				 			
			}
		}
	}
?>
