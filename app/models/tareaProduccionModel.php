<?php
require_once "app/core/mysql.php";

class TareaProduccionModel extends Mysql
{
    // Atributos privados
    private $idtarea;
    private $idproduccion;
    private $idempleado;
    private $cantidad_asignada;
    private $cantidad_realizada;
    private $estado;
    private $fecha_inicio;
    private $fecha_fin;
    private $observaciones;

    private $db;
    private $conexion;
    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
        // Obtener el ID del usuario desde la sesión
        $idUsuario = $this->obtenerIdUsuarioSesion();

        if ($idUsuario) {
            // Establecer la variable de sesión SQL
            $this->setUsuarioActual($idUsuario);
        }
    }
    private function obtenerIdUsuarioSesion(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    // Método para establecer @usuario_actual en MySQL
    private function setUsuarioActual(int $idUsuario)
    {
        $sql = "SET @usuario_actual = $idUsuario";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("No se pudo establecer @usuario_actual: " . $e->getMessage());
        }
    }
    

    // Getters
    public function getIdTarea()
    {
        return $this->idtarea;
    }
    public function getIdProduccion()
    {
        return $this->idproduccion;
    }
    public function getIdEmpleado()
    {
        return $this->idempleado;
    }
    public function getCantidadAsignada()
    {
        return $this->cantidad_asignada;
    }
    public function getCantidadRealizada()
    {
        return $this->cantidad_realizada;
    }
    public function getEstado()
    {
        return $this->estado;
    }
    public function getFechaInicio()
    {
        return $this->fecha_inicio;
    }
    public function getFechaFin()
    {
        return $this->fecha_fin;
    }
    public function getObservaciones()
    {
        return $this->observaciones;
    }

    // Setters con validación
    public function setIdTarea($id)
    {
        if ($id !== null && (!is_numeric($id) || $id <= 0)) {
            throw new InvalidArgumentException("ID de tarea inválido.");
        }
        $this->idtarea = $id;
    }

    public function setIdProduccion($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("ID de producción inválido.");
        }
        $this->idproduccion = $id;
    }

    public function setIdEmpleado($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("ID de empleado inválido.");
        }
        $this->idempleado = $id;
    }

    public function setCantidadAsignada($cant)
    {
        if (!is_numeric($cant) || $cant <= 0) {
            throw new InvalidArgumentException("La cantidad asignada debe ser un número positivo.");
        }
        $this->cantidad_asignada = $cant;
    }

    public function setCantidadRealizada($cant)
    {
        if (!is_numeric($cant) || $cant < 0) {
            throw new InvalidArgumentException("La cantidad realizada debe ser un número mayor o igual a cero.");
        }
        if ($cant > $this->cantidad_asignada) {
            throw new InvalidArgumentException("No puedes realizar más de lo asignado.");
        }
        $this->cantidad_realizada = $cant;
    }

    public function setEstado($estado)
    {
        $estadosValidos = ['pendiente', 'en_progreso', 'completado'];
        if (!in_array($estado, $estadosValidos)) {
            throw new InvalidArgumentException("Estado inválido.");
        }
        $this->estado = $estado;
    }

    public function setFechaInicio($fecha)
    {
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha)) {
            throw new InvalidArgumentException("Formato de fecha inválido. Use YYYY-MM-DD");
        }
        $this->fecha_inicio = $fecha;
    }

    public function setFechaFin($fecha)
    {
        if ($fecha && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha)) {
            throw new InvalidArgumentException("Formato de fecha fin inválido.");
        }
        $this->fecha_fin = $fecha;
    }

    public function setObservaciones($obs)
    {
        $this->observaciones = htmlspecialchars(strip_tags($obs), ENT_QUOTES, 'UTF-8');
    }

    // Método para insertar una nueva tarea
    public function insertTarea(array $data)
    {
        try {
            $this->setIdEmpleado($data['idempleado']);
            $this->setIdProduccion($data['idproduccion']);
            $this->setCantidadAsignada($data['cantidad_asignada']);
            $this->setFechaInicio($data['fecha_inicio']);
            $this->setFechaFin($data['fecha_fin'] ?? null);
            $this->setObservaciones($data['observaciones'] ?? '');
            $this->setEstado($data['estado'] ?? 'pendiente');

            $sql = "INSERT INTO tarea_produccion (
                        idproduccion, idempleado, cantidad_asignada, cantidad_realizada,
                        estado, fecha_inicio, fecha_fin, observaciones
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->getIdProduccion(),
                $this->getIdEmpleado(),
                $this->getCantidadAsignada(),
                $this->getCantidadRealizada(),
                $this->getEstado(),
                $this->getFechaInicio(),
                $this->getFechaFin(),
                $this->getObservaciones()
            ]);

            return true;
        } catch (Exception $e) {
            error_log("TareaProduccionModel - Error al insertar tarea: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar una tarea existente
    public function updateTarea(array $data)
    {
        try {

            error_log("Datos recibidos: " . json_encode($data));


            $idtarea = isset($data['idtarea']) ? (int)$data['idtarea'] : null;
            $cantidad = isset($data['cantidad_realizada']) ? (int)$data['cantidad_realizada'] : null;

            if ($idtarea === null || $cantidad === null) {
                error_log("Datos inválidos para actualizar.");
                return false;
            }


            $sql = "UPDATE tarea_produccion SET cantidad_realizada = ? WHERE idtarea = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cantidad, $idtarea]);

            if ($stmt->rowCount() > 0) {
                error_log("Tarea actualizada con éxito.");
                return true;
            } else {
                error_log("No se afectó ninguna fila con idtarea = $idtarea (posible dato idéntico o ID no existe).");
                return false;
            }
        } catch (Exception $e) {
            error_log("TareaProduccionModel - Error al actualizar tarea: " . $e->getMessage());
            return false;
        }
    }






    // Obtener todas las tareas de una producción
    public function getTareasByProduccion($idproduccion)
    {
        $sql = "SELECT tp.*, e.nombre AS nombre_empleado 
                FROM tarea_produccion tp
                JOIN empleado e ON tp.idempleado = e.idempleado
                WHERE tp.idproduccion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar producción si se completan todas las tareas
    public function actualizarProduccionSiCompletada($idproduccion)
    {
        $sql = "SELECT SUM(cantidad_realizada) AS total_realizado, p.cantidad_a_realizar
                FROM tarea_produccion tp
                JOIN produccion p ON tp.idproduccion = p.idproduccion
                WHERE tp.idproduccion = ?
                GROUP BY p.cantidad_a_realizar";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idproduccion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;

        if ($row['total_realizado'] >= $row['cantidad_a_realizar']) {
            $update_sql = "UPDATE produccion SET estado = 'realizado' WHERE idproduccion = ?";
            $update_stmt = $this->db->prepare($update_sql);
            return $update_stmt->execute([$idproduccion]);
        }

        return false;
    }
}
