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
                <select id="idmoneda_general" data-codigo name="idmoneda_general" class="w-full px-4 py-2 text-sm bg-white border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
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
<div id="modalRegistrarCliente" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="w-full max-w-lg mx-4 bg-white rounded-xl shadow-xl transform scale-95 transition-transform duration-300">
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
    
    <form id="formRegistrarCliente" class="px-6 py-6">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label for="nombre_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Nombre <span class="text-red-500">*</span>
          </label>
          <input type="text" id="nombre_cliente_modal" name="nombre" 
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                 placeholder="Ingrese el nombre" required>
          <div id="error-nombre_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="apellido_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Apellido <span class="text-red-500">*</span>
          </label>
          <input type="text" id="apellido_cliente_modal" name="apellido" 
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                 placeholder="Ingrese el apellido" required>
          <div id="error-apellido_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="identificacion_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Identificación <span class="text-red-500">*</span>
          </label>
          <input type="text" id="identificacion_cliente_modal" name="identificacion" 
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                 placeholder="Ingrese la identificación" required>
          <div id="error-identificacion_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="telefono_principal_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Teléfono Principal <span class="text-red-500">*</span>
          </label>
          <input type="text" id="telefono_principal_cliente_modal" name="telefono_principal" 
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                 placeholder="Ingrese el teléfono" required>
          <div id="error-telefono_principal_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="fecha_nacimiento_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Fecha de Nacimiento
          </label>
          <input type="date" id="fecha_nacimiento_cliente_modal" name="fecha_nacimiento" 
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <div id="error-fecha_nacimiento_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div>
          <label for="genero_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Género
          </label>
          <select id="genero_cliente_modal" name="genero" 
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
            <option value="">Seleccionar género</option>
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
            <option value="Otro">Otro</option>
            <option value="Prefiero no decir">Prefiero no decir</option>
          </select>
          <div id="error-genero_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div class="md:col-span-2">
          <label for="correo_electronico_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Correo Electrónico
          </label>
          <input type="email" id="correo_electronico_cliente_modal" name="correo_electronico" 
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                 placeholder="cliente@email.com">
          <div id="error-correo_electronico_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div class="md:col-span-2">
          <label for="direccion_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Dirección <span class="text-red-500">*</span>
          </label>
          <textarea id="direccion_cliente_modal" name="direccion" rows="2" 
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                    placeholder="Ingrese la dirección completa" required></textarea>
          <div id="error-direccion_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
        
        <div class="md:col-span-2">
          <label for="observaciones_cliente_modal" class="block mb-1 text-sm font-medium text-gray-700">
            Observaciones
          </label>
          <textarea id="observaciones_cliente_modal" name="observaciones" rows="2" 
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                    placeholder="Observaciones adicionales"></textarea>
          <div id="error-observaciones_cliente_modal" class="mt-1 text-xs text-red-500 hidden"></div>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" id="cancelarRegistrarClienteBtn" 
                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
          Cancelar
        </button>
        <button type="submit" 
                class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
          <i class="mr-2 fas fa-save"></i>
          Registrar Cliente
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



<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>