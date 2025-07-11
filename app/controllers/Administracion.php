<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";

class Administracion extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // Solo usuarios con permisos de administrador pueden acceder
        if (!PermisosModuloVerificar::verificarAccesoModulo('usuarios') || 
            !PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'total')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Administracion', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Administración";
        $data['page_title'] = "Panel de Administración";
        $data['page_name'] = "administracion";
        $data['page_content'] = "Gestión de seguridad y backups del sistema";
        $data['page_functions_js'] = "functions_administracion.js";
        
        $this->views->getView($this, "administracion", $data);
    }

    public function crearBackup()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            // Verificar permisos de administrador total
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'total')) {
                BitacoraHelper::registrarError('administracion', 'Intento de crear backup sin permisos', $idusuario, $this->bitacoraModel);
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear backups');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $resultado = $this->model->crearBackupBaseDatos();

            if ($resultado['status']) {
                $detalle = "Backup creado exitosamente: " . ($resultado['archivo'] ?? 'N/A');
                BitacoraHelper::registrarAccion('administracion', 'CREAR_BACKUP', $idusuario, $this->bitacoraModel, $detalle);
            } else {
                BitacoraHelper::registrarError('administracion', $resultado['message'], $idusuario, $this->bitacoraModel);
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en crearBackup: " . $e->getMessage());
            BitacoraHelper::registrarError('administracion', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    public function listarBackups()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        try {
            // Verificar permisos
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'total')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para ver backups');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $resultado = $this->model->listarBackupsDisponibles();
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en listarBackups: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    public function descargarBackup()
    {
        try {
            // Verificar permisos
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'total')) {
                header('HTTP/1.0 403 Forbidden');
                echo "No tienes permisos para descargar backups";
                die();
            }

            $archivo = $_GET['archivo'] ?? '';
            if (empty($archivo)) {
                header('HTTP/1.0 400 Bad Request');
                echo "Archivo no especificado";
                die();
            }

            $resultado = $this->model->descargarBackup($archivo);

            if (!$resultado['status']) {
                header('HTTP/1.0 404 Not Found');
                echo $resultado['message'];
                die();
            }

            // Registrar descarga en bitácora
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            $detalle = "Backup descargado: " . $archivo;
            BitacoraHelper::registrarAccion('administracion', 'DESCARGAR_BACKUP', $idusuario, $this->bitacoraModel, $detalle);

            // Configurar headers para descarga
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');
            header('Content-Length: ' . filesize($resultado['ruta_completa']));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            // Enviar archivo
            readfile($resultado['ruta_completa']);

        } catch (Exception $e) {
            error_log("Error en descargarBackup: " . $e->getMessage());
            header('HTTP/1.0 500 Internal Server Error');
            echo "Error interno del servidor";
        }

        die();
    }

    public function eliminarBackup()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            // Verificar permisos
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'total')) {
                BitacoraHelper::registrarError('administracion', 'Intento de eliminar backup sin permisos', $idusuario, $this->bitacoraModel);
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar backups');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($request['archivo'])) {
                throw new Exception('Datos inválidos');
            }

            $resultado = $this->model->eliminarBackup($request['archivo']);

            if ($resultado['status']) {
                $detalle = "Backup eliminado: " . $request['archivo'];
                BitacoraHelper::registrarAccion('administracion', 'ELIMINAR_BACKUP', $idusuario, $this->bitacoraModel, $detalle);
            } else {
                BitacoraHelper::registrarError('administracion', $resultado['message'], $idusuario, $this->bitacoraModel);
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en eliminarBackup: " . $e->getMessage());
            BitacoraHelper::registrarError('administracion', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    public function getInfoSistema()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        try {
            // Verificar permisos
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'total')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para ver información del sistema');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $resultado = $this->model->obtenerInformacionSistema();
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en getInfoSistema: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        die();
    }
}
