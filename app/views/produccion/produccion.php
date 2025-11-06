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
                    <table id="TablaLotes" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
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
                
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Registros de Producción</h3>
                       
                    </div>
                    <button id="btnAbrirModalRegistrarProduccion"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base transition-all hover:shadow-lg">
                        <i class="fas fa-plus-circle mr-2"></i> Nuevo Registro de Producción
                    </button>
                </div>

               

           
                <!-- Lista de procesos recientes -->
                <div class="mt-8">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Procesos de Hoy</h4>
                    <div class="overflow-x-auto w-full relative">
                        <table id="TablaProcesos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 600px;">
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
                        <p class="text-gray-600 text-sm">Consulta registros de producción y envía pagos a los empleados</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="btnCalcularNomina"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium text-sm shadow-md transition-all hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i> Consultar Registros por Fecha
                        </button>
                    </div>
                </div>

               

                <div class="overflow-x-auto w-full relative">
                    <table id="TablaNomina" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 500px;">
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

                    <!-- Sección Salarios por Proceso-Producto (FUERA del form principal) -->
                    <div class="mt-10">
                        <h4 class="text-md font-semibold text-gray-800 mb-4"><i class="fas fa-dollar-sign mr-2"></i>Configuración de Salarios por Proceso</h4>
                        <p class="text-xs text-gray-600 mb-4">Define cuánto se paga por kg/unidad según el tipo de proceso y producto. Esta configuración afecta el cálculo de salarios en producción.</p>
                        
                        <div class="bg-white border rounded-lg p-4 mb-4">
                            <form id="formPrecioProceso">
                                <input type="hidden" name="moneda" value="USD" />
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Tipo Proceso *</label>
                                        <select name="tipo_proceso" id="tipo_proceso_salario" class="w-full border rounded px-2 py-1 text-sm" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLASIFICACION">Clasificación</option>
                                            <option value="EMPAQUE">Empaque</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600">Producto *</label>
                                        <select name="idproducto_precio" id="idproducto_precio" class="w-full border rounded px-2 py-1 text-sm" required>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Salario por Unidad *</label>
                                        <input name="salario_unitario" id="salario_unitario_input" type="number" step="0.0001" min="0.0001" class="w-full border rounded px-2 py-1 text-sm" placeholder="0.0000" required />
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" id="btnAgregarSalario" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                            <i class="fas fa-plus mr-2"></i>Agregar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proceso</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Salario/Unidad</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Moneda</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estatus</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-precios-proceso-body" class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODALES -->

