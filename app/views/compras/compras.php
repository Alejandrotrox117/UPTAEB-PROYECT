<?php headerAdmin($data); ?>

<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Gestión de Compras</h2>
  </div>

  <div>
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
<div id="modalNuevaCompra" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl">
    <!-- Encabezado del Modal -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-shopping-cart mr-1 text-green-600"></i>Registrar Nueva Compra
      </h3>
      <button id="btnCerrarModalNuevaCompra" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
      </button>
    </div>

    <!-- Contenido del Modal (Formulario) -->
    <form id="formNuevaCompraModal" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
        
        <!-- Sección Datos Generales -->
        <div>
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Datos Generales</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="fecha_compra_modal" class="form-label">Fecha Compra <span class="text-red-500">*</span></label>
                    <input type="date" id="fecha_compra_modal" name="fecha_compra" class="w-1/3 border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <div id="tasaDelDiaInfo" class="text-xs text-blue-700 font-semibold my-2"></div>
                </div>
                <div>
                    <label for="idmoneda_general_compra_modal" class="form-label">Moneda General <span class="text-red-500">*</span></label>
                    <select id="idmoneda_general_compra_modal" name="idmoneda_general_compra" class="w-1/3 mt-5 border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- En la sección Proveedor del modal de compra -->
        <div class="mb-4">
            <label for="buscar_proveedor_modal" class="form-label">Buscar Proveedor <span class="text-red-500">*</span></label>
            <div class="flex gap-4 ml-2">
                <input type="text" id="inputCriterioProveedorModal" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Identificación...">
                <button type="button" id="btnBuscarProveedorModal" class="btn-success px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base">Buscar</button>
            </div>
            <input type="hidden" id="idproveedor_seleccionado_modal" name="idproveedor_seleccionado">
            <div id="proveedor_seleccionado_info_modal" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
            <div id="listaResultadosProveedorModal" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden">
            </div>
        </div>
        <button type="button" onclick="abrirModalProveedor('Registrar Proveedor', 'proveedorForm', 'POST', 'proveedores/createProveedor')" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-700 transition text-base font-medium">
            <i class="fas fa-user-plus mr-2"></i>Registrar Nuevo Proveedor
        </button>

        <!-- Sección Detalle de la Compra -->
        <div>
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Detalle de la Compra</h4>
            <div class="flex flex-col sm:flex-row items-end gap-3 mb-4">
                <div class="flex-grow w-full sm:w-auto">
                    <label for="select_producto_agregar_modal" class="form-label">Agregar Producto <span class="text-red-500">*</span></label>
                    <select id="select_producto_agregar_modal" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Cargando productos...</option>
                    </select>
                </div>
                <button type="button" id="btnAgregarProductoDetalleModal" class="btn-primary-solid w-full sm:w-auto">
                    <i class="fas fa-plus mr-2"></i>Agregar al Detalle
                </button>
            </div>
            <div class="overflow-x-auto border border-gray-200 rounded-md">
                <table id="tablaDetalleCompraModal" class="w-full text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Producto</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Info Específica</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Precio U.</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Subtotal</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-600">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaDetalleCompraModal" class="divide-y divide-gray-200">
                    </tbody>
                </table>
                <p id="noDetallesMensaje" class="text-center text-gray-500 py-4 text-xs hidden">No hay productos en el detalle.</p>
            </div>
        </div>

        <!-- Sección Resumen y Observaciones -->
        <div>
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Resumen y Observaciones</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 content-evenly mb-4">
                <div>
                    <label for="subtotal_general_display_modal" class="block text-gray-700 font-medium mb-1">Subtotal</label>
                    <input type="text" id="subtotal_general_display_modal" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" readonly>
                    <input type="hidden" id="subtotal_general_input_modal" name="subtotal_general_input">
                </div>
                <div>
                    <label for="descuento_porcentaje_input_modal" class="block text-gray-700 font-medium mb-1">Descuento (%)</label>
                    <input type="number" id="descuento_porcentaje_input_modal" name="descuento_porcentaje_input" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" value="0" min="0" max="100" step="0.01">
                </div>
                <div>
                    <label for="monto_descuento_display_modal" class="block text-gray-700 font-medium mb-1">Monto Descuento</label>
                    <input type="text" id="monto_descuento_display_modal" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" readonly>
                    <input type="hidden" id="monto_descuento_input_modal" name="monto_descuento_input">
                </div>
            </div>
            <div class="mb-4 bg-gray-100 p-3 rounded-md">
                <label for="total_general_display_modal" class="block text-xs font-medium text-gray-500 uppercase mb-0.5">Total General</label>
                <input type="text" id="total_general_display_modal" class="w-full bg-transparent text-xl font-bold text-green-600 focus:outline-none p-0 border-0" readonly>
                <input type="hidden" id="total_general_input_modal" name="total_general_input">
            </div>
            <div>
                <label for="observaciones_compra_modal" class="form-label">Observaciones</label>
                <textarea id="observaciones_compra_modal" name="observaciones_compra" rows="3" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
        </div>
        <div id="mensajeErrorFormCompraModal" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
    </form>

    <!-- Pie del Modal (Acciones) -->
    <div class=" mr-4 bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
      <button type="button" id="btnCancelarCompraModal" class="btn-neutral px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnGuardarCompraModal" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
        <i class="fas fa-save mr-2"> </i> Guardar Compra
      </button>
    </div>
  </div>
