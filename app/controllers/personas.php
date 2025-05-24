<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";


class personas extends Controllers
{

    public function __construct()
    {
        parent::__construct();
        $this->model = new PersonasModel();
    }

    public function index()
    {
        $this->views->getView($this, "personas");
    }

    public function personas($params = null)
    {
        $data['page_id'] = 1;
        $data["page_title"] = "Pagina principal";
        $data["tag_page"] = "La pradera de pavia";
        $data["page_name"] = "Personas";

        // Verifica si hay parámetros
        if ($params) {
            echo "Parámetros recibidos: " . $params;
        }

        $this->views->getView($this, "personas", $data);
    }


    public function ConsultarPersonas()
    {
        session_start();
        
        $user = $_SESSION['userData']['idrol'];

        $resultados = $this->model->ConsultarPersonas($user);

        // Verificar si se encontraron resultados
        if ($resultados) {
            // Si hay resultados, devolver success: true
            echo json_encode([
                'success' => true,
                'personas' => $resultados
            ]);
        } else {
            // Si no se encontraron resultados, devolver success: false
            echo json_encode([
                'success' => false,
                'message' => 'No se encontraron personas.'
            ]);
        }
    }

    public function consultarunaPersona()
    {
        header("Content-Type: application/json");

        // Verificar si el parámetro 'id' está presente en la URL
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
            return;
        }

        // Sanitizar el parámetro 'id' para evitar inyecciones SQL
        $id = intval($_GET['id']);

        // Verificar que el id sea válido
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        // Obtener el rol por ID
        $persona = $this->model->getpersonaById($id);

        // Verificar si el rol existe
        if ($persona) {
            echo json_encode(['success' => true, 'persona' => $persona]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Persona no encontrado.']);
        }
    }


    public function eliminar()
    {
        // Verificar que el parámetro ID está presente
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Falta el parámetro ID.']);
            return;
        }

        // Sanitizar el parámetro 'id' para evitar inyecciones SQL
        $id = intval($_GET['id']);

