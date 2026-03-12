# Resumen de Pruebas Unitarias — Todos los Módulos

---

## Módulo de Producción (RF-PROD-001) — 69 pruebas · 174 aserciones

### Entradas
- **Configuración:** productividad 150 kg/h-h, capacidad máxima 50 operarios, salario base $30, coeficientes beta/gamma, umbrales de error y rangos de peso de pacas (25–35 kg).
- **Lotes válidos:** supervisor asignado, volumen estimado 150 kg, fecha de jornada actual.
- **Lotes inválidos:** sin supervisor, volumen 0 o negativo (−100 kg), fecha inválida (2025-13-45), productividad cero, volumen extremo (999 999 kg).
- **Transiciones de estado:** lotes inexistentes (ID 99999), planificados, ya finalizados y en proceso.
- **Registros de producción válidos:** ID de lote/empleado/producto, 100 kg a procesar → 90 kg producidos, tipo CLASIFICACION o EMPAQUE.
- **Registros inválidos:** lote/producto/empleado inexistente (ID 99999), stock insuficiente (9 999 kg vs 5 disponibles), estados no modificables (ENVIADO, PAGADO, CANCELADO).
- **Nómina:** arrays de IDs en estado BORRADOR, IDs inexistentes (99999, 99998), arrays vacíos.
- **Precios válidos:** CLASIFICACION $0.30/kg, EMPAQUE $5/unidad USD. **Inválidos:** campos omitidos, salarios negativos/cero, tipo TIPO_INVALIDO.

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Consulta de configuración exitosa | `status true` + parámetros operativos |
| Actualización con cambios | `status true` |
| Actualización sin cambios | `status false` |
| Lote creado exitosamente | `status true` + id, número `LOTE-AAAAMMDD-NNN`, operarios calculados |
| Lote con datos inválidos | `status false` + mensaje con palabra clave (supervisor / volumen / fecha / capacidad) |
| Inicio de lote planificado | `status true`; inexistente → `status false` |
| Cierre de lote en proceso | `status true`; ya finalizado/inexistente → `status false` |
| Inserción de registro válida | BORRADOR creado, salario calculado, stock descontado, `status true` + id |
| Inserción inválida (lote/producto/empleado/stock) | `status false` + mensaje con palabra clave correspondiente |
| Consultas con filtros | `status true` + `data` (puede ser vacío) |
| Actualizar/eliminar BORRADOR | `status true`; no-BORRADOR → `status false` |
| Solicitud de pago (BORRADOR → ENVIADO) | `status true`; sin registros/IDs inexistentes → `status false` |
| Precio válido | `status true`; inválido → `status false` + mensaje |
| Marcar como pagado (requiere ENVIADO) | si BORRADOR → `status false` |
| Cancelar (solo BORRADOR o ENVIADO) | otro estado → `status false` + mensaje |

### Observaciones
Las 69 pruebas se ejecutaron exitosamente en ~13.5 s con 10 MB de memoria. Se validó el ciclo completo PLANIFICADO → EN_PROCESO → FINALIZADO con cálculo automático de operarios y salarios. El control de stock previene descuentos mayores a las existencias. Las restricciones de modificación por estado protegen la integridad de registros ya enviados, pagados o cancelados. La gestión de nómina y precios por proceso opera con transacciones y rollbacks correctos ante fallos.

---

## Módulo de Productos (RF001) — 29 pruebas · 105 aserciones

### Entradas
- **Inserción:** "Cartón Corrugado" (kg, $0.15 USD, cat. 1), "Aceite Mineral" (litros, $2.50 BS, cat. 2), nombre duplicado "Producto Ya Existente", `lastInsertId` = 0, excepción BD.
- **Actualización:** ID 5 → "Cartón Premium" $0.20 USD; ID 10 → "Aceite Industrial" $3.00 BS; conflicto de nombre (ID 7 + "Nombre Duplicado"); ID inexistente (999999); nombre vacío; excepción BD.
- **Consultas:** dos productos activos ("Cartón", "Plástico"), búsqueda por ID existente/inexistente (999999, 0), conjuntos vacíos, excepciones BD.
- **Eliminación:** IDs existentes (1 - "Cartón Corrugado", 50 - "Plástico Duro"), IDs inexistentes (999999, 888888), verificación de soft-delete.

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Inserción válida | `status true` + mensaje de éxito + ID generado |
| Nombre duplicado | `status false` + "producto ya existe" |
| `lastInsertId` = 0 / excepción | `status false` + mensaje |
| Actualización exitosa | `status true` + confirmación de cambio |
| Nombre en conflicto | `status false` + "nombre ya existe" |
| ID inexistente en actualización | `status false` (0 filas afectadas) |
| Consulta con datos | `status true` + `data` con estructura completa (id, nombre, precio, unidad, existencia, categoría, moneda, estatus) |
| Búsqueda por ID inexistente | `false` |
| Sin registros disponibles | `status true` + `data []` |
| Excepción en consulta | `status false` + `data []` |
| Eliminación de ID existente | `true` (UPDATE estatus → INACTIVO, **no** DELETE físico) |
| Eliminación de ID inexistente | `false` |

