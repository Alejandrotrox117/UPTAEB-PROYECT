
<?php headerAdmin($data); 
$permisos = $data['permisos'] ?? []; ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Gestión de Ventas</h1>
    <p class="text-green-500 text-lg">Ventas</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
       <?php if ($permisos['puede_crear']): ?>
        <button id="abrirModalBtn" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar Venta
        </button>
        <?php else: ?>
          <div class="text-gray-500 text-sm">
          <i class="fas fa-lock mr-2"></i>No tiene permisos para registrar ventas
        </div>
        <?php endif; ?>
        <?php if (!$permisos['puede_ver']): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm">Acceso limitado: Solo puede ver la información básica.</p>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div style="overflow-x: auto;">
        <table id="Tablaventas" class="w-full text-left border-collapse mt-6 ">
          <thead>
            <tr class="text-gray-500 text-sm border-b">
              <th class="py-2">Nro venta</th>
              <th class="py-2">Cliente</th>
              <th class="py-2">Fecha</th>
              <th class="py-2">Total</th>
              <th class="py-2">Estatus</th>
              <?php if ($permisos['puede_editar'] || $permisos['puede_eliminar']): ?>
              <th class="py-2">Acciones</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody class="text-gray-900">
            <!-- Las filas de la tabla se cargarán aquí por JavaScript -->
          </tbody>
        </table>
      </div>
    </div>
