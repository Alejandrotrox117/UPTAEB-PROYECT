<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class ModulosModel extends mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $message;
    private $status;

    private $idmodulo;
    private $titulo;
    private $descripcion;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
    }

    // GETTERS Y SETTERS DE CONTROL
    public function getQuery(){
        return $this->query;
    }
    public function setQuery(string $query){
        $this->query = $query; 
    }
    public function getArray(){ 
        return $this->array ?? []; 
    }
    public function setArray(array $array){
        $this->array = $array;
    }
    public function getData(){
        return $this->data ?? []; 
    }
    public function setData(array $data){
        $this->data = $data; 
    }
    public function getResult(){
        return $this->result;
    }
    public function setResult($result){
        $this->result = $result;
    }
    public function getMessage(){
        return $this->message ?? ''; 
    }
    public function setMessage(string $message){
        $this->message = $message;
    }
    public function getStatus(){
        return $this->status ?? false;
    }
    public function setStatus(bool $status){
        $this->status = $status;
    }

    // GETTERS Y SETTERS DE ENTIDAD
    public function setIdModulo($idmodulo){
        $this->idmodulo = $idmodulo;
    }
    public function getIdModulo(){
        return $this->idmodulo;
    }
    public function setTitulo($titulo){
        $this->titulo = $titulo;
    }
    public function getTitulo(){
        return $this->titulo;
    }
    public function setDescripcion($descripcion){
        $this->descripcion = $descripcion;
    }
    public function getDescripcion(){
        return $this->descripcion;
    }
    public function setEstatus($estatus){
        $this->estatus = $estatus;
    }
    public function getEstatus(){
        return $this->estatus;
    }

    // MÉTODOS PÚBLICOS
    public function insertModulo(array $data)
    {
        $this->setData($data);
        return $this->ejecutarInsercionModulo();
    }

    public function updateModulo(int $idmodulo, array $data)
    {
        $this->setIdModulo($idmodulo);
        $this->setData($data);
        return $this->ejecutarActualizacionModulo();
    }

    public function selectModuloById(int $idmodulo)
    {
        $this->setIdModulo($idmodulo);
        return $this->ejecutarConsultaModuloPorId();
    }

    public function deleteModuloById(int $idmodulo)
    {
        $this->setIdModulo($idmodulo);
        return $this->ejecutarEliminacionLogicaModulo();
    }

    public function selectAllModulosActivos()
    {
        return $this->ejecutarConsultaTodosModulosActivos();
    }

    public function getControlladoresDisponibles()
    {
        return $this->ejecutarConsultaControlladoresDisponibles();
    }

    // MÉTODOS PRIVADOS
    private function ejecutarVerificacionControllerExiste()
    {
        $nombreController = ucfirst(strtolower(trim($this->getTitulo())));
        $rutaController = "app/Controllers/" . $nombreController . ".php";
        return file_exists($rutaController);
    }

    private function ejecutarVerificacionModuloExiste()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM modulos WHERE LOWER(titulo) = LOWER(?)");
            $this->setArray([trim($this->getTitulo())]);
            
            if ($this->getIdModulo() !== null) {
                $this->setQuery("SELECT COUNT(*) as total FROM modulos WHERE LOWER(titulo) = LOWER(?) AND idmodulo != ?");
                $this->setArray([trim($this->getTitulo()), $this->getIdModulo()]);
            }
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            $result = $this->getResult();
            return $result['total'] > 0;
            
        } catch (Exception $e) {
            error_log("ModulosModel::ejecutarVerificacionModuloExiste - Error: " . $e->getMessage());
            return true;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInsercionModulo()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $data = $this->getData();
            $this->setTitulo($data['titulo']);
            $this->setDescripcion($data['descripcion']);

            if ($this->ejecutarVerificacionModuloExiste()) {
                return [
                    'status' => false,
                    'message' => 'Ya existe un módulo con ese título.',
                    'modulo_id' => null
                ];
            }

            if (!$this->ejecutarVerificacionControllerExiste()) {
                return [
                    'status' => false,
                    'message' => 'No existe un controlador con el nombre "' . ucfirst(strtolower($this->getTitulo())) . '.php". Debe crear el controlador antes de registrar el módulo.',
                    'modulo_id' => null
                ];
            }

            $dbSeguridad->beginTransaction();

            $this->setQuery("INSERT INTO modulos (titulo, descripcion, estatus, fecha_creacion, fecha_modificacion) VALUES (?, ?, ?, NOW(), NOW())");
            $this->setArray([
                $this->getTitulo(),
                $this->getDescripcion(),
                'ACTIVO'
            ]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $insertExitoso = $stmt->execute($this->getArray());

            $idModuloInsertado = $dbSeguridad->lastInsertId();

            if (!$idModuloInsertado) {
                $dbSeguridad->rollBack();
                error_log("ModulosModel::ejecutarInsercionModulo - Error: No se pudo obtener el lastInsertId");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de módulo tras registro.',
                    'modulo_id' => null
                ];
            }

            $dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Módulo registrado exitosamente (ID: ' . $idModuloInsertado . ').',
                'modulo_id' => $idModuloInsertado
            ];

        } catch (PDOException $e) {
            if ($dbSeguridad->inTransaction()) {
                $dbSeguridad->rollBack();
            }
            error_log("ModulosModel::ejecutarInsercionModulo - Error: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar módulo: ' . $e->getMessage(),
                'modulo_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarActualizacionModulo()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $data = $this->getData();
            $this->setTitulo($data['titulo']);
            $this->setDescripcion($data['descripcion']);

            if ($this->ejecutarVerificacionModuloExiste()) {
                return [
                    'status' => false,
                    'message' => 'Ya existe otro módulo con ese título.'
                ];
            }

            if (!$this->ejecutarVerificacionControllerExiste()) {
                return [
                    'status' => false,
                    'message' => 'No existe un controlador con el nombre "' . ucfirst(strtolower($this->getTitulo())) . '.php". Debe crear el controlador antes de actualizar el módulo.'
                ];
            }

            $dbSeguridad->beginTransaction();

            $this->setQuery("UPDATE modulos SET titulo = ?, descripcion = ?, fecha_modificacion = NOW() WHERE idmodulo = ?");
            $this->setArray([
                $this->getTitulo(),
                $this->getDescripcion(),
                $this->getIdModulo()
            ]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $updateExitoso = $stmt->execute($this->getArray());

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar el módulo o no se realizaron cambios.'
                ];
            }

            $dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Módulo actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($dbSeguridad->inTransaction()) {
                $dbSeguridad->rollBack();
            }
            error_log("ModulosModel::ejecutarActualizacionModulo - Error: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar módulo: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaModuloPorId()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT 
                        idmodulo,
                        titulo,
                        descripcion,
                        estatus,
                        fecha_creacion,
                        fecha_modificacion,
                        DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                        DATE_FORMAT(fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                    FROM modulos 
                    WHERE idmodulo = ?");
            
            $this->setArray([$this->getIdModulo()]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));
            
            return $this->getResult();
            
        } catch (Exception $e) {
            error_log("ModulosModel::ejecutarConsultaModuloPorId - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarEliminacionLogicaModulo()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $dbSeguridad->beginTransaction();

            $this->setQuery("UPDATE modulos SET estatus = 'INACTIVO', fecha_modificacion = NOW() WHERE idmodulo = ?");
            $this->setArray([$this->getIdModulo()]);
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            
            $dbSeguridad->commit();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $dbSeguridad->rollBack();
            error_log("ModulosModel::ejecutarEliminacionLogicaModulo - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaTodosModulosActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT 
                        idmodulo,
                        titulo,
                        descripcion,
                        estatus,
                        fecha_creacion,
                        fecha_modificacion,
                        DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion_formato,
                        DATE_FORMAT(fecha_modificacion, '%d/%m/%Y') as fecha_modificacion_formato
                    FROM modulos 
                    WHERE estatus = 'ACTIVO'
                    ORDER BY titulo ASC");

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return [
                "status" => true, 
                "message" => "Módulos obtenidos.", 
                "data" => $this->getResult()
            ];

        } catch (PDOException $e) {
            error_log("ModulosModel::ejecutarConsultaTodosModulosActivos - Error: " . $e->getMessage());
            return [
                "status" => false, 
                "message" => "Error al obtener módulos: " . $e->getMessage(), 
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaControlladoresDisponibles()
    {
        try {
            $controladores = [];
            $rutaControllers = "app/Controllers/";
            
            if (is_dir($rutaControllers)) {
                $archivos = scandir($rutaControllers);
                foreach ($archivos as $archivo) {
                    if (pathinfo($archivo, PATHINFO_EXTENSION) === 'php') {
                        $nombreController = pathinfo($archivo, PATHINFO_FILENAME);
                        if (!in_array($nombreController, ['Home', 'Error', 'Controllers'])) {
                            $controladores[] = [
                                'nombre' => $nombreController,
                                'archivo' => $archivo
                            ];
                        }
                    }
                }
            }
            
            return ["status" => true, "data" => $controladores];
            
        } catch (Exception $e) {
            error_log("ModulosModel::ejecutarConsultaControlladoresDisponibles - Error: " . $e->getMessage());
            return ["status" => false, "data" => []];
        }
    }
}
?>