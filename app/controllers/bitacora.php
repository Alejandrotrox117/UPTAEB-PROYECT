<?php
require_once "app/core/Controllers.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "helpers/bitacora_helper.php";

class Bitacora extends Controllers
{
    private $BitacoraHelper;

    public function __construct()
    {
        parent::__construct();
        $this->model = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        // Verificar si el usuario está logueado
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // ✅ CAMBIAR AL SISTEMA NUEVO DE PERMISOS
        if (!PermisosModuloVerificar::verificarAccesoModulo('bitacora')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        // Verificar permisos específicos para ver
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        // Registrar acceso al módulo
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Bitacora', $idusuario, $this->model);

        $data['page_title'] = "Bitácora del Sistema";
        $data['page_name'] = "Bitácora";
        $data['page_functions_js'] = "functions_bitacora.js";

        // Obtener permisos para la vista
        $data['permisos'] = [
            'puede_ver' => PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver'),
            'puede_exportar' => PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver'), // Si puede ver, puede exportar
            'puede_filtrar' => PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')
        ];

        $this->views->getView($this, "bitacora", $data);
    }

    public function getBitacoraData()
    {
        header('Content-Type: application/json');

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para ver la bitácora"
            ]);
            exit();
        }

        try {
            // Obtener filtros de la solicitud
            $filtros = [];
            if (!empty($_POST['modulo'])) {
                $filtros['tabla'] = $_POST['modulo'];
            }
            if (!empty($_POST['fecha_desde'])) {
                $filtros['fecha_desde'] = $_POST['fecha_desde'];
            }
            if (!empty($_POST['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_POST['fecha_hasta'];
            }
            if (!empty($_POST['usuario'])) {
                $filtros['idusuario'] = $_POST['usuario'];
            }

            $arrData = $this->model->obtenerHistorial($filtros);

            // Formatear datos para DataTables
            $data = [];
            foreach ($arrData as $row) {
                $data[] = [
                    'idbitacora' => $row['idbitacora'],
                    'tabla' => strtoupper($row['tabla']),
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
            error_log("Error en getBitacoraData: " . $e->getMessage());
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener datos de bitácora"
            ]);
        }
        exit();
    }

    public function getBitacoraById($idbitacora)
    {
        header('Content-Type: application/json');

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para ver detalles de bitácora"
            ]);
            exit();
        }

        try {
            $idbitacora = intval($idbitacora);
            if ($idbitacora <= 0) {
                echo json_encode([
                    "status" => false,
                    "message" => "ID de bitácora inválido"
                ]);
                exit();
            }

            $bitacora = $this->model->obtenerRegistroPorId($idbitacora);

            if ($bitacora) {
                echo json_encode([
                    "status" => true, 
                    "data" => [
                        'id' => $bitacora['idbitacora'],
                        'modulo' => strtoupper($bitacora['tabla']),
                        'accion' => $bitacora['accion'],
                        'usuario' => $bitacora['nombre_usuario'] ?? 'Usuario desconocido',
                        'fecha' => $this->formatearFecha($bitacora['fecha']),
                        'fecha_raw' => $bitacora['fecha']
                    ]
                ]);
            } else {
                echo json_encode([
                    "status" => false, 
                    "message" => "Registro de bitácora no encontrado"
                ]);
            }
        } catch (Exception $e) {
            error_log("Error en getBitacoraById: " . $e->getMessage());
            echo json_encode([
                "status" => false, 
                "message" => "Error interno del servidor"
            ]);
        }
        exit();
    }

    public function getModulosDisponibles()
    {
        header('Content-Type: application/json');

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos"
            ]);
            exit();
        }

        try {
            $modulos = $this->model->obtenerModulosDisponibles();
            echo json_encode([
                "status" => true,
                "data" => $modulos
            ]);
        } catch (Exception $e) {
            error_log("Error en getModulosDisponibles: " . $e->getMessage());
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener módulos"
            ]);
        }
        exit();
    }

    public function limpiarBitacora()
    {
        header('Content-Type: application/json');

        // Solo administradores pueden limpiar bitácora
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'eliminar')) {
            echo json_encode([
                "status" => false,
                "message" => "No tienes permisos para limpiar la bitácora"
            ]);
            exit();
        }

        try {
            $dias = intval($_POST['dias'] ?? 30);
            if ($dias < 1) {
                echo json_encode([
                    "status" => false,
                    "message" => "El número de días debe ser mayor a 0"
                ]);
                exit();
            }

            $registrosEliminados = $this->model->limpiarRegistrosAntiguos($dias);

            // Registrar la acción de limpieza
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            BitacoraHelper::registrarAccion('Bitacora', 'LIMPIEZA', $idusuario, $this->model, 
                "Eliminados {$registrosEliminados} registros anteriores a {$dias} días");

            echo json_encode([
                "status" => true,
                "message" => "Se eliminaron {$registrosEliminados} registros antiguos",
                "registros_eliminados" => $registrosEliminados
            ]);
        } catch (Exception $e) {
            error_log("Error en limpiarBitacora: " . $e->getMessage());
            echo json_encode([
                "status" => false,
                "message" => "Error al limpiar la bitácora"
            ]);
        }
        exit();
    }

    // Métodos privados auxiliares
    private function formatearAccion($accion)
    {
        $acciones = [
            'ACCESO_MODULO' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">ACCESO</span>',
            'INSERTAR' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">CREAR</span>',
            'ACTUALIZAR' => '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">ACTUALIZAR</span>',
            'ELIMINAR' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">ELIMINAR</span>',
            'VER' => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">VER</span>',
            'LOGIN' => '<span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">LOGIN</span>',
            'LOGOUT' => '<span class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">LOGOUT</span>',
            'ERROR' => '<span class="px-2 py-1 text-xs font-medium bg-red-200 text-red-900 rounded-full">ERROR</span>',
            'LIMPIEZA' => '<span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">LIMPIEZA</span>'
        ];

        return $acciones[$accion] ?? '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">' . $accion . '</span>';
    }

    private function formatearFecha($fecha)
    {
        try {
            return date('d/m/Y H:i:s', strtotime($fecha));
        } catch (Exception $e) {
            return $fecha; // Devolver fecha original si hay error
        }
    }

    private function generarBotonesAccion($idbitacora)
    {
        $botones = '';
        
        if (PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
            $botones .= '<button type="button" class="text-blue-600 hover:text-blue-800 p-1 transition-colors duration-150 btn-ver-detalle" data-id="' . $idbitacora . '" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>';
        }

        return $botones;
    }
}
?>