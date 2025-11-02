<?php
// Script para eliminar comentarios (//, /* */, /** */) de archivos PHP dentro de tests/
// Uso: php strip_comments.php

$root = __DIR__ . DIRECTORY_SEPARATOR . 'tests';
if (!is_dir($root)) {
    echo "No se encontró la carpeta tests/ en: $root\n";
    exit(1);
}

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$files = 0;
$processed = 0;

foreach ($it as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
        $files++;
        $path = $file->getPathname();
        $code = file_get_contents($path);
        if ($code === false) continue;

        $tokens = token_get_all($code);
        $out = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $id = $token[0];
                $text = $token[1];
                if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                    // mantener saltos de línea equivalentes para no alterar números de línea
                    $out .= str_repeat("\n", substr_count($text, "\n"));
                } else {
                    $out .= $text;
                }
            } else {
                $out .= $token;
            }
        }

        // Escribir sólo si cambió
        if ($out !== $code) {
            file_put_contents($path, $out);
            $processed++;
            echo "Procesado: $path\n";
        }
    }
}

echo "Encontrados: $files archivos PHP. Procesados (comentarios eliminados): $processed\n";
return 0;
