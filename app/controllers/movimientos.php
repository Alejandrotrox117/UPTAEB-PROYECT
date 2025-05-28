<?php


require_once "app/core/Controllers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/helpers.php";

class Movimientos extends Controllers
{
    public function set_model($model)
    {
        $this->model = $model;
    }

    public function get_model()
    {
        return $this->model;
    }

    public function __construct()
    {
        parent::__construct();

        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si el usuario está logueado antes de verificar permisos
        if (!$this->verificarUsuarioLogueado()) {
            $this->redirigirLogin();
            return;
        }

        // Solo verificar permisos si está logueado
        permisosVerificar::verificarAccesoModulo('Movimientos');
    }

    /**
     * Verifica si el usuario está logueado
     */
    private function verificarUsuarioLogueado(): bool
    {
        $tieneLogin = isset($_SESSION['login']) && $_SESSION['login'] === true;
        $tieneIdUser = isset($_SESSION['idUser']) && !empty($_SESSION['idUser']);
        $tieneUsuarioId = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);

        return $tieneLogin && ($tieneIdUser || $tieneUsuarioId);
    }

    /**
     * Obtiene el ID del usuario de la sesión
     */
    private function obtenerIdUsuario(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    /**
     * Redirige al login
     */
    private function redirigirLogin()
    {
        if (function_exists('base_url')) {
            $loginUrl = base_url() . 'login';
        } else {
            $loginUrl = '/project/login';
        }

        header('Location: ' . $loginUrl);
        exit;
    }

    public function index()
    {
        // Doble verificación de seguridad
        if (!$this->verificarUsuarioLogueado()) {
            $this->redirigirLogin();
            return;
        }

        // Obtener ID del usuario
        $idUsuario = $this->obtenerIdUsuario();

        if (!$idUsuario) {
            error_log("Movimientos::index - No se pudo obtener ID de usuario");
            $this->redirigirLogin();
            return;
        }

        try {
            $permisos = PermisosHelper::getPermisosDetalle($idUsuario, 'Movimientos');
        } catch (Exception $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            // Permisos por defecto (sin acceso)
            $permisos = [
                'puede_ver' => false,
                'puede_crear' => false,
                'puede_editar' => false,
                'puede_eliminar' => false,
                'acceso_total' => false
            ];
        }

        $data['page_title'] = "Movimientos de existencias";
        $data['page_name'] = "Movimiento de inventario";
        $data['page_functions_js'] = "functions_movimientos.js";
        $data['permisos'] = $permisos;

        $this->views->getView($this, "movimientos", $data);
    }


public function getDetalleMovimiento()
{
    if (!$this->verificarUsuarioLogueado()) {
        echo json_encode(['status' => false, 'message' => 'No autenticado']);
        exit;
    }
    $id = intval($_GET['idmovimiento'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => false, 'message' => 'ID inválido']);
        exit;
    }
    $detalle = $this->model->obtenerMovimientoPorId($id);
    if ($detalle) {
        echo json_encode(['status' => true, 'data' => $detalle]);
    } else {
        echo json_encode(['status' => false, 'message' => 'No encontrado']);
    }
    exit;
}
public function getMovimientos()
{
    if (!$this->verificarUsuarioLogueado()) {
        echo json_encode(['status' => false, 'message' => 'No autenticado']);
        exit;
    }
    if (!permisosVerificar::verificarPermisoAccion('Movimientos', 'ver')) {
        echo json_encode(['status' => false, 'message' => 'Sin permiso']);
        exit;
    }
    $data = $this->model->selectAllMovimientos();
    echo json_encode(['status' => true, 'data' => $data]);
    exit;
}

public function crearMovimiento()
{
    if (!$this->verificarUsuarioLogueado()) {
        echo json_encode(['status' => false, 'message' => 'No autenticado']);
        exit;
    }
    if (!permisosVerificar::verificarPermisoAccion('Inventario', 'crear')) {
        echo json_encode(['status' => false, 'message' => 'Sin permiso']);
        exit;
    }
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    try {
        $result = $this->model->crearMovimiento($data);
        echo json_encode(['status' => true, 'message' => 'Movimiento registrado', 'id' => $result['idmovimiento']]);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
    // Aquí puedes agregar métodos similares a los de ventas para crear, editar, eliminar movimientos,
    // siguiendo el mismo patrón de permisos y validación de sesión.
}