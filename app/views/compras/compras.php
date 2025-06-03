<?php headerAdmin($data); ?>
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
  <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
     <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
  </div>

  <div class="mt-0 sm:mt-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?= $data['page_name'] ?></h1>
    <p class="text-green-600 text-base md:text-lg">Registro y consulta de compras de materiales</p>
  </div>

  <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
    <div class="flex justify-between items-center mb-6">

      <button id="btnAbrirModalNuevaCompra" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
        <i class="mr-1 md:mr-2"></i>Registrar Nueva Compra
      </button>
    </div>

    <div class="overflow-x-auto w-full relative">
      <table id="TablaCompras" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 800px;">
        <thead>
          <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
            <th class="px-3 py-3 text-left">Nro. Compra</th>
            <th class="px-3 py-3 text-left">Fecha</th>
            <th class="px-3 py-3 text-left">Proveedor</th>
            <th class="px-3 py-3 text-left">Total</th>
            <th class="px-3 py-3 text-left">Estado</th>
            <th class="px-3 py-3 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
          <!-- Filas inyectadas por DataTable -->
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal para Registrar Nueva Compra-->
<div id="modalNuevaCompra" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">

    <div class="bg-gray-50 px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-xl md:text-2xl font-bold text-gray-800">
        <i class="fas fa-shopping-cart mr-1 text-green-600"></i>Registrar Nueva Compra
      </h3>
      <button id="btnCerrarModalNuevaCompra" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
      </button>
    </div>

    <form id="formNuevaCompraModal" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
        
        <div>
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Datos Generales</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="fecha_compra_modal" class="block text-sm font-medium text-gray-700 mb-1">Fecha Compra <span class="text-red-500">*</span></label>
                    <input type="date" id="fecha_compra_modal" name="fecha_compra" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <div id="tasaDelDiaInfo" class="text-xs text-blue-700 font-semibold my-2"></div>
                </div>
                <div>
                    <label for="idmoneda_general_compra_modal" class="block text-sm font-medium text-gray-700 mb-1">Moneda General <span class="text-red-500">*</span></label>
                    <select id="idmoneda_general_compra_modal" name="idmoneda_general_compra" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección Proveedor -->
        <div class="mt-4 mb-4">
            <label for="inputCriterioProveedorModal" class="block text-sm font-medium text-gray-700 mb-1">Buscar Proveedor <span class="text-red-500">*</span></label>
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end mt-1">
                <input type="text" id="inputCriterioProveedorModal" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Identificación...">
                <button type="button" id="btnBuscarProveedorModal" class="bg-green-500 hover:bg-green-600 text-white rounded-lg transition text-sm md:text-base px-4 py-3 w-full sm:w-auto">Buscar</button>
            </div>
            <input type="hidden" id="idproveedor_seleccionado_modal" name="idproveedor_seleccionado">
            <div id="proveedor_seleccionado_info_modal" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
            <div id="listaResultadosProveedorModal" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden">
            </div>
        </div>
        <button type="button" onclick="abrirModalProveedor('Registrar Proveedor', 'proveedorForm', 'POST', 'proveedores/createProveedor')" class="bg-green-500 hover:bg-green-700 text-white rounded-lg transition text-sm md:text-base font-medium px-4 py-2 md:px-6 md:py-3">
            <i class="fas fa-user-plus mr-2"></i>Registrar Nuevo Proveedor
        </button>

        <!-- Sección Detalle de la Compra -->
        <div class="mt-6">
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Detalle de la Compra</h4>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 mb-4">
                <div class="flex-grow w-full sm:w-auto">
                    <label for="select_producto_agregar_modal" class="block text-sm font-medium text-gray-700 mb-1">Agregar Producto <span class="text-red-500">*</span></label>
                    <select id="select_producto_agregar_modal" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Cargando productos...</option>
                    </select>
                </div>
                <button type="button" id="btnAgregarProductoDetalleModal" class="bg-green-500 hover:bg-green-600 text-white rounded-lg px-4 py-3 text-sm md:text-base w-full sm:w-auto">
                    <i class="fas fa-plus mr-2"></i>Agregar al Detalle
                </button>
            </div>
            <div class="overflow-x-auto border border-gray-200 rounded-md">
                <table id="tablaDetalleCompraModal" class="w-full text-xs sm:text-sm">
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
                <p id="noDetallesMensaje" class="text-center text-gray-500 py-4 text-sm hidden">No hay productos en el detalle.</p>
            </div>
        </div>

        <!-- Sección Resumen y Observaciones -->
        <div class="mt-6">
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Resumen y Observaciones</h4>
            <div class="mb-4 bg-gray-100 p-3 rounded-md">
                <label for="total_general_display_modal" class="block text-xs font-medium text-gray-500 uppercase mb-0.5">Total General</label>
                <input type="text" id="total_general_display_modal" class="w-full bg-transparent text-xl font-bold text-green-600 focus:outline-none p-0 border-0" readonly>
                <input type="hidden" id="total_general_input_modal" name="total_general_input">
            </div>
            <div>
                <label for="observaciones_compra_modal" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea id="observaciones_compra_modal" name="observaciones_compra" rows="3" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
        </div>
        <div id="mensajeErrorFormCompraModal" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
    </form>

    <!-- Pie del Modal (Acciones) -->
    <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
      <button type="button" id="btnCancelarCompraModal" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnGuardarCompraModal" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
        <i class="fas fa-save mr-2"> </i> Guardar Compra
      </button>
    </div>
  </div>
