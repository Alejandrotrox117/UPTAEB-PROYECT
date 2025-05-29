<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class monedaModel extends Mysql
{
    private $db;
    private $conexion;

    private $idmoneda;
    private $nombre;
    private $valor;
    private $estatus;
  public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
        // Obtener el ID del usuario desde la sesión
        $idUsuario = $this->obtenerIdUsuarioSesion();

        if ($idUsuario) {
            // Establecer la variable de sesión SQL
            $this->setUsuarioActual($idUsuario);
        }
    }
    private function obtenerIdUsuarioSesion(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    // Método para establecer @usuario_actual en MySQL
    private function setUsuarioActual(int $idUsuario)
    {
        $sql = "SET @usuario_actual = $idUsuario";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("No se pudo establecer @usuario_actual: " . $e->getMessage());
        }
    }


    // Métodos Getters y Setters
    public function getIdmoneda() {
        return $this->idmoneda;
    }

    public function setIdmoneda($idmoneda) {
        $this->idmoneda = $idmoneda;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getValor() {
        return $this->valor;
    }

    public function setValor($valor) {
        $this->valor = $valor;
    }
    
    public function getEstatus() {
        return $this->estatus;
    }

    public function setEstatus($estatus) {
        $this->estatus = $estatus;
    }
    // Obtener todas las categorías activas
    public function SelectAllMoneda()
    {
        $sql = "SELECT * FROM monedas WHERE estatus = 'activo'";
        return $this->searchAll($sql);
    }

    public function insertMoneda($data)
    {
        $sql = "INSERT INTO monedas (
                    nombre_moneda, valor, estatus
                ) VALUES (?, ?, ?)";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre'],
            $data['valor'],
            $data['estatus']
        ];
    
        return $stmt->execute($arrValues);
    }
 public function getMonedas()
    {
        // Asumiendo que tu tabla monedas tiene un campo 'estatus'
        $sql = "SELECT idmoneda, codigo_moneda, valor FROM monedas WHERE estado = 'activo'";
        // Si no tiene estatus, simplemente:
        // $sql = "SELECT idmoneda, nombre_moneda, simbolo, codigo_iso FROM monedas";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ComprasModel::getMonedasActivas - Error de BD: " . $e->getMessage());
            return [];
        }
    }

    public function getIdMonedaByCodigo($codigoMoneda)
    {
        $sql = "SELECT idmoneda FROM monedas WHERE codigo_moneda = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigoMoneda]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

    // Método para eliminar lógicamente un categoria
    public function deleteMoneda($idmoneda) {
        $sql = "UPDATE monedas SET estatus = 'INACTIVO' WHERE idmoneda = ?";
        $stmt = $this->db->prepare($sql); 
        return $stmt->execute([$idmoneda]); 
    }

    // Método para actualizar un categoria
    public function updateMoneda($data)
    {
        $sql = "UPDATE monedas SET 
                    nombre_moneda = ?, 
                    valor = ?, 
                    estatus = ? 
                WHERE idmoneda = ?";
    
        $stmt = $this->db->prepare($sql);
        $arrValues = [
            $data['nombre_moneda'],
            $data['valor'],
            $data['estatus'],
            $data['idmoneda']
        ];
    
        return $stmt->execute($arrValues);
    }

    // Método para obtener un categoria por ID
    public function getMonedaById($idmoneda) {
        $sql = "SELECT * FROM monedas WHERE idmoneda = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idmoneda]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Asignar los valores a las propiedades del objeto
            $this->setIdmoneda($data['idmoneda']);
            $this->setNombre($data['nombre_moneda']);
            $this->setValor($data['valor']);
    
            $this->setEstatus($data['estatus']);
        }

        return $data; 
    }
   
}