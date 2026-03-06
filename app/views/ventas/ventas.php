<?php headerAdmin($data);
$permisos = $data['permisos'] ?? []; ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>

  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Gestión de Ventas</h1>
    <p class="text-green-500 text-lg">Ventas</p>
    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <?php if ($permisos['puede_crear']): ?>
          <button id="abrirModalBtn" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
            <i class="fas fa-plus mr-2"></i>Registrar Venta
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
          <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
        </button>
      </div>

      <!-- Contenido del Modal -->
      <div class="px-8">
        <form id="ventaForm" class="px-8 py-6 max-h-[80vh] overflow-y-auto">
          <!-- Sección Datos Generales -->
          <div class="mb-4">
            <h4 class="pb-2 mb-3 text-base font-semibold text-gray-700 border-b">Datos Generales</h4>
            <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-1">
              <div>
                <label for="fecha_venta_modal" class="block text-sm font-medium text-gray-700">Fecha de Venta <span class="text-red-500">*</span></label>
                <input type="date" id="fecha_venta_modal" name="fecha_venta" class="w-full px-4 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" />
                <div id="error-fecha_venta_modal-vacio" class="mt-1 hidden text-xs text-red-500"></div>
                <div id="error-fecha_venta_modal-fechaPosterior" class="mt-1 hidden text-xs text-red-500"></div>
              </div>
              <!-- Información de moneda fija -->
              <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center">
                  <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                  <span class="text-sm text-blue-700">
                    <strong>Moneda:</strong> VES (Bolívares) - Seleccionada automáticamente
                  </span>
                </div>
              </div>
              <!-- Campo de moneda oculto con valor fijo VES -->
              <div style="display: none;">
                <select id="idmoneda_general" data-codigo name="idmoneda_general" class="w-full px-4 py-2 text-sm bg-white border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" >
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
              <button type="button" id="btnLimpiarClienteModal" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 transition hidden" title="Limpiar selección">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <input type="hidden" id="idcliente" name="idcliente"> <!-- ID del cliente para la VENTA -->
            <div id="cliente_seleccionado_info_modal" class="p-1.5 mt-1 hidden rounded-md border border-gray-200 bg-gray-50 text-xs relative">
              <button type="button" id="btnEliminarClienteSeleccionado" class="absolute top-1 right-1 p-1 text-gray-400 hover:text-red-500 transition-colors" title="Eliminar cliente seleccionado">
                <i class="fas fa-times text-xs"></i>
              </button>
            </div>
            <div id="listaResultadosClienteModal" class="z-10 mt-1 hidden max-h-20 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg"></div>
          </div>

          <!-- Botón para abrir el modal de registro de cliente -->
          <button type="button" id="btnAbrirModalRegistrarCliente" class="px-4 py-2 mb-3 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 transition">
            <i class="mr-2 fas fa-user-plus"></i>Registrar Nuevo Cliente
          </button>

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
              <button type="button" id="agregarDetalleBtn" class="w-full px-4 py-2 text-sm text-white font-medium bg-green-500 rounded-lg  sm:w-auto transition">
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
        <button type="button" id="cerrarModalBtn" class="btn-neutral px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">Cancelar</button>
        <button type="button" id="registrarVentaBtn" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base ">
          <i class="mr-2 fas fa-save"></i> Guardar Venta
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>


