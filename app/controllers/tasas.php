<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php"; 
require_once "app/models/BcvScraperModel.php"; 

class Tasas extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    
    }

    public function set_model($model) {
        $this->model = $model; 
    }

    public function get_model() {
        return $this->model; 
    }


    public function index(){
        $data['page_title'] = "Gestión de Histórico de Tasas";
        $data['page_name'] = "Tasas BCV";
        $data['page_functions_js'] = "functions_tasas_bcv.js"; 
        $this->views->getView($this, "tasas", $data);
    }

    public function getTasas(){
        header('Content-Type: application/json');
        if (!($this->get_model() instanceof TasasModel)) {
             $this->set_model(new TasasModel());
        }

        $tasasUsd = $this->get_model()->obtenerTasasPorMoneda('USD', 5);
        $tasasEur = $this->get_model()->obtenerTasasPorMoneda('EUR', 5);

        $mensajeFlash = $_SESSION['mensaje_flash'] ?? null;
        unset($_SESSION['mensaje_flash']);

        echo json_encode([
            'tasasUsd' => $tasasUsd,
            'tasasEur' => $tasasEur,
            'mensajeFlash' => $mensajeFlash
        ]);
        exit;
    }

    public function actualizarTasasBCV(){
        header('Content-Type: application/json');
        
        if (!($this->get_model() instanceof TasasModel)) {
            $this->set_model(new TasasModel());
        }

        $bcvScraper = new BcvScraperModel(); 
        $monedasParaActualizar = ['USD', 'EUR'];
        $respuestasIndividuales = [];
        $huboExitoGeneral = false;
        $huboAdvertencia = false;

        foreach ($monedasParaActualizar as $moneda) {
            $datosTasa = $bcvScraper->obtenerDatosTasaBcv($moneda);

            if ($datosTasa && isset($datosTasa['tasa']) && isset($datosTasa['fecha_bcv'])) {
                $resultadoGuardado = $this->get_model()->guardarTasa(
                    $moneda,
                    $datosTasa['tasa'],
                    $datosTasa['fecha_bcv']
                );

                if ($resultadoGuardado === 'insertado') {
                    $respuestasIndividuales[] = "Tasa para {$moneda} actualizada a {$datosTasa['tasa']} VES (Fecha BCV: {$datosTasa['fecha_bcv']}).";
                    $huboExitoGeneral = true;
                } elseif ($resultadoGuardado === 'duplicado') {
                    $respuestasIndividuales[] = "Tasa para {$moneda} (Fecha BCV: {$datosTasa['fecha_bcv']}) ya estaba registrada.";
                    $huboAdvertencia = true;
                } else { 
                    $respuestasIndividuales[] = "Error al intentar guardar la tasa para {$moneda}.";
                }
            } else {
                $respuestasIndividuales[] = "No se pudieron obtener los datos actuales para {$moneda} desde el BCV.";
            }
        }

        $tipoRespuestaGeneral = 'error';
        if ($huboExitoGeneral) {
            $tipoRespuestaGeneral = 'exito';
        } elseif ($huboAdvertencia && !$huboExitoGeneral) { 
            $tipoRespuestaGeneral = 'advertencia';
        }
        
        if (empty($respuestasIndividuales)) {
             $respuestasIndividuales[] = "No se procesó ninguna actualización. Verifique la conexión o los logs.";
        }

        echo json_encode([
            'tipo' => $tipoRespuestaGeneral,
            'texto' => implode("<br>", $respuestasIndividuales) 
        ]);
        exit;
    }

    public function getTasasData()
    {
        if (!($this->get_model() instanceof TasasModel)) {
            $this->set_model(new TasasModel());
        }
        $arrData = $this->get_model()->SelectAllTasas();
        // Formato para DataTables
        $response = [
            "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            "recordsTotal" => count($arrData),
            "recordsFiltered" => count($arrData),
            "data" => $arrData
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>
