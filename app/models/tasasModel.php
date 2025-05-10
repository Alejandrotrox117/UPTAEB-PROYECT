<?php
require_once("app/core/conexion.php");

Class TasasModel{
    private $id;
    private $codigoMoneda;
    private $tasa;
    private $fechaBcv;
    private $fechaCaptura;
    private $fechaOperacion;
    private $db;
    
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
    public function setFechaOperacion($fechaOperacion){
        $this->fechaOperacion = $fechaOperacion;
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
    public function getFechaOperacion(){
        return $this->fechaOperacion;
    }
    

    public function __construct()
    {
        $this->db = (new Conexion())->connect();
    }

    // Método destructor: se llama automáticamente cuando el objeto es destruido
    public function __destruct()
    {
        if ($this->db) {
            $this->db = (new Conexion())->disconnect(); // Para cerrar la conexion
        }
    }

    public function guardarTasa(string $codigoMoneda, float $tasa, string $fechaBcv)
    {
        // Verificar si la conexión está activa (opcional, pero buena práctica si hay posibilidad de que se cierre antes)
        if (!$this->db) {
            error_log("Error: No hay conexión a la base de datos en guardarTasa.");
            return false;
        }
        
        $stmtCheck = $this->db->prepare("SELECT id FROM historial_tasas_bcv WHERE codigo_moneda = :codigo_moneda AND fecha_publicacion_bcv = :fecha_bcv");
        $stmtCheck->execute([':codigo_moneda' => $codigoMoneda, ':fecha_bcv' => $fechaBcv]);
        if ($stmtCheck->fetch()) {
            error_log("La tasa para {$codigoMoneda} en fecha {$fechaBcv} ya existe.");
            return true; 
        }

        $sql = "INSERT INTO historial_tasas_bcv (codigo_moneda, tasa_a_bs, fecha_publicacion_bcv)
                VALUES (:codigo_moneda, :tasa_a_ves, :fecha_publicacion_bcv)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':codigo_moneda' => $codigoMoneda,
                ':tasa_a_bs' => $tasa,
                ':fecha_publicacion_bcv' => $fechaBcv
            ]);
        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al guardar tasa - " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTasasPorMoneda(string $codigoMoneda, int $limite = 10)
    {
        if (!$this->db) {
            error_log("Error: No hay conexión a la base de datos en obtenerTasasPorMoneda.");
            return []; // Devolver un array vacío si no hay conexión
        }

        $sql = "SELECT codigo_moneda, tasa_a_ves, fecha_publicacion_bcv, fecha_captura
                FROM historial_tasas_bcv
                WHERE codigo_moneda = :codigo_moneda
                ORDER BY fecha_publicacion_bcv DESC, fecha_captura DESC
                LIMIT :limite";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':codigo_moneda', $codigoMoneda, PDO::PARAM_STR);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al obtener tasas - " . $e->getMessage());
            return []; // Devolver un array vacío en caso de error
        }
    }
}


?>