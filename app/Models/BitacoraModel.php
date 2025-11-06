<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;

class BitacoraModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $message;
    private $status;

    private $idbitacora;
    private $tabla;
    private $accion;
    private $idusuario;
    private $fecha_accion;
    private $detalle;
    private $idRegistro;
    private $filtros;
    private $dias;

    public function __construct()
    {
        parent::__construct();
    }

    // GETTERS Y SETTERS DE CONTROL
    public function getQuery(){
        return $this->query;
    }
    public function setQuery(string $query){
        $this->query = $query; 
    }
    public function getArray(){ 
        return $this->array ?? []; 
    }
    public function setArray(array $array){
        $this->array = $array;
    }
    public function getData(){
        return $this->data ?? []; 
    }
    public function setData(array $data){
        $this->data = $data; 
    }
    public function getResult(){
        return $this->result;
    }
    public function setResult($result){
        $this->result = $result;
    }
    public function getMessage(){
        return $this->message ?? ''; 
    }
    public function setMessage(string $message){
        $this->message = $message;
    }
    public function getStatus(){
        return $this->status ?? false;
    }
    public function setStatus(bool $status){
        $this->status = $status;
    }

    // GETTERS Y SETTERS DE ENTIDAD
    public function setIdBitacora($idbitacora){
        $this->idbitacora = $idbitacora;
    }
    public function getIdBitacora(){
        return $this->idbitacora;
    }
    public function setTabla($tabla){
        $this->tabla = $tabla;
    }
    public function getTabla(){
        return $this->tabla;
    }
    public function setAccion($accion){
        $this->accion = $accion;
    }
    public function getAccion(){
        return $this->accion;
    }
    public function setIdUsuario($idusuario){
        $this->idusuario = $idusuario;
    }
    public function getIdUsuario(){
        return $this->idusuario;
    }
    public function setFechaAccion($fecha_accion){
        $this->fecha_accion = $fecha_accion;
    }
    public function getFechaAccion(){
        return $this->fecha_accion;
    }
    public function setDetalle($detalle){
        $this->detalle = $detalle;
    }
    public function getDetalle(){
        return $this->detalle;
    }
    public function setIdRegistro($idRegistro){
        $this->idRegistro = $idRegistro;
    }
    public function getIdRegistro(){
        return $this->idRegistro;
    }
    public function setFiltros($filtros){
        $this->filtros = $filtros;
    }
    public function getFiltros(){
        return $this->filtros;
    }
    public function setDias($dias){
        $this->dias = $dias;
    }
    public function getDias(){
        return $this->dias;
    }

    // MÉTODOS PÚBLICOS
    public function SelectAllBitacora()
    {
        return $this->ejecutarConsultaTodaBitacora();
    }

    public function obtenerRegistroPorId($idbitacora)
    {
        $this->setIdBitacora($idbitacora);
        return $this->ejecutarConsultaBitacoraPorId();
    }

    public function obtenerHistorial($filtros = [])
    {
        $this->setFiltros($filtros);
        return $this->ejecutarConsultaHistorialConFiltros();
    }

    public function limpiarRegistrosAntiguos($dias)
    {
        $this->setDias($dias);
        return $this->ejecutarLimpiezaRegistrosAntiguos();
    }

    public function registrarAccion($tabla, $accion, $idusuario, $detalle = null, $idRegistro = null)
    {
        $this->setTabla($tabla);
        $this->setAccion($accion);
        $this->setIdUsuario($idusuario);
        $this->setDetalle($detalle);
        $this->setIdRegistro($idRegistro);
        return $this->ejecutarRegistroAccion();
    }

    public function obtenerModulosDisponibles()
    {
        return $this->ejecutarConsultaModulosDisponibles();
    }

    // MÉTODOS PRIVADOS
    private function ejecutarConsultaTodaBitacora()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT 
                b.idbitacora,
                b.tabla,
                b.accion,
                b.idusuario,
                u.usuario AS nombre_usuario,
                b.fecha_accion as fecha
            FROM bitacora b
            LEFT JOIN usuario u ON b.idusuario = u.idusuario
            ORDER BY b.fecha_accion DESC");

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (PDOException $e) {
            error_log("BitacoraModel::ejecutarConsultaTodaBitacora - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaBitacoraPorId()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT 
                        b.idbitacora,
                        b.tabla,
                        b.accion,
                        b.idusuario,
                        u.usuario as nombre_usuario,
                        b.fecha_accion as fecha
                    FROM bitacora b
                    LEFT JOIN usuario u ON b.idusuario = u.idusuario
                    WHERE b.idbitacora = ?");

            $this->setArray([$this->getIdBitacora()]);

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (PDOException $e) {
            error_log("BitacoraModel::ejecutarConsultaBitacoraPorId - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaHistorialConFiltros()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT 
                        b.idbitacora,
                        b.tabla,
                        b.accion,
                        b.idusuario,
                        u.usuario AS nombre_usuario,
                        b.fecha_accion as fecha
                    FROM bitacora b
                    LEFT JOIN usuario u ON b.idusuario = u.idusuario");

            $array = [];
            $whereClause = $this->ejecutarConstruccionFiltros($array);
            
            if ($whereClause) {
                $this->setQuery($this->getQuery() . " WHERE " . $whereClause);
            }

            $this->setQuery($this->getQuery() . " ORDER BY b.fecha_accion DESC");
            $this->setArray($array);

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            error_log("BitacoraModel::ejecutarConsultaHistorialConFiltros - Consulta exitosa: " . count($this->getResult()) . " registros encontrados");
            return $this->getResult();

        } catch (PDOException $e) {
            error_log("BitacoraModel::ejecutarConsultaHistorialConFiltros - Error: " . $e->getMessage());
            error_log("SQL: " . $this->getQuery());
            error_log("Array: " . json_encode($this->getArray()));
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConstruccionFiltros(&$array)
    {
        $condiciones = [];
        $array = [];
        $filtros = $this->getFiltros();

        if (!empty($filtros['tabla'])) {
            $condiciones[] = "b.tabla = ?";
            $array[] = $filtros['tabla'];
        }
        if (!empty($filtros['modulo'])) {
            $condiciones[] = "b.tabla = ?";
            $array[] = $filtros['modulo'];
        }
        if (!empty($filtros['idusuario'])) {
            $condiciones[] = "b.idusuario = ?";
            $array[] = $filtros['idusuario'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = "b.fecha_accion >= ?";
            $array[] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = "b.fecha_accion <= ?";
            $array[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        return implode(' AND ', $condiciones);
    }

    private function ejecutarLimpiezaRegistrosAntiguos()
    {
        if (!is_numeric($this->getDias()) || $this->getDias() <= 0) {
            error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - Error: Días inválido: " . $this->getDias());
            return 0;
        }

        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM bitacora WHERE fecha_accion >= DATE_SUB(NOW(), INTERVAL ? DAY)");
            $this->setArray([$this->getDias()]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $conteoAEliminar = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - Registros de los últimos " . $this->getDias() . " días a eliminar: " . $conteoAEliminar);
            
            if ($conteoAEliminar == 0) {
                error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - No hay registros de los últimos " . $this->getDias() . " días para eliminar");
                return 0;
            }
            
            $this->setQuery("DELETE FROM bitacora WHERE fecha_accion >= DATE_SUB(NOW(), INTERVAL ? DAY)");
            $this->setArray([$this->getDias()]);

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $resultado = $stmt->execute($this->getArray());
            
            if (!$resultado) {
                error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - Error: La consulta DELETE falló");
                return 0;
            }
            
            $registrosEliminados = $stmt->rowCount();
            
            error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - Eliminación exitosa: " . $registrosEliminados . " registros eliminados de los últimos " . $this->getDias() . " días");
            
            return $registrosEliminados;

        } catch (PDOException $e) {
            error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - Error: " . $e->getMessage());
            error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - SQL: " . $this->getQuery());
            error_log("BitacoraModel::ejecutarLimpiezaRegistrosAntiguos - Array: " . json_encode($this->getArray()));
            return 0;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarRegistroAccion()
    {
        if (!$this->ejecutarValidacionParametros()) {
            return false;
        }

        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $datos = [
                'tabla' => trim(strtolower($this->getTabla())),
                'accion' => trim(strtoupper($this->getAccion())),
                'idusuario' => (int)$this->getIdUsuario(),
                'fecha_accion' => date('Y-m-d H:i:s')
            ];

            $this->setQuery("INSERT INTO bitacora (tabla, accion, idusuario, fecha_accion, fecha) 
                    VALUES (?, ?, ?, ?, ?)");
            $this->setArray([
                $datos['tabla'],
                $datos['accion'],
                $datos['idusuario'],
                $datos['fecha_accion'],
                $datos['fecha_accion']
            ]);

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $resultado = $stmt->execute($this->getArray());
            
            if ($resultado) {
                $idInsertado = $dbSeguridad->lastInsertId();
                error_log("BitacoraModel::ejecutarRegistroAccion - Registro exitoso - ID: {$idInsertado}, Tabla: {$datos['tabla']}, Acción: {$datos['accion']}, Usuario: {$datos['idusuario']}");
                return $idInsertado;
            } else {
                error_log("BitacoraModel::ejecutarRegistroAccion - Error: No se pudo ejecutar la inserción");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("BitacoraModel::ejecutarRegistroAccion - Error: " . $e->getMessage());
            error_log("BitacoraModel::ejecutarRegistroAccion - Datos que causaron el error: " . json_encode($this->getArray()));
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaModulosDisponibles()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT DISTINCT tabla AS modulo 
                    FROM bitacora 
                    WHERE tabla IS NOT NULL 
                    ORDER BY tabla ASC");

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return $this->getResult();

        } catch (PDOException $e) {
            error_log("BitacoraModel::ejecutarConsultaModulosDisponibles - Error: " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarValidacionParametros()
    {
        if (empty($this->getTabla()) || empty($this->getAccion())) {
            error_log("BitacoraModel::ejecutarValidacionParametros - Error: Tabla o acción vacías");
            return false;
        }
        
        if (!is_numeric($this->getIdUsuario()) || $this->getIdUsuario() <= 0) {
            error_log("BitacoraModel::ejecutarValidacionParametros - Error: ID de usuario inválido: " . $this->getIdUsuario());
            return false;
        }
        
        if (strlen($this->getTabla()) > 50) {
            error_log("BitacoraModel::ejecutarValidacionParametros - Error: Nombre de tabla muy largo: " . $this->getTabla());
            return false;
        }
        
        if (strlen($this->getAccion()) > 50) {
            error_log("BitacoraModel::ejecutarValidacionParametros - Error: Nombre de acción muy largo: " . $this->getAccion());
            return false;
        }
        
        return true;
    }
}
?>