<?php

include_once 'application/db/connection.php';

class Answer_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

    public function a_m_add_answer($a) {
        return $this->conn->execute_query("INSERT INTO a_answer(a_stars,a_comment,a_poll_key,a_user_key) VALUES(" . $this->conn->escape_var($a['stars']) . ",'" . $this->conn->escape_var($a['comment']) . "'," . $this->conn->escape_var($a['poll_key']) . "," . $this->conn->escape_var($a['user_key']) . ")");
    }

    public function a_m_get_answers($key) {
        return $this->conn->get_results("SELECT * FROM a_answer WHERE a_poll_key=" . $this->conn->escape_var($key));
    }

}
