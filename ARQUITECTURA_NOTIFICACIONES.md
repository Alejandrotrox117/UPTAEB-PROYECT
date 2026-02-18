# Arquitectura del Sistema de Notificaciones

## 📋 Resumen General

El sistema de notificaciones tiene **3 capas principales**:

```
┌─────────────────────────────────────────────────────────────┐
│  GENERACIÓN (Modelos: Ventas, Productos, etc.)              │
│  └─ Detectan cambios y llaman NotificacionHelper             │
└──────────────────┬──────────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────────┐
│  PROCESAMIENTO (NotificacionHelper)                          │
│  ├─ Obtiene roles con permiso en módulo                      │
│  ├─ Filtra por configuración (notificaciones_config)         │
│  ├─ Guarda en BD (tabla notificaciones)                      │
│  └─ Envía por WebSocket (Redis)                             │
└──────────────────┬──────────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────────┐
│  RECUPERACIÓN (Controller + Modelo)                          │
│  ├─ getNotificaciones() obtiene del rol del usuario          │
│  ├─ Filtra por BD (notificaciones + rol/usuario)            │
│  └─ Retorna al frontend                                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Archivos Modificados

### 1. **helpers/NotificacionHelper.php** (NÚCLEO)
**Responsabilidad**: Generar, filtrar y enviar notificaciones

**Métodos principales**:

```php
// Enviar para un módulo específico
enviarPorModulo($modulo, $tipo, $data, $prioridad)
  ├─ obtenerRolesConPermiso('productos')
  │  └─ SELECT roles que tienen permisos EN el módulo
  ├─ filtrarRolesPorConfigNotificacion($roles, 'productos', 'STOCK_MINIMO')
  │  └─ SELECT roles donde habilitada=0 para QUITAR
  ├─ guardarNotificacionCompletaBD() [Guarda en BD]
  └─ redis->publish('notificaciones', ...) [Envía WebSocket]

// Enviar para notificación de stock mínimo
enviarNotificacionStockMinimo($producto)
  ├─ obtenerRolesConPermiso('productos') [Sin parámetro acción]
  ├─ filtrarRolesPorConfigNotificacion(..., 'STOCK_MINIMO')
  └─ guardarNotificacionCompletaBD()

// Filtrar según configuración del rol
filtrarRolesPorConfigNotificacion($roles, $modulo, $tipo)
  └─ SELECT roles DESHABILITADOS para ESTE tipo en ESTE módulo
     └─ array_diff para eliminarlos de la lista
```

**Problema que ANTES había**:
- ❌ Buscaba en tabla `rol` en lugar de `roles`
- ❌ Buscaba por campo `url` que NO existía en `modulos`
- ❌ No incluía el `modulo` en el filtro de notificaciones_config
- ❌ Código duplicado en enviarNotificacionStockMinimo

---

### 2. **app/Models/NotificacionesConfigModel.php**
**Responsabilidad**: CRUD de configuración de notificaciones por rol

**Métodos principales**:

```php
// Obtener configuración de un rol
obtenerConfiguracionRol($rolId)
  └─ SELECT modulo, tipo_notificacion, habilitada
     FROM notificaciones_config
     WHERE idrol = ?
  └─ Retorna estructura: 
     {
       'productos': { 'STOCK_MINIMO': { habilitada: true }, ... },
       'compras': { 'COMPRA_AUTORIZADA_PAGO': { habilitada: false }, ... }
     }

// Guardar configuración de un rol
guardarConfiguracion($rolId, $configuraciones)
  ├─ DELETE FROM notificaciones_config WHERE idrol = ?
  ├─ INSERT INTO con (idrol, modulo, tipo_notificacion, habilitada)
  └─ Retorna status success/error
