<?php headerAdmin($data); ?>
<main class="flex-1 p-6">
    <h1 class="text-3xl font-bold text-gray-900"><?= $data['page_name'] ?></h1>

    <form id="formNuevaCompra" class="bg-white p-6 mt-6 rounded-2xl shadow-md">
        <!-- Sección Datos Generales de la Compra -->
        <fieldset class="border p-4 rounded mb-6">
            <legend class="text-xl font-semibold px-2">Datos Generales</legend>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="fecha_compra" class="block text-gray-700 font-medium mb-1">Fecha Compra</label>
                    <input type="date" id="fecha_compra" name="fecha_compra" class="w-full border rounded-lg px-3 py-2" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label for="idmoneda_general_compra" class="block text-gray-700 font-medium mb-1">Moneda General</label>
                    <select id="idmoneda_general_compra" name="idmoneda_general_compra" class="w-full border rounded-lg px-3 py-2" required>
                        <option value="">Seleccione Moneda</option>
                        <?php foreach ($data['monedas'] as $moneda): ?>
                            <option value="<?= $moneda['idmoneda'] ?>" data-simbolo="<?= $moneda['simbolo'] ?>"><?= $moneda['nombre_moneda'] ?> (<?= $moneda['simbolo'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- Sección Proveedor -->
        <fieldset class="border p-4 rounded mb-6">
            <legend class="text-xl font-semibold px-2">Proveedor</legend>
            <div class="mb-4">
                <label for="buscar_proveedor" class="block text-gray-700 font-medium mb-1">Buscar Proveedor (Nombre, Apellido o Identificación)</label>
                <input type="text" id="buscar_proveedor" class="w-full border rounded-lg px-3 py-2" placeholder="Escriba para buscar...">
                <input type="hidden" id="idproveedor_seleccionado" name="idproveedor_seleccionado">
                <div id="proveedor_seleccionado_info" class="mt-2 p-2 border rounded bg-gray-50 hidden"></div>
            </div>
            <button type="button" id="btnAbrirModalNuevoProveedor" class="bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold">Registrar Nuevo Proveedor</button>
        </fieldset>

        <!-- Sección Productos/Detalle de Compra -->
        <fieldset class="border p-4 rounded mb-6">
            <legend class="text-xl font-semibold px-2">Detalle de la Compra</legend>
            <div class="mb-4">
                <label for="select_producto_agregar" class="block text-gray-700 font-medium mb-1">Agregar Producto</label>
                <select id="select_producto_agregar" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Seleccione un producto...</option>
                    <?php foreach ($data['productos'] as $producto): ?>
                        <option value="<?= $producto['idproducto'] ?>"
                                data-idcategoria="<?= $producto['idcategoria'] ?>"
                                data-nombre="<?= htmlspecialchars($producto['nombre_producto']) ?>"
                                data-precio="<?= $producto['precio_referencia_compra'] ?? '0.00' ?>"
                                data-idmoneda="<?= $producto['idmoneda_referencia'] ?? '' ?>"
                                data-moneda-simbolo="<?= $producto['moneda_simbolo'] ?? '' ?>">
                            <?= htmlspecialchars($producto['nombre_producto']) ?> (<?= htmlspecialchars($producto['nombre_categoria']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="btnAgregarProductoDetalle" class="mt-2 bg-green-500 text-white px-4 py-2 rounded-lg font-semibold">Agregar al Detalle</button>
            </div>

            <table id="tablaDetalleCompra" class="w-full text-left border-collapse mt-4">
                <thead>
                    <tr class="text-gray-600 text-sm border-b">
                        <th class="py-2">Producto</th>
                        <th class="py-2">Info Específica</th> <!-- Peso/Cantidad -->
                        <th class="py-2">Precio Unit.</th>
                        <th class="py-2">Subtotal</th>
                        <th class="py-2">Acción</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaDetalleCompra" class="text-gray-900">
                    <!-- Filas de productos agregados dinámicamente -->
                </tbody>
            </table>
        </fieldset>

        <!-- Sección Totales -->
        <fieldset class="border p-4 rounded mb-6">
            <legend class="text-xl font-semibold px-2">Totales</legend>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="subtotal_general_display" class="block text-gray-700 font-medium mb-1">Subtotal General</label>
                    <input type="text" id="subtotal_general_display" class="w-full border rounded-lg px-3 py-2 bg-gray-100" readonly>
                    <input type="hidden" id="subtotal_general_input" name="subtotal_general_input">
                </div>
                <div>
                    <label for="descuento_porcentaje_input" class="block text-gray-700 font-medium mb-1">Descuento (%)</label>
                    <input type="number" id="descuento_porcentaje_input" name="descuento_porcentaje_input" class="w-full border rounded-lg px-3 py-2" value="0" min="0" max="100" step="0.01">
                </div>
                <div>
                    <label for="monto_descuento_display" class="block text-gray-700 font-medium mb-1">Monto Descuento</label>
                    <input type="text" id="monto_descuento_display" class="w-full border rounded-lg px-3 py-2 bg-gray-100" readonly>
                    <input type="hidden" id="monto_descuento_input" name="monto_descuento_input">
                </div>
            </div>
            <div class="mt-4">
                <label for="total_general_display" class="block text-xl font-semibold text-gray-700 mb-1">TOTAL GENERAL</label>
                <input type="text" id="total_general_display" class="w-full border rounded-lg px-3 py-3 text-xl font-bold bg-gray-100" readonly>
                <input type="hidden" id="total_general_input" name="total_general_input">
            </div>
             <div class="mt-4">
                <label for="observaciones_compra" class="block text-gray-700 font-medium mb-1">Observaciones</label>
                <textarea id="observaciones_compra" name="observaciones_compra" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
        </fieldset>

        <div class="flex justify-end space-x-4 mt-6">
            <button type="button" id="btnCancelarCompra" class="px-6 py-3 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition text-lg">Cancelar</button>
            <button type="submit" id="btnGuardarCompra" class="px-6 py-3 bg-green-600 text-white rounded hover:bg-green-700 transition text-lg">Guardar Compra</button>
        </div>
    </form>
</main>

<!-- Modal para Registrar Nuevo Proveedor (similar al que tenías pero adaptado) -->
<div id="modalNuevoProveedor" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl">
    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-800">Registrar Nuevo Proveedor</h3>
      <button id="btnCerrarModalNuevoProveedor" class="text-gray-600 hover:text-gray-800">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="formNuevoProveedor" class="px-6 py-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="identificacion_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Identificación*</label>
          <input type="text" name="identificacion_proveedor_nuevo" id="identificacion_proveedor_nuevo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3" required>
        </div>
        <div>
          <label for="nombre_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Nombre(s)*</label>
          <input type="text" name="nombre_proveedor_nuevo" id="nombre_proveedor_nuevo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3" required>
        </div>
        <div>
          <label for="apellido_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Apellido(s)</label>
          <input type="text" name="apellido_proveedor_nuevo" id="apellido_proveedor_nuevo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>
        <div>
          <label for="telefono_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Teléfono</label>
          <input type="text" name="telefono_proveedor_nuevo" id="telefono_proveedor_nuevo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>
        <div class="md:col-span-2">
          <label for="correo_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
          <input type="email" name="correo_proveedor_nuevo" id="correo_proveedor_nuevo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>
        <div class="md:col-span-2">
          <label for="direccion_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Dirección</label>
          <textarea name="direccion_proveedor_nuevo" id="direccion_proveedor_nuevo" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3"></textarea>
        </div>
      </div>
      <div class="flex justify-end space-x-3 mt-4 pt-4 border-t">
        <button type="button" id="btnCancelarModalNuevoProveedor" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Guardar Proveedor</button>
      </div>
    </form>
  </div>
</div>

<?php footerAdmin($data); ?>