</main>
</div>
<?php if ($permisos['puede_crear']): ?>
<!-- Modal para Registrar Nueva Venta -->
<div id="ventaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-50 opacity-0 pointer-events-none transparent backdrop-blur-[2px] transition-opacity duration-300">
  <div class="w-11/12 max-w-4xl max-h-screen overflow-hidden bg-white rounded-xl shadow-lg">
    <!-- Encabezado del Modal -->
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200">
      <h3 class="text-2xl font-bold text-gray-800">
        <i class="mr-1 text-green-600 fas fa-shopping-cart"></i>Registrar Nueva Venta
      </h3>
      <button id="btnCerrarModalNuevaVenta" class="p-1 text-gray-400 transition-colors rounded-full hover:text-gray-600 hover:bg-gray-200">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
    </div>

    <!-- Contenido del Modal -->
    <div class="px-8">
      <form id="ventaForm" class="px-8 py-6 max-h-[80vh] overflow-y-auto">
        <!-- Sección Datos Generales -->
        <div class="mb-4">
          <h4 class="pb-2 mb-3 text-base font-semibold text-gray-700 border-b">Datos Generales</h4>
          <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
              <label for="fecha_venta_modal" class="block text-sm font-medium text-gray-700">Fecha de Venta <span class="text-red-500">*</span></label>
              <input type="date" id="fecha_venta_modal" name="fecha_venta" class="w-full px-4 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" />
              <div id="error-fecha_venta_modal-vacio" class="mt-1 hidden text-xs text-red-500"></div>
              <div id="error-fecha_venta_modal-fechaPosterior" class="mt-1 hidden text-xs text-red-500"></div>
            </div>
            <div>
              <label for="idmoneda_general" class="block text-sm font-medium text-gray-700">Moneda <span class="text-red-500">*</span></label>
              <select id="idmoneda_general" name="idmoneda_general" class="w-full px-4 py-2 text-sm bg-white border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <option value="">Seleccione...</option>
              </select>
              <div id="error-idmoneda_general-vacio" class="mt-1 hidden text-xs text-red-500"></div>
            </div>
          </div>
        </div>

        <!-- Sección Cliente -->
        <div class="mb-4">
          <h4 class="pb-2 mb-3 text-base font-semibold text-gray-700 border-b">Cliente</h4>
          <label for="inputCriterioClienteModal" class="block mb-1 text-sm font-medium text-gray-700">Buscar Cliente Existente <span class="text-red-500">*</span></label>
          <div class="flex gap-2 mb-2">
            <input type="text" id="inputCriterioClienteModal" class="w-full px-3 py-1.5 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Identificación...">
            <button type="button" id="btnBuscarClienteModal" class="px-4 py-1.5 text-sm text-white bg-blue-500 rounded-md hover:bg-blue-600 transition">Buscar</button>
          </div>
          <input type="hidden" id="idcliente" name="idcliente"> <!-- ID del cliente para la VENTA -->
          <div id="cliente_seleccionado_info_modal" class="p-1.5 mt-1 hidden rounded-md border border-gray-200 bg-gray-50 text-xs"></div>
          <div id="listaResultadosClienteModal" class="z-10 mt-1 hidden max-h-20 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg"></div>
        </div>

        <!-- Botón para MOSTRAR/OCULTAR el formulario de nuevo cliente -->
        <button type="button" id="btnToggleNuevoCliente" class="px-4 py-2 mb-3 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 transition">
          <i class="mr-2 fas fa-user-plus"></i>Registrar Nuevo Cliente
        </button>

        <!-- Contenedor para el formulario de nuevo cliente (inicialmente oculto) -->
        <div id="nuevoClienteContainer" class="hidden p-3 mb-4 bg-gray-50 border border-gray-200 rounded-md">
            <h3 class="pb-1 mb-2 text-gray-700 border-b text-md font-semibold">Datos del Nuevo Cliente</h3>
            <!-- Aquí NO necesitas un <form> anidado, usarás los campos directamente -->
            <div class="grid grid-cols-1 gap-x-4 gap-y-2 md:grid-cols-2">
                <!-- Campo oculto para el ID del cliente nuevo, si se registra y se quiere usar inmediatamente -->
                <input type="hidden" id="idcliente_nuevo_hidden" name="idcliente_nuevo_formulario_hidden" value="" disabled>
                <div>
                    <label for="cedula_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Cédula <span class="text-red-500">*</span></label>
                    <input type="text" id="cedula_nuevo" name="cedula_nuevo_formulario" class="w-full px-2.5 py-1 text-xs border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled>
                    <div id="error-cedula_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                    <div id="error-cedula_nuevo-formato" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
                <div>
                    <label for="nombre_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre_nuevo" name="nombre_nuevo_formulario" class="w-full px-2.5 py-1 text-xs border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled>
                    <div id="error-nombre_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                    <div id="error-nombre_nuevo-formato" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
                <div>
                    <label for="apellido_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Apellido <span class="text-red-500">*</span></label>
                    <input type="text" id="apellido_nuevo" name="apellido_nuevo_formulario" class="w-full px-2.5 py-1 text-xs border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled>
                    <div id="error-apellido_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                    <div id="error-apellido_nuevo-formato" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
                <div>
                    <label for="telefono_principal_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Teléfono <span class="text-red-500">*</span></label>
                    <input type="text" id="telefono_principal_nuevo" name="telefono_principal_nuevo_formulario" class="w-full px-2.5 py-1 text-xs border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled>
                    <div id="error-telefono_principal_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                    <div id="error-telefono_principal_nuevo-formato" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="direccion_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Dirección <span class="text-red-500">*</span></label>
                    <input type="text" id="direccion_nuevo" name="direccion_nuevo_formulario" class="w-full px-2.5 py-1 text-xs border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled>
                    <div id="error-direccion_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                    <div id="error-direccion_nuevo-formato" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
                 <div class="md:col-span-2">
                    <label for="observacionesCliente_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Observaciones</label>
                    <textarea id="observacionesCliente_nuevo" name="observacionesCliente_nuevo_formulario" rows="1" class="w-full px-2.5 py-1 text-xs border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled></textarea>
                    <div id="error-observacionesCliente_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                    <div id="error-observacionesCliente_nuevo-formato" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
                <div>
                    <label for="estatus_nuevo" class="block mb-0.5 text-xs font-medium text-gray-600">Estatus <span class="text-red-500">*</span></label>
                    <select id="estatus_nuevo" name="estatus_nuevo_formulario" class="w-full px-2.5 py-1 text-xs bg-white border rounded-md focus:ring-1 focus:ring-green-400 focus:border-green-400" disabled>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                    <div id="error-estatus_nuevo-vacio" class="mt-0.5 hidden text-xs text-red-500"></div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-1.5 mt-2 border-t border-gray-100">
                <button type="button" id="cancelarNuevoClienteBtn" class="px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Cancelar</button>
                <button type="button" id="registrarClienteInlineBtn" class="px-2.5 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 transition">Guardar Cliente</button>
            </div>
        </div>
        <!-- FIN Contenedor para el formulario de nuevo cliente -->

        <!-- Sección Detalle de la Venta -->
        <div class="mb-4">
          <h4 class="pb-2 mb-3 text-base font-semibold text-gray-700 border-b">Detalle de la Venta</h4>
          <div class="flex flex-col items-end gap-3 mb-3 sm:flex-row">
            <div class="flex-grow w-full sm:w-auto">
              <label for="select_producto_agregar_modal" class="block mb-1 text-sm font-medium text-gray-700">Agregar Producto <span class="text-red-500">*</span></label>
              <select id="select_producto_agregar_modal" name="select_producto_agregar_modal" class="w-full px-4 py-2 text-sm bg-white border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">Seleccione un producto...</option>
              </select>
              <!-- <div id="error-select_producto_agregar_modal-vacio" class="mt-1 hidden text-xs text-red-500"></div> -->
            </div>
            <button type="button" id="agregarDetalleBtn" class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-500 rounded-lg hover:bg-indigo-600 sm:w-auto transition">
              <i class="mr-2 fas fa-plus"></i>Agregar al Detalle
            </button>
          </div>
          <div class="overflow-x-auto border border-gray-200 rounded-md">
            <table id="detalleVentaTable" class="w-full text-xs">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-1.5 text-left font-medium text-gray-600">Producto</th>
                  <th class="px-3 py-1.5 text-left font-medium text-gray-600">Cantidad</th>
                  <th class="px-3 py-1.5 text-left font-medium text-gray-600">Precio U.</th>
                  <th class="px-3 py-1.5 text-left font-medium text-gray-600">Subtotal</th>
                  <th class="px-3 py-1.5 text-center font-medium text-gray-600">Acción</th>
                </tr>
              </thead>
              <tbody id="detalleVentaBody" class="divide-y divide-gray-200">
              </tbody>
            </table>
            <p id="noDetallesMensaje" class="py-3 hidden text-xs text-center text-gray-500">No hay productos en el detalle.</p>
          </div>
        </div>

        <!-- Sección Resumen y Observaciones -->
        <div class="mb-4">
          <h4 class="pb-2 mb-3 text-base font-semibold text-gray-700 border-b">Resumen y Observaciones</h4>
          <div class="grid grid-cols-1 gap-4 mb-3 sm:grid-cols-3 content-evenly">
            <div>
              <label for="subtotal_general_display_modal" class="block mb-1 text-sm font-medium text-gray-700">Subtotal</label>
              <input type="text" id="subtotal_general_display_modal" class="w-full px-4 py-2 text-sm bg-gray-100 border rounded-md focus:outline-none" readonly>
              <input type="hidden" id="subtotal_general" name="subtotal_general">
            </div>
            <div>
              <label for="descuento_porcentaje_general" class="block mb-1 text-sm font-medium text-gray-700">Descuento (%)</label>
              <input type="number" id="descuento_porcentaje_general" name="descuento_porcentaje_general" class="w-full px-4 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" value="0" min="0" max="100" step="0.01">
            </div>
            <div>
              <label for="monto_descuento_general_display" class="block mb-1 text-sm font-medium text-gray-700">Monto Descuento</label>
              <input type="text" id="monto_descuento_general_display" class="w-full px-4 py-2 text-sm bg-gray-100 border rounded-md focus:outline-none" readonly>
              <input type="hidden" id="monto_descuento_general" name="monto_descuento_general">
            </div>
          </div>
          <div class="p-2.5 mb-3 bg-gray-100 rounded-md">
            <label for="total_general_display_modal" class="block mb-0.5 text-xs font-medium text-gray-500 uppercase">Total General</label>
            <input type="text" id="total_general_display_modal" class="w-full p-0 text-xl font-bold text-green-600 bg-transparent border-0 focus:outline-none" readonly>
            <input type="hidden" id="total_general" name="total_general">
          </div>
          <div>
            <label for="observaciones" class="block mb-1 text-sm font-medium text-gray-700">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="2" class="w-full px-4 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
           <div id="error-observaciones-formato" class="mt-1 hidden text-xs text-red-500"></div>
          </div>
        </div>
        <div id="mensajeErrorFormVentaModal" class="mt-3 hidden text-xs font-medium text-center text-red-600"></div>
      </form>
    </div>

    <!-- Pie del Modal -->
    <div class="flex justify-end px-6 py-3 space-x-3 bg-gray-50 border-t border-gray-200">
      <button type="button" id="cerrarModalBtn" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">Cancelar</button>
      <button type="button" id="registrarVentaBtn" class="px-5 py-2.5 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 transition">
        <i class="mr-2 fas fa-save"></i> Guardar Venta
      </button>
    </div>
  </div>
</div>
<?php endif; ?>


<script>
window.PERMISOS_USUARIO = {
    puede_ver: <?= json_encode($permisos['puede_ver'] ?? false) ?>,
    puede_crear: <?= json_encode($permisos['puede_crear'] ?? false) ?>,
    puede_editar: <?= json_encode($permisos['puede_editar'] ?? false) ?>,
    puede_eliminar: <?= json_encode($permisos['puede_eliminar'] ?? false) ?>,
    acceso_total: <?= json_encode($permisos['acceso_total'] ?? false) ?>
};
</script>

<?php footerAdmin($data); ?>