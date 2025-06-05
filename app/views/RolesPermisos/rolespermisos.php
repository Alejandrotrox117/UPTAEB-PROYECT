<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Gestión de asignaciones entre roles y permisos del sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <button id="btnAbrirModalRegistrarRolPermiso"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="mr-1 md:mr-2"></i> Nueva Asignación
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaRolesPermisos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 600px;"> <!-- Adjust min-width as needed -->
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
            <div id="loaderTableRolesPermisos" class="flex justify-center items-center my-4" style="display: none;">
                <div class="dot-flashing"></div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Asignación -->
<div id="modalRegistrarRolPermiso"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Nueva Asignación Rol-Permiso</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarRolPermiso" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <div class="mb-4">
                <label for="rolSelect" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                <select id="rolSelect" name="idrol" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un rol</option>
                </select>
                <div id="rolSelect-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <div class="mb-4">
                <label for="permisoSelect" class="block text-sm font-medium text-gray-700 mb-1">Permiso <span class="text-red-500">*</span></label>
                <select id="permisoSelect" name="idpermiso" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un permiso</option>
                </select>
                <div id="permisoSelect-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 md:p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> No se pueden duplicar asignaciones. Un rol puede tener múltiples permisos, pero cada combinación rol-permiso debe ser única.
                        </p>
                    </div>
                </div>
            </div>
        </form>

        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarRolPermiso" form="formRegistrarRolPermiso" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Asignación
            </button>
        </div>
    </div>
</div>


<div id="modalActualizarRolPermiso"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Asignación Rol-Permiso</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarRolPermiso" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idRolPermisoActualizar" name="idrolpermiso">
            
            <div class="mb-4">
                <label for="rolSelectActualizar" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                <select id="rolSelectActualizar" name="idrol" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un rol</option>
                </select>
                <div id="rolSelectActualizar-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <div class="mb-4">
                <label for="permisoSelectActualizar" class="block text-sm font-medium text-gray-700 mb-1">Permiso <span class="text-red-500">*</span></label>
                <select id="permisoSelectActualizar" name="idpermiso" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un permiso</option>
                </select>
                <div id="permisoSelectActualizar-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 md:p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> No se pueden duplicar asignaciones. Verifique que la nueva combinación rol-permiso no exista.
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
            <button type="submit" id="btnActualizarRolPermiso" form="formActualizarRolPermiso" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Asignación
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Asignación -->
<div id="modalVerRolPermiso" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                <i class="fas fa-eye mr-2 text-green-600"></i>
                Detalles de la Asignación
            </h3>
            <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
            <div class="mb-6">
                <h4 class="text-base md:text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-user-tag mr-2 text-green-600"></i>
                    Información del Rol
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Nombre del Rol</label>
                        <p id="verRolNombre" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500">Descripción del Rol</label>
                        <p id="verRolDescripcion" class="text-gray-900 font-medium">-</p>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <h4 class="text-base md:text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-key mr-2 text-blue-600"></i>
                    Información del Permiso
                </h4>
                <div class="grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Nombre del Permiso</label>
                        <p id="verPermisoNombre" class="text-gray-900 font-medium">-</p>
                    </div>
                </div>
            </div>

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

<?php footerAdmin($data); ?>