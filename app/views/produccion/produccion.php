<?php headerAdmin($data); ?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Control de lotes, operarios y procesos de producción</p>
    </div>

    <!-- Pestañas de navegación -->
    <div class="bg-white mt-6 rounded-2xl shadow-lg">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap space-x-0 sm:space-x-8 px-2 sm:px-6 py-3" aria-label="Tabs">
                <button id="tab-lotes" class="tab-button active border-b-2 border-green-500 py-2 px-1 text-sm font-medium text-green-600 w-full sm:w-auto">
                    <i class="fas fa-boxes mr-2"></i>Gestión de Lotes
                </button>
                <button id="tab-procesos" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 w-full sm:w-auto">
                    <i class="fas fa-cogs mr-2"></i>Procesos
                </button>
                <button id="tab-nomina" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 w-full sm:w-auto">
                    <i class="fas fa-calculator mr-2"></i>Nómina
                </button>
                <button id="tab-configuracion" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 w-full sm:w-auto">
                    <i class="fas fa-cog mr-2"></i>Configuración
                </button>
            </nav>
        </div>

        <!-- Contenido de pestañas -->
        <div class="p-4 md:p-6">
            <!-- Pestaña Gestión de Lotes -->
            <div id="content-lotes" class="tab-content">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Lotes de Producción</h3>
                        <p class="text-gray-600 text-sm">Administra los lotes diarios de producción</p>
                    </div>
                    <button id="btnAbrirModalRegistrarLote"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                        <i class="fas fa-plus mr-2"></i> Crear Lote
                    </button>
                </div>

                <div class="overflow-x-auto w-full relative">
                    <table id="TablaLotes" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 1200px;">
                        <thead>
                            <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                                <!-- Columnas inyectadas por JS -->
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña Procesos -->
            <div id="content-procesos" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Clasificación -->
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-filter text-blue-600 text-2xl mr-3"></i>
                            <div>
                                <h4 class="text-lg font-semibold text-blue-800">Proceso de Clasificación</h4>
                                <p class="text-blue-600 text-sm">Registra material clasificado y contaminantes</p>
                            </div>
                        </div>
                        <button id="btnAbrirModalClasificacion"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-plus mr-2"></i> Registrar Clasificación
                        </button>
                    </div>

                    <!-- Empaque -->
                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-cube text-purple-600 text-2xl mr-3"></i>
                            <div>
                                <h4 class="text-lg font-semibold text-purple-800">Proceso de Empaque</h4>
                                <p class="text-purple-600 text-sm">Crea pacas desde material clasificado</p>
                            </div>
                        </div>
                        <button id="btnAbrirModalEmpaque"
                            class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-plus mr-2"></i> Registrar Empaque
                        </button>
                    </div>
                </div>

                <!-- Lista de procesos recientes -->
                <div class="mt-8">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Procesos de Hoy</h4>
                    <div class="overflow-x-auto">
                        <table id="TablaProcesos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%;">
                            <thead>
                                <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                                    <!-- Columnas inyectadas por JS -->
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pestaña Nómina -->
            <div id="content-nomina" class="tab-content hidden">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Gestión de Nómina</h3>
                        <p class="text-gray-600 text-sm">Registra producción diaria y calcula salarios</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="btnCalcularNomina"
                            class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-medium text-sm">
                            <i class="fas fa-calculator mr-2"></i> Calcular Nómina
                        </button>
                        <button id="btnRegistrarProduccionDiaria"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium text-sm">
                            <i class="fas fa-clipboard-list mr-2"></i> Registrar Producción
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto w-full relative">
                    <table id="TablaNomina" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 1000px;">
                        <thead>
                            <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                                <!-- Columnas inyectadas por JS -->
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña Configuración -->
            <div id="content-configuracion" class="tab-content hidden">
                <div class="max-w-4xl">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Configuración de Producción</h3>
                    <form id="formConfiguracionProduccion" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Parámetros de Productividad -->
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                <h4 class="text-md font-semibold text-blue-800 mb-4">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Productividad
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Productividad Clasificación (kg/operario/día)
                                        </label>
                                        <input type="number" step="0.01" id="productividad_clasificacion" name="productividad_clasificacion"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="150.00">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Capacidad Máxima Planta (operarios)
                                        </label>
                                        <input type="number" id="capacidad_maxima_planta" name="capacidad_maxima_planta"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="50">
                                    </div>
                                </div>
                            </div>

                            <!-- Parámetros Salariales -->
                            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                <h4 class="text-md font-semibold text-green-800 mb-4">
                                    <i class="fas fa-dollar-sign mr-2"></i>Salarios
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Salario Base Diario ($)
                                        </label>
                                        <input type="number" step="0.01" id="salario_base" name="salario_base"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="30.00">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Beta - Bono Clasificación ($/kg)
                                        </label>
                                        <input type="number" step="0.0001" id="beta_clasificacion" name="beta_clasificacion"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="0.2500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Gamma - Bono Empaque ($/paca)
                                        </label>
                                        <input type="number" step="0.01" id="gamma_empaque" name="gamma_empaque"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="5.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Control de Calidad -->
                            <div class="bg-orange-50 p-6 rounded-lg border border-orange-200">
                                <h4 class="text-md font-semibold text-orange-800 mb-4">
                                    <i class="fas fa-shield-alt mr-2"></i>Control de Calidad
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Umbral Máximo de Error (%)
                                        </label>
                                        <input type="number" step="0.01" id="umbral_error_maximo" name="umbral_error_maximo"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="5.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Especificaciones de Pacas -->
                            <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                <h4 class="text-md font-semibold text-purple-800 mb-4">
                                    <i class="fas fa-cube mr-2"></i>Especificaciones de Pacas
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Peso Mínimo Paca (kg)
                                        </label>
                                        <input type="number" step="0.01" id="peso_minimo_paca" name="peso_minimo_paca"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="25.00">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Peso Máximo Paca (kg)
                                        </label>
                                        <input type="number" step="0.01" id="peso_maximo_paca" name="peso_maximo_paca"
                                            class="w-full border rounded-lg px-3 py-2 text-sm" value="35.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="button" id="btnCargarConfiguracion"
                                class="mr-3 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">
                                <i class="fas fa-sync-alt mr-2"></i> Recargar
                            </button>
                            <button type="submit" id="btnGuardarConfiguracion"
                                class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm font-medium">
                                <i class="fas fa-save mr-2"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODALES -->

