<?php
headerAdmin($data); 
$permisos = $data['permisos'] ?? []; 
?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Movimientos de existencias</h1>
        <p class="text-green-500 text-lg">Movimientos</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <?php if ($permisos['puede_crear']): ?>
                    <button id="btnAbrirModalMovimiento" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-600 transition">
                        <i class="fas fa-plus mr-2"></i>Registrar Movimiento
                    </button>
                <?php else: ?>
                    <div class="text-gray-500 text-sm">
                        <i class="fas fa-lock mr-2"></i>No tiene permisos para registrar movimientos
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

            <table id="TablaMovimiento" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-500 text-sm border-b">
                        <th class="py-2">Nro. Movimiento</th>
                        <th class="py-2">Producto</th>
                        <th class="py-2">Tipo de Movimiento</th>
                        <th class="py-2">Entrada</th>
                        <th class="py-2">Salida</th>
                        <th class="py-2">Stock Resultante</th>
                        <th class="py-2">Estatus</th>
                        <?php if ($permisos['puede_editar'] || $permisos['puede_eliminar']): ?>
                            <th class="py-2">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="text-gray-900">
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>

<?php if ($permisos['puede_crear']): ?>
<!-- Modal para Registrar Nuevo Movimiento -->
<div id="movimientoModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl">
        <!-- Encabezado -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-box mr-2 text-green-600"></i>
                <span id="tituloModalMovimiento">Registrar Nuevo Movimiento</span>
            </h3>
            <button id="cerrarModalMovimiento" class="p-1 text-gray-400 transition-colors rounded-full hover:text-gray-600 hover:bg-gray-200">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <!-- Formulario -->
        <form id="formMovimiento" class="px-6 py-4">
            <input type="hidden" id="idmovimiento" name="idmovimiento" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="producto" class="block text-gray-700 font-medium mb-2">
                        Producto <span class="text-red-500">*</span>
                    </label>
                    <select id="producto" name="producto" class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un producto</option>
                    </select>
                    <div id="error-producto-vacio" class="text-red-500 text-sm mt-1 hidden">
                        Este campo es obligatorio
                    </div>
                </div>

                <div>
                    <label for="tipo_movimiento" class="block text-gray-700 font-medium mb-2">
                        Tipo de Movimiento <span class="text-red-500">*</span>
                    </label>
                    <select id="tipo_movimiento" name="tipo_movimiento" class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione tipo</option>
                        <option value="ENTRADA">Entrada</option>
                        <option value="SALIDA">Salida</option>
                        <option value="AJUSTE">Ajuste</option>
                    </select>
                    <div id="error-tipo_movimiento-vacio" class="text-red-500 text-sm mt-1 hidden">
                        Este campo es obligatorio
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="cantidad" class="block text-gray-700 font-medium mb-2">
                        Cantidad <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="cantidad" name="cantidad" 
                           class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                           min="0.01" step="0.01" placeholder="0.00" required>
                    <div id="error-cantidad-vacio" class="text-red-500 text-sm mt-1 hidden">
                        La cantidad debe ser mayor a 0
                    </div>
                </div>

                <div>
                    <label for="fecha" class="block text-gray-700 font-medium mb-2">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="fecha" name="fecha" 
                           class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                           value="<?= date('Y-m-d') ?>" required>
                    <div id="error-fecha-vacio" class="text-red-500 text-sm mt-1 hidden">
                        La fecha es obligatoria
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label for="observaciones" class="block text-gray-700 font-medium mb-2">
                    Observaciones
                </label>
                <textarea id="observaciones" name="observaciones" rows="3"
                          class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400 resize-none"
                          placeholder="Ingrese observaciones adicionales (opcional)"></textarea>
                <div id="error-observaciones-formato" class="text-red-500 text-sm mt-1 hidden">
                    Formato de observaciones inválido
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6">
                <div>
                    <button type="button" id="cancelarMovimiento" 
                            class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
                        Cancelar
                    </button>
                </div>
                <div>
                    <button type="button" id="guardarMovimiento"
                            class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-lg">
                        Registrar Movimiento
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal Detalle de Movimiento -->
<div id="modalDetalleMovimiento" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl">
        <!-- Encabezado -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="mr-2 text-indigo-600 fas fa-eye"></i>Detalle de Movimiento
            </h3>
            <button id="cerrarDetalleMovimiento" class="p-1 text-gray-400 transition-colors rounded-full hover:text-gray-600 hover:bg-gray-200">
                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        
        <div class="px-6 py-4" id="detalleMovimientoContenido">
            <!-- Contenido del detalle se carga dinámicamente -->
        </div>
        
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="button" id="cerrarDetalleMovimiento2" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
                Cerrar
            </button>
        </div>
    </div>
</div>

<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>