<!-- Modal Registrar Cliente -->
<div id="modalRegistrarCliente" class="fixed inset-0 z-50 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="overflow-hidden w-11/12 max-w-md bg-white rounded-xl shadow-xl transform scale-95 transition-transform duration-300">
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200 rounded-t-xl">
      <h3 class="text-xl font-bold text-gray-800">
        <i class="mr-2 text-green-600 fas fa-user-plus"></i>
        Registrar Nuevo Cliente
      </h3>
      <button id="cerrarModalRegistrarClienteBtn" class="p-1 text-gray-400 transition-colors rounded-full hover:text-gray-600 hover:bg-gray-200">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>
    
    <form id="formRegistrarCliente" class="px-4 py-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <!-- Campo oculto para ID -->
        <input type="hidden" id="idcliente_modal" name="idcliente" value="">
        
        <div>
          <label for="cedula_cliente_modal" class="block mb-2 text-gray-700 font-medium">
            Cédula <span class="text-red-500">*</span>
          </label>
          <input type="text" id="cedula_cliente_modal" name="cedula" 
                 class="w-full px-4 py-2 text-lg border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                 placeholder="V-12345678" >
          <div id="error-cedula_cliente_modal-vacio" class="mt-1 text-sm text-red-500 hidden"></div>
          <div id="error-cedula_cliente_modal-formato" class="mt-1 text-sm text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="nombre_cliente_modal" class="block mb-2 text-gray-700 font-medium">
            Nombre <span class="text-red-500">*</span>
          </label>
          <input type="text" id="nombre_cliente_modal" name="nombre" 
                 class="w-full px-4 py-2 text-lg border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                 placeholder="Ingrese el nombre" >
          <div id="error-nombre_cliente_modal-vacio" class="mt-1 text-sm text-red-500 hidden"></div>
          <div id="error-nombre_cliente_modal-formato" class="mt-1 text-sm text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="apellido_cliente_modal" class="block mb-2 text-gray-700 font-medium">
            Apellido <span class="text-red-500">*</span>
          </label>
          <input type="text" id="apellido_cliente_modal" name="apellido" 
                 class="w-full px-4 py-2 text-lg border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                 placeholder="Ingrese el apellido" >
          <div id="error-apellido_cliente_modal-vacio" class="mt-1 text-sm text-red-500 hidden"></div>
          <div id="error-apellido_cliente_modal-formato" class="mt-1 text-sm text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="telefono_principal_cliente_modal" class="block mb-2 text-gray-700 font-medium">
            Teléfono Principal <span class="text-red-500">*</span>
          </label>
          <input type="text" id="telefono_principal_cliente_modal" name="telefono_principal" 
                 class="w-full px-4 py-2 text-lg border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                 placeholder="04XXXXXXXXX" >
          <div id="error-telefono_principal_cliente_modal-vacio" class="mt-1 text-sm text-red-500 hidden"></div>
          <div id="error-telefono_principal_cliente_modal-formato" class="mt-1 text-sm text-red-500 hidden"></div>
        </div>
        
        <div class="md:col-span-2">
          <label for="direccion_cliente_modal" class="block mb-2 text-gray-700 font-medium">
            Dirección <span class="text-red-500">*</span>
          </label>
          <input type="text" id="direccion_cliente_modal" name="direccion" 
                 class="w-full px-4 py-2 text-lg border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                 placeholder="Ingrese la dirección completa" >
          <div id="error-direccion_cliente_modal-vacio" class="mt-1 text-sm text-red-500 hidden"></div>
          <div id="error-direccion_cliente_modal-formato" class="mt-1 text-sm text-red-500 hidden"></div>
        </div>
        
        <div class="md:col-span-2">
          <label for="observaciones_cliente_modal" class="block mb-2 text-gray-700 font-medium">
            Observaciones
          </label>
          <textarea id="observaciones_cliente_modal" name="observaciones" 
                 class="w-full px-4 py-2 text-lg border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                 placeholder="Observaciones adicionales"></textarea>
          <div id="error-observaciones_cliente_modal-formato" class="mt-1 text-sm text-red-500 hidden"></div>
        </div>
      </div>
      
      <div class="grid grid-cols-2 gap-4 mt-6">
        <button type="button" id="cancelarRegistrarClienteBtn" 
                class="px-4 py-2 text-lg text-gray-800 transition bg-gray-200 rounded hover:bg-gray-300">
          Cancelar
        </button>
        <button type="submit" id="registrarClienteBtn"
                class="px-4 py-2 text-lg text-white transition bg-green-500 rounded hover:bg-green-600">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Detalle de Venta -->
<div id="modalDetalleVenta" class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-50 opacity-0 pointer-events-none transparent backdrop-blur-[2px] transition-opacity duration-300">
  <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg">
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200">
      <h3 class="text-xl font-bold text-gray-800">
        <i class="mr-1 text-indigo-600 fas fa-eye"></i>Detalle de Venta
      </h3>
      <button id="cerrarModalDetalleVentaBtn" class="p-1 text-gray-400 transition-colors rounded-full hover:text-gray-600 hover:bg-gray-200">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>
    <div class="px-8 py-6 max-h-[70vh] overflow-y-auto" id="detalleVentaContenido">
      <!-- Aquí se cargará el detalle por JS -->
    </div>
    <div class="flex justify-end px-6 py-3 bg-gray-50 border-t border-gray-200">
      <button type="button" id="cerrarModalDetalleVentaBtn2" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">Cerrar</button>
    </div>
  </div>
</div>



