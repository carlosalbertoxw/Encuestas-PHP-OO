<?php

include_once 'application/db/connection.php';

class Public_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

}
