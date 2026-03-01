---
name: Validador y Limpiador de Módulos
description: Skill para validar que los tours no se inicien automáticamente y limpiar logs debug y comentarios innecesarios de los módulos.
---

# Validador y Limpiador de Módulos

Esta skill proporciona instrucciones paso a paso para validar que la ayuda (tours) no se abra automáticamente y limpiar el código de un módulo específico eliminando logs de debug y comentarios innecesarios.

## Objetivo

Cuando se solicita validar y limpiar un módulo, el agente debe:
1. **Validar y remover el auto-inicio del tour** del módulo especificado
2. **Eliminar logs de debug** (console.log, console.debug, error_log, print_r, var_dump)
3. **Eliminar comentarios innecesarios** que sean código comentado o comentarios obvios

## Pasos a Seguir

### 1. Identificar el Módulo

Cuando el usuario solicita validar un módulo (ej: "clientes", "proveedores", "ventas"):
- Determinar el nombre del módulo
- Identificar todos los archivos relacionados

### 2. Validar y Deshabilitar Auto-inicio del Tour

**Archivos a revisar:**
- `app/assets/js/ayuda/{modulo}-tour.js`

**Patrón a buscar:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // ...
    const tourCompleted = localStorage.getItem('{modulo}-tour-completed');
    
    if (!tourCompleted) {
        iniciarTour{Modulo}();
    }
    // ...
});
```

**Acción requerida:**
- **COMENTAR o ELIMINAR** completamente el bloque que inicia automáticamente el tour
- El bloque típicamente incluye:
  - La verificación de `localStorage.getItem('{modulo}-tour-completed')`
  - El `setTimeout` que retrasa el inicio
  - La llamada a `iniciarTour{Modulo}()` dentro del condicional
- **MANTENER**:
  - La función `agregarBotonAyuda{Modulo}()` debe seguir ejecutándose
  - La exportación global `window.iniciarTour{Modulo} = iniciarTour{Modulo};`
  - La función principal `iniciarTour{Modulo}()` completa

**Ejemplo de limpieza:**

ANTES:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    agregarBotonAyudaClientes();
    
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('clientes-tour-completed');
        
        if (!tourCompleted) {
            iniciarTourClientes();
        }
    }, 1000);
});

window.iniciarTourClientes = iniciarTourClientes;
```

DESPUÉS:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    agregarBotonAyudaClientes();
    
    // Auto-inicio del tour deshabilitado - solo se inicia manualmente desde el botón de ayuda
    // setTimeout(() => {
    //     const tourCompleted = localStorage.getItem('clientes-tour-completed');
    //     
    //     if (!tourCompleted) {
    //         iniciarTourClientes();
    //     }
    // }, 1000);
});

window.iniciarTourClientes = iniciarTourClientes;
```

O completamente eliminado:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    agregarBotonAyudaClientes();
});

window.iniciarTourClientes = iniciarTourClientes;
```

### 3. Limpiar Logs de Debug en PHP

**Archivos a revisar:**
- `app/Controllers/{Modulo}.php`
- `app/Models/{Modulo}Model.php`
- Cualquier helper o archivo relacionado en `helpers/`

**Patrones a eliminar:**

1. **error_log()**:
   ```php
   error_log("Debug: ...");
   error_log("🔍 DEBUG ...");
   error_log("Datos recibidos: " . print_r($data, true));
   ```

2. **print_r() y var_dump() en contextos de debug**:
   ```php
   print_r($variable);
   var_dump($data);
   echo "<pre>" . print_r($array, true) . "</pre>";
   ```

3. **Excepciones**: NO eliminar error_log que sean parte del manejo de errores críticos en bloques catch o validaciones importantes.

**Regla importante:**
- Si el error_log está dentro de un bloque `try-catch` o maneja un error crítico → **MANTENER**
- Si el error_log es para depuración/desarrollo (contiene "DEBUG", "datos recibidos", emojis) → **ELIMINAR**

### 4. Limpiar Logs de Debug en JavaScript

