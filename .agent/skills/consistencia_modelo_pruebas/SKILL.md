---
name: Validador de Consistencia Modelo-Pruebas
description: Evalúa un módulo para garantizar que sus validaciones internas correspondan uno a uno con sus pruebas de falla correspondientes tanto Unitarias como de Integración.
---

# Validador de Consistencia Modelo-Pruebas

Esta skill tiene como objetivo garantizar la consistencia estricta entre las reglas de validación implementadas en los Modelos y las Pruebas Unitarias y de Integración. Cualquier condición que haga que un Modelo retorne un error o falla debe estar probada explícitamente y viceversa.

## Instrucciones de Ejecución

Cuando el usuario invoque esta skill y proporcione el nombre de un Módulo (por ejemplo, `Sueldos`), debes seguir los siguientes pasos metodológicamente:

### 1. Entendimiento del Modelo
1. Localiza el archivo del modelo correspondiente en la ruta `app/Models/<NombreModulo>Model.php`.
2. Analiza los métodos de alteración de estado (comúnmente `insert`, `update`, `delete` o equivalentes).
3. Haz un listado mental exhaustivo de todas y cada una de las **validaciones de retorno temprano** ('early returns' con `status => false`) que se ejecuten. Presta especial atención a validaciones de:
    - Tipos de datos o valores absolutos nulos/vacíos (`empty`, `isset`).
    - Rangos o límites de montos (`<= 0`, `> límite`).
    - Requisitos de base de datos o lógica de negocio (existencia de llaves foráneas, duplicidades lógicas).
    
### 2. Contraste con Pruebas Unitarias
1. Localiza la carpeta de Pruebas Unitarias en `tests/unitTest/<NombreModulo>/`.
2. Abre el archivo o archivos de prueba de las funciones analizadas (ej. `<NombreModulo>InsertUnitTest.php`).
3. Comprueba, validación por validación, que exista una prueba específica tipo `test<Accion>_Falla_<Motivo>` (ej. `testInsertSueldo_Falla_ConMontoNegativo`).
4. Si un caso de prueba asume una condición que no está validada en el código, o si el código valida algo que no tiene prueba:
    * Modifica las Pruebas o el Modelo, asegurándote de no borrar funcionalidades lógicas críticas y **basando siempre el comportamiento final** en las indicaciones del usuario o, si no las hay, en el sentido común de negocio extraído del Modelo.

### 3. Contraste con Pruebas de Integración
1. Repite el "Paso 2", pero en esta ocasión apuntando al directorio `tests/integrationTest/<NombreModulo>/`.
2. Cerciórate de que las Pruebas de Integración repliquen las pruebas de fallas de lógica de las pruebas unitarias. Por lo general, los tests de integración deben validar cómo interactúa el modelo con la BD usando fallas genuinas, validando el mismo DataProvider de Unit.

### 4. Ajustes y Reporte
1. Realiza los cambios necesarios empleando las herramientas de edición de archivos.
2. Tras la corrección o validación de ambos lados, **ejecuta las pruebas**.
    - Usa la herramienta `run_command` y pásale `vendor\bin\phpunit` sobre el archivo o carpeta.
    - Asegúrate de que tanto Unitarias como Integración arrojen verde (100% OK).
3. Escribe un reporte estructurado y conciso comentando al usuario:
    - Las discrepancias identificadas.
    - Los ajustes realizados (ya sea añadiendo un test ignorado, o agregando una validación al modelo que se había quedado fuera).
    - El status final con un comprobante corto de la ejecución de PHPUnit.

**Nota técnica:** Está estrictamente prohibido permitir casos de prueba que fallen intencionalmente un `assert` (es decir, el framework PHPUnit debe finalizar siempre exitosamente indicando si atrapó correctamente la excepción/falla modelada).
