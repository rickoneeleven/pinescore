<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class UserModel extends CI_Model {
        
        public function __construct()
        {
            parent::__construct();
        }
        
        function login($email,$password)
        {
            $this->db->where("email",$email);
            $this->db->where("password",$password);

            $query=$this->db->get("user");
            if($query->num_rows()>0)
            {
                foreach($query->result() as $rows)
                {

                    $newdata = array(
                        'user_id'  => $rows->id,
                        'user_email'    => $rows->email,
                        'lastlogin' => $rows->lastlogin,
                        'hideOffline' => $rows->hideOffline,
                        'logged_in'  => TRUE,
                        'default_EA' => $rows->default_EA,
                    );
                }
                $this->session->set_userdata($newdata);
                $data2 = array(
                    'lastlogin' => date('Y-m-d H:i:s')
                );
                $this->db->where('id', $newdata['user_id']);
                $this->db->update('user', $data2);
                return true;
            }
            return false;
        }
    }
?>
