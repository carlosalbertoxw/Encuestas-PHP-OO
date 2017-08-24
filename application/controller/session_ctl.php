<?php

class Session_CTL extends Controller {

    private $session_mdl;

    public function __construct() {
        parent::__construct();

        $this->session_mdl = $this->load->model('session');

        $this->session = $this->session_validation();
    }

    public function s_c_dashboard() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Tablero';
            $a['msg'] = read_msjs();
            $this->load->view('session/dashboard', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

}
