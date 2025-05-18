<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/bitacoraModel.php"; 
class Permisos extends Controllers
{
    protected $model;

    public function __construct()
    {
        parent::__construct(); // aquí también se carga PermisosModel
        $this->model = new PermisosModel();
    }

     public function index()
    {
        session_start();
        $bitacora = new BitacoraModel();

        $idusuario = $_SESSION['user']['idusuario'] ?? null;

        if ($idusuario) {
            $bitacora->setTabla("Permisos");
            $bitacora->setAccion("vista");
            $bitacora->setIdUsuario($idusuario);
            // Si quieres puedes también setear la fecha manualmente, pero no es necesario
            $bitacora->setFecha(date("Y-m-d H:i:s"));
            $bitacora->insertar2();
        }

       $this->views->getView($this, "permisos");
    }


    public function personas()
    {

    }

  public function ConsultarPermisos()
{
    
    
    // Llamar al modelo para traer permisos (ya es un array)
    $resultado = $this->model->obtenerTodosLosPermisos();

    if ($resultado['success']) {
        echo json_encode($resultado);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron permisos.'
        ]);
    }
}

public function desactivar() 
{
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET"); // o POST si quieres cambiar

    // Leer id desde $_GET
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID del permiso no válido.'
        ]);
        return;
    }

    $idpermiso = intval($_GET['id']);

    $resultado = $this->model->desactivarPermiso($idpermiso);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Permiso desactivado correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al desactivar el permiso.'
        ]);
    }
}




}