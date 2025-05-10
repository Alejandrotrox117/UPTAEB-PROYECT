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

    public function set_model($model) { $this->model = $model; }
    public function get_model() { return $this->model; }


    public function index()
    {

        $data['page_title'] = "Gestión de Histórico de Tasas";
        $data['page_name'] = "Tasas BCV";
        $data['page_functions_js'] = "functions_tasas_bcv.js"; 
        $this->views->getView($this, "tasas", $data);
    }

    // Endpoint para que el JS obtenga los datos iniciales de las tablas
    public function getTasas() // Renombrado desde tu getTasasData para coincidir con el JS
    {
        header('Content-Type: application/json');
        // Asegúrate de que $this->model es una instancia de TasasModel
        if (!($this->get_model() instanceof TasasModel)) {
             // Si tu framework no carga el modelo automáticamente, hazlo aquí:
             $this->set_model(new TasasModel()); // O usa tu método loadModel
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

    // Endpoint para actualizar AMBAS tasas (USD y EUR)
    public function actualizarTasasBCV() // Nuevo nombre para el método unificado
    {
        header('Content-Type: application/json');
        
        // Asegúrate de que $this->model es una instancia de TasasModel
        if (!($this->get_model() instanceof TasasModel)) {
            $this->set_model(new TasasModel());
        }

        $bcvScraper = new BcvScraperModel(); // Instanciamos el scraper
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
                } else { // false (error al guardar)
                    $respuestasIndividuales[] = "Error al intentar guardar la tasa para {$moneda}.";
                }
            } else {
                $respuestasIndividuales[] = "No se pudieron obtener los datos actuales para {$moneda} desde el BCV.";
            }
        }

        $tipoRespuestaGeneral = 'error'; // Por defecto
        if ($huboExitoGeneral) {
            $tipoRespuestaGeneral = 'exito';
        } elseif ($huboAdvertencia && !$huboExitoGeneral) { // Solo advertencia si no hubo ningún éxito
            $tipoRespuestaGeneral = 'advertencia';
        }
        
        // Si no hay mensajes, es un error genérico de que no se procesó nada
        if (empty($respuestasIndividuales)) {
             $respuestasIndividuales[] = "No se procesó ninguna actualización. Verifique la conexión o los logs.";
        }

        echo json_encode([
            'tipo' => $tipoRespuestaGeneral,
            'texto' => implode("<br>", $respuestasIndividuales) // Unir mensajes con <br> para display
        ]);
        exit;
    }

    // El método getTasasData para DataTables (si aún lo usas para otra cosa)
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