**Archivos a revisar:**
- `app/assets/js/{modulo}.js`
- `app/views/{modulo}/{modulo}.php` (scripts inline)
- Archivos de tour: `app/assets/js/ayuda/{modulo}-tour.js`

**Patrones a eliminar:**

1. **console.log()**:
   ```javascript
   console.log('Debug:', variable);
   console.log('DEBUG: ...', data);
   ```

2. **console.debug()**:
   ```javascript
   console.debug('Debugging info');
   ```

3. **Excepciones**: Mantener `console.error()` y `console.warn()` que manejen errores reales.

### 5. Limpiar Comentarios Innecesarios

**Eliminar:**
- Código comentado que no se usa
- Comentarios obvios que no aportan valor:
  ```php
  // Asignar variable
  $x = 5;
  ```
- Comentarios de debug:
  ```javascript
  // TODO: revisar esto
  // FIXME: temporal
  ```
- Bloques grandes de código comentado

**MANTENER:**
- Comentarios de documentación (PHPDoc, JSDoc)
- Comentarios que explican lógica compleja
- Comentarios de secciones importantes:
  ```php
  // Validación de permisos de usuario
  // Cálculo de impuestos según tasa vigente
  ```

### 6. Verificar Sintaxis

Después de realizar los cambios:

**Para PHP:**
```bash
php -l app/Controllers/{Modulo}.php
php -l app/Models/{Modulo}Model.php
```

**Para JavaScript:**
- Verificar en el navegador que no haya errores de sintaxis
- Probar que el módulo funcione correctamente

### 7. Confirmar Cambios

Realizar un resumen de:
- ✅ Tour: auto-inicio deshabilitado en `{modulo}-tour.js`
- ✅ Logs eliminados: X líneas de error_log, Y líneas de console.log
- ✅ Comentarios eliminados: Z bloques de código comentado
- ✅ Archivos procesados: lista de archivos modificados
- ✅ Sintaxis verificada: sin errores

## Ejemplo de Uso

**Usuario dice:**
> "Validar y limpiar el módulo de clientes"

**El agente debe:**
1. Buscar y editar `app/assets/js/ayuda/clientes-tour.js` → deshabilitar auto-inicio
2. Buscar y editar `app/Controllers/Clientes.php` → eliminar error_log de debug
3. Buscar y editar `app/Models/ClientesModel.php` → eliminar error_log y print_r
4. Buscar y editar archivos JS relacionados → eliminar console.log
5. Eliminar comentarios innecesarios en todos los archivos
6. Verificar sintaxis con `php -l`
7. Confirmar cambios realizados

## Consideraciones Importantes

1. **No eliminar manejo de errores**: Los error_log dentro de try-catch o que registran errores críticos deben mantenerse
2. **Mantener funcionalidad del tour**: Solo se deshabilita el auto-inicio, el botón de ayuda debe seguir funcionando
3. **Revisar contexto**: Antes de eliminar un comentario, verificar que no sea documentación importante
4. **Backup implícito**: Confiar en que el usuario tiene control de versiones (git)
5. **Archivos múltiples**: Un módulo puede tener múltiples archivos relacionados (Controller, Model, vistas, JS)

## Archivos Típicos por Módulo

Para un módulo llamado "ejemplo":
- `app/Controllers/Ejemplo.php` - Controlador principal
- `app/Models/EjemploModel.php` - Modelo de datos
- `app/views/ejemplo/ejemplo.php` - Vista principal
- `app/assets/js/ejemplo.js` - JavaScript del módulo (si existe)
- `app/assets/js/ayuda/ejemplo-tour.js` - Tour de ayuda
- Helpers relacionados en `helpers/` (si existen)

## Mensajes al Usuario

Después de completar la limpieza, informar al usuario de manera concisa:

```
Módulo {nombre} validado y limpiado:

✅ Tour: auto-inicio deshabilitado
✅ Logs debug eliminados: {cantidad} líneas
✅ Comentarios innecesarios eliminados
✅ Archivos procesados: {lista}
✅ Sintaxis verificada sin errores

El tour ahora solo se inicia manualmente mediante el botón de ayuda.
```
