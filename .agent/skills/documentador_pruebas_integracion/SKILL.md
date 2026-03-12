---
name: Documentador de Pruebas de Integración
description: Genera automáticamente la documentación formal de las pruebas de integración de un módulo, leyendo los archivos reales de test y produciendo el texto listo para copiar y pegar en un documento con fuente Times New Roman 12, fondo transparente y letra negra.
---

# Documentador de Pruebas de Integración

Esta skill lee los archivos de pruebas de integración de un módulo específico y genera su documentación formal lista para insertar directamente en un documento de Word o Google Docs (Times New Roman 12, fondo transparente, letra negra).

## Pre-requisitos

- Las pruebas de integración del módulo deben estar en `tests/integrationTest/<NombreModulo>/`.
- Cada archivo debe seguir la convención `<accion><Modulo>IntegrationTest.php` (ej. `crearCompraIntegrationTest.php`).

## Proceso

Cuando el usuario indique el **nombre de un módulo** (ej. "Compras", "Ventas", "BCV"), ejecuta estrictamente los siguientes pasos:

### 1. Localizar los archivos de integración

Usa `find_by_name` en `tests/integrationTest/<NombreModulo>/` para listar todos los archivos `.php` existentes.

### 2. Leer cada archivo de prueba

Usa `view_file` en cada archivo encontrado. De cada uno extrae los siguientes datos reales:

- **Nombre del método de prueba** (`#[Test]` + nombre de función)
- **DataProvider** (si existe): nombre del provider y todos sus casos (`return [...]`)
- **setUp()**: qué datos se crean o cargan como precondición (productos, proveedores, etc.)
- **Parámetros de entrada** usados dentro de cada test (campos, valores fijos, valores aleatorios, etc.)
- **Assertions**: `assertEquals`, `assertFalse`, `assertTrue`, `assertIsArray`, `assertEmpty`, `assertNull`, `assertGreaterThan`, `assertStringContainsString`, etc.
- **Resultado esperado** por caso (status, message, tipo de retorno)

### 3. Generar en texto plano el bloque de documentación

Produce el siguiente texto **sin markdown, sin tablas, sin código**, listo para pegar en Word/Google Docs. Usa el siguiente esquema por cada archivo de prueba leído, respetando exactamente los encabezados en negrita:

---

**TIPO:** Integración (Caja blanca)

**OBJETIVO:** [Describe en 1–2 oraciones qué operación del módulo verifica esta suite y qué garantiza.]

**DESCRIPCIÓN:** [Describe el flujo completo: qué se crea en setUp, qué hace el test, qué escenarios cubre. Menciona si usa DataProvider y cuántos casos tiene. Sé específico con los datos reales del código.]

**ENTRADAS:**

- [Campo 1]: [valor o descripción real del código]
- [Campo 2]: [valor o descripción real del código]
- [... un bullet por cada input relevante encontrado en el código]

**SALIDAS ESPERADAS:**

- [Caso o método 1]: [descripción del resultado esperado real según las assertions]
- [Caso o método 2]: [descripción del resultado esperado real según las assertions]
- [... un bullet por cada caso o assertion relevante]

---

Repite este bloque completo para cada archivo de prueba del módulo.

Al final, genera un **bloque consolidado único** que agrupe TIPO, OBJETIVO, DESCRIPCIÓN, ENTRADAS y SALIDAS ESPERADAS de **todos los archivos** del módulo en un solo texto corrido (útil para documentos que requieren una sola sección por módulo).

### 4. Reglas de redacción

- **Fuente objetivo:** Times New Roman 12, fondo transparente, letra negra. El texto generado debe ser limpio y sin adornos para que al pegarse en Word/Google Docs adopte el formato del documento destino sin conflictos.
- Escribe en **español formal**.
- Usa información **100% real** extraída del código. No inventes campos ni valores que no estén en los archivos.
- Los valores de IDs inexistentes (ej. `888888 + rand(1, 99999)`) se describen como "valor aleatorio fuera de rango" para mayor claridad.
- Las entradas deben coincidir con los campos reales de los arreglos o parámetros del código (`$datosCompra`, `$detallesCompra`, DataProvider, etc.).
- Las salidas deben coincidir con los `assert*` reales del código.
- Si un método marca `markTestSkipped`, documéntalo como "precondición requerida: [motivo del skip]".

### 5. Presentar el resultado

Muestra primero la documentación separada por archivo (para revisión), y después el bloque unificado del módulo completo separado con una línea `---` para que el usuario pueda copiarlo fácilmente.

## Ejemplo de uso

Si el usuario dice *"documenta las pruebas de integración del módulo Ventas"*, irás a `tests/integrationTest/Ventas/`, leerás todos los archivos `.php`, extraerás la información real y generarás el texto en el formato descrito.
