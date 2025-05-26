<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Proveedores extends Controllers
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
    }
    public function index()
    {
        $data['page_title'] = "Gestión de Proveedores";
        $data['page_name'] = "Proveedores";
        $data['page_functions_js'] = "functions_proveedores.js"; // Tu nuevo archivo JS
        $this->views->getView($this, "proveedores", $data); // Tu nueva vista HTML
    }

    public function getProveedoresData(){
        $arrData = $this->model->selectAllProveedores(); 

        echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function createProveedor()
    {
        // if (!is_ajax()) { /* ... */ }
        if ($_POST) { 
            // Sanitizar y validar datos de $_POST
            $data = [
                'nombre' => $_POST['nombre'] ?? null,
                'apellido' => $_POST['apellido'] ?? null,
                'identificacion' => $_POST['identificacion'] ?? null,
                'telefono_principal' => $_POST['telefono_principal'] ?? null,
                'correo_electronico' => $_POST['correo_electronico'] ?? null, FILTER_SANITIZE_EMAIL,
                'direccion' => $_POST['direccion'] ?? null,
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null, // Validar formato de fecha
                'genero' => $_POST['genero'] ?? null,
                'estatus' => $_POST['estatus'] ?? 'ACTIVO',
                'observaciones' => $_POST['observaciones'] ?? null,
            ];

            // Validaciones adicionales aquí (ej. que identificación no exista)

            $request = $this->model->insertProveedor($data);


            if ($request) {
                $response = ['status' => true, 'message' => 'Proveedor registrado con éxito.'];
            } else {
                $response = ['status' => false, 'message' => 'No se pudo registrar el proveedor. Verifique los datos o la identificación podría ya existir.'];
            }
        } else {
            $response = ['status' => false, 'message' => 'Método no permitido.'];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }

   public function createProveedorinCompras()
    {
        $response = ['status' => false, 'message' => 'Error desconocido.'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => filter_var($_POST['nombre'] ?? null, FILTER_SANITIZE_STRING),
                'apellido' => filter_var($_POST['apellido'] ?? null, FILTER_SANITIZE_STRING),
                'identificacion' => filter_var($_POST['identificacion'] ?? null, FILTER_SANITIZE_STRING),
                'telefono_principal' => filter_var($_POST['telefono_principal'] ?? null, FILTER_SANITIZE_STRING),
                'correo_electronico' => filter_var($_POST['correo_electronico'] ?? null, FILTER_SANITIZE_EMAIL),
                'direccion' => filter_var($_POST['direccion'] ?? null, FILTER_SANITIZE_STRING),
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                'genero' => filter_var($_POST['genero'] ?? null, FILTER_SANITIZE_STRING),
                'estatus' => filter_var($_POST['estatus'] ?? 'ACTIVO', FILTER_SANITIZE_STRING),
                'observaciones' => filter_var($_POST['observaciones'] ?? null, FILTER_SANITIZE_STRING),
            ];

            if (!empty($data['correo_electronico']) && !filter_var($data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
                $response = ['status' => false, 'message' => 'Formato de correo electrónico inválido.'];
                header('Content-Type: application/json');
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }
            
            if (!empty($data['fecha_nacimiento'])) {
                $d = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
                if (!$d || $d->format('Y-m-d') !== $data['fecha_nacimiento']) {
                    $response = ['status' => false, 'message' => 'Formato de fecha de nacimiento inválido. Use YYYY-MM-DD.'];
                    header('Content-Type: application/json');
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            if (empty($data['nombre']) || empty($data['identificacion']) || empty($data['telefono_principal'])) {
                 $response = ['status' => false, 'message' => 'Nombre, Identificación y Teléfono son obligatorios.'];
                 header('Content-Type: application/json');
                 echo json_encode($response, JSON_UNESCAPED_UNICODE);
                 die();
            }

            $request = $this->model->insertProveedorbackid($data);

            if ($request !== false && $request > 0) {
                $response = [
                    'status' => true,
                    'message' => 'Proveedor registrado con éxito.',
                    'idproveedor' => $request['idproveedor'] ?? null
                ];
            } elseif ($request === false) {
                $response = ['status' => false, 'message' => 'Error al registrar el proveedor.'];
            } elseif (is_array($request) && isset($request['error'])) {
                $response = ['status' => false, 'message' => $request['error']];

            } else {
                $response = ['status' => false, 'message' => 'Error al registrar el proveedor en la base de datos.'];
            }
        } else {
            $response = ['status' => false, 'message' => 'Método no permitido.'];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }


    public function getProveedorById(int $idproveedor)
    {
        $id = intval($idproveedor);
        if ($id > 0) {
            $data = $this->model->getProveedorById($id);
            if ($data) {
                $response = ['status' => true, 'data' => $data];
            } else {
                $response = ['status' => false, 'message' => 'Proveedor no encontrado.'];
            }
        } else {
            $response = ['status' => false, 'message' => 'ID de proveedor no válido.'];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function updateProveedor()
    {
        // if (!is_ajax()) { /* ... */ }
        if ($_SERVER['REQUEST_METHOD'] == "POST") { // O PUT si tu JS y servidor lo manejan
            // Sanitizar y validar datos de $_POST
            $idproveedor = intval($_POST['idproveedor']);
            var_dump($idproveedor);
            if ($idproveedor > 0) {
                $this->model->setIdpersona($idproveedor);
                $this->model->setNombre($_POST['nombre'] ?? null);
                $this->model->setApellido($_POST['apellido'] ?? null);
                $this->model->setIdentificacion($_POST['identificacion'] ?? null);
                $this->model->setTelefonoPrincipal($_POST['telefono_principal'] ?? null);
                $this->model->setCorreoElectronico($_POST['correo_electronico'] ?? null, FILTER_SANITIZE_EMAIL);
                $this->model->setDireccion($_POST['direccion'] ?? null);
                $this->model->setFechaNacimiento($_POST['fecha_nacimiento'] ?? null);
                $this->model->setGenero($_POST['genero'] ?? null);
                $this->model->setEstatus($_POST['estatus'] ?? 'ACTIVO');
                $this->model->setObservaciones($_POST['observaciones'] ?? null);

                // Validaciones adicionales (ej. que la nueva identificación no exista para otro proveedor)

                $request = $this->model->updateProveedor();
                if ($request) {
                    $response = ['status' => true, 'message' => 'Proveedor actualizado con éxito.'];
                } else {
                    $response = ['status' => false, 'message' => 'No se pudo actualizar el proveedor. Verifique los datos.'];
                }
            } else {
                 $response = ['status' => false, 'message' => 'ID de proveedor no válido para actualizar.'];
            }
        } else {
            $response = ['status' => false, 'message' => 'Método no permitido.'];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function deleteProveedor()
    {
        // if (!is_ajax()) { /* ... */ }
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $data = json_decode(file_get_contents('php://input'), true); // Si envías JSON
            $idproveedor = intval($data['idpersona'] ?? 0);

            if ($idproveedor > 0) {
                $request = $this->model->deleteProveedor($idproveedor);
                if ($request) {
                    $response = ['status' => true, 'message' => 'Proveedor desactivado con éxito.'];
                } else {
                    $response = ['status' => false, 'message' => 'No se pudo desactivar el proveedor.'];
                }
            } else {
                $response = ['status' => false, 'message' => 'ID de proveedor no válido.'];
            }
        } else {
            $response = ['status' => false, 'message' => 'Método no permitido.'];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }
}
?>
