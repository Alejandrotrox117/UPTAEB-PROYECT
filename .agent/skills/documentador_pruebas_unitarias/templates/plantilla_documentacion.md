# Plantilla de Documentación de Pruebas Unitarias

## Cuadro Nº [X]: Módulo de [Nombre Completo del Módulo] (RF[XX])

### Objetivos de la prueba

[Describe qué se está validando y qué debe hacer el sistema. Incluye tanto casos válidos como inválidos.]

**Ejemplo:**
Validar que una compra sólo se registre cuando incluye un proveedor existente, al menos un producto válido con cantidad positiva y datos consistentes. El sistema debe rechazar compras con proveedores o productos inexistentes, cantidades inválidas o sin detalles, incluso cuando los datos de prueba se generan dinámicamente.

### Técnicas

[Describe el enfoque de prueba, metodología y aspectos técnicos evaluados.]

**Ejemplo:**
Pruebas de caja blanca con enfoque en integración, transacciones y aislamiento: se crea un producto de prueba al inicio y se obtiene un proveedor existente para garantizar independencia. Se evalúa el método insertarCompra() en escenarios válidos e inválidos, verificando la validación previa a la inserción y el manejo de errores dentro de una transacción.

### Código Involucrado

```php
<?php
// [Aquí va el código completo de todas las pruebas unitarias del módulo]
// Incluir todos los archivos de prueba del módulo concatenados
?>
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** [Objetivo específico y conciso del caso de prueba]

**Ejemplo:** Asegurar que solo se creen compras válidas, con proveedores y productos reales, cantidades positivas y al menos un ítem en el detalle.

**DESCRIPCIÓN:** [Descripción de los escenarios probados]

**Ejemplo:** Se prueban escenarios de éxito (compra completa con datos generados en tiempo de prueba) y falla (proveedor inexistente, sin detalles, cantidad cero, producto inexistente).

**ENTRADAS:**
- (Datos válidos con proveedor y producto de prueba, cantidad) → operación exitosa
- (Proveedor = aleatorio, producto de prueba) → proveedor inexistente
- (Detalles = []) → sin ítems
- (Cantidad = 0) → cantidad inválida
- (Producto = aleatorio, proveedor de prueba) → producto inexistente

**SALIDAS ESPERADAS:**
- Operación válida: `['status' => true, 'id' => N]` con N > 0.
- En todos los demás casos: `['status' => false]` con mensaje descriptivo.

### Resultado

```
[Aquí va la salida completa de la ejecución en terminal]

Ejemplo:
PHPUnit 11.5.3 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.17
Configuration: C:\xampp\htdocs\project\phpunit.xml

................................                                  32 / 32 (100%)

Time: 00:01.234, Memory: 10.00 MB

OK (32 tests, 64 assertions)
```

### Observaciones

[Analiza los resultados y describe hallazgos importantes]

**Ejemplo:**
Se observa la ejecución de 32 pruebas unitarias para las funciones de 'Gestionar Compra', de las cuales todas resultaron exitosas, verificando escenarios clave como la creación exitosa, el manejo de proveedores y productos inexistentes, la falta de detalles en la compra y las cantidades iguales a cero.

---

## Notas para el Agente

1. **Recopilar todos los archivos**: Lista todos los archivos `.php` en `tests/unitTest/<Modulo>/`
2. **Leer y concatenar**: Lee cada archivo y únelos en la sección "Código Involucrado"
3. **Ejecutar pruebas**: Corre PHPUnit específicamente para ese módulo
4. **Analizar resultados**: Cuenta tests, assertions y describe lo que se probó
5. **Mantener formato**: Sigue exactamente esta estructura
