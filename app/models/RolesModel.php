<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class RolesModel extends Mysql
{
    private $conn;
    public function __construct()
    {
        $this->conn = new Conexion();
    }


    function guardarRol()
    {
        $conn = $this->conn->connect();

        // Leer los datos enviados en formato JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(['success' => false, 'message' => 'Error al procesar los datos JSON: ' . json_last_error_msg()]);
        }

        // Obtener los datos del JSON
        $nombre = isset($data['nombre']) ? $data['nombre'] : '';
        $estatus = 'Activo';
        $descripcion = isset($data['descripcion']) ? $data['descripcion'] : '';

        // Validar los datos recibidos
        if (empty($nombre) || strlen($nombre) > 255) {
            return json_encode(['success' => false, 'message' => 'El nombre es obligatorio y no puede tener más de 255 caracteres']);
        }

        if (!in_array($estatus, ['Activo', 'Inactivo'])) {
            return json_encode(['success' => false, 'message' => 'El estatus debe ser "Activo" o "Inactivo"']);
        }

        if (isset($descripcion) && strlen($descripcion) > 255) {
            return json_encode(['success' => false, 'message' => 'La descripción no puede tener más de 255 caracteres']);
        }

        // Escapar los datos para evitar inyección SQL
        $nombre = mysqli_real_escape_string($conn, $nombre);
        $estatus = mysqli_real_escape_string($conn, $estatus);
        $descripcion = !empty($descripcion) ? mysqli_real_escape_string($conn, $descripcion) : null;



        // Insertar la fecha y hora actual (puedes usar la función NOW() de MySQL)
        $fecha_creacion = date('Y-m-d H:i:s'); // Obtener la fecha y hora actuales



        // Insertar el rol en la base de datos
        $sql = "INSERT INTO roles (nombre, estatus, descripcion, fecha_creacion) 
        VALUES ('$nombre', '$estatus', '$descripcion', '$fecha_creacion')";

        if (mysqli_query($conn, $sql)) {
            return json_encode(['success' => true, 'message' => 'Rol guardado correctamente']);
        } else {
            return json_encode(['success' => false, 'message' => 'Error al guardar el rol: ' . mysqli_error($conn)]);
        }
    }


    public function get_Roles()
    {
        $conn = $this->conn->connect();
        session_start();

        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idrol'])) {
            return json_encode(['success' => false, 'message' => 'No se pudo identificar el rol del usuario.']);
        }

        $userRole = $_SESSION['user']['idrol'];

        if ($userRole == 3) { // Root
            $sql = "SELECT idrol, nombre, estatus, descripcion FROM roles";
        } elseif ($userRole == 1) { // Administrador
            $sql = "SELECT idrol, nombre, estatus, descripcion 
                FROM roles 
                WHERE estatus = 'Activo' 
                AND idrol != 3";
        } else {
            return json_encode(['success' => false, 'message' => 'Rol de usuario no autorizado para consultar roles.']);
        }

        $result = mysqli_query($conn, $sql);

        if (!$result) {
            return json_encode(['success' => false, 'message' => 'Error en la consulta: ' . mysqli_error($conn)]);
        }

        $roles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = [
                'id' => $row['idrol'],
                'nombre' => $row['nombre'],
                'estatus' => $row['estatus'],
                'descripcion' => $row['descripcion']
            ];
        }

        return json_encode(['success' => true, 'roles' => $roles]);
    }


    public function rol()
    {
        // Asegurarnos de que el 'id' esté presente en la URL.
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']); // Sanitizar el ID para evitar inyecciones SQL

            // Obtener la conexión a la base de datos
            $conn = $this->conn->connect();

            // Consulta para obtener los datos del rol por ID
            $sql = "SELECT nombre, estatus, descripcion FROM roles WHERE idrol = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta.']);
                return;
            }

            // Asociar el parámetro de entrada (ID)
            $stmt->bind_param("i", $id);

            // Ejecutar la consulta
            $stmt->execute();

            // Obtener el resultado
            $result = $stmt->get_result();

            // Verificar si se encontró el rol
            if ($result->num_rows > 0) {
                $rol = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'rol' => $rol
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Rol no encontrado.'
                ]);
            }

            // Cerrar la declaración y la conexión
            $stmt->close();
            $conn->close();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'ID no proporcionado.'
            ]);
        }
    }


    public function eliminar_roles()
    {
        if (isset($_GET['id'])) 
        {
            $id = intval($_GET['id']); // Sanitizar el ID para evitar inyección SQL
    
            // Obtener la conexión a la base de datos
            $conn = $this->conn->connect();
    
            if (!$conn) {
                // Error de conexión
                echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
                return;
            }
    
            try {
                // Preparar y ejecutar la consulta para eliminar el rol
                $stmt = $conn->prepare("UPDATE roles SET estatus = 'Inactivo' WHERE idrol = ?
");
                $stmt->bind_param('i', $id);
    
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Rol eliminado correctamente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el rol.']);
                }
    
                $stmt->close();
                $conn->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } 
        else 
        {
            echo json_encode(['success' => false, 'message' => 'ID no recibido.']);
        }
    }
    







}
?>