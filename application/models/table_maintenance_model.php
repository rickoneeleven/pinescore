<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Table_maintenance_model extends CI_Model {

    private $CI;
    
    const TRUNCATION_THRESHOLD = 1000000000;

    public function __construct()
    {
        parent::__construct();
        $this->CI =& get_instance();
    }

    public function check_and_truncate_ping_table()
    {
        try {
            log_message('debug', 'Table_maintenance_model: Starting ping_result_table auto_increment check.');

            $query = $this->CI->db->query("SHOW TABLE STATUS LIKE 'ping_result_table'");

            if (!$query || !$query->row()) {
                log_message('error', 'Table_maintenance_model: Failed to retrieve status for ping_result_table.');
                return 'ERROR';
            }

            $table_status = $query->row();
            $current_increment = (int) $table_status->Auto_increment;

            log_message('info', 'Table_maintenance_model: Current ping_result_table AUTO_INCREMENT is ' . $current_increment);

            if ($current_increment >= self::TRUNCATION_THRESHOLD) {
                log_message('warn', 'Table_maintenance_model: Threshold exceeded. Truncating ping_result_table.');
                
                $this->CI->db->query('TRUNCATE TABLE ping_result_table');
                
                $this->record_truncation_event();

                log_message('warn', 'Table_maintenance_model: Truncation of ping_result_table complete.');
                return 'TRUNCATED';
            }

            log_message('debug', 'Table_maintenance_model: Threshold not met. No action taken.');
            return 'NOT_REQUIRED';

        } catch (Exception $e) {
            log_message('error', 'Table_maintenance_model: Exception during check/truncation. Message: ' . $e->getMessage());
            return 'ERROR';
        }
    }

    private function record_truncation_event()
    {
        $metric_name = 'ping_table_last_truncation';
        $timestamp = date('Y-m-d H:i:s');
        
        $this->CI->db->where('metric', $metric_name);
        $this->CI->db->update('health_dashboard', ['result' => $timestamp]);

        if ($this->CI->db->affected_rows() == 0) {
             $this->CI->db->insert('health_dashboard', ['metric' => $metric_name, 'result' => $timestamp]);
        }
        log_message('info', 'Table_maintenance_model: Recorded truncation event timestamp.');
    }
}