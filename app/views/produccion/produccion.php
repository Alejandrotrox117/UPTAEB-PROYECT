<?php headerAdmin($data);
$permisos = $data['permisos'] ?? []; ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Módulo de Producción</h2>
    <input type="text" placeholder="Buscar producción..." class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>
  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Producciones</h1>
    <p class="text-green-500 text-lg">Gestión de procesos de producción</p>
    <div class="flex justify-between bg-white p-6 rounded-2xl shadow-md space-x-8 mt-5">
      <!-- Tarjeta 1 -->
      <div id="card-total" class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
          <i class="fa-solid fa-weight-scale text-green-500 text-3xl"></i>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Cantidad de Producciones</p>
          <p id="total-producciones" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
      </div>

      <!-- Separador -->
      <div class="border-l border-gray-300 h-16"></div>

      <!-- Tarjeta 2 -->
      <div id="card-clasificacion" class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
          <i class="fa-solid fa-box text-green-500 text-3xl"></i>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Cantidad en Clasificación</p>
          <p id="en-clasificacion" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
      </div>

      <!-- Separador -->
      <div class="border-l border-gray-300 h-16"></div>

      <!-- Tarjeta 3 -->
      <div id="card-finalizadas" class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
          <i class="fa-solid fa-file text-green-500 text-3xl"></i>
        </div>
        <div>
          <p class="text-gray-500 text-sm">Cantidad Producidas</p>
          <p id="producidas" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
      </div>
      <!-- Separador -->
      <div class="border-l border-gray-300 h-16"></div>
    </div>
    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button id="abrirModalProduccion" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar Producción
        </button>
      </div>
      <table id="TablaProduccion" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2 px-3">ID Producción</th>
            <th class="py-2 px-3">Producto</th>
            <th class="py-2 px-3">Empleado</th>
            <th class="py-2 px-3">Cantidad</th>
            <th class="py-2 px-3">Fecha Inicio</th>
            <th class="py-2 px-3">Fecha Fin</th>
            <th class="py-2 px-3">Estado</th>
            <th class="py-2 px-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-gray-900">

        </tbody>
      </table>
    </div>
  </div>
</main>