<!-- Modal Pagos de Venta -->
<div id="modalPagosVenta" class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-50 opacity-0 pointer-events-none transparent backdrop-blur-[2px] transition-opacity duration-300">
  <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg flex flex-col" style="max-height:92vh">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200 flex-shrink-0">
      <div>
        <h3 class="text-lg font-bold text-gray-800">
          <i class="fas fa-credit-card mr-2 text-green-600"></i>
          <span id="modalPagos_titulo">Pagos de Venta</span>
        </h3>
        <p class="text-xs text-gray-500 mt-0.5" id="modalPagos_subtitulo"></p>
      </div>
      <button id="cerrarModalPagosVentaBtn" class="p-1 text-gray-400 rounded-full hover:text-gray-600 hover:bg-gray-200 transition-colors">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>

    <!-- Resumen balance -->
    <div id="modalPagos_resumen" class="px-6 pt-4 pb-3 border-b border-gray-100 flex-shrink-0"></div>

    <!-- Cuerpo scrolleable: historial -->
    <div class="overflow-y-auto flex-1 px-6 py-4" id="modalPagos_cuerpo">
      <div class="flex justify-center items-center py-8">
        <i class="fas fa-spinner fa-spin mr-2 text-gray-400"></i>
        <span class="text-gray-400">Cargando...</span>
      </div>
    </div>

    <!-- Formulario de nuevo pago (estático, se muestra/oculta por JS) -->
    <div id="seccionNuevoPago" class="hidden border-t border-gray-200 px-6 py-4 bg-gray-50 flex-shrink-0">
      <h4 class="text-sm font-semibold text-gray-700 mb-3">
        <i class="fas fa-plus-circle mr-1 text-green-500"></i>Registrar nuevo pago
      </h4>
      <form id="formNuevoPagoVenta" novalidate>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

          <!-- Monto -->
          <div>
            <label for="pagoMonto" class="block text-xs font-medium text-gray-600 mb-1">
              Monto <span class="text-red-400">*</span>
              <span id="pagoMonto_hint" class="text-gray-400 font-normal ml-1"></span>
            </label>
            <div id="pagoMonto_wrapper" class="flex items-center border rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-green-400">
              <input type="number" id="pagoMonto" name="monto" step="0.01" min="0.01"
                class="flex-1 px-3 py-2 text-sm outline-none bg-white"
                placeholder="0.00" />
              <span id="pagoMonto_moneda" class="px-2 text-xs text-gray-400 bg-gray-50 border-l whitespace-nowrap">—</span>
            </div>
            <p id="pagoMonto_error" class="mt-1 text-xs text-red-500 hidden"></p>
          </div>

          <!-- Método de pago -->
          <div>
            <label for="pagoTipo" class="block text-xs font-medium text-gray-600 mb-1">
              Método de pago <span class="text-red-400">*</span>
            </label>
            <select id="pagoTipo" name="idtipo_pago"
              class="w-full px-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-400 bg-white">
              <option value="">Seleccionar...</option>
            </select>
            <p id="pagoTipo_error" class="mt-1 text-xs text-red-500 hidden"></p>
          </div>

          <!-- Fecha -->
          <div>
            <label for="pagoFecha" class="block text-xs font-medium text-gray-600 mb-1">
              Fecha <span class="text-red-400">*</span>
            </label>
            <input type="date" id="pagoFecha" name="fecha_pago"
              class="w-full px-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-400 bg-white" />
            <p id="pagoFecha_error" class="mt-1 text-xs text-red-500 hidden"></p>
          </div>

          <!-- Referencia (opcional) -->
          <div>
            <label for="pagoReferencia" class="block text-xs font-medium text-gray-600 mb-1">
              Referencia <span class="text-gray-400 font-normal">(opcional)</span>
            </label>
            <input type="text" id="pagoReferencia" name="referencia"
              class="w-full px-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-400 bg-white"
              placeholder="Nro. transferencia, cheque..." maxlength="100" />
          </div>

          <!-- Observaciones (span 2, opcional) -->
          <div class="sm:col-span-2">
            <label for="pagoObservaciones" class="block text-xs font-medium text-gray-600 mb-1">
              Observaciones <span class="text-gray-400 font-normal">(opcional)</span>
            </label>
            <input type="text" id="pagoObservaciones" name="observaciones"
              class="w-full px-3 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-green-400 bg-white"
              placeholder="Ej: Pago parcial del cliente, cheque diferido..." maxlength="255" />
          </div>

        </div>
      </form>
    </div>

   
    <div class="flex justify-between px-6 py-3 bg-gray-50 border-t border-gray-200 flex-shrink-0">
      <button type="button" id="cerrarModalPagosVentaBtn2"
        class="btn-neutral px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
        Cancelar
      </button>
      <button type="submit" form="formNuevoPagoVenta" id="btnSubmitPago"
        class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium disabled:opacity-60 flex items-center gap-2">
        <i class="fas fa-save mr-2"></i> Guardar Pago
      </button>
    </div>

  </div>
</div>



<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>