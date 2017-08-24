<?php

include_once 'application/db/connection.php';

class User_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

    public function u_m_update_profile($a) {
        return $this->conn->execute_query("UPDATE p_user_profile SET u_p_name='" . $this->conn->escape_var($a['name']) . "', u_p_user='" . $this->conn->escape_var($a['user']) . "' WHERE u_p_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_update_password($a) {
        return $this->conn->execute_query("UPDATE p_user SET u_password='" . $this->conn->escape_var($a['new_password']) . "' WHERE u_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_update_email($a) {
        return $this->conn->execute_query("UPDATE p_user SET u_email='" . $this->conn->escape_var($a['email']) . "' WHERE u_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_delete_account($a) {
        $this->conn->autocommit(FALSE);
        $r1 = $this->conn->execute_query("DELETE FROM p_answer WHERE a_user_key=" . $this->conn->escape_var($a['key']));
        $r2 = $this->conn->execute_query("DELETE FROM p_poll WHERE p_user_key=" . $this->conn->escape_var($a['key']));
        $r3 = $this->conn->execute_query("DELETE FROM p_user_profile WHERE u_p_key=" . $this->conn->escape_var($a['key']));
        $r4 = $this->conn->execute_query("DELETE FROM p_user WHERE u_key=" . $this->conn->escape_var($a['key']));
        if ($r1 == 0 and $r2 == 0 and $r3 == 0 and $r4 == 0) {
            $this->conn->commit();
            return 0;
        } else {
            $this->conn->rollback();
            return $r1 . ' ' . $r2 . ' ' . $r3 . ' ' . $r4;
        }
    }

    public function u_m_get_user_by_key($key) {
        return $this->conn->get_result("SELECT * FROM p_user WHERE u_key=" . $this->conn->escape_var($key));
    }

}
