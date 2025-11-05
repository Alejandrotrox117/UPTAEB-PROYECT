<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de productos registrados en el sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <button id="btnAbrirModalRegistrarProducto"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="fas fa-plus mr-1 md:mr-2"></i> Registrar Producto
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaProductos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Registrar Producto -->
<div id="modalRegistrarProducto"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Producto</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarProducto" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="md:col-span-2">
                    <label for="productoNombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                    <input type="text" id="productoNombre" name="nombre" placeholder="Nombre del producto" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div class="md:col-span-2">
                    <label for="productoDescripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="productoDescripcion" name="descripcion" rows="3" placeholder="Descripción del producto (opcional)" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoCategoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select id="productoCategoria" name="idcategoria" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccione una categoría</option>
                        <!-- Las opciones se cargan dinámicamente -->
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoUnidadMedida" class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida <span class="text-red-500">*</span></label>
                    <select id="productoUnidadMedida" name="unidad_medida" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccione unidad</option>
                        <option value="UNIDAD">Unidad</option>
                        <option value="KG">Kilogramo</option>
                        <option value="GRAMO">Gramo</option>
                        <option value="LITRO">Litro</option>
                        <option value="ML">Mililitro</option>
                        <option value="METRO">Metro</option>
                        <option value="CM">Centímetro</option>
                        <option value="CAJA">Caja</option>
                        <option value="PAQUETE">Paquete</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoPrecio" class="block text-sm font-medium text-gray-700 mb-1">Precio <span class="text-red-500">*</span></label>
                    <input type="number" id="productoPrecio" name="precio" step="0.0001" min="0.0001" placeholder="0.0000" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoMoneda" class="block text-sm font-medium text-gray-700 mb-1">Moneda <span class="text-red-500">*</span></label>
                    <select id="productoMoneda" name="moneda" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccione moneda</option>
                        <option value="BS" selected>Bolívares (BS)</option>
                        <option value="USD">Dólares (USD)</option>
                        <option value="EUR">Euros (EUR)</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarProducto" form="formRegistrarProducto" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Producto
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Producto -->
<div id="modalActualizarProducto"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Producto</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formActualizarProducto" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idProductoActualizar" name="idproducto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="md:col-span-2">
                    <label for="productoNombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                    <input type="text" id="productoNombreActualizar" name="nombre" placeholder="Nombre del producto" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div class="md:col-span-2">
                    <label for="productoDescripcionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="productoDescripcionActualizar" name="descripcion" rows="3" placeholder="Descripción del producto (opcional)" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoCategoriaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select id="productoCategoriaActualizar" name="idcategoria" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccione una categoría</option>
                        <!-- Las opciones se cargan dinámicamente -->
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoUnidadMedidaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida <span class="text-red-500">*</span></label>
                    <select id="productoUnidadMedidaActualizar" name="unidad_medida" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccione unidad</option>
                        <option value="UNIDAD">Unidad</option>
                        <option value="KG">Kilogramo</option>
                        <option value="GRAMO">Gramo</option>
                        <option value="LITRO">Litro</option>
                        <option value="ML">Mililitro</option>
                        <option value="METRO">Metro</option>
                        <option value="CM">Centímetro</option>
                        <option value="CAJA">Caja</option>
                        <option value="PAQUETE">Paquete</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoPrecioActualizar" class="block text-sm font-medium text-gray-700 mb-1">Precio <span class="text-red-500">*</span></label>
                    <input type="number" id="productoPrecioActualizar" name="precio" step="0.0001" min="0.0001" placeholder="0.0000" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                
                <div>
                    <label for="productoMonedaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Moneda <span class="text-red-500">*</span></label>
                    <select id="productoMonedaActualizar" name="moneda" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                        <option value="">Seleccione moneda</option>
                        <option value="BS">Bolívares (BS)</option>
                        <option value="USD">Dólares (USD)</option>
                        <option value="EUR">Euros (EUR)</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarProducto" form="formActualizarProducto" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Producto
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Producto -->
<div id="modalVerProducto" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">
        <div class="bg-white rounded-lg shadow-xl w-full max-h-[calc(95vh-80px)] sm:max-h-[90vh]">
            <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
                <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Producto
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
                <div class="mb-6">
                    <h4 class="text-base md:text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-box mr-2 text-green-600"></i>
                        Información del Producto
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Nombre</label>
                            <p id="verProductoNombre" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Categoría</label>
                            <p id="verProductoCategoria" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Unidad de Medida</label>
                            <p id="verProductoUnidadMedida" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Precio</label>
                            <p id="verProductoPrecio" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Existencia</label>
                            <p id="verProductoExistencia" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Estatus</label>
                            <p id="verProductoEstatus" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-500">Descripción</label>
                            <p id="verProductoDescripcion" class="text-gray-900 font-medium">-</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-4 md:pt-6 border-t border-gray-200">
                    <button type="button" id="btnCerrarModalVer2"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 text-sm md:text-base">
                        <i class="fas fa-times mr-1 md:mr-2"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>