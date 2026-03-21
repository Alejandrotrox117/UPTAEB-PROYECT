<?php

function findTests($dir) {
    $tests = [];
    if (!is_dir($dir)) return $tests;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/Test\.php$/', $file->getFilename())) {
            $tests[] = $file->getPathname();
        }
    }
    sort($tests);
    return $tests;
}

function runSequential($type, $dir) {
    echo "\n======================================================================\n";
    echo "  EJECUTANDO PRUEBAS " . strtoupper($type) . " SECUENCIALMENTE\n";
    echo "======================================================================\n\n";
    
    $files = findTests($dir);
    if (empty($files)) {
        echo "No se encontraron pruebas en la ruta especificada.\n";
        return;
    }
    
    $total = count($files);
    $current = 1;

    foreach ($files as $file) {
        // Formatear la ruta para que sea relativa y amigable
        $relativePath = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
        
        echo "[$current/$total] Evaluando: $relativePath\n";
        
        // Adaptar comando para Windows o Linux/Mac
        $phpunitBin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
                      ? 'vendor\bin\phpunit' 
                      : 'vendor/bin/phpunit';
                      
        $command = escapeshellcmd($phpunitBin) . " " . escapeshellarg($file) . " --colors=always";
        
        // Ejecutamos passthru para ver la salida original en tiempo real
        passthru($command, $status);
        
        if ($status !== 0) {
            echo ">>> RESULTADO: FALLO O ADVERTENCIA (Codigo $status)\n";
        } else {
            echo ">>> RESULTADO: EXITOSO\n";
        }
        echo str_repeat("-", 70) . "\n";
        $current++;
    }
}

$unitDir = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'unitTest';
$integrationDir = __DIR__ . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'integrationTest';

runSequential('Unitarias', $unitDir);
runSequential('de Integración', $integrationDir);

echo "\n¡Ejecución secuencial de todas las pruebas finalizada!\n";
