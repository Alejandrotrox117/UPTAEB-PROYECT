---
name: Documentador de Pruebas Unitarias
description: Skill para documentar pruebas unitarias del proyecto, generando documentación estructurada con objetivos, técnicas, código involucrado, casos de prueba y resultados de ejecución.
---

# Documentador de Pruebas Unitarias

Esta skill te asiste en la generación automática de documentación completa para las pruebas unitarias de un módulo específico.

## Contexto del Proyecto

Las pruebas unitarias están organizadas en `tests/unitTest/`, divididas por módulos. Cada módulo tiene sus propios archivos de prueba que siguen las convenciones de PHPUnit.

## Proceso de Documentación

Cuando el usuario solicite documentar un módulo (ej: "Documenta las pruebas de Pagos"), sigue estos pasos:

### 1. Identificación del Módulo

- Extrae el nombre del módulo de la solicitud del usuario (ej: "Pagos", "Compras", "Productos")
- Determina el código del requisito funcional (RF) asociado al módulo si el usuario lo proporciona
- Si no se proporciona, asigna un código RF secuencial

### 2. Exploración de Archivos de Prueba

- Navega a `tests/unitTest/<NombreModulo>/`
- Lista todos los archivos `.php` de pruebas unitarias en ese directorio
- Lee el contenido completo de cada archivo de prueba
- Une todo el código en un solo bloque para la sección "Código Involucrado"

### 3. Análisis de las Pruebas

Analiza el código de las pruebas para extraer:

- **Métodos de prueba**: Identifica todos los métodos marcados con `#[Test]` o que inicien con `test`
- **Escenarios válidos e inválidos**: Examina los `DataProvider` y casos de prueba
- **Validaciones**: Identifica qué se está validando (proveedores, productos, cantidades, etc.)
- **Expectativas**: Revisa las aserciones (`assertEquals`, `assertTrue`, `assertFalse`, etc.)

### 4. Ejecución de Pruebas

Ejecuta las pruebas del módulo específico usando PHPUnit:

```powershell
php vendor/bin/phpunit tests/unitTest/<NombreModulo>/
```

O si hay una configuración específica:

```powershell
php vendor/bin/phpunit --configuration phpunit.xml tests/unitTest/<NombreModulo>/
```

Captura la salida completa de la terminal para la sección "Resultado".

### 5. Generación de la Documentación

Genera un documento con la siguiente estructura:

```markdown
## Cuadro Nº [Número]: Módulo de [Nombre del Módulo] (RF[Código])

### Objetivos de la prueba

[Describe el objetivo principal de validación. Ejemplo:]
Validar que [operación principal] solo se ejecute cuando [condiciones válidas]. El sistema debe rechazar [casos inválidos específicos].

### Técnicas

[Describe las técnicas de prueba utilizadas. Ejemplo:]
Pruebas de caja blanca con enfoque en [aspectos técnicos: integración, transacciones, aislamiento, etc.]. Se evalúa el método [nombreMétodo()] en escenarios válidos e inválidos, verificando [qué se verifica].

### Código Involucrado

```php
[Aquí va todo el código de las pruebas unitarias del módulo, unido de todos los archivos]
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** [Objetivo específico del caso de prueba]

**DESCRIPCIÓN:** [Descripción de los escenarios probados]

**ENTRADAS:**
[Describe las entradas de forma natural, como un texto corrido. Incluye los casos válidos e inválidos que se probaron, explicando qué datos se usaron y qué se esperaba de cada uno. No uses listas con flechas, sino párrafos descriptivos.]

**SALIDAS ESPERADAS:**
[Describe las salidas esperadas de manera natural, explicando qué debe pasar en casos exitosos y qué en casos de error. Escribe en párrafos fluidos, no en listas estructuradas.]

### Resultado

```
[Aquí va la salida completa de la ejecución de PHPUnit desde la terminal]
```

### Observaciones

[Escribe las observaciones de forma narrativa y natural. Cuenta lo que se probó como si estuvieras explicándoselo a alguien. Menciona cuántas pruebas se ejecutaron, qué aspectos se verificaron, si todo funcionó correctamente, y cualquier detalle relevante sobre el comportamiento del sistema. Usa un lenguaje fluido y descriptivo, evitando listas de bullets.]
```

### 6. Guardar la Documentación

Después de generar la documentación:

1. Crea un archivo `.md` con el nombre del módulo en formato kebab-case
2. Guarda el archivo en `.agent/skills/documentador_pruebas_unitarias/Documents/`
3. Nombra el archivo como: `documentacion-[modulo-en-minusculas].md`
4. Ejemplo: `documentacion-pagos.md`, `documentacion-compras.md`
5. Muestra al usuario el contenido generado Y confirma dónde se guardó el archivo

## Reglas Importantes

1. **Análisis completo**: Lee TODOS los archivos de prueba del módulo antes de generar la documentación
2. **Código completo**: Incluye el código completo de todas las pruebas, no resúmenes
3. **Ejecución real**: Siempre ejecuta las pruebas en la terminal para obtener resultados actualizados
4. **Formato consistente**: Mantén la estructura exacta del formato especificado
5. **Detalles precisos**: Extrae información real del código, no inventes datos
6. **Idioma**: Toda la documentación debe estar en español
7. **Lenguaje natural**: Escribe las entradas, salidas y observaciones en párrafos fluidos y naturales, no en listas estructuradas
8. **Almacenamiento**: Guarda SIEMPRE cada documentación en un archivo .md en la carpeta `Documents/`

## Ejemplo de Uso

**Usuario:** "Documenta las pruebas de Compras"

**Acciones del Agente:**
1. Ve a `tests/unitTest/Compra/`
2. Lee todos los archivos: `crearCompraUnitTest.php`, `editarCompraUnitTest.php`, `eliminarCompraUnitTest.php`, `consultarComprasUnitTest.php`
3. Une todo el código
4. Ejecuta: `php vendor/bin/phpunit tests/unitTest/Compra/`
5. Genera la documentación completa con toda la información

**Usuario:** "Ahora documenta Pagos"

**Acciones del Agente:**
1. Ve a `tests/unitTest/Pagos/`
2. Repite el mismo proceso para los archivos de Pagos
3. Genera la documentación correspondiente

## Formato de Salida

La documentación generada debe ser entregada como texto plano en formato Markdown, lista para copiar y pegar en un documento de trabajo.

## Notas Adicionales

- Si un módulo no tiene pruebas, informa al usuario
- Si hay errores al ejecutar las pruebas, inclúyelos en la sección "Resultado" y explícalos en "Observaciones"
- Numera los cuadros secuencialmente si el usuario documenta múltiples módulos
- Adapta el lenguaje técnico al contexto específico de cada módulo