<!-- Modal Registrar Lote -->
<div id="modalRegistrarLote"
    class="fixed inset-0 flex  items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4 ">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Crear Lote de Producción</h3>
            <button id="btnCerrarModalRegistrarLote" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarLote" class="px-4 md:px-8 py-6 overflow-auto-y max-h-[75vh]">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="lote_fecha_jornada" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Jornada <span class="text-red-500">*</span></label>
                    <input type="date" id="lote_fecha_jornada" name="fecha_jornada" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                </div>
                <div>
                    <label for="lote_volumen_estimado" class="block text-sm font-medium text-gray-700 mb-1">Volumen Estimado (kg) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" id="lote_volumen_estimado" name="volumen_estimado" placeholder="Ej: 2500.00" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                </div>
                <div>
                    <label for="lote_supervisor" class="block text-sm font-medium text-gray-700 mb-1">Supervisor <span class="text-red-500">*</span></label>
                    <select id="lote_supervisor" name="idsupervisor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccionar supervisor...</option>
                    </select>
                </div>
                <div>
                    <label for="lote_observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="lote_observaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                </div>

                <!-- Información calculada -->
                <div id="infoCalculada" class="bg-blue-50 p-4 rounded-lg border border-blue-200 hidden">
                    <h4 class="text-sm font-semibold text-blue-800 mb-2">Información Calculada</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-blue-600">Operarios Requeridos:</span>
                            <span id="operariosCalculados" class="font-semibold ml-2">-</span>
                        </div>
                        <div>
                            <span class="text-blue-600">Capacidad Máxima:</span>
                            <span id="capacidadMaxima" class="font-semibold ml-2">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrarLote" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarLote" form="formRegistrarLote" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Crear Lote
            </button>
        </div>
    </div>
</div>