<!-- Modal Registrar Lote -->
<div id="modalRegistrarLote"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-5xl max-h-[95vh] flex flex-col">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-700 px-4 md:px-6 py-4 flex justify-between items-center flex-shrink-0 border-b border-gray-200">
            <div class="flex items-center gap-2">
                <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                    <i class="fas fa-boxes text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg md:text-xl font-bold text-white">
                        Crear Lote de Producción
                    </h3>
                    <p class="text-green-100 text-xs hidden sm:block">
                        <i class="fas fa-info-circle mr-1"></i>
                        Registra un nuevo lote con sus procesos
                    </p>
                </div>
            </div>
            <button id="btnCerrarModalRegistrarLote" type="button" 
                class="text-white/80 hover:text-white hover:bg-white/20 transition-all p-1.5 rounded-full">
                <svg class="h-6 w-6 md:h-7 md:w-7" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarLote" class="px-4 md:px-6 py-4 flex-1 overflow-y-auto">
            
            <!-- Sección Datos Generales del Lote -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 md:p-5 border-2 border-green-200 mb-4">
                <h4 class="text-base font-bold text-green-900 mb-3 flex items-center gap-2">
                    <div class="bg-green-500 p-1.5 rounded-lg">
                        <i class="fas fa-clipboard-list text-white text-sm"></i>
                    </div>
                    Datos Generales del Lote
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="lote_fecha_jornada" class="block text-xs font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-calendar-alt text-green-600 mr-1"></i>
                            Fecha de Jornada <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="lote_fecha_jornada" name="fecha_jornada" 
                            class="w-full border-2 border-green-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" required>
                    </div>
                    <div>
                        <label for="lote_volumen_estimado" class="block text-xs font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-balance-scale text-green-600 mr-1"></i>
                            Volumen Estimado (kg) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" id="lote_volumen_estimado" name="volumen_estimado" placeholder="Ej: 2500.00" 
                            class="w-full border-2 border-green-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" required>
                    </div>
                    <div>
                        <label for="lote_supervisor" class="block text-xs font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-user-tie text-green-600 mr-1"></i>
                            Supervisor <span class="text-red-500">*</span>
                        </label>
                        <select id="lote_supervisor" name="idsupervisor" 
                            class="w-full border-2 border-green-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm" required>
                            <option value="">Seleccionar supervisor...</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="lote_observaciones" class="block text-xs font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-comment-alt text-green-600 mr-1"></i>
                            Observaciones
                        </label>
                        <textarea id="lote_observaciones" name="observaciones" rows="2" placeholder="Observaciones adicionales..." 
                            class="w-full border-2 border-green-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-sm"></textarea>
                    </div>
                </div>

                <!-- Información calculada -->
                <div id="infoCalculada" class="bg-white/70 backdrop-blur-sm p-3 rounded-lg border border-green-300 mt-3 hidden">
                    <h4 class="text-xs font-bold text-green-900 mb-2 flex items-center gap-2">
                        <i class="fas fa-calculator text-green-600 text-xs"></i>
                        Información Calculada Automáticamente
                    </h4>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-green-100 p-2 rounded-lg">
                            <span class="text-green-700 font-medium">Operarios Requeridos:</span>
                            <span id="operariosCalculados" class="font-bold ml-2 text-green-900">-</span>
                        </div>
                        <div class="bg-green-100 p-2 rounded-lg">
                            <span class="text-green-700 font-medium">Capacidad Máxima:</span>
                            <span id="capacidadMaxima" class="font-bold ml-2 text-green-900">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección Agregar Registros de Producción al Lote -->
            <div class="mt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1.5 flex items-center">
                    <i class="fas fa-industry text-green-600 mr-2 text-xs"></i>
                    Registros de Producción del Lote
                </h4>
                
                <!-- Formulario para agregar registro de producción -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-3 rounded-lg border border-gray-200 mb-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <!-- Fila 1: Empleado, Fecha, Tipo -->
                        <div>
                            <label for="lote_prod_empleado" class="block text-xs font-medium text-gray-700 mb-1">
                                Empleado <span class="text-red-500">*</span>
                            </label>
                            <select id="lote_prod_empleado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar empleado...</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="lote_prod_fecha" class="block text-xs font-medium text-gray-700 mb-1">
                                Fecha Proceso <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="lote_prod_fecha" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <div>
                            <label for="lote_prod_tipo" class="block text-xs font-medium text-gray-700 mb-1">
                                Tipo de Proceso <span class="text-red-500">*</span>
                            </label>
                            <select id="lote_prod_tipo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar tipo...</option>
                                <option value="CLASIFICACION">🔵 Clasificación</option>
                                <option value="EMPAQUE">🟣 Empaque</option>
                            </select>
                        </div>

                        <!-- Fila 2: Producto Inicial y Cantidad -->
                        <div>
                            <label for="lote_prod_producto_inicial" class="block text-xs font-medium text-gray-700 mb-1">
                                Producto Inicial <span class="text-red-500">*</span>
                            </label>
                            <select id="lote_prod_producto_inicial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar producto...</option>
                            </select>
                        </div>

                        <div>
                            <label for="lote_prod_cantidad_inicial" class="block text-xs font-medium text-gray-700 mb-1">
                                Cantidad Inicial (kg) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" id="lote_prod_cantidad_inicial" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <!-- Fila 3: Producto Final y Cantidad -->
                        <div>
                            <label for="lote_prod_producto_final" class="block text-xs font-medium text-gray-700 mb-1">
                                Producto Final <span class="text-red-500">*</span>
                            </label>
                            <select id="lote_prod_producto_final" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccionar producto...</option>
                            </select>
                        </div>

                        <div>
                            <label for="lote_prod_cantidad_producida" class="block text-xs font-medium text-gray-700 mb-1">
                                Cantidad Producida (kg) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" id="lote_prod_cantidad_producida" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <!-- Salarios (calculados automáticamente) -->
                        <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-3 bg-yellow-50 p-2.5 rounded-lg border border-yellow-200">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Salario Base</label>
                                <input type="text" id="lote_prod_salario_base" readonly class="w-full bg-gray-100 border border-gray-200 rounded px-2.5 py-1.5 text-xs text-gray-700" value="$0.00">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pago por Trabajo</label>
                                <input type="text" id="lote_prod_pago_trabajo" readonly class="w-full bg-gray-100 border border-gray-200 rounded px-2.5 py-1.5 text-xs text-gray-700" value="$0.00">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Salario Total</label>
                                <input type="text" id="lote_prod_salario_total" readonly class="w-full bg-green-100 border border-green-300 rounded px-2.5 py-1.5 text-xs font-bold text-green-700" value="$0.00">
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="lg:col-span-3">
                            <label for="lote_prod_observaciones" class="block text-xs font-medium text-gray-700 mb-1">
                                Observaciones
                            </label>
                            <textarea id="lote_prod_observaciones" rows="2" placeholder="Observaciones del registro..." class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                    </div>

                    <!-- Botón Agregar -->
                    <div class="mt-3 flex justify-end">
                        <button type="button" id="btnAgregarRegistroProduccionLote" class="bg-green-500 hover:bg-green-600 text-white rounded-lg px-4 py-2 text-xs font-medium shadow-sm transition-all hover:shadow-md">
                            <i class="fas fa-plus-circle mr-1.5"></i>Agregar Registro
                        </button>
                    </div>
                </div>

                <!-- Tabla de registros agregados -->
                <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                    <table id="tablaRegistrosProduccionLote" class="w-full text-xs">
                        <thead class="bg-gradient-to-r from-green-50 to-green-100">
                            <tr>
                                <th class="px-2 py-2 text-left font-semibold text-green-900">Empleado</th>
                                <th class="px-2 py-2 text-left font-semibold text-green-900">Fecha</th>
                                <th class="px-2 py-2 text-left font-semibold text-green-900">Tipo</th>
                                <th class="px-2 py-2 text-left font-semibold text-green-900">Prod. Inicial</th>
                                <th class="px-2 py-2 text-right font-semibold text-green-900">Cant. Inicial</th>
                                <th class="px-2 py-2 text-left font-semibold text-green-900">Prod. Final</th>
                                <th class="px-2 py-2 text-right font-semibold text-green-900">Cant. Producida</th>
                                <th class="px-2 py-2 text-right font-semibold text-green-900">Salario</th>
                                <th class="px-2 py-2 text-center font-semibold text-green-900">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaRegistrosProduccionLote" class="divide-y divide-gray-200 bg-white">
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                    <p id="noRegistrosProdMensaje" class="text-center text-gray-500 py-6 text-sm bg-gray-50">
                        <i class="fas fa-inbox text-gray-300 text-2xl mb-2"></i><br>
                        No hay registros de producción agregados al lote.
                    </p>
                </div>
            </div>

            <div id="mensajeErrorFormLote" class="text-red-600 text-xs mt-4 text-center font-medium"></div>
        </form>

        <div class="bg-gray-50 px-4 md:px-6 py-3 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 flex-shrink-0">
            <button type="button" id="btnCancelarModalRegistrarLote" class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                Cancelar
            </button>
            <button type="button" id="btnGuardarLote" class="w-full sm:w-auto px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm font-medium">
                <i class="fas fa-save mr-2"></i> Crear Lote con Procesos
            </button>
        </div>
    </div>
