<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;

class NotificacionesConfigModel {
    
    /**
     * Catálogo de tipos de notificaciones por módulo
     */
    private function obtenerCatalogoNotificaciones() {
        return [
            'compras' => [
                'COMPRA_POR_AUTORIZAR' => [
                    'nombre' => 'Compra Por Autorizar',
                    'descripcion' => 'Cuando una compra requiere autorización del gerente',
                    'prioridad' => 'ALTA'
                ],
                'COMPRA_AUTORIZADA_PAGO' => [
                    'nombre' => 'Compra Autorizada - Pago Pendiente',
                    'descripcion' => 'Compra autorizada lista para procesar pago',
                    'prioridad' => 'MEDIA'
                ],
                'COMPRA_PAGADA' => [
                    'nombre' => 'Compra Pagada',
                    'descripcion' => 'Compra completamente pagada',
                    'prioridad' => 'BAJA'
                ]
            ],
            'productos' => [
                'PRODUCTO_NUEVO' => [
                    'nombre' => 'Producto Nuevo',
                    'descripcion' => 'Nuevo producto registrado en el sistema',
                    'prioridad' => 'MEDIA'
                ],
                'PRODUCTO_ACTUALIZADO' => [
                    'nombre' => 'Producto Actualizado',
                    'descripcion' => 'Información de producto modificada',
                    'prioridad' => 'BAJA'
                ],
                'STOCK_BAJO' => [
                    'nombre' => 'Stock Bajo',
                    'descripcion' => 'Producto por debajo del stock mínimo',
                    'prioridad' => 'ALTA'
                ],
                'SIN_STOCK' => [
                    'nombre' => 'Sin Stock',
                    'descripcion' => 'Producto sin existencias disponibles',
                    'prioridad' => 'CRITICA'
                ]
            ],
            'ventas' => [
                'VENTA_CREADA' => [
                    'nombre' => 'Nueva Venta',
                    'descripcion' => 'Venta registrada en el sistema',
                    'prioridad' => 'MEDIA'
                ],
                'VENTA_PAGADA' => [
                    'nombre' => 'Venta Pagada',
                    'descripcion' => 'Venta completamente pagada',
                    'prioridad' => 'BAJA'
                ]
            ]
        ];
    }
    
    /**
     * Obtener todos los roles activos
     */
    public function obtenerRoles() {
        try {
            $conn = new Conexion();
            $conn->connect();
            $db = $conn->get_conectSeguridad(); // rol está en bd_pda_seguridad
            
            $sql = "SELECT idrol, nombre
                    FROM roles 
                    WHERE estatus = 'ACTIVO'
                    ORDER BY nombre";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $conn->disconnect();
            return $roles;
        } catch (\Exception $e) {
            error_log("Error obtenerRoles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener configuración completa de un rol
     */
    public function obtenerConfiguracionRol($rolId) {
        try {
            $conn = new Conexion();
            $conn->connect();
            $db = $conn->get_conectSeguridad();
            
            // Obtener config guardada
            $sql = "SELECT modulo, tipo_notificacion, habilitada 
                    FROM notificaciones_config 
                    WHERE idrol = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$rolId]);
            $configGuardada = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $conn->disconnect();
            
            // Crear mapa de config
            $configMap = [];
            foreach ($configGuardada as $item) {
                $key = $item['modulo'] . '|' . $item['tipo_notificacion'];
                $configMap[$key] = $item['habilitada'];
            }
            
            // Combinar con catálogo
            $catalogo = $this->obtenerCatalogoNotificaciones();
            $resultado = [];
            
            foreach ($catalogo as $modulo => $tipos) {
                $resultado[$modulo] = [];
                foreach ($tipos as $tipo => $info) {
                    $key = $modulo . '|' . $tipo;
                    $resultado[$modulo][$tipo] = [
                        'nombre' => $info['nombre'],
                        'descripcion' => $info['descripcion'],
                        'prioridad' => $info['prioridad'],
                        'habilitada' => isset($configMap[$key]) ? (bool)$configMap[$key] : true
                    ];
                }
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            error_log("Error obtenerConfiguracionRol: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Guardar configuración de un rol
     */
    public function guardarConfiguracion($rolId, $configuraciones) {
        try {
            $conn = new Conexion();
            $conn->connect();
            $db = $conn->get_conectSeguridad();
            
            $db->beginTransaction();
            
            // Eliminar config anterior del rol
            $sqlDelete = "DELETE FROM notificaciones_config WHERE idrol = ?";
            $stmtDelete = $db->prepare($sqlDelete);
            $stmtDelete->execute([$rolId]);
            
            // Insertar nueva config
            $sqlInsert = "INSERT INTO notificaciones_config 
                         (idrol, modulo, tipo_notificacion, habilitada) 
                         VALUES (?, ?, ?, ?)";
            $stmtInsert = $db->prepare($sqlInsert);
            
            foreach ($configuraciones as $config) {
                $stmtInsert->execute([
                    $rolId,
                    $config['modulo'],
                    $config['tipo'],
                    $config['habilitada'] ? 1 : 0
                ]);
            }
            
            $db->commit();
            $conn->disconnect();
            
            return ['status' => true, 'message' => 'Configuración guardada correctamente'];
            
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $errorMsg = $e->getMessage();
            error_log("Error guardarConfiguracion: " . $errorMsg);
            error_log("Stack trace: " . $e->getTraceAsString());
            return ['status' => false, 'message' => 'Error al guardar: ' . $errorMsg];
        }
    }
}
