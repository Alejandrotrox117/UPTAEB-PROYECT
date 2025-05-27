<?php headerAdmin($data);?>

<!-- Input hidden para el rol del usuario actual (usado por JS) -->
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Módulos</h2>
        <input type="text" placeholder="Buscar en página..."
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-500 text-lg">Listado de módulos registrados en el sistema</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button id="btnAbrirModalRegistrarModulo"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="mr-2"></i> Registrar Módulo
                </button>
                <button id="btnVerControladores"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="fas fa-code mr-2"></i> Ver Controladores
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="TablaModulos" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <!-- Los títulos se definen en la configuración de DataTable en JS -->
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Las filas se cargarán dinámicamente con DataTables -->
                    </tbody>
                </table>
                <div id="loaderTableModulos" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Módulo -->
<div id="modalRegistrarModulo"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-2xl font-bold text-gray-800">Registrar Módulo</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarModulo" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <!-- Título del Módulo -->
            <div class="mb-4">
                <label for="moduloTitulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Módulo <span class="text-red-500">*</span></label>
                <input type="text" id="moduloTitulo" name="titulo" placeholder="Ej: Usuarios, Productos, Ventas" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500">El título debe coincidir con el nombre del controlador (sin .php)</small>
            </div>

            <!-- Descripción -->
            <div class="mb-4">
                <label for="moduloDescripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="moduloDescripcion" name="descripcion" rows="4" placeholder="Describe la funcionalidad del módulo..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> Antes de registrar un módulo, asegúrese de que existe un controlador con el mismo nombre en la carpeta app/Controllers/
                        </p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarModulo" form="formRegistrarModulo" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Módulo
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Módulo -->
<div id="modalActualizarModulo"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-2xl font-bold text-gray-800">Actualizar Módulo</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarModulo" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="idModuloActualizar" name="idmodulo">
            
            <!-- Título del Módulo -->
            <div class="mb-4">
                <label for="moduloTituloActualizar" class="block text-sm font-medium text-gray-700 mb-1">Título del Módulo <span class="text-red-500">*</span></label>
                <input type="text" id="moduloTituloActualizar" name="titulo" placeholder="Ej: Usuarios, Productos, Ventas" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500">El título debe coincidir con el nombre del controlador (sin .php)</small>
            </div>

            <!-- Descripción -->
            <div class="mb-4">
                <label for="moduloDescripcionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="moduloDescripcionActualizar" name="descripcion" rows="4" placeholder="Describe la funcionalidad del módulo..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> El controlador correspondiente debe existir en app/Controllers/
                        </p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarModulo" form="formActualizarModulo" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Actualizar Módulo
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Módulo -->
<div id="modalVerModulo" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh]">
            <!-- Header del Modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Módulo
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <!-- Información del Módulo -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-cube mr-2 text-green-600"></i>
                        Información del Módulo
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Título</label>
                            <p id="verModuloTitulo" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Estatus</label>
                            <p id="verModuloEstatus" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</label>
                            <p id="verModuloDescripcion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</label>
                            <p id="verModuloFechaCreacion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Última Modificación</label>
                            <p id="verModuloFechaModificacion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                    </div>
                </div>

                <!-- Botón Cerrar -->
                <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="btnCerrarModalVer2"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Controladores -->
<div id="modalVerControladores" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-800">Controladores Disponibles</h3>
            <button id="btnCerrarModalControladores" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <div class="mb-4">
                <p class="text-gray-600">Lista de controladores encontrados en el sistema:</p>
            </div>
            
            <div id="listaControladores" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Los controladores se cargarán dinámicamente -->
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
            <button type="button" id="btnCerrarModalControladores2" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-base font-medium">
                <i class="fas fa-times mr-2"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
