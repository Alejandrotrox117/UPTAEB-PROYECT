<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Gestión de Ventas</h1>
    <p class="text-green-500 text-lg">Ventas</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button id="abrirModalBtn" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar
        </button>
      </div>
      <div style="overflow-x: auto;">
        <table id="Tablaventas" class="w-full text-left border-collapse mt-6 ">
          <thead>
            <tr class="text-gray-500 text-sm border-b">
              <th class="py-2">Nro venta</th>
              <th class="py-2">Producto </th>
              <th class="py-2">Fecha</th>
              <th class="py-2">Cantidad </th>


              <th class="py-2">Descuento</th>

              <th class="py-2">Total</th>

              <th class="py-2">Estatus</th>



            </tr>
          </thead>
          <tbody class="text-gray-900">
            <td>
              <button class="editar-btn bg-blue-500 text-white px-4 py-2 rounded" data-idcliente="1">Editar</button>
            </td>
          </tbody>
        </table>
      </div>
    </div>
</main>
</div>





<!-- Modal para Registrar Nueva Venta -->
<div id="ventaModal" class="fixed inset-0 flex items-center justify-center transparent backdrop-blur-[2px] transition-opacity duration-300 z-50 bg-opacity-50 opacity-0 pointer-events-none ">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl max-h-screen">
    <!-- Encabezado del Modal -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-shopping-cart mr-1 text-green-600"></i>Registrar Nueva Venta
      </h3>
      <button id="btnCerrarModalNuevaVenta" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414 1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>

    <!-- Contenido del Modal -->
    <div class="px-8 ">
      <form id="ventaForm" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
        <!-- Sección Datos Generales -->
        <div>
          <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Datos Generales</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
              <label for="fecha_venta_modal" class="block text-sm font-medium text-gray-700">
                Fecha de Venta <span class="text-red-500">*</span>
              </label>
              <input
                type="date"
                id="fecha_venta_modal"
                name="fecha_venta"
                class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
              <div id="error-fecha_venta_modal" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            <div>
              <label for="idmoneda_general" class="form-label">Moneda General <span class="text-red-500">*</span></label>
              <select id="idmoneda_general" name="idmoneda_general" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <option value="">Seleccione...</option>
              </select>
              <div id="error-idmoneda_general-vacio" class="text-red-500 text-sm mt-1 hidden">

              </div>
              <div id="error-idmoneda_general-invalida" class="text-red-500 text-sm mt-1 hidden">

              </div>
            </div>
          </div>
        </div>

        <!-- Sección Cliente -->
        <div class="mb-4">
          <label for="buscar_cliente_modal" class="form-label">Buscar Cliente <span class="text-red-500">*</span></label>
          <div class="flex gap-4">
            <input type="text" id="inputCriterioClienteModal" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Identificación...">
            <button type="button" id="btnBuscarClienteModal" class="btn-success px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base">Buscar</button>
          </div>
          <input type="hidden" id="idcliente" name="idcliente">
          <div id="cliente_seleccionado_info_modal" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
          <div id="listaResultadosClienteModal" class="mt-2 border border-gray-300 rounded-md max-h-10 overflow-y-auto hidden"></div>
        </div>
        <button type="button" id="abrirModalCliente" data-post="POST" data-form="clienteForm" data-url="clientes/createCliente" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-blue-700 transition text-base font-medium">
          <i class="fas fa-user-plus mr-2"></i>Registrar Nuevo Cliente
        </button>

        <!-- Sección Detalle de la Venta -->
        <div>
          <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Detalle de la Venta</h4>
          <div class="flex flex-col sm:flex-row items-end gap-3 mb-4">
            <div class="flex-grow w-full sm:w-auto">
              <label for="select_producto_agregar_modal" class="form-label">Agregar Producto <span class="text-red-500">*</span></label>
              <select id="select_producto_agregar_modal" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">Seleccione un producto...</option>
              </select>
            </div>
            <button type="button" id="btnAgregarProductoDetalleModal" class="btn-primary-solid w-full sm:w-auto">
              <i class="fas fa-plus mr-2"></i>Agregar al Detalle
            </button>
          </div>
          <div class="overflow-x-auto border border-gray-200 rounded-md">
            <table id="detalleVentaTable" class="w-full text-xs">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Producto</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Descripción</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Cantidad</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Precio U.</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Subtotal</th>
                  <th class="px-3 py-2 text-center font-medium text-gray-600">Acción</th>
                </tr>
              </thead>
              <tbody id="detalleVentaBody" class="divide-y divide-gray-200">
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
              <input type="hidden" id="subtotal_general" name="subtotal_general">
            </div>
            <div>
              <label for="descuento_porcentaje_general" class="block text-gray-700 font-medium mb-1">Descuento (%)</label>
              <input type="number" id="descuento_porcentaje_general" name="descuento_porcentaje_general" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" value="0" min="0" max="100" step="0.01">
            </div>
            <div>
              <label for="monto_descuento_general" class="block text-gray-700 font-medium mb-1">Monto Descuento</label>
              <input type="text" id="monto_descuento_general" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" readonly>


            </div>
          </div>
          <div class="mb-4 bg-gray-100 p-3 rounded-md">
            <label for="total_general_display_modal" class="block text-xs font-medium text-gray-500 uppercase mb-0.5">Total General</label>
            <input type="text" id="total_general_display_modal" class="w-full bg-transparent text-xl font-bold text-green-600 focus:outline-none p-0 border-0" readonly>
            <input type="hidden" id="total_general" name="total_general">
          </div>
          <div>
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
           <div id="error-observaciones-formato" class="text-red-500 text-sm mt-1 hidden">
            </div>
           
          </div>
        </div>
        <div id="mensajeErrorFormVentaModal" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
      </form>
    </div>

    <!-- Pie del Modal -->
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
      <button type="button" id="cerrarModalBtn" class="btn-neutral px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="registrarVentaBtn" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
        <i class="fas fa-save mr-2"></i> Guardar Venta
      </button>
    </div>
  </div>
