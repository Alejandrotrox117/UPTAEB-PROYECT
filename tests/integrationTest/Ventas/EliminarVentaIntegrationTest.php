<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EliminarVentaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $ventasModel;
    private $productosModel;
    private $clientesModel;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel = new ClientesModel();
    }

    private function getActivoCliente()
    {
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'] ?? [];
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo')
                return $c;
        }
        return null;
    }

    #[Test]
    public function testEliminarVentaExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $clienteActivo = $this->getActivoCliente();

        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('No cliente activo');

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 50,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 50
        ];
        $detallesVenta = [['idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_venta' => 10]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $idVenta = $resultadoInsercion['idventa'];

        $resultado = $this->ventasModel->eliminarVenta($idVenta);
        $this->assertTrue(is_array($resultado) ? $resultado['success'] : $resultado);

        $ventaEliminada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertNotEmpty($ventaEliminada);
        // Wait, the model sets to inactivo, check value
        $estatus = strtolower($ventaEliminada['estatus']);
        $this->assertTrue($estatus == 'inactivo' || $estatus == 'anulada');
    }

    #[Test]
    public function testEliminarVenta_Falla_VentaNoExiste()
    {
        $resultado = $this->ventasModel->eliminarVenta(999999);
        $this->assertFalse(is_array($resultado) ? $resultado['success'] : $resultado);
    }

    #[Test]
    public function testEliminarVenta_Falla_VentaNoBorrador()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $clienteActivo = $this->getActivoCliente();

        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('No cliente activo');

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 50,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 50
        ];
        $detallesVenta = [['idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_venta' => 10]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $idVenta = $resultadoInsercion['idventa'];

        $conexion = new \App\Core\Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        $db->exec("UPDATE venta SET estatus = 'PROCESADO' WHERE idventa = $idVenta");
        $conexion->disconnect();

        $resultado = $this->ventasModel->eliminarVenta($idVenta);
        $this->assertFalse(is_array($resultado) ? $resultado['success'] : $resultado);
    }
}
