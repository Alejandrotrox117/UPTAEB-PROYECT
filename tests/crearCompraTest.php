<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/proveedoresModel.php';

class crearCompraTest extends TestCase
{
    private $comprasModel;
    private $productosModel;
    private $proveedoresModel;

    public function setUp(): void
    {
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();
    }

    /**
     * * CASO DE PRUEBA: PRUEBA DE CAMINO BASE.
     * Objetivo: Asegurar que el camino de ejecución independiente más típico (la inserción exitosa) funcione correctamente "camino feliz"
     */
    public function testCrearCompraExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "El producto de prueba con ID 1 no pudo ser encontrado.");

        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $this->assertTrue($resultadoProveedores['status'], "La consulta de proveedores falló.");
        $proveedores = $resultadoProveedores['data'];
        $this->assertNotEmpty($proveedores, "No se encontraron proveedores en la base de datos para realizar la prueba.");
        $proveedor = $proveedores[0]; 

        $precioUnitario = 10.5;
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'], 
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria de creación de compra.',
        ];

        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnitario,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $subtotal,
                'subtotal_original_linea' => $subtotal,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado, "La función insertarCompra debe devolver un array.");
        $this->assertTrue($resultado['status'], "La inserción de la compra falló: " . ($resultado['message'] ?? 'Error desconocido'));
        $this->assertArrayHasKey('id', $resultado, "El resultado no contiene el ID de la compra insertada.");
        $this->assertGreaterThan(0, $resultado['id'], "La inserción de la compra devolvió un ID no válido.");

        $idCompraInsertada = $resultado['id'];
        $compraCreada = $this->comprasModel->getCompraById($idCompraInsertada);


        echo $resultado['message'];
    }

    /**
     * CASO DE PRUEBA: PRUEBA DE CONDICIÓN / PRUEBA DE BIFURCACIÓN.
     * Objetivo: Probar la rama o bifurcación 'falsa' de la condición de validación del proveedor.
     */
    public function testCrearCompraConProveedorInexistente()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $precioUnitario = 10.5;
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;

        // Datos generales de la compra con un proveedor que no existe
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => 99999, // ID de proveedor inexistente
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria con proveedor inexistente.',
        ];

        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnitario,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $subtotal,
                'subtotal_original_linea' => $subtotal,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        
        $this->assertIsArray($resultado, "La función debe devolver un array para indicar el fallo.");
        $this->assertFalse($resultado['status'], "La inserción de la compra debería fallar con un proveedor inexistente.");
        $this->assertStringContainsString('El proveedor con ID 99999 no existe.', $resultado['message'], "El mensaje de error no es el esperado.");
    }

    /**
     * CASO DE PRUEBA: PRUEBA DE BUCLES.
     * Objetivo: Probar el límite inferior del ciclo (cero iteraciones) que procesa los detalles de la compra.
     */
    public function testCrearCompraSinDetalles()
    {
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $this->assertTrue($resultadoProveedores['status'], "La consulta de proveedores falló.");
        $proveedores = $resultadoProveedores['data'];
        $this->assertNotEmpty($proveedores, "No se encontraron proveedores para la prueba.");
        $proveedor = $proveedores[0];

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'total_general_compra' => 0,
            'observaciones_compra' => 'Prueba de compra sin detalles.',
        ];

        // Array de detalles vacío
        $detallesCompra = [];

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado, "La función debe devolver un array.");
        $this->assertFalse($resultado['status'], "La inserción debería fallar sin detalles de compra.");
        $this->assertEquals('No hay detalles de compra para procesar.', $resultado['message'], "El mensaje de error no es el esperado.");
        $this->assertEquals('No hay detalles de compra para procesar.', $resultado['message'], "El mensaje de error no es el esperado.");
    }

    /**
     * CASO DE PRUEBA: PRUEBA DE FLUJO DE DATOS.
     * Objetivo: Validar la integridad de los datos (`cantidad`) a medida que fluyen a través de la lógica de negocio.
     */
    public function testCrearCompraConCantidadCero()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "El producto de prueba no fue encontrado.");

        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'total_general_compra' => 0,
            'observaciones_compra' => 'Prueba con cantidad cero.',
        ];

        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => 0, // Cantidad inválida, forzando un error de validación de datos
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 0,
            ]
        ];

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado, "La función debe devolver un array.");
        $this->assertFalse($resultado['status'], "La inserción debería fallar con cantidad cero.");
        $this->assertEquals('La cantidad o el precio unitario no pueden ser negativos o cero.', $resultado['message'], "El mensaje de error no es el esperado.");
    }

    /**
     * CASO DE PRUEBA: PRUEBA DE FLUJO DE DATOS.
     * Objetivo: Validar la integridad de los datos (`idproducto`) contra la base de datos durante el flujo de procesamiento.
     */
    public function testCrearCompraConProductoInexistente()
    {
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $this->assertTrue($resultadoProveedores['status'], "La consulta de proveedores falló.");
        $proveedores = $resultadoProveedores['data'];
        $this->assertNotEmpty($proveedores, "No se encontraron proveedores para la prueba.");
        $proveedor = $proveedores[0];

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'total_general_compra' => 100,
            'observaciones_compra' => 'Prueba con producto inexistente.',
        ];

        $detallesCompra = [
            [
                'idproducto' => 99999, // ID de producto inexistente
                'descripcion_temporal_producto' => 'Producto Fantasma',
                'cantidad' => 10,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 100,
            ]
        ];

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado, "La función debe devolver un array.");
        $this->assertFalse($resultado['status'], "La inserción debería fallar con un producto inexistente.");
        $this->assertEquals('El producto con ID 99999 no existe.', $resultado['message'], "El mensaje de error no es el esperado.");
    }
}
