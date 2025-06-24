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
                    error_log("Persona con ID {$data['idpersona']} no existe");
                    $data['idpersona'] = null;
                }
            }

            $query = "INSERT INTO pagos (
                idpersona, idtipo_pago, idventa, idcompra, idsueldotemp, 
                monto, referencia, fecha_pago, observaciones, estatus, 
                fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())";

            $params = [
                $data['idpersona'],
                $data['idtipo_pago'],
                $data['idventa'],
                $data['idcompra'],
                $data['idsueldotemp'],
                $data['monto'],
                $data['referencia'],
                $data['fecha_pago'],
                $data['observaciones']
            ];

            $idpago = $this->insert($query, $params);

            if ($idpago > 0) {
                return [
                    'status' => true,
                    'message' => 'Pago registrado exitosamente',
                    'data' => ['idpago' => $idpago]
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
                'message' => 'Error interno del servidor'
            ];
        }
    }

    public function updatePago(int $idpago, array $data)
    {
        try {
        
            if ($data['idpersona'] !== null) {
                if (!$this->verificarPersonaExiste($data['idpersona'])) {
                    error_log("Persona con ID {$data['idpersona']} no existe");
                    $data['idpersona'] = null;
                }
            }

            $query = "UPDATE pagos SET 
                idpersona = ?, 
                idtipo_pago = ?, 
                idventa = ?, 
                idcompra = ?, 
                idsueldotemp = ?, 
                monto = ?, 
                referencia = ?, 
                fecha_pago = ?, 
                observaciones = ?
                WHERE idpago = ? AND estatus = 'activo'";

            $params = [
                $data['idpersona'],
                $data['idtipo_pago'],
                $data['idventa'],
                $data['idcompra'],
                $data['idsueldotemp'],
                $data['monto'],
                $data['referencia'],
                $data['fecha_pago'],
                $data['observaciones'],
                $idpago
            ];

            $result = $this->update($query, $params);

            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Pago actualizado exitosamente'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo actualizar el pago'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en updatePago: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

 
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

 
    public function getInfoCompra($idcompra)
    {
        try {
     
            $query = "SELECT p.idproveedor, per.idpersona
                     FROM compra c 
                     INNER JOIN proveedor p ON c.idproveedor = p.idproveedor
                     LEFT JOIN personas per ON p.identificacion = per.identificacion
                     WHERE c.idcompra = ?";
            
            $result = $this->search($query, [$idcompra]);
            
            if (!empty($result)) {
                if ($result['idpersona']) {
                    return ['idpersona' => $result['idpersona']];
                } else {
                 
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
         
            $query = "SELECT c.idcliente, per.idpersona
                     FROM venta v 
                     INNER JOIN cliente c ON v.idcliente = c.idcliente
                     LEFT JOIN personas per ON c.cedula = per.identificacion
                     WHERE v.idventa = ?";
            
            $result = $this->search($query, [$idventa]);
            
            if (!empty($result)) {
                if ($result['idpersona']) {
                    return ['idpersona' => $result['idpersona']];
                } else {
                    // Crear persona para el cliente si no existe
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
                $insertQuery = "INSERT INTO personas (nombre, apellido, identificacion, tipo, estatus, fecha_creacion) 
                               VALUES (?, ?, ?, 'proveedor', 'activo', NOW())";
                $idpersona = $this->insert($insertQuery, [
                    $proveedor['nombre'],
                    $proveedor['apellido'] ?? '',
                    $proveedor['identificacion']
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
                $insertQuery = "INSERT INTO personas (nombre, apellido, identificacion, tipo, estatus, fecha_creacion) 
                               VALUES (?, ?, ?, 'cliente', 'activo', NOW())";
                $idpersona = $this->insert($insertQuery, [
                    $cliente['nombre'],
                    $cliente['apellido'] ?? '',
                    $cliente['cedula']
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
                p.fecha_creacion,
                tp.nombre as metodo_pago,
                per.nombre as persona_nombre,
                per.apellido as persona_apellido,
                per.identificacion as persona_identificacion,
                -- Información de compra
                c.nro_compra,
                prov.nombre as proveedor_nombre,
                prov.apellido as proveedor_apellido,
                prov.identificacion as proveedor_identificacion,
                -- Información de venta
                v.nro_venta,
                cli.nombre as cliente_nombre,
                cli.apellido as cliente_apellido,
                cli.cedula as cliente_cedula,
                -- Determinar tipo y destinatario
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN 'Compra'
                    WHEN p.idventa IS NOT NULL THEN 'Venta'
                    WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                    ELSE 'Otro'
                END as tipo_pago_texto,
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN CONCAT(COALESCE(prov.nombre, ''), ' ', COALESCE(prov.apellido, ''))
                    WHEN p.idventa IS NOT NULL THEN CONCAT(COALESCE(cli.nombre, ''), ' ', COALESCE(cli.apellido, ''))
                    ELSE 'Otro pago'
                END as destinatario
            FROM pagos p
            LEFT JOIN personas per ON p.idpersona = per.idpersona
            LEFT JOIN tipos_pagos tp ON p.idtipo_pago = tp.idtipo_pago
            LEFT JOIN compra c ON p.idcompra = c.idcompra
            LEFT JOIN proveedor prov ON c.idproveedor = prov.idproveedor
            LEFT JOIN venta v ON p.idventa = v.idventa  
            LEFT JOIN cliente cli ON v.idcliente = cli.idcliente
            LEFT JOIN sueldos_temporales st ON p.idsueldotemp = st.idsueldotemp
            ORDER BY p.fecha_pago DESC, p.idpago DESC";

            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?: []
            ];
        } catch (Exception $e) {
            error_log("Error en selectAllPagos: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener los pagos'
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
                tp.nombre as metodo_pago,
                per.nombre as persona_nombre,
                per.apellido as persona_apellido,
                per.identificacion as persona_identificacion,
                -- Información de compra
                c.nro_compra,
                prov.nombre as proveedor_nombre,
                prov.apellido as proveedor_apellido,
                prov.identificacion as proveedor_identificacion,
                -- Información de venta
                v.nro_venta,
                cli.nombre as cliente_nombre,
                cli.apellido as cliente_apellido,
                cli.cedula as cliente_cedula,
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN 'Compra'
                    WHEN p.idventa IS NOT NULL THEN 'Venta'
                    WHEN p.idsueldotemp IS NOT NULL THEN 'Sueldo'
                    ELSE 'Otro'
                END as tipo_pago_texto,
                CASE 
                    WHEN p.idcompra IS NOT NULL THEN CONCAT(COALESCE(prov.nombre, ''), ' ', COALESCE(prov.apellido, ''))
                    WHEN p.idventa IS NOT NULL THEN CONCAT(COALESCE(cli.nombre, ''), ' ', COALESCE(cli.apellido, ''))
                    ELSE 'Otro pago'
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
                'message' => 'Error al obtener el pago'
            ];
        }
    }

    public function deletePagoById(int $idpago)
    {
        try {
            $query = "UPDATE pagos SET estatus = 'inactivo' WHERE idpago = ? AND estatus = 'activo'";
            $result = $this->update($query, [$idpago]);

            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Pago eliminado exitosamente'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo eliminar el pago'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en deletePagoById: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error interno del servidor'
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
                'data' => $result ?: []
            ];
        } catch (Exception $e) {
            error_log("Error en selectTiposPago: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener tipos de pago'
            ];
        }
    }

    public function selectComprasPendientes()
    {
        try {
            $query = "SELECT
                            c.idcompra,
                            c.nro_compra,
                            c.balance, 
                            p.nombre AS proveedor,
                            p.identificacion AS proveedor_identificacion
                        FROM
                            compra c
                        INNER JOIN
                            proveedor p ON c.idproveedor = p.idproveedor
                        WHERE
                            (c.estatus_compra = 'POR_PAGAR' OR c.estatus_compra = 'PAGO_FRACCIONADO')
                            AND c.balance > 0
                        ORDER BY
                            c.fecha DESC;";
            
            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?: []
            ];
        } catch (Exception $e) {
            error_log("Error en selectComprasPendientes: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener compras pendientes'
            ];
        }
    }

    public function selectVentasPendientes()
    {
        try {
            $query = "SELECT 
                v.idventa,
                v.nro_venta,
                v.total,
                c.nombre as cliente,
                c.cedula as cliente_identificacion
                FROM venta v
                INNER JOIN cliente c ON v.idcliente = c.idcliente
                WHERE v.estatus = 'activo'
                AND v.idventa NOT IN (
                    SELECT pg.idventa 
                    FROM pagos pg 
                    WHERE pg.idventa IS NOT NULL 
                    AND pg.estatus IN ('activo', 'conciliado')
                )
                ORDER BY v.fecha_venta DESC";
            
            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?: []
            ];
        } catch (Exception $e) {
            error_log("Error en selectVentasPendientes: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener ventas pendientes'
            ];
        }
    }

    public function selectSueldosPendientes()
    {
        try {
            $query = "SELECT 
                st.idsueldotemp,
                st.descripcion as empleado,
                st.monto as total,
                st.periodo,
                '' as empleado_identificacion
                FROM sueldos_temporales st
                WHERE st.estatus = 'activo'
                AND st.idsueldotemp NOT IN (
                    SELECT pg.idsueldotemp 
                    FROM pagos pg 
                    WHERE pg.idsueldotemp IS NOT NULL 
                    AND pg.estatus IN ('activo', 'conciliado')
                )
                ORDER BY st.fecha_creacion DESC";
            
            $result = $this->searchAll($query);

            return [
                'status' => true,
                'data' => $result ?: []
            ];
        } catch (Exception $e) {
            error_log("Error en selectSueldosPendientes: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al obtener sueldos pendientes'
            ];
        }
    }

    public function conciliarPago(int $idpago)
    {
        try {
            $query = "UPDATE pagos SET 
                     estatus = 'conciliado'
                     WHERE idpago = ? AND estatus = 'activo'";
            
            $result = $this->update($query, [$idpago]);

            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Pago conciliado exitosamente',
                    'idpago' => $idpago
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo conciliar el pago',
                    'idpago' => $idpago
                ];
            }
        } catch (Exception $e) {
            error_log("Error en conciliarPago: " . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'idpago' => $idpago
            ];
        }
    }
}
?>