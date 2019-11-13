<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class EmailValidationModel extends CI_Model {
        
        public function __construct()
        {
            parent::__construct();
        }
        
        function go($id,$code) {
            $query = $this->db->get_where('verify_email', array('id' => $id));
            $data['validation'] = 0;
            foreach ($query->result() as $row) {
                
                if($row->code == $code) {
                    
                    $data['validation'] = 1;
                    $data['password'] = $row->password;
                    $data['email'] = $row->email;
                    
                }
                
            }
            return $data;
        }
        
    }
    
?>
