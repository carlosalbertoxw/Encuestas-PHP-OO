<?php

include_once 'application/db/connection.php';

class User_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

    public function u_m_update_profile($a) {
        return $this->conn->execute_query("UPDATE a_user_profile SET u_p_name='" . $this->conn->escape_var($a['name']) . "' WHERE u_p_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_update_user($a) {
        return $this->conn->execute_query("UPDATE a_user_profile SET u_p_user='" . $this->conn->escape_var($a['user']) . "' WHERE u_p_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_update_password($a) {
        return $this->conn->execute_query("UPDATE a_user SET u_password='" . $this->conn->escape_var($a['new_password']) . "' WHERE u_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_update_email($a) {
        return $this->conn->execute_query("UPDATE a_user SET u_email='" . $this->conn->escape_var($a['email']) . "' WHERE u_key=" . $this->conn->escape_var($a['key']));
    }

    public function u_m_delete_account($a) {
        $this->conn->autocommit(FALSE);
        $r1 = $this->conn->execute_query("DELETE FROM a_answer WHERE a_user_key=" . $this->conn->escape_var($a['key']));
        $r2 = $this->conn->execute_query("DELETE FROM a_poll WHERE p_user_key=" . $this->conn->escape_var($a['key']));
        $r3 = $this->conn->execute_query("DELETE FROM a_user_profile WHERE u_p_key=" . $this->conn->escape_var($a['key']));
        $r4 = $this->conn->execute_query("DELETE FROM a_user WHERE u_key=" . $this->conn->escape_var($a['key']));
        if ($r1 == 0 and $r2 == 0 and $r3 == 0 and $r4 == 0) {
            $this->conn->commit();
            return 0;
        } else {
            $this->conn->rollback();
            return $r1 . ' ' . $r2 . ' ' . $r3 . ' ' . $r4;
        }
    }

    public function u_m_get_user_by_key($key) {
        return $this->conn->get_result("SELECT * FROM a_user WHERE u_key=" . $this->conn->escape_var($key));
    }

    public function u_m_get_profile($user) {
        return $this->conn->get_result("SELECT * FROM a_user_profile WHERE u_p_user='" . $this->conn->escape_var($user) . "'");
    }

    public function u_m_get_user_by_email($a) {
        return $this->conn->get_result("SELECT u.u_key,u.u_email,u.u_password,up.u_p_user,up.u_p_name FROM a_user as u JOIN a_user_profile as up ON u.u_key=up.u_p_key WHERE u.u_email='" . $this->conn->escape_var($a['email']) . "'");
    }

    public function u_m_sign_up($a) {
        $this->conn->autocommit(FALSE);
        $r1 = $this->conn->execute_query("INSERT INTO a_user(u_email,u_password) VALUES('" . $this->conn->escape_var($a['email']) . "','" . $this->conn->escape_var($a['password']) . "')");
        $r2 = $this->conn->execute_query("INSERT INTO a_user_profile(u_p_key,u_p_user,u_p_name) VALUES((SELECT u_key FROM a_user WHERE u_email='" . $this->conn->escape_var($a['email']) . "'),concat('usuario',(SELECT u_key FROM a_user WHERE u_email='" . $this->conn->escape_var($a['email']) . "')),concat('Usuario',(SELECT u_key FROM a_user WHERE u_email='" . $this->conn->escape_var($a['email']) . "')))");
        if ($r1 == 0 and $r2 == 0) {
            $this->conn->commit();
            return 0;
        } else {
            $this->conn->rollback();
            return $r1 . '-' . $r2;
        }
    }

}
