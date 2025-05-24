<?php
require_once("app/core/conexion.php"); 
require_once("app/core/mysql.php");


class TasasModel extends Mysql{
    private $db;
    private $conexionObjeto;
    private $id;
    private $codigoMoneda;
    private $fechaCaptura;
    private $tasa;
    private $fechaBcv;

    //SETTERS
    public function setId($id){ 
        $this->id = $id; 
    }
    public function setCodigoMoneda($codigoMoneda){
        $this->codigoMoneda = $codigoMoneda; 
    }
    public function setTasa($tasa){
        $this->tasa = $tasa; 
    }
    public function setFechaBcv($fechaBcv){
        $this->fechaBcv = $fechaBcv; 
    }
    public function setFechaCaptura($fechaCaptura){
        $this->fechaCaptura = $fechaCaptura;
    }
    //GETTERS
    public function getId(){
        return $this->id;
    }
    public function getCodigoMoneda(){
        return $this->codigoMoneda;
    }
    public function getTasa(){
        return $this->tasa;
    }
    public function getFechaBcv(){
        return $this->fechaBcv;
    }
    public function getFechaCaptura(){
        return $this->fechaCaptura;
    }


    public function __construct()
    {
        parent::__construct();
        $this->conexionObjeto = new Conexion();
        $this->conexionObjeto->connect(); // Asegúrate de conectar antes de obtener la conexión
        $this->db = $this->conexionObjeto->get_conectGeneral();
       

    }

    public function guardarTasa(string $codigoMoneda, float $tasa, string $fechaBcv){
        $this->setCodigoMoneda($codigoMoneda);
        $this->setTasa($tasa);
        $this->setFechaBcv($fechaBcv);
        // Validar que la tasa no sea cero
        if ($tasa == 0) {
            error_log("Tasa no puede ser cero para {$this->getCodigoMoneda()} - {$this->getFechaBcv()}");
            return false;
        }
        // Verificar si la conexión a la base de datos es válida
        if (!$this->db) {
            error_log("No hay conexión a la base de datos en guardar Tasa.");
            return false;
        }

        try {
            $stmtCheck = $this->db->prepare("SELECT id FROM historial_tasas_bcv WHERE codigo_moneda = ? AND fecha_publicacion_bcv = ?");
            $stmtCheck->execute([$this->getCodigoMoneda(), $this->getFechaBcv()]);

            if ($stmtCheck->fetch()) {
                $this->conexionObjeto->disconnect(); // Cierra la conexión
                return 'duplicado';
            }

            $sql = "INSERT INTO historial_tasas_bcv (codigo_moneda, tasa_a_bs, fecha_publicacion_bcv, fecha_creacion)
                    VALUES (?,?,?,?)";
            
            $stmt = $this->db->prepare($sql);
            date_default_timezone_set('America/Caracas');
            $fechaActual = date('Y-m-d H:i:s');

            $exito = $stmt->execute([
                $this->getCodigoMoneda(),
                $this->getTasa(),
                $this->getFechaBcv(),
                $fechaActual
            ]);

            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $exito ? 'insertado' : false;

        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al guardar tasa para {$this->getCodigoMoneda()} - " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return false;
        }
    }

    public function obtenerTasasPorMoneda(string $codigoMoneda){
        $this->setCodigoMoneda($codigoMoneda);
        if (!$this->db) {
            error_log("TasasModel: No hay conexión a la base de datos en obtenerTasasPorMoneda.");
            return [];
        }

        $sql = "SELECT codigo_moneda, tasa_a_bs AS tasa_a_ves, fecha_publicacion_bcv, fecha_creacion AS fecha_captura
                FROM historial_tasas_bcv
                WHERE codigo_moneda = ?
                ORDER BY fecha_publicacion_bcv DESC, fecha_creacion";
        $arrData = [$codigoMoneda];

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($arrData);
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al obtener tasas para {$codigoMoneda} - " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return [];
        }
    }


    public function SelectAllTasas(): array{
        if (!$this->db) {
            error_log("TasasModel: No hay conexión a la base de datos en SelectAllTasas.");
            return [];
        }
        $sql = "SELECT id, codigo_moneda, tasa_a_bs AS tasa_a_ves, fecha_publicacion_bcv, fecha_creacion AS fecha_captura FROM historial_tasas_bcv ORDER BY fecha_publicacion_bcv DESC, fecha_creacion DESC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error de BD al seleccionar todas las tasas - " . $e->getMessage());
            $this->conexionObjeto->disconnect(); // Cierra la conexión
            return [];
        }
    }
}
?>
