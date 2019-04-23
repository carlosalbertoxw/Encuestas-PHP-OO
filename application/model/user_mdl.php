<?php

include_once 'application/db/connection.php';

class User_MDL {

    private $db;

    public function __construct() {
        $this->db = new Connection();
    }

    public function u_m_update_profile($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "UPDATE a_users_profiles SET u_p_name='" . $conn->escape_string($a['name']) . "' WHERE u_p_key=" . $conn->escape_string($a['key']));
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_update_user($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "UPDATE a_users_profiles SET u_p_user_name='" . $conn->escape_string($a['user']) . "' WHERE u_p_key=" . $conn->escape_string($a['key']));
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_update_password($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "UPDATE a_users SET u_password='" . $conn->escape_string($a['new_password']) . "' WHERE u_key=" . $conn->escape_string($a['key']));
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_update_email($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->execute_query($conn, "UPDATE a_users SET u_email='" . $conn->escape_string($a['email']) . "' WHERE u_key=" . $conn->escape_string($a['key']));
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_delete_account($a) {
        $conn = $this->db->open_connection();
        $conn->autocommit(FALSE);
        $r1 = $this->db->execute_query($conn, "DELETE FROM a_answers WHERE a_user_key=" . $conn->escape_string($a['key']));
        $r2 = $this->db->execute_query($conn, "DELETE FROM a_polls WHERE p_user_key=" . $conn->escape_string($a['key']));
        $r3 = $this->db->execute_query($conn, "DELETE FROM a_users_profiles WHERE u_p_key=" . $conn->escape_string($a['key']));
        $r4 = $this->db->execute_query($conn, "DELETE FROM a_users WHERE u_key=" . $conn->escape_string($a['key']));
        if ($r1 == 0 and $r2 == 0 and $r3 == 0 and $r4 == 0) {
            $conn->commit();
            $res = 0;
        } else {
            $conn->rollback();
            $res = $r1 . ' ' . $r2 . ' ' . $r3 . ' ' . $r4;
        }
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_get_user_by_key($key) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_result($conn, "SELECT * FROM a_users WHERE u_key=" . $conn->escape_string($key));
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_get_profile($user) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_result($conn, "SELECT * FROM a_users_profiles WHERE u_p_user_name='" . $conn->escape_string($user) . "'");
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_get_user_by_email($a) {
        $conn = $this->db->open_connection();
        $res = $this->db->get_result($conn, "SELECT u.u_key,u.u_email,u.u_password,up.u_p_user_name,up.u_p_name FROM a_users as u JOIN a_users_profiles as up ON u.u_key=up.u_p_key WHERE u.u_email='" . $conn->escape_string($a['email']) . "'");
        $this->db->close_connection($conn);
        return $res;
    }

    public function u_m_sign_up($a) {
        $conn = $this->db->open_connection();
        $conn->autocommit(FALSE);
        $r1 = $this->db->execute_query($conn, "INSERT INTO a_users(u_email,u_password) VALUES('" . $conn->escape_string($a['email']) . "','" . $conn->escape_string($a['password']) . "')");
        $r2 = $this->db->execute_query($conn, "INSERT INTO a_users_profiles(u_p_key,u_p_user_name,u_p_name) VALUES((SELECT u_key FROM a_users WHERE u_email='" . $conn->escape_string($a['email']) . "'),concat('usuario',(SELECT u_key FROM a_users WHERE u_email='" . $conn->escape_string($a['email']) . "')),concat('Usuario',(SELECT u_key FROM a_users WHERE u_email='" . $conn->escape_string($a['email']) . "')))");
        if ($r1 == 0 and $r2 == 0) {
            $conn->commit();
            $res = 0;
        } else {
            $conn->rollback();
            $res = $r1 . '-' . $r2;
        }
        $this->db->close_connection($conn);
        return $res;
    }

}
