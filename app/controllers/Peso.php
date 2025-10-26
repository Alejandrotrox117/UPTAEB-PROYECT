<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";

class Peso extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;
    private $moduloClave = 'compras';

    public function __construct()
    {
        parent::__construct();

        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        if (!PermisosModuloVerificar::verificarAccesoModulo($this->moduloClave)) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion($this->moduloClave, 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('peso', $idusuario, $this->bitacoraModel);

        $resultadoPeso = $this->model->obtenerUltimoPeso();

        $data = [
            'page_tag' => 'Romana',
            'page_title' => 'Último Peso Registrado',
            'page_name' => 'peso',
            'page_functions_js' => 'functions_peso.js',
            'ultimo_peso' => $resultadoPeso['data'] ?? null,
            'ultimo_peso_status' => $resultadoPeso['status'] ?? false,
            'ultimo_peso_message' => $resultadoPeso['message'] ?? null,
        ];

        $this->views->getView($this, "peso", $data);
    }

    public function getUltimoPeso()
    {
        header('Content-Type: application/json');

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion($this->moduloClave, 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para consultar el peso.',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Leer directamente del archivo JSON que actualiza el script Python
        $filePath = 'C:\com_data\peso_mysql.json';
        
        if (!file_exists($filePath)) {
            echo json_encode([
                'status' => false,
                'message' => 'Archivo de peso no encontrado'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);
        
        if ($data === null) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al leer datos de peso'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        echo json_encode([
            'status' => true,
            'data' => [
                'peso' => $data["peso_numerico"],
                'fecha' => $data["fecha_hora"],
                'estado' => $data["estado"] ?? 'ACTIVO',
                'variacion' => $data["variacion"] ?? '0',
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function formatearFechaHora($fecha)
    {
        if (!$fecha) {
            return null;
        }

        try {
            $date = new DateTime($fecha, new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone('America/Caracas'));
            return $date->format('d/m/Y h:i:s A');
        } catch (Exception $e) {
            error_log('Peso::formatearFechaHora - Error: ' . $e->getMessage());
            return $fecha;
        }
    }
}
