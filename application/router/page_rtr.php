<?php

class Page_RTR {

    private $load;

    public function __construct() {
        $this->load = Load::get_instance();
        if (filter_input(INPUT_GET, 'one') === 'session') {
            $this->session_router();
        } else
        if (filter_input(INPUT_GET, 'one') === 'public') {
            $this->public_router();
        } else
        if (filter_input(INPUT_GET, 'one') === '') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_home();
        } else
        if (URI === WEB_PATH . 'index.php') {
            header('Location:' . WEB_PATH);
        } else {
            $ctl = $this->load->controller('user');
            $ctl->u_c_profile();
        }
    }

    private function public_router() {
        if (filter_input(INPUT_GET, 'two') === 'add-answer') {
            $ctl = $this->load->controller('answer');
            $ctl->a_c_add_answer();
        } else {
            $this->load->error_404();
        }
    }

    private function session_router() {
        if (filter_input(INPUT_GET, 'two') === 'view-answers') {
            $ctl = $this->load->controller('answer');
            $ctl->a_c_view_answers();
        } else
        if (filter_input(INPUT_GET, 'two') === 'delete-poll') {
            $ctl = $this->load->controller('poll');
            $ctl->p_c_delete_poll();
        } else
        if (filter_input(INPUT_GET, 'two') === 'edit-poll') {
            $ctl = $this->load->controller('poll');
            $ctl->p_c_edit_poll();
        } else
        if (filter_input(INPUT_GET, 'two') === 'new-poll') {
            $ctl = $this->load->controller('poll');
            $ctl->p_c_new_poll();
        } else
        if (filter_input(INPUT_GET, 'two') === 'dashboard') {
            $ctl = $this->load->controller('poll');
            $ctl->p_c_dashboard();
        } else
        if (filter_input(INPUT_GET, 'two') === 'close-session') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_close_session();
        } else
        if (filter_input(INPUT_GET, 'two') === 'delete-account') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_delete_account();
        } else
        if (filter_input(INPUT_GET, 'two') === 'edit-profile') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_edit_profile();
        } else
        if (filter_input(INPUT_GET, 'two') === 'change-user') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_change_user();
        } else
        if (filter_input(INPUT_GET, 'two') === 'change-password') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_change_password();
        } else
        if (filter_input(INPUT_GET, 'two') === 'change-email') {
            $ctl = $this->load->controller('user');
            $ctl->u_c_change_email();
        } else {
            $this->load->error_404();
        }
    }

}