<!-- Modal Asignar Operarios -->
<div id="modalAsignarOperarios"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-6xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Asignar Operarios al Lote</h3>
            <button id="btnCerrarModalAsignarOperarios" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-4 md:px-8 py-6 max-h-[calc(95vh-180px)] overflow-y-auto">
            <input type="hidden" id="idLoteAsignar">

            <!-- Información del Lote -->
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-800 mb-3">Información del Lote</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 font-medium">Número de Lote:</span>
                            <span id="infoNumeroLote" class="font-semibold ml-2 text-blue-600">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Operarios Requeridos:</span>
                            <span id="infoOperariosRequeridos" class="font-semibold ml-2 text-green-600">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Fecha Jornada:</span>
                            <span id="infoFechaJornada" class="font-semibold ml-2 text-purple-600">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área principal de asignaciones -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Panel izquierdo: Operarios disponibles -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="bg-blue-50 px-4 py-3 border-b border-blue-200">
                        <h4 class="text-md font-semibold text-blue-800 flex items-center">
                            <i class="fas fa-users mr-2"></i>
                            Operarios Disponibles
                        </h4>
                        <p class="text-xs text-blue-600 mt-1">Selecciona los operarios para asignar al lote</p>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <table id="tablaOperariosDisponibles" class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left w-12">
                                        <i class="fas fa-check text-gray-400"></i>
                                    </th>
                                    <th class="px-3 py-2 text-left">Operario</th>
                                    <th class="px-3 py-2 text-left">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="bodyOperariosDisponibles">
                                <tr>
                                    <td colspan="3" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                        <p>Cargando operarios...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Panel derecho: Operarios asignados -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="bg-green-50 px-4 py-3 border-b border-green-200">
                        <h4 class="text-md font-semibold text-green-800 flex items-center">
                            <i class="fas fa-user-check mr-2"></i>
                            Operarios Asignados
                            <span id="contadorAsignados" class="ml-2 bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full">0</span>
                        </h4>
                        <p class="text-xs text-green-600 mt-1">Configura las tareas y turnos de cada operario</p>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <div id="listaOperariosAsignados" class="p-4 space-y-3 min-h-[200px]">
                            <div class="flex flex-col items-center justify-center h-full text-gray-500 py-8">
                                <i class="fas fa-user-plus text-3xl mb-3 text-gray-300"></i>
                                <p class="text-center">No hay operarios asignados</p>
                                <p class="text-xs text-center mt-1">Selecciona operarios de la lista de la izquierda</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de debug (solo en desarrollo) -->
            <div id="debugPanel" class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4" style="display: none;">
                <h5 class="text-sm font-semibold text-yellow-800 mb-2">Debug Info</h5>
                <button onclick="debugAsignaciones()" class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded">
                    Mostrar Debug
                </button>
                <div id="debugContent" class="text-xs mt-2 bg-white p-2 rounded"></div>
            </div>
        </div>

        <!-- Footer del modal -->
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <!-- Información de progreso -->
                <div class="text-sm text-gray-600">
                    <span>Asignados: </span>
                    <span id="progresoAsignados" class="font-semibold text-green-600">0</span>
                    <span> / </span>
                    <span id="progresoRequeridos" class="font-semibold text-blue-600">0</span>
                    <span> requeridos</span>
                </div>

                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="button" id="btnCancelarModalAsignarOperarios"
                        class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                    <button type="button" id="btnGuardarAsignaciones"
                        class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-1 md:mr-2"></i>Guardar Asignaciones
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Clasificación -->
<div id="modalRegistrarClasificacion"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-3xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Registrar Proceso de Clasificación</h3>
            <button id="btnCerrarModalClasificacion" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarClasificacion" class="px-4 md:px-8 py-6 max-h-[75vh] overflow-auto-y">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="clas_lote" class="block text-sm font-medium text-gray-700 mb-1">Lote <span class="text-red-500">*</span></label>
                    <select id="clas_lote" name="idlote" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                        <option value="">Seleccionar lote...</option>
                    </select>
                </div>
                <div>
                    <label for="clas_operario" class="block text-sm font-medium text-gray-700 mb-1">Operario <span class="text-red-500">*</span></label>
                    <select id="clas_operario" name="idempleado" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                        <option value="">Seleccionar operario...</option>
                    </select>
                </div>
                <div>
                    <label for="clas_producto_origen" class="block text-sm font-medium text-gray-700 mb-1">Producto Origen <span class="text-red-500">*</span></label>
                    <select id="clas_producto_origen" name="idproducto_origen" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                        <option value="">Seleccionar producto...</option>
                    </select>
                </div>
                
                <div class="campo-con-boton">
                    <label for="clas_kg_procesados" class="block text-sm font-medium text-gray-700 mb-1">
                        Total Procesado (kg) <span class="text-red-500">*</span>
                    </label>
                    <div class="input-con-boton">
                        <input type="number" step="0.01" id="clas_kg_procesados" name="kg_procesados"
                            placeholder="0.00" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                        <button type="button" class="btnUltimoPesoRomanaClasificacion boton-interno"
                            data-campo="clas_kg_procesados" title="Traer último peso de romana">
                            <i class="fas fa-balance-scale"></i>
                        </button>
                    </div>
                </div>

                <div class="campo-con-boton">
                    <label for="clas_kg_limpios" class="block text-sm font-medium text-gray-700 mb-1">
                        Material Limpio (kg) <span class="text-red-500">*</span>
                    </label>
                    <div class="input-con-boton">
                        <input type="number" step="0.01" id="clas_kg_limpios" name="kg_limpios"
                            placeholder="0.00" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                        <button type="button" class="btnUltimoPesoRomanaClasificacion boton-interno"
                            data-campo="clas_kg_limpios" title="Traer último peso de romana">
                            <i class="fas fa-balance-scale"></i>
                        </button>
                    </div>
                </div>

                <div class="campo-con-boton">
                    <label for="clas_kg_contaminantes" class="block text-sm font-medium text-gray-700 mb-1">
                        Contaminantes (kg) <span class="text-red-500">*</span>
                    </label>
                    <div class="input-con-boton">
                        <input type="number" step="0.01" id="clas_kg_contaminantes" name="kg_contaminantes"
                            placeholder="0.00" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                        <button type="button" class="btnUltimoPesoRomanaClasificacion boton-interno"
                            data-campo="clas_kg_contaminantes" title="Traer último peso de romana">
                            <i class="fas fa-balance-scale"></i>
                        </button>
                    </div>
                </div>

                <!-- Div vacío para mantener el grid balanceado -->
                <div></div>

                <!-- Validación - ocupa las 2 columnas -->
                <div class="md:col-span-2">
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 mb-4">
                        <p class="text-blue-800 text-sm font-medium">Validación:</p>
                        <p class="text-blue-600 text-xs">Material Limpio + Contaminantes = Total Procesado</p>
                        <p id="validacionClasificacion" class="text-xs mt-1 font-semibold">Diferencia: 0.00 kg</p>
                    </div>
                </div>

                <!-- Observaciones - ocupa las 2 columnas -->
                <div class="md:col-span-2">
                    <label for="clas_observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="clas_observaciones" name="observaciones" rows="3" placeholder="Observaciones del proceso..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm"></textarea>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalClasificacion" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarClasificacion" form="formRegistrarClasificacion" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Registrar Clasificación
            </button>
        </div>
    </div>