</div>

<!-- Modal para Registrar Nuevo Proveedor (desde Compras) -->
<div id="proveedorModal" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]"> 
    <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 id="modalProveedorTitulo" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Proveedor</h3>
      <button onclick="cerrarModalProveedor()" class="text-gray-500 hover:text-gray-700 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <form id="proveedorForm" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
      <input type="hidden" id="idproveedor" name="idproveedor"> 
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
        <div>
          <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre o Razón Social <span class="text-red-500">*</span></label>
          <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label for="apellido" class="block text-sm font-medium text-gray-700 mb-1">Apellido (Contacto, si aplica)</label>
          <input type="text" id="apellido" name="apellido" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label for="identificacion" class="block text-sm font-medium text-gray-700 mb-1">Identificación (RIF/CI) <span class="text-red-500">*</span></label>
          <input type="text" id="identificacion" name="identificacion" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label for="telefono_principal" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
          <input type="text" id="telefono_principal" name="telefono_principal" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label for="correo_electronico" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
          <input type="email" id="correo_electronico" name="correo_electronico" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
         <div>
          <label for="genero" class="block text-sm font-medium text-gray-700 mb-1">Género (Contacto, si aplica)</label>
          <select id="genero" name="genero" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Seleccione...</option>
            <option value="masculino">Masculino</option>
            <option value="femenino">Femenino</option>
            <option value="no_aplica">No Aplica</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="md:col-span-2"> 
          <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
          <textarea id="direccion" name="direccion" rows="2" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
        <div>
          <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento/Constitución</label>
          <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label for="estatus" class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
          <select id="estatus" name="estatus" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="ACTIVO">Activo</option>
            <option value="INACTIVO">Inactivo</option>
          </select>
        </div>
        <div class="md:col-span-2"> 
          <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <textarea id="observaciones" name="observaciones" rows="3" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
      </div>
    </form>
    <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
      <button type="button" onclick="cerrarModalProveedor()" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
        Cancelar
      </button>
      <button type="submit" id="btnSubmitProveedor" form="proveedorForm" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
        Registrar
      </button>
    </div>
  </div>
</div>

<!-- Modal para Editar Compra -->
<div id="modalEditarCompra" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">

    <div class="bg-gray-50 px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-xl md:text-2xl font-bold text-gray-800">
        <i class="fas fa-edit mr-1 text-green-600"></i>Editar Compra
      </h3>
      <button id="btnCerrarModalEditarCompra" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
      </button>
    </div>


    <form id="formEditarCompraModal" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
        <input type="hidden" id="idcompra_editar" name="idcompra">
      
        <div>
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Datos Generales</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="fechaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Fecha Compra <span class="text-red-500">*</span></label>
                    <input type="date" id="fechaActualizar" name="fechaActualizar" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <div id="tasaDelDiaInfoActualizar" class="text-xs text-blue-700 font-semibold my-2"></div>
                </div>
                <div>
                    <label for="idmoneda_general_compra_actualizar" class="block text-sm font-medium text-gray-700 mb-1">Moneda General <span class="text-red-500">*</span></label>
                    <select id="idmoneda_general_compra_actualizar" name="idmoneda_general_compra" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección Proveedor -->
        <div class="mt-4 mb-4">
            <label for="inputCriterioProveedorActualizar" class="block text-sm font-medium text-gray-700 mb-1">Buscar Proveedor <span class="text-red-500">*</span></label>
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end mt-1">
                <input type="text" id="inputCriterioProveedorActualizar" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Identificación...">
                <button type="button" id="btnBuscarProveedorActualizar" class="bg-green-500 hover:bg-green-600 text-white rounded-lg transition text-sm md:text-base px-4 py-3 w-full sm:w-auto">Buscar</button>
            </div>
            <input type="hidden" id="idproveedor_seleccionado_actualizar" name="idproveedor_seleccionado">
            <div id="proveedor_seleccionado_info_actualizar" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
            <div id="listaResultadosProveedorActualizar" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden">
            </div>
        </div>

        <div class="mt-6">
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Detalle de la Compra</h4>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 mb-4">
                <div class="flex-grow w-full sm:w-auto">
                    <label for="select_producto_agregar_actualizar" class="block text-sm font-medium text-gray-700 mb-1">Agregar Producto <span class="text-red-500">*</span></label>
                    <select id="select_producto_agregar_actualizar" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Cargando productos...</option>
                    </select>
                </div>
                <button type="button" id="btnAgregarProductoDetalleActualizar" class="bg-green-500 hover:bg-green-600 text-white rounded-lg px-4 py-3 text-sm md:text-base w-full sm:w-auto">
                    <i class="fas fa-plus mr-2"></i>Agregar al Detalle
                </button>
            </div>
            <div class="overflow-x-auto border border-gray-200 rounded-md">
                <table id="tablaDetalleCompraActualizar" class="w-full text-xs sm:text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Producto</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Info Específica</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Precio U.</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-600">Subtotal</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-600">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaDetalleCompraActualizar" class="divide-y divide-gray-200">
                    </tbody>
                </table>
                 <p id="noDetallesMensajeActualizar" class="text-center text-gray-500 py-4 text-sm hidden">No hay productos en el detalle.</p> <!-- Added similar message for edit -->
            </div>
        </div>

        <!-- Sección Resumen y Observaciones -->
        <div class="mt-6">
            <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Resumen y Observaciones</h4>
            <div class="mb-4 bg-gray-100 p-3 rounded-md">
                <label for="total_general_display_actualizar" class="block text-xs font-medium text-gray-500 uppercase mb-0.5">Total General</label>
                <input type="text" id="total_general_display_actualizar" class="w-full bg-transparent text-xl font-bold text-green-600 focus:outline-none p-0 border-0" readonly>
                <input type="hidden" id="total_general_input_actualizar" name="total_general_input">
            </div>
            <div>
                <label for="observacionesActualizar" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea id="observacionesActualizar" name="observacionesActualizar" rows="3" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
        </div>
        <div id="mensajeErrorFormCompraActualizar" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
    </form>

    <!-- Pie del Modal  -->
    <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
      <button type="button" id="btnCancelarCompraActualizar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnActualizarCompraModal" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
        <i class="fas fa-save mr-2"> </i> Actualizar Compra
      </button>
    </div>
  </div>
