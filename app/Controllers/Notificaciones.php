<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\NotificacionesModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use Exception;

class Notificaciones extends Controllers
{
    private $BitacoraHelper;
    public function __construct()
    {
        parent::__construct();
        $this->model = new NotificacionesModel();
        $this->BitacoraHelper = new BitacoraHelper();
        
        // Solo verificar sesión si no es una solicitud AJAX a /notificaciones/getNotificaciones
        // o si es un método que requiere autenticación
        $usuarioId = $this->BitacoraHelper->obtenerUsuarioSesion();
        
        // Si es una solicitud AJAX, configurar header JSON y permitir sin autenticación
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        if (!$usuarioId) {
            // Si es AJAX, devolver JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['status' => false, 'message' => 'No autenticado']);
                die();
            }
            // Si no, redirigir a login
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
       
        
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            
            
            try {
                $usuarioId = $this->obtenerUsuarioSesion();
               
                
                if (!$usuarioId) {
                   
                    $arrResponse = array(
                        'status' => false, 
                        'message' => 'Usuario no autenticado', 
                        'data' => []
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

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

                // Obtener parámetros opcionales
                $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
                $soloNoLeidas = isset($_GET['no_leidas']) ? (bool)$_GET['no_leidas'] : false;

               
                $arrResponse = $this->model->obtenerNotificacionesPorUsuario($usuarioId, $rolId);
               
                
                // Si la respuesta no tiene status, agregar
                if (!isset($arrResponse['status'])) {
                    $arrResponse['status'] = true;
                }
                if (!isset($arrResponse['data'])) {
                    $arrResponse['data'] = [];
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
               
                $response = array(
                    'status' => false, 
                    'message' => 'Error interno del servidor', 
                    'data' => []
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        } else {
           
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
    public function obtenerNotificaciones()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $usuarioId = $this->obtenerUsuarioSesion();
                
                if (!$usuarioId) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'Usuario no autenticado', 
                        'data' => []
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
                
                if (!$rolId) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'No se pudo obtener el rol del usuario', 
                        'data' => []
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener notificaciones no leídas
                $arrResponse = $this->model->obtenerNotificacionesPorUsuario($usuarioId, $rolId);
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                
                
                echo json_encode([
                    'status' => false, 
                    'message' => 'Error interno del servidor', 
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getNotificacionesSimple()
    {
       
        
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
       
        die();
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die();
        }

        try {
            $usuarioId = $this->obtenerUsuarioSesion();
            if (!$usuarioId) {
                echo json_encode(['status' => false, 'message' => 'No autenticado']);
                die();
            }

            $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['idnotificacion'])) {
                echo json_encode(['status' => false, 'message' => 'ID inválido']);
                die();
            }

            $resultado = $this->model->marcarComoLeidaCompleto($data['idnotificacion'], $usuarioId, $rolId);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
        die();
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die();
        }

        try {
            $usuarioId = $this->obtenerUsuarioSesion();
            if (!$usuarioId) {
                echo json_encode(['status' => false, 'message' => 'No autenticado']);
                die();
            }

            $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
            $resultado = $this->model->marcarTodasComoLeidasCompleto($usuarioId, $rolId);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
        die();
    }

    /**
     * Eliminar una notificación
     */
    public function eliminarNotificacion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            die();
        }

        try {
            $usuarioId = $this->obtenerUsuarioSesion();
            if (!$usuarioId) {
                echo json_encode(['status' => false, 'message' => 'No autenticado']);
                die();
            }

            $rolId = $this->model->obtenerRolPorUsuario($usuarioId);
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['idnotificacion'])) {
                echo json_encode(['status' => false, 'message' => 'ID inválido']);
                die();
            }

            $resultado = $this->model->eliminarNotificacion($data['idnotificacion'], $usuarioId, $rolId);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
        die();
    }
}