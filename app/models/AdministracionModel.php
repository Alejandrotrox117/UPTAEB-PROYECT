<?php
require_once("app/core/conexion.php");

class AdministracionModel
{
    private $query;
    private $array;
    private $data;
    private $result;

    public function __construct()
    {
        // Constructor vacío
    }

    // Getters y Setters
    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function getArray()
    {
        return $this->array ?? [];
    }

    public function setArray(array $array)
    {
        $this->array = $array;
    }

    public function getData()
    {
        return $this->data ?? [];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Crea un backup de la base de datos
     */
    public function crearBackupBaseDatos()
    {
        try {
            // Obtener configuración de la base de datos
            $config = $this->obtenerConfiguracionBD();
            
            // Crear directorio de backups si no existe
            $directorioBackups = 'backups/';
            if (!file_exists($directorioBackups)) {
                if (!mkdir($directorioBackups, 0755, true)) {
                    throw new Exception("No se pudo crear el directorio de backups");
                }
            }

            // Generar nombre del archivo
            $fecha = date('Y-m-d_H-i-s');
            $nombreArchivo = $config['database'] . '_backup_' . $fecha . '.sql';
            $rutaCompleta = $directorioBackups . $nombreArchivo;

            // Construir comando mysqldump
            $comando = $this->construirComandoMysqldump($config, $rutaCompleta);

            // Ejecutar backup
            $output = [];
            $returnCode = 0;
            exec($comando, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception("Error al ejecutar mysqldump. Código de retorno: $returnCode. Output: " . implode("\n", $output));
            }

            // Verificar que el archivo se creó y tiene contenido
            if (!file_exists($rutaCompleta) || filesize($rutaCompleta) == 0) {
                throw new Exception("El archivo de backup no se generó correctamente");
            }

            // Comprimir el archivo (opcional)
            $archivoComprimido = $this->comprimirBackup($rutaCompleta);

            return [
                'status' => true,
                'message' => 'Backup creado exitosamente',
                'archivo' => $archivoComprimido ?? $nombreArchivo,
                'tamaño' => $this->formatearTamaño(filesize($archivoComprimido ?? $rutaCompleta)),
                'fecha' => date('d/m/Y H:i:s')
            ];

        } catch (Exception $e) {
            error_log("AdministracionModel::crearBackupBaseDatos - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lista todos los backups disponibles
     */
    public function listarBackupsDisponibles()
    {
        try {
            $directorioBackups = 'backups/';
            
            if (!is_dir($directorioBackups)) {
                return [
                    'status' => true,
                    'backups' => [],
                    'message' => 'No hay backups disponibles'
                ];
            }

            $archivos = glob($directorioBackups . '*backup*.{sql,zip,gz}', GLOB_BRACE);
            $backups = [];

            foreach ($archivos as $archivo) {
                $nombreArchivo = basename($archivo);
                $tamaño = filesize($archivo);
                $fechaModificacion = filemtime($archivo);

                $backups[] = [
                    'nombre' => $nombreArchivo,
                    'tamaño' => $this->formatearTamaño($tamaño),
                    'tamaño_bytes' => $tamaño,
                    'fecha' => date('d/m/Y H:i:s', $fechaModificacion),
                    'fecha_timestamp' => $fechaModificacion
                ];
            }

            // Ordenar por fecha (más reciente primero)
            usort($backups, function($a, $b) {
                return $b['fecha_timestamp'] - $a['fecha_timestamp'];
            });

            return [
                'status' => true,
                'backups' => $backups,
                'total' => count($backups)
            ];

        } catch (Exception $e) {
            error_log("AdministracionModel::listarBackupsDisponibles - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al listar backups: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Permite descargar un backup específico
     */
    public function descargarBackup($nombreArchivo)
    {
        try {
            // Validar nombre del archivo para evitar path traversal
            if (!$this->validarNombreArchivo($nombreArchivo)) {
                throw new Exception("Nombre de archivo no válido");
            }

            $rutaCompleta = 'backups/' . $nombreArchivo;

            if (!file_exists($rutaCompleta)) {
                throw new Exception("El archivo de backup no existe");
            }

            return [
                'status' => true,
                'ruta_completa' => $rutaCompleta,
                'mensaje' => 'Archivo listo para descarga'
            ];

        } catch (Exception $e) {
            error_log("AdministracionModel::descargarBackup - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al preparar descarga: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un backup específico
     */
    public function eliminarBackup($nombreArchivo)
    {
        try {
            // Validar nombre del archivo
            if (!$this->validarNombreArchivo($nombreArchivo)) {
                throw new Exception("Nombre de archivo no válido");
            }

            $rutaCompleta = 'backups/' . $nombreArchivo;

            if (!file_exists($rutaCompleta)) {
                throw new Exception("El archivo de backup no existe");
            }

            if (!unlink($rutaCompleta)) {
                throw new Exception("No se pudo eliminar el archivo");
            }

            return [
                'status' => true,
                'message' => 'Backup eliminado exitosamente'
            ];

        } catch (Exception $e) {
            error_log("AdministracionModel::eliminarBackup - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al eliminar backup: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene información del sistema
     */
    public function obtenerInformacionSistema()
    {
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectGeneral();

            // Información de la base de datos
            $stmt = $db->query("SELECT COUNT(*) as total_tablas FROM information_schema.tables WHERE table_schema = DATABASE()");
            $totalTablas = $stmt->fetch(PDO::FETCH_ASSOC)['total_tablas'];

            $stmt = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS tamaño_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
            $tamañoBD = $stmt->fetch(PDO::FETCH_ASSOC)['tamaño_mb'] ?? 0;

            // Información del servidor
            $infoSistema = [
                'php_version' => phpversion(),
                'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
                'sistema_operativo' => php_uname('s') . ' ' . php_uname('r'),
                'memoria_php' => ini_get('memory_limit'),
                'tiempo_ejecucion_max' => ini_get('max_execution_time') . ' segundos',
                'tamaño_upload_max' => ini_get('upload_max_filesize'),
                'base_datos' => [
                    'motor' => $db->getAttribute(PDO::ATTR_DRIVER_NAME),
                    'version' => $db->getAttribute(PDO::ATTR_SERVER_VERSION),
                    'total_tablas' => $totalTablas,
                    'tamaño_mb' => $tamañoBD . ' MB'
                ],
                'espacio_disco' => [
                    'libre' => $this->formatearTamaño(disk_free_space('.')),
                    'total' => $this->formatearTamaño(disk_total_space('.'))
                ]
            ];

            $conexion->disconnect();

            return [
                'status' => true,
                'info' => $infoSistema
            ];

        } catch (Exception $e) {
            error_log("AdministracionModel::obtenerInformacionSistema - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener información del sistema: ' . $e->getMessage()
            ];
        }
    }

    // Métodos privados auxiliares

    private function obtenerConfiguracionBD()
    {
        // Usar la misma configuración que la clase Conexion
        $conexion = new Conexion();
        
        return [
            'host' => $conexion->getServidor(),
            'port' => '3306',
            'database' => $conexion->getDatabaseGeneral(),
            'username' => $conexion->getUsername(),
            'password' => $conexion->getPassword()
        ];
    }

    private function construirComandoMysqldump($config, $rutaArchivo)
    {
        // Ruta del mysqldump en XAMPP
        $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';
        
        // Si no existe en XAMPP, intentar comando global
        if (!file_exists($mysqldumpPath)) {
            $mysqldumpPath = 'mysqldump';
        }

        // Construir comando
        $comando = sprintf(
            '"%s" --host=%s --port=%s --user=%s --routines --triggers --single-transaction --lock-tables=false %s > "%s"',
            $mysqldumpPath,
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            $rutaArchivo
        );

        // Agregar password si existe
        if (!empty($config['password'])) {
            $comando = str_replace(
                '--user=' . escapeshellarg($config['username']),
                '--user=' . escapeshellarg($config['username']) . ' --password=' . escapeshellarg($config['password']),
                $comando
            );
        }

        return $comando;
    }

    private function comprimirBackup($rutaArchivo)
    {
        try {
            // Intentar comprimir con gzip si está disponible
            if (function_exists('gzopen')) {
                $archivoComprimido = $rutaArchivo . '.gz';
                
                $input = fopen($rutaArchivo, 'rb');
                $output = gzopen($archivoComprimido, 'wb9');
                
                if ($input && $output) {
                    while (!feof($input)) {
                        gzwrite($output, fread($input, 8192));
                    }
                    
                    fclose($input);
                    gzclose($output);
                    
                    // Eliminar archivo original si la compresión fue exitosa
                    if (file_exists($archivoComprimido) && filesize($archivoComprimido) > 0) {
                        unlink($rutaArchivo);
                        return basename($archivoComprimido);
                    }
                }
            }
            
            return null; // No se pudo comprimir
            
        } catch (Exception $e) {
            error_log("Error al comprimir backup: " . $e->getMessage());
            return null;
        }
    }

    private function validarNombreArchivo($nombreArchivo)
    {
        // Validar que el nombre del archivo sea seguro
        if (empty($nombreArchivo)) {
            return false;
        }

        // No permitir path traversal
        if (strpos($nombreArchivo, '..') !== false || strpos($nombreArchivo, '/') !== false || strpos($nombreArchivo, '\\') !== false) {
            return false;
        }

        // Solo permitir archivos de backup
        $extensionesPermitidas = ['sql', 'zip', 'gz'];
        $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($extension), $extensionesPermitidas)) {
            return false;
        }

        // Verificar que contiene 'backup' en el nombre
        if (strpos($nombreArchivo, 'backup') === false) {
            return false;
        }

        return true;
    }

    private function formatearTamaño($bytes)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log(1024));
        
        return round($bytes / pow(1024, $i), 2) . ' ' . $unidades[$i];
    }
}
