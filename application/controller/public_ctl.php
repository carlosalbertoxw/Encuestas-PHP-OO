<?php

class Public_CTL extends Controller {

    private $public_mdl;

    public function __construct() {
        parent::__construct();

        //$this->public_mdl = $this->load->model('public');
    }

    public function p_c_home() {
        $a['title'] = 'Inicio';
        $a['msg'] = read_msjs();
        $this->load->view('home', $a);
    }

}
