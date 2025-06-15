<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class RolesIntegradoModel extends mysql
{
    private $conexion;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    /**
     * Obtiene asignaciones completas con permisos específicos por módulo
     */
    public function selectAsignacionesRolCompletas(int $idrol)
    {
        try {
            $sql = "SELECT 
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
                    ORDER BY m.titulo ASC";

            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol, $idrol]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $modulosConPermisos = [];
            foreach ($resultados as $fila) {
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
            error_log("Error al obtener asignaciones del rol: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener asignaciones.',
                'data' => []
            ];
        }
    }

    /**
     * Guarda asignaciones con permisos específicos por módulo
     */
    public function guardarAsignacionesRolCompletas(array $data)
    {
        try {
            $idrol = intval($data['idrol'] ?? 0);
            $asignaciones = $data['asignaciones'] ?? [];

            if ($idrol <= 0) {
                return ['status' => false, 'message' => 'ID de rol no válido.'];
            }

            $this->dbSeguridad->beginTransaction();

            // Eliminar asignaciones existentes
            $this->eliminarAsignacionesExistentes($idrol);

            $totalModulos = 0;
            $totalPermisosEspecificos = 0;

            foreach ($asignaciones as $asignacion) {
                $idmodulo = intval($asignacion['idmodulo'] ?? 0);
                $permisosEspecificos = $asignacion['permisos_especificos'] ?? [];

                if ($idmodulo > 0 && !empty($permisosEspecificos)) {
                    // Insertar relación rol-módulo
                    $this->insertarRolModulo($idrol, $idmodulo);
                    $totalModulos++;

                    // Insertar permisos específicos para este módulo
                    foreach ($permisosEspecificos as $idpermiso) {
                        $idpermiso = intval($idpermiso);
                        if ($idpermiso > 0) {
                            $this->insertarRolModuloPermiso($idrol, $idmodulo, $idpermiso);
                            $totalPermisosEspecificos++;
                        }
                    }
                }
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true,
                'message' => "Configuración guardada exitosamente: {$totalModulos} módulos con {$totalPermisosEspecificos} permisos específicos.",
                'modulos_asignados' => $totalModulos,
                'permisos_especificos_asignados' => $totalPermisosEspecificos
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al guardar asignaciones: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ];
        }
    }

    private function eliminarAsignacionesExistentes(int $idrol): void
    {
        // Eliminar permisos específicos por módulo
        $sqlPermisosEspecificos = "DELETE FROM rol_modulo_permisos WHERE idrol = ?";
        $stmtPermisosEspecificos = $this->dbSeguridad->prepare($sqlPermisosEspecificos);
        $stmtPermisosEspecificos->execute([$idrol]);

        // Eliminar módulos del rol
        $sqlModulos = "DELETE FROM rol_modulo WHERE idrol = ?";
        $stmtModulos = $this->dbSeguridad->prepare($sqlModulos);
        $stmtModulos->execute([$idrol]);
    }

    private function insertarRolModulo(int $idrol, int $idmodulo): void
    {
        // Verificar si ya existe antes de insertar
        $sqlCheck = "SELECT COUNT(*) FROM rol_modulo WHERE idrol = ? AND idmodulo = ?";
        $stmtCheck = $this->dbSeguridad->prepare($sqlCheck);
        $stmtCheck->execute([$idrol, $idmodulo]);
        
        if ($stmtCheck->fetchColumn() == 0) {
            $sql = "INSERT INTO rol_modulo (idrol, idmodulo) VALUES (?, ?)";
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol, $idmodulo]);
        }
    }

    private function insertarRolModuloPermiso(int $idrol, int $idmodulo, int $idpermiso): void
    {
        $sql = "INSERT INTO rol_modulo_permisos (idrol, idmodulo, idpermiso, activo) VALUES (?, ?, ?, 1)";
        $stmt = $this->dbSeguridad->prepare($sql);
        $stmt->execute([$idrol, $idmodulo, $idpermiso]);
    }

    /**
     * Obtiene todos los roles activos
     */
    public function selectAllRoles()
    {
        // CAMBIAR 'activo' por 'ACTIVO' para coincidir con tu BD
        $sql = "SELECT idrol, nombre, descripcion FROM roles WHERE estatus = 'ACTIVO' ORDER BY nombre ASC";
        
        try {
            $stmt = $this->dbSeguridad->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => true, 'message' => 'Roles obtenidos.', 'data' => $roles];
        } catch (PDOException $e) {
            error_log("Error al obtener roles: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener roles.', 'data' => []];
        }
    }

    /**
     * Obtiene todos los módulos activos
     */
    public function selectAllModulosActivos()
    {
        // CAMBIAR 'activo' por 'activo' (verificar en tu BD cuál es el valor correcto)
        $sql = "SELECT idmodulo, titulo, descripcion FROM modulos WHERE estatus = 'activo' ORDER BY titulo ASC";
        
        try {
            $stmt = $this->dbSeguridad->query($sql);
            $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => true, 'message' => 'Módulos obtenidos.', 'data' => $modulos];
        } catch (PDOException $e) {
            error_log("Error al obtener módulos: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener módulos.', 'data' => []];
        }
    }

    /**
     * Obtiene todos los permisos activos
     */
    public function selectAllPermisosActivos()
    {
        $sql = "SELECT idpermiso, nombre_permiso FROM permisos ORDER BY nombre_permiso ASC";
        
        try {
            $stmt = $this->dbSeguridad->query($sql);
            $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => true, 'message' => 'Permisos obtenidos.', 'data' => $permisos];
        } catch (PDOException $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al obtener permisos.', 'data' => []];
        }
    }
}
?>