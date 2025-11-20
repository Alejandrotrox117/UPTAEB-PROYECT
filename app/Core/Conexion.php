<?php
namespace App\Core;

use PDO;
use PDOException;

class Conexion
{
    private $servidor;
    private $username;
    private $password;
    private $databaseGeneral;
    private $databaseSeguridad;
    private $conectSeguridad;
    private $conectGeneral;

    public function __construct(
        $servidor = null,
        $username = null,
        $password = null,
        $databaseGeneral = null,
        $databaseSeguridad = null
    ) {
        $this->servidor = $servidor ?? \DB_HOST;
        $this->username = $username ?? \DB_USERNAME;
        $this->password = $password ?? \DB_PASSWORD;
        $this->databaseGeneral = $databaseGeneral ?? \DB_NAME_GENERAL;
        $this->databaseSeguridad = $databaseSeguridad ?? \DB_NAME_SEGURIDAD;
    }

    // Getters
    public function get_conectSeguridad() {
        return $this->conectSeguridad;
    }
    public function get_conectGeneral() {
        return $this->conectGeneral;
    }
    public function getServidor() {
        return $this->servidor;
    }
    public function getUsername() {
        return $this->username;
    }
    public function getPassword() {
        return $this->password;
    }
    public function getDatabaseGeneral() {
        return $this->databaseGeneral;
    }
    public function getDatabaseSeguridad() {
        return $this->databaseSeguridad;
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
    public function setDatabaseGeneral($database) {
        $this->databaseGeneral = $database;
    }
    public function setDatabaseSeguridad($database) {
        $this->databaseSeguridad = $database;
    }
    public function set_conectSeguridad($conectSeguridad) {
        $this->conectSeguridad = $conectSeguridad;
    }
    public function set_conectGeneral($conectGeneral) {
        $this->conectGeneral = $conectGeneral;
    }

    // Conexión a ambas bases de datos
    public function connect() {
        try {
            // Conexión a la base de datos de seguridad
            $connectionStringSeguridad = "mysql:host={$this->servidor};dbname={$this->databaseSeguridad};charset=utf8";
            $conectSeguridad = new PDO(
                $connectionStringSeguridad,
                $this->username,
                $this->password
            );
            $conectSeguridad->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Conexión a la base de datos general
            $connectionStringGeneral = "mysql:host={$this->servidor};dbname={$this->databaseGeneral};charset=utf8";
            $conectGeneral = new PDO(
                $connectionStringGeneral,
                $this->username,
                $this->password
            );
            $conectGeneral->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Guardar las conexiones
            $this->set_conectSeguridad($conectSeguridad);
            $this->set_conectGeneral($conectGeneral);

        } catch(PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            echo "Error de conexión a la base de datos.";
            throw new Exception("Error de conexión a la base de datos.");
        }
    }

    public function disconnect() {
        $this->conectSeguridad = null;
        $this->conectGeneral = null;
    }
}
?>