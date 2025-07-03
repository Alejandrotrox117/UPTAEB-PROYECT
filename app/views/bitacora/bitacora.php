<?php headerAdmin($data); ?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<!-- Campos ocultos para permisos -->
<input type="hidden" id="permisoVer" value="<?php echo PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver') ? '1' : '0'; ?>">
<input type="hidden" id="permisoExportar" value="<?php echo PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver') ? '1' : '0'; ?>">

<!-- Main Content -->
<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">
            Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋
        </h2>
    </div>

    <!-- Header -->
    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Bitácora del Sistema</h1>
        <p class="text-green-600 text-base md:text-lg">Registro de acciones y eventos importantes</p>
    </div>

    <!-- Filtros y Controles -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="filtroModulo" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-filter mr-1"></i> Módulo
                </label>
                <select id="filtroModulo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option value="">Todos los módulos</option>
                </select>
            </div>
            
            <div>
                <label for="filtroFechaDesde" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i> Fecha desde
                </label>
                <input type="date" id="filtroFechaDesde" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div>
                <label for="filtroFechaHasta" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i> Fecha hasta
                </label>
                <input type="date" id="filtroFechaHasta" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            
            <div class="flex gap-2">
                <button id="btnLimpiarFiltros" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors duration-200 flex items-center" 
                        title="Limpiar filtros">
                    <i class="fas fa-times mr-2"></i>Limpiar
                </button>
                <button id="btnActualizarBitacora" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors duration-200 flex items-center" 
                        title="Actualizar datos">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla Principal -->
    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-history mr-2 text-green-600"></i>
                    Registros de Actividad
                </h3>
                <p class="text-sm text-gray-600 mt-1">Historial completo de acciones del sistema</p>
            </div>
            
            <!-- Botones de acción -->
            <div class="flex gap-2">
                <button id="btnEstadisticas" 
                        class="px-4 py-2 bg-indigo-500 text-white rounded-md hover:bg-indigo-600 transition-colors duration-200 flex items-center text-sm" 
                        title="Ver estadísticas">
                    <i class="fas fa-chart-bar mr-2"></i>Estadísticas
                </button>
                
                <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'eliminar')): ?>
                <button id="btnLimpiarBitacora" 
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors duration-200 flex items-center text-sm" 
                        title="Limpiar registros antiguos">
                    <i class="fas fa-trash mr-2"></i>Limpiar Antiguos
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="overflow-x-auto w-full relative">
            <table id="TablaBitacora" class="display stripe hover responsive nowrap fuente-tabla-pequena w-full min-w-full">
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                        <!-- Columnas se definen en JavaScript -->
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                    <!-- Datos se cargan dinámicamente -->
                </tbody>
            </table>
            
            <!-- Loader -->
            <div id="loaderTableBitacora" class=" justify-center items-center my-4 hidden">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500"></div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DETALLE BITÁCORA -->
<div id="modalDetalleBitacora" 
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 transform scale-95 transition-transform duration-300 max-h-screen overflow-hidden">
        
        <!-- Header del Modal -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-history text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Detalle del Registro</h3>
                    <p class="text-sm text-gray-600">Información completa de la actividad</p>
                </div>
            </div>
            <button id="btnCerrarModalDetalle" 
                    class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 hover:bg-gray-100 rounded-full">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Contenido del Modal -->
        <div class="p-6 space-y-6 max-h-96 overflow-y-auto">
            
            <!-- Información Principal -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Información Principal
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">ID de Registro</label>
                        <p id="detalleId" class="text-gray-900 font-medium bg-white px-3 py-2 rounded border">-</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Módulo/Tabla</label>
                        <p id="detalleModulo" class="text-gray-900 font-medium bg-white px-3 py-2 rounded border">-</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Acción Realizada</label>
                        <div id="detalleAccion" class="bg-white px-3 py-2 rounded border">-</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Usuario</label>
                        <p id="detalleUsuario" class="text-gray-900 font-medium bg-white px-3 py-2 rounded border flex items-center">
                            <i class="fas fa-user text-gray-400 mr-2"></i>
                            <span>-</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Información Temporal -->
            <div class="bg-green-50 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-clock text-green-500 mr-2"></i>
                    Información Temporal
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Fecha y Hora</label>
                        <p id="detalleFecha" class="text-gray-900 font-medium bg-white px-3 py-2 rounded border flex items-center">
                            <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                            <span>-</span>
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Tiempo Transcurrido</label>
                        <p id="detalleTiempoTranscurrido" class="text-gray-900 font-medium bg-white px-3 py-2 rounded border flex items-center">
                            <i class="fas fa-hourglass-half text-gray-400 mr-2"></i>
                            <span>-</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer del Modal -->
        <div class="flex justify-between items-center p-6 border-t border-gray-200 bg-gray-50">
            <div class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Información extraída de la bitácora del sistema
            </div>
            
            <div class="flex gap-3">
                <button id="btnExportarDetalle" 
                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors duration-200 flex items-center text-sm">
                    <i class="fas fa-download mr-2"></i>
                    Exportar
                </button>
                
                <button id="btnCerrarModalDetalle2" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors duration-200 flex items-center text-sm">
                    <i class="fas fa-times mr-2"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ESTADÍSTICAS -->
<div id="modalEstadisticas" 
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 transform scale-95 transition-transform duration-300 max-h-screen overflow-hidden">
        
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex items-center">
                <div class="bg-indigo-100 p-3 rounded-full mr-4">
                    <i class="fas fa-chart-bar text-indigo-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Estadísticas de Bitácora</h3>
                    <p class="text-sm text-gray-600">Resumen de actividad del sistema</p>
                </div>
            </div>
            <button id="btnCerrarModalEstadisticas" 
                    class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 hover:bg-gray-100 rounded-full">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Contenido -->
        <div class="p-6 max-h-96 overflow-y-auto">
            <div id="contenidoEstadisticas" class="space-y-6">
                <!-- Se llena dinámicamente -->
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500 mx-auto mb-4"></div>
                    <p class="text-gray-600">Cargando estadísticas...</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="flex justify-end p-6 border-t border-gray-200 bg-gray-50">
            <button id="btnCerrarModalEstadisticas2" 
                    class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors duration-200 flex items-center">
                <i class="fas fa-times mr-2"></i>
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Mensaje de Permisos Insuficientes -->
<?php if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
                Permisos Limitados
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
                <p>Su nivel de acceso actual no permite ver el contenido de este módulo. Contacte al administrador si necesita acceso adicional.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Scripts específicos para el tour de ayuda -->
<script src="/project/app/assets/js/ayuda/bitacora-tour.js"></script>

<?php footerAdmin($data); ?>