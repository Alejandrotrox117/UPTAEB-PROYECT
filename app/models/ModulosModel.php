<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class ModulosModel extends mysql
{
    private $conexion;
    private $dbPrincipal;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbPrincipal = $this->conexion->get_conectGeneral();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    private function verificarControllerExiste(string $titulo): bool
    {
        $nombreController = ucfirst(strtolower(trim($titulo)));
        $rutaController = "app/Controllers/" . $nombreController . ".php";
        return file_exists($rutaController);
    }

    private function verificarModuloExiste(string $titulo, int $idModuloExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM modulos WHERE LOWER(titulo) = LOWER(?)";
        $params = [trim($titulo)];
        
        if ($idModuloExcluir !== null) {
            $sql .= " AND idmodulo != ?";
            $params[] = $idModuloExcluir;
        }
        
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar módulo existente: " . $e->getMessage());
            return true;
        }
    }

    public function insertModulo(array $data): array
    {
        try {
            $titulo = $data['titulo'];
            $descripcion = $data['descripcion'];

            if ($this->verificarModuloExiste($titulo)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe un módulo con ese título.',
                    'modulo_id' => null
                ];
            }

            if (!$this->verificarControllerExiste($titulo)) {
                return [
                    'status' => false,
                    'message' => 'No existe un controlador con el nombre "' . ucfirst(strtolower($titulo)) . '.php". Debe crear el controlador antes de registrar el módulo.',
                    'modulo_id' => null
                ];
            }

            $this->dbSeguridad->beginTransaction();

            $sql = "INSERT INTO modulos (titulo, descripcion, estatus, fecha_creacion, fecha_modificacion) VALUES (?, ?, ?, NOW(), NOW())";
            
            $valores = [
                $titulo,
                $descripcion,
                'ACTIVO'
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $insertExitoso = $stmt->execute($valores);

            $idModuloInsertado = $this->dbSeguridad->lastInsertId();

            if (!$idModuloInsertado) {
                $this->dbSeguridad->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para el módulo.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de módulo tras registro.',
                    'modulo_id' => null
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Módulo registrado exitosamente (ID: ' . $idModuloInsertado . ').',
                'modulo_id' => $idModuloInsertado
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al insertar módulo: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar módulo: ' . $e->getMessage(),
                'modulo_id' => null
            ];
        }
    }

    public function updateModulo(int $idmodulo, array $data): array
    {
        try {
            $titulo = $data['titulo'];
            $descripcion = $data['descripcion'];

            if ($this->verificarModuloExiste($titulo, $idmodulo)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe otro módulo con ese título.'
                ];
            }

            if (!$this->verificarControllerExiste($titulo)) {
                return [
                    'status' => false,
                    'message' => 'No existe un controlador con el nombre "' . ucfirst(strtolower($titulo)) . '.php". Debe crear el controlador antes de actualizar el módulo.'
                ];
            }

            $this->dbSeguridad->beginTransaction();

            $sql = "UPDATE modulos SET titulo = ?, descripcion = ?, fecha_modificacion = NOW() WHERE idmodulo = ?";
            
            $valores = [
                $titulo,
                $descripcion,
                $idmodulo
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar el módulo o no se realizaron cambios.'
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Módulo actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al actualizar módulo: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar módulo: ' . $e->getMessage()
            ];
        }
    }

    public function selectModuloById(int $idmodulo)
    {
        $sql = "SELECT 
                    idmodulo,
                    titulo,
                    descripcion,
                    estatus,
                    fecha_creacion,
                    fecha_modificacion,
                    DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                FROM modulos 
                WHERE idmodulo = ?";
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idmodulo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ModulosModel::selectModuloById -> " . $e->getMessage());
            return false;
        }
    }

    public function deleteModuloById(int $idmodulo): bool
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $sql = "UPDATE modulos SET estatus = 'INACTIVO', fecha_modificacion = NOW() WHERE idmodulo = ?";
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idmodulo]);
            
            $this->dbSeguridad->commit();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->dbSeguridad->rollBack();
            error_log("ModulosModel::deleteModuloById -> " . $e->getMessage());
            return false;
        }
    }

    public function selectAllModulosActivos()
    {
        $sql = "SELECT 
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
                ORDER BY titulo ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Módulos obtenidos.", "data" => $modulos];
        } catch (PDOException $e) {
            error_log("ModulosModel::selectAllModulosActivos - Error al seleccionar módulos: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener módulos: " . $e->getMessage(), "data" => []];
        }
    }

    public function getControlladoresDisponibles(): array
    {
        $controladores = [];
        $rutaControllers = "app/Controllers/";
        
        try {
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
            error_log("Error al obtener controladores: " . $e->getMessage());
            return ["status" => false, "data" => []];
        }
    }
}
?>
