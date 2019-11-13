<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SecurityChecks extends CI_model {

    function loggedIn() {
        if(($this->session->userdata('user_email')!="")) {//is user logged in check [true] else
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function ownerCheckRedirect($owner) {
        if($this->session->userdata('user_id') !==$owner) {
            echo "error code: dave has no pajamas";
            //you don't own this group
            header('Refresh: 3;url=https://www.google.co.uk/search?num=20&safe=strict&site=&source=hp&q=sausage+butty+time');
            die();
        } else {
            return TRUE;
        }
    }

    public function ownerMatchesLoggedIn($group_or_node) {
        $owner_field_name = "owner";
        if($group_or_node === "group") {
            $table = "grouped_reports"; 
            $owner_field_name = "owner_id";
        }
        if($group_or_node === "node") $table = "ping_ip_table"; 
        $this->db->limit(1);
        $this->db->where($owner_field_name, $this->session->userdata('user_id'));
        $Table = $this->db->get($table);
        if($Table->num_rows() > 0) return TRUE;
        return FALSE;
    }
}
?>
