<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de módulos registrados en el sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mb-6">
            <button id="btnAbrirModalRegistrarModulo"
                class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="mr-1 md:mr-2"></i> Registrar Módulo
            </button>
            <button id="btnVerControladores"
                class="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="fas fa-code mr-1 md:mr-2"></i> Ver Controladores
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaModulos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 600px;"> <!-- Adjust min-width as needed -->
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
            <div id="loaderTableModulos" class="flex justify-center items-center my-4" style="display: none;">
                <div class="dot-flashing"></div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Módulo -->
<div id="modalRegistrarModulo"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Módulo</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarModulo" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <div class="mb-4">
                <label for="moduloTitulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Módulo <span class="text-red-500">*</span></label>
                <input type="text" id="moduloTitulo" name="titulo" placeholder="Ej: Usuarios, Productos, Ventas" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500 text-xs">El título debe coincidir con el nombre del controlador (sin .php)</small>
            </div>

            <div class="mb-4">
                <label for="moduloDescripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="moduloDescripcion" name="descripcion" rows="3" placeholder="Describe la funcionalidad del módulo..." class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 md:p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> Antes de registrar un módulo, asegúrese de que existe un controlador con el mismo nombre en la carpeta <code class="bg-blue-100 text-blue-800 px-1 py-0.5 rounded">app/Controllers/</code>
                        </p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarModulo" form="formRegistrarModulo" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Módulo
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Módulo -->
<div id="modalActualizarModulo"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Módulo</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarModulo" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idModuloActualizar" name="idmodulo">
            
            <div class="mb-4">
                <label for="moduloTituloActualizar" class="block text-sm font-medium text-gray-700 mb-1">Título del Módulo <span class="text-red-500">*</span></label>
                <input type="text" id="moduloTituloActualizar" name="titulo" placeholder="Ej: Usuarios, Productos, Ventas" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500 text-xs">El título debe coincidir con el nombre del controlador (sin .php)</small>
            </div>

            <div class="mb-4">
                <label for="moduloDescripcionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="moduloDescripcionActualizar" name="descripcion" rows="3" placeholder="Describe la funcionalidad del módulo..." class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 md:p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> El controlador correspondiente debe existir en <code class="bg-blue-100 text-blue-800 px-1 py-0.5 rounded">app/Controllers/</code>
                        </p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarModulo" form="formActualizarModulo" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Módulo
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Módulo -->
<div id="modalVerModulo" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                <i class="fas fa-eye mr-2 text-green-600"></i>
                Detalles del Módulo
            </h3>
            <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
            <div class="mb-6">
                <h4 class="text-base md:text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-cube mr-2 text-green-600"></i>
                    Información del Módulo
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Título</label>
                        <p id="verModuloTitulo" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Estatus</label>
                        <p id="verModuloEstatus" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500">Descripción</label>
                        <p id="verModuloDescripcion" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Fecha de Creación</label>
                        <p id="verModuloFechaCreacion" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Última Modificación</label>
                        <p id="verModuloFechaModificacion" class="text-gray-900 font-medium">-</p>
                    </div>
                </div>
            </div>

            <!-- Botón Cerrar -->
            <div class="flex justify-end pt-4 md:pt-6 border-t border-gray-200">
                <button type="button" id="btnCerrarModalVer2"
                        class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 text-sm md:text-base font-medium">
                    <i class="fas fa-times mr-1 md:mr-2"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Controladores -->
<div id="modalVerControladores" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]"> <!-- max-w-4xl for wider content -->
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Controladores Disponibles</h3>
            <button id="btnCerrarModalControladores" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[70vh] overflow-y-auto"> <!-- Increased max-h for content -->
            <div class="mb-4">
                <p class="text-sm text-gray-600">Lista de controladores encontrados en el sistema que aún no han sido registrados como módulos:</p>
            </div>
            
            <div id="listaControladores" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            </div>
            <p id="noControladoresDisponibles" class="text-center text-gray-500 py-4 text-sm hidden">No hay controladores nuevos disponibles para registrar.</p>
        </div>

        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end">
            <button type="button" id="btnCerrarModalControladores2" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm md:text-base font-medium">
                <i class="fas fa-times mr-1 md:mr-2"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>