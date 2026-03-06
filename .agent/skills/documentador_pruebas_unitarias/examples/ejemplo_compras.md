# Ejemplo: Documentación del Módulo de Compras

Este es un ejemplo real de cómo debería verse la documentación generada.

---

## Cuadro Nº 4: Módulo de Gestión de Compras (RF03)

### Objetivos de la prueba

Validar que una compra sólo se registre cuando incluye un proveedor existente, al menos un producto válido con cantidad positiva y datos consistentes. El sistema debe rechazar compras con proveedores o productos inexistentes, cantidades inválidas o sin detalles, incluso cuando los datos de prueba se generan dinámicamente.

### Técnicas

Pruebas de caja blanca con enfoque en integración, transacciones y aislamiento: se crea un producto de prueba al inicio y se obtiene un proveedor existente para garantizar independencia. Se evalúa el método insertarCompra() en escenarios válidos e inválidos, verificando la validación previa a la inserción y el manejo de errores dentro de una transacción.

### Código Involucrado

```php
[Aquí iría el código completo de los archivos:
- crearCompraUnitTest.php
- editarCompraUnitTest.php
- eliminarCompraUnitTest.php
- consultarComprasUnitTest.php
Todos concatenados]
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Asegurar que solo se creen compras válidas, con proveedores y productos reales, cantidades positivas y al menos un ítem en el detalle.

**DESCRIPCIÓN:** Se prueban escenarios de éxito (compra completa con datos generados en tiempo de prueba) y falla (proveedor inexistente, sin detalles, cantidad cero, producto inexistente).

**ENTRADAS:**
- (Datos válidos con proveedor y producto de prueba, cantidad) → compra exitosa
- (Proveedor = aleatorio, producto de prueba) → proveedor inexistente
- (Detalles = []) → sin ítems
- (Cantidad = 0) → cantidad inválida
- (Producto = aleatorio, proveedor de prueba) → producto inexistente

**SALIDAS ESPERADAS:**
- Compra válida: `['status' => true, 'id' => N]` con N > 0.
- En todos los demás casos: `['status' => false]` con mensaje descriptivo.

### Resultado

```
PHPUnit 11.5.3 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.17
Configuration: C:\xampp\htdocs\project\phpunit.xml

................................                                  32 / 32 (100%)

Time: 00:01.234, Memory: 10.00 MB

OK (32 tests, 64 assertions)
```

### Observaciones

Se observa la ejecución de 32 pruebas unitarias para las funciones de 'Gestionar Compra', de las cuales todas resultaron exitosas, verificando escenarios clave como la creación exitosa, el manejo de proveedores y productos inexistentes, la falta de detalles en la compra y las cantidades iguales a cero.

---

## Cómo se generó este ejemplo

1. Se listaron los archivos en `tests/unitTest/Compra/`
2. Se leyó el contenido completo de cada archivo
3. Se ejecutó: `php vendor/bin/phpunit tests/unitTest/Compra/`
4. Se analizaron los resultados y se generó la documentación
