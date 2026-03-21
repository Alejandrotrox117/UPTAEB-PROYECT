<?php

namespace Tests\Traits;

use App\Core\Conexion;

/**
 * Trait para tests que requieren conexión a base de datos.
 * 
 * Uso: incluir `$this->requireDatabase()` al inicio de cada test
 * que necesite acceso a MySQL. Si la BD no está disponible,
 * el test se marca como "skipped" en lugar de fallar.
 * 
 * Esto permite que los tests unitarios puros pasen siempre,
 * mientras los tests de integración (DB) se ejecutan solo
 * cuando MySQL está corriendo.
 */
trait RequiresDatabase
{
    private static ?bool $dbDisponible = null;

    /**
     * Verifica que la base de datos esté disponible.
     * Cachea el resultado para no reconectar en cada test.
     * 
     * @throws \PHPUnit\Framework\SkippedTestError si no hay conexión
     */
    protected function requireDatabase(): void
    {
        if (self::$dbDisponible === null) {
            self::$dbDisponible = $this->verificarConexionBD();
        }

        if (!self::$dbDisponible) {
            $this->markTestSkipped(
                'Base de datos no disponible. Inicie MySQL para ejecutar tests de integración.'
            );
        }
    }

    /**
     * Intenta conectar a la BD y retorna true/false.
     */
    private function verificarConexionBD(): bool
    {
        try {
            // Suprimir echo de Conexion.php en caso de error
            ob_start();
            $conexion = new Conexion();
            $conexion->connect();
            
            // Limpiar datos huérfanos antes de correr las pruebas para evitar choques de IDs
            $db = $conexion->get_conectGeneral();
            $db->exec('SET FOREIGN_KEY_CHECKS = 0;
                       TRUNCATE TABLE venta;
                       TRUNCATE TABLE detalle_venta;
                       TRUNCATE TABLE movimientos_existencia;
                       TRUNCATE TABLE compra;
                       TRUNCATE TABLE detalle_compra;
                       SET FOREIGN_KEY_CHECKS = 1;');

            $conexion->disconnect();
            ob_end_clean();
            return true;
        } catch (\Throwable $e) {
            ob_end_clean();
            return false;
        }
    }
}

