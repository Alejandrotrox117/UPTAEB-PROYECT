<?php
class Conexion {
    private $ip = "localhost";
    private $bd = "bd_pda";
    private $user = "root";
    private $password = "";
    private $conect;

    //setters
    public function set_conect($conect) {
        $this->conect = $conect;
    }


    //getters
    public function get_conect() {
        return $this->conect;
    }

    
// CREACION DE FUNCIONCION CONSTRUCTOR
public function __construct() {
   
    $connectionStringGeneral = "mysql:host=".$this->ip.";dbname=".$this->bd;
    try {
        $conectGeneral = new PDO($connectionStringGeneral, $this->user, $this->password);
        // Configurar las conexiones para que lancen excepciones en caso de error
        $conectGeneral->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->set_conect($conectGeneral);
    } catch(PDOException $e) {
        // Mostrar el error en el log
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        // Puedes mostrar un mensaje de error al usuario
        echo "Error de conexión a la base de datos.";
        // O lanzar una excepción personalizada
        throw new Exception("Error de conexión a la base de datos.");
    } 
   

}



public function connectGeneral() {
    return $this->get_conect();
}

// Crear una instancia de la clase Conexion y realizar operaciones
public function initializeConnection() {
    $conexion = new Conexion();

    // Obtener las conexiones
    $conectGeneral = $conexion->connectGeneral();

    // Realizar consultas y operaciones en las bases de datos...

    // Cerrar las conexiones
    $conectSeguridad = null;
    $conectGeneral = null;
}

    // public function registrar_bitacora($accion, $modulo, $id) {
    //     $sql = "INSERT INTO bitacora (fecha, accion, modulo, id_usuario) 
    //     VALUES(CURDATE(), :accion, :modulo, :id_usuario)";
    //     $stmt = $this->conecta()->prepare($sql);
    //     $stmt->execute(array(
    //         ":accion" => $accion,
    //         ":modulo" => $modulo,
    //         ":id_usuario" => $id
    //     ));
    // }
}
?>