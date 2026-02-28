<?php

use App\Models\ProduccionModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Produccion - Estilo Funcional
 */

function produccion_verificarSesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idusuario = obtenerUsuarioSesion();
    if (!$idusuario) {
        header('Location: ' . base_url() . '/login');
        die();
    }
    return $idusuario;
}

function produccion_index()
{
    $idusuario = produccion_verificarSesion();
    if (!PermisosModuloVerificar::verificarAccesoModulo('produccion')) {
        renderView('errors', "permisos");
        exit();
    }
    registrarAccesoModulo('produccion', $idusuario);

    $data['page_tag'] = "Producción";
    $data['page_title'] = "Gestión de Producción";
    $data['page_name'] = "produccion";
    $data['page_content'] = "Control de lotes, operarios y procesos de producción";
    $data['page_functions_js'] = "functions_produccion.js";
    renderView("produccion", "produccion", $data);
}

function produccion_createLote()
{
    $idusuario = produccion_verificarSesion();
    try {
        $request = json_decode(file_get_contents('php://input'), true);
        if (empty($request['fecha_jornada'])) {
            echo json_encode(['status' => false, 'message' => 'Faltan datos']);
            die();
        }

        $model = new ProduccionModel();
        $arrResponse = $model->insertLote([
            'fecha_jornada' => $request['fecha_jornada'],
            'volumen_estimado' => floatval($request['volumen_estimado']),
            'idsupervisor' => intval($request['idsupervisor']),
            'observaciones' => trim($request['observaciones'] ?? '')
        ]);

        if ($arrResponse['status'])
            registrarEnBitacora('lote_produccion', 'INSERTAR');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function produccion_getLotesData()
{
    produccion_verificarSesion();
    try {
        $model = new ProduccionModel();
        $arrResponse = $model->selectAllLotes();
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'data' => []]);
    }
    die();
}

function produccion_getLoteById($idlote)
{
    produccion_verificarSesion();
    if (is_array($idlote))
        $idlote = $idlote[0] ?? 0;
    try {
        $model = new ProduccionModel();
        $data = $model->selectLoteById(intval($idlote));
        echo json_encode($data ? ['status' => true, 'data' => $data] : ['status' => false]);
    } catch (Exception $e) {
        echo json_encode(['status' => false]);
    }
    die();
}

function produccion_iniciarLote()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->iniciarLoteProduccion(intval($request['idlote'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('lote_produccion', 'INICIAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_cerrarLote()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->cerrarLoteProduccion(intval($request['idlote'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('lote_produccion', 'CERRAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getConfiguracionProduccion()
{
    produccion_verificarSesion();
    $model = new ProduccionModel();
    echo json_encode($model->selectConfiguracionProduccion(), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_updateConfiguracionProduccion()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->updateConfiguracionProduccion($request);
    if ($res['status'])
        registrarEnBitacora('configuracion_produccion', 'ACTUALIZAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getPreciosProceso()
{
    produccion_verificarSesion();
    $model = new ProduccionModel();
    echo json_encode($model->selectPreciosProceso(), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_createPrecioProceso()
{
    produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    echo json_encode($model->createPrecioProceso($data), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_updatePrecioProceso($idprecio)
{
    if (is_array($idprecio))
        $idprecio = $idprecio[0] ?? 0;
    produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    echo json_encode($model->updatePrecioProceso(intval($idprecio), $data), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_deletePrecioProceso($idprecio)
{
    if (is_array($idprecio))
        $idprecio = $idprecio[0] ?? 0;
    produccion_verificarSesion();
    $model = new ProduccionModel();
    echo json_encode($model->deletePrecioProceso(intval($idprecio)), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getEmpleadosActivos()
{
    produccion_verificarSesion();
    $model = new ProduccionModel();
    echo json_encode($model->selectEmpleadosActivos(), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getProductos()
{
    produccion_verificarSesion();
    $tipo = $_GET['tipo'] ?? 'todos';
    $model = new ProduccionModel();
    echo json_encode($model->selectProductos($tipo), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getEstadisticasProduccion()
{
    produccion_verificarSesion();
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $model = new ProduccionModel();
    echo json_encode($model->selectEstadisticasProduccion($fecha), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getProduccionDiaria()
{
    produccion_verificarSesion();
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $model = new ProduccionModel();
    echo json_encode($model->selectProduccionDiaria($fecha), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_verificarDisponibilidadOperario()
{
    produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    echo json_encode($model->verificarDisponibilidadOperario(intval($data['idempleado'] ?? 0), $data['fecha'] ?? date('Y-m-d')), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getResumenLote($idlote)
{
    if (is_array($idlote))
        $idlote = $idlote[0] ?? 0;
    produccion_verificarSesion();
    $model = new ProduccionModel();
    echo json_encode($model->selectResumenLote(intval($idlote)), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_limpiarRegistrosTemporales()
{
    $idusuario = produccion_verificarSesion();
    $model = new ProduccionModel();
    $res = $model->limpiarRegistrosTemporales();
    if ($res['status'])
        registrarEnBitacora('sistema_produccion', 'LIMPIEZA');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_recalcularEstadisticas()
{
    $idusuario = produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->recalcularEstadisticas($data['fecha_inicio'] ?? date('Y-m-01'), $data['fecha_fin'] ?? date('Y-m-d'));
    if ($res['status'])
        registrarEnBitacora('estadisticas_produccion', 'RECALCULAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_registrarSolicitudPago()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->registrarSolicitudPago($request['registros'] ?? []);
    if ($res['status'])
        registrarEnBitacora('sueldo_produccion', 'SOLICITUD_PAGO');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_marcarComoPagado()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->marcarRegistroComoPagado(intval($request['idregistro'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('registro_produccion', 'MARCAR_PAGADO');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_cancelarRegistroNomina()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $model = new ProduccionModel();
    $res = $model->cancelarRegistroProduccion(intval($request['idregistro'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('registro_produccion', 'CANCELAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}