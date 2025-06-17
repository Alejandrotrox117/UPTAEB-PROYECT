<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php"; // Asegúrate de incluir el helper de permisos

class Romana extends Controllers
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
        // Verificar permisos de acceso al módulo Romana
        permisosVerificar::verificarAccesoModulo('romana');
    }

    // Vista principal para gestionar pesajes de romana
    public function index()
    {
        $data['page_title'] = "Gestión de Romanas";
        $data['page_name'] = "Romana";
        $data['page_functions_js'] = "functions_romana.js";
        
        // Agregar permisos a los datos
        $data['permisos'] = [
            'puede_ver' => PermisosVerificar::verificarPermisoAccion('romana', 'ver'),
            'puede_crear' => PermisosVerificar::verificarPermisoAccion('romana', 'crear'),
            'puede_editar' => PermisosVerificar::verificarPermisoAccion('romana', 'editar'),
            'puede_eliminar' => PermisosVerificar::verificarPermisoAccion('romana', 'eliminar')
        ];
        
        $this->views->getView($this, "romana", $data);
    }

    // Obtener datos de Pesos de Romana para DataTables (encapsulado)
    public function getRomanaData()
    {
        $arrData = $this->get_model()->selectAllRomana();

        // Si el modelo devuelve un array con 'status' y 'data'
        if (isset($arrData['data'])) {
            $data = $arrData['data'];
        } else {
            $data = $arrData;
        }

        $response = [
            "recordsTotal" => count($data),
            "recordsFiltered" => count($data),
            "data" => $data
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
}