<?php

abstract class Controller {

    protected $load;
    protected $session;

    public function __construct() {
        $this->load = Load::get_instance();

        $this->load->helper('inputs_validation');
        $this->load->helper('message');
        $this->load->helper('session');
        $this->load->helper('encryption');
        $this->load->helper('random_string');

        $this->session = $this->session_validation();
    }

    public function session_validation() {
        $c = get_session_cookie();
        if ($c !== FALSE and val_input($c['u_key'], VI_NUMERIC, 10, FALSE) and val_input($c['u_email'], VI_EMAIL, 50, FALSE) and val_input($c['u_password'], VI_STRING, 500, FALSE)) {
            $model = new Model();
            $r = $model->get_user_by_key($c['u_key']);
            $r['remember'] = $c['remember'];
            if ($r['u_email'] == $c['u_email'] and $r['u_password'] == $c['u_password']) {
                return $c;
            }
        }
        remove_session_cookie();
        return FALSE;
    }

}