> **Soft-delete confirmado:** ninguna query ejecutada durante las eliminaciones contiene la instrucción `DELETE FROM`.

### Observaciones
Las 29 pruebas se ejecutaron correctamente en ~20 s con 10 MB de memoria. Se verificó que la eliminación lógica preserva el historial actualizando el campo estatus a INACTIVO. La validación de unicidad de nombres opera tanto en creación como en actualización. Los mocks de PDO garantizan aislamiento completo de la base de datos real.

---

## Módulo de Pagos (RF05) — 12 pruebas · 47 aserciones

### Entradas
- **Inserción exitosa:** pago por compra (`idpersona` nulo, monto 500.00, REF-TEST-001); pago por venta (`idventa` = 10, monto 1 200.50, REF-TEST-002).
- **Inserción fallida:** campo `monto` omitido intencionalmente; monto negativo (−100.00); `lastInsertId` retorna 0; persona inexistente (ID 9999).
- **Consultas:** listado completo con 2 registros y con 0 registros; búsqueda por ID existente (5) e inexistente (99999); excepciones de conexión a BD simuladas (PDOException "Connection lost", "Timeout").

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Inserción con datos válidos | `status true` + `data.idpago` > 0 (42 o 55 en mocks) |
| Monto omitido / monto negativo | `status false` + mensaje descriptivo |
| `lastInsertId` = 0 | `status false` |
| Persona inexistente (ID 9999) | `idpersona` se nulifica → inserción exitosa → `status true` + id |
| Listado con registros | `status true` + `data` con pagos completos (monto, referencia, fecha formateada, estatus, método de pago, destinatario) |
| Listado vacío | `status true` + `data []` |
| Búsqueda por ID existente | `status true` + datos completos del pago |
| ID inexistente | `status false` + mensaje |
| Excepción de BD (cualquier operación) | `status false` + mensaje descriptivo |

### Observaciones
Las 12 pruebas se ejecutaron correctamente en ~2 s con 10 MB de memoria. El comportamiento de nulificación automática de personas inexistentes evita fallos innecesarios en la inserción. La detección de `lastInsertId` = 0 captura fallos silenciosos de BD. Las excepciones PDO son capturadas y convertidas en respuestas controladas con `status false`, evitando que la aplicación falle abruptamente.

---

## Módulo de Proveedores (RF005) — 30 pruebas · 68 aserciones

### Entradas
- **Creación:** María López (V-12345678, con fecha de nacimiento, dirección, correo, teléfono), Carlos Ramos (V-98765432, sin fecha); duplicados V-11111111 y V-22222222; inserción sin ID válido; excepción BD.
- **Consultas:** todos los proveedores con nivel superusuario y usuario regular; por ID existente (5 - Pedro Jiménez) e inexistentes (99999, 12345678); solo activos; búsquedas por término "Luis", término vacío y "xyzxyzxyz".
- **Actualización:** datos completos (ID 10), solo nombre (ID 20), identificación duplicada (IDs 1 y 5 con V-99999999 y J-12345678-0 respectivamente), excepción BD.
- **Eliminación/desactivación:** ID existente (5), IDs inexistentes (99999, 12345678), excepción BD.
- **Reactivación:** ID válido inactivo (3), IDs inexistentes (88888, 77777), excepción BD.

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Inserción válida | `status true` + mensaje de creación exitosa |
| Identificación duplicada / fallo de ID | `status false` + mensaje descriptivo |
| Consulta de todos los proveedores | siempre `{status: true, data: [...]}` |
| Consulta por ID existente | datos completos (idproveedor, nombre, apellido, identificacion, teléfono, correo, estatus) |
| Consulta por ID inexistente | `false` |
| Proveedores activos / búsquedas | `status true` + `data` (vacío si no hay coincidencias) |
| Actualización exitosa | `status true` + mensaje de actualización |
| Identificación duplicada en actualización | `status false` + mensaje de duplicado |
| Eliminación de ID existente | `true` |
| Eliminación de ID inexistente / ya inactivo | `false` |
| Reactivación exitosa | `status true` |
| Reactivación de ID inexistente / excepción | `status false` |

