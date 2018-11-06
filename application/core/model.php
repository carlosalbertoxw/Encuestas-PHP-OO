<?php

include_once 'application/db/connection.php';

class Model {

    private $db;

    public function __construct() {
        $this->db = new Connection();
    }

    public function get_user_by_key($key) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_result($conn, "SELECT * FROM a_users WHERE u_key=" . $conn->escape_string($key));
        $this->db->close_connection($conn);
        return $res;
    }

}