```

**Cambios realizados**:
- ✅ Agregó campo `modulo` a la lectura/escritura
- ✅ Cambió `STOCK_BAJO` → `STOCK_MINIMO` en catálogo

---

### 3. **app/Models/NotificacionesModel.php**
**Responsabilidad**: Recuperar notificaciones del usuario

```php
obtenerNotificacionesPorUsuario($usuarioId, $rolId)
  └─ SELECT * FROM notificaciones
     WHERE (idusuario_destino = ? OR idrol_destino = ?)
       AND activa = 1
       AND habilitada = 1
     ORDER BY leida ASC, fecha_creacion DESC
```

**Problema que ANTES había**:
- ❌ Buscaba `estatus = 'ACTIVO'` pero usuario usa `'activo'` (minúsculas)
- ❌ Buscaba `FROM rol` en lugar de `FROM roles`

**Cambios realizados**:
- ✅ Cambió a `estatus = 'activo'` para tabla usuario
- ✅ Cambió a `FROM roles` para tabla de roles

---

### 4. **app/Controllers/Notificaciones.php**
**Responsabilidad**: API REST para obtener notificaciones

```php
getNotificaciones()
  ├─ Obtiene usuarioId de sesión
  ├─ Llama obtenerRolPorUsuario($usuarioId)
  │  └─ SELECT u.idrol FROM usuario u JOIN roles r WHERE u.estatus='activo'
  ├─ Llama obtenerNotificacionesPorUsuario($usuarioId, $rolId)
  │  └─ Retorna notificaciones que coinciden su rol O usuario
  └─ Retorna JSON
```

---

## 🔄 Flujo Completo: Ejemplo Práctico

### Escenario: Crear producto con stock mínimo configurado

```
1. GENERACIÓN (ProductosModel.php)
   └─ actualizarStockProducto()
      └─ Detecta: existencia <= stock_minimo
         └─ new NotificacionHelper()
            └─ enviarNotificacionStockMinimo($producto)

2. PROCESAMIENTO (NotificacionHelper.php)
   
   a) OBTENER ROLES CON PERMISO
      └─ SELECT DISTINCT rmp.idrol
         FROM rol_modulo_permisos rmp
         JOIN modulos m ON rmp.idmodulo = m.idmodulo
         WHERE LOWER(m.titulo) = 'productos'
         └─ Resultado: [1, 2, 6]  ← SuperAdmin, Admin, Administrativo
   
   b) FILTRAR POR CONFIGURACIÓN
      └─ SELECT idrol FROM notificaciones_config
         WHERE idrol IN (1,2,6)
         AND modulo = 'productos'
         AND tipo_notificacion = 'STOCK_MINIMO'
         AND habilitada = 0  ← Los que tienen DESHABILITADA
         └─ Resultado: [6]  ← Solo rol 6 la tiene deshabilitada
      └─ array_diff([1,2,6], [6]) = [1,2]  ← Enviar solo a 1 y 2
   
   c) GUARDAR EN BD
      └─ INSERT INTO notificaciones
         (tipo, titulo, mensaje, modulo, idrol_destino, ...)
         VALUES ('STOCK_MINIMO', '...', '...', 13, 1, ...)  ← Para rol 1
      └─ INSERT INTO notificaciones
         (tipo, titulo, mensaje, modulo, idrol_destino, ...)
         VALUES ('STOCK_MINIMO', '...', '...', 13, 2, ...)  ← Para rol 2
   
   d) ENVIAR WEBSOCKET
      └─ redis->publish('notificaciones', JSON)
         └─ Los clientes conectados reciben en tiempo real

3. RECUPERACIÓN (NotificacionesController.php)

   Cuando Usuario con rol 2 (Admin) accede:
   
   a) getNotificaciones()
      └─ obtenerRolPorUsuario(4)  ← Usuario 4 es rol 2 (Admin)
      
   b) obtenerNotificacionesPorUsuario(4, 2)
      └─ SELECT * FROM notificaciones
         WHERE (idusuario_destino = 4 OR idrol_destino = 2)
           AND activa = 1
           AND habilitada = 1
         └─ Resultado: Notificación de stock mínimo ✅ APARECE
   
   Cuando Usuario con rol 6 (Administrativo) accede:
   
   a) getNotificaciones()
      └─ obtenerRolPorUsuario(15)  ← Usuario 15 es rol 6
      
   b) obtenerNotificacionesPorUsuario(15, 6)
      └─ SELECT * FROM notificaciones
         WHERE (idusuario_destino = 15 OR idrol_destino = 6)
           AND activa = 1
           AND habilitada = 1
         └─ Resultado: NADA ❌ NO APARECE (nunca se guardó para rol 6)
