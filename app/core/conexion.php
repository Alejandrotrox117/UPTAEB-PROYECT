<?php
class Conexion
{
    private $servidor;
    private $username;
    private $password;
    private $database;
    private $conn;

    public function __construct($servidor = 'localhost', $username = 'root', $password = '', $database = 'project')
    {
        $this->servidor = $servidor;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    // Getters
    public function getServidor() {
        return $this->servidor;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getDatabase() {
        return $this->database;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Setters
    public function setServidor($servidor) {
        $this->servidor = $servidor;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function setDatabase($database) {
        $this->database = $database;
    }

    // Método para conectar con PDO
    public function connect() {
        try {
            $dsn = "mysql:host={$this->servidor};dbname={$this->database};charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function disconnect() {
        $this->conn = null;
    }
}
?>
