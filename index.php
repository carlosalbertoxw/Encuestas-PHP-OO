<?php

include_once 'application/config/constants.php';
include_once 'application/core/load.php';
include_once 'application/core/controller.php';
include_once 'application/core/model.php';
include_once 'application/core/functions.php';

class Index {

    private $load;

    public function __construct() {
        $this->load = Load::get_instance();
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == 'POST' and filter_input(INPUT_POST, 'ajax') !== NULL) {
            $this->load->router('ajax');
        } else
        if ($method == 'POST') {
            $this->load->router('form');
        } else
        if ($method == 'GET') {
            $this->load->router('page');
        }
    }

}