```

---

## 📊 Estructura de Tablas Clave

### `notificaciones` (Almacena las notificaciones)
```sql
idnotificacion (PK)
tipo              ← 'STOCK_MINIMO', 'COMPRA_AUTORIZADA_PAGO', etc
titulo            ← "Stock Mínimo - Producto X"
mensaje           ← Descripción detallada
modulo            ← ID del módulo (13=productos)
referencia_id     ← ID del producto relacionado
idusuario_destino ← NULL o ID usuario (para notif personal)
idrol_destino     ← NULL o ID rol (para notif de rol)
leida             ← 0 o 1
habilitada        ← 0 o 1 (¿se debe mostrar?)
activa            ← 0 o 1 (¿sigue vigente?)
fecha_creacion
```

### `notificaciones_config` (Configuración por rol)
```sql
id (PK)
idrol             ← Qué rol
modulo            ← 'productos', 'compras', 'ventas' ← NUEVO
tipo_notificacion ← 'STOCK_MINIMO', 'COMPRA_AUTORIZADA_PAGO', etc
habilitada        ← 0 o 1 (¿quiere recibir esta?)
fecha_creacion
fecha_modificacion
```

### `rol_modulo_permisos` (¿Qué roles pueden ver qué módulo?)
```sql
idrol
idmodulo  ← 13 = productos
idpermiso ← 1=Solo Lectura, 4=Registrar y Editar, etc
```

---

## 🔑 Reglas de Negocio

### ¿Quién recibe una notificación?

Una notificación se envía a un rol SI Y SOLO SI:

1. ✅ El rol tiene **permiso de acceso** al módulo
   - `rol_modulo_permisos` contiene entrada (rol, módulo)

2. ✅ La notificación NO está **deshabilitada** para ese rol
   - `notificaciones_config.habilitada = 1` O no existe registro

3. ✅ La notificación está **habilitada globalmente**
   - `notificaciones.habilitada = 1`

4. ✅ La notificación es **activa**
   - `notificaciones.activa = 1`

**Lógica en pseudocódigo**:
```
Si tipo_notificacion = 'STOCK_MINIMO' generada para módulo 'productos':

roles_candidatos = SELECT roles CON permiso EN 'productos'
roles_bloqueados = SELECT roles CON 'STOCK_MINIMO' deshabilitado EN 'productos'
roles_finales = roles_candidatos - roles_bloqueados

PARA CADA rol EN roles_finales:
  INSERT INTO notificaciones (idrol_destino, ...)
  redis.publish('notificaciones', ...)
```

---

## 🚀 Flujo de Configuración (Módulo NotificacionesConfig)

```
1. Usuario accede a "Configuración Notificaciones"
   └─ Controller: notificacionesconfig_obtenerConfiguracion()
      └─ Model: obtenerConfiguracionRol($rolId)
         └─ SELECT modulo, tipo_notificacion, habilitada
            FROM notificaciones_config WHERE idrol = ?
         └─ Combina con catálogo de tipos
         └─ Retorna JSON estructura

2. Usuario deshabilita "Stock Mínimo" para rol "Admin"
   └─ Frontend envía: {
        "idrol": 2,
        "configuraciones": [
          {"modulo": "productos", "tipo": "STOCK_MINIMO", "habilitada": false},
          ...
        ]
      }
   
   └─ Controller: notificacionesconfig_guardar()
      └─ Model: guardarConfiguracion($rolId, $configuraciones)
         ├─ DELETE FROM notificaciones_config WHERE idrol = 2
         ├─ INSERT INTO (idrol, modulo, tipo, habilitada)
         │  VALUES (2, 'productos', 'STOCK_MINIMO', 0)
         └─ Retorna {"status": true}

