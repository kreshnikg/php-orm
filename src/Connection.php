<?php

class Connection {
    /**
     * @var string
     */
    private $server = "localhost";

    /**
     * @var string
     */
    private $user = "root";

    /**
     * @var string
     */
    private $password = "";

    /**
     * @var string
     */
    private $db = "orari";

    /**
     * @var \mysqli
     */
    private $connection;

    /**
     * @return \mysqli
     */
    public function open(){
        $this->connection = new \mysqli($this->server,$this->user,$this->password,$this->db);
        if($this->connection->connect_error)
        {
            die("Problem ne konektim " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8");
        return $this->connection;
    }

    /**
     * @return void
     */
    public function close(){
        if(method_exists($this->connection, 'close'))
            $this->connection->close();
        else
            die('connection->close() : Connection is not open');
    }
}
