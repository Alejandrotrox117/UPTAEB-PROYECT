<?php

use App\Models\CategoriasModel;

// =============================================================================
// CONSTANTES DEL MÓDULO
// =============================================================================

// Categorías del sistema que no se pueden eliminar
// 1=Pacas, 2=Materiales, 3=Consumibles
define('CATEGORIAS_SISTEMA', [1, 2, 3]);

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de categorías
 */
function getCategoriasModel()
{
    return new CategoriasModel();
}

/**
 * Renderiza una vista de categorías
 */
function renderCategoriasView($view, $data = [])
{
    renderView('categorias', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Vista principal de categorías
 */
function categorias_index()
{
    $data['page_title'] = "Gestion de categorias";
    $data['page_name'] = "categorias";
    $data['page_functions_js'] = "functions_categorias.js";
    renderCategoriasView("categorias", $data);
}

/**
 * Obtiene todas las categorías para DataTable
 */
function categorias_getCategoriasData()
{
    $objCategorias = getCategoriasModel();
    $arrData = $objCategorias->SelectAllCategorias();

    $response = [
        "recordsTotal" => count($arrData),
        "recordsFiltered" => count($arrData),
        "data" => $arrData
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Crear una nueva categoría
 */
function categorias_crearCategoria()
{
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "No se recibieron datos validos."]);
            exit();
        }

        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $estatus = trim($data['estatus'] ?? 'ACTIVO');

        if (empty($nombre)) {
            echo json_encode(["status" => false, "message" => "El nombre de la categoria es obligatorio."]);
            exit();
        }

        $objCategorias = getCategoriasModel();
        $insertData = $objCategorias->insertCategoria([
            "nombre" => $nombre,
            "descripcion" => $descripcion,
            "estatus" => $estatus,
        ]);

        if ($insertData) {
            echo json_encode(["status" => true, "message" => "Categoria registrada correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al registrar la categoria. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Actualizar una categoría existente
 */
function categorias_actualizarCategoria()
{
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "No se recibieron datos validos."]);
            exit();
        }

        $idcategoria = trim($data['idcategoria'] ?? null);
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $estatus = trim($data['estatus'] ?? '');

        if (empty($idcategoria) || empty($nombre)) {
            echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
            exit();
        }

        $objCategorias = getCategoriasModel();
        $updateData = $objCategorias->updateCategoria([
            "idcategoria" => $idcategoria,
            "nombre" => $nombre,
            "descripcion" => $descripcion,
            "estatus" => $estatus,
        ]);

        if ($updateData) {
            echo json_encode(["status" => true, "message" => "Categoria actualizada correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al actualizar la categoria. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Eliminar (desactivar) una categoría
 */
function categorias_deleteCategoria($idcategoria)
{
    try {
        if (empty($idcategoria)) {
            echo json_encode(["status" => false, "message" => "ID de categoria no proporcionado."]);
            exit();
        }

        // Validar que no sea una categoría del sistema
        if (in_array((int) $idcategoria, CATEGORIAS_SISTEMA)) {
            echo json_encode([
                "status" => false,
                "message" => "No se puede eliminar esta categoria porque es una categoria del sistema."
            ]);
            exit();
        }

        $objCategorias = getCategoriasModel();
        $deleteData = $objCategorias->deleteCategoria($idcategoria);

        if ($deleteData) {
            echo json_encode(["status" => true, "message" => "Categoria desactivada correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al desactivar la categoria. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Obtener una categoría por su ID
 */
function categorias_getCategoriaById($idcategoria)
{
    try {
        $objCategorias = getCategoriasModel();
        $categoria = $objCategorias->getCategoriaById($idcategoria);

        if ($categoria) {
            echo json_encode(["status" => true, "data" => $categoria]);
        } else {
            echo json_encode(["status" => false, "message" => "Categoria no encontrada."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Reactivar una categoría inactiva
 */
function categorias_reactivarCategoria()
{
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "No se recibieron datos validos."]);
            exit();
        }

        $idcategoria = intval($data['idcategoria'] ?? 0);

        if ($idcategoria <= 0) {
            echo json_encode(["status" => false, "message" => "ID de categoria invalido."]);
            exit();
        }

        $objCategorias = getCategoriasModel();
        $reactivarData = $objCategorias->reactivarCategoria($idcategoria);

        if ($reactivarData) {
            echo json_encode(["status" => true, "message" => "Categoria reactivada correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al reactivar la categoria. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}
