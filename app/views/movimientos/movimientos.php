<?php 
headerAdmin($data);
$permisos = $data['permisos'];
?>

<?= renderJavaScriptData('permisosMovimientos', $permisos); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Movimientos de Inventario</h1>
        <p class="text-green-600 text-base md:text-lg">Gestión integral de movimientos de existencias</p>
    </div>

    <?php if (!$permisos['ver']): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 mt-6 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 font-medium">
                        <strong>Acceso Restringido:</strong> No tienes permisos para ver la lista de movimientos.
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!--  ESTADÍSTICAS SIMPLES -->
        <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-bar mr-2 text-blue-500"></i>
                    Estadísticas de Movimientos
                </h3>
                <div id="estadisticas-movimientos">
                    <!-- Se llena dinámicamente con JavaScript -->
                </div>
            </div>
        </div>

        <!--  FILTROS Y BÚSQUEDA (SIMILAR A BITÁCORA) -->
        <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div class="flex flex-col sm:flex-row gap-4 flex-1">
                    <!-- Select de Tipo de Movimiento -->
                    <div class="flex-1 min-w-64">
                        <label for="filtro-tipo-movimiento" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-filter mr-1"></i>Filtrar por Tipo
                        </label>
                        <select id="filtro-tipo-movimiento" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Cargando tipos...</option>
                        </select>
                    </div>

                    <!-- Búsqueda Personalizada -->
                    <div class="flex-1 min-w-64">
                        <label for="busqueda-movimientos" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-1"></i>Búsqueda
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="busqueda-movimientos" 
                                   placeholder="Buscar en movimientos..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indicador de Filtro Actual -->
            <div class="flex items-center justify-between text-sm border-t pt-4">
                <div id="indicador-filtro-actual" class="text-gray-600 flex items-center">
                    <i class="fas fa-list mr-1"></i>Mostrando todos los movimientos
                </div>
                <div class="text-gray-500 text-xs">
                    <i class="fas fa-info-circle mr-1"></i>
                    Los contadores se actualizan automáticamente
                </div>
            </div>
        </div>

        <!--  TABLA DE MOVIMIENTOS -->
        <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div class="flex flex-col sm:flex-row gap-2">
                    <?php if ($permisos['crear']): ?>
                        <button id="btnAbrirModalMovimiento"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base transition-colors duration-200">
                            <i class="fas fa-plus mr-1 md:mr-2"></i> Registrar Movimiento
                        </button>
                    <?php endif; ?>

                    <?php if ($permisos['exportar']): ?>
                        <button id="btnExportarMovimientos"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base transition-colors duration-200">
                            <i class="fas fa-download mr-1 md:mr-2"></i> Exportar
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!$permisos['crear']): ?>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-500 text-sm">
                        <i class="fas fa-lock mr-2"></i> Sin permisos para crear movimientos
                    </div>
                <?php endif; ?>
            </div>

            <div class="overflow-x-auto w-full relative">
                <table id="TablaMovimiento" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 700px;">
                    <thead>
                        <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                            <!-- Headers generados automáticamente por DataTables -->
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                        <!-- Datos cargados automáticamente por DataTables -->
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</main>

<!--  MODAL VER DETALLE MOVIMIENTO -->
<?php if ($permisos['ver']): ?>
<div id="modalVerDetalleMovimiento" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl">
        <div class="px-4 py-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Detalle del Movimiento</h3>
            <button type="button" id="btnCerrarModalDetalle" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="px-4 py-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600">Nro. Movimiento:</label>
                    <p id="verMovimientoNumero" class="text-gray-900 font-semibold"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Producto:</label>
                    <p id="verMovimientoProducto" class="text-gray-900"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Tipo:</label>
                    <p id="verMovimientoTipo" class="text-gray-900"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Cantidad:</label>
                    <p id="verMovimientoCantidad" class="text-gray-900"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Stock Resultante:</label>
                    <p id="verMovimientoStock" class="text-gray-900"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Estatus:</label>
                    <p id="verMovimientoEstatus" class="text-gray-900"></p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600">Observaciones:</label>
                <p id="verMovimientoObservaciones" class="text-gray-900"></p>
            </div>
        </div>

        <div class="px-4 py-4 border-t bg-gray-50 flex justify-end">
            <button type="button" id="btnCerrarModalDetalle2"
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition text-lg">
                Cerrar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!--  MODAL REGISTRAR MOVIMIENTO -->
