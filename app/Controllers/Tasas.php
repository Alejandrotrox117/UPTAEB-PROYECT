<?php

use App\Models\BcvScraperModel;
use App\Models\TasasModel;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de tasas
 */
function getTasasModel()
{
    return new TasasModel();
}

/**
 * Renderiza una vista de tasas
 */
function renderTasasView($view, $data = [])
{
    renderView('tasas', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Vista principal de histórico de tasas BCV
 */
function tasas_index()
{
    $data['page_title'] = "Gestión de Histórico de Tasas";
    $data['page_name'] = "Tasas BCV";
    $data['page_functions_js'] = "functions_tasas_bcv.js";
    renderTasasView("tasas", $data);
}

/**
 * Obtener tasas USD y EUR recientes
 */
function tasas_getTasas()
{
    header('Content-Type: application/json');

    $objTasas = getTasasModel();

    $tasasUsd = $objTasas->obtenerTasasPorMoneda('USD', 5);
    $tasasEur = $objTasas->obtenerTasasPorMoneda('EUR', 5);

    $mensajeFlash = $_SESSION['mensaje_flash'] ?? null;
    unset($_SESSION['mensaje_flash']);

    echo json_encode([
        'tasasUsd' => $tasasUsd,
        'tasasEur' => $tasasEur,
        'mensajeFlash' => $mensajeFlash
    ]);
    exit;
}

/**
 * Actualizar tasas desde el BCV (scraping)
 */
function tasas_actualizarTasasBCV()
{
    header('Content-Type: application/json');

    $objTasas = getTasasModel();
    $bcvScraper = new BcvScraperModel();
    $monedasParaActualizar = ['USD', 'EUR'];
    $respuestasIndividuales = [];
    $huboExitoGeneral = false;
    $huboAdvertencia = false;

    foreach ($monedasParaActualizar as $moneda) {
        $datosTasa = $bcvScraper->obtenerDatosTasaBcv($moneda);

        if ($datosTasa && isset($datosTasa['tasa']) && isset($datosTasa['fecha_bcv'])) {
            $resultadoGuardado = $objTasas->guardarTasa(
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

/**
 * Obtener todas las tasas para DataTable
 */
function tasas_getTasasData()
{
    $objTasas = getTasasModel();
    $arrData = $objTasas->SelectAllTasas();

    $response = [
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => count($arrData),
        "recordsFiltered" => count($arrData),
        "data" => $arrData
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
