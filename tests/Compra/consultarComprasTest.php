<?php 
use PHPUnit\Framework\TestCase;
require_once "app/models/ComprasModel.php";
require_once "helpers/helpers.php";
class consultarComprasTest extends TestCase{
	private $compras;
	private function showMessage(string $msg): void
	{
		fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
	}
	public function setUp():void{
		$this->compras = new ComprasModel();
	}

	public function testSelectAllComprasRetornaArray()
	{
		$result = $this->compras->selectAllCompras();
		$this->assertIsArray($result);
	}

	public function testGetCompraByIdConIdInexistente()
	{
		$result = $this->compras->getCompraById(888888 + rand(1, 99999));
		$this->assertFalse($result);
		$this->showMessage("Validación correcta: Compra inexistente retorna false");
	}

	public function testSelectCompraConIdInexistente()
	{
		$result = $this->compras->selectCompra(888888 + rand(1, 99999));
		$this->assertIsArray($result);
		$this->assertEmpty($result);
		$this->showMessage("Validación correcta: Select compra inexistente retorna array vacío");
	}

	public function testGetDetalleCompraByIdInexistente()
	{
		$result = $this->compras->getDetalleCompraById(888888 + rand(1, 99999));
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testGetCompraCompletaParaEditarInexistente()
	{
		$result = $this->compras->getCompraCompletaParaEditar(888888 + rand(1, 99999));
		$this->assertFalse($result);
		$this->showMessage("Validación correcta: Compra completa inexistente retorna false");
	}

	public function testObtenerEstadoCompraInexistente()
	{
		$result = $this->compras->obtenerEstadoCompra(888888 + rand(1, 99999));
		$this->assertNull($result);
	}

	public function testGetMonedasActivas()
	{
		$result = $this->compras->getMonedasActivas();
		$this->assertIsArray($result);
	}

	public function testGetProductosConCategoria()
	{
		$result = $this->compras->getProductosConCategoria();
		$this->assertIsArray($result);
	}

	public function testGetProductoByIdInexistente()
	{
		$result = $this->compras->getProductoById(888888 + rand(1, 99999));
		$this->assertFalse($result);
	}

	public function testGetProveedorByIdInexistente()
	{
		$result = $this->compras->getProveedorById(888888 + rand(1, 99999));
		$this->assertFalse($result);
	}

	protected function tearDown(): void
	{
		$this->compras = null;
	}
}
