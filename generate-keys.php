<?php

/**
 * Generador de claves de seguridad para el archivo .env
 */

echo "=== GENERADOR DE CLAVES DE SEGURIDAD ===\n\n";

/**
 * Genera una clave aleatoria segura
 */
function generateSecureKey($length = 64) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;:,.<>?';
    $key = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $key .= $characters[random_int(0, $max)];
    }
    
    return $key;
}

/**
 * Genera una clave hexadecimal
 */
function generateHexKey($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

echo "Claves generadas para tu archivo .env:\n\n";

echo "# Claves de Seguridad - Copia estas líneas a tu archivo .env\n";
echo "JWT_SECRET=" . generateSecureKey(64) . "\n";
echo "SESSION_SECRET=" . generateSecureKey(64) . "\n";
echo "ENCRYPTION_KEY=" . generateHexKey(32) . "\n\n";

echo "# Claves alternativas (hexadecimales)\n";
echo "JWT_SECRET=" . generateHexKey(64) . "\n";
echo "SESSION_SECRET=" . generateHexKey(64) . "\n";
echo "ENCRYPTION_KEY=" . generateHexKey(32) . "\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "1. Usa estas claves SOLO en producción\n";
echo "2. Guarda una copia segura de las claves\n";
echo "3. NUNCA compartas estas claves\n";
echo "4. Cambia las claves regularmente\n\n";

// Generar una clave específica si se pasa como parámetro
if (isset($argv[1])) {
    $type = strtolower($argv[1]);
    $length = isset($argv[2]) ? (int)$argv[2] : 64;
    
    echo "Clave específica solicitada ($type, $length caracteres):\n";
    
    switch ($type) {
        case 'jwt':
            echo generateSecureKey($length) . "\n";
            break;
        case 'session':
            echo generateSecureKey($length) . "\n";
            break;
        case 'encryption':
            echo generateHexKey(32) . "\n"; // Siempre 32 para encriptación
            break;
        case 'hex':
            echo generateHexKey($length) . "\n";
            break;
        default:
            echo generateSecureKey($length) . "\n";
    }
}

?>
