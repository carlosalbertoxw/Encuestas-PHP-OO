<?php

include_once 'application/db/connection.php';

class Poll_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

    public function p_m_get_poll($key) {
        return $this->conn->get_result("SELECT * FROM a_polls AS p JOIN a_users_profiles as up ON p.p_user_key=up.u_p_key WHERE p.p_key=" . $this->conn->escape_var($key));
    }

    public function p_m_delete_poll($a) {
        $this->conn->autocommit(FALSE);
        $r1 = $this->conn->execute_query("DELETE FROM a_answers WHERE a_poll_key=" . $this->conn->escape_var($a['key']));
        $r2 = $this->conn->execute_query("DELETE FROM a_polls WHERE p_user_key=" . $this->conn->escape_var($a['user_key']) . " AND p_key=" . $this->conn->escape_var($a['key']));
        if ($r1 == 0 and $r2 == 0) {
            $this->conn->commit();
            return 0;
        } else {
            $this->conn->rollback();
            return $r1 . ' ' . $r2;
        }
    }

    public function p_m_update_poll($a) {
        return $this->conn->execute_query("UPDATE a_polls SET p_title='" . $this->conn->escape_var($a['title']) . "', p_description='" . $this->conn->escape_var($a['description']) . "', p_position=" . $this->conn->escape_var($a['position']) . " WHERE p_user_key=" . $this->conn->escape_var($a['user_key']) . " AND p_key=" . $this->conn->escape_var($a['key']));
    }

    public function p_m_new_poll($a) {
        return $this->conn->execute_query("INSERT INTO a_polls(p_title,p_description,p_position,p_user_key) VALUES('" . $this->conn->escape_var($a['title']) . "','" . $this->conn->escape_var($a['description']) . "'," . $this->conn->escape_var($a['position']) . "," . $this->conn->escape_var($a['key']) . ")");
    }

    public function p_m_get_polls($key) {
        return $this->conn->get_results("SELECT * FROM a_polls WHERE p_user_key=" . $this->conn->escape_var($key) . " ORDER BY p_position ASC");
    }

}
