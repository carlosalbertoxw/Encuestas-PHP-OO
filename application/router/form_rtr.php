<?php

class Form_RTR {

    private $load;

    public function __construct() {
        $this->load = Load::get_instance();
        if (filter_input(INPUT_GET, 'one') === 'public') {
            $this->public_router();
        } else
        if (filter_input(INPUT_GET, 'one') === 'session') {
            $this->session_router();
        } else
        if (URI === WEB_PATH . 'index.php') {
            header('Location:' . WEB_PATH);
        } else {
            $this->load->error_404();
        }
    }

    private function session_router() {
        if (filter_input(INPUT_GET, 'two') === 'delete-account') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_delete_account_form();
        } else
        if (filter_input(INPUT_GET, 'two') === 'change-email') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_change_email_form();
        } else
        if (filter_input(INPUT_GET, 'two') === 'change-password') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_change_password_form();
        } else
        if (filter_input(INPUT_GET, 'two') === 'edit-profile') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_edit_profile_form();
        } else {
            $this->load->error_404();
        }
    }

    private function public_router() {
        if (filter_input(INPUT_GET, 'two') === 'sign-up') {
            $ctl = $this->load->controller('public');
            $ctl->p_c_sign_up_form();
        } else
        if (filter_input(INPUT_GET, 'two') === 'sign-in') {
            $ctl = $this->load->controller('public');
            $ctl->p_c_sign_in_form();
        } else {
            $this->load->error_404();
        }
    }

}
