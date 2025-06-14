<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class PagosModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertPago(array $data)
    {
        try {
            // Verificar si idpersona es válido antes de insertar
            if ($data['idpersona'] !== null) {
                if (!$this->verificarPersonaExiste($data['idpersona'])) {
                    return [
                        'status' => false,
                        'message' => 'La persona especificada no existe en el sistema'
                    ];
                }
            }

            $query = "INSERT INTO pagos (
                idpersona, 
                idtipo_pago, 
                idventa, 
                idcompra, 
                idsueldotemp,
                monto, 
                referencia, 
                fecha_pago, 
                observaciones, 
                estatus, 
                fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())";

            $params = [
                $data['idpersona'], // Puede ser NULL
                $data['idtipo_pago'],
                $data['idventa'] ?: null,
                $data['idcompra'] ?: null,
                $data['idsueldotemp'] ?: null,
                $data['monto'],
                $data['referencia'] ?: null,
                $data['fecha_pago'],
                $data['observaciones'] ?: null
            ];

            $idpago = $this->insert($query, $params);

            if ($idpago > 0) {
                return [
                    'status' => true,
                    'message' => 'Pago registrado exitosamente',
                    'idpago' => $idpago
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Error al registrar el pago'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en insertPago: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error interno al registrar el pago: ' . $e->getMessage()
            ];
        }
    }

    // Método para verificar si una persona existe
    private function verificarPersonaExiste($idpersona)
    {
        try {
            $query = "SELECT idpersona FROM personas WHERE idpersona = ?";
            $result = $this->search($query, [$idpersona]);
            return !empty($result);
        } catch (Exception $e) {
            error_log("Error en verificarPersonaExiste: " . $e->getMessage());
            return false;
        }
    }

    // Métodos para obtener información de destinatarios - CORREGIDOS
    public function getInfoCompra($idcompra)
    {
        try {
            // Primero verificamos si el proveedor tiene una persona asociada
            $query = "SELECT p.idproveedor, per.idpersona
                     FROM compra c 
                     INNER JOIN proveedor p ON c.idproveedor = p.idproveedor 
                     LEFT JOIN personas per ON p.idproveedor = per.idpersona
                     WHERE c.idcompra = ?";
            
            $result = $this->search($query, [$idcompra]);
            
            if (!empty($result)) {
                // Si el proveedor tiene una persona asociada, la usamos
                // Si no, intentamos crear o buscar una persona para este proveedor
                if ($result['idpersona']) {
                    return ['idpersona' => $result['idpersona']];
                } else {
                    // Intentamos crear una persona para el proveedor si no existe
                    return $this->crearPersonaParaProveedor($result['idproveedor']);
                }
            }
            
            return ['idpersona' => null];
        } catch (Exception $e) {
            error_log("Error en getInfoCompra: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    public function getInfoVenta($idventa)
    {
        try {
            // Similar para clientes
            $query = "SELECT c.idcliente, per.idpersona
                     FROM venta v 
                     INNER JOIN cliente c ON v.idcliente = c.idcliente 
                     LEFT JOIN personas per ON c.idcliente = per.idpersona
                     WHERE v.idventa = ?";
            
            $result = $this->search($query, [$idventa]);
            
            if (!empty($result)) {
                if ($result['idpersona']) {
                    return ['idpersona' => $result['idpersona']];
                } else {
                    return $this->crearPersonaParaCliente($result['idcliente']);
                }
            }
            
            return ['idpersona' => null];
        } catch (Exception $e) {
            error_log("Error en getInfoVenta: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    // Método para crear persona para proveedor
    private function crearPersonaParaProveedor($idproveedor)
    {
        try {
            $query = "SELECT nombre, apellido, identificacion 
                     FROM proveedor WHERE idproveedor = ?";
            $proveedor = $this->search($query, [$idproveedor]);
            
            if (!empty($proveedor)) {
                $insertQuery = "INSERT INTO personas (nombre, apellido, identificacion, tipo_persona, estatus, fecha_creacion) 
                               VALUES (?, ?, ?, 'proveedor', 'activo', NOW())";
                
                $idpersona = $this->insert($insertQuery, [
                    $proveedor['nombre'] ?: 'Sin nombre',
                    $proveedor['apellido'] ?: 'Sin apellido', 
                    $proveedor['identificacion'] ?: 'Sin identificación'
                ]);
                
                if ($idpersona > 0) {
                    return ['idpersona' => $idpersona];
                }
            }
            
            return ['idpersona' => null];
        } catch (Exception $e) {
            error_log("Error en crearPersonaParaProveedor: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    // Método para crear persona para cliente
    private function crearPersonaParaCliente($idcliente)
    {
        try {
            $query = "SELECT nombre, apellido, cedula 
                     FROM cliente WHERE idcliente = ?";
            $cliente = $this->search($query, [$idcliente]);
            
            if (!empty($cliente)) {
                $insertQuery = "INSERT INTO personas (nombre, apellido, identificacion, tipo_persona, estatus, fecha_creacion) 
                               VALUES (?, ?, ?, 'cliente', 'activo', NOW())";
                
                $idpersona = $this->insert($insertQuery, [
                    $cliente['nombre'] ?: 'Sin nombre',
                    $cliente['apellido'] ?: 'Sin apellido',
                    $cliente['cedula'] ?: 'Sin cédula'
                ]);
                
                if ($idpersona > 0) {
                    return ['idpersona' => $idpersona];
                }
            }
            
            return ['idpersona' => null];
        } catch (Exception $e) {
            error_log("Error en crearPersonaParaCliente: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    public function getInfoSueldo($idsueldotemp)
    {
        try {
            // Como no tienes tabla empleados, retornamos null
            return ['idpersona' => null];
        } catch (Exception $e) {
            error_log("Error en getInfoSueldo: " . $e->getMessage());
            return ['idpersona' => null];
        }
    }

    public function selectAllPagos()
    {
        try {
            $query = "SELECT 
                p.idpago,
                p.monto,
                p.referencia,
                p.fecha_pago,
                DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_pago_formato,
                p.observaciones,
                p.estatus,
                
                -- Información de la persona (si existe)
                CASE 
                    WHEN p.idpersona IS NOT NULL THEN 
                        CONCAT(per.nombre, ' ', per.apellido)
                    ELSE 'Sin especificar'
                END as persona_nombre,
                
                -- Tipo de pago desde la tabla tipos_pagos
                COALESCE(tp.nombre, 'Sin especificar') as metodo_pago,
                
                -- Determinar el tipo de operación
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN 'Compra'
                    WHEN p.idventa IS NOT NULL THEN 'Venta'
                    WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                    ELSE 'Otro'
                END as tipo_pago_texto,
                
                -- Información del destinatario/operación
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN 
                        CONCAT('Compra #', COALESCE(c.nro_compra, p.idcompra), ' - ', 
                               COALESCE(CONCAT(prov.nombre, ' ', prov.apellido), 'Proveedor'))
                    WHEN p.idventa IS NOT NULL THEN 
                        CONCAT('Venta #', COALESCE(v.nro_venta, p.idventa), ' - ', 
                               COALESCE(CONCAT(cli.nombre, ' ', cli.apellido), 'Cliente'))
                    WHEN p.idsueldotemp IS NOT NULL THEN 
                        CONCAT('Sueldo temporal #', p.idsueldotemp)
                    ELSE 'Pago general'
                END as destinatario
                
            FROM pagos p
            LEFT JOIN personas per ON p.idpersona = per.idpersona
            LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
            LEFT JOIN compra c ON p.idcompra = c.idcompra
            LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
            LEFT JOIN venta v ON p.idventa = v.idventa  
            LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
            LEFT JOIN sueldos_temporales st ON p.idsueldotemp = st.idsueldotemp
            WHERE p.estatus = 'activo'
            ORDER BY p.fecha_pago DESC, p.idpago DESC";

            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?? []
            ];
        } catch (Exception $e) {
            error_log("Error en selectAllPagos: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener los pagos: ' . $e->getMessage()
            ];
        }
    }

    public function selectPagoById(int $idpago)
    {
        try {
            $query = "SELECT 
                p.idpago,
                p.idpersona,
                p.idtipo_pago,
                p.idventa,
                p.idcompra,
                p.idsueldotemp,
                p.monto,
                p.referencia,
                p.fecha_pago,
                DATE_FORMAT(p.fecha_pago, '%d/%m/%Y') as fecha_pago_formato,
                p.observaciones,
                p.estatus,
                p.fecha_creacion,
                p.ultima_modificacion,
                
                -- Información de la persona (si existe)
                CASE 
                    WHEN p.idpersona IS NOT NULL THEN 
                        CONCAT(per.nombre, ' ', per.apellido)
                    ELSE 'Sin especificar'
                END as persona_nombre,
                
                -- Tipo de pago desde la tabla tipos_pagos
                COALESCE(tp.nombre, 'Sin especificar') as metodo_pago,
                
                -- Determinar el tipo de operación
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN 'Compra'
                    WHEN p.idventa IS NOT NULL THEN 'Venta'
                    WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                    ELSE 'Otro'
                END as tipo_pago_texto,
                
                -- Información del destinatario/operación
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN 
                        CONCAT('Compra #', COALESCE(c.nro_compra, p.idcompra), ' - ', 
                               COALESCE(CONCAT(prov.nombre, ' ', prov.apellido), 'Proveedor'))
                    WHEN p.idventa IS NOT NULL THEN 
                        CONCAT('Venta #', COALESCE(v.nro_venta, p.idventa), ' - ', 
                               COALESCE(CONCAT(cli.nombre, ' ', cli.apellido), 'Cliente'))
                    WHEN p.idsueldotemp IS NOT NULL THEN 
                        CONCAT('Sueldo temporal #', p.idsueldotemp)
                    ELSE 'Pago general'
                END as destinatario
                
            FROM pagos p
            LEFT JOIN personas per ON p.idpersona = per.idpersona
            LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
            LEFT JOIN compra c ON p.idcompra = c.idcompra
            LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
            LEFT JOIN venta v ON p.idventa = v.idventa  
            LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
            LEFT JOIN sueldos_temporales st ON p.idsueldotemp = st.idsueldotemp
            WHERE p.idpago = ?";

            $result = $this->search($query, [$idpago]);

            if (!empty($result)) {
                return [
                    'status' => true,
                    'data' => $result
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Pago no encontrado'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en selectPagoById: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al buscar el pago: ' . $e->getMessage()
            ];
        }
    }

    public function deletePagoById(int $idpago)
    {
        try {
            $query = "UPDATE pagos SET 
                estatus = 'inactivo',
                ultima_modificacion = NOW()
            WHERE idpago = ?";

            $result = $this->update($query, [$idpago]);

            if ($result > 0) {
                return [
                    'status' => true,
                    'message' => 'Pago desactivado exitosamente'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se encontró el pago o ya estaba inactivo'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en deletePagoById: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al desactivar el pago: ' . $e->getMessage()
            ];
        }
    }

    public function selectTiposPago()
    {
        try {
            $query = "SELECT idtipo_pago, nombre 
                     FROM tipos_pagos 
                     WHERE estatus = 'activo' 
                     ORDER BY nombre";

            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?? []
            ];
        } catch (Exception $e) {
            error_log("Error en selectTiposPago: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener tipos de pago: ' . $e->getMessage()
            ];
        }
    }

    public function selectComprasPendientes()
    {
        try {
            $query = "SELECT 
                c.idcompra,
                c.nro_compra,
                c.total_general as total,
                CONCAT(p.nombre, ' ', p.apellido) as proveedor,
                p.identificacion as proveedor_identificacion,
                DATE_FORMAT(c.fecha, '%d/%m/%Y') as fecha_compra_formato
            FROM compra c
            INNER JOIN proveedor p ON c.idproveedor = p.idproveedor
            WHERE c.estatus_compra IN ('BORRADOR', 'POR_PAGAR', 'PAGADA') 
            ORDER BY c.fecha DESC";

            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?? []
            ];
        } catch (Exception $e) {
            error_log("Error en selectComprasPendientes: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener compras: ' . $e->getMessage()
            ];
        }
    }

    public function selectVentasPendientes()
    {
        try {
            $query = "SELECT 
                v.idventa,
                v.nro_venta,
                v.total_general as total,
                CONCAT(c.nombre, ' ', c.apellido) as cliente,
                c.cedula as cliente_identificacion,
                DATE_FORMAT(v.fecha_venta, '%d/%m/%Y') as fecha_venta_formato
            FROM venta v
            INNER JOIN cliente c ON v.idcliente = c.idcliente
            WHERE v.estatus = 'activo' 
            ORDER BY v.fecha_venta DESC";

            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?? []
            ];
        } catch (Exception $e) {
            error_log("Error en selectVentasPendientes: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener ventas: ' . $e->getMessage()
            ];
        }
    }

    public function selectSueldosPendientes()
    {
        try {
            $query = "SELECT 
                st.idsueldotemp,
                st.sueldo as total,
                'Empleado pendiente' as empleado,
                'Sin ID' as empleado_identificacion,
                DATE_FORMAT(st.fecha_creacion, '%m/%Y') as periodo
            FROM sueldos_temporales st
            WHERE st.estatus = 'activo'
            ORDER BY st.fecha_creacion DESC";

            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?? []
            ];
        } catch (Exception $e) {
            error_log("Error en selectSueldosPendientes: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener sueldos: ' . $e->getMessage()
            ];
        }
    }
}
?>