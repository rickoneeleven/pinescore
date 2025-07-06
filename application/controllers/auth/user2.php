<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	class User2 extends CI_Controller{
		
		public function logout() {
			$newdata = array(
			'user_id'   =>'',
			'user_email'     => '',
			'logged_in' => FALSE,
			);
			$this->session->unset_userdata($newdata );
			$this->session->sess_destroy();

			redirect(base_url());
			}
	}
?>
