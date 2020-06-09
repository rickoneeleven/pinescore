<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    class Inmyday extends CI_Controller {
        
        public function oldTools() {
            $data_meta = array(
                'title' => "pinescore.com | historical tools",
                'description' => "Blow off the dust, check out some of our older tools.",
                'keywords' => "dns lookup, email testing, smtp auth",
            );
            $this->load->view('header_view', $data_meta);
            $this->load->view('tools_nav_buttons');
        }
    }
?>
