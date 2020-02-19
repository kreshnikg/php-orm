<?php

namespace Database;

class Connection {
    private $server = "localhost";
    private $user = "root";
    private $password = "";
    private $db = "phptest";
    private $connection;

    public function open(){
        $this->connection = new \mysqli($this->server,$this->user,$this->password,$this->db);
        if($this->connection->connect_error)
        {
            die("Problem ne konektim " . $this->connection->connect_error);
        }
        return $this->connection;
    }

    public function close(){
        if(method_exists($this->connection, 'close'))
            $this->connection->close();
        else
            die('connection->close() : Connection is not open');
    }
}
