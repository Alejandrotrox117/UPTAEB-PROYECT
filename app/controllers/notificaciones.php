<?php
require_once "app/core/Controllers.php";
require_once "app/models/notificacionesModel.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";

class Notificaciones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new NotificacionesModel();

        if (!$this->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }

    private function obtenerUsuarioSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['idusuario'])) {
            return $_SESSION['idusuario'];
        } elseif (isset($_SESSION['idUser'])) {
            return $_SESSION['idUser'];
        } else {
            return null;
        }
    }


    public function getNotificaciones()
    {
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

                // Obtener el rol desde el modelo
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
                $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
                $soloNoLeidas = isset($_GET['no_leidas']) ? (bool)$_GET['no_leidas'] : false;

                $arrResponse = $this->model->obtenerNotificacionesPorUsuario($usuarioId, $rolId, $limite, $soloNoLeidas);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log("Error en getNotificaciones: " . $e->getMessage());
                $response = array(
                    'status' => false, 
                    'message' => 'Error interno del servidor', 
                    'data' => []
                );
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getContadorNotificaciones()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $usuarioId = $this->obtenerUsuarioSesion();
                // Obtener el rol desde el modelo
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
                $postdata = file_get_contents("php://input");
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
                // Obtener el rol desde el modelo
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
}
?>