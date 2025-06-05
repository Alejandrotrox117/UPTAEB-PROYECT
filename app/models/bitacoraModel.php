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

    public function SelectAllBitacora()
    {
        $sql = "SELECT 
            b.idbitacora,
            b.tabla,
            b.accion,
            b.idusuario,
            u.usuario AS nombre_usuario,
            b.fecha
        FROM bitacora b
    LEFT JOIN usuario u ON b.idusuario = u.idusuario
    ORDER BY b.fecha DESC;";
    
    try {
        $stmt = $this->dbSeguridad->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("BitacoraModel: Error al seleccionar registros de bitácora - " . $e->getMessage());
        return [];
    }
}

    /**
     * MÉTODO PRINCIPAL - Única forma pública de registrar en bitácora
     * Encapsula toda la lógica de validación e inserción
     */
    public function registrarAccion($tabla, $accion, $idusuario, $fecha = null)
    {
        // Validación encapsulada
        if (!$this->validarParametros($tabla, $accion, $idusuario)) {
            return false;
        }

        // Preparación de datos encapsulada
        $datos = $this->prepararDatos($tabla, $accion, $idusuario, $fecha);

        // Inserción encapsulada
        return $this->ejecutarInsercion($datos);
    }

    /**
     * MÉTODO PÚBLICO para consultas
     */
    public function obtenerHistorial($filtros = [])
    {
        return $this->construirYEjecutarConsulta($filtros);
    }

    /**
     * MÉTODOS PRIVADOS - Lógica interna encapsulada
     */
    private function validarParametros($tabla, $accion, $idusuario)
    {
        if (empty($tabla) || empty($accion) || !is_numeric($idusuario) || $idusuario <= 0) {
            error_log("BitacoraModel: Parámetros inválidos");
            return false;
        }
        return true;
    }

    private function prepararDatos($tabla, $accion, $idusuario, $fecha)
    {
        return [
            'tabla' => trim($tabla),
            'accion' => trim($accion),
            'idusuario' => (int)$idusuario,
            'fecha' => $fecha ?? date('Y-m-d H:i:s')
        ];
    }

    private function ejecutarInsercion($datos)
    {
        $sql = "INSERT INTO bitacora (tabla, accion, idusuario, fecha) VALUES (:tabla, :accion, :idusuario, :fecha)";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($datos);
            return $this->dbSeguridad->lastInsertId();
        } catch (PDOException $e) {
            error_log("BitacoraModel: Error al insertar - " . $e->getMessage());
            return false;
        }
    }

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
        LEFT JOIN bd_pda.personas p ON u.idpersona = p.idpersona";

        // Agregar filtros si existen
        $whereClause = $this->construirFiltros($filtros);
        if ($whereClause) {
            $sql .= " WHERE " . $whereClause;
        }

        $sql .= " ORDER BY b.fecha DESC";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($filtros);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BitacoraModel: Error en consulta - " . $e->getMessage());
            return [];
        }
    }

    private function construirFiltros($filtros)
    {
        $condiciones = [];

        if (!empty($filtros['tabla'])) {
            $condiciones[] = "b.tabla = :tabla";
        }
        if (!empty($filtros['idusuario'])) {
            $condiciones[] = "b.idusuario = :idusuario";
        }
        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = "b.fecha >= :fecha_desde";
        }
        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = "b.fecha <= :fecha_hasta";
        }

        return implode(' AND ', $condiciones);
    }
}