<?php if ($permisos['crear']): ?>
<div id="modalRegistrarMovimiento" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center  bg-opacity-50 backdrop-blur-sm transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-11/12 max-w-4xl max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 bg-green-50 flex-shrink-0">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-green-800">
                    <i class="fas fa-plus-circle mr-2"></i>Registrar Nuevo Movimiento
                </h3>
                <button type="button" id="btnCerrarModalRegistrar" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <form id="formRegistrarMovimiento" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Producto -->
                    <div>
                        <label for="idproducto" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-box mr-1"></i>Producto *
                        </label>
                        <select id="idproducto" name="idproducto" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Seleccione un producto</option>
                        </select>
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Tipo de Movimiento -->
                    <div>
                        <label for="idtipomovimiento" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1"></i>Tipo de Movimiento *
                        </label>
                        <select id="idtipomovimiento" name="idtipomovimiento" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Seleccione un tipo</option>
                        </select>
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Cantidad Entrada -->
                    <div>
                        <label for="cantidad_entrada" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-arrow-up text-green-500 mr-1"></i>Cantidad Entrada
                        </label>
                        <input type="number" id="cantidad_entrada" name="cantidad_entrada" 
                               step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Cantidad Salida -->
                    <div>
                        <label for="cantidad_salida" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-arrow-down text-red-500 mr-1"></i>Cantidad Salida
                        </label>
                        <input type="number" id="cantidad_salida" name="cantidad_salida" 
                               step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Stock Anterior -->
                    <div>
                        <label for="stock_anterior" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-history mr-1"></i>Stock Anterior
                        </label>
                        <input type="number" id="stock_anterior" name="stock_anterior" 
                               step="0.01" min="0" placeholder="0.00" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-gray-600">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Stock Resultante -->
                    <div>
                        <label for="stock_resultante" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calculator mr-1"></i>Stock Resultante
                        </label>
                        <input type="number" id="stock_resultante" name="stock_resultante" 
                               step="0.01" min="0" placeholder="0.00" readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-gray-600">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div>
                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment mr-1"></i>Observaciones
                    </label>
                    <textarea id="observaciones" name="observaciones" rows="3" placeholder="Observaciones opcionales..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                    <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                </div>

            </form>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3 flex-shrink-0">
            <button type="button" id="btnCancelarModalRegistrar" 
                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
            <button type="submit" form="formRegistrarMovimiento" id="btnRegistrarMovimiento"
                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 font-medium">
                <i class="fas fa-save mr-2"></i>Registrar Movimiento
            </button>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if ($permisos['editar']): ?>
<div id="modalActualizarMovimiento" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center  bg-opacity-50 backdrop-blur-sm transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-11/12 max-w-4xl max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 bg-green-50 flex-shrink-0">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-green-800">
                    <i class="fas fa-edit mr-2"></i>Actualizar Movimiento
                </h3>
                <button type="button" id="btnCerrarModalActualizar" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <form id="formActualizarMovimiento" class="p-6 space-y-6">
                <!-- ID del Movimiento (hidden) -->
                <input type="hidden" id="idmovimientoActualizar" name="idmovimientoActualizar">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Número de Movimiento -->
                    <div>
                        <label for="numeroMovimientoActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-hashtag mr-1"></i>Número de Movimiento
                        </label>
                        <input type="text" id="numeroMovimientoActualizar" name="numeroMovimientoActualizar" 
                               readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                    </div>

                    <!-- Producto -->
                    <div>
                        <label for="idproductoActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-box mr-1"></i>Producto *
                        </label>
                        <select id="idproductoActualizar" name="idproductoActualizar" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione un producto</option>
                        </select>
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Tipo de Movimiento -->
                    <div>
                        <label for="idtipomovimientoActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1"></i>Tipo de Movimiento *
                        </label>
                        <select id="idtipomovimientoActualizar" name="idtipomovimientoActualizar" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione un tipo</option>
                        </select>
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Estatus -->
                    <div>
                        <label for="estatusActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-toggle-on mr-1"></i>Estatus *
                        </label>
                        <select id="estatusActualizar" name="estatusActualizar" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Cantidad Entrada -->
                    <div>
                        <label for="cantidad_entradaActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-arrow-up text-green-500 mr-1"></i>Cantidad Entrada
                        </label>
                        <input type="number" id="cantidad_entradaActualizar" name="cantidad_entradaActualizar" 
                               step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Cantidad Salida -->
                    <div>
                        <label for="cantidad_salidaActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-arrow-down text-red-500 mr-1"></i>Cantidad Salida
                        </label>
                        <input type="number" id="cantidad_salidaActualizar" name="cantidad_salidaActualizar" 
                               step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Stock Anterior -->
                    <div>
                        <label for="stock_anteriorActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-history mr-1"></i>Stock Anterior
                        </label>
                        <input type="number" id="stock_anteriorActualizar" name="stock_anteriorActualizar" 
                               step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Stock Resultante -->
                    <div>
                        <label for="stock_resultanteActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calculator mr-1"></i>Stock Resultante
                        </label>
                        <input type="number" id="stock_resultanteActualizar" name="stock_resultanteActualizar" 
                               step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div>
                    <label for="observacionesActualizar" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment mr-1"></i>Observaciones
                    </label>
                    <textarea id="observacionesActualizar" name="observacionesActualizar" rows="3" placeholder="Observaciones opcionales..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
                </div>

                
            </form>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3 flex-shrink-0">
            <button type="button" id="btnCancelarModalActualizar" 
                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
            <button type="submit" form="formActualizarMovimiento" id="btnActualizarMovimiento"
                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 font-medium">
                <i class="fas fa-save mr-2"></i>Actualizar Movimiento
            </button>
        </div>
    </div>
</div>
<?php endif; ?>


<div id="modalPermisosDenegados" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300 z-[60]">
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-11/12 max-w-md transform transition-transform duration-300">
        <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-red-800">Acceso Denegado</h3>
                </div>
            </div>
        </div>
        
        <div class="px-6 py-4">
            <p id="mensajePermisosDenegados" class="text-gray-700 text-base">
                No tienes permisos para realizar esta acción.
            </p>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
            <button type="button" id="btnCerrarModalPermisos" 
                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 font-medium">
                Entendido
            </button>
        </div>
    </div>
</div>

<script src="<?= base_url('app/assets/js/ayuda/movimientos-tour.js'); ?>"></script>
<?php footerAdmin($data); ?>