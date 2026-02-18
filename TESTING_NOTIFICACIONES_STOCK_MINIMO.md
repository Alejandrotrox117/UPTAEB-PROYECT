# 🧪 PRUEBAS Y VALIDACIÓN - Notificaciones Stock Mínimo

## Antes de Iniciar

Asegúrate de que:
1. ✅ Redis está corriendo (`redis-cli ping` debe responder PONG)
2. ✅ Database de seguridad tiene tabla `notificaciones_config`
3. ✅ Base de datos general tiene tabla `producto`
4. ✅ Tienes usuarios con roles diferentes

---

## Test 1: Verificar Roles Existentes

```sql
-- Ejecutar en base de datos de SEGURIDAD
SELECT idrol, nombre, estatus FROM rol WHERE estatus = 'ACTIVO';
```

**Resultado esperado:**
```
idrol | nombre        | estatus
------|---------------|--------
1     | SuperAdmin    | ACTIVO
2     | Admin         | ACTIVO
3     | Vendedor      | ACTIVO
4     | Operario      | ACTIVO
```

---

## Test 2: Verificar Permisos en Módulo 'productos'

```sql
-- Ejecutar en base de datos de SEGURIDAD
SELECT 
    rmp.idrol,
    r.nombre as rol_nombre,
    p.accion,
    rmp.activo
FROM rol_modulo_permisos rmp
INNER JOIN rol r ON rmp.idrol = r.idrol
INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
WHERE m.url = 'productos'
ORDER BY rmp.idrol, p.accion;
```

**Resultado esperado:**
```
idrol | rol_nombre | accion | activo
------|-----------|--------|-------
1     | SuperAdmin | ver    | 1
1     | SuperAdmin | crear  | 1
1     | SuperAdmin | editar | 1
2     | Admin      | ver    | 1
2     | Admin      | crear  | 1
2     | Admin      | editar | 1
3     | Vendedor   | ver    | 1
```

---

## Test 3: Verificar Configuración de Notificaciones

```sql
-- Ejecutar en base de datos de SEGURIDAD
SELECT 
    idrol,
    modulo,
    tipo_notificacion,
    habilitada
FROM notificaciones_config
WHERE modulo = 'productos'
ORDER BY idrol, tipo_notificacion;
```

**Resultado esperado:**
```
idrol | modulo    | tipo_notificacion | habilitada
------|-----------|-------------------|----------
1     | productos | STOCK_MINIMO      | 1
2     | productos | STOCK_MINIMO      | 1
3     | productos | STOCK_MINIMO      | 1
4     | productos | STOCK_MINIMO      | 0
```

---

## Test 4: Crear Producto de Prueba

```sql
-- Ejecutar en base de datos GENERAL
INSERT INTO producto (
    nombre, 
    descripcion, 
    existencia, 
    stock_minimo, 
    unidad_medida, 
    precio, 
    moneda, 
    estatus, 
    fecha_creacion
) VALUES (
    'ARROZ_TEST',
    'Producto de prueba para notificaciones',
    3,
    5,
    'kg',
    100.00,
    'VEF',
    'ACTIVO',
    NOW()
);

-- Obtener el ID
SELECT idproducto FROM producto WHERE nombre = 'ARROZ_TEST';
```

**Anota el ID del producto para los siguiente tests**

---

## Test 5: Inicializar Configuración de Notificaciones

Si no existe configuración, inicializarla:

```sql
-- Ejecutar en base de datos de SEGURIDAD (si no existen registros)

-- Asegurar que exista rol 4 (Operario) con notificación deshabilitada
INSERT INTO notificaciones_config (idrol, modulo, tipo_notificacion, habilitada)
VALUES 
    (1, 'productos', 'STOCK_MINIMO', 1),
    (2, 'productos', 'STOCK_MINIMO', 1),
    (3, 'productos', 'STOCK_MINIMO', 1),
    (4, 'productos', 'STOCK_MINIMO', 0)
ON DUPLICATE KEY UPDATE habilitada = VALUES(habilitada);
```

---

## Test 6: Monitorear Logs en Tiempo Real

```bash
# En terminal, monitorear el archivo de errores
tail -f /opt/lampp/logs/php_error_log | grep "STOCK MINIMO"

# O si usas un archivo específico
tail -f /opt/lampp/logs/error.log | grep "STOCK MINIMO"
```

**Deberías ver algo como:**
```
🔔 [STOCK MINIMO] Procesando producto: ARROZ_TEST
1️⃣ Obteniendo roles con permiso 'ver' en módulo 'productos'
✅ Roles con permiso: 1, 2, 3
2️⃣ Filtrando roles por configuración de notificaciones
✅ Roles con notificación habilitada: 1, 2, 3
3️⃣ Enviando notificación a través de enviarPorRoles()
✅ [STOCK MINIMO] Notificación enviada exitosamente a roles: 1, 2, 3
```

---

## Test 7: Prueba de Actualización de Producto

**Vía PHP (en controlador o terminal):**

