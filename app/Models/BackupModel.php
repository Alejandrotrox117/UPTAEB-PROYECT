<?php
namespace App\Models;

use App\Core\Conexion;
use App\Helpers\BitacoraHelper;
use PDO;
use PDOException;
use Exception;

class BackupModel {

    private $dbSeguridad;
    private $dbGeneral;
    private $conexion;
    private $bitacoraModel;
    private $BitacoraHelper;
    private $query;
    private $array;
    private $result;
    private $status;
    private $message;
    private $directorioBackups;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
        $this->dbGeneral = $this->conexion->get_conectGeneral();
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();
        $this->directorioBackups = $this->obtenerRutaBackups();
    }

    public function getDbSeguridad() {
        return $this->dbSeguridad;
    }

    public function getDbGeneral() {
        return $this->dbGeneral;
    }

    public function getQuery() {
        return $this->query;
    }

    public function setQuery(string $query) {
        $this->query = $query;
    }

    public function getArray() {
        return $this->array ?? [];
    }

    public function setArray(array $array) {
        $this->array = $array;
    }

    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        $this->result = $result;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus(bool $status) {
        $this->status = $status;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage(string $message) {
        $this->message = $message;
    }

    public function crearBackupCompleto()
    {
        return $this->ejecutarBackupCompleto();
    }

    public function crearBackupTabla(string $tabla)
    {
        return $this->ejecutarBackupTabla($tabla);
    }

    public function obtenerListaBackups()
    {
        return $this->ejecutarBusquedaBackups();
    }

    public function eliminarBackup(string $nombreArchivo)
    {
        return $this->ejecutarEliminacionBackup($nombreArchivo);
    }

    public function restaurarBackup(string $nombreArchivo)
    {
        return $this->ejecutarRestauracionBackup($nombreArchivo);
    }

    /**
     * Obtiene estadísticas generales de los backups
     */

    public function obtenerEstadisticasBackups()
    {
        try {
            $directorio = $this->directorioBackups;
            
            if (!is_dir($directorio)) {
                return [
                    'status' => 'success',
                    'estadisticas' => [
                        'total' => 0,
                        'ultimo' => 'N/A',
                        'espacio' => '0 KB'
                    ]
                ];
            }

            $archivos = glob($directorio . '/*.sql');
            $totalArchivos = count($archivos);
            $espacioTotal = 0;
            $ultimoArchivo = 'N/A';

            if ($totalArchivos > 0) {
                // Calcular espacio total
                foreach ($archivos as $archivo) {
                    if (file_exists($archivo)) {
                        $espacioTotal += filesize($archivo);
                    }
                }

                // Encontrar el archivo más reciente
                $archivosConFecha = [];
                foreach ($archivos as $archivo) {
                    $archivosConFecha[$archivo] = filemtime($archivo);
                }
                arsort($archivosConFecha);
                $archivoMasReciente = array_key_first($archivosConFecha);
                
                if ($archivoMasReciente) {
                    $fechaModificacion = date('d/m/Y H:i', filemtime($archivoMasReciente));
                    $ultimoArchivo = $fechaModificacion;
                }
            }

            return [
                'status' => 'success',
                'estadisticas' => [
                    'total' => $totalArchivos,
                    'ultimo' => $ultimoArchivo,
                    'espacio' => $this->formatearTamaño($espacioTotal)
                ]
            ];

        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasBackups: " . $e->getMessage());
            return [
                'status' => 'error',
                'mensaje' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'estadisticas' => [
                    'total' => 0,
                    'ultimo' => 'N/A',
                    'espacio' => 'N/A'
                ]
            ];
        }
    }

    private function ejecutarBackupCompleto()
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $nombreArchivo = "backup_completo_{$timestamp}.sql";
            $rutaBackup = $this->obtenerRutaBackups() . $nombreArchivo;

            $this->crearDirectorioBackups();

            $backupContent = $this->generarBackupBD('general');
            $backupContent .= "\n\n-- ================================================\n";
            $backupContent .= "-- BACKUP BASE DE DATOS SEGURIDAD\n";
            $backupContent .= "-- ================================================\n\n";
            $backupContent .= $this->generarBackupBD('seguridad');

            if (file_put_contents($rutaBackup, $backupContent) !== false) {
                chmod($rutaBackup, 0644); // Asegurar que el archivo sea legible por el servidor web
                $this->registrarBackupEnBD($nombreArchivo, 'COMPLETO', filesize($rutaBackup));
                $this->registrarAccionBitacora('CREAR_BACKUP_COMPLETO', $nombreArchivo);
                
                return [
                    'status' => true,
                    'message' => 'Backup completo creado exitosamente.',
                    'archivo' => $nombreArchivo,
                    'ruta' => $rutaBackup
                ];
            } else {
                throw new Exception("Error al escribir el archivo de backup");
            }

        } catch (Exception $e) {
            error_log("BackupModel::ejecutarBackupCompleto - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage(),
                'archivo' => null
            ];
        }
    }

    private function ejecutarBackupTabla(string $tabla)
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $nombreArchivo = "backup_tabla_{$tabla}_{$timestamp}.sql";
            $rutaBackup = $this->obtenerRutaBackups() . $nombreArchivo;

            $this->crearDirectorioBackups();

            $database = $this->determinarBaseDatos($tabla);
            $backupContent = $this->generarBackupTabla($tabla, $database);

            if (file_put_contents($rutaBackup, $backupContent) !== false) {
                chmod($rutaBackup, 0644); // Asegurar que el archivo sea legible por el servidor web
                $this->registrarBackupEnBD($nombreArchivo, 'TABLA', filesize($rutaBackup));
                $this->registrarAccionBitacora('CREAR_BACKUP_TABLA', $nombreArchivo);
                
                return [
                    'status' => true,
                    'message' => "Backup de la tabla {$tabla} creado exitosamente.",
                    'archivo' => $nombreArchivo,
                    'ruta' => $rutaBackup
                ];
            } else {
                throw new Exception("Error al escribir el archivo de backup");
            }

        } catch (Exception $e) {
            error_log("BackupModel::ejecutarBackupTabla - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al crear backup de tabla: ' . $e->getMessage(),
                'archivo' => null
            ];
        }
    }

    private function ejecutarBusquedaBackups()
    {
        try {
            $this->setQuery(
                "SELECT id, nombre_archivo, tipo_backup, tamaño_archivo, 
                        fecha_creacion, estatus,
                        DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formato
                 FROM historial_backups 
                 WHERE estatus = 'activo' 
                 ORDER BY fecha_creacion DESC"
            );

            $stmt = $this->dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'data' => $resultado,
                'message' => 'Backups obtenidos exitosamente'
            ];

        } catch (Exception $e) {
            error_log("BackupModel::ejecutarBusquedaBackups - Error: " . $e->getMessage());
            return [
                'status' => false,
                'data' => [],
                'message' => 'Error al obtener lista de backups: ' . $e->getMessage()
            ];
        }
    }

    private function ejecutarEliminacionBackup(string $nombreArchivo)
    {
        try {
            $rutaArchivo = $this->obtenerRutaBackups() . $nombreArchivo;

            if (file_exists($rutaArchivo)) {
                if (unlink($rutaArchivo)) {
                    $this->actualizarEstatusBackup($nombreArchivo, 'eliminado');
                    $this->registrarAccionBitacora('ELIMINAR_BACKUP', $nombreArchivo);
                    
                    return [
                        'status' => true,
                        'message' => 'Backup eliminado exitosamente.'
                    ];
                } else {
                    throw new Exception("No se pudo eliminar el archivo físico");
                }
            } else {
                $this->actualizarEstatusBackup($nombreArchivo, 'eliminado');
                return [
                    'status' => true,
                    'message' => 'Registro de backup eliminado (archivo no encontrado).'
                ];
            }

        } catch (Exception $e) {
            error_log("BackupModel::ejecutarEliminacionBackup - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al eliminar backup: ' . $e->getMessage()
            ];
        }
    }

    private function ejecutarRestauracionBackup(string $nombreArchivo)
    {
        try {
            $rutaArchivo = $this->obtenerRutaBackups() . $nombreArchivo;

            if (!file_exists($rutaArchivo)) {
                throw new Exception("El archivo de backup no existe");
            }

            $contenidoSQL = file_get_contents($rutaArchivo);
            if ($contenidoSQL === false) {
                throw new Exception("No se pudo leer el archivo de backup");
            }

            $this->ejecutarRestauracionSQL($contenidoSQL);
            $this->registrarAccionBitacora('RESTAURAR_BACKUP', $nombreArchivo);

            return [
                'status' => true,
                'message' => 'Backup restaurado exitosamente.'
            ];

        } catch (Exception $e) {
            error_log("BackupModel::ejecutarRestauracionBackup - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al restaurar backup: ' . $e->getMessage()
            ];
        }
    }

    private function generarBackupBD(string $database)
    {
        $db = $database === 'seguridad' ? $this->dbSeguridad : $this->dbGeneral;
        
        // Obtener el nombre de la base de datos directamente
        $stmt = $db->query("SELECT DATABASE() as db_name");
        $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbName = $dbInfo['db_name'];
        
        $backup = "-- ================================================\n";
        $backup .= "-- BACKUP BASE DE DATOS: {$dbName}\n";
        $backup .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- ================================================\n\n";
        
        $backup .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tablas = $this->obtenerTablasBD($db);

        foreach ($tablas as $tabla) {
            $tableName = isset($tabla['Name']) ? $tabla['Name'] : $tabla['TABLE_NAME'];
            $backup .= $this->generarBackupTablaCompleta($tableName, $db);
        }

        $backup .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

        return $backup;
    }

    private function generarBackupTabla(string $tabla, string $database)
    {
        $db = $database === 'seguridad' ? $this->dbSeguridad : $this->dbGeneral;
        
        $backup = "-- ================================================\n";
        $backup .= "-- BACKUP TABLA: {$tabla}\n";
        $backup .= "-- Base de datos: {$database}\n";
        $backup .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- ================================================\n\n";
        
        $backup .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $backup .= $this->generarBackupTablaCompleta($tabla, $db);
        $backup .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

        return $backup;
    }

    private function generarBackupTablaCompleta(string $tabla, $db)
    {
        $backup = "";

        try {
            // Verificar que la tabla existe
            $checkStmt = $db->prepare("SHOW TABLES LIKE ?");
            $checkStmt->execute([$tabla]);
            if (!$checkStmt->fetch()) {
                error_log("Tabla {$tabla} no existe");
                return "";
            }

            // Obtener estructura de la tabla
            $estructuraStmt = $db->query("SHOW CREATE TABLE `{$tabla}`");
            $estructura = $estructuraStmt->fetch(PDO::FETCH_ASSOC);
            
            $backup .= "-- Estructura de tabla para `{$tabla}`\n";
            $backup .= "DROP TABLE IF EXISTS `{$tabla}`;\n";
            $backup .= $estructura['Create Table'] . ";\n\n";

            // Obtener datos de la tabla
            $datosStmt = $db->query("SELECT * FROM `{$tabla}`");
            $datos = $datosStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($datos)) {
                $backup .= "-- Volcado de datos para la tabla `{$tabla}`\n";
                $backup .= "LOCK TABLES `{$tabla}` WRITE;\n";
                
                // Obtener nombres de columnas
                $columnStmt = $db->query("SHOW COLUMNS FROM `{$tabla}`");
                $columnas = $columnStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $backup .= "INSERT INTO `{$tabla}` (`" . implode('`, `', $columnas) . "`) VALUES ";

                $first = true;
                foreach ($datos as $fila) {
                    if (!$first) {
                        $backup .= ",\n";
                    }
                    $backup .= "(";
                    $valores = [];
                    foreach ($fila as $valor) {
                        if ($valor === null) {
                            $valores[] = "NULL";
                        } else {
                            $valores[] = $db->quote($valor);
                        }
                    }
                    $backup .= implode(", ", $valores);
                    $backup .= ")";
                    $first = false;
                }
                $backup .= ";\n";
                $backup .= "UNLOCK TABLES;\n\n";
            }

        } catch (Exception $e) {
            error_log("Error generando backup para tabla {$tabla}: " . $e->getMessage());
        }

        return $backup;
    }

    private function obtenerTablasBD($db)
    {
        try {
            $stmt = $db->query("SHOW TABLE STATUS");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo tablas: " . $e->getMessage());
            return [];
        }
    }

    private function determinarBaseDatos(string $tabla)
    {
        try {
            $tablasSeguridadStmt = $this->dbSeguridad->prepare("SHOW TABLES LIKE ?");
            $tablasSeguridadStmt->execute([$tabla]);
            if ($tablasSeguridadStmt->fetch()) {
                return 'seguridad';
            }
            return 'general';
        } catch (Exception $e) {
            error_log("Error determinando base de datos para tabla {$tabla}: " . $e->getMessage());
            return 'general';
        }
    }

    private function obtenerRutaBackups()
{
    // Usar BACKUP_DIR si está definida, sino usar ruta por defecto
    $ruta = defined('BACKUP_DIR') ? BACKUP_DIR : __DIR__ . '/../../config/backups/';
    $ruta = rtrim($ruta, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    
    // Si el directorio no existe, intentar crearlo
    if (!is_dir($ruta)) {
        if (!@mkdir($ruta, 0775, true) && !is_dir($ruta)) {
            throw new Exception("No se pudo crear el directorio de backups: permisos insuficientes.");
        }
    }
    
    // Intentar escribir un archivo de prueba para verificar permisos reales
    $archivoTest = $ruta . '.test_permisos';
    $puedeEscribir = @file_put_contents($archivoTest, 'test') !== false;
    
    if ($puedeEscribir) {
        @unlink($archivoTest); // Eliminar archivo de prueba
        return $ruta;
    }
    
    throw new Exception("El directorio de backups no tiene permisos de escritura: " . $ruta);
}




    private function crearDirectorioBackups()
    {
        $rutaBackups = dirname($this->obtenerRutaBackups());
        if (!is_dir($rutaBackups)) {
            mkdir($rutaBackups, 0755, true);
        }
    }

    private function registrarBackupEnBD(string $nombreArchivo, string $tipo, int $tamaño)
    {
        try {
            $this->setQuery(
                "INSERT INTO historial_backups (nombre_archivo, tipo_backup, tamaño_archivo, fecha_creacion, estatus) 
                 VALUES (?, ?, ?, NOW(), 'activo')"
            );
            
            $this->setArray([$nombreArchivo, $tipo, $tamaño]);
            
            $stmt = $this->dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
        } catch (Exception $e) {
            error_log("Error al registrar backup en BD: " . $e->getMessage());
        }
    }

    private function actualizarEstatusBackup(string $nombreArchivo, string $estatus)
    {
        try {
            $this->setQuery(
                "UPDATE historial_backups SET estatus = ? WHERE nombre_archivo = ?"
            );
            
            $this->setArray([$estatus, $nombreArchivo]);
            
            $stmt = $this->dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
        } catch (Exception $e) {
            error_log("Error al actualizar estatus de backup: " . $e->getMessage());
        }
    }

    private function ejecutarRestauracionSQL(string $contenidoSQL)
    {
        $statements = $this->dividirSQL($contenidoSQL);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || substr($statement, 0, 2) === '--') {
                continue;
            }

            if (strpos($statement, 'USE ') === 0) {
                continue;
            }

            try {
                if ($this->esTablaDeSecurity($statement)) {
                    $this->dbSeguridad->exec($statement);
                } else {
                    $this->dbGeneral->exec($statement);
                }
            } catch (Exception $e) {
                error_log("Error ejecutando statement: " . $e->getMessage());
                error_log("Statement: " . substr($statement, 0, 200) . "...");
            }
        }
    }

    private function esTablaDeSecurity(string $statement)
    {
        $tablasSecurity = ['bitacora', 'usuarios', 'roles', 'modulos', 'permisos', 'rol_modulo', 'rol_modulo_permisos', 'notificaciones', 'historial_backups'];
        
        foreach ($tablasSecurity as $tabla) {
            if (strpos($statement, "`{$tabla}`") !== false || strpos($statement, " {$tabla} ") !== false) {
                return true;
            }
        }
        return false;
    }

    private function dividirSQL(string $sql)
    {
        $statements = [];
        $currentStatement = '';
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }
            
            $currentStatement .= $line . "\n";
            
            if (substr($line, -1) === ';') {
                $statements[] = rtrim($currentStatement, "\n");
                $currentStatement = '';
            }
        }
        
        if (!empty(trim($currentStatement))) {
            $statements[] = trim($currentStatement);
        }
        
        return $statements;
    }

    private function registrarAccionBitacora(string $accion, string $detalles)
    {
        try {
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idusuario) {
                $this->bitacoraModel->registrarAccion('backups', $accion, $idusuario, $detalles);
            }
        } catch (Exception $e) {
            error_log("Error al registrar en bitácora: " . $e->getMessage());
        }
    }

    /**
     * Formatea el tamaño de bytes a formato legible
     */
    private function formatearTamaño($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
    }

    /**
     * Importa una base de datos desde un archivo SQL
     */
    public function importarBaseDatos($rutaArchivo, $baseDatos = 'bd_pda')
    {
        try {
            if (!file_exists($rutaArchivo)) {
                return ['status' => false, 'message' => 'El archivo SQL no existe'];
            }

            // Leer el contenido del archivo
            $contenidoSQL = file_get_contents($rutaArchivo);
            if ($contenidoSQL === false) {
                return ['status' => false, 'message' => 'No se pudo leer el archivo SQL'];
            }

            // Validar que el archivo no esté vacío
            if (empty(trim($contenidoSQL))) {
                return ['status' => false, 'message' => 'El archivo SQL está vacío'];
            }

            // Determinar qué base(s) de datos importar
            $resultados = [];
            
            if ($baseDatos === 'ambas') {
                $resultados['bd_pda'] = $this->ejecutarImportacion($contenidoSQL, $this->dbGeneral, 'BD Principal');
                
                try {
                    $resultados['bd_pda_seguridad'] = $this->ejecutarImportacion($contenidoSQL, $this->dbSeguridad, 'BD Seguridad');
                } catch (Exception $e) {
                    $resultados['bd_pda_seguridad'] = ['status' => false, 'message' => 'BD Seguridad no disponible: ' . $e->getMessage()];
                }
                
                // Verificar si al menos una fue exitosa
                $alguaExitosa = $resultados['bd_pda']['status'] || $resultados['bd_pda_seguridad']['status'];
                
                if ($alguaExitosa) {
                    $mensajes = [];
                    if ($resultados['bd_pda']['status']) $mensajes[] = 'BD Principal: OK';
                    if ($resultados['bd_pda_seguridad']['status']) $mensajes[] = 'BD Seguridad: OK';
                    
                    $errores = [];
                    if (!$resultados['bd_pda']['status']) $errores[] = 'BD Principal: ' . $resultados['bd_pda']['message'];
                    if (!$resultados['bd_pda_seguridad']['status']) $errores[] = 'BD Seguridad: ' . $resultados['bd_pda_seguridad']['message'];
                    
                    $mensaje = 'Importación completada. ' . implode(', ', $mensajes);
                    if (!empty($errores)) {
                        $mensaje .= '. Advertencias: ' . implode('; ', $errores);
                    }
                    
                    return ['status' => true, 'message' => $mensaje];
                } else {
                    $errores = [];
                    if (!$resultados['bd_pda']['status']) $errores[] = 'BD Principal: ' . $resultados['bd_pda']['message'];
                    if (!$resultados['bd_pda_seguridad']['status']) $errores[] = 'BD Seguridad: ' . $resultados['bd_pda_seguridad']['message'];
                    return ['status' => false, 'message' => 'Errores en importación: ' . implode('; ', $errores)];
                }
            } else {
                // Importar a una sola base de datos
                try {
                    $db = ($baseDatos === 'bd_pda_seguridad') ? $this->dbSeguridad : $this->dbGeneral;
                    $nombreDB = ($baseDatos === 'bd_pda_seguridad') ? 'BD Seguridad' : 'BD Principal';
                    
                    return $this->ejecutarImportacion($contenidoSQL, $db, $nombreDB);
                } catch (Exception $e) {
                    return ['status' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()];
                }
            }

        } catch (Exception $e) {
            error_log("Error en importarBaseDatos: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al importar: ' . $e->getMessage()];
        }
    }

    /**
     * Ejecuta la importación en una base de datos específica
     */
    private function ejecutarImportacion($contenidoSQL, $db, $nombreDB)
    {
        try {
            // Verificar que la conexión esté disponible
            if (!$db) {
                return ['status' => false, 'message' => "Conexión a {$nombreDB} no disponible"];
            }

            // Dividir el contenido en sentencias individuales
            $sentencias = $this->dividirSentenciasSQL($contenidoSQL);
            
            if (empty($sentencias)) {
                return ['status' => false, 'message' => "No se encontraron sentencias SQL válidas para {$nombreDB}"];
            }

            $db->beginTransaction();
            
            $sentenciasEjecutadas = 0;
            $sentenciasOmitidas = 0;
            $errores = [];
            $advertencias = [];

            foreach ($sentencias as $index => $sentencia) {
                $sentencia = trim($sentencia);
                if (empty($sentencia)) continue;

                try {
                    // Verificar si es una sentencia que debe omitirse
                    if ($this->debeOmitirSentencia($sentencia)) {
                        $sentenciasOmitidas++;
                        $advertencias[] = "Sentencia omitida (línea " . ($index + 1) . "): " . substr($sentencia, 0, 50) . "...";
                        continue;
                    }

                    $stmt = $db->prepare($sentencia);
                    $stmt->execute();
                    $sentenciasEjecutadas++;
                    
                } catch (PDOException $e) {
                    $errorMsg = $e->getMessage();
                    
                    // Errores que podemos ignorar (no críticos)
                    if ($this->esErrorIgnorable($errorMsg)) {
                        $advertencias[] = "Advertencia (línea " . ($index + 1) . "): " . $errorMsg;
                        continue;
                    }
                    
                    // Errores críticos
                    $errores[] = "Error crítico (línea " . ($index + 1) . "): " . $errorMsg . " - Sentencia: " . substr($sentencia, 0, 100) . "...";
                    
                    // Si hay muchos errores críticos, detener la importación
                    if (count($errores) > 10) {
                        throw new Exception("Demasiados errores críticos encontrados. Deteniendo importación.");
                    }
                }
            }

            $db->commit();

            // Preparar mensaje de resultado
            $mensaje = "Importación en {$nombreDB} completada:\n";
            $mensaje .= "- Sentencias ejecutadas: {$sentenciasEjecutadas}\n";
            
            if ($sentenciasOmitidas > 0) {
                $mensaje .= "- Sentencias omitidas: {$sentenciasOmitidas}\n";
            }
            
            if (!empty($advertencias)) {
                $mensaje .= "- Advertencias: " . count($advertencias) . "\n";
            }
            
            if (!empty($errores)) {
                $mensaje .= "- Errores no críticos: " . count($errores) . "\n";
            }

            if ($sentenciasEjecutadas > 0) {
                return [
                    'status' => true, 
                    'message' => $mensaje,
                    'detalles' => [
                        'ejecutadas' => $sentenciasEjecutadas,
                        'omitidas' => $sentenciasOmitidas,
                        'advertencias' => $advertencias,
                        'errores' => $errores
                    ]
                ];
            } else {
                return [
                    'status' => false, 
                    'message' => "No se pudo ejecutar ninguna sentencia en {$nombreDB}. Errores: " . implode('; ', array_slice($errores, 0, 3))
                ];
            }

        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'status' => false, 
                'message' => "Error en {$nombreDB}: " . $e->getMessage(),
                'detalles' => [
                    'error_tipo' => 'transaccion',
                    'errores' => $errores ?? []
                ]
            ];
        }
    }

    /**
     * Verifica si una sentencia debe omitirse
     */
    private function debeOmitirSentencia($sentencia)
    {
        $patronesOmitir = [
            '/^\s*DELIMITER\s/i',
            '/^\s*USE\s+/i',
            '/^\s*SET\s+SQL_MODE\s*=/i',
            '/^\s*SET\s+FOREIGN_KEY_CHECKS\s*=/i',
            '/^\s*SET\s+UNIQUE_CHECKS\s*=/i',
            '/^\s*SET\s+AUTOCOMMIT\s*=/i',
            '/^\s*--/i',  // Comentarios
            '/^\s*\/\*/i' // Comentarios multilínea
        ];
        
        foreach ($patronesOmitir as $patron) {
            if (preg_match($patron, $sentencia)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verifica si un error puede ser ignorado (no crítico)
     */
    private function esErrorIgnorable($errorMsg)
    {
        $erroresIgnorables = [
            'already exists',
            'duplicate entry',
            'duplicate key',
            'table already exists',
            'database already exists',
            'ya existe',
            'duplicado',
            'duplicate column',
            'column already exists'
        ];
        
        $errorMsgLower = strtolower($errorMsg);
        
        foreach ($erroresIgnorables as $errorIgnorable) {
            if (strpos($errorMsgLower, strtolower($errorIgnorable)) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Divide el contenido SQL en sentencias individuales
     */
    private function dividirSentenciasSQL($contenidoSQL)
    {
        // Remover comentarios pero mantener la estructura
        $contenidoSQL = preg_replace('/\/\*.*?\*\//s', '', $contenidoSQL);
        $contenidoSQL = preg_replace('/--.*$/m', '', $contenidoSQL);
        
        // Limpiar espacios extra
        $contenidoSQL = preg_replace('/\s+/', ' ', $contenidoSQL);
        $contenidoSQL = trim($contenidoSQL);
        
        $sentencias = [];
        $sentenciaActual = '';
        $dentroComillas = false;
        $tipoComilla = '';
        $delimitador = ';';
        $i = 0;
        $longitud = strlen($contenidoSQL);
        
        while ($i < $longitud) {
            $char = $contenidoSQL[$i];
            
            // Detectar cambio de delimitador
            if (!$dentroComillas && strtoupper(substr($contenidoSQL, $i, 9)) === 'DELIMITER') {
                // Buscar el nuevo delimitador
                $finLinea = strpos($contenidoSQL, "\n", $i);
                if ($finLinea === false) $finLinea = $longitud;
                
                $lineaDelimitador = trim(substr($contenidoSQL, $i, $finLinea - $i));
                $partesDelimitador = explode(' ', $lineaDelimitador);
                
                if (count($partesDelimitador) >= 2) {
                    $nuevoDelimitador = trim($partesDelimitador[1]);
                    if (!empty($nuevoDelimitador)) {
                        $delimitador = $nuevoDelimitador;
                    }
                }
                
                // Saltar toda la línea DELIMITER
                $i = $finLinea + 1;
                continue;
            }
            
            // Manejo de comillas
            if (!$dentroComillas && ($char === '"' || $char === "'")) {
                $dentroComillas = true;
                $tipoComilla = $char;
                $sentenciaActual .= $char;
            } elseif ($dentroComillas && $char === $tipoComilla) {
                // Verificar si es escape
                if ($i > 0 && $contenidoSQL[$i-1] !== '\\') {
                    $dentroComillas = false;
                    $tipoComilla = '';
                }
                $sentenciaActual .= $char;
            } elseif (!$dentroComillas && substr($contenidoSQL, $i, strlen($delimitador)) === $delimitador) {
                // Encontrado delimitador
                $sentenciaActual = trim($sentenciaActual);
                if (!empty($sentenciaActual) && !$this->esLineaDelimitador($sentenciaActual)) {
                    $sentencias[] = $sentenciaActual;
                }
                $sentenciaActual = '';
                $i += strlen($delimitador) - 1; // -1 porque el bucle incrementará i
            } else {
                $sentenciaActual .= $char;
            }
            
            $i++;
        }
        
        // Agregar la última sentencia si no termina con delimitador
        $sentenciaActual = trim($sentenciaActual);
        if (!empty($sentenciaActual) && !$this->esLineaDelimitador($sentenciaActual)) {
            $sentencias[] = $sentenciaActual;
        }
        
        return $this->preprocesarSentencias($sentencias);
    }

    /**
     * Verifica si una línea es una declaración de delimitador
     */
    private function esLineaDelimitador($sentencia)
    {
        return stripos(trim($sentencia), 'DELIMITER') === 0;
    }

    /**
     * Preprocesa las sentencias para manejar casos especiales
     */
    private function preprocesarSentencias($sentencias)
    {
        $sentenciasProcesadas = [];
        
        foreach ($sentencias as $sentencia) {
            $sentencia = trim($sentencia);
            
            // Saltar sentencias vacías o solo de delimitador
            if (empty($sentencia) || $this->esLineaDelimitador($sentencia)) {
                continue;
            }
            
            // Manejar triggers, procedimientos y funciones
            if ($this->esSentenciaCompleja($sentencia)) {
                $sentenciasProcesadas[] = $this->normalizarSentenciaCompleja($sentencia);
            } else {
                $sentenciasProcesadas[] = $sentencia;
            }
        }
        
        return $sentenciasProcesadas;
    }

    /**
     * Verifica si es una sentencia compleja (trigger, procedure, function)
     */
    private function esSentenciaCompleja($sentencia)
    {
        $patrones = [
            '/^\s*CREATE\s+(TRIGGER|PROCEDURE|FUNCTION)/i',
            '/^\s*DROP\s+(TRIGGER|PROCEDURE|FUNCTION)/i'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $sentencia)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Normaliza sentencias complejas para MariaDB/MySQL
     */
    private function normalizarSentenciaCompleja($sentencia)
    {
        // Remover cualquier referencia a DELIMITER que pueda quedar
        $sentencia = preg_replace('/DELIMITER\s+\$\$\s*/i', '', $sentencia);
        $sentencia = preg_replace('/\$\$\s*DELIMITER\s*;/i', '', $sentencia);
        
        // Limpiar delimitadores especiales
        $sentencia = str_replace('$$', '', $sentencia);
        
        // Asegurar que termine con punto y coma
        $sentencia = rtrim($sentencia, ' ;') . ';';
        
        return $sentencia;
    }

    public function __destruct()
    {
        if ($this->conexion) {
            $this->conexion->disconnect();
        }
    }
}
?>