3. Ahora cuando se genere notificación STOCK_MINIMO:
   └─ filtrarRolesPorConfigNotificacion() buscará:
      WHERE idrol IN (1,2,6)
        AND modulo = 'productos'
        AND tipo_notificacion = 'STOCK_MINIMO'
        AND habilitada = 0
      └─ Encuentra: [2]  ← Rol 2 está deshabilitado
      └─ Excluye rol 2 de la lista final
      └─ Solo roles [1, 6] reciben la notificación
```

---

## 🐛 Errores Encontrados y Corregidos

| Archivo | Error | Corrección |
|---------|-------|-----------|
| **NotificacionHelper.php** | `FROM rol` | → `FROM roles` |
| **NotificacionHelper.php** | `m.url = ?` | → `LOWER(m.titulo) = LOWER(?)` |
| **NotificacionHelper.php** | Sin campo `modulo` en filter | → Agregó filtro por módulo |
| **NotificacionHelper.php** | Código duplicado + return prematuro | → Eliminado, retorna después de WebSocket |
| **NotificacionesModel.php** | `estatus = 'ACTIVO'` para usuario | → `estatus = 'activo'` (BD usa minúsculas) |
| **NotificacionesModel.php** | `FROM rol` | → `FROM roles` |
| **NotificacionesConfigModel.php** | No guardaba `modulo` | → Agregó `modulo` al INSERT/SELECT |
| **notificaciones_config** (BD) | Faltaba columna `modulo` | → ALTER TABLE ADD COLUMN modulo |
| **notificaciones_config** (datos) | Tipos no consistentes | → Cambió `STOCK_BAJO` → `STOCK_MINIMO` |

---

## 📝 Resumen: Qué hace cada capa

### 🟢 Capa de Generación (Modelos)
- **Detecta**: Cambios en datos (ej: stock bajo)
- **Gatilla**: `new NotificacionHelper()`
- **Llama**: `enviarNotificacionStockMinimo($producto)`

### 🔵 Capa de Procesamiento (NotificacionHelper)
- **Obtiene**: Roles con permiso en el módulo
- **Filtra**: Por configuración del rol
- **Guarda**: En BD (tabla notificaciones)
- **Envía**: Por WebSocket (Redis)

### 🟡 Capa de Configuración (NotificacionesConfig)
- **Permite**: Al usuario activar/desactivar por rol
- **Guarda**: En BD (tabla notificaciones_config)
- **Valida**: Qué notificaciones ve cada rol

### 🟣 Capa de Recuperación (Controllers)
- **Obtiene**: Rol del usuario logueado
- **Consulta**: Notificaciones para su rol
- **Retorna**: JSON al frontend

---

## 🎯 Puntos Clave a Recordar

1. **Una notificación se crea UNA VEZ por cada rol que la debe recibir**
   - No es una sola notificación reutilizada
   - Son múltiples filas en `notificaciones` tabla

2. **El filtro ocurre en GENERACIÓN, no en RECUPERACIÓN**
   - No se genera para todos y luego se oculta
   - Se genera solo para quien debe recibirla

3. **Dos niveles de control**:
   - **Permisos** (rol_modulo_permisos): ¿El rol PUEDE ver el módulo?
   - **Configuración** (notificaciones_config): ¿El rol QUIERE esta notificación?

4. **El módulo es obligatorio ahora**
   - Antes: `notificaciones_config` guardaba solo tipo
   - Ahora: Guarda (módulo, tipo) para diferenciar

5. **Redis es para tiempo real, BD para persistencia**
   - BD: Recuperar después si se fue la página
   - Redis: Mostrar inmediatamente si está conectado