</div>

<!-- Modal para Ver Compra -->
<div id="modalVerCompra" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-30 z-50 opacity-0 pointer-events-none transition-opacity duration-300 p-4">
  <div class="bg-white rounded-xl shadow-lg w-full sm:w-11/12 max-w-4xl max-h-[95vh]">
    <div class="flex justify-between items-center px-4 md:px-6 py-4 border-b">
      <h3 class="text-lg md:text-xl font-bold text-gray-800">
        <i class="fas fa-eye mr-2 text-green-600"></i>Detalle de la Compra
      </h3>
      <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path
            fill-rule="evenodd"
            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
    </div>
    <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6">
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-500">Número de Compra:</label>
          <p id="verNroCompra" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
        </div>
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-500">Fecha:</label>
          <p id="verFecha" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
        </div>
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-500">Proveedor:</label>
          <p id="verProveedor" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
        </div>
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-500">Estado:</label>
          <p id="verEstado" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs sm:text-sm font-medium text-gray-500">Observaciones:</label>
          <p id="verObservaciones" class="text-sm sm:text-base text-gray-700">-</p>
        </div>
      </div>
      <div class="mt-4">
        <h4 class="text-base md:text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
          Detalle de Productos
        </h4>
        <div class="overflow-x-auto border border-gray-200 rounded-md">
          <table class="w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Producto</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600">Cantidad</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600">Precio Unitario</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600">Descuento</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600">Subtotal</th>
              </tr>
            </thead>
            <tbody id="verDetalleProductos" class="divide-y divide-gray-200">
            </tbody>
          </table>
        </div>
      </div>

      <!-- Resumen de Totales -->
      <div class="mt-6 pt-4 border-t">
        <h4 class="text-base md:text-lg font-semibold text-gray-800 mb-3">Resumen de Totales</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
          <div id="contenedorTotalProductosEUR" style="display: none">
            <label class="block text-xs sm:text-sm font-medium text-gray-500">Total Productos en Euros (€):</label>
            <p id="verTotalProductosEUR"class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
          </div>
          <div id="contenedorTotalProductosUSD" style="display: none">
            <label class="block text-xs sm:text-sm font-medium text-gray-500">Total Productos en Dólares ($):</label>
            <p id="verTotalProductosUSD" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
          </div>
          <div id="contenedorTasaEURVES" style="display: none">
            <label class="block text-xs sm:text-sm font-medium text-gray-500">Tasa EUR/VES (Fecha Compra):</label>
            <p id="verTasaEURVES" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
          </div>
          <div id="contenedorTasaUSDVES" style="display: none">
            <label class="block text-xs sm:text-sm font-medium text-gray-500">Tasa USD/VES (Fecha Compra):</label>
            <p id="verTasaUSDVES" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
          </div>
          <div class="md:col-span-2 mt-2">
            <label class="block text-sm font-medium text-gray-500">Total General (Bs.):</label>
            <p id="verTotalGeneral" class="text-lg md:text-xl font-bold text-green-600">-</p>
          </div>
        </div>
      </div>
    
    <div class="flex justify-end pt-4 md:pt-6 border-t border-gray-200">
      <button type="button" id="btnCerrarModalVer2" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 text-sm md:text-base"> <i class="fas fa-times mr-1 md:mr-2"></i>
        Cerrar
      </button>
    </div>
    </div>
  </div>
</div>


<?php footerAdmin($data); ?>