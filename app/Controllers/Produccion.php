<?php

use App\Models\ProduccionModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Produccion - Estilo Funcional
 */

function getProduccionModel()
{
    return new ProduccionModel();
}

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

        $objProduccion = getProduccionModel();
        $arrResponse = $objProduccion->insertLote([
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
        $objProduccion = getProduccionModel();
        $arrResponse = $objProduccion->selectAllLotes();
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
        $objProduccion = getProduccionModel();
        $data = $objProduccion->selectLoteById(intval($idlote));
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
    $objProduccion = getProduccionModel();
    $res = $objProduccion->iniciarLoteProduccion(intval($request['idlote'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('lote_produccion', 'INICIAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_cerrarLote()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    $res = $objProduccion->cerrarLoteProduccion(intval($request['idlote'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('lote_produccion', 'CERRAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getConfiguracionProduccion()
{
    produccion_verificarSesion();
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectConfiguracionProduccion(), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_updateConfiguracionProduccion()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    $res = $objProduccion->updateConfiguracionProduccion($request);
    if ($res['status'])
        registrarEnBitacora('configuracion_produccion', 'ACTUALIZAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getPreciosProceso()
{
    produccion_verificarSesion();
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectPreciosProceso(), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_createPrecioProceso()
{
    produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->createPrecioProceso($data), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_updatePrecioProceso($idprecio)
{
    if (is_array($idprecio))
        $idprecio = $idprecio[0] ?? 0;
    produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->updatePrecioProceso(intval($idprecio), $data), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_deletePrecioProceso($idprecio)
{
    if (is_array($idprecio))
        $idprecio = $idprecio[0] ?? 0;
    produccion_verificarSesion();
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->deletePrecioProceso(intval($idprecio)), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getEmpleadosActivos()
{
    produccion_verificarSesion();
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectEmpleadosActivos(), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getProductos()
{
    produccion_verificarSesion();
    $tipo = $_GET['tipo'] ?? 'todos';
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectProductos($tipo), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getEstadisticasProduccion()
{
    produccion_verificarSesion();
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectEstadisticasProduccion($fecha), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getProduccionDiaria()
{
    produccion_verificarSesion();
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectProduccionDiaria($fecha), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_verificarDisponibilidadOperario()
{
    produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->verificarDisponibilidadOperario(intval($data['idempleado'] ?? 0), $data['fecha'] ?? date('Y-m-d')), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getResumenLote($idlote)
{
    if (is_array($idlote))
        $idlote = $idlote[0] ?? 0;
    produccion_verificarSesion();
    $objProduccion = getProduccionModel();
    echo json_encode($objProduccion->selectResumenLote(intval($idlote)), JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_limpiarRegistrosTemporales()
{
    $idusuario = produccion_verificarSesion();
    $objProduccion = getProduccionModel();
    $res = $objProduccion->limpiarRegistrosTemporales();
    if ($res['status'])
        registrarEnBitacora('sistema_produccion', 'LIMPIEZA');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_recalcularEstadisticas()
{
    $idusuario = produccion_verificarSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    $res = $objProduccion->recalcularEstadisticas($data['fecha_inicio'] ?? date('Y-m-01'), $data['fecha_fin'] ?? date('Y-m-d'));
    if ($res['status'])
        registrarEnBitacora('estadisticas_produccion', 'RECALCULAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_registrarSolicitudPago()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    $res = $objProduccion->registrarSolicitudPago($request['registros'] ?? []);
    if ($res['status'])
        registrarEnBitacora('sueldo_produccion', 'SOLICITUD_PAGO');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_marcarComoPagado()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    $res = $objProduccion->marcarRegistroComoPagado(intval($request['idregistro'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('registro_produccion', 'MARCAR_PAGADO');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_cancelarRegistroNomina()
{
    $idusuario = produccion_verificarSesion();
    $request = json_decode(file_get_contents('php://input'), true);
    $objProduccion = getProduccionModel();
    $res = $objProduccion->cancelarRegistroProduccion(intval($request['idregistro'] ?? 0));
    if ($res['status'])
        registrarEnBitacora('registro_produccion', 'CANCELAR');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

function produccion_getRegistrosProduccion()
{
    produccion_verificarSesion();
    try {
        $objProduccion = getProduccionModel();

        $filtros = [];
        if (isset($_GET['fecha_desde']))
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        if (isset($_GET['fecha_hasta']))
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        if (isset($_GET['tipo_movimiento']))
            $filtros['tipo_movimiento'] = $_GET['tipo_movimiento'];
        if (isset($_GET['idlote']))
            $filtros['idlote'] = $_GET['idlote'];

        $arrResponse = $objProduccion->selectAllRegistrosProduccion($filtros);
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'data' => []]);
    }
    die();
}

function produccion_actualizarRegistroProduccion($idregistro)
{
    produccion_verificarSesion();
    if (is_array($idregistro)) {
        $idregistro = $idregistro[0] ?? 0;
    }
    try {
        $request = json_decode(file_get_contents('php://input'), true);
        if (!$idregistro) {
            $idregistro = intval($request['idregistro'] ?? 0);
        }
        $objProduccion = getProduccionModel();
        $res = $objProduccion->actualizarRegistroProduccion(intval($idregistro), $request);
        if (isset($res['status']) && $res['status']) {
            registrarEnBitacora('registro_produccion', 'ACTUALIZAR');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function produccion_eliminarRegistroProduccion($idregistro)
{
    produccion_verificarSesion();
    if (is_array($idregistro)) {
        $idregistro = $idregistro[0] ?? 0;
    }
    try {
        if (!$idregistro) {
            $request = json_decode(file_get_contents('php://input'), true);
            $idregistro = intval($request['idregistro'] ?? 0);
        }
        $objProduccion = getProduccionModel();
        $res = $objProduccion->eliminarRegistroProduccion(intval($idregistro));
        if (isset($res['status']) && $res['status']) {
            registrarEnBitacora('registro_produccion', 'ELIMINAR');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function produccion_getRegistroById($idregistro)
{
    produccion_verificarSesion();
    if (is_array($idregistro)) {
        $idregistro = $idregistro[0] ?? 0;
    }
    try {
        $objProduccion = getProduccionModel();
        $res = $objProduccion->getRegistroById(intval($idregistro));
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'data' => []]);
    }
    die();
}

function produccion_getRegistrosPorLote($idlote)
{
    produccion_verificarSesion();
    if (is_array($idlote)) {
        $idlote = $idlote[0] ?? 0;
    }
    try {
        $objProduccion = getProduccionModel();
        $res = $objProduccion->obtenerRegistrosPorLote(intval($idlote));
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'data' => []]);
    }
    die();
}

function produccion_crearRegistroProduccion()
{
    $idusuario = produccion_verificarSesion();
    try {
        $data = [];
        if (!empty($_POST)) {
            $data = [
                'idlote' => $_POST['idlote'] ?? 0,
                'idempleado' => $_POST['idempleado'] ?? 0,
                'fecha_jornada' => $_POST['fecha'] ?? '',
                'tipo_movimiento' => $_POST['tipo_proceso'] ?? '',
                'idproducto_producir' => $_POST['idproducto_inicial'] ?? 0,
                'idproducto_terminado' => $_POST['idproducto_final'] ?? 0,
                'cantidad_producir' => $_POST['cantidad_producir'] ?? ($_POST['cantidad_inicial'] ?? 0),
                'cantidad_producida' => $_POST['cantidad_producida'] ?? 0,
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
        } else {
            $request = json_decode(file_get_contents('php://input'), true);
            $data = $request ?? [];
        }

        $objProduccion = getProduccionModel();
        $res = $objProduccion->insertarRegistroProduccion($data);
        if (isset($res['status']) && $res['status']) {
            registrarEnBitacora('registro_produccion', 'INSERTAR');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function produccion_actualizarLote($idlote)
{
    produccion_verificarSesion();
    if (is_array($idlote)) {
        $idlote = $idlote[0] ?? 0;
    }
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $objProduccion = getProduccionModel();
        $res = $objProduccion->actualizarLote(intval($idlote), $data);
        if (isset($res['status']) && $res['status']) {
            registrarEnBitacora('lotes_produccion', 'ACTUALIZAR');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error al actualizar lote']);
    }
    die();
}

function produccion_eliminarLote($idlote)
{
    produccion_verificarSesion();
    if (is_array($idlote)) {
        $idlote = $idlote[0] ?? 0;
    }
    try {
        $objProduccion = getProduccionModel();
        $res = $objProduccion->eliminarLote(intval($idlote));
        if (isset($res['status']) && $res['status']) {
            registrarEnBitacora('lotes_produccion', 'ELIMINAR');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error al eliminar lote']);
    }
    die();
}