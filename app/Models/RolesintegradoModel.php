<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;

class RolesintegradoModel
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $message;
    private $status;

    private $idrol;
    private $idmodulo;
    private $idpermiso;
    private $asignaciones;

    public function __construct()
    {
        // Inicialización del modelo sin herencia
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
    public function setIdRol($idrol){
        $this->idrol = $idrol;
    }
    public function getIdRol(){
        return $this->idrol;
    }
    public function setIdModulo($idmodulo){
        $this->idmodulo = $idmodulo;
    }
    public function getIdModulo(){
        return $this->idmodulo;
    }
    public function setIdPermiso($idpermiso){
        $this->idpermiso = $idpermiso;
    }
    public function getIdPermiso(){
        return $this->idpermiso;
    }
    public function setAsignaciones($asignaciones){
        $this->asignaciones = $asignaciones;
    }
    public function getAsignaciones(){
        return $this->asignaciones;
    }

    // MÉTODOS PÚBLICOS
    public function selectAsignacionesRolCompletas(int $idrol)
    {
        $this->setIdRol($idrol);
        return $this->ejecutarConsultaAsignacionesRolCompletas();
    }

    public function guardarAsignacionesRolCompletas(array $data)
    {
        $this->setData($data);
        return $this->ejecutarGuardadoAsignacionesRolCompletas();
    }

    public function selectAllRoles()
    {
        return $this->ejecutarConsultaTodosRoles();
    }

    public function selectAllModulosActivos()
    {
        return $this->ejecutarConsultaTodosModulosActivos();
    }

    public function selectAllPermisosActivos()
    {
        return $this->ejecutarConsultaTodosPermisosActivos();
    }

    // MÉTODOS PRIVADOS
    private function ejecutarConsultaAsignacionesRolCompletas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT 
                        m.idmodulo,
                        m.titulo as nombre_modulo,
                        m.descripcion as descripcion_modulo,
                        GROUP_CONCAT(DISTINCT rmp.idpermiso ORDER BY rmp.idpermiso) as permisos_especificos_ids,
                        GROUP_CONCAT(DISTINCT p.nombre_permiso ORDER BY rmp.idpermiso SEPARATOR '|') as permisos_especificos_nombres,
                        CASE WHEN rm.idrol IS NOT NULL THEN 1 ELSE 0 END as tiene_acceso_modulo
                    FROM modulos m
                    LEFT JOIN rol_modulo rm ON m.idmodulo = rm.idmodulo AND rm.idrol = ?
                    LEFT JOIN rol_modulo_permisos rmp ON m.idmodulo = rmp.idmodulo AND rmp.idrol = ? AND rmp.activo = 1
                    LEFT JOIN permisos p ON rmp.idpermiso = p.idpermiso
                    WHERE m.estatus = 'activo'
                    GROUP BY m.idmodulo, m.titulo, m.descripcion, rm.idrol
                    ORDER BY m.titulo ASC");

            $this->setArray([$this->getIdRol(), $this->getIdRol()]);

            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            $modulosConPermisos = [];
            foreach ($this->getResult() as $fila) {
                $permisosIds = $fila['permisos_especificos_ids'] ? explode(',', $fila['permisos_especificos_ids']) : [];
                $permisosNombres = $fila['permisos_especificos_nombres'] ? explode('|', $fila['permisos_especificos_nombres']) : [];
                
                $permisos = [];
                for ($i = 0; $i < count($permisosIds); $i++) {
                    if (!empty($permisosIds[$i])) {
                        $permisos[] = [
                            'idpermiso' => intval($permisosIds[$i]),
                            'nombre_permiso' => $permisosNombres[$i] ?? ''
                        ];
                    }
                }

                $modulosConPermisos[] = [
                    'idmodulo' => intval($fila['idmodulo']),
                    'nombre_modulo' => $fila['nombre_modulo'],
                    'descripcion_modulo' => $fila['descripcion_modulo'],
                    'tiene_acceso' => (bool)$fila['tiene_acceso_modulo'],
                    'permisos_especificos' => $permisos
                ];
            }

            return [
                'status' => true,
                'message' => 'Asignaciones obtenidas correctamente.',
                'data' => $modulosConPermisos
            ];

        } catch (PDOException $e) {
            error_log("RolesIntegradoModel::ejecutarConsultaAsignacionesRolCompletas - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener asignaciones.',
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarGuardadoAsignacionesRolCompletas()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $data = $this->getData();
            $idrol = intval($data['idrol'] ?? 0);
            $asignaciones = $data['asignaciones'] ?? [];

            if ($idrol <= 0) {
                return ['status' => false, 'message' => 'ID de rol no válido.'];
            }

            $this->setIdRol($idrol);
            $this->setAsignaciones($asignaciones);

            $dbSeguridad->beginTransaction();

            $this->ejecutarEliminacionAsignacionesExistentes($dbSeguridad);

            $totalModulos = 0;
            $totalPermisosEspecificos = 0;

            foreach ($this->getAsignaciones() as $asignacion) {
                $idmodulo = intval($asignacion['idmodulo'] ?? 0);
                $permisosEspecificos = $asignacion['permisos_especificos'] ?? [];

                if ($idmodulo > 0 && !empty($permisosEspecificos)) {
                    $this->setIdModulo($idmodulo);
                    $this->ejecutarInsercionRolModulo($dbSeguridad);
                    $totalModulos++;

                    foreach ($permisosEspecificos as $idpermiso) {
                        $idpermiso = intval($idpermiso);
                        if ($idpermiso > 0) {
                            $this->setIdPermiso($idpermiso);
                            $this->ejecutarInsercionRolModuloPermiso($dbSeguridad);
                            $totalPermisosEspecificos++;
                        }
                    }
                }
            }

            $dbSeguridad->commit();

            return [
                'status' => true,
                'message' => "Configuración guardada exitosamente: {$totalModulos} módulos con {$totalPermisosEspecificos} permisos específicos.",
                'modulos_asignados' => $totalModulos,
                'permisos_especificos_asignados' => $totalPermisosEspecificos
            ];

        } catch (PDOException $e) {
            if ($dbSeguridad->inTransaction()) {
                $dbSeguridad->rollBack();
            }
            error_log("RolesIntegradoModel::ejecutarGuardadoAsignacionesRolCompletas - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarEliminacionAsignacionesExistentes($dbSeguridad)
    {
        $this->setQuery("DELETE FROM rol_modulo_permisos WHERE idrol = ?");
        $this->setArray([$this->getIdRol()]);
        $stmt = $dbSeguridad->prepare($this->getQuery());
        $stmt->execute($this->getArray());

        $this->setQuery("DELETE FROM rol_modulo WHERE idrol = ?");
        $this->setArray([$this->getIdRol()]);
        $stmt = $dbSeguridad->prepare($this->getQuery());
        $stmt->execute($this->getArray());
    }

    private function ejecutarInsercionRolModulo($dbSeguridad)
    {
        $this->setQuery("SELECT COUNT(*) FROM rol_modulo WHERE idrol = ? AND idmodulo = ?");
        $this->setArray([$this->getIdRol(), $this->getIdModulo()]);
        $stmt = $dbSeguridad->prepare($this->getQuery());
        $stmt->execute($this->getArray());
        
        if ($stmt->fetchColumn() == 0) {
            $this->setQuery("INSERT INTO rol_modulo (idrol, idmodulo) VALUES (?, ?)");
            $this->setArray([$this->getIdRol(), $this->getIdModulo()]);
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
        }
    }

    private function ejecutarInsercionRolModuloPermiso($dbSeguridad)
    {
        $this->setQuery("INSERT INTO rol_modulo_permisos (idrol, idmodulo, idpermiso, activo) VALUES (?, ?, ?, 1)");
        $this->setArray([$this->getIdRol(), $this->getIdModulo(), $this->getIdPermiso()]);
        $stmt = $dbSeguridad->prepare($this->getQuery());
        $stmt->execute($this->getArray());
    }

    private function ejecutarConsultaTodosRoles()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT idrol, nombre, descripcion FROM roles WHERE estatus = 'activo' ORDER BY nombre ASC");
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return [
                'status' => true, 
                'message' => 'Roles obtenidos.', 
                'data' => $this->getResult()
            ];

        } catch (PDOException $e) {
            error_log("RolesIntegradoModel::ejecutarConsultaTodosRoles - Error: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error al obtener roles.', 
                'data' => []
            ];
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
            $this->setQuery("SELECT idmodulo, titulo, descripcion FROM modulos WHERE estatus = 'activo' ORDER BY titulo ASC");
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return [
                'status' => true, 
                'message' => 'Módulos obtenidos.', 
                'data' => $this->getResult()
            ];

        } catch (PDOException $e) {
            error_log("RolesIntegradoModel::ejecutarConsultaTodosModulosActivos - Error: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error al obtener módulos.', 
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarConsultaTodosPermisosActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT idpermiso, nombre_permiso FROM permisos ORDER BY nombre_permiso ASC");
            
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute();
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));

            return [
                'status' => true, 
                'message' => 'Permisos obtenidos.', 
                'data' => $this->getResult()
            ];

        } catch (PDOException $e) {
            error_log("RolesIntegradoModel::ejecutarConsultaTodosPermisosActivos - Error: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error al obtener permisos.', 
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }
    }
}
?>