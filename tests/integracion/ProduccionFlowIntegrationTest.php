<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';
require_once __DIR__ . '/../../app/models/productosModel.php';
require_once __DIR__ . '/../../app/models/empleadosModel.php';

class ProduccionFlowIntegrationTest extends TestCase
{
    private $produccionModel;
    private $productosModel;
    private $empleadosModel;
    private $loteIdPrueba;
    private $numeroLotePrueba;
    
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[INTEGRATION TEST] " . $msg . "\n");
    }
    
    protected function setUp(): void
    {
        $this->produccionModel = new ProduccionModel();
        $this->productosModel = new ProductosModel();
        $this->empleadosModel = new EmpleadosModel();
    }
    
    public function testFlujoCompletoProduccionLote()
    {
        $volumenEstimado = 500 + rand(1, 500);
        
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => $volumenEstimado,
            'fecha_jornada' => date('Y-m-d', strtotime('+' . rand(1, 5) . ' days')),
            'observaciones' => 'Lote de prueba de integración - ' . time()
        ];
        
        $resultadoLote = $this->produccionModel->insertLote($dataLote);
        $this->assertTrue($resultadoLote['status'], "Fallo al crear lote de producción");
        $this->assertArrayHasKey('lote_id', $resultadoLote);
        $this->assertArrayHasKey('numero_lote', $resultadoLote);
        
        $this->loteIdPrueba = $resultadoLote['lote_id'];
        $this->numeroLotePrueba = $resultadoLote['numero_lote'];
        
        $loteCreado = $this->produccionModel->selectLoteById($this->loteIdPrueba);
        $this->assertNotFalse($loteCreado, "No se pudo recuperar el lote creado");
        $this->assertEquals('PLANIFICADO', $loteCreado['estatus_lote']);
        $this->assertNull($loteCreado['fecha_inicio_real'], "La fecha de inicio debe ser nula en estado PLANIFICADO");
        
        $resultadoInicio = $this->produccionModel->iniciarLoteProduccion($this->loteIdPrueba);
        $this->assertTrue($resultadoInicio['status'], "Fallo al iniciar lote: " . ($resultadoInicio['message'] ?? ''));
        
        $loteIniciado = $this->produccionModel->selectLoteById($this->loteIdPrueba);
        $this->assertNotFalse($loteIniciado, "No se pudo recuperar el lote después de iniciarlo");
        $this->assertEquals('EN_PROCESO', $loteIniciado['estatus_lote']);
        $this->assertNotNull($loteIniciado['fecha_inicio_real'], "Debe tener fecha de inicio registrada");
        
        $empleados = $this->empleadosModel->selectAllEmpleados(1);
        if (!$empleados['status'] || empty($empleados['data'])) {
            $idEmpleado = 1;
        } else {
            $idEmpleado = $empleados['data'][0]['idempleado'];
        }
        
        $dataRegistro1 = [
            'idlote' => $this->loteIdPrueba,
            'idempleado' => $idEmpleado,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 95,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        
        $resultadoRegistro1 = $this->produccionModel->insertarRegistroProduccion($dataRegistro1);
        if ($resultadoRegistro1['status']) {
            $this->showMessage($resultadoRegistro1['message']);
        } else {
            $this->showMessage($resultadoRegistro1['message']);
        }
        
        $dataRegistro2 = [
            'idlote' => $this->loteIdPrueba,
            'idempleado' => $idEmpleado,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 2,
            'cantidad_producir' => 95,
            'idproducto_terminado' => 3,
            'cantidad_producida' => 92,
            'tipo_movimiento' => 'EMPAQUE'
        ];
        
        $resultadoRegistro2 = $this->produccionModel->insertarRegistroProduccion($dataRegistro2);
        if ($resultadoRegistro2['status']) {
            $this->showMessage($resultadoRegistro2['message']);
        } else {
            $this->showMessage($resultadoRegistro2['message']);
        }
        
        $resultadoCierre = $this->produccionModel->cerrarLoteProduccion($this->loteIdPrueba);
        $this->assertTrue($resultadoCierre['status'], "Fallo al cerrar lote: " . ($resultadoCierre['message'] ?? ''));
        
        $loteFinalizado = $this->produccionModel->selectLoteById($this->loteIdPrueba);
        $this->assertNotFalse($loteFinalizado, "No se pudo recuperar el lote después de cerrarlo");
        $this->assertEquals('FINALIZADO', $loteFinalizado['estatus_lote']);
        $this->assertNotNull($loteFinalizado['fecha_fin_real'], "Debe tener fecha de cierre registrada");
        
        $intentoReinicio = $this->produccionModel->iniciarLoteProduccion($this->loteIdPrueba);
        $this->assertFalse($intentoReinicio['status'], "No debería poder reiniciar un lote finalizado");
        $this->showMessage($intentoReinicio['message']);
        
        $intentoReCierre = $this->produccionModel->cerrarLoteProduccion($this->loteIdPrueba);
        $this->assertFalse($intentoReCierre['status'], "No debería poder cerrar nuevamente un lote finalizado");
        $this->showMessage($intentoReCierre['message']);
    }    public function testCrearMultiplesLotesMismaFecha()
    {
        $fechaComun = date('Y-m-d', strtotime('+20 days'));
        $numerosLote = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $dataLote = [
                'idsupervisor' => 1,
                'volumen_estimado' => (100 * $i) + rand(50, 150),
                'fecha_jornada' => $fechaComun,
                'observaciones' => 'Lote múltiple ' . $i . ' - ' . time()
            ];
            
            $resultado = $this->produccionModel->insertLote($dataLote);
            $this->assertTrue($resultado['status'], "Fallo al crear lote " . $i);
            
            if ($resultado['status']) {
                $numerosLote[] = $resultado['numero_lote'];
            }
        }
        
        $numerosUnicos = array_unique($numerosLote);
        $this->assertCount(3, $numerosUnicos, "Todos los números de lote deben ser únicos");
    }
    
    public function testValidacionesNegocioProduccion()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 300,
            'fecha_jornada' => date('Y-m-d', strtotime('+25 days')),
            'observaciones' => 'Lote para validación'
        ];
        
        $loteCreado = $this->produccionModel->insertLote($dataLote);
        if ($loteCreado['status']) {
            $intentoCierre = $this->produccionModel->cerrarLoteProduccion($loteCreado['lote_id']);
            $this->showMessage($intentoCierre['message']);
        }
        
        $dataRegistroInvalido = [
            'idlote' => 888888 + rand(1, 99999), // ID inexistente
            'idempleado' => 1,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 95,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        
        $resultadoRegistroInvalido = $this->produccionModel->insertarRegistroProduccion($dataRegistroInvalido);
        $this->assertFalse($resultadoRegistroInvalido['status'], "No debería crear registro con lote inexistente");
        $this->showMessage($resultadoRegistroInvalido['message']);
        
        $dataSinSupervisor = [
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d')
        ];
        
        $resultadoSinSupervisor = $this->produccionModel->insertLote($dataSinSupervisor);
        $this->assertFalse($resultadoSinSupervisor['status'], "No debería crear lote sin supervisor");
        $this->showMessage($resultadoSinSupervisor['message']);
        
        $dataVolumenInvalido = [
            'idsupervisor' => 1,
            'volumen_estimado' => -100,
            'fecha_jornada' => date('Y-m-d')
        ];
        
        $resultadoVolumenInvalido = $this->produccionModel->insertLote($dataVolumenInvalido);
        $this->assertFalse($resultadoVolumenInvalido['status'], "No debería crear lote con volumen negativo");
        $this->showMessage($resultadoVolumenInvalido['message']);
    }
    
    protected function tearDown(): void
    {
        $this->produccionModel = null;
        $this->productosModel = null;
        $this->empleadosModel = null;
    }
}
