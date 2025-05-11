<?php headerAdmin($data); ?>

<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Gestión de Compras</h2>
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900"><?= $data['page_name'] ?></h1>
    <p class="text-green-500 text-lg">Registro y consulta de compras de materiales</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button id="btnAbrirModalNuevaCompra" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar Nueva Compra
        </button>
      </div>

      <table id="TablaCompras" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2">Nro. Compra</th>
            <th class="py-2">Fecha</th>
            <th class="py-2">Proveedor</th>
            <th class="py-2">Total</th>
            <th class="py-2">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-gray-900">
          <!-- Filas inyectadas por DataTable -->
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal para Registrar Nueva Compra -->
<div id="modalNuevaCompra" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-60 opacity-0 pointer-events-none transition-opacity duration-300 z-50 overflow-y-auto p-4">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full max-w-4xl max-h-full flex flex-col">
    <!-- Encabezado del Modal -->
    <div class="px-6 py-4 border-b flex justify-between items-center flex-shrink-0">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Nueva Compra</h3>
      <button id="btnCerrarModalNuevaCompra" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Contenido del Modal (Formulario) - Se llenará con JS -->
    <div class="p-6 flex-grow overflow-y-auto">
        <form id="formNuevaCompraModal">
            <!-- Sección Datos Generales de la Compra -->
            <fieldset class="border p-3 rounded mb-4">
                <legend class="text-lg font-semibold px-2">Datos Generales</legend>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="fecha_compra_modal" class="block text-sm font-medium text-gray-700 mb-1">Fecha Compra</label>
                        <input type="date" id="fecha_compra_modal" name="fecha_compra" class="form-input w-full" required>
                    </div>
                    <div>
                        <label for="idmoneda_general_compra_modal" class="block text-sm font-medium text-gray-700 mb-1">Moneda General</label>
                        <select id="idmoneda_general_compra_modal" name="idmoneda_general_compra" class="form-select w-full" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <!-- Sección Proveedor -->
            <fieldset class="border p-3 rounded mb-4">
                <legend class="text-lg font-semibold px-2">Proveedor</legend>
                <div class="mb-3">
                    <label for="buscar_proveedor_modal" class="block text-sm font-medium text-gray-700 mb-1">Buscar Proveedor</label>
                    <input type="text" id="buscar_proveedor_modal" class="form-input w-full" placeholder="Nombre, Apellido o Identificación...">
                    <input type="hidden" id="idproveedor_seleccionado_modal" name="idproveedor_seleccionado">
                    <div id="proveedor_seleccionado_info_modal" class="mt-1 p-1 border rounded bg-gray-50 text-sm hidden"></div>
                </div>
                <button type="button" id="btnAbrirModalNuevoProveedorDENTRO" class="btn-secondary btn-sm">Registrar Nuevo Proveedor</button>
            </fieldset>

            <!-- Sección Productos/Detalle de Compra -->
            <fieldset class="border p-3 rounded mb-4">
                <legend class="text-lg font-semibold px-2">Detalle de la Compra</legend>
                <div class="mb-3 flex items-end gap-2">
                    <div class="flex-grow">
                        <label for="select_producto_agregar_modal" class="block text-sm font-medium text-gray-700 mb-1">Agregar Producto</label>
                        <select id="select_producto_agregar_modal" class="form-select w-full">
                            <option value="">Cargando productos...</option>
                        </select>
                    </div>
                    <button type="button" id="btnAgregarProductoDetalleModal" class="btn-primary">Agregar</button>
                </div>
                <div class="overflow-x-auto">
                    <table id="tablaDetalleCompraModal" class="w-full text-left border-collapse mt-2 text-sm">
                        <thead>
                            <tr class="text-gray-600 border-b">
                                <th class="py-1 px-1">Producto</th>
                                <th class="py-1 px-1">Info Específica</th>
                                <th class="py-1 px-1">Precio U.</th>
                                <th class="py-1 px-1">Subtotal</th>
                                <th class="py-1 px-1">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaDetalleCompraModal" class="text-gray-900">
                            <!-- Filas de productos agregados dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </fieldset>

            <!-- Sección Totales -->
            <fieldset class="border p-3 rounded mb-4">
                <legend class="text-lg font-semibold px-2">Totales</legend>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                        <label for="subtotal_general_display_modal" class="block font-medium text-gray-700 mb-1">Subtotal</label>
                        <input type="text" id="subtotal_general_display_modal" class="form-input-plaintext w-full" readonly>
                        <input type="hidden" id="subtotal_general_input_modal" name="subtotal_general_input">
                    </div>
                    <div>
                        <label for="descuento_porcentaje_input_modal" class="block font-medium text-gray-700 mb-1">Descuento (%)</label>
                        <input type="number" id="descuento_porcentaje_input_modal" name="descuento_porcentaje_input" class="form-input w-full" value="0" min="0" max="100" step="0.01">
                    </div>
                    <div>
                        <label for="monto_descuento_display_modal" class="block font-medium text-gray-700 mb-1">Monto Desc.</label>
                        <input type="text" id="monto_descuento_display_modal" class="form-input-plaintext w-full" readonly>
                        <input type="hidden" id="monto_descuento_input_modal" name="monto_descuento_input">
                    </div>
                </div>
                <div class="mt-3">
                    <label for="total_general_display_modal" class="block text-base font-semibold text-gray-700 mb-1">TOTAL</label>
                    <input type="text" id="total_general_display_modal" class="form-input-plaintext w-full text-lg font-bold" readonly>
                    <input type="hidden" id="total_general_input_modal" name="total_general_input">
                </div>
                <div class="mt-3">
                    <label for="observaciones_compra_modal" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observaciones_compra_modal" name="observaciones_compra" rows="2" class="form-textarea w-full"></textarea>
                </div>
            </fieldset>
            <!-- Aquí podrías añadir un div para mensajes de error del formulario -->
            <div id="mensajeErrorFormCompraModal" class="text-red-600 text-sm mt-2"></div>
        </form>
    </div>

    <!-- Pie del Modal (Acciones) -->
    <div class="px-6 py-4 border-t flex justify-end space-x-3 flex-shrink-0">
      <button type="button" id="btnCancelarCompraModal" class="btn-secondary">Cancelar</button>
      <button type="button" id="btnGuardarCompraModal" class="btn-primary">Guardar Compra</button>
    </div>
  </div>
