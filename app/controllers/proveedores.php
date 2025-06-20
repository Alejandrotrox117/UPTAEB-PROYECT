<?php

require_once "app/core/Controllers.php";
require_once "app/models/proveedoresModel.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/expresiones_regulares.php";
require_once "helpers/bitacora_helper.php";
class Proveedores extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function get_model()
    {
        return $this->model;
    }
    public function set_model($model)
    {
        $this->model = $model;
    }

    public function __construct()
    {
        parent::__construct();
     
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

      if (!PermisosModuloVerificar::verificarAccesoModulo('proveedores')) {
            $this->views->getView($this, "permisos");
            exit();
        }

    }



    public function createProveedor()
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

             
                $datosLimpios = [
                    'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
                    'apellido' => ExpresionesRegulares::limpiar($request['apellido'] ?? '', 'apellido'),
                    'identificacion' => ExpresionesRegulares::limpiar($request['identificacion'] ?? '', 'cedula'),
                    'telefono_principal' => ExpresionesRegulares::limpiar($request['telefono_principal'] ?? '', 'telefono'),
                    'correo_electronico' => ExpresionesRegulares::limpiar($request['correo_electronico'] ?? '', 'email'),
                    'direccion' => trim($request['direccion'] ?? ''),
                    'fecha_nacimiento' => $request['fecha_nacimiento'] ?? null,
                    'genero' => strtoupper(trim($request['genero'] ?? '')),
                    'observaciones' => trim($request['observaciones'] ?? '')
                ];

        
                $camposObligatorios = ['nombre', 'apellido', 'identificacion', 'telefono_principal'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $reglasValidacion = [
                    'nombre' => 'nombre',
                    'apellido' => 'apellido',
                    'identificacion' => 'cedula',
                    'telefono_principal' => 'telefono'
                ];

                
                if (!empty($datosLimpios['correo_electronico'])) {
                    $reglasValidacion['correo_electronico'] = 'email';
                }

                
                if (!empty($datosLimpios['direccion'])) {
                    $reglasValidacion['direccion'] = 'direccion';
                }

                
                if (!empty($datosLimpios['genero'])) {
                    $reglasValidacion['genero'] = 'genero';
                }

                $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, $reglasValidacion);

      
                $errores = [];
                foreach ($resultadosValidacion as $campo => $resultado) {
                    if (!$resultado['valido']) {
                        $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
                    }
                }

       
                if (!empty($errores)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Errores de validación: ' . implode(' | ', $errores)
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }



              
                if (!empty($datosLimpios['fecha_nacimiento'])) {
                    $fechaOriginal = $datosLimpios['fecha_nacimiento'];

                    
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaOriginal)) {
                        $arrResponse = array(
                            'status' => false,
                            'message' => 'El formato de fecha de nacimiento debe ser YYYY-MM-DD'
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    
                    $fechaNacimiento = DateTime::createFromFormat('Y-m-d', $fechaOriginal);
                    if (!$fechaNacimiento) {
                        $arrResponse = array(
                            'status' => false,
                            'message' => 'Formato de fecha inválido'
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $fechaHoy = new DateTime();
                    
                    
                    if ($fechaNacimiento > $fechaHoy) {
                        $arrResponse = array(
                            'status' => false,
                            'message' => 'La fecha de nacimiento no puede ser posterior a la fecha actual'
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    
                    $fechaMinima = clone $fechaHoy;
                    $fechaMinima->sub(new DateInterval('P18Y')); 
                    
                    if ($fechaNacimiento > $fechaMinima) {
                        $edad = $fechaHoy->diff($fechaNacimiento)->y;
                        $arrResponse = array(
                            'status' => false,
                            'message' => "La persona debe ser mayor de 18 años. Edad actual: {$edad} años"
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

              
                    $datosLimpios['fecha_nacimiento'] = $fechaOriginal;
                } else {
               
                    $datosLimpios['fecha_nacimiento'] = null;
                }

                $arrData = array(
                    'nombre' => $datosLimpios['nombre'],
                    'apellido' => $datosLimpios['apellido'],
                    'identificacion' => $datosLimpios['identificacion'],
                    'telefono_principal' => $datosLimpios['telefono_principal'],
                    'correo_electronico' => $datosLimpios['correo_electronico'],
                    'direccion' => $datosLimpios['direccion'],
                    'fecha_nacimiento' => $datosLimpios['fecha_nacimiento'],
                    'genero' => $datosLimpios['genero'],
                    'observaciones' => $datosLimpios['observaciones']
                );

                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createProveedor()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertProveedor($arrData);

                
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('proveedor', 'INSERTAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación del proveedor ID: " .
                            ($arrResponse['proveedor_id'] ?? 'desconocido'));
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createProveedor: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

  

   public function index()
{
 
   $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
    BitacoraHelper::registrarAccesoModulo('proveedor', $idusuario, $this->bitacoraModel);

    $data['page_tag'] = "Proveedores";
    $data['page_title'] = "Administración de Proveedores";
    $data['page_name'] = "proveedores";
    $data['page_content'] = "Gestión integral de proveedores del sistema";
    $data['page_functions_js'] = "functions_proveedores.js";
    $this->views->getView($this, "proveedores", $data);
}

    public function getProveedoresData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectAllProveedores();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProveedoresData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProveedorById($idproveedor)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idproveedor) || !is_numeric($idproveedor)) {
                $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrData = $this->model->selectProveedorById(intval($idproveedor));
                if (!empty($arrData)) {
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Proveedor no encontrado');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProveedorById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }





    public function updateProveedor()
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


                $intIdProveedor = intval($request['idproveedor'] ?? 0);
                if ($intIdProveedor <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }


                $datosLimpios = [
                    'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
                    'apellido' => ExpresionesRegulares::limpiar($request['apellido'] ?? '', 'apellido'),
                    'identificacion' => ExpresionesRegulares::limpiar($request['identificacion'] ?? '', 'cedula'),
                    'telefono_principal' => ExpresionesRegulares::limpiar($request['telefono_principal'] ?? '', 'telefono'),
                    'correo_electronico' => ExpresionesRegulares::limpiar($request['correo_electronico'] ?? '', 'email'),
                    'direccion' => trim($request['direccion'] ?? ''),
                    'fecha_nacimiento' => $request['fecha_nacimiento'] ?? null,
                    'genero' => strtoupper(trim($request['genero'] ?? '')),
                    'observaciones' => trim($request['observaciones'] ?? '')
                ];


                $camposObligatorios = ['nombre', 'apellido', 'identificacion', 'telefono_principal'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $reglasValidacion = [
                    'nombre' => 'nombre',
                    'apellido' => 'apellido',
                    'identificacion' => 'cedula',
                    'telefono_principal' => 'telefono'
                ];

                
                if (!empty($datosLimpios['correo_electronico'])) {
                    $reglasValidacion['correo_electronico'] = 'email';
                }

                
                if (!empty($datosLimpios['direccion'])) {
                    $reglasValidacion['direccion'] = 'direccion';
                }

                
                if (!empty($datosLimpios['genero'])) {
                    $reglasValidacion['genero'] = 'genero';
                }


                $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, $reglasValidacion);

                $errores = [];
                foreach ($resultadosValidacion as $campo => $resultado) {
                    if (!$resultado['valido']) {
                        $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
                    }
                }


                if (!empty($errores)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Errores de validación: ' . implode(' | ', $errores)
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }


                
                if (!empty($datosLimpios['fecha_nacimiento'])) {
                    $fechaOriginal = $datosLimpios['fecha_nacimiento'];

                    
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaOriginal)) {
                        $arrResponse = array(
                            'status' => false,
                            'message' => 'El formato de fecha de nacimiento debe ser YYYY-MM-DD'
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    
                    $fechaNacimiento = DateTime::createFromFormat('Y-m-d', $fechaOriginal);
                    if (!$fechaNacimiento) {
                        $arrResponse = array(
                            'status' => false,
                            'message' => 'Formato de fecha inválido'
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $fechaHoy = new DateTime();
                    
                    
                    if ($fechaNacimiento > $fechaHoy) {
                        $arrResponse = array(
                            'status' => false,
                            'message' => 'La fecha de nacimiento no puede ser posterior a la fecha actual'
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    
                    $fechaMinima = clone $fechaHoy;
                    $fechaMinima->sub(new DateInterval('P18Y')); 
                    
                    if ($fechaNacimiento > $fechaMinima) {
                        $edad = $fechaHoy->diff($fechaNacimiento)->y;
                        $arrResponse = array(
                            'status' => false,
                            'message' => "La persona debe ser mayor de 18 años. Edad actual: {$edad} años"
                        );
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    
                    $datosLimpios['fecha_nacimiento'] = $fechaOriginal;
                } else {
                    
                    $datosLimpios['fecha_nacimiento'] = null;
                }

                $arrData = array(
                    'nombre' => $datosLimpios['nombre'],
                    'apellido' => $datosLimpios['apellido'],
                    'identificacion' => $datosLimpios['identificacion'],
                    'telefono_principal' => $datosLimpios['telefono_principal'],
                    'correo_electronico' => $datosLimpios['correo_electronico'],
                    'direccion' => $datosLimpios['direccion'],
                    'fecha_nacimiento' => $datosLimpios['fecha_nacimiento'],
                    'genero' => $datosLimpios['genero'],
                    'observaciones' => $datosLimpios['observaciones']
                );

                
              $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateProveedor()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }


                $arrResponse = $this->model->updateProveedor($intIdProveedor, $arrData);

                
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('proveedor', 'ACTUALIZAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del proveedor ID: " . $intIdProveedor);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateProveedor: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteProveedor()
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

                $intIdProveedor = intval($request['idproveedor'] ?? 0);
                if ($intIdProveedor <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $requestDelete = $this->model->deleteProveedorById($intIdProveedor, $idusuario);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Proveedor desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el proveedor');
                }
                if ($arrResponse['status'] === true) {
                    
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('proveedor', 'ELIMINAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del proveedor ID: " .
                            ($arrResponse['proveedor_id'] ?? 'desconocido'));
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteProveedor: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProveedoresActivos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectProveedoresActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProveedoresActivos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    
    public function activarProveedor()
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

                $intIdProveedor = intval($request['idproveedor'] ?? 0);
                if ($intIdProveedor <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }


               $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $requestActivar = $this->model->activarProveedorById($intIdProveedor, $idusuario);
                if ($requestActivar) {
                    $arrResponse = array('status' => true, 'message' => 'Proveedor activado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al activar el proveedor');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en activarProveedor: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
    public function exportarProveedores()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->get_model()->selectAllProveedores();

                if ($arrData['status']) {
                    $data['proveedores'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Proveedores";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');

                    $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarProveedores: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function buscarProveedor()
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

                $strTermino = strClean($request['termino'] ?? '');
                if (empty($strTermino)) {
                    $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = $this->get_model()->buscarProveedores($strTermino);
                if ($arrData['status']) {
                    $arrResponse = array('status' => true, 'data' => $arrData['data']);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en buscarProveedor: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