```php
<?php
require_once 'app/Core/Conexion.php';
require_once 'helpers/NotificacionHelper.php';

use App\Core\Conexion;
use App\Helpers\NotificacionHelper;

$conn = new Conexion();
$conn->connect();
$db = $conn->get_conectGeneral();

// Obtener el producto de prueba
$sql = "SELECT idproducto, nombre, existencia, stock_minimo 
        FROM producto 
        WHERE nombre = 'ARROZ_TEST'";
$stmt = $db->prepare($sql);
$stmt->execute();
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

$conn->disconnect();

if ($producto) {
    // Disparar notificación
    $notificador = new NotificacionHelper();
    $resultado = $notificador->enviarNotificacionStockMinimo($producto);
    
    echo "Resultado: " . ($resultado ? "OK" : "FAILED");
} else {
    echo "Producto no encontrado";
}
?>
```

---

## Test 8: Verificar Notificaciones en BD

```sql
-- Ejecutar en base de datos de SEGURIDAD
SELECT 
    idnotificacion,
    tipo,
    titulo,
    idrol_destino,
    fecha_creacion,
    leida
FROM notificaciones
WHERE tipo = 'STOCK_MINIMO'
ORDER BY fecha_creacion DESC
LIMIT 10;
```

**Resultado esperado después de Test 7:**
```
idnotificacion | tipo          | titulo                      | idrol_destino | fecha_creacion      | leida
---------------|---------------|----------------------------|---------------|---------------------|------
1234           | STOCK_MINIMO  | Stock Mínimo - ARROZ_TEST   | 1             | 2026-02-17 10:30:45 | 0
1235           | STOCK_MINIMO  | Stock Mínimo - ARROZ_TEST   | 2             | 2026-02-17 10:30:45 | 0
1236           | STOCK_MINIMO  | Stock Mínimo - ARROZ_TEST   | 3             | 2026-02-17 10:30:45 | 0
```

⚠️ **NOTA:** Rol 4 (Operario) NO debe tener notificación registrada

---

## Test 9: Prueba con Reducción de Stock (VentasModel)

**Simular una venta que reduce stock:**

```sql
-- Ejecutar en base de datos GENERAL

-- Ver stock actual
SELECT idproducto, nombre, existencia, stock_minimo 
FROM producto WHERE nombre = 'ARROZ_TEST';

-- Simular venta reduciendo stock a 3 (igual al mínimo)
UPDATE producto 
SET existencia = 3 
WHERE nombre = 'ARROZ_TEST';
```

**Monitorear logs durante la actualización**

---

## Test 10: Verificar WebSocket (Redis)

Si tienes cliente Redis:

```bash
# Terminal 1: Monitorear canal de notificaciones
redis-cli SUBSCRIBE notificaciones

# Terminal 2: Ejecutar test de notificación
php test_notificacion.php
```

**Deberías ver el mensaje en Terminal 1:**
```
Reading messages... (press Ctrl-C to quit)
1) "subscribe"
2) "notificaciones"
3) (integer) 1
1) "message"
2) "notificaciones"
3) "{\"idnotificacion\":1234,\"tipo\":\"STOCK_MINIMO\",\"roles_destino\":[1,2,3],...}"
```

---

## Casos de Prueba - Matriz Completa

| Rol | Permiso | Notif Habilitada | Debe Recibir | Código |
|-----|---------|------------------|--------------|--------|
| 1 - SuperAdmin | ✅ | ✅ | ✅ | T101 |
| 2 - Admin | ✅ | ✅ | ✅ | T102 |
| 3 - Vendedor | ✅ | ✅ | ✅ | T103 |
| 4 - Operario | ✅ | ❌ | ❌ | T104 |
| 5 - Gerente* | ❌ | ✅ | ❌ | T105 |
| 6 - Auditor* | ✅ | No existe | ✅ | T106 |

\* Crear roles de prueba si es necesario

---

## Checklist de Validación

- [ ] Test 1: Roles activos verificados
- [ ] Test 2: Permisos en módulo verificados
- [ ] Test 3: Configuración de notificaciones verificada
- [ ] Test 4: Producto de prueba creado
- [ ] Test 5: Configuración inicializada correctamente
- [ ] Test 6: Logs se ven correctamente
- [ ] Test 7: Notificación se envía sin errores
- [ ] Test 8: Notificaciones guardadas en BD (solo 3 roles)
- [ ] Test 9: Reducción de stock dispara notificación
- [ ] Test 10: WebSocket recibe mensaje
- [ ] Matriz de casos completa

---

## Rollback (si algo falla)

```bash
# Restaurar versión anterior
git checkout app/helpers/NotificacionHelper.php

# O revertir cambios específicos
git diff
```

---

## Logs a Buscar

### ✅ Logs Esperados (Éxito)
```
🔔 [STOCK MINIMO] Procesando producto: ARROZ_TEST
✅ Roles con permiso: 1, 2, 3
✅ Roles con notificación habilitada: 1, 2, 3
✅ [STOCK MINIMO] Notificación enviada exitosamente
```

### ❌ Logs de Error
```
❌ [STOCK MINIMO] No hay roles con permiso ver en productos
❌ [STOCK MINIMO] Producto sin ID
❌ [STOCK MINIMO] Error al enviar notificación
Error obtenerRolesConPermiso: ...
```