### Observaciones
Las 30 pruebas se ejecutaron correctamente en ~6 s con 10 MB de memoria. La validación de identificaciones duplicadas es robusta tanto en creación como en actualización. El módulo cubre el ciclo completo CRUD más la operación de reactivación. La ejecución en procesos separados evita interferencias entre casos de prueba.

---

## Módulo de Movimientos de Inventario (RF06) — 19 pruebas · 95 aserciones

### Entradas
- **Inserción inválida:** sin ID de producto, ID nulo o string vacío, sin tipo de movimiento, tipo nulo, ambas cantidades en cero, cantidades negativas, entrada y salida simultáneas (contradicción lógica), producto no encontrado en BD (fetch retorna `false`).
- **Actualización (siempre rechazada):** ID válido con datos completos, ID grande (999999) con datos vacíos, ID máximo con datos complejos.
- **Eliminación (siempre rechazada):** ID válido, ID presumiblemente inexistente, ID máximo.
- **IDs inválidos:** valor 0 en `selectMovimientoById` y en `anularMovimientoById`.
- **Pruebas de contrato:** comparación estructural de respuestas entre métodos deshabilitados y validaciones fallidas.

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Inserción con datos inválidos | `status false` + mensaje con palabra clave específica (producto / tipo de movimiento / cantidad / "entrada y salida al mismo tiempo") + `data null` |
| Producto no encontrado (stock no verificable) | `status false` + mensaje con "stock" + `data null` |
| Cualquier intento de actualización | `status false` + mensaje con "no está permitida" + `data null` |
| Cualquier intento de eliminación | `status false` + mensaje con "no está permitida" + `data null` |
| ID = 0 en consulta o anulación | `status false` + mensaje con "inválido" + `data null` (antes de consultar BD) |
| Toda respuesta de error | estructura uniforme `{status: bool, message: string, data: null}` |

### Observaciones
Las 19 pruebas se ejecutaron correctamente en ~4 s con 10 MB de memoria. El módulo es **inmutable por diseño**: los movimientos registrados no pueden modificarse ni eliminarse para garantizar la trazabilidad de auditoría de inventario. Las validaciones son exhaustivas y ocurren antes de cualquier consulta a la BD, incluida la regla de exclusividad (una operación es entrada *o* salida, nunca ambas ni ninguna). La consistencia estructural de todas las respuestas de error fue verificada mediante pruebas de contrato.

---

## Módulo de Roles (RF05) — 41 pruebas · 113 aserciones

### Entradas
- **Consultas:** ID existente (5 - OPERADOR), IDs inexistentes (999999, 888888, 2147483647), listados con/sin datos, selects. Super usuario: count = 1 (es super), count = 0 (normal).
- **Creación:** nombres únicos GERENTE, AUDITOR, SUPERVISOR (estados ACTIVO/INACTIVO, descripciones hasta 255 chars); duplicados ADMIN y SUPER_USUARIO; fallos de BD con constraint violation.
- **Actualización:** cambios válidos de nombre/estatus; conflicto con nombre ADMIN; sin cambios reales; reactivación de rol inactivo (ID 10 - VIEJO); inexistente (ID 99999); ya activo.
- **Eliminación:** roles sin usuarios (IDs 7, 12 → count = 0, rowCount = 1); roles en uso (1 o 10 usuarios); IDs inexistentes (count = 0, rowCount = 0); excepciones BD.
- **Módulo integrado:** IDs inválidos (0, −5); asignación sin permisos específicos (módulos 1 y 5); asignación con permisos (módulo 1 + permisos 1,2; módulo 4 + permiso 3); fallo por FK constraint; consultas de asignaciones vacías y con datos.

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Consulta por ID existente | datos completos: idrol, nombre, descripción, estatus, fechas |
| ID inexistente | `false` |
| Listados | `status true` + `data` (puede ser vacío) |
| Super usuario (count = 1) | `true`; count = 0 → `false` |
| Creación exitosa | `status true` + mensaje + `rol_id = 42` |
| Nombre duplicado | `status false` + "Ya existe un rol activo con ese nombre." |
| Fallo BD en creación | `status false` + mensaje con "Error" |
| Actualización exitosa | `status true` |
| Conflicto de nombre | `status false` + "Ya existe otro rol activo con ese nombre." |
| Sin cambios reales | `status true` + mensaje con "idénticos" |
| Reactivación exitosa | `status true` + mensaje con "reactivado" |
| Rol no existe | `status false` + "El rol no existe." |
| Rol ya activo | `status false` + "El rol ya se encuentra activo." |
| Eliminación exitosa | `status true` + mensaje con "desactivado" |
| Rol en uso | `status false` + mensaje con "siendo usado" |
| ID inexistente en eliminación | `status false` + mensaje con "No se encontró" |
| Integrado — ID inválido | `status false` + "ID de rol no válido." |
| Integrado — sin permisos específicos | `status true` + `modulos_asignados = 0`, `permisos_especificos_asignados = 0` |
| Integrado — con permisos | `status true` + `modulos_asignados = 2`, `permisos_especificos_asignados = 3` |
| Integrado — fallo FK | `status false` + mensaje con "Error" |
| Consultas de asignaciones | `status true` + `data` |

