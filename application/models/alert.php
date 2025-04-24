<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Removed Psr\Log\LoggerInterface use statement

class Alert extends CI_model {

    // Removed logger property

    public function __construct()
    {
        parent::__construct();
        // No logger needed here
    }

    /**
     * Sets the disabled_until timestamp for all alerts associated with a user's email.
     *
     * @param string $user_email The email address of the user.
     * @param string|null $timestamp The future timestamp (YYYY-MM-DD HH:MM:SS) to disable until, or null to enable.
     * @return bool True on success, False on failure or if no alerts found.
     */
    public function set_alerts_disabled_until($user_email, $timestamp) // REMOVED: type hints string, ?string, : bool
    {
        if (empty($user_email)) {
             log_message('warn', 'set_alerts_disabled_until called with empty email. Timestamp: ' . ($timestamp ? $timestamp : 'NULL'));
            return false;
        }

        log_message('info', 'Attempting to update alert disable status for user: ' . $user_email . '. Set disabled_until to: ' . ($timestamp ? $timestamp : 'NULL'));

        try {
            $this->db->where('email', $user_email);
            $this->db->from('alerts');
            $count = $this->db->count_all_results(); // Check count before update

            if ($count === 0) {
                log_message('info', 'No alerts found for user ' . $user_email . ', no update performed.');
                return true; // Treat as success if nothing to update
            }

            $data = ['disabled_until' => $timestamp]; // $timestamp can be NULL or string
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
        } catch (Exception $e) { // Changed to generic Exception for PHP 5.6
            log_message('error', 'Exception occurred while updating alert disable status for user: ' . $user_email . '. Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the current disabled_until status for a user's alerts.
     *
     * @param string $user_email The email address of the user.
     * @return string|null The timestamp string if disabled, null if enabled or no alerts found.
     */
    public function get_alert_disable_status($user_email) // REMOVED: type hints string, : ?string
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
            $this->db->limit(1); // Optimization: only need one record to check status
            $query = $this->db->get();

            if ($query->num_rows() > 0) {
                $result = $query->row();
                log_message('debug', 'Found alert disable status for user ' . $user_email . ': ' . ($result->disabled_until ? $result->disabled_until : 'NULL'));
                return $result->disabled_until; // Returns the value (string or NULL)
            } else {
                log_message('info', 'No alerts found for user ' . $user_email . ' when checking disable status.');
                return null; // No alerts found, effectively enabled
            }
        } catch (Exception $e) { // Changed to generic Exception for PHP 5.6
            log_message('error', 'Exception occurred while fetching alert disable status for user: ' . $user_email . '. Error: ' . $e->getMessage());
            return null; // Return null on error
        }
    }

    /**
     * Placeholder function
     *
     * @param array $array Input array (structure TBD).
     * @return void
     */
    public function updateMultipleAlertEmailsInGroup($array) // REMOVED: array type hint, : void return type
    {
        log_message('warn', 'updateMultipleAlertEmailsInGroup function called but is not implemented.');
    }
}