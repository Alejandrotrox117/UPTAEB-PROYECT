<?php 
use PHPUnit\Framework\TestCase;
require_once "app/models/ComprasModel.php";
require_once "helpers/helpers.php";
class consultarComprasTest extends TestCase{
	private $compras;
	private function showMessage(string $msg): void
	{
		fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
	}
	public function setUp():void{
		$this->compras = new ComprasModel();
	}
}
