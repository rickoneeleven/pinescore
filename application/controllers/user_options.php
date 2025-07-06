<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_options extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->helper('form');
        $this->load->helper('date');
        $this->load->model('alert');

        $this->_ensure_logged_in();
    }

    public function options($data = null)
    {
        $user_email = $this->session->userdata('user_email');
        $user_id = $this->session->userdata('user_id');

        log_message('info', 'Loading user options page for user_id: ' . $user_id);

        if ($data === null) {
            $data = [];
        }

        try {
            $data['alert_disable_status'] = $this->alert->get_alert_disable_status($user_email);
            $data['alerts_are_currently_disabled'] = ($data['alert_disable_status'] !== null && strtotime($data['alert_disable_status']) > now());
             log_message('debug', 'Alert disable status fetched for user_id ' . $user_id . ': ' . ($data['alert_disable_status'] ? $data['alert_disable_status'] : 'NULL') . '; CurrentlyDisabled: ' . ($data['alerts_are_currently_disabled'] ? 'Yes' : 'No'));

        } catch (Exception $e) {
             log_message('error', 'Error fetching alert disable status for user_id ' . $user_id . ': ' . $e->getMessage());
             $data['alert_disable_status'] = null;
             $data['alerts_are_currently_disabled'] = false;
             $this->session->set_flashdata('error_message', 'Could not retrieve alert status.');
        }

        $data['title'] = 'Account Options';
        $data['description'] = "You can manage your account settings and alert preferences here.";
        $data['keywords'] = "account,manage,settings,options,alerts";

        try {
            $this->load->view('header_view', $data);
            $this->load->view('navTop_view');
            $this->load->view("auth/options_view", $data);
            $this->load->view('footer_view');
        } catch (Exception $e) {
             log_message('error', 'Error loading options view for user_id ' . $user_id . ': ' . $e->getMessage());
            show_error('An error occurred while loading the options page. Please try again later.');
        }
    }

    public function optionsForm()
    {
        $user_id = $this->session->userdata('user_id');
        $user_email = $this->session->userdata('user_email');

        log_message('info', 'Processing options form submission for user_id: ' . $user_id);

        if ($this->input->post('password')) {
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[4]|max_length[32]');
            $this->form_validation->set_rules('password_confirm', 'Password Confirmation', 'trim|required|matches[password]');
             log_message('debug', 'Password change requested for user_id: ' . $user_id);
        }

        $new_email = $this->input->post('email');
        if ($user_email !== $new_email) {
            $this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email|callback_checkEmailExists');
             log_message('debug', 'Email change requested for user_id: ' . $user_id . ' to: ' . $new_email);
        } else {
             $this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email');
        }

        $this->form_validation->set_rules('hideOffline', 'Hide Offline Nodes', 'trim|integer|in_list[0,1]');
        $this->form_validation->set_rules('default_EA', 'Default setting for Public Access', 'trim|integer|in_list[0,1]');

        if ($this->form_validation->run() == FALSE) {
             log_message('warn', 'Options form validation failed for user_id: ' . $user_id . ' Errors: ' . validation_errors());
            $this->options();
        } else {
            $update_data = [
                'hideOffline'   => (int)$this->input->post('hideOffline'),
                'default_EA'    => (int)$this->input->post('default_EA'),
            ];

            if ($user_email !== $new_email) {
                $update_data['email'] = $new_email;
            }

            if ($this->input->post('password')) {
                $update_data['password'] = md5($this->input->post('password'));
            }

            try {
                $this->db->where('id', $user_id);
                $update_success = $this->db->update('user', $update_data);

                if ($update_success) {
                     log_message('info', 'User options updated successfully for user_id: ' . $user_id . '. Fields: ' . implode(', ', array_keys($update_data)));

                    $new_session_data = [
                        'hideOffline'   => $update_data['hideOffline'],
                        'default_EA'    => $update_data['default_EA'],
                    ];
                    if (isset($update_data['email'])) {
                        $new_session_data['user_email'] = $update_data['email'];
                    }
                    $this->session->set_userdata($new_session_data);
                     log_message('debug', 'User session updated for user_id: ' . $user_id);

                    $this->session->set_flashdata('message', 'Configuration saved successfully at '.date('H:i:s'));
                } else {
                     $db_error = $this->db->error();
                     log_message('error', 'Failed to update user options in database for user_id: ' . $user_id . '. DB Error: ' . print_r($db_error, true));
                     $this->session->set_flashdata('error_message', 'Failed to save configuration. Please try again.');
                }
            } catch (Exception $e) {
                 log_message('error', 'Exception during user options update for user_id ' . $user_id . ': ' . $e->getMessage());
                 $this->session->set_flashdata('error_message', 'An unexpected error occurred while saving configuration.');
            }

            redirect(base_url("user_options/options"));
        }
    }

    public function disable_alerts_temporarily()
    {
        $user_id = $this->session->userdata('user_id');
        $user_email = $this->session->userdata('user_email');
        $duration_hours = $this->input->get('duration') ? (int)$this->input->get('duration') : 2;

        if ($duration_hours <= 0 || $duration_hours > 168) {
             log_message('warn', 'Invalid duration requested for disabling alerts for user_id: ' . $user_id . '. Duration: ' . $duration_hours);
             $this->session->set_flashdata('error_message', 'Invalid duration specified.');
             redirect(base_url("user_options/options"));
             return;
        }

        log_message('info', 'Request received to disable alerts for user_id: ' . $user_id . ' for ' . $duration_hours . ' hours.');

        $disable_until_timestamp = now() + ($duration_hours * 3600);
        $disable_until_string = date('Y-m-d H:i:s', $disable_until_timestamp);

        try {
            $success = $this->alert->set_alerts_disabled_until($user_email, $disable_until_string);

            if ($success) {
                log_message('info', 'Alerts successfully disabled for user_id: ' . $user_id . ' until ' . $disable_until_string);
                $this->session->set_flashdata('message', "Email alerts have been disabled until {$disable_until_string}.");
            } else {
                log_message('error', 'Failed to disable alerts via model for user_id: ' . $user_id);
                $this->session->set_flashdata('error_message', 'Failed to disable alerts. Please try again.');
            }
        } catch (Exception $e) {
             log_message('error', 'Exception during alert disable for user_id ' . $user_id . ': ' . $e->getMessage());
             $this->session->set_flashdata('error_message', 'An unexpected error occurred while disabling alerts.');
        }

        redirect(base_url("user_options/options"));
    }

    public function enable_alerts_now()
    {
        $user_id = $this->session->userdata('user_id');
        $user_email = $this->session->userdata('user_email');

        log_message('info', 'Request received to enable alerts now for user_id: ' . $user_id);

        try {
            $success = $this->alert->set_alerts_disabled_until($user_email, null);

            if ($success) {
                log_message('info', 'Alerts successfully enabled for user_id: ' . $user_id);
                $this->session->set_flashdata('message', 'Email alerts have been re-enabled.');
            } else {
                log_message('error', 'Failed to enable alerts via model for user_id: ' . $user_id);
                $this->session->set_flashdata('error_message', 'Failed to enable alerts. Please try again.');
            }
        } catch (Exception $e) {
             log_message('error', 'Exception during alert enable for user_id ' . $user_id . ': ' . $e->getMessage());
             $this->session->set_flashdata('error_message', 'An unexpected error occurred while enabling alerts.');
        }

        redirect(base_url("user_options/options"));
    }

    public function checkEmailExists($email)
    {
        $user_id = $this->session->userdata('user_id');
        log_message('debug', 'Callback checkEmailExists running for user_id: ' . $user_id . ' checking email: ' . $email);

        try {
            $this->db->where('email', $email);
            $this->db->where('id !=', $user_id);
            $this->db->from('user');
            $count = $this->db->count_all_results();

            if ($count > 0) {
                 log_message('warn', 'Email existence check failed - email already in use. User_id: ' . $user_id . ', Email: ' . $email);
                $this->form_validation->set_message('checkEmailExists', 'The {field} provided is already registered to another account.');
                return FALSE;
            } else {
                 log_message('debug', 'Email existence check passed for user_id: ' . $user_id . ', Email: ' . $email);
                return TRUE;
            }
        } catch (Exception $e) {
             log_message('error', 'Exception during email existence check for user_id ' . $user_id . ', Email: ' . $email . '. Error: ' . $e->getMessage());
             $this->form_validation->set_message('checkEmailExists', 'An error occurred while verifying the email address. Please try again.');
             return FALSE;
        }
    }

    private function _ensure_logged_in()
    {
        if (!$this->session->userdata('logged_in')) {
             log_message('warn', 'Unauthorized access attempt to user_options controller from IP: ' . $this->input->ip_address());
            $this->session->set_flashdata('error_message', 'You must be logged in to access this page.');
            redirect(base_url());
            exit;
        }
    }
}