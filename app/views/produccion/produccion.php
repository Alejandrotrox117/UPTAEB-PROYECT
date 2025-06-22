<?php headerAdmin($data); ?>
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
  
  <!-- HEADER PRINCIPAL -->
  <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
    <div>
      <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Control de Producción</h1>
      <p class="text-green-600 text-base md:text-lg">Gestión diaria de procesos y lotes</p>
    </div>
    <div class="text-right">
      <p class="text-sm text-gray-500">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</p>
      <p class="text-xs text-gray-400" id="fechaActual"></p>
    </div>
  </div>

  <!-- PANEL DE MÉTRICAS -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <!-- Empleados Trabajando -->
    <div class="bg-white rounded-xl shadow-md p-6 flex items-center space-x-4">
      <div class="bg-green-100 p-3 rounded-full">
        <i class="fas fa-users text-green-600 text-2xl"></i>
      </div>
      <div>
        <div class="text-gray-500 text-sm font-medium">Empleados Activos</div>
        <div id="empleados-trabajando" class="text-3xl font-bold text-gray-900">0</div>
        <div class="text-xs text-green-600">trabajando hoy</div>
      </div>
    </div>

    <!-- Kg Clasificados -->
    <div class="bg-white rounded-xl shadow-md p-6 flex items-center space-x-4">
      <div class="bg-blue-100 p-3 rounded-full">
        <i class="fas fa-balance-scale text-blue-600 text-2xl"></i>
      </div>
      <div>
        <div class="text-gray-500 text-sm font-medium">Material Clasificado</div>
        <div id="kg-clasificados" class="text-3xl font-bold text-gray-900">0</div>
        <div class="text-xs text-blue-600">kg hoy</div>
      </div>
    </div>

    <!-- Pacas Producidas -->
    <div class="bg-white rounded-xl shadow-md p-6 flex items-center space-x-4">
      <div class="bg-yellow-100 p-3 rounded-full">
        <i class="fas fa-cube text-yellow-600 text-2xl"></i>
      </div>
      <div>
        <div class="text-gray-500 text-sm font-medium">Pacas Producidas</div>
        <div id="pacas-producidas" class="text-3xl font-bold text-gray-900">0</div>
        <div class="text-xs text-yellow-600">unidades hoy</div>
      </div>
    </div>

    <!-- Lotes Activos -->
    <div class="bg-white rounded-xl shadow-md p-6 flex items-center space-x-4">
      <div class="bg-purple-100 p-3 rounded-full">
        <i class="fas fa-layer-group text-purple-600 text-2xl"></i>
      </div>
      <div>
        <div class="text-gray-500 text-sm font-medium">Lotes Activos</div>
        <div id="lotes-activos" class="text-3xl font-bold text-gray-900">0</div>
        <div class="text-xs text-purple-600">en proceso</div>
      </div>
    </div>
  </div>

  <!-- GESTIÓN DE LOTES -->
  <div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-800">
        <i class="fas fa-layer-group mr-2 text-green-600"></i>Lotes de Producción
      </h2>
      <button id="btnNuevoLote" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-semibold shadow-md transition">
        <i class="fas fa-plus mr-2"></i>Nuevo Lote
      </button>
    </div>
    
    <div class="overflow-x-auto">
      <table id="tablaLotesActivos" class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-gray-600 text-xs uppercase tracking-wider">
            <th class="px-4 py-3 text-left">N° Lote</th>
            <th class="px-4 py-3 text-left">Supervisor</th>
            <th class="px-4 py-3 text-left">Meta Total</th>
            <th class="px-4 py-3 text-left">Producido</th>
            <th class="px-4 py-3 text-left">Avance</th>
            <th class="px-4 py-3 text-left">Estado</th>
            <th class="px-4 py-3 text-left">Fechas</th>
            <th class="px-4 py-3 text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <!-- Datos inyectados por JS -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- REGISTRO DIARIO DE TRABAJO -->
  <div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">
      <i class="fas fa-clipboard-list mr-2 text-blue-600"></i>Registro Diario de Trabajo
    </h2>
    
    <!-- Formulario de Registro -->
    <form id="formRegistroDiario" class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
        <!-- Lote -->
        <div>
          <label for="selectLote" class="block text-sm font-medium text-gray-700 mb-1">
            Lote <span class="text-red-500">*</span>
          </label>
          <select id="selectLote" name="lote" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
            <option value="">Seleccione lote...</option>
          </select>
        </div>

        <!-- Proceso -->
        <div>
          <label for="selectProceso" class="block text-sm font-medium text-gray-700 mb-1">
            Proceso <span class="text-red-500">*</span>
          </label>
          <select id="selectProceso" name="proceso" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
            <option value="">Seleccione proceso...</option>
          </select>
        </div>

        <!-- Empleado -->
        <div>
          <label for="selectEmpleado" class="block text-sm font-medium text-gray-700 mb-1">
            Empleado <span class="text-red-500">*</span>
          </label>
          <select id="selectEmpleado" name="empleado" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
            <option value="">Seleccione empleado...</option>
          </select>
        </div>

        <!-- Producto/Material -->
        <div>
          <label for="selectProducto" class="block text-sm font-medium text-gray-700 mb-1">
            Material <span class="text-red-500">*</span>
          </label>
          <select id="selectProducto" name="producto" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
            <option value="">Seleccione material...</option>
          </select>
        </div>

        <!-- Cantidad Asignada -->
        <div>
          <label for="cantidadAsignada" class="block text-sm font-medium text-gray-700 mb-1">
            Cantidad Asignada <span class="text-red-500">*</span>
          </label>
          <input type="number" id="cantidadAsignada" name="cantidad_asignada" min="0" step="0.01" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
        </div>

        <!-- Cantidad Producida -->
        <div>
          <label for="cantidadProducida" class="block text-sm font-medium text-gray-700 mb-1">
            Cantidad Producida
          </label>
          <input type="number" id="cantidadProducida" name="cantidad_producida" min="0" step="0.01" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
        </div>

        <!-- Unidad de Medida -->
        <div>
          <label for="unidadMedida" class="block text-sm font-medium text-gray-700 mb-1">
            Unidad
          </label>
          <input type="text" id="unidadMedida" name="unidad" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-600" readonly>
        </div>

        <!-- Estado -->
        <div>
          <label for="estadoDetalle" class="block text-sm font-medium text-gray-700 mb-1">
            Estado <span class="text-red-500">*</span>
          </label>
          <select id="estadoDetalle" name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
            <option value="PENDIENTE">Pendiente</option>
            <option value="EN_PROCESO">En Proceso</option>
            <option value="COMPLETADO">Completado</option>
          </select>
        </div>
      </div>

      <!-- Observaciones y Fechas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">
            Observaciones
          </label>
          <textarea id="observaciones" name="observaciones" rows="2" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
        </div>
        <div>
          <label for="fechaInicioProceso" class="block text-sm font-medium text-gray-700 mb-1">
            Fecha/Hora Inicio
          </label>
          <input type="datetime-local" id="fechaInicioProceso" name="fecha_inicio" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
        </div>
        <div>
          <label for="fechaFinProceso" class="block text-sm font-medium text-gray-700 mb-1">
            Fecha/Hora Fin
          </label>
          <input type="datetime-local" id="fechaFinProceso" name="fecha_fin" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
        </div>
      </div>

      <!-- Botones -->
      <div class="flex justify-end space-x-3">
        <button type="button" id="btnLimpiarFormulario" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
          <i class="fas fa-eraser mr-2"></i>Limpiar
        </button>
        <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-semibold shadow-md">
          <i class="fas fa-save mr-2"></i>Registrar Trabajo
        </button>
      </div>
    </form>

    <!-- Tabla de Registros Diarios -->
    <div class="border-t pt-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Registros del Día</h3>
      <div class="overflow-x-auto">
        <table id="tablaRegistrosDiarios" class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-gray-600 text-xs uppercase tracking-wider">
              <th class="px-4 py-3 text-left">Empleado</th>
              <th class="px-4 py-3 text-left">Proceso</th>
              <th class="px-4 py-3 text-left">Material</th>
              <th class="px-4 py-3 text-left">Asignado</th>
              <th class="px-4 py-3 text-left">Producido</th>
              <th class="px-4 py-3 text-left">Unidad</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-left">Tiempo</th>
              <th class="px-4 py-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <!-- Datos inyectados por JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- CONTROL DE CALIDAD -->
  <div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">
      <i class="fas fa-clipboard-check mr-2 text-yellow-600"></i>Control de Calidad
    </h2>
    
    <form id="formControlCalidad" class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label for="inspectorCalidad" class="block text-sm font-medium text-gray-700 mb-1">
            Inspector <span class="text-red-500">*</span>
          </label>
          <select id="inspectorCalidad" name="inspector" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" required>
            <option value="">Seleccione inspector...</option>
          </select>
        </div>

        <div>
          <label for="procesoCalidad" class="block text-sm font-medium text-gray-700 mb-1">
            Proceso <span class="text-red-500">*</span>
          </label>
          <select id="procesoCalidad" name="proceso_calidad" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" required>
            <option value="">Seleccione proceso...</option>
          </select>
        </div>

        <div>
          <label for="calificacion" class="block text-sm font-medium text-gray-700 mb-1">
            Calificación (1-10) <span class="text-red-500">*</span>
          </label>
          <input type="number" id="calificacion" name="calificacion" min="1" max="10" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" required>
        </div>

        <div>
          <label for="porcentajeHumedad" class="block text-sm font-medium text-gray-700 mb-1">
            % Humedad
          </label>
          <input type="number" id="porcentajeHumedad" name="humedad" min="0" max="100" step="0.1" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="estadoCalidad" class="block text-sm font-medium text-gray-700 mb-1">
            Estado <span class="text-red-500">*</span>
          </label>
          <select id="estadoCalidad" name="estado_calidad" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" required>
            <option value="APROBADO">Aprobado</option>
            <option value="REPROCESO">Requiere Reproceso</option>
            <option value="RECHAZADO">Rechazado</option>
          </select>
        </div>

        <div>
          <label for="observacionesCalidad" class="block text-sm font-medium text-gray-700 mb-1">
            Observaciones
          </label>
          <textarea id="observacionesCalidad" name="observaciones_calidad" rows="2" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"></textarea>
        </div>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition font-semibold shadow-md">
          <i class="fas fa-check-circle mr-2"></i>Registrar Control
        </button>
      </div>
    </form>
  </div>

  <!-- REPORTES Y ANÁLISIS -->
  <div class="bg-white rounded-xl shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6">
      <i class="fas fa-chart-bar mr-2 text-purple-600"></i>Reportes y Análisis
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button id="btnReporteEmpleado" class="flex items-center justify-center px-4 py-6 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
        <div class="text-center">
          <i class="fas fa-user-chart text-blue-600 text-3xl mb-2"></i>
          <div class="text-sm font-semibold text-blue-700">Producción por Empleado</div>
        </div>
      </button>

      <button id="btnReporteLote" class="flex items-center justify-center px-4 py-6 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition">
        <div class="text-center">
          <i class="fas fa-layer-group text-green-600 text-3xl mb-2"></i>
          <div class="text-sm font-semibold text-green-700">Avance de Lotes</div>
        </div>
      </button>

      <button id="btnReporteMaterial" class="flex items-center justify-center px-4 py-6 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition">
        <div class="text-center">
          <i class="fas fa-boxes text-purple-600 text-3xl mb-2"></i>
          <div class="text-sm font-semibold text-purple-700">Consumo de Materiales</div>
        </div>
      </button>
    </div>
  </div>
