<?php

include_once 'application/db/connection.php';

class Poll_MDL {

    private $db;

    public function __construct() {
        $this->db = Connection::get_instance();
    }

    public function p_m_get_poll($key) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_result($conn, "SELECT * FROM a_polls AS p JOIN a_users_profiles as up ON p.p_user_key=up.u_p_key WHERE p.p_key=" . $conn->escape_string($key));
        $this->db->close_connection($conn);
        return $res;
    }

    public function p_m_delete_poll($a) {
        $conn = $this->db->open_connection();
        $conn->autocommit(FALSE);
        $r1 = $this->db->execute_query($conn, "DELETE FROM a_answers WHERE a_poll_key=" . $conn->escape_string($a['key']));
        $r2 = $this->db->execute_query($conn, "DELETE FROM a_polls WHERE p_user_key=" . $conn->escape_string($a['user_key']) . " AND p_key=" . $conn->escape_string($a['key']));
        if ($r1 == 0 and $r2 == 0) {
            $conn->commit();
            $res = 0;
        } else {
            $conn->rollback();
            $res = $r1 . ' ' . $r2;
        }
        $this->db->close_connection($conn);
        return $res;
    }

    public function p_m_update_poll($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "UPDATE a_polls SET p_title='" . $conn->escape_string($a['title']) . "', p_description='" . $conn->escape_string($a['description']) . "', p_position=" . $conn->escape_string($a['position']) . " WHERE p_user_key=" . $conn->escape_string($a['user_key']) . " AND p_key=" . $conn->escape_string($a['key']));
        $this->db->close_connection($conn);
        return $res;
    }

    public function p_m_new_poll($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "INSERT INTO a_polls(p_title,p_description,p_position,p_user_key) VALUES('" . $conn->escape_string($a['title']) . "','" . $conn->escape_string($a['description']) . "'," . $conn->escape_string($a['position']) . "," . $conn->escape_string($a['key']) . ")");
        $this->db->close_connection($conn);
        return $res;
    }

    public function p_m_get_polls($key) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_results($conn, "SELECT * FROM a_polls WHERE p_user_key=" . $conn->escape_string($key) . " ORDER BY p_position ASC");
        $this->db->close_connection($conn);
        return $res;
    }

}
