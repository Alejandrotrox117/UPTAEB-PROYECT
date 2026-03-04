---
name: Refactorizador de Pruebas (Unit e Integration)
description: Refactoriza, de forma automática, las pruebas antiguas de los módulos para separarlas en Pruebas Unitarias (con Mocks y DataProviders) y Pruebas de Integración (con DB real y DataProviders).
---

# Refactorizador de Pruebas a Unit e Integración

Esta skill permite tomar todos los tests antiguos de un módulo en específico y refactorizarlos a los mejores estándares modernos. Separando la lógica en dos capas funcionales de testing.

## Pre-requisitos
- El sistema cuenta con dos directorios estandar para pruebas: `tests/unitTest/` y `tests/integrationTest/`.
- La librería `Mockery` está instalada para los mocks en Unit Tests.
- Existe el trait `\Tests\Traits\RequiresDatabase` para las pruebas de integración a BD real (`bd_pda_test`).

## Proceso de Refactorización

Cuando el usuario provea el **nombre de un módulo** (ej. "Compras", "Ventas", "Caja"), sigue estrictamente los siguientes pasos:

1. **Localizar las Pruebas Antiguas:**
   Busca todas las pruebas que actualmente estén ubicadas libremente dentro de la carpeta `tests/<NombreModulo>/` usando las herramientas de sistema (\`view_file\`, \`find_by_name\`, etc).

2. **Crear Directorios Modulares (Si no existen):**
   Crea a través de consola los directorios:
   - `tests/unitTest/<NombreModulo>/`
   - `tests/integrationTest/<NombreModulo>/`

3. **Construir la Prueba Unitaria (Unit Testing):**
   Analiza el test original y crea su versión unitaria en la ruta `tests/unitTest/<NombreModulo>/<NombrePrueba>UnitTest.php`.
   - **Reglas del Unit Test:** 
     - **Debe** utilizar la estructura `#[Test]` y `#[DataProvider(...)]` de PHPUnit.
     - **No** debe conectarse a ninguna base de datos real.
     - **Debe** emplear \`Mockery\` para sobrecargar la conexión al sistema usando \`overload:App\Core\Conexion\`, emulando el comportamiento de PDO (\`mockPdo\` y \`mockStmt\`).
     - Asegúrate de agregar validaciones para retornos falsos y control de errores por Mocks de las bases de datos.
     - El Namespace debe ser `namespace Tests\UnitTest\<NombreModulo>;`

4. **Construir la Prueba de Integración (Integration Testing):**
   Con el mismo caso analizado, crea su versión en `tests/integrationTest/<NombreModulo>/<NombrePrueba>IntegrationTest.php`.
   - **Reglas del Integration Test:**
     - **Debe** incluir el trait `use \Tests\Traits\RequiresDatabase;` y llamar a `$this->requireDatabase()` en el método `setUp()`.
     - **No** debe incluir objetos de simulación o librería `Mockery`. Todo comportamiento debe ser interactuando nativamente con las instancias reales de los Modelos (ej: `new VentasModel()`).
     - **Debe** utilizar `#[Test]` y `#[DataProvider(...)]` de PHPUnit para iterar por casos exitosos o fallidos inyectados.
     - El Namespace debe ser `namespace Tests\IntegrationTest\<NombreModulo>;`
     - Ajustar los paths (\`require_once\`) con las rutas correspondientes (`../../Traits/RequiresDatabase.php`).

5. **Eliminar el Test Antiguo:**
   Una vez que ambas pruebas (Unitaria y de Integración) estén guardadas y verificadas correctamente, elimina o mueve el archivo del test original en `tests/<NombreModulo>/`.

6. **Ejecutar las Pruebas:**
   Como comprobación final, siéntete libre de ejecutar los comandos `vendor\bin\phpunit --testsuite Unit` y `vendor\bin\phpunit --testsuite Integration` al terminar para constatar tus acciones.

## Ejemplo de uso
Si el usuario dice *Aplica la skill del refactorizador de pruebas al módulo Dashboard*, irás inmediatamente a buscar en `tests/Dashboard/`, leerás el contenido y aplicarás los 6 pasos descritos aquí arriba.
