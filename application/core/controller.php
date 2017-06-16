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
        if ($c !== FALSE and val_input($c['key'], VI_NUMERIC, 10, FALSE) and val_input($c['email'], VI_EMAIL, 50, FALSE) and val_input($c['password'], VI_STRING, 500, FALSE)) {
            $model = new Model();
            $r = $model->get_user_by_key($c['key']);
            unset($r['password_key']);
            $r['remember'] = $c['remember'];
            if ($r['email'] == $c['email'] and $r['password'] == $c['password']) {
                return $r;
            }
        }
        remove_session_cookie();
        return FALSE;
    }

}
