<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\BackupModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

class Backup extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // Verificar permisos de acceso al módulo backups
        if (!PermisosModuloVerificar::verificarAccesoModulo('Backup')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Backup', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Backups";
        $data['page_title'] = "Gestión de Backups";
        $data['page_name'] = "backups";
        $data['page_content'] = "Gestión integral de copias de seguridad del sistema";
        $data['page_functions_js'] = "functions_backup.js";
        
        $this->views->getView($this, "backup", $data);
    }

    public function obtenerListaBackups()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'ver')) {
                echo json_encode([
                    'status' => false,
                    'message' => 'No tienes permisos para ver backups',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $resultado = $this->model->obtenerListaBackups();
                
                if ($resultado['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('Backup', 'CONSULTA_LISTADO', $idusuario);
                }
                
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log("Error en obtenerListaBackups: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function crearBackupCompleto()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'crear')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para crear backups'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $resultado = $this->model->crearBackupCompleto();
                
                if ($resultado['status']) {
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                }
                
            } catch (Exception $e) {
                error_log("Error en crearBackupCompleto: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al crear backup completo: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function crearBackupTabla()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'crear')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para crear backups'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $tabla = $input['tabla'] ?? $_POST['tabla'] ?? '';
                
                if (empty($tabla)) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Debe especificar una tabla'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultado = $this->model->crearBackupTabla($tabla);
                
                if ($resultado['status']) {
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                }
                
            } catch (Exception $e) {
                error_log("Error en crearBackupTabla: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al crear backup de tabla: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function eliminarBackup()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'eliminar')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para eliminar backups'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $nombreArchivo = $input['archivo'] ?? $_POST['nombre_archivo'] ?? '';
                
                if (empty($nombreArchivo)) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Debe especificar el nombre del archivo'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultado = $this->model->eliminarBackup($nombreArchivo);
                
                if ($resultado['status']) {
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                }
                
            } catch (Exception $e) {
                error_log("Error en eliminarBackup: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al eliminar backup: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /*
    // MÉTODO RESTAURAR BACKUP DESHABILITADO POR SEGURIDAD
    public function restaurarBackup()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'editar')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para restaurar backups'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $nombreArchivo = $input['archivo'] ?? $_POST['nombre_archivo'] ?? '';
                
                if (empty($nombreArchivo)) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Debe especificar el nombre del archivo'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $esSuperUsuario = $this->model->getDbSeguridad()->prepare("SELECT COUNT(*) FROM usuarios WHERE idusuario = ? AND estatus = 'activo' AND idrol = 1");
                $esSuperUsuario->execute([$idusuario]);
                
                if (!$esSuperUsuario->fetchColumn()) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Solo usuarios con privilegios de super administrador pueden restaurar backups'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultado = $this->model->restaurarBackup($nombreArchivo);
                
                if ($resultado['status']) {
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                }
                
            } catch (Exception $e) {
                error_log("Error en restaurarBackup: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al restaurar backup: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
    */

    public function descargarBackup()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'ver')) {
                http_response_code(403);
                echo "No tienes permisos para descargar backups";
                die();
            }

            try {
                $nombreArchivo = $_GET['archivo'] ?? '';
                
                if (empty($nombreArchivo)) {
                    http_response_code(400);
                    echo "Nombre de archivo no especificado";
                    die();
                }

                $rutaCompleta = realpath(dirname(__FILE__) . '/../../config/backups/') . '/' . $nombreArchivo;
                
                if (!file_exists($rutaCompleta)) {
                    http_response_code(404);
                    echo "Archivo no encontrado";
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $this->bitacoraModel->registrarAccion('Backup', 'DESCARGAR_BACKUP', $idusuario, $nombreArchivo);

                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
                header('Content-Length: ' . filesize($rutaCompleta));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                
                readfile($rutaCompleta);
                
            } catch (Exception $e) {
                error_log("Error en descargarBackup: " . $e->getMessage());
                http_response_code(500);
                echo "Error interno del servidor";
            }
            die();
        }
    }

    public function importarDB()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'editar')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para importar bases de datos'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                // Verificar que el usuario sea super administrador
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Intentar verificar en BD de seguridad primero, luego en BD general como respaldo
                $esSuperUsuario = false;
                
                try {
                    $stmt = $this->model->getDbSeguridad()->prepare("SELECT COUNT(*) FROM usuarios WHERE idusuario = ? AND estatus = 'activo' AND idrol = 1");
                    $stmt->execute([$idusuario]);
                    $esSuperUsuario = $stmt->fetchColumn() > 0;
                } catch (Exception $e) {
                    // Si falla la BD de seguridad, intentar con la BD general
                    try {
                        $stmt = $this->model->getDbGeneral()->prepare("SELECT COUNT(*) FROM usuarios WHERE idusuario = ? AND estatus = 'activo' AND idrol = 1");
                        $stmt->execute([$idusuario]);
                        $esSuperUsuario = $stmt->fetchColumn() > 0;
                    } catch (Exception $e2) {
                        // Si ambas fallan, verificar por session como último recurso
                        $esSuperUsuario = (isset($_SESSION['user']['idrol']) && $_SESSION['user']['idrol'] == 1);
                    }
                }
                
                if (!$esSuperUsuario) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Solo usuarios con privilegios de super administrador pueden importar bases de datos'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Verificar que se haya subido un archivo
                if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'No se pudo cargar el archivo SQL'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $archivo = $_FILES['archivo'];
                $baseDatos = $_POST['base_datos'] ?? 'bd_pda';

                // Validar extensión del archivo
                $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
                if ($extension !== 'sql') {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Solo se permiten archivos con extensión .sql'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar tamaño del archivo (máximo 50MB)
                $maxSize = 50 * 1024 * 1024; // 50MB
                if ($archivo['size'] > $maxSize) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'El archivo es demasiado grande. Máximo permitido: 50MB'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Crear directorio temporal si no existe
                $dirTemporal = __DIR__ . '/../../config/temp/';
                if (!is_dir($dirTemporal)) {
                    mkdir($dirTemporal, 0755, true);
                }

                // Mover archivo a directorio temporal
                $nombreTemporal = uniqid('import_') . '.sql';
                $rutaTemporal = $dirTemporal . $nombreTemporal;
                
                if (!move_uploaded_file($archivo['tmp_name'], $rutaTemporal)) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'Error al procesar el archivo'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Ejecutar importación
                $resultado = $this->model->importarBaseDatos($rutaTemporal, $baseDatos);
                
                // Limpiar archivo temporal
                if (file_exists($rutaTemporal)) {
                    unlink($rutaTemporal);
                }
                
                if ($resultado['status']) {
                    // Registrar en bitácora
                    $this->bitacoraModel->registrarAccion('Backup', 'IMPORTACION_DB', $idusuario, "Archivo: {$archivo['name']}, BD: {$baseDatos}");

                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => $resultado['message']
                    ], JSON_UNESCAPED_UNICODE);
                }
                
            } catch (Exception $e) {
                // Limpiar archivo temporal en caso de error
                if (isset($rutaTemporal) && file_exists($rutaTemporal)) {
                    unlink($rutaTemporal);
                }
                
                error_log("Error en importarDB: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al importar base de datos: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function obtenerTablas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'ver')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para ver tablas',
                    'tablas' => []
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $tablas = [];
                
                $stmtGeneral = $this->model->getDbGeneral()->query("SHOW TABLES");
                while ($row = $stmtGeneral->fetch(PDO::FETCH_NUM)) {
                    $tablas[] = [
                        'name' => $row[0],
                        'base_datos' => 'general'
                    ];
                }
                
                $stmtSeguridad = $this->model->getDbSeguridad()->query("SHOW TABLES");
                while ($row = $stmtSeguridad->fetch(PDO::FETCH_NUM)) {
                    $tablas[] = [
                        'name' => $row[0],
                        'base_datos' => 'seguridad'
                    ];
                }
                
                echo json_encode([
                    'status' => 'success',
                    'tablas' => $tablas,
                    'mensaje' => 'Tablas obtenidas exitosamente'
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log("Error en obtenerTablas: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'tablas' => [],
                    'mensaje' => 'Error al obtener tablas: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function listarBackups()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'ver')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para ver backups',
                    'backups' => []
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $resultado = $this->model->obtenerListaBackups();
                
                if ($resultado['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('Backup', 'CONSULTA_LISTADO', $idusuario);
                    
                    echo json_encode([
                        'status' => 'success',
                        'mensaje' => 'Backups obtenidos exitosamente',
                        'backups' => $resultado['data']
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => $resultado['message'],
                        'backups' => []
                    ], JSON_UNESCAPED_UNICODE);
                }
                
            } catch (Exception $e) {
                error_log("Error en listarBackups: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error interno del servidor',
                    'backups' => []
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function obtenerEstadisticas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            header('Content-Type: application/json');
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Backup', 'ver')) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No tienes permisos para ver estadísticas'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $resultado = $this->model->obtenerEstadisticasBackups();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log("Error en obtenerEstadisticas: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al obtener estadísticas'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>
