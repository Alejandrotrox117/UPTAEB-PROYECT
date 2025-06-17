<?php

require_once("app/core/conexion.php");

class BitacoraModel
{
    private $dbSeguridad;
    private $conexion;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    /**
     * ✅ CORREGIR: Usar fecha_accion en lugar de fecha
     */
    public function SelectAllBitacora()
    {
        $sql = "SELECT 
            b.idbitacora,
            b.tabla,
            b.accion,
            b.idusuario,
            u.usuario AS nombre_usuario,
            b.fecha_accion as fecha
        FROM bitacora b
        LEFT JOIN usuario u ON b.idusuario = u.idusuario
        ORDER BY b.fecha_accion DESC";
    
        try {
            $stmt = $this->dbSeguridad->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BitacoraModel: Error al seleccionar registros de bitácora - " . $e->getMessage());
            return [];
        }
    }

    /**
     * ✅ CORREGIR: Usar fecha_accion
     */
    public function obtenerRegistroPorId($idbitacora)
    {
        $sql = "SELECT 
                    b.idbitacora,
                    b.tabla,
                    b.accion,
                    b.idusuario,
                    u.usuario as nombre_usuario,
                    b.fecha_accion as fecha
                FROM bitacora b
                LEFT JOIN usuario u ON b.idusuario = u.idusuario
                WHERE b.idbitacora = ?";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idbitacora]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BitacoraModel: Error al obtener registro por ID - " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ CORREGIR: Usar fecha_accion
     */
    public function obtenerHistorial($filtros = [])
    {
        $sql = "SELECT 
                    b.idbitacora,
                    b.tabla,
                    b.accion,
                    b.idusuario,
                    u.usuario AS nombre_usuario,
                    b.fecha_accion as fecha
                FROM bitacora b
                LEFT JOIN usuario u ON b.idusuario = u.idusuario";
private function construirYEjecutarConsulta($filtros)
{
    $sql = "SELECT 
            b.idbitacora,
            b.tabla,
            b.accion,
            b.idusuario,
            CONCAT(p.nombre, ' ', p.apellido) AS nombre_usuario,
            b.fecha
        FROM bitacora b
        LEFT JOIN usuario u ON b.idusuario = u.idusuario
        LEFT JOIN recuperadora.personas p ON u.idpersona = p.idpersona";

        $parametros = [];
        $whereClause = $this->construirFiltros($filtros, $parametros);
        
        if ($whereClause) {
            $sql .= " WHERE " . $whereClause;
        }

        $sql .= " ORDER BY b.fecha_accion DESC";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($parametros);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("✅ BitacoraModel: Consulta exitosa - " . count($resultado) . " registros encontrados");
            return $resultado;
        } catch (PDOException $e) {
            error_log("❌ BitacoraModel: Error en consulta - " . $e->getMessage());
            error_log("❌ SQL: " . $sql);
            error_log("❌ Parámetros: " . json_encode($parametros));
            return [];
        }
    }

    /**
     * ✅ CORREGIR: Usar fecha_accion para filtros
     */
    private function construirFiltros($filtros, &$parametros)
    {
        $condiciones = [];
        $parametros = [];

        if (!empty($filtros['tabla'])) {
            $condiciones[] = "b.tabla = :tabla";
            $parametros[':tabla'] = $filtros['tabla'];
        }
        if (!empty($filtros['modulo'])) {
            $condiciones[] = "b.tabla = :modulo";
            $parametros[':modulo'] = $filtros['modulo'];
        }
        if (!empty($filtros['idusuario'])) {
            $condiciones[] = "b.idusuario = :idusuario";
            $parametros[':idusuario'] = $filtros['idusuario'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = "b.fecha_accion >= :fecha_desde";
            $parametros[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = "b.fecha_accion <= :fecha_hasta";
            $parametros[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        return implode(' AND ', $condiciones);
    }

    /**
     * ✅ CORREGIR: Usar fecha_accion para limpieza
     */
    public function limpiarRegistrosAntiguos($dias = 30)
    {
        $sql = "DELETE FROM bitacora WHERE fecha_accion < DATE_SUB(NOW(), INTERVAL ? DAY)";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$dias]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("BitacoraModel: Error al limpiar registros antiguos - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ✅ CORREGIR: Insertar en fecha_accion
     */
    public function registrarAccion($tabla, $accion, $idusuario, $detalle = null, $idRegistro = null)
    {
        // Validación
        if (!$this->validarParametros($tabla, $accion, $idusuario)) {
            return false;
        }

        // Preparar datos
        $datos = [
            'tabla' => trim(strtolower($tabla)),
            'accion' => trim(strtoupper($accion)),
            'idusuario' => (int)$idusuario,
            'fecha_accion' => date('Y-m-d H:i:s')
        ];

        // ✅ INSERTAR EN AMBOS CAMPOS PARA COMPATIBILIDAD
        $sql = "INSERT INTO bitacora (tabla, accion, idusuario, fecha_accion, fecha) 
                VALUES (:tabla, :accion, :idusuario, :fecha_accion, :fecha_accion)";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $resultado = $stmt->execute($datos);
            
            if ($resultado) {
                $idInsertado = $this->dbSeguridad->lastInsertId();
                error_log("✅ BitacoraModel: Registro exitoso - ID: {$idInsertado}, Tabla: {$datos['tabla']}, Acción: {$datos['accion']}, Usuario: {$datos['idusuario']}");
                return $idInsertado;
            } else {
                error_log("❌ BitacoraModel: Error - No se pudo ejecutar la inserción");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("❌ BitacoraModel: Error al insertar en bitácora - " . $e->getMessage());
            error_log("❌ BitacoraModel: Datos que causaron el error - " . json_encode($datos));
            return false;
        }
    }

    /**
     * Obtiene módulos disponibles
     */
    public function obtenerModulosDisponibles()
    {
        $sql = "SELECT DISTINCT tabla AS modulo 
                FROM bitacora 
                WHERE tabla IS NOT NULL 
                ORDER BY tabla ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BitacoraModel: Error al obtener módulos disponibles - " . $e->getMessage());
            return [];
        }
    }

    // Métodos privados auxiliares
    private function validarParametros($tabla, $accion, $idusuario)
    {
        if (empty($tabla) || empty($accion)) {
            error_log("BitacoraModel: Error - Tabla o acción vacías");
            return false;
        }
        
        if (!is_numeric($idusuario) || $idusuario <= 0) {
            error_log("BitacoraModel: Error - ID de usuario inválido: " . $idusuario);
            return false;
        }
        
        if (strlen($tabla) > 50) {
            error_log("BitacoraModel: Error - Nombre de tabla muy largo: " . $tabla);
            return false;
        }
        
        if (strlen($accion) > 50) {
            error_log("BitacoraModel: Error - Nombre de acción muy largo: " . $accion);
            return false;
        }
        
        return true;
    }
}
?>
