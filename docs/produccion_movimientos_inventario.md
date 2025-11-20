# Modificaciones al Sistema de Producción - Generación de Movimientos de Inventario

## Fecha de Implementación
Enero 2025

## Descripción General
Se modificó el sistema de lotes de producción para que al finalizar un lote (`cerrarLoteProduccion()`), se generen automáticamente movimientos de inventario en la tabla `movimientos_existencia` para cada registro de producción asociado al lote.

## Cambios Realizados

### 1. Archivo: `app/models/produccionModel.php`

#### Nuevas Funciones

##### `generarNumeroMovimiento(int $idregistro): string`
- **Tipo**: Privada
- **Propósito**: Genera un número único para cada movimiento de inventario
- **Formato**: `MOV-PROD-YYYYMMDD-HHMMSS-{idregistro}`
- **Ejemplo**: `MOV-PROD-20250125-143052-42`

##### `registrarMovimientosProduccion($db, array $registro): bool`
- **Tipo**: Privada
- **Propósito**: Inserta 2 movimientos de inventario por cada registro de producción:
  1. **Movimiento de SALIDA**: Resta del inventario el producto consumido (idproducto_producir)
  2. **Movimiento de ENTRADA**: Suma al inventario el producto terminado (idproducto_terminado)

**Campos que utiliza del registro de producción:**
```php
- idregistro
- idlote
- idproducto_producir     // Producto que se consume
- cantidad_producir       // Cantidad consumida
- idproducto_terminado    // Producto que se produce
- cantidad_producida      // Cantidad producida
```

**Estructura del movimiento de SALIDA:**
```sql
INSERT INTO movimientos_existencia (
    numero_movimiento,      // MOV-PROD-YYYYMMDD-HHMMSS-{id}-S
    idproducto,            // idproducto_producir
    idtipomovimiento,      // 5 (Producción)
    idproduccion,          // idregistro
    cantidad_entrada,      // NULL
    cantidad_salida,       // cantidad_producir
    stock_anterior,        // existencia actual del producto
    stock_resultante,      // stock_anterior - cantidad_producir
    observaciones,         // "Consumo de material para producción - Lote: X, Registro: Y"
    total,                 // stock_resultante
    estatus               // 'activo'
)
```

**Estructura del movimiento de ENTRADA:**
```sql
INSERT INTO movimientos_existencia (
    numero_movimiento,      // MOV-PROD-YYYYMMDD-HHMMSS-{id}-E
    idproducto,            // idproducto_terminado
    idtipomovimiento,      // 5 (Producción)
    idproduccion,          // idregistro
    cantidad_entrada,      // cantidad_producida
    cantidad_salida,       // NULL
    stock_anterior,        // existencia actual del producto
    stock_resultante,      // stock_anterior + cantidad_producida
    observaciones,         // "Entrada de producto terminado - Lote: X, Registro: Y"
    total,                 // stock_resultante
    estatus               // 'activo'
)
```

#### Función Modificada

##### `ejecutarCierreLote(int $idlote)`
- **Cambios**:
  1. Obtiene todos los `registro_produccion` asociados al lote
  2. Para cada registro con cantidades válidas (> 0), llama a `registrarMovimientosProduccion()`
  3. Mantiene la transacción de base de datos para garantizar consistencia
  4. Actualiza el mensaje de éxito para incluir el número de movimientos generados

**Flujo de ejecución:**
```
1. Verificar que el lote existe
2. Verificar que el lote no está finalizado
3. Obtener todos los registros de producción del lote
4. Para cada registro:
   - Generar movimiento de salida (consumo)
   - Generar movimiento de entrada (producción)
5. Actualizar estado del lote a FINALIZADO
6. Commit de la transacción
```

### 2. Migración de Base de Datos

#### Archivo: `migrations/add_tipo_movimiento_produccion.sql`
- **Propósito**: Agregar el tipo de movimiento con id=5 para "Producción"
- **Ejecución**: Este script debe ejecutarse manualmente en la base de datos antes de cerrar un lote

```sql
INSERT INTO tipo_movimiento (idtipomovimiento, nombre, descripcion, estatus, fecha_creacion, fecha_modificacion)
SELECT 5, 'Producción', 'Movimiento por proceso de producción (consumo de materiales y entrada de productos terminados)', 'activo', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM tipo_movimiento WHERE idtipomovimiento = 5);
```

## Requisitos Previos

1. **Base de Datos**: 
   - La tabla `registro_produccion` debe tener los campos: `idproducto_producir`, `cantidad_producir`, `idproducto_terminado`, `cantidad_producida`
   - Ejecutar el script `migrations/add_tipo_movimiento_produccion.sql` para crear el tipo de movimiento id=5

2. **Permisos**: 
   - El usuario de la base de datos debe tener permisos INSERT en `movimientos_existencia`

## Validaciones

- Solo se generan movimientos si las cantidades son mayores a 0
- Cada movimiento se registra dentro de una transacción para garantizar consistencia
- Si falla algún INSERT, se hace ROLLBACK de toda la operación de cierre

## Logs

Los movimientos generados se registran en el log de PHP:
```
[PRODUCCION] Cierre de lote {idlote}: {N} registros encontrados
[PRODUCCION] Movimientos registrados para registro {idregistro}: {numero_movimiento_salida}, {numero_movimiento_entrada}
```

En caso de error:
```
[PRODUCCION] Error al registrar movimientos: {mensaje_error}
```

## Ejemplo de Uso

Cuando se cierra un lote con 3 registros de producción:
- Se generarán 6 movimientos de inventario (2 por cada registro)
- 3 movimientos de salida (consumo de materiales)
- 3 movimientos de entrada (productos terminados)

## Testing

Para probar la funcionalidad:
1. Ejecutar la migración SQL
2. Crear un lote de producción
3. Agregar registros de producción al lote
4. Cerrar el lote mediante la interfaz o API
5. Verificar en la tabla `movimientos_existencia` que se crearon los registros correspondientes

```sql
-- Verificar movimientos generados para un lote
SELECT m.*, p.nombre as producto_nombre
FROM movimientos_existencia m
INNER JOIN producto p ON m.idproducto = p.idproducto
WHERE m.idproduccion IN (
    SELECT idregistro FROM registro_produccion WHERE idlote = ?
)
ORDER BY m.fecha_creacion;
```

## Consideraciones Importantes

1. **Integridad de Datos**: Los movimientos se generan basándose en las existencias actuales de los productos. Asegúrate de que los productos tengan existencias correctas antes de cerrar un lote.

2. **Tipo de Movimiento**: El código usa `idtipomovimiento = 5`. Si en tu base de datos este ID corresponde a otro tipo de movimiento, debes modificar el código o la migración.

3. **Transacciones**: Todo el proceso se ejecuta en una transacción. Si algo falla, no se actualiza el estado del lote ni se crean movimientos.

4. **Unicidad de Números**: Los números de movimiento incluyen timestamp con segundos, lo que reduce significativamente las colisiones. Sin embargo, en procesos muy rápidos podría haber duplicados. Considera agregar un índice único o usar UUID si es necesario.

## Mantenimiento Futuro

- Si se necesita cambiar el formato del número de movimiento, modificar `generarNumeroMovimiento()`
- Si se necesitan campos adicionales en los movimientos, modificar `registrarMovimientosProduccion()`
- Para agregar validaciones adicionales, hacerlo antes del loop en `ejecutarCierreLote()`

## Autor
Sistema de Gestión de Producción - Enero 2025
