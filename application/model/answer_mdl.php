<?php

include_once 'application/db/connection.php';

class Answer_MDL {

    private $db;

    public function __construct() {
        $this->db = new Connection();
    }

    public function a_m_add_answer($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "INSERT INTO a_answers(a_stars,a_comment,a_poll_key,a_user_key) VALUES(" . $conn->escape_string($a['stars']) . ",'" . $conn->escape_string($a['comment']) . "'," . $conn->escape_string($a['poll_key']) . "," . $conn->escape_string($a['user_key']) . ")");
        $this->db->close_connection($conn);
        return $res;
    }

    public function a_m_get_answers($key) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_results($conn, "SELECT * FROM a_answers WHERE a_poll_key=" . $conn->escape_string($key));
        $this->db->close_connection($conn);
        return $res;
    }

}
