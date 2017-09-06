<?php

include_once 'application/db/connection.php';

class Model {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

    public function get_user_by_key($key) {
        return $this->conn->get_result("SELECT * FROM a_user WHERE u_key=" . $this->conn->escape_var($key));
    }

}