        // Iniciar sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start(); // Inicia la sesión si no está iniciada
        }

        // Verificar si el usuario está autenticado y si la clave 'user' existe en la sesión
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idpersona'])) {
            echo json_encode(['success' => false, 'message' => 'No estás autenticado.']);
            return;
        }

        $usuarioAutenticadoId = $_SESSION['user']['idpersona']; // Obtener el ID del usuario autenticado

        // Verificar que no sea el usuario autenticado quien está intentando desactivarse
        if ($usuarioAutenticadoId == $id) {
            // Si el usuario intenta desactivarse a sí mismo, lanzar un error
            echo json_encode(['success' => false, 'message' => 'No puedes desactivarte a ti mismo.']);
            return;
        }

        // Llamar al método eliminarUsuario para desactivar tanto el usuario como la persona
        $resultado = $this->model->eliminarUsuario($id); // Asegúrate de que este método maneje correctamente los parámetros

        // Verificar si la desactivación fue exitosa
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Usuario y persona desactivados correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al desactivar usuario y persona.']);
        }
    }


    public function guardar()
    {
        // Asegúrate de que la solicitud sea POST
        try {
            header("Content-Type: application/json");
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: POST");
            header("Access-Control-Allow-Headers: Content-Type");

            $data = json_decode(file_get_contents('php://input'), true);
            // Obtener los datos JSON del cuerpo de la solicitud


            // Si los datos no son válidos, devolver un error
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'Datos no válidos.']);
                return;
            }



            // Recibir datos del formulario desde el JSON
            $nombre = $data['nombre'] ?? '';
            $apellido = $data['apellido'] ?? '';
            $cedula = $data['cedula'] ?? '';
            $rif = $data['rif'] ?? '';
            $telefono = $data['telefono'] ?? '';
            $tipo = $data['tipo'] ?? '';
            $genero = $data['genero'] ?? '';
            $fechaNacimiento = $data['fecha_nacimiento'] ?? '';
            $estado = $data['estado'] ?? '';
            $ciudad = $data['ciudad'] ?? '';
            $pais = $data['pais'] ?? '';
            $observaciones = $data['observaciones'] ?? '';
            $crearUsuario = $data['crear_usuario'] ?? '0';
            $correo = $data['correo_electronico'] ?? '';
            $clave = $data['clave'] ?? '';
            $rol = $data['rol'] ?? '';

            // Validar datos (ejemplo simple)
            if (empty($nombre) || empty($apellido) || empty($cedula)) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
                return;
            }

            // Si se crea un usuario, encriptar la clave
            if ($crearUsuario == '1' && !empty($clave)) {
                $clave = password_hash($clave, PASSWORD_DEFAULT);
            } else {
                $clave = null;
            }

            // Llamar al modelo para insertar en la base de datos
            $resultado = $this->model->guardar_usuario([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'cedula' => $cedula,
                'rif' => $rif,
                'telefono' => $telefono,
                'tipo' => $tipo,
                'genero' => $genero,
                'fecha_nacimiento' => $fechaNacimiento,
                'estado' => $estado,
                'ciudad' => $ciudad,
                'pais' => $pais,
                'observaciones' => $observaciones,
                'correo' => $correo,
                'clave' => $clave,
                'rol' => $rol,
                'crear_usuario' => $crearUsuario
            ]);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Registro exitoso.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo registrar.']);
            }
        } catch (\Throwable $th) {
            echo json_encode(['success' => false, 'message' => $th->getMessage()]);

        }

    }


    public function obtenerPersonaPorId()
    {
        $id = $_GET['id'] ?? null;
    header('Content-Type: application/json');

    if ($id === null) {
        echo json_encode(['success' => false, 'message' => 'Falta el parámetro id']);
        exit;
    }

    $resultado = $this->model->buscarunapersona($id);

    // Si $resultado es un arreglo con 'success', es error
    if (isset($resultado['success']) && $resultado['success'] === false) {
        echo json_encode($resultado);
        exit;
    }

    // En caso contrario, devolvemos success true con los datos
    echo json_encode(['success' => true, 'usuario' => $resultado]);
    exit;
    }



   public function editar()
{

    header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Allow-Headers: Content-Type");

    // Suponiendo que recibes datos por POST con JSON
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);

    // Validación manual
    $errores = [];

  
    if (empty($datos['nombre']) || !preg_match('/^[a-zA-ZÁÉÍÓÚÑáéíóúñ\s]{2,50}$/u', $datos['nombre'])) {
        $errores[] = 'Nombre inválido.';
    }

    if (empty($datos['apellido']) || !preg_match('/^[a-zA-ZÁÉÍÓÚÑáéíóúñ\s]{2,50}$/u', $datos['apellido'])) {
        $errores[] = 'Apellido inválido.';
    }

    if (empty($datos['cedula']) || !preg_match('/^\d{5,12}$/', $datos['cedula'])) {
        $errores[] = 'Cédula inválida.';
    }

    if (empty($datos['rif']) || !preg_match('/^[VEJPG]-\d{6,10}$/', $datos['rif'])) {
        $errores[] = 'RIF inválido.';
    }

    if (empty($datos['telefono_principal']) || !preg_match('/^\d{10,15}$/', $datos['telefono_principal'])) {
        $errores[] = 'Teléfono inválido.';
    }

    if (empty($datos['tipo']) || !in_array($datos['tipo'], ['admin', 'usuario', 'otro'])) {
        $errores[] = 'Tipo inválido.';
    }

    if (empty($datos['genero']) || !in_array($datos['genero'], ['masculino', 'femenino', 'otro'])) {
        $errores[] = 'Género inválido.';
    }

    if (empty($datos['fecha_nacimiento']) || !strtotime($datos['fecha_nacimiento'])) {
        $errores[] = 'Fecha de nacimiento inválida.';
    }

    if (empty($datos['estado']) || strlen($datos['estado']) > 100) {
        $errores[] = 'Estado inválido.';
    }

    if (empty($datos['ciudad']) || strlen($datos['ciudad']) > 100) {
        $errores[] = 'Ciudad inválida.';
    }

    if (empty($datos['pais']) || strlen($datos['pais']) > 100) {
        $errores[] = 'País inválido.';
    }

    if (!empty($datos['observaciones']) && strlen($datos['observaciones']) > 500) {
        $errores[] = 'Observaciones demasiado largas.';
    }

    if (empty($datos['correo_electronico']) || !filter_var($datos['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Correo electrónico inválido.';
    }

    if (empty($datos['clave']) || strlen($datos['clave']) < 6 || strlen($datos['clave']) > 16) {
        $errores[] = 'Clave inválida.';
    }

    if (empty($datos['rol']) || !in_array($datos['rol'], ['admin', 'moderador', 'cliente'])) {
        $errores[] = 'Rol inválido.';
    }

    // Si hay errores, devolverlos
    if (!empty($errores)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['errores' => $errores]);
        return;
    }

    

    $resultado = $this->model->actualizarUsuario($datos);

    if ($resultado) {
        echo json_encode(['mensaje' => 'Usuario actualizado correctamente.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar usuario.']);
    }
}










}