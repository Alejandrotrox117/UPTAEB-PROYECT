<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\NotificacionesModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

class Notificaciones extends Controllers
{
    private $BitacoraHelper;
    public function __construct()
    {
        parent::__construct();
        $this->model = new NotificacionesModel();
        $this->BitacoraHelper = new BitacoraHelper();
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }

    private function obtenerUsuarioSesion()
    {
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        if ($idusuario) {
            return $idusuario;  
        } else {
            return null;
        }
    }


    public function getNotificaciones()
    {
        // LOG DE DEPURACIÓN - INICIO
        error_log(" getNotificaciones - INICIADO");
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            error_log(" getNotificaciones - Método GET verificado");
            
            try {
                $usuarioId = $this->obtenerUsuarioSesion();
                error_log(" getNotificaciones - Usuario ID obtenido: " . ($usuarioId ?: 'NULL'));
                
                if (!$usuarioId) {
                    error_log(" getNotificaciones - ERROR: Usuario no autenticado");
                    $arrResponse = array(
                        'status' => false, 
                        'message' => 'Usuario no autenticado', 
                        'data' => []
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
                error_log(" getNotificaciones - Rol ID obtenido: " . ($rolId ?: 'NULL'));
                
                if (!$rolId) {
                    error_log(" getNotificaciones - ERROR: No se pudo obtener el rol del usuario");
                    $arrResponse = array(
                        'status' => false, 
                        'message' => 'No se pudo obtener el rol del usuario', 
                        'data' => []
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener parámetros opcionales
                $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
                $soloNoLeidas = isset($_GET['no_leidas']) ? (bool)$_GET['no_leidas'] : false;

                error_log(" getNotificaciones - Llamando al modelo para obtener notificaciones");
                $arrResponse = $this->model->obtenerNotificacionesPorUsuario($usuarioId, $rolId);
                error_log(" getNotificaciones - Respuesta del modelo: " . json_encode($arrResponse));
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log(" getNotificaciones - EXCEPCIÓN: " . $e->getMessage());
                $response = array(
                    'status' => false, 
                    'message' => 'Error interno del servidor', 
                    'data' => []
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        } else {
            error_log(" getNotificaciones - ERROR: Método no es GET, es: " . $_SERVER['REQUEST_METHOD']);
        }
    }

    public function getContadorNotificaciones()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $usuarioId = $this->obtenerUsuarioSesion();
                
                $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
                
                if (!$rolId) {
                    $arrResponse = array(
                        'status' => false, 
                        'message' => 'No se pudo obtener el rol del usuario', 
                        'data' => []
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (!$usuarioId || !$rolId) {
                    echo json_encode(array('count' => 0), JSON_UNESCAPED_UNICODE);
                    die();
                }

                $count = $this->model->contarNotificacionesNoLeidas($usuarioId, $rolId);
                echo json_encode(array('count' => $count), JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getContadorNotificaciones: " . $e->getMessage());
                echo json_encode(array('count' => 0), JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function marcarLeida()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $notificacionId = intval($request['idnotificacion'] ?? 0);
                $usuarioId = $this->obtenerUsuarioSesion();

                if ($notificacionId <= 0 || !$usuarioId) {
                    $arrResponse = array('status' => false, 'message' => 'Datos inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultado = $this->model->marcarComoLeida($notificacionId, $usuarioId);
                
                if ($resultado) {
                    $arrResponse = array('status' => true, 'message' => 'Notificación marcada como leída');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al marcar notificación');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en marcarLeida: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function marcarTodasLeidas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $usuarioId = $this->obtenerUsuarioSesion();
                
                $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
                
                if (!$rolId) {
                    $arrResponse = array(
                        'status' => false, 
                        'message' => 'No se pudo obtener el rol del usuario', 
                        'data' => []
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (!$usuarioId || !$rolId) {
                    $arrResponse = array('status' => false, 'message' => 'Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultado = $this->model->marcarTodasComoLeidas($usuarioId, $rolId);
                
                $arrResponse = array(
                    'status' => true, 
                    'message' => "Se marcaron {$resultado} notificaciones como leídas"
                );

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en marcarTodasLeidas: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function generarNotificacionesProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $resultado = $this->model->generarNotificacionesProductos();
                
                if ($resultado) {
                    $arrResponse = array('status' => true, 'message' => 'Notificaciones de productos generadas correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al generar notificaciones de productos');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en generarNotificacionesProductos: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // Método temporal de depuración - REMOVER DESPUÉS DE SOLUCIONAR
    public function diagnosticar()
    {
        header('Content-Type: application/json');
        
        try {
            $usuarioId = $this->obtenerUsuarioSesion();
            $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
            
            if (!$usuarioId || !$rolId) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Usuario o rol no válido',
                    'usuario_id' => $usuarioId,
                    'rol_id' => $rolId
                ], JSON_UNESCAPED_UNICODE);
                die();
            }
            
            $diagnostico = $this->model->diagnosticarNotificaciones($usuarioId, $rolId);
            echo json_encode([
                'status' => true,
                'usuario_id' => $usuarioId,
                'rol_id' => $rolId,
                'diagnostico' => $diagnostico
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // MÉTODO TEMPORAL DE PRUEBA - ELIMINAR DESPUÉS
    public function getNotificacionesSimple()
    {
        error_log(" getNotificacionesSimple - INICIADO");
        
        // Respuesta de prueba
        $response = [
            'status' => true,
            'message' => 'Método temporal funcionando',
            'data' => [
                [
                    'idnotificacion' => 999,
                    'tipo' => 'TEST',
                    'titulo' => 'Notificación de Prueba',
                    'mensaje' => 'Esta es una notificación de prueba para verificar que el controlador funciona',
                    'fecha_formato' => date('d/m/Y H:i'),
                    'leida' => 0
                ]
            ],
            'debug_info' => [
                'session_usuario_id' => $_SESSION['usuario_id'] ?? 'No definido',
                'session_rol_id' => $_SESSION['rol_id'] ?? 'No definido',
                'session_login' => $_SESSION['login'] ?? 'No definido',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'No definido'
            ]
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        error_log(" getNotificacionesSimple - RESPUESTA ENVIADA");
        die();
    }
}
?>