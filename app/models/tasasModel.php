<?php
require_once("app/core/conexion.php"); 

class TasasModel {
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
        $this->conexionObjeto = new Conexion();
        $this->db = $this->conexionObjeto->connect();
    }

    public function __destruct()
    {
        if ($this->conexionObjeto) {
            $this->conexionObjeto->disconnect();
        }
    }
    public function guardarTasa(string $codigoMoneda, float $tasa, string $fechaBcv): string|bool
    {
        if (!$this->db) {
            error_log("TasasModel: No hay conexión a la base de datos en guardarTasa.");
            return false;
        }

        try {
            $stmtCheck = $this->db->prepare("SELECT id FROM historial_tasas_bcv WHERE codigo_moneda = :codigo_moneda AND fecha_publicacion_bcv = :fecha_bcv");
            $stmtCheck->execute([':codigo_moneda' => $codigoMoneda, ':fecha_bcv' => $fechaBcv]);

            if ($stmtCheck->fetch()) {
                return 'duplicado';
            }

            $sql = "INSERT INTO historial_tasas_bcv (codigo_moneda, tasa_a_bs, fecha_publicacion_bcv, fecha_creacion)
                    VALUES (:codigo_moneda, :tasa_valor, :fecha_publicacion_bcv, :fecha_creacion_actual)";
            
            $stmt = $this->db->prepare($sql);
            $fechaActual = date('Y-m-d H:i:s');

            $exito = $stmt->execute([
                ':codigo_moneda' => $codigoMoneda,
                ':tasa_valor' => $tasa,
                ':fecha_publicacion_bcv' => $fechaBcv,
                ':fecha_creacion_actual' => $fechaActual
            ]);

            return $exito ? 'insertado' : false;

        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al guardar tasa para {$codigoMoneda} - " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTasasPorMoneda(string $codigoMoneda, int $limite = 10): array
    {
        if (!$this->db) {
            error_log("TasasModel: No hay conexión a la base de datos en obtenerTasasPorMoneda.");
            return [];
        }

        $sql = "SELECT codigo_moneda, tasa_a_bs AS tasa_a_ves, fecha_publicacion_bcv, fecha_creacion AS fecha_captura
                FROM historial_tasas_bcv
                WHERE codigo_moneda = :codigo_moneda
                ORDER BY fecha_publicacion_bcv DESC, fecha_creacion DESC
                LIMIT :limite";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':codigo_moneda', $codigoMoneda, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al obtener tasas para {$codigoMoneda} - " . $e->getMessage());
            return [];
        }
    }

    public function SelectAllTasas(): array
    {
        if (!$this->db) {
            error_log("TasasModel: No hay conexión a la base de datos en SelectAllTasas.");
            return [];
        }
        $sql = "SELECT id, codigo_moneda, tasa_a_bs AS tasa_a_ves, fecha_publicacion_bcv, fecha_creacion AS fecha_captura FROM historial_tasas_bcv ORDER BY fecha_publicacion_bcv DESC, fecha_creacion DESC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al seleccionar todas las tasas - " . $e->getMessage());
            return [];
        }
    }
}
?>