<div id="produccionModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl">

    <!-- Encabezado del Modal -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-industry mr-1 text-green-600"></i>Registrar Producción
      </h3>
      <button id="btnCerrarModalProduccion" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>


    <form id="formRegistrarProduccion" class="px-8 py-6 max-h-[70vh] overflow-y-auto">

      <input type="hidden" id="idproduccion" name="idproduccion">
      <input type="hidden" id="idproducto" name="idproducto">
      <input type="hidden" id="detalleProduccionJson" name="detalleProduccionJson">


      <div>
        <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Datos Generales</h4>
        <div class="mb-4">
          <div>
            <label for="fecha_inicio" class="form-label">Fecha Inicio <span class="text-red-500">*</span></label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" class="w-1/3 border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <label for="fecha_fin" class="form-label">Fecha Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" class="w-1/3 mt-5 border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
          </div>

        </div>


        <div class="mb-4">
          <label for="select_producto" class="form-label">Producto <span class="text-red-500">*</span></label>
          <select id="select_producto" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Cargando productos...</option>
          </select>
        </div>


        <div class="mb-4">
          <label for="cantidad_a_realizar" class="form-label">Meta a Producir <span class="text-red-500">*</span></label>
          <input type="number" id="cantidad_a_realizar" name="cantidad_a_realizar" class="w-1/3 border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" min="1" required>
        </div>

      </div>

      <!-- Sección Empleado -->
      <div class="mb-4 grid-item">
       
        <div id="empleado" class="flex gap-4 ml-2">
           <label for="buscar_empleado" class="form-label">Buscar Empleado <span class="text-red-500">*</span></label>
          <input type="text" id="inputCriterioEmpleado" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Cédula...">
          <button type="button" id="btnBuscarEmpleado" class="btn-success px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base">Buscar</button>
        </div>
        <input type="hidden"  id="idempleado_seleccionado" name="idempleado_seleccionado">
        <div id="empleado_seleccionado_info" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
        <div id="listaResultadosEmpleado" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden"></div>
      </div>


      <button id="registrarEmpleado" type="button" onclick="abrirModalEmpleado('Registrar Empleado', 'empleadoForm', 'POST', 'empleados/createEmpleado')" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-700 transition text-base font-medium mb-6">
        <i class="fas fa-user-plus mr-2"></i>Registrar Nuevo Empleado
      </button>

      <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="tabs">
          <li class="mr-2">

            <button type="button" class="tab-button inline-block p-4 border-b-2 rounded-t-lg active border-green-500 text-green-600" data-tab="detalle">
              Detalle de Producción
            </button>
          </li>
          <li class="mr-2">
            <button type="button" class="tab-button inline-block p-4 border-b-2 rounded-t-lg border-transparent hover:text-gray-600 hover:border-gray-300" data-tab="tareas">
              Tareas Asignadas
            </button>
          </li>
        </ul>
      </div>

      <div id="tab-tareas" class="tab-content hidden">
        <h4 class="font-semibold mt-6 mb-2">Tareas Asignadas</h4>
        <div class="mb-4">
          <label for="buscar_empleado" class="form-label">Buscar Empleado</label>
          <div class="flex gap-4 ml-2">
            <input type="text" id="inputCriterioEmpleadoTarea" class="w-full border rounded-md px-4 py-2" placeholder="Nombre, Apellido o Cédula...">
            <button type="button" id="btnBuscarEmpleadoTarea" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Buscar</button>
          </div>
          <input type="hidden" id="idempleado_seleccionado_tarea">
          <div id="empleado_seleccionado_info_tarea" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
          <div id="listaResultadosEmpleadoTarea" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden"></div>
        </div>

        <button type="button" id="btnAgregarTarea" class="btn-primary-solid w-full sm:w-auto">
          <i class="fas fa-plus mr-2"></i>Asignar Tarea
        </button>
        <br><br><br>
        <hr>

        <!-- Tabla de tareas -->
        <table id="tablaTareas" class="w-full text-xs">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 text-left font-medium text-gray-600">Empleado</th>
              <th class="px-3 py-2 text-left font-medium text-gray-600">Asignado</th>
              <th class="px-3 py-2 text-left font-medium text-gray-600">Realizado</th>
              <th class="px-3 py-2 text-left font-medium text-gray-600">Estado</th>
              <th class="px-3 py-2 text-left font-medium text-gray-600">Acciones</th>
            </tr>
          </thead>
          <tbody id="detalleTareasBody"></tbody>
        </table>
        <!-- Campos de progreso -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
      
         

          <!-- Ya Realizado -->
          <div class="flex items-center p-6 bg-white rounded-2xl shadow-md space-x-4">
            <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
              <i class="fa-solid fa-check-circle text-green-500 text-3xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Ya Realizado</p>
              <p id="producido" class="text-gray-900 text-2xl font-bold">0</p>
            </div>
          </div>

          <!-- Faltante -->
          <div class="flex items-center p-6 bg-white rounded-2xl shadow-md space-x-4">
            <div class="flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full">
              <i class="fa-solid fa-hourglass-half text-yellow-500 text-3xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Faltante</p>
              <p id="faltante" class="text-gray-900 text-2xl font-bold">0</p>
            </div>
          </div>
        </div>


        <!-- Barra de progreso -->
        <div class="w-full bg-gray-200 h-4 rounded-full overflow-hidden mt-4">
          <div id="porcentaje-progreso" class="bg-green-500 h-4" style="width: 0%"></div>
        </div>
        <p class="text-center mt-2" id="porcentaje-progreso-texto">0%</p>
      </div>


      <div id="tab-detalle" class="tab-content block ">
        <div>
          <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Detalle de Producción</h4>
          <div class="flex flex-col sm:flex-row items-end gap-3 mb-4">
            <div class="flex-grow w-full sm:w-auto">
              <label for="select_producto_agregar_detalle" class="form-label">Agregar Producto <span class="text-red-500">*</span></label>
              <select id="select_producto_agregar_detalle" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">Seleccione un producto...</option>
              </select>
            </div>
            <button type="button" id="btnAgregarProductoDetalleProduccion" class="btn-primary-solid w-full sm:w-auto">
              <i class="fas fa-plus mr-2"></i>Agregar al Detalle
            </button>
          </div>
          <div class="overflow-x-auto border border-gray-200 rounded-md">
            <table id="tablaDetalleProduccion" class="w-full text-xs">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Producto</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Cantidad Requerida</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Cantidad Utilizada</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Observaciones</th>
                  <th class="px-3 py-2 text-center font-medium text-gray-600">Acción</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaDetalleProduccion" class="divide-y divide-gray-200">

              </tbody>
            </table>
            <p id="noDetallesMensajeProduccion" class="text-center text-gray-500 py-4 text-xs hidden">No hay productos en el detalle.</p>
          </div>
        </div>



        <div>
          <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Estado y Observaciones</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="estado" class="block text-gray-700 font-medium mb-1">Estado <span class="text-red-500">*</span></label>
              <select id="estado" name="estado" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="borrador">Borrador</option>
                <option value="en_clasificacion">En Clasificación</option>
                <option value="empacando">Empacando</option>
                <option value="realizado">Realizado</option>
              </select>
            </div>
            <div>
              <label for="observaciones_produccion" class="form-label">Observaciones</label>
              <textarea id="observaciones_produccion" name="observaciones_produccion" rows="3" class="w-full border rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
          </div>
        </div>

        <!-- Mensaje de error -->
        <div id="mensajeErrorFormProduccion" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
      </div>





    </form>

    <!-- Pie del Modal (Acciones) -->
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
      <button type="button" id="btnCancelarProduccion" class="btn-neutral px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnGuardarProduccion" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
        <i class="fas fa-save mr-2"></i>Registrar Producción
      </button>

    </div>

  </div>
</div>

</div>

<div id="detalleModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-30 z-50 opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white p-6 rounded-lg w-full max-w-4xl max-h-full overflow-auto">
    <h3 class="text-xl font-bold mb-4">Detalles de Producción</h3>
    <div id="contenidoDetalleProduccion"></div>
    <button id="cerrarDetalleProduccion" class="mt-4 px-4 py-2 bg-gray-300 rounded">Cerrar</button>
  </div>
</div>
<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>