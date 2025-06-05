<?php
require_once "app/core/Controllers.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
class Bitacora extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new bitacoraModel();
    }

    // Vista principal para gestionar bitácora
    public function index()
    {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // // Verificar si el usuario está logueado antes de verificar permisos
        // if (!$this->verificarUsuarioLogueado()) {
        //     $this->redirigirLogin();
        //     return;
        // }

        // Solo verificar permisos si está logueado
        permisosVerificar::verificarAccesoModulo('Bitacora');

        $data['page_title'] = "Bitácora del Sistema";
        $data['page_name'] = "Bitácora";
        $data['page_functions_js'] = "functions_bitacora.js";
        $this->views->getView($this, "bitacora", $data);
    }

    // Obtener datos de bitácora para DataTables
    public function getBitacoraData()
    {
        // Verificar permisos
        if (!PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para ver la bitácora"
            ]);
            exit();
        }

        try {
            $arrData = $this->model->SelectAllBitacora();

            // Formatear datos para DataTables
            $data = [];
            foreach ($arrData as $row) {
                $data[] = [
                    'idbitacora' => $row['idbitacora'],
                    'tabla' => $row['tabla'],
                    'accion' => $this->formatearAccion($row['accion']),
                    'usuario' => $row['nombre_usuario'] ?? 'Usuario desconocido',
                    'fecha' => $this->formatearFecha($row['fecha']),
                    'acciones' => $this->generarBotonesAccion($row['idbitacora'])
                ];
            }

            $response = [
                "draw" => intval($_POST['draw'] ?? 1),
                "recordsTotal" => count($data),
                "recordsFiltered" => count($data),
                "data" => $data
            ];

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener datos de bitácora: " . $e->getMessage()
            ]);
        }
        exit();
    }

    // Obtener un registro de bitácora por ID
    public function getBitacoraById($idbitacora)
    {
        // Verificar permisos
        if (!PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para ver detalles de bitácora"
            ]);
            exit();
        }

        try {
            $bitacora = $this->model->getBitacoraById($idbitacora);

            if ($bitacora) {
                echo json_encode(["status" => true, "data" => $bitacora]);
            } else {
                echo json_encode(["status" => false, "message" => "Registro de bitácora no encontrado."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    // Filtrar bitácora por fecha
    public function filtrarPorFecha()
    {
        // Verificar permisos
        if (!PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para filtrar bitácora"
            ]);
            exit();
        }

        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin = $_POST['fecha_fin'] ?? '';

            if (empty($fechaInicio) || empty($fechaFin)) {
                echo json_encode([
                    "status" => false,
                    "message" => "Debe proporcionar fecha de inicio y fin"
                ]);
                exit();
            }

            $arrData = $this->model->filtrarPorFecha($fechaInicio, $fechaFin);

            // Formatear datos
            $data = [];
            foreach ($arrData as $row) {
                $data[] = [
                    'idbitacora' => $row['idbitacora'],
                    'tabla' => $row['tabla'],
                    'accion' => $this->formatearAccion($row['accion']),
                    'usuario' => $row['nombre_usuario'] ?? 'Usuario desconocido',
                    'fecha' => $this->formatearFecha($row['fecha']),
                    'acciones' => $this->generarBotonesAccion($row['idbitacora'])
                ];
            }

            echo json_encode([
                "status" => true,
                "data" => $data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => "Error al filtrar bitácora: " . $e->getMessage()
            ]);
        }
        exit();
    }

    // Filtrar por módulo/tabla
    public function filtrarPorModulo()
    {
        // Verificar permisos
        if (!PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para filtrar bitácora"
            ]);
            exit();
        }

        try {
            $modulo = $_POST['modulo'] ?? '';

            if (empty($modulo)) {
                echo json_encode([
                    "status" => false,
                    "message" => "Debe especificar un módulo"
                ]);
                exit();
            }

            $arrData = $this->model->filtrarPorModulo($modulo);

            // Formatear datos
            $data = [];
            foreach ($arrData as $row) {
                $data[] = [
                    'idbitacora' => $row['idbitacora'],
                    'tabla' => $row['tabla'],
                    'accion' => $this->formatearAccion($row['accion']),
                    'usuario' => $row['nombre_usuario'] ?? 'Usuario desconocido',
                    'fecha' => $this->formatearFecha($row['fecha']),
                    'acciones' => $this->generarBotonesAccion($row['idbitacora'])
                ];
            }

            echo json_encode([
                "status" => true,
                "data" => $data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => "Error al filtrar por módulo: " . $e->getMessage()
            ]);
        }
        exit();
    }

    // Obtener módulos disponibles para filtro
    public function getModulosDisponibles()
    {
        try {
            $modulos = $this->model->getModulosDisponibles();
            echo json_encode([
                "status" => true,
                "data" => $modulos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener módulos: " . $e->getMessage()
            ]);
        }
        exit();
    }

    // Métodos privados auxiliares
    private function formatearAccion($accion)
    {
        $acciones = [
            'INSERTAR' => '<span class="badge badge-success">CREAR</span>',
            'ACTUALIZAR' => '<span class="badge badge-warning">ACTUALIZAR</span>',
            'ELIMINAR' => '<span class="badge badge-danger">ELIMINAR</span>',
            'VER' => '<span class="badge badge-info">VER</span>',
            'LOGIN' => '<span class="badge badge-primary">LOGIN</span>',
            'LOGOUT' => '<span class="badge badge-secondary">LOGOUT</span>'
        ];

        return $acciones[$accion] ?? '<span class="badge badge-light">' . $accion . '</span>';
    }

    private function formatearFecha($fecha)
    {
        return date('d/m/Y H:i:s', strtotime($fecha));
    }

    private function generarBotonesAccion($idbitacora)
    {
        $botones = '';
        
        if (PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')) {
            $botones .= '<button type="button" class="btn btn-info btn-sm" onclick="verDetalleBitacora(' . $idbitacora . ')" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>';
        }

        return $botones;
    }
}