</div>

<!-- Modal Registrar Empaque -->
<div id="modalRegistrarEmpaque"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Registrar Proceso de Empaque</h3>
            <button id="btnCerrarModalEmpaque" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarEmpaque" class="px-4 md:px-8 py-6">
            <div class="grid grid-cols-1 gap-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="emp_lote" class="block text-sm font-medium text-gray-700 mb-1">Lote <span class="text-red-500">*</span></label>
                        <select id="emp_lote" name="idlote" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm" required>
                            <option value="">Seleccionar lote...</option>
                        </select>
                    </div>
                    <div>
                        <label for="emp_operario" class="block text-sm font-medium text-gray-700 mb-1">Operario <span class="text-red-500">*</span></label>
                        <select id="emp_operario" name="idempleado" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm" required>
                            <option value="">Seleccionar operario...</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="emp_producto_clasificado" class="block text-sm font-medium text-gray-700 mb-1">Material Clasificado <span class="text-red-500">*</span></label>
                    <select id="emp_producto_clasificado" name="idproducto_clasificado" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm" required>
                        <option value="">Seleccionar material...</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="emp_peso_paca" class="block text-sm font-medium text-gray-700 mb-1">Peso de la Paca (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" id="emp_peso_paca" name="peso_paca" placeholder="0.00" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm" required>
                    </div>
                    <div>
                        <label for="emp_calidad" class="block text-sm font-medium text-gray-700 mb-1">Calidad <span class="text-red-500">*</span></label>
                        <select id="emp_calidad" name="calidad" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm" required>
                            <option value="">Seleccionar calidad...</option>
                            <option value="PREMIUM">Premium</option>
                            <option value="ESTANDAR">Estándar</option>
                            <option value="SEGUNDA">Segunda</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="emp_observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="emp_observaciones" name="observaciones" rows="3" placeholder="Observaciones del empaque..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"></textarea>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalEmpaque" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarEmpaque" form="formRegistrarEmpaque" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Registrar Empaque
            </button>
        </div>
    </div>
</div>

<!-- Modal Registrar Producción Diaria -->
<div id="modalRegistrarProduccionDiaria"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-6xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Registrar Producción Diaria</h3>
            <button id="btnCerrarModalProduccionDiaria" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] overflow-y-auto">
            <input type="hidden" id="idLoteProduccionDiaria">

            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                        <select id="selectLoteProduccionDiaria" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccionar lote...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                        <input type="date" id="fechaProduccionDiaria" class="w-full border rounded-lg px-3 py-2 text-sm" readonly>
                    </div>
                    <div>
                        <button id="btnCargarOperarios" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium mt-6">
                            <i class="fas fa-refresh mr-2"></i> Cargar Operarios
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="tablaProduccionDiaria" class="w-full text-sm border-collapse border border-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 text-left">Operario</th>
                            <th class="border border-gray-300 px-3 py-2 text-center">Kg Clasificados</th>
                            <th class="border border-gray-300 px-3 py-2 text-center">Kg Contaminantes</th>
                            <th class="border border-gray-300 px-3 py-2 text-center">Pacas Armadas</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody id="bodyProduccionDiaria">
                        <tr>
                            <td colspan="5" class="border border-gray-300 px-3 py-4 text-center text-gray-500">
                                Seleccione un lote para cargar los operarios
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalProduccionDiaria" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="button" id="btnGuardarProduccionDiaria" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Producción
            </button>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
    .operario-asignado {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #0ea5e9;
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .operario-asignado::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #0ea5e9, #0284c7);
    }

    .operario-asignado:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
    }

    .operario-asignado .info {
        flex: 1;
        padding-left: 0.5rem;
    }

    .operario-asignado .info .font-medium {
        color: #075985;
        font-size: 0.95rem;
    }

    .operario-asignado .acciones {
        flex-shrink: 0;
    }

    .operario-asignado select {
        font-size: 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        padding: 0.25rem 0.5rem;
        background-color: white;
        color: #374151;
        transition: border-color 0.2s;
    }

    .operario-asignado select:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 1px #0ea5e9;
    }

    .operario-checkbox {
        width: 1.125rem;
        height: 1.125rem;
        accent-color: #0ea5e9;
        cursor: pointer;
    }

    .operario-checkbox:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Animaciones para las transiciones */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .operario-asignado {
        animation: fadeInUp 0.3s ease;
    }

    /* Estados de carga */
    .loading-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #6b7280;
    }

    .loading-state i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .operario-asignado {
            flex-direction: column;
            gap: 0.75rem;
        }

        .operario-asignado .acciones {
            align-self: flex-end;
        }

        .operario-asignado .info .flex {
            flex-direction: column;
            gap: 0.5rem;
        }
    }

    /* Indicadores visuales mejorados */
    .badge-disponible {
        background-color: #dcfce7;
        color: #166534;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .badge-asignado {
        background-color: #fed7aa;
        color: #9a3412;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .badge-disponible::before {
        content: '●';
        color: #22c55e;
    }

    .badge-asignado::before {
        content: '●';
        color: #f97316;
    }

    .campo-con-boton {
        position: relative;
    }

    .input-con-boton {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .input-con-boton input {
        width: 100%;
        padding-right: 40px;
    }

    .boton-interno {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #1d4ed8;
        cursor: pointer;
        font-size: 14px;
    }
</style>

<?php footerAdmin($data); ?>