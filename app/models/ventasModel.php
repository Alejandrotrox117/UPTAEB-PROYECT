<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class VentasModel extends Mysql
{
    private $idventa;
    private $idcliente;
    private $fecha_venta;
    private $total_venta;
    private $estatus;

    public function __construct()
    {
        parent::__construct();
    }

    // Getters y Setters
    public function getIdVenta()
    {
        return $this->idventa;
    }

    public function setIdVenta($idventa)
    {
        $this->idventa = $idventa;
    }

    public function getIdcliente()
    {
        return $this->idcliente;
    }

    public function setIdcliente($idcliente)
    {
        $this->idcliente = $idcliente;
    }

    public function getFechaVenta()
    {
        return $this->fecha_venta;
    }

    public function setFechaVenta($fecha_venta)
    {
        $this->fecha_venta = $fecha_venta;
    }

    public function getTotalVenta()
    {
        return $this->total_venta;
    }

    public function setTotalVenta($total_venta)
    {
        $this->total_venta = $total_venta;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }


    public function getTasaPorCodigoYFecha()
    {
        $codigo = $_GET['codigo_moneda'] ?? '';
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        if (!$codigo) {
            echo json_encode(['tasa' => 1]);
            exit;
        }
        $sql = "SELECT tasa_a_bs FROM historial_tasas_bcv WHERE codigo_moneda = ? AND fecha_publicacion_bcv = ? LIMIT 1";
        $result = $this->search($sql, [$codigo, $fecha]);
        echo json_encode(['tasa' => $result['tasa_a_bs'] ?? 1]);
        exit;
    }
    // Método para obtener productos activos
  
public function obtenerProductos()
{
    $sql = "SELECT
                p.idproducto,
                p.nombre AS nombre_producto,
                p.idcategoria,
                c.nombre AS nombre_categoria,
                p.precio AS precio_unitario,
                p.moneda AS idmoneda_producto
            FROM
                producto p
            JOIN
                categoria c ON p.idcategoria = c.idcategoria
            LEFT JOIN
                monedas m ON p.moneda = m.idmoneda
            WHERE
                p.estatus = 'activo'";
    try {
        return $this->searchAll($sql);
    } catch (Exception $e) {
        error_log("ventasModel::obtenerProductos - Error de BD: " . $e->getMessage());
        return [];
    }
}

    private function generarNumeroVenta()
    {
        // Busca el último idventa registrado
        $sql = "SELECT MAX(idventa) as ultimo_id FROM venta";
        $result = $this->search($sql);
        $ultimoId = isset($result['ultimo_id']) ? intval($result['ultimo_id']) : 0;
        $nuevoId = $ultimoId + 1;
        // Formato: V-000001
        return 'V-' . str_pad($nuevoId, 6, '0', STR_PAD_LEFT);
    }


    public function getVentasDatatable()
    {
        try {
            $sql = "SELECT v.idventa,
                           CONCAT('V-', LPAD(v.idventa, 6, '0')) as nro_venta, 
                           CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                           DATE_FORMAT(v.fecha_creacion, '%d/%m/%Y') as fecha_venta,
                           FORMAT(v.total_general, 2) as total_formateado, 
                           v.estatus
                    FROM venta v
                    INNER JOIN cliente c ON v.idcliente = c.idcliente
                    ORDER BY v.idventa DESC";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener ventas con cliente: " . $e->getMessage());
            throw new Exception("Error al obtener ventas con cliente: " . $e->getMessage());
        }
    }

    public function crearCliente($datosCliente)
    {
        try {
            // Verificar si ya existe un cliente con la misma cédula
            $sqlVerificar = "SELECT COUNT(*) as count FROM cliente WHERE cedula = ?";
            $existe = $this->search($sqlVerificar, [$datosCliente['cedula']]);

            if ($existe['count'] > 0) {
                throw new Exception("Ya existe un cliente con la cédula: " . $datosCliente['cedula']);
            }

            $sqlCliente = "INSERT INTO cliente 
                          (cedula, nombre, apellido, telefono_principal, direccion, observaciones, estatus, fecha_creacion) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $paramsCliente = [
                $datosCliente['cedula'],
                $datosCliente['nombre'],
                $datosCliente['apellido'],
                $datosCliente['telefono_principal'],
                $datosCliente['direccion'],
                $datosCliente['observaciones'] ?? '',
                $datosCliente['estatus'] ?? 'Activo'
            ];

            $idCliente = $this->insert($sqlCliente, $paramsCliente);

            if (!$idCliente) {
                throw new Exception("No se pudo crear el cliente");
            }

            return $idCliente;
        } catch (Exception $e) {
            throw new Exception("Error al crear cliente: " . $e->getMessage());
        }
    }

    /**
     * Método para crear venta con cliente nuevo (si es necesario)
     */
    public function insertVenta($data, $detalles, $datosClienteNuevo = null)
    {
        return $this->executeTransaction(function ($mysql) use ($data, $detalles, $datosClienteNuevo) {
            $idCliente = $data['idcliente'];

            // Si no hay cliente seleccionado pero hay datos de cliente nuevo
            if (!$idCliente && $datosClienteNuevo) {
                // Crear el cliente primero
                $idCliente = $this->crearCliente($datosClienteNuevo);

                // Actualizar los datos de la venta con el nuevo ID de cliente
                $data['idcliente'] = $idCliente;
            }

            // Validar que tenemos un cliente
            if (!$idCliente) {
                throw new Exception("No se pudo determinar el cliente para la venta");
            }

            // Validar que el cliente existe
            $clienteExiste = $this->search("SELECT COUNT(*) as count FROM cliente WHERE idcliente = ?", [$idCliente]);
            if ($clienteExiste['count'] == 0) {
                throw new Exception("El cliente especificado no existe");
            }

            // Validar datos obligatorios de la venta
            $camposObligatorios = [
                'idcliente',
                'fecha_venta',
                'idmoneda_general',
                'subtotal_general',
                'descuento_porcentaje_general',
                'monto_descuento_general',
                'total_general',
                'estatus'
            ];
            foreach ($camposObligatorios as $campo) {
                if (!isset($data[$campo]) || $data[$campo] === '') {
                    throw new Exception("Falta el campo obligatorio: $campo");
                }
            }

            // Validar que hay detalles
            if (empty($detalles)) {
                throw new Exception("La venta debe tener al menos un producto");
            }

            // Generar número de venta

            $nro_venta = $this->generarNumeroVenta();
            if (!$nro_venta) {
                // Si es una petición AJAX, responde con JSON y termina la ejecución
                echo json_encode([
                    'status' => false,
                    'message' => 'Error: No se pudo generar el número de venta'
                ]);
                exit;
            }
            // Insertar venta
            $sqlVenta = "INSERT INTO venta 
                (nro_venta, idcliente, fecha_venta, idmoneda, subtotal_general, descuento_porcentaje_general, 
                 monto_descuento_general, estatus, total_general, observaciones, tasa, fecha_creacion, ultima_modificacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $paramsVenta = [
                $nro_venta,
                $data['idcliente'],
                $data['fecha_venta'],
                $data['idmoneda_general'],
                $data['subtotal_general'],
                $data['descuento_porcentaje_general'],
                $data['monto_descuento_general'],
                $data['estatus'],
                $data['total_general'],
                $data['observaciones'] ?? '',
                $data['tasa_usada'] ?? 1
            ];

            $idventa = $this->insert($sqlVenta, $paramsVenta);
            if (!$idventa) throw new Exception("No se pudo crear la venta.");

            // Insertar detalles
            $sqlDetalle = "INSERT INTO detalle_venta 
                (idventa, idproducto, cantidad, precio_unitario_venta, 
                 idmoneda, subtotal_general, peso_vehiculo, peso_bruto, peso_neto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            foreach ($detalles as $detalle) {
                // Validar que el producto existe
                $productoExiste = $this->search("SELECT COUNT(*) as count FROM producto WHERE idproducto = ?", [$detalle['idproducto']]);
                if ($productoExiste['count'] == 0) {
                    throw new Exception("El producto con ID " . $detalle['idproducto'] . " no existe");
                }

                $paramsDetalle = [
                    $idventa,
                    $detalle['idproducto'],
                    
                    $detalle['cantidad'],
                    $detalle['precio_unitario_venta'],
                    $detalle['id_moneda_detalle'] ?? $data['idmoneda_general'],
                    $detalle['subtotal_general'] ?? 0,
                    $detalle['peso_vehiculo'] ?? 0,
                    $detalle['peso_bruto'] ?? 0,
                    $detalle['peso_neto'] ?? 0
                ];

                $detalleInsertado = $this->insert($sqlDetalle, $paramsDetalle);
                if (!$detalleInsertado) {
                    throw new Exception("No se pudo insertar el detalle del producto ID: " . $detalle['idproducto']);
                }
            }

            return [
                'success' => true,
                'message' => 'Venta y cliente registrados exitosamente',
                'idventa' => $idventa,
                'idcliente' => $idCliente,
                'nro_venta' => $nro_venta
            ];
        });
    }

    /**
     * Validar datos de cliente antes de crear
     */
    public function validarDatosCliente($datos)
    {
        $errores = [];

        if (empty($datos['cedula'])) {
            $errores[] = "La cédula es obligatoria";
        } elseif (!preg_match('/^[VEJP]-\d{6,8}$/', $datos['cedula'])) {
            $errores[] = "Formato de cédula inválido (ej: V-12345678)";
        }

        if (empty($datos['nombre'])) {
            $errores[] = "El nombre es obligatorio";
        } elseif (strlen($datos['nombre']) < 2 || strlen($datos['nombre']) > 50) {
            $errores[] = "El nombre debe tener entre 2 y 50 caracteres";
        }

        if (empty($datos['apellido'])) {
            $errores[] = "El apellido es obligatorio";
        } elseif (strlen($datos['apellido']) < 2 || strlen($datos['apellido']) > 50) {
            $errores[] = "El apellido debe tener entre 2 y 50 caracteres";
        }

        if (empty($datos['telefono_principal'])) {
            $errores[] = "El teléfono es obligatorio";
        } elseif (!preg_match('/^\d{11}$/', $datos['telefono_principal'])) {
            $errores[] = "El teléfono debe tener 11 dígitos";
        }

        if (empty($datos['direccion'])) {
            $errores[] = "La dirección es obligatoria";
        } elseif (strlen($datos['direccion']) < 5 || strlen($datos['direccion']) > 200) {
            $errores[] = "La dirección debe tener entre 5 y 200 caracteres";
        }

        return $errores;
    }
    public function obtenerVentaPorId($idventa)
    {
        try {
            $sql = "SELECT v.idventa, v.nro_venta, v.fecha_venta, v.idmoneda, v.subtotal_general, 
               v.descuento_porcentaje_general, v.monto_descuento_general, v.estatus, v.total_general, v.observaciones,
               v.tasa as tasa_usada,
               c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.cedula as cliente_cedula
        FROM venta v
        INNER JOIN cliente c ON v.idcliente = c.idcliente
        WHERE v.idventa = ?";
            return $this->search($sql, [$idventa]);
        } catch (Exception $e) {
            error_log("Error al obtener la venta: " . $e->getMessage());
            throw new Exception("Error al obtener la venta: " . $e->getMessage());
        }
    }

    // Método para obtener el detalle de una venta

    public function obtenerDetalleVenta($idventa)
    {
        try {
            $sql = "SELECT dv.cantidad, 
                       dv.precio_unitario_venta, 
                       dv.subtotal_general, 
                       
                       p.nombre as producto_nombre,
                       m.codigo_moneda
                FROM detalle_venta dv
                INNER JOIN producto p ON dv.idproducto = p.idproducto
                INNER JOIN monedas m ON dv.idmoneda = m.idmoneda
                WHERE dv.idventa = ?
                ORDER BY p.nombre";
            return $this->searchAll($sql, [$idventa]);
        } catch (Exception $e) {
            error_log("Error al obtener el detalle de la venta: " . $e->getMessage());
            throw new Exception("Error al obtener el detalle de la venta: " . $e->getMessage());
        }
    }

    // Método para eliminar/desactivar venta
    public function eliminarVenta($idventa)
    {
        return $this->executeTransaction(function ($mysql) use ($idventa) {

            // Verificar que la venta existe
            $venta = $this->search("SELECT COUNT(*) as count FROM venta WHERE idventa = ?", [$idventa]);
            if ($venta['count'] == 0) {
                throw new Exception("La venta especificada no existe.");
            }

            
            $sqlUpdate = "UPDATE venta SET estatus = 'Inactivo', ultima_modificacion = NOW() WHERE idventa = ?";
            $result = $this->update($sqlUpdate, [$idventa]);

            if ($result > 0) {
                return ['success' => true, 'message' => 'Venta desactivada exitosamente'];
            } else {
                throw new Exception("No se pudo desactivar la venta.");
            }
        });
    }


    // Método para buscar clientes por criterio
    public function buscarClientes($criterio)
    {
        try {
            $sql = "SELECT idcliente as id, nombre, apellido, cedula
                    FROM cliente
                    WHERE estatus = 'Activo' 
                    AND (nombre LIKE ? OR apellido LIKE ? OR cedula LIKE ?)
                    ORDER BY nombre, apellido
                    LIMIT 20";

            $parametroBusqueda = '%' . $criterio . '%';
            return $this->searchAll($sql, [$parametroBusqueda, $parametroBusqueda, $parametroBusqueda]);
        } catch (Exception $e) {
            error_log("Error al buscar clientes: " . $e->getMessage());
            throw new Exception("Error al buscar clientes: " . $e->getMessage());
        }
    }

    // Método para obtener productos para formulario
    public function getListaProductosParaFormulario()
    {
        try {
            $sql = "SELECT p.idproducto, 
                           p.nombre as nombre_producto,
                           p.precio as precio_unitario,
                           c.nombre as nombre_categoria
                    FROM productos p
                    LEFT JOIN categorias c ON p.idcategoria = c.idcategoria
                    WHERE p.activo = 1
                    ORDER BY p.nombre";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener productos para formulario: " . $e->getMessage());
            throw new Exception("Error al obtener productos para formulario: " . $e->getMessage());
        }
    }

    // Método para obtener monedas activas
    public function getMonedasActivas()
    {
        try {
            $sql = "SELECT idmoneda, codigo_moneda, nombre_moneda, valor
                    FROM monedas 
                    WHERE estatus = 'Activo'
                    ORDER BY codigo_moneda";
            return $this->searchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener monedas: " . $e->getMessage());
            throw new Exception("Error al obtener monedas: " . $e->getMessage());
        }
    }
}
