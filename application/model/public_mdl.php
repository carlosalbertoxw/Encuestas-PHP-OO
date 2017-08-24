<?php

include_once 'application/db/connection.php';

class Public_MDL {

    private $conn;

    public function __construct() {
        $this->conn = Connection::get_instance();
    }

    public function p_m_get_profile($user) {
        return $this->conn->get_result("SELECT * FROM p_user_profile WHERE u_p_user='" . $this->conn->escape_var($user) . "'");
    }

    public function p_m_get_user_by_email($a) {
        return $this->conn->get_result("SELECT u.u_key,u.u_email,u.u_password,up.u_p_user,up.u_p_name FROM p_user as u JOIN p_user_profile as up ON u.u_key=up.u_p_key WHERE u.u_email='" . $this->conn->escape_var($a['email']) . "'");
    }

    public function p_m_sign_up($a) {
        $this->conn->autocommit(FALSE);
        $r1 = $this->conn->execute_query("INSERT INTO p_user(u_email,u_password) VALUES('" . $this->conn->escape_var($a['email']) . "','" . $this->conn->escape_var($a['password']) . "')");
        $r2 = $this->conn->execute_query("INSERT INTO p_user_profile(u_p_key,u_p_user,u_p_name) VALUES((SELECT u_key FROM p_user WHERE u_email='" . $this->conn->escape_var($a['email']) . "'),concat('usuario',(SELECT u_key FROM p_user WHERE u_email='" . $this->conn->escape_var($a['email']) . "')),concat('Usuario',(SELECT u_key FROM p_user WHERE u_email='" . $this->conn->escape_var($a['email']) . "')))");
        if ($r1 == 0 and $r2 == 0) {
            $this->conn->commit();
            return 0;
        } else {
            $this->conn->rollback();
            return $r1 . '-' . $r2;
        }
    }

}
