<?php
class datos {
    private $ip = "localhost";
    private $bd = "bd_pda";
    private $user = "root";
    private $password = "";

    public function conecta() {
        try {
            $pdo = new PDO("mysql:host=".$this->ip.";dbname=".$this->bd, $this->user, $this->password);
            $pdo->exec("set names utf8");
            return $pdo;
        } catch (PDOException $e) {
            echo "Error en la conexión: " . $e->getMessage();
            return null;
        }
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