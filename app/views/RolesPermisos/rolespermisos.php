<?php headerAdmin($data);?>

<!-- Input hidden para el rol del usuario actual (usado por JS) -->
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Roles y Permisos</h2>
        <input type="text" placeholder="Buscar en página..."
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-500 text-lg">Gestión de asignaciones entre roles y permisos del sistema</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button id="btnAbrirModalRegistrarRolPermiso"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="mr-2"></i> Nueva Asignación
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="TablaRolesPermisos" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <!-- Los títulos se definen en la configuración de DataTable en JS -->
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Las filas se cargarán dinámicamente con DataTables -->
                    </tbody>
                </table>
                <div id="loaderTableRolesPermisos" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Asignación -->
<div id="modalRegistrarRolPermiso"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-2xl font-bold text-gray-800">Nueva Asignación Rol-Permiso</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarRolPermiso" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <!-- Rol -->
            <div class="mb-4">
                <label for="rolSelect" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                <select id="rolSelect" name="idrol" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un rol</option>
                </select>
                <div id="rolSelect-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <!-- Permiso -->
            <div class="mb-4">
                <label for="permisoSelect" class="block text-sm font-medium text-gray-700 mb-1">Permiso <span class="text-red-500">*</span></label>
                <select id="permisoSelect" name="idpermiso" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un permiso</option>
                </select>
                <div id="permisoSelect-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Importante:</strong> No se pueden duplicar asignaciones. Un rol puede tener múltiples permisos, pero cada combinación rol-permiso debe ser única.
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
            <button type="submit" id="btnGuardarRolPermiso" form="formRegistrarRolPermiso" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Asignación
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Asignación -->
<div id="modalActualizarRolPermiso"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-2xl font-bold text-gray-800">Actualizar Asignación Rol-Permiso</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarRolPermiso" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="idRolPermisoActualizar" name="idrolpermiso">
            
            <!-- Rol -->
            <div class="mb-4">
                <label for="rolSelectActualizar" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                <select id="rolSelectActualizar" name="idrol" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un rol</option>
                </select>
                <div id="rolSelectActualizar-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <!-- Permiso -->
            <div class="mb-4">
                <label for="permisoSelectActualizar" class="block text-sm font-medium text-gray-700 mb-1">Permiso <span class="text-red-500">*</span></label>
                <select id="permisoSelectActualizar" name="idpermiso" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un permiso</option>
                </select>
                <div id="permisoSelectActualizar-error" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
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
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarRolPermiso" form="formActualizarRolPermiso" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Actualizar Asignación
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Asignación -->
<div id="modalVerRolPermiso" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh]">
            <!-- Header del Modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles de la Asignación
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <!-- Información del Rol -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-user-tag mr-2 text-green-600"></i>
                        Información del Rol
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre del Rol</label>
                            <p id="verRolNombre" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Descripción del Rol</label>
                            <p id="verRolDescripcion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                    </div>
                </div>

                <!-- Información del Permiso -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-key mr-2 text-blue-600"></i>
                        Información del Permiso
                    </h4>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre del Permiso</label>
                            <p id="verPermisoNombre" class="text-gray-900 dark:text-white font-medium">-</p>
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

<?php footerAdmin($data); ?>
