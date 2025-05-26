<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "app/models/bitacoraModel.php"; 
class modulos extends Controllers
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ModulosModel();
    }

    public function index()
    {
        session_start();
        $bitacora = new BitacoraModel();

        $idusuario = $_SESSION['user']['idusuario'] ?? null;

        if ($idusuario) {
            $bitacora->setTabla("Modulos");
            $bitacora->setAccion("vista");
            $bitacora->setIdUsuario($idusuario);
            // Si quieres puedes también setear la fecha manualmente, pero no es necesario
            $bitacora->setFecha(date("Y-m-d H:i:s"));
            $bitacora->insertar2();
        }

        $this->views->getView($this, "modulos");
    }



    public function Consultar_modulos()
    {
        // Establecer la cabecera de la respuesta como JSON
        header('Content-Type: application/json');
        // Llamar al método getAllModulos() del modelo
        $modulos = $this->model->getAllModulos();



        // Devolver los módulos en formato JSON
        echo json_encode([
            'success' => true,
            'modulos' => $modulos
        ]);

    }

    public function guardarModulo()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Allow-Headers: Content-Type");
        header('Content-Type: application/json; charset=utf-8');


        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Error al procesar los datos JSON: ' . json_last_error_msg()]);
            return;
        }

        // Verificar que los datos obligatorios estén presentes
        if (empty($data['titulo']) || empty($data['estatus']) || empty($data['descripcion'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        try {
            // Usar los setters para asignar los valores del módulo
            $this->model->setTitulo($data['titulo']); // Asumiendo que "titulo" es el nombre del módulo
            $this->model->setDescripcion($data['descripcion']);
            $this->model->setEstatus($data['estatus']);

            // Llamar al método para actualizar el módulo
            $resultado = $this->model->registrarModulo();

            if ($resultado === 'duplicado') {
                echo json_encode(['success' => false, 'message' => 'Ya existe un módulo con ese título.']);
            } elseif ($resultado === true) {
                echo json_encode(['success' => true, 'message' => 'Módulo Guardado Correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo Guardar el Módulo.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el módulo: ' . $e->getMessage()]);
        }
    }



    public function consultarunmodulo()
    {
        header("Content-Type: application/json");

        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
            return;
        }

        $rol = $this->model->getmoduloById($_GET['id']);

        if ($rol) {
            echo json_encode(['success' => true, 'rol' => $rol]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Rol no encontrado.']);
        }
    }

    public function eliminar()
    {
        header("Content-Type: application/json");

        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
            return;
        }

        $result = $this->model->eliminarModulo($_GET['id']);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Modulo eliminado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el Modulo.']);
        }
    }

    public function actualizarModulo()
    {
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Allow-Headers: Content-Type");

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Error al procesar los datos JSON: ' . json_last_error_msg()]);
            return;
        }

        // Verificar que los datos obligatorios estén presentes
        if (empty($data['id']) || empty($data['titulo']) || empty($data['estatus']) || empty($data['descripcion'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        try {
            // Usar los setters para asignar los valores del módulo
            $this->model->setIdmodulo($data['id']);
            $this->model->setTitulo($data['titulo']); // Asumiendo que "titulo" es el nombre del módulo
            $this->model->setDescripcion($data['descripcion']);
            $this->model->setEstatus($data['estatus']);

            // Llamar al método para actualizar el módulo
            $resultado = $this->model->actualizarModulo();

            if ($resultado === 'duplicado') {
                echo json_encode(['success' => false, 'message' => 'Ya existe un módulo con ese título.']);
            } elseif ($resultado === true) {
                echo json_encode(['success' => true, 'message' => 'Módulo actualizado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo Guardar el Módulo.']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el módulo: ' . $e->getMessage()]);
        }
    }







}