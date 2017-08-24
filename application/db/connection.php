<?php

function write_log($cadena, $tipo) {
    $file = fopen(realpath('.') . "/application/db/dbl/log_" . date("Y-m-d") . ".txt", "a+");
    fwrite($file, "[" . date("Y-m-d H:i:s") . "] - " . $tipo . " - " . $cadena . "\n");
    fclose($file);
}

class Connection {

    private static $instance = NULL;
    private $conn = FALSE;

    private function __construct() {
        $this->open_connection();
    }

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new Connection();
        }
        return self::$instance;
    }

    public function open_connection() {
        if (!$this->conn) {
            $this->conn = new mysqli('localhost', 'root', '1029384756', 'PHPOOTemplate');
            if ($this->conn->connect_errno == 0) {
                $this->conn->set_charset('UTF8');
                //write_log('----conexión abierta----', 'info');
            } else {
                write_log($this->conn->connect_errno . ' - Error al abrir la conexión a la BD - ' . $this->conn->connect_error, 'Error');
            }
        }
    }

    public function close_connection() {
        if ($this->conn !== FALSE) {
            $this->conn->close();
            $this->conn = FALSE;
            //write_log('----conexión cerrada----', 'info');
        }
    }

    public function autocommit($mode) {
        $this->conn->autocommit($mode);
    }

    public function rollback() {
        $this->conn->rollback();
    }

    public function commit() {
        $this->conn->commit();
    }

    public function escape_var($var) {
        $string = $this->conn->escape_string($var);
        return $string;
    }

    public function execute_query($query) {
        $this->conn->query($query);
        $result = $this->conn->errno;
        //write_log($query, 'info');
        if ($this->conn->errno != 0) {
            write_log($this->conn->errno . ' - Error al ejecutar la query - ' . $this->conn->error . ' Query ' . $query, 'Error');
        }
        return $result;
    }

    public function get_result($query) {
        $row = NULL;
        $result = $this->conn->query($query);
        //write_log($query, 'info');
        if ($this->conn->errno != 0) {
            $row = $this->conn->errno;
            write_log($this->conn->errno . ' - Error al ejecutar la query - ' . $this->conn->error . ' Query ' . $query, 'Error');
        } else {
            $row = mysqli_fetch_array($result);
        }
        return $row;
    }

    public function get_results($query) {
        $rows = NULL;
        $result = $this->conn->query($query);
        //write_log($query, 'info');
        if ($this->conn->errno != 0) {
            $rows = $this->conn->errno;
            write_log($this->conn->errno . ' - Error al ejecutar la query - ' . $this->conn->error . ' Query ' . $query, 'Error');
        } else {
            while ($rows[] = mysqli_fetch_array($result));
            array_pop($rows);
        }
        return $rows;
    }

}
