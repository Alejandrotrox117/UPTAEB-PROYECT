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
 * @param string $tabla Nombre de la tabla/módulo afectado
 * @param string $accion Acción realizada (INSERTAR, ACTUALIZAR, ELIMINAR, ACCESO_MODULO, etc.)
 * @param int $idusuario ID del usuario que realiza la acción
 * @param string|null $fecha Fecha específica (opcional, usa fecha actual por defecto)
 * @return int|false ID del registro insertado o false si falló
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
    // Validar que los parámetros no estén vacíos
    if (empty($tabla) || empty($accion)) {
        error_log("BitacoraModel: Error - Tabla o acción vacías");
        return false;
    }
    
    // Validar que el ID de usuario sea numérico y mayor a 0
    if (!is_numeric($idusuario) || $idusuario <= 0) {
        error_log("BitacoraModel: Error - ID de usuario inválido: " . $idusuario);
        return false;
    }
    
    // Validar longitud de los campos
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

private function prepararDatos($tabla, $accion, $idusuario, $fecha)
{
    return [
        'tabla' => trim(strtolower($tabla)),        // Limpiar y convertir a minúsculas
        'accion' => trim(strtoupper($accion)),      // Limpiar y convertir a mayúsculas
        'idusuario' => (int)$idusuario,             // Asegurar que sea entero
        'fecha' => $fecha ?? date('Y-m-d H:i:s')    // Usar fecha actual si no se proporciona
    ];
}

/**
 * MÉTODO PRIVADO - Ejecuta la inserción en la base de datos
 * @param array $datos Array con los datos a insertar
 * @return int|false ID del registro insertado o false si falló
 */
private function ejecutarInsercion($datos)
{
    $sql = "INSERT INTO bitacora (tabla, accion, idusuario, fecha) VALUES (:tabla, :accion, :idusuario, :fecha)";

    try {
        // Preparar la consulta
        $stmt = $this->dbSeguridad->prepare($sql);
        
        // Ejecutar con los datos
        $resultado = $stmt->execute($datos);
        
        if ($resultado) {
            $idInsertado = $this->dbSeguridad->lastInsertId();
            
            // Log de éxito (opcional, para debugging)
            error_log("BitacoraModel: Registro exitoso - ID: {$idInsertado}, Tabla: {$datos['tabla']}, Acción: {$datos['accion']}, Usuario: {$datos['idusuario']}");
            
            return $idInsertado;
        } else {
            error_log("BitacoraModel: Error - No se pudo ejecutar la inserción");
            return false;
        }
        
    } catch (PDOException $e) {
        // Log detallado del error
        error_log("BitacoraModel: Error al insertar en bitácora - " . $e->getMessage());
        error_log("BitacoraModel: Datos que causaron el error - " . json_encode($datos));
        return false;
    } catch (Exception $e) {
        // Capturar cualquier otro tipo de error
        error_log("BitacoraModel: Error inesperado al insertar en bitácora - " . $e->getMessage());
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
