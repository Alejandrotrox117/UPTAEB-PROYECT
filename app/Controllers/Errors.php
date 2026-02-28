<?php

/**
 * Controlador de Errores - Estilo Funcional
 */

/**
 * Página de error 404 / Página no encontrada
 */
function errors_notFound()
{
    renderView('errors', 'error');
}

/**
 * Página principal de errores (alias de notFound)
 */
function errors_index()
{
    renderView('errors', 'error');
}