</div>

<!-- Modal Asignar Operarios -->
<div id="modalAsignarOperarios"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-6xl max-h-[95vh] flex flex-col">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center flex-shrink-0">
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Asignar Operarios al Lote</h3>
            <button id="btnCerrarModalAsignarOperarios" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 md:h-7 md:w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-4 md:px-6 py-4 flex-1 overflow-y-auto">
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
        <div class="bg-gray-50 px-4 md:px-6 py-3 border-t border-gray-200 flex-shrink-0">
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
                        class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                    <button type="button" id="btnGuardarAsignaciones"
                        class="w-full sm:w-auto px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2"></i>Guardar Asignaciones
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Modal Registrar Producción Diaria -->
<div id="modalRegistrarProduccionDiaria"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-6xl max-h-[95vh] flex flex-col">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center flex-shrink-0">
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Registrar Producción Diaria</h3>
            <button id="btnCerrarModalProduccionDiaria" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 md:h-7 md:w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="px-4 md:px-6 py-4 flex-1 overflow-y-auto">
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
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 flex-shrink-0">
            <button type="button" id="btnCancelarModalProduccionDiaria" class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                Cancelar
            </button>
            <button type="button" id="btnGuardarProduccionDiaria" class="w-full sm:w-auto px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition text-sm font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Producción
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- MODAL: REGISTRAR PRODUCCIÓN -->
<!-- ============================================= -->
<div id="modalRegistrarProduccion" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-30 z-50 opacity-0 pointer-events-none transition-opacity duration-300 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full sm:w-10/11 max-w-4xl max-h-[95vh] flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex justify-between items-center px-4 md:px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 border-b flex-shrink-0">
            <h3 class="text-lg md:text-xl font-bold text-white flex items-center">
                <i class="fas fa-industry mr-2"></i>Registrar Producción
            </h3>
            <button id="btnCerrarModalRegistrarProduccion" type="button" class="text-white hover:text-gray-200 transition-colors p-1 rounded-full hover:bg-green-700">
                <svg class="h-6 w-6 md:h-7 md:w-7" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Formulario -->
        <form id="formRegistrarProduccion" class="px-4 md:px-6 py-4 flex-1 overflow-y-auto">
            
            <!-- Información del Lote y Fecha -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                <h4 class="text-base font-semibold text-gray-700 mb-4 flex items-center border-b pb-2">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>Información de la Jornada
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="prod_lote" class="block text-sm font-medium text-gray-700 mb-1">
                            Lote <span class="text-red-500">*</span>
                        </label>
                        <select id="prod_lote" name="idlote" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                            <option value="">Seleccionar lote...</option>
                        </select>
                    </div>
                    <div>
                        <label for="prod_empleado" class="block text-sm font-medium text-gray-700 mb-1">
                            Empleado/Operario <span class="text-red-500">*</span>
                        </label>
                        <select id="prod_empleado" name="idempleado" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                            <option value="">Seleccionar empleado...</option>
                        </select>
                    </div>
                    <div>
                        <label for="prod_fecha_jornada" class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha de Jornada <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="prod_fecha_jornada" name="fecha_jornada" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                    </div>
                </div>
            </div>

            <!-- Producto a Producir y Cantidad -->
            <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200">
                <h4 class="text-base font-semibold text-gray-700 mb-4 flex items-center border-b pb-2">
                    <i class="fas fa-箱 text-blue-600 mr-2"></i>Materia Prima
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="prod_producto_producir" class="block text-sm font-medium text-gray-700 mb-1">
                            Producto a Producir <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500">(Producto que se convertirá)</span>
                        </label>
                        <select id="prod_producto_producir" name="idproducto_producir" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <option value="">Seleccionar producto...</option>
                        </select>
                    </div>
                    <div>
                        <label for="prod_cantidad_producir" class="block text-sm font-medium text-gray-700 mb-1">
                            Cantidad a Producir (kg) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" id="prod_cantidad_producir" name="cantidad_producir" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                </div>
            </div>

            <!-- Producto Terminado y Cantidad Producida -->
            <div class="bg-green-50 p-4 rounded-lg mb-6 border border-green-200">
                <h4 class="text-base font-semibold text-gray-700 mb-4 flex items-center border-b pb-2">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>Producto Terminado
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="prod_producto_terminado" class="block text-sm font-medium text-gray-700 mb-1">
                            Producto Terminado <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500">(Producto resultante)</span>
                        </label>
                        <select id="prod_producto_terminado" name="idproducto_terminado" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                            <option value="">Seleccionar producto...</option>
                        </select>
                    </div>
                    <div>
                        <label for="prod_cantidad_producida" class="block text-sm font-medium text-gray-700 mb-1">
                            Cantidad Producida (kg) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" id="prod_cantidad_producida" name="cantidad_producida" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                    </div>
                </div>
            </div>

            <!-- Tipo de Movimiento (Clasificación o Empaque) -->
            <div class="bg-purple-50 p-4 rounded-lg mb-6 border border-purple-200">
                <h4 class="text-base font-semibold text-gray-700 mb-4 flex items-center border-b pb-2">
                    <i class="fas fa-cogs text-purple-600 mr-2"></i>Tipo de Proceso
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="prod_tipo_movimiento" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de Movimiento <span class="text-red-500">*</span>
                        </label>
                        <select id="prod_tipo_movimiento" name="tipo_movimiento" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="CLASIFICACION">Clasificación</option>
                            <option value="EMPAQUE">Empaque</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Salarios (calculados automáticamente) -->
            <div class="bg-yellow-50 p-4 rounded-lg mb-6 border border-yellow-200">
                <h4 class="text-base font-semibold text-gray-700 mb-4 flex items-center border-b pb-2">
                    <i class="fas fa-dollar-sign text-yellow-600 mr-2"></i>Información Salarial
                    <span class="ml-auto text-xs text-gray-500 font-normal">(Calculado según configuración)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="prod_salario_base_dia" class="block text-sm font-medium text-gray-700 mb-1">
                            Salario Base Día
                        </label>
                        <input type="number" step="0.01" min="0" id="prod_salario_base_dia" name="salario_base_dia" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" readonly>
                        <p class="text-xs text-gray-500 mt-1">Según configuración_produccion</p>
                    </div>
                    <div>
                        <label for="prod_pago_clasificacion" class="block text-sm font-medium text-gray-700 mb-1">
                            Pago por Trabajo
                        </label>
                        <input type="number" step="0.01" min="0" id="prod_pago_clasificacion" name="pago_clasificacion_trabajo" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" readonly>
                        <p class="text-xs text-gray-500 mt-1">Basado en cantidad producida</p>
                    </div>
                    <div>
                        <label for="prod_salario_total" class="block text-sm font-medium text-gray-700 mb-1">
                            Salario Total
                        </label>
                        <input type="number" step="0.01" min="0" id="prod_salario_total" name="salario_total" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-green-100 font-bold text-green-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" readonly>
                        <p class="text-xs text-gray-500 mt-1">Suma total</p>
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
                <h4 class="text-base font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-sticky-note text-gray-600 mr-2"></i>Observaciones
                </h4>
                <textarea id="prod_observaciones" name="observaciones" rows="3" placeholder="Notas adicionales sobre este registro de producción..." class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent resize-none"></textarea>
            </div>

        </form>

        <!-- Footer con botones -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 px-4 md:px-6 py-3 bg-gray-50 border-t border-gray-200 flex-shrink-0">
            <button type="button" id="btnCancelarRegistrarProduccion" class="w-full sm:w-auto px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200 text-sm font-medium">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
            <button type="submit" form="formRegistrarProduccion" id="btnGuardarRegistrarProduccion" class="w-full sm:w-auto px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200 text-sm font-medium">
                <i class="fas fa-save mr-2"></i>Guardar Registro
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle del Lote -->
<div id="modalVerLote" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] bg-opacity-30 z-50 opacity-0 pointer-events-none transition-opacity duration-300 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full sm:w-11/12 max-w-5xl h-[90vh] flex flex-col">
        <div class="flex justify-between items-center px-4 md:px-6 py-4 border-b flex-shrink-0">
            <h3 class="text-lg md:text-xl font-bold text-gray-800">
                <i class="fas fa-boxes mr-2 text-green-600"></i>Detalle del Lote de Producción
            </h3>
            <button id="btnCerrarModalVerLote" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        
        <div class="p-4 md:p-6 flex-1 overflow-y-auto custom-scrollbar">
            <!-- Información General del Lote -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6">
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Número de Lote:</label>
                    <p id="verLoteNumero" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Fecha de Jornada:</label>
                    <p id="verLoteFecha" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Volumen Estimado:</label>
                    <p id="verLoteVolumen" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Supervisor:</label>
                    <p id="verLoteSupervisor" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Estado:</label>
                    <p id="verLoteEstado" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Operarios Asignados:</label>
                    <p id="verLoteOperarios" class="text-sm sm:text-base md:text-lg font-semibold text-gray-900">-</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs sm:text-sm font-medium text-gray-500">Observaciones:</label>
                    <p id="verLoteObservaciones" class="text-sm sm:text-base text-gray-700">-</p>
                </div>
            </div>

            <!-- Registros de Producción del Lote -->
            <div class="mt-6" id="seccionRegistrosProduccion">
                <h4 class="text-base md:text-lg font-semibold text-gray-800 mb-3 border-b pb-2 flex items-center">
                    <i class="fas fa-industry text-green-600 mr-2"></i>
                    Registros de Producción
                </h4>
                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="w-full text-sm">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-green-900">Fecha</th>
                                <th class="px-3 py-2 text-left font-medium text-green-900">Empleado</th>
                                <th class="px-3 py-2 text-left font-medium text-green-900">Producto Inicial</th>
                                <th class="px-3 py-2 text-right font-medium text-green-900">Cantidad (kg)</th>
                                <th class="px-3 py-2 text-left font-medium text-green-900">Producto Final</th>
                                <th class="px-3 py-2 text-right font-medium text-green-900">Producido (kg)</th>
                                <th class="px-3 py-2 text-center font-medium text-green-900">Tipo</th>
                                <th class="px-3 py-2 text-right font-medium text-green-900">Salario Total</th>
                            </tr>
                        </thead>
                        <tbody id="verRegistrosProduccion" class="divide-y divide-gray-200">
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen de Totales de Producción -->
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-base md:text-lg font-semibold text-gray-800 mb-3">Resumen Financiero</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <label class="block text-xs font-medium text-blue-700">Total Registros:</label>
                        <p id="verTotalRegistros" class="text-lg font-bold text-blue-900">0</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <label class="block text-xs font-medium text-green-700">Total Producido:</label>
                        <p id="verTotalProducido" class="text-lg font-bold text-green-900">0.00 kg</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <label class="block text-xs font-medium text-yellow-700">Total Salarios Base:</label>
                        <p id="verTotalSalariosBase" class="text-lg font-bold text-yellow-900">$0.00</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <label class="block text-xs font-medium text-purple-700">Total General:</label>
                        <p id="verTotalSalariosGeneral" class="text-lg font-bold text-purple-900">$0.00</p>
                    </div>
                </div>
                
                <!-- Desglose por tipo -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <label class="block text-sm font-medium text-blue-700 mb-2">
                            <i class="fas fa-filter mr-1"></i>Clasificación
                        </label>
                        <div class="space-y-1 text-xs text-blue-800">
                            <p>Registros: <span id="verCantidadClasificacion" class="font-semibold">0</span></p>
                            <p>Producido: <span id="verTotalKgClasificacion" class="font-semibold">0.00 kg</span></p>
                        </div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <label class="block text-sm font-medium text-purple-700 mb-2">
                            <i class="fas fa-cube mr-1"></i>Empaque
                        </label>
                        <div class="space-y-1 text-xs text-purple-800">
                            <p>Registros: <span id="verCantidadEmpaque" class="font-semibold">0</span></p>
                            <p>Producido: <span id="verTotalKgEmpaque" class="font-semibold">0.00 kg</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensaje cuando no hay registros -->
            <div id="mensajeNoRegistros" class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg" style="display: none;">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 font-medium">
                            Este lote aún no tiene registros de producción.
                        </p>
                        <p class="text-xs text-yellow-600 mt-1">
                            Los registros se crean desde la pestaña "Procesos" → "Registrar Producción".
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end px-4 md:px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
            <button type="button" id="btnCerrarModalVerLote2" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 text-sm">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
    /* Scrollbar personalizado para modales */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
        transition: background 0.2s;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Para Firefox */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e0 #f1f5f9;
    }

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