</div>

<!-- Modal Cliente-->
<div id="clienteModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
    <!-- Encabezado -->
    <div class="px-4 py-4 border-b flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-800">Registrar cliente</h3>

     <button id="btnCerrarModalCliente" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414 1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>

    <!-- Formulario -->
    <form id="clienteForm" class="px-4 py-4">
      <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
        <div class="">
          <input type="hidden" id="idcliente" name="idcliente" value="">
          <label for="cedula" class="block  font-medium mb-2">Cédula</label>
          <input type="text" id="cedula" name="cedula"
            class="w-full border rounded-lg px-4 py-2 text-lg  focus:ring-2 focus:ring-green-400">
          <div id="error-cedula-vacio" class="text-red-500 text-sm mt-1 hidden">

          </div>
          <div id="error-cedula-formato" class="text-red-500 text-sm mt-1 hidden">

          </div>
        </div>
        <div class="">
          <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre</label>
          <input type="text" id="nombre" name="nombre"
            class="w-full border rounded-lg px-4 py-2 text-lg  focus:ring-2 focus:ring-green-400">
          <div id="error-nombre-vacio" class="text-red-500 text-sm mt-1 hidden">

          </div>
          <div id="error-nombre-formato" class="text-red-500 text-sm mt-1 hidden">

          </div>
        </div>
        <div class="">
          <label for="apellido" class="block text-gray-700 font-medium mb-2">Apellido</label>
          <input type="text" id="apellido" name="apellido"
            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
          <div id="error-apellido-vacio" class="text-red-500 text-sm mt-1 hidden">

          </div>
          <div id="error-apellido-formato" class="text-red-500 text-sm mt-1 hidden">

          </div>
        </div>
        <div class="">
          <label for="telefono_principal" class="block text-gray-700 font-medium mb-2">Teléfono Principal</label>
          <input type="text" id="telefono_principal" name="telefono_principal"
            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
          <div id="error-telefono_principal-vacio" class="text-red-500 text-sm mt-1 hidden">

          </div>
          <div id="error-telefono_principal-formato" class="text-red-500 text-sm mt-1 hidden">

          </div>
        </div>
        <div class="flex-1 min-w-[100%]">
          <label for="estatus" class="block text-gray-700 font-medium mb-2">Estatus</label>
          <select id="estatus" name="estatus"
            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
          <div id="error-estatus-vacio" class="text-red-500 text-sm mt-1 hidden">

          </div>
        </div>
        <div class="">
          <label for="direccion" class="block text-gray-700 font-medium mb-2">Dirección</label>
          <input type="text" id="direccion" name="direccion"
            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
          <div id="error-direccion-vacio" class="text-red-500 text-sm mt-1 hidden">

          </div>
          <div id="error-direccion-formato" class="text-red-500 text-sm mt-1 hidden">

          </div>
        </div>
      </div>
      <div class="grid-flow-row mt-4">
        <label for="observacionesCliente" class="block text-gray-700 font-medium mb-2">Observaciones</label>
        <input type="text" id="observacionesCliente" name="observacionesCliente"
          class="w-full border rounded-lg px-4 py-2 text-lg  focus:ring-2 focus:ring-green-400">
        <div id="error-observacionesCliente-formato" class="text-red-500 text-sm mt-1 hidden">
 <div id="error-observacionesCliente-vacio" class="text-red-500 text-sm mt-1 hidden">
        </div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mt-4">
        <div class="">
          <button type="button" id="cerrarModalClienteBtn"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
            Cancelar
          </button>
        </div>
        <div class="flex justify-end">
          <button type="button" id="registrarClienteBtn"
            class="px-4 ml-10 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-lg">
            Registrar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php footerAdmin($data); ?>