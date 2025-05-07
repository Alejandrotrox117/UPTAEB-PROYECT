<?php
require_once("app/core/conexion.php");

class ModulosModel
{
    private $db;
    private $idmodulo;
    private $titulo;
    private $descripcion;
    private $estatus;
    private $fecha_creacion;
    private $fecha_modificacion;

    public function __construct()
    {
        $this->db = (new Conexion())->connect();
    }

    // ===========================
    // SETTERS
    // ===========================
    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;
    }

    public function setIdmodulo($idmodulo)
    {
        $this->idmodulo = $idmodulo;
    }

    public function setFechaCreacion($fecha_creacion)
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    public function setFechaModificacion($fecha_modificacion)
    {
        $this->fecha_modificacion = $fecha_modificacion;
    }


    // Puedes añadir más setters si los necesitas

    // ===========================
    // GETTERS
    // ===========================
    public function getEstatus()
    {
        return $this->estatus;
    }

    public function getTitulo()
    {
        return $this->titulo;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function getIdmodulo()
    {
        return $this->idmodulo;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    public function getFechaModificacion()
    {
        return $this->fecha_modificacion;
    }


    // ===========================
    // MÉTODOS FUNCIONALES
    // ===========================

    // Obtener módulos según estatus, o todos si no hay filtro
    public function getAllModulos()
    {
        // Consulta para obtener todos los módulos
        $sql = "SELECT idmodulo, titulo, descripcion, estatus, fecha_creacion, fecha_modificacion FROM modulos";

        // Ejecutar la consulta y devolver los resultados
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna los módulos como un arreglo asociativo
    }


    public function registrarModulo()
    {
        $this->setFechaCreacion(date('Y-m-d H:i:s'));  // Establecer la fecha de creación

        $sqlVerificar = "SELECT COUNT(*) FROM modulos WHERE titulo = :titulo";
        $stmtVerificar = $this->db->prepare($sqlVerificar);
        $stmtVerificar->execute([':titulo' => $this->getTitulo()]);
        $existe = $stmtVerificar->fetchColumn();
    
        if ($existe > 0) {
            return 'duplicado';
        }
    
    
        $sql = "INSERT INTO modulos (titulo, estatus, descripcion, fecha_creacion, fecha_modificacion) 
                VALUES (:titulo, :estatus, :descripcion, :fecha_creacion, :fecha_modificacion)";  // Consulta SQL para insertar
    
        $stmt = $this->db->prepare($sql);  // Preparar la consulta SQL
    
        // Ejecutar la consulta pasando los valores de los setters
        return $stmt->execute([
            ':titulo' => $this->getTitulo(),
            ':estatus' => $this->getEstatus(),
            ':descripcion' => $this->getDescripcion(),
            ':fecha_creacion' => $this->getFechaCreacion(),
            ':fecha_modificacion'=>$this->getFechaCreacion()
        ]);
    }
    




    public function getModuloById($id)
    {
        $sql = "SELECT * FROM modulos WHERE idmodulo = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $this->setIdmodulo($data['idmodulo']);
            $this->setTitulo($data['titulo']);
            $this->setDescripcion($data['descripcion']);
            $this->setEstatus($data['estatus']);
            $this->setFechaCreacion($data['fecha_creacion']);
            $this->setFechaModificacion($data['fecha_modificacion']);
        }

        return $data;
    }

    public function eliminarModulo($id)
{
    $this->setFechaModificacion(date('Y-m-d H:i:s')); // Establece la última modificación

    // Actualiza el estatus del módulo a 'Inactivo'
    $sql = "UPDATE modulos SET estatus = 'Inactivo', fecha_modificacion = :fecha WHERE idmodulo = :id";
    $stmt = $this->db->prepare($sql);

    // Ejecuta la consulta con los parámetros correspondientes
    return $stmt->execute([
        ':id' => $id,
        ':fecha' => $this->getFechaModificacion()
    ]);
}



public function actualizarModulo()
{
    $this->setFechaModificacion(date('Y-m-d H:i:s'));  // Establecer la fecha de la última modificación

    // Obtener el título original desde la base de datos antes de hacer la comparación
    $sqlOriginal = "SELECT titulo FROM modulos WHERE idmodulo = :idmodulo";
    $stmtOriginal = $this->db->prepare($sqlOriginal);
    $stmtOriginal->execute([':idmodulo' => $this->getIdmodulo()]);
    $tituloOriginal = $stmtOriginal->fetchColumn();  // Esto obtendrá el título original del módulo

    // Verificar si el título ha cambiado. Si ha cambiado, validamos duplicados.
    if ($this->getTitulo() !== $tituloOriginal) {
        $sqlVerificar = "SELECT COUNT(*) FROM modulos WHERE titulo = :titulo AND idmodulo != :idmodulo";
        $stmtVerificar = $this->db->prepare($sqlVerificar);
        $stmtVerificar->execute([':titulo' => $this->getTitulo(), ':idmodulo' => $this->getIdmodulo()]);
        $existe = $stmtVerificar->fetchColumn();

        if ($existe > 0) {
            return 'duplicado';  // Ya existe un módulo con el mismo título
        }
    }

    // Si el título no es duplicado, proceder con la actualización
    $sql = "UPDATE modulos 
            SET titulo = :nombre, estatus = :estatus, descripcion = :descripcion, fecha_modificacion = :ultima_modificacion
            WHERE idmodulo = :idmodulo";  // Solo actualizamos el módulo con el ID correspondiente

    $stmt = $this->db->prepare($sql);  // Preparar la consulta SQL

    // Ejecutar la consulta pasando los valores de los setters
    return $stmt->execute([
        ':idmodulo' => $this->getIdmodulo(),  // Asegúrate de tener este método
        ':nombre' => $this->getTitulo(),
        ':estatus' => $this->getEstatus(),
        ':descripcion' => $this->getDescripcion(),
        ':ultima_modificacion' => $this->getFechaModificacion()
    ]);
}







}
?>