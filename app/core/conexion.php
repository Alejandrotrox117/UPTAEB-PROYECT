<?php
class Conexion 
{
    private $servidor;
    private $username;
    private $password;
    private $database;
    private $conn; // ← Guardamos la conexión como propiedad de la clase

    public function __construct()
    {
        $this->servidor = 'localhost';
        $this->username = 'root';
        $this->password = '';
        $this->database = 'project';
    }

    public function connect()
    {
        $this->conn = new mysqli($this->servidor, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            die('Error de Conexión (' . $this->conn->connect_errno . ') ' . $this->conn->connect_error);
        }

        return $this->conn;
    }

    public function disconnect()
    {
        if ($this->conn) {
            $this->conn->close(); // ← Cierra la conexión
        }
    }
}
?>