<?php

use App\Models\MonedaModel;

/**
 * Controlador Moneda - Estilo Funcional
 */

function moneda_index()
{
    $data['page_title'] = "Gestión de moneda";
    $data['page_name'] = "moneda";
    $data['page_functions_js'] = "functions_moneda.js";
    renderView("moneda", "moneda", $data);
}

function moneda_getMonedaData()
{
    $model = new MonedaModel();
    $arrData = $model->SelectAllMoneda();

    echo json_encode([
        "recordsTotal" => count($arrData),
        "recordsFiltered" => count($arrData),
        "data" => $arrData
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function moneda_getMonedasActivas()
{
    try {
        $model = new MonedaModel();
        $monedas = $model->getMonedas();
        echo json_encode(["status" => true, "data" => $monedas]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

function moneda_crearMoneda()
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            echo json_encode(["status" => false, "message" => "Datos inválidos."]);
            exit();
        }

        $nombre = trim($data['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(["status" => false, "message" => "Nombre obligatorio."]);
            exit();
        }

        $model = new MonedaModel();
        $res = $model->insertMoneda([
            "nombre" => $nombre,
            "valor" => $data['valor'] ?? '',
            "estatus" => $data['estatus'] ?? 'ACTIVO',
        ]);

        echo json_encode(["status" => (bool) $res, "message" => $res ? "Registrada." : "Error."]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => $e->getMessage()]);
    }
    exit();
}

function moneda_actualizarMoneda()
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $idmoneda = $data['idmoneda'] ?? null;
        if (!$idmoneda) {
            echo json_encode(["status" => false, "message" => "ID obligatorio."]);
            exit();
        }

        $model = new MonedaModel();
        $res = $model->updateMoneda([
            "idmoneda" => $idmoneda,
            "nombre_moneda" => $data['nombre'] ?? '',
            "valor" => $data['valor'] ?? '',
            "estatus" => $data['estatus'] ?? '',
        ]);

        echo json_encode(["status" => (bool) $res, "message" => $res ? "Actualizada." : "Error."]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => $e->getMessage()]);
    }
    exit();
}

function moneda_deleteMoneda($idmoneda)
{
    try {
        $model = new MonedaModel();
        $res = $model->deleteMoneda($idmoneda);
        echo json_encode(["status" => (bool) $res, "message" => $res ? "Desactivada." : "Error."]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => $e->getMessage()]);
    }
    exit();
}

function moneda_getMonedaById($idmoneda)
{
    try {
        $model = new MonedaModel();
        $moneda = $model->getMonedaById($idmoneda);
        echo json_encode(["status" => (bool) $moneda, "data" => $moneda]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => $e->getMessage()]);
    }
    exit();
}
