<?php

/**
 * Controlador Home - Estilo Funcional
 */

/**
 * Página principal - index
 */
function home_index()
{
    $data['page_id'] = 1;
    $data["page_title"] = "Pagina principal";
    $data["tag_page"] = "La pradera de pavia";
    $data["page_name"] = "Home";

    renderView("home", "home", $data);
}

/**
 * Página principal - home (alias o método con parámetros)
 */
function home_home($params = null)
{
    $data['page_id'] = 1;
    $data["page_title"] = "Pagina principal";
    $data["tag_page"] = "La pradera de pavia";
    $data["page_name"] = "Home";

    if ($params) {
        // En estilo funcional, los parámetros llegarían como argumentos de la función
        // Pero el método original hacía un echo si había params.
    }

    renderView("home", "home", $data);
}