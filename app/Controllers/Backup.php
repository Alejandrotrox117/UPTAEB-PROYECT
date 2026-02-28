<?php

use App\Models\BackupModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Backup - Estilo Funcional
 */

// Helper para verificar acceso común
function backup_verificarAcceso()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!obtenUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('Backup')) {
        renderView('backup', "permisos");
        exit();
    }
}

function backup_index()
{
    backup_verificarAcceso();

    $idusuario = obtenerUsuarioSesion();
    registrarAccesoModulo('Backup', $idusuario);

    $data['page_tag'] = "Backups";
    $data['page_title'] = "Gestión de Backups";
    $data['page_name'] = "backups";
    $data['page_content'] = "Gestión integral de copias de seguridad del sistema";
    $data['page_functions_js'] = "functions_backup.js";

    renderView("backup", "backup", $data);
}

function backup_obtenerListaBackups()
{
    backup_verificarAcceso();

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
            $model = new BackupModel();
            $resultado = $model->obtenerListaBackups();

            if ($resultado['status']) {
                registrarEnBitacora('Backup', 'CONSULTA_LISTADO');
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en backup_obtenerListaBackups: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error interno del servidor',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

function backup_crearBackupCompleto()
{
    backup_verificarAcceso();

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
            $model = new BackupModel();
            $resultado = $model->crearBackupCompleto();

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
            error_log("Error en backup_crearBackupCompleto: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al crear backup completo: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

function backup_crearBackupTabla()
{
    backup_verificarAcceso();

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

            $model = new BackupModel();
            $resultado = $model->crearBackupTabla($tabla);

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
            error_log("Error en backup_crearBackupTabla: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al crear backup de tabla: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

function backup_eliminarBackup()
{
    backup_verificarAcceso();

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

            $model = new BackupModel();
            $resultado = $model->eliminarBackup($nombreArchivo);

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
            error_log("Error en backup_eliminarBackup: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al eliminar backup: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

function backup_descargarBackup()
{
    backup_verificarAcceso();

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

            $rutaDirectorio = dirname(__FILE__) . '/../../config/backups/';
            $rutaCompleta = $rutaDirectorio . $nombreArchivo;

            if (!file_exists($rutaCompleta)) {
                http_response_code(404);
                echo "Archivo no encontrado: " . $rutaCompleta;
                die();
            }

            registrarEnBitacora('Backup', 'DESCARGAR_BACKUP', null, $nombreArchivo);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . filesize($rutaCompleta));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            readfile($rutaCompleta);

        } catch (Exception $e) {
            error_log("Error en backup_descargarBackup: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
        }
        die();
    }
}

function backup_importarDB()
{
    backup_verificarAcceso();

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
            $idusuario = obtenerUsuarioSesion();
            $model = new BackupModel();

            $esSuperUsuario = (isset($_SESSION['user']['idrol']) && $_SESSION['user']['idrol'] == 1);

            if (!$esSuperUsuario) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Solo usuarios con privilegios de super administrador pueden importar bases de datos'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'No se pudo cargar el archivo SQL'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $archivo = $_FILES['archivo'];
            $baseDatos = $_POST['base_datos'] ?? 'bd_pda';

            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if ($extension !== 'sql') {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Solo se permiten archivos con extensión .sql'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $maxSize = 50 * 1024 * 1024; // 50MB
            if ($archivo['size'] > $maxSize) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'El archivo es demasiado grande. Máximo permitido: 50MB'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $dirTemporal = __DIR__ . '/../../config/temp/';
            if (!is_dir($dirTemporal)) {
                mkdir($dirTemporal, 0755, true);
            }

            $nombreTemporal = uniqid('import_') . '.sql';
            $rutaTemporal = $dirTemporal . $nombreTemporal;

            if (!move_uploaded_file($archivo['tmp_name'], $rutaTemporal)) {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al procesar el archivo'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $resultado = $model->importarBaseDatos($rutaTemporal, $baseDatos);

            if (file_exists($rutaTemporal)) {
                unlink($rutaTemporal);
            }

            if ($resultado['status']) {
                registrarEnBitacora('Backup', 'IMPORTACION_DB', $idusuario, "Archivo: {$archivo['name']}, BD: {$baseDatos}");

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
            if (isset($rutaTemporal) && file_exists($rutaTemporal)) {
                unlink($rutaTemporal);
            }

            error_log("Error en backup_importarDB: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al importar base de datos: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

function backup_obtenerTablas()
{
    backup_verificarAcceso();

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
            $model = new BackupModel();
            $tablas = [];

            $stmtGeneral = $model->getDbGeneral()->query("SHOW TABLES");
            while ($row = $stmtGeneral->fetch(PDO::FETCH_NUM)) {
                $tablas[] = [
                    'name' => $row[0],
                    'base_datos' => 'general'
                ];
            }

            $stmtSeguridad = $model->getDbSeguridad()->query("SHOW TABLES");
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
            error_log("Error en backup_obtenerTablas: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'tablas' => [],
                'mensaje' => 'Error al obtener tablas: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

function backup_obtenerEstadisticas()
{
    backup_verificarAcceso();

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
            $model = new BackupModel();
            $resultado = $model->obtenerEstadisticasBackups();
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en backup_obtenerEstadisticas: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al obtener estadísticas'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
