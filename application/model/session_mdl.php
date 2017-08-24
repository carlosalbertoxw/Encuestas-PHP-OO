<?php

include_once 'application/db/connection.php';

class Session_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

}