</div>

<!-- Modal para Registrar Nuevo Proveedor -->
<div id="proveedorModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl"> 
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 id="modalProveedorTitulo" class="text-2xl font-bold text-gray-800">Registrar Proveedor</h3>
      <button onclick="cerrarModalProveedor()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <form id="proveedorForm" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
      <input type="hidden" id="idproveedor" name="idproveedor"> 
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
        <div>
          <label class="block text-gray-700 font-medium mb-1">Nombre o Razón Social <span class="text-red-500">*</span></label>
          <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Apellido (Contacto, si aplica)</label>
          <input type="text" id="apellido" name="apellido" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Identificación (RIF/CI) <span class="text-red-500">*</span></label>
          <input type="text" id="identificacion" name="identificacion" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
          <input type="text" id="telefono_principal" name="telefono_principal" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Correo Electrónico</label>
          <input type="email" id="correo_electronico" name="correo_electronico" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
         <div>
          <label class="block text-gray-700 font-medium mb-1">Género (Contacto, si aplica)</label>
          <select id="genero" name="genero" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Seleccione...</option>
            <option value="masculino">Masculino</option>
            <option value="femenino">Femenino</option>
            <option value="no_aplica">No Aplica</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="md:col-span-2"> 
          <label class="block text-gray-700 font-medium mb-1">Dirección</label>
          <textarea id="direccion" name="direccion" rows="2" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Fecha de Nacimiento/Constitución</label>
          <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Estatus</label>
          <select id="estatus" name="estatus" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="ACTIVO">Activo</option>
            <option value="INACTIVO">Inactivo</option>
          </select>
        </div>
        <div class="md:col-span-2"> 
          <label class="block text-gray-700 font-medium mb-1">Observaciones</label>
          <textarea id="observaciones" name="observaciones" rows="3" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
      </div>

      <div class="flex justify-end space-x-4 mt-8">
        <button type="button" onclick="cerrarModalProveedor()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
          Cancelar
        </button>
        <button type="submit" id="btnSubmitProveedor" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ver Compra -->
<div id="modalDetalleCompra" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-30 z-50 opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white rounded-xl shadow-lg w-11/12 max-w-2xl overflow-auto max-h-[80vh]">
    <div class="flex justify-between items-center px-6 py-4 border-b">
      <h3 class="text-xl font-bold text-gray-800">Detalle de la Compra</h3>
      <button id="btnCerrarModalDetalleCompra" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
      </button>
    </div>
    <div id="contenidoModalDetalleCompra" class="px-6 py-4">
      <!-- Aquí se inyecta el detalle -->
    </div>
    <div class="flex justify-end px-6 py-4 border-t">
      <button id="btnCerrarModalDetalleCompra2" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">Cerrar</button>
    </div>
  </div>
</div>




<?php footerAdmin($data); ?>