</main>

<!-- MODAL PARA CREAR/EDITAR LOTE -->
<div id="modalLote" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
    
    <!-- Header del Modal -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-800">
        <i class="fas fa-layer-group mr-2 text-green-600"></i>
        <span id="tituloModalLote">Nuevo Lote de Producción</span>
      </h3>
      <button id="btnCerrarModalLote" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
        <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Contenido del Modal -->
    <form id="formLote" class="px-6 py-6 max-h-[70vh] overflow-y-auto">
      <input type="hidden" id="idProduccionModal" name="idproduccion">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Información General -->
        <div class="space-y-4">
          <h4 class="text-lg font-semibold text-gray-700 border-b pb-2">Información General</h4>
          
          <div>
            <label for="numeroLote" class="block text-sm font-medium text-gray-700 mb-1">
              Número de Lote <span class="text-red-500">*</span>
            </label>
            <input type="text" id="numeroLote" name="numero_lote" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
          </div>

          <div>
            <label for="supervisorLote" class="block text-sm font-medium text-gray-700 mb-1">
              Supervisor <span class="text-red-500">*</span>
            </label>
            <select id="supervisorLote" name="supervisor" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
              <option value="">Seleccione supervisor...</option>
            </select>
          </div>

          <div>
            <label for="metaTotal" class="block text-sm font-medium text-gray-700 mb-1">
              Meta Total <span class="text-red-500">*</span>
            </label>
            <input type="number" id="metaTotal" name="meta_total" min="1" step="0.01" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
          </div>

          <div>
            <label for="estadoLote" class="block text-sm font-medium text-gray-700 mb-1">
              Estado <span class="text-red-500">*</span>
            </label>
            <select id="estadoLote" name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
              <option value="BORRADOR">Borrador</option>
              <option value="EN_PROCESO">En Proceso</option>
              <option value="BLOQUEADO">Bloqueado</option>
              <option value="FINALIZADA">Finalizada</option>
            </select>
          </div>
        </div>

        <!-- Fechas y Observaciones -->
        <div class="space-y-4">
          <h4 class="text-lg font-semibold text-gray-700 border-b pb-2">Fechas y Detalles</h4>
          
          <div>
            <label for="fechaInicio" class="block text-sm font-medium text-gray-700 mb-1">
              Fecha de Inicio <span class="text-red-500">*</span>
            </label>
            <input type="date" id="fechaInicio" name="fecha_inicio" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
          </div>

          <div>
            <label for="fechaEstimadaFin" class="block text-sm font-medium text-gray-700 mb-1">
              Fecha Estimada de Fin
            </label>
            <input type="date" id="fechaEstimadaFin" name="fecha_estimada_fin" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
          </div>

          <div>
            <label for="fechaFinal" class="block text-sm font-medium text-gray-700 mb-1">
              Fecha Final
            </label>
            <input type="date" id="fechaFinal" name="fecha_final" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
          </div>

          <div>
            <label for="observacionesLote" class="block text-sm font-medium text-gray-700 mb-1">
              Observaciones
            </label>
            <textarea id="observacionesLote" name="observaciones" rows="4" 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
          </div>
        </div>
      </div>
    </form>

    <!-- Footer del Modal -->
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
      <button type="button" id="btnCancelarLote" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
        Cancelar
      </button>
      <button type="button" id="btnGuardarLote" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-semibold shadow-md">
        <i class="fas fa-save mr-2"></i>Guardar Lote
      </button>
    </div>
  </div>
</div>

<div id="permisosUsuario" data-permisos='<?= json_encode($data['permisos'] ?? []) ?>' style="display:none"></div>

<script>
// Mostrar fecha actual
document.getElementById('fechaActual').textContent = new Date().toLocaleDateString('es-ES', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric'
});
</script>

<?php footerAdmin($data); ?>