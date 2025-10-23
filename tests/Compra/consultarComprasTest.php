<?php 
use PHPUnit\Framework\TestCase;
require_once "app/models/ComprasModel.php";
require_once "helpers/helpers.php";

class consultarComprasTest extends TestCase{
	private $compras;
	public function setUp():void{
		$this->compras = new ComprasModel();
	}
	public function testConsultaExitosa()
    {
        $idusuario = 1;
        $respuesta = $this->compras->selectAllCompras($idusuario);
        $this->assertNotEmpty($respuesta);
    }
}