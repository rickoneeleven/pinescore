<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Alert extends CI_model {

    public function __construct()
    {
        parent::__construct();

    }

    public function set_alerts_disabled_until($user_email, $timestamp)
    {
        if (empty($user_email)) {
             log_message('warn', 'set_alerts_disabled_until called with empty email. Timestamp: ' . ($timestamp ? $timestamp : 'NULL'));
            return false;
        }

        log_message('info', 'Attempting to update alert disable status for user: ' . $user_email . '. Set disabled_until to: ' . ($timestamp ? $timestamp : 'NULL'));

        try {
            $this->db->where('email', $user_email);
            $this->db->from('alerts');
            $count = $this->db->count_all_results();

            if ($count === 0) {
                log_message('info', 'No alerts found for user ' . $user_email . ', no update performed.');
                return true;
            }

            $data = ['disabled_until' => $timestamp];
            $this->db->where('email', $user_email);
            $update_result = $this->db->update('alerts', $data);

            if ($update_result) {
                $affected_rows = $this->db->affected_rows();
                log_message('info', 'Successfully updated alert disable status for user: ' . $user_email . '. Affected rows: ' . $affected_rows);
                return true;
            } else {
                 $db_error = $this->db->error();
                 log_message('error', 'Database error occurred while updating alert disable status for user: ' . $user_email . '. DB Error: ' . print_r($db_error, true));
                return false;
            }
        } catch (Exception $e) {
            log_message('error', 'Exception occurred while updating alert disable status for user: ' . $user_email . '. Error: ' . $e->getMessage());
            return false;
        }
    }

    public function get_alert_disable_status($user_email)
    {
        if (empty($user_email)) {
             log_message('warn', 'get_alert_disable_status called with empty email.');
            return null;
        }

        log_message('debug', 'Fetching alert disable status for user: ' . $user_email);

        try {
            $this->db->select('disabled_until');
            $this->db->from('alerts');
            $this->db->where('email', $user_email);
            $this->db->limit(1);
            $query = $this->db->get();

            if ($query->num_rows() > 0) {
                $result = $query->row();
                log_message('debug', 'Found alert disable status for user ' . $user_email . ': ' . ($result->disabled_until ? $result->disabled_until : 'NULL'));
                return $result->disabled_until;
            } else {
                log_message('info', 'No alerts found for user ' . $user_email . ' when checking disable status.');
                return null;
            }
        } catch (Exception $e) {
            log_message('error', 'Exception occurred while fetching alert disable status for user: ' . $user_email . '. Error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateMultipleAlertEmailsInGroup($array)
    {
        log_message('warn', 'updateMultipleAlertEmailsInGroup function called but is not implemented.');
    }
}