### Observaciones
Las 41 pruebas se ejecutaron correctamente en ~7 s con 10 MB de memoria, distribuidas en 5 clases: `consultarRol` (9), `crearRol` (5), `editarRol` (8), `eliminarRol` (6), `rolesIntegrado` (13). La gestión transaccional de asignaciones de módulos y permisos fue validada incluyendo rollback ante fallos de FK. La prevención de eliminación de roles en uso activo protege la integridad referencial. Los mensajes de error son específicos y distinguibles por módulo de validación.

---

## Módulo de Romana (RF07) — 7 pruebas · 31 aserciones

### Entradas
- **Consulta exitosa (varios registros):** `idromana` 1/2, pesos 250.50/310.00 kg, estatus ACTIVO, fechas 2026-03-01/02.
- **Consulta exitosa (un registro):** `idromana` 5, peso 99.99 kg, estatus INACTIVO, fecha 2026-03-05.
- **Consulta exitosa (vacía):** `fetchAll` retorna `[]`.
- **Validación de estructura:** registro con `idromana` 3, peso 175.25 kg, fecha 2026-03-03, estatus ACTIVO.
- **Fallo en `prepare`:** `PDOException('Simulated DB error')` / `PDOException('Connection lost')`.
- **Fallo en `execute`:** `PDOException('Execute failed')`.

### Salidas Esperadas
| Escenario | Resultado esperado |
|---|---|
| Varios registros | `status true` + `data` con 2 elementos |
| Un registro | `status true` + `data` con 1 elemento |
| Lista vacía | `status true` + `data []` |
| Estructura de campos | `data[0]` tiene `idromana`, `peso`, `fecha`, `estatus`, `fecha_creacion` |
| Fallo en `prepare` | `status false` + `data []` + `message` presente |
| Fallo en `execute` | `status false` + `data []` + `message` contiene 'Error' |
| Mensaje exacto | `message === 'Error al obtener los registros'` |

### Observaciones
Las 7 pruebas se ejecutaron correctamente en ~1.9 s con 10 MB de memoria. El DataProvider cubre tres variantes del resultado: múltiples registros, un registro y lista vacía. El mensaje de error está fijado exactamente como `'Error al obtener los registros'`, garantizando consistencia en la respuesta del modelo ante fallos de base de datos.

---

## Resumen consolidado

| Módulo | Pruebas | Aserciones | Tiempo | Estado |
|---|---|---|---|---|
| Producción (RF-PROD-001) | 69 | 174 | ~13.5 s | ✅ |
| Roles (RF05) | 41 | 113 | ~7 s | ✅ |
| Productos (RF001) | 29 | 105 | ~20 s | ✅ |
| Proveedores (RF005) | 30 | 68 | ~6 s | ✅ |
| Movimientos (RF06) | 19 | 95 | ~4 s | ✅ |
| Pagos (RF05) | 12 | 47 | ~2 s | ✅ |
| Romana (RF07) | 7 | 31 | ~2 s | ✅ |
| **Total** | **207** | **633** | | ✅ **Todas pasaron** |

> **Nota:** El warning recurrente de "No code coverage driver available" en todos los módulos se debe a la ausencia de Xdebug en el entorno y no afecta la validez de las pruebas.

---

**Documentación generada el:** 6 de marzo de 2026
