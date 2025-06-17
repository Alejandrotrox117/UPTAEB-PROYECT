
<?php headerAdmin($data); ?>
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
  <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
     <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
  </div>

  <div class="mt-0 sm:mt-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?= $data['page_name'] ?></h1>
    <p class="text-green-600 text-base md:text-lg">Gestión de procesos de producción</p>
  </div>

  <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
    <div class="flex justify-between items-center mb-6">
      <button id="abrirModalProduccion" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
        <i class="fas fa-industry mr-2"></i>Registrar Producción
      </button>
    </div>

    <div class="overflow-x-auto w-full relative">
      <table id="TablaProduccion" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 800px;">
        <thead>
          <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
            <th class="px-3 py-3 text-left">ID Producción</th>
            <th class="px-3 py-3 text-left">Producto</th>
            <th class="px-3 py-3 text-left">Empleado</th>
            <th class="px-3 py-3 text-left">Cantidad</th>
            <th class="px-3 py-3 text-left">Fecha Inicio</th>
            <th class="px-3 py-3 text-left">Fecha Fin</th>
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

<!-- Modal para Registrar/Editar Producción -->
<div id="produccionModal" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">

    <div class="bg-gray-50 px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-xl md:text-2xl font-bold text-gray-800">
        <i class="fas fa-industry mr-1 text-green-600"></i>Registrar Producción
      </h3>
      <button id="btnCerrarModalProduccion" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <form id="formRegistrarProduccion" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
      <input type="hidden" id="idproduccion" name="idproduccion">
      <input type="hidden" id="idproducto" name="idproducto">
      <input type="hidden" id="detalleProduccionJson" name="detalleProduccionJson">

      <div>
        <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Datos Generales</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
          <div>
            <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" required>
          </div>
          <div>
            <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
          </div>
        </div>
        <div class="mt-4 mb-4">
          <label for="select_producto" class="block text-sm font-medium text-gray-700 mb-1">Producto <span class="text-red-500">*</span></label>
          <select id="select_producto" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Cargando productos...</option>
          </select>
        </div>
        <div class="mb-4">
          <label for="cantidad_a_realizar" class="block text-sm font-medium text-gray-700 mb-1">Meta a Producir <span class="text-red-500">*</span></label>
          <input type="number" id="cantidad_a_realizar" name="cantidad_a_realizar" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" min="1" required>
        </div>
      </div>

      <!-- Sección Empleado -->
      <div class="mt-4 mb-4">
        <label for="inputCriterioEmpleado" class="block text-sm font-medium text-gray-700 mb-1">Buscar Empleado <span class="text-red-500">*</span></label>
        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end mt-1">
          <input type="text" id="inputCriterioEmpleado" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Cédula...">
          <button type="button" id="btnBuscarEmpleado" class="bg-green-500 hover:bg-green-600 text-white rounded-lg transition text-sm md:text-base px-4 py-3 w-full sm:w-auto">Buscar</button>
        </div>
        <input type="hidden" id="idempleado_seleccionado" name="idempleado_seleccionado">
        <div id="empleado_seleccionado_info" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
        <div id="listaResultadosEmpleado" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden"></div>
      </div>
      <button id="registrarEmpleado" type="button" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base mb-6">
        <i class="fas fa-user-plus mr-2"></i>Registrar Nuevo Empleado
      </button>

      <!-- Tabs -->
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
          <label for="inputCriterioEmpleadoTarea" class="block text-sm font-medium text-gray-700 mb-1">Buscar Empleado</label>
          <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end mt-1">
            <input type="text" id="inputCriterioEmpleadoTarea" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nombre, Apellido o Cédula...">
            <button type="button" id="btnBuscarEmpleadoTarea" class="bg-green-500 hover:bg-green-600 text-white rounded-lg transition text-sm md:text-base px-4 py-3 w-full sm:w-auto">Buscar</button>
          </div>
          <input type="hidden" id="idempleado_seleccionado_tarea">
          <div id="empleado_seleccionado_info_tarea" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 text-xs hidden"></div>
          <div id="listaResultadosEmpleadoTarea" class="mt-2 border border-gray-300 rounded-md max-h-20 overflow-y-auto hidden"></div>
        </div>
        <button type="button" id="btnAgregarTarea" class="bg-green-500 hover:bg-green-600 text-white rounded-lg px-4 py-3 text-sm md:text-base w-full sm:w-auto">
          <i class="fas fa-plus mr-2"></i>Asignar Tarea
        </button>
        <div class="mt-6">
          <table id="tablaTareas" class="w-full text-xs sm:text-sm">
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
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
          <div class="flex items-center p-6 bg-white rounded-2xl shadow-md space-x-4">
            <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
              <i class="fa-solid fa-check-circle text-green-500 text-3xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Ya Realizado</p>
              <p id="producido" class="text-gray-900 text-2xl font-bold">0</p>
            </div>
          </div>
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
        <div class="w-full bg-gray-200 h-4 rounded-full overflow-hidden mt-4">
          <div id="porcentaje-progreso" class="bg-green-500 h-4" style="width: 0%"></div>
        </div>
        <p class="text-center mt-2" id="porcentaje-progreso-texto">0%</p>
      </div>

      <div id="tab-detalle" class="tab-content block">
        <div>
          <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Detalle de Producción</h4>
          <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 mb-4">
            <div class="flex-grow w-full sm:w-auto">
              <label for="select_producto_agregar_detalle" class="block text-sm font-medium text-gray-700 mb-1">Agregar Insumo <span class="text-red-500">*</span></label>
              <select id="select_producto_agregar_detalle" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">Seleccione un insumo...</option>
              </select>
            </div>
            <button type="button" id="btnAgregarProductoDetalleProduccion" class="bg-green-500 hover:bg-green-600 text-white rounded-lg px-4 py-3 text-sm md:text-base w-full sm:w-auto">
              <i class="fas fa-plus mr-2"></i>Agregar al Detalle
            </button>
          </div>
          <div class="overflow-x-auto border border-gray-200 rounded-md">
            <table id="tablaDetalleProduccion" class="w-full text-xs sm:text-sm">
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
            <p id="noDetallesMensajeProduccion" class="text-center text-gray-500 py-4 text-xs hidden">No hay insumos en el detalle.</p>
          </div>
        </div>
        <div class="mt-6">
          <h4 class="text-base font-semibold text-gray-700 mb-3 border-b pb-2">Estado y Observaciones</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="estado" class="block text-gray-700 font-medium mb-1">Estado <span class="text-red-500">*</span></label>
              <select id="estado" name="estado" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="borrador">Borrador</option>
                <option value="en_clasificacion">En Clasificación</option>
                <option value="empacando">Empacando</option>
                <option value="realizado">Realizado</option>
              </select>
            </div>
            <div>
              <label for="observaciones_produccion" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
              <textarea id="observaciones_produccion" name="observaciones_produccion" rows="3" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
          </div>
        </div>
        <div id="mensajeErrorFormProduccion" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
      </div>
    </form>

    <!-- Pie del Modal (Acciones) -->
    <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
      <button type="button" id="btnCancelarProduccion" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnGuardarProduccion" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
        <i class="fas fa-save mr-2"></i>Registrar Producción
      </button>
    </div>
  </div>
</div>

<!-- Modal para Ver Producción -->
<div id="detalleModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-30 z-50 opacity-0 pointer-events-none transition-opacity duration-300 p-4">
  <div class="bg-white rounded-xl shadow-lg w-full sm:w-11/12 max-w-4xl max-h-[95vh]">
    <div class="flex justify-between items-center px-4 md:px-6 py-4 border-b">
      <h3 class="text-lg md:text-xl font-bold text-gray-800">
        <i class="fas fa-eye mr-2 text-green-600"></i>Detalle de la Producción
      </h3>
      <button id="cerrarDetalleProduccion" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
      <div id="contenidoDetalleProduccion"></div>
      <div class="flex justify-end pt-4 md:pt-6 border-t border-gray-200">
        <button type="button" id="cerrarDetalleProduccion2" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 text-sm md:text-base">
          <i class="fas fa-times mr-1 md:mr-2"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<div id="permisosUsuario" data-permisos='<?= json_encode($data['permisos'] ?? []) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>