<?php

use PHPUnit\Framework\TestCase;
use App\Core\Conexion;

class ConnectionTest extends TestCase
{
    public function testCanConnectToTestDatabase(): void
    {
        // Verificar variables de entorno de prueba
        $this->assertEquals('testing', env('APP_ENV'), 'APP_ENV debe ser testing');
        $this->assertEquals('bd_pda_test', env('DB_NAME_GENERAL'), 'DB_NAME_GENERAL debe ser bd_pda_test');

        // Instanciar conexión
        // Como Conexion usa constantes globales, y PHPUnit carga bootstrap, las constantes ya se definieron.
        // Pero si config.php define constantes usando env(), y env() lee de EnvLoader, y EnvLoader lee de putenv/$_ENV,
        // entonces al cambiar env vars en phpunit.xml, las constantes deberían reflejar los valores de prueba
        // SI Y SOLO SI config.php se carga DESPUES de que PHPUnit establezca las variables de entorno.
        
        // El problema es que bootstrap.php carga config.php.
        // PHPUnit carga phpunit.xml -> establece env vars -> ejecuta bootstrap.php -> carga config.php.
        // Así que las constantes deberían tener los valores de prueba.

        $conexion = new Conexion();
        
        try {
            $conexion->connect();
            $this->assertInstanceOf(PDO::class, $conexion->get_conectGeneral());
            $this->assertInstanceOf(PDO::class, $conexion->get_conectSeguridad());
            
            // Verificar nombre de la base de datos conectada
            $stmt = $conexion->get_conectGeneral()->query("SELECT DATABASE()");
            $dbName = $stmt->fetchColumn();
            $this->assertEquals('bd_pda_test', $dbName);
            
        } catch (\Exception $e) {
            $this->fail("Error de conexión: " . $e->getMessage());
        }
    }
}