</div>

<!-- Modal para Registrar Nuevo Proveedor (se mantiene igual, pero su JS de apertura se adaptará) -->
<div id="modalNuevoProveedor" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-[60]"> <!-- z-index mayor -->
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl">
    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-800">Registrar Nuevo Proveedor</h3>
      <button id="btnCerrarModalNuevoProveedor" class="text-gray-600 hover:text-gray-800">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="formNuevoProveedor" class="px-6 py-4">
      <!-- Campos del formulario de proveedor (igual que antes) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="identificacion_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Identificación*</label>
          <input type="text" name="identificacion_proveedor_nuevo" id="identificacion_proveedor_nuevo" class="form-input mt-1 block w-full" required>
        </div>
        <div>
          <label for="nombre_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Nombre(s)*</label>
          <input type="text" name="nombre_proveedor_nuevo" id="nombre_proveedor_nuevo" class="form-input mt-1 block w-full" required>
        </div>
        <div>
          <label for="apellido_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Apellido(s)</label>
          <input type="text" name="apellido_proveedor_nuevo" id="apellido_proveedor_nuevo" class="form-input mt-1 block w-full">
        </div>
        <div>
          <label for="telefono_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Teléfono</label>
          <input type="text" name="telefono_proveedor_nuevo" id="telefono_proveedor_nuevo" class="form-input mt-1 block w-full">
        </div>
        <div class="md:col-span-2">
          <label for="correo_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
          <input type="email" name="correo_proveedor_nuevo" id="correo_proveedor_nuevo" class="form-input mt-1 block w-full">
        </div>
        <div class="md:col-span-2">
          <label for="direccion_proveedor_nuevo" class="block text-sm font-medium text-gray-700">Dirección</label>
          <textarea name="direccion_proveedor_nuevo" id="direccion_proveedor_nuevo" rows="2" class="form-textarea mt-1 block w-full"></textarea>
        </div>
      </div>
      <div class="flex justify-end space-x-3 mt-4 pt-4 border-t">
        <button type="button" id="btnCancelarModalNuevoProveedor" class="btn-secondary">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar Proveedor</button>
      </div>
    </form>
  </div>
</div>


<?php footerAdmin($data); ?>
