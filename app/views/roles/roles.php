<?php headerAdmin($data);?>
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Roles</h2>
        <input type="text" placeholder="Buscar en página..."
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-500 text-lg">Listado de roles registrados en el sistema</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button id="btnAbrirModalRegistrarRol"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="mr-2"></i> Registrar Rol
                </button>
            </div>

            <div class="overflow-x-auto">
                <!-- DataTable -->
                <table id="TablaRoles" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                    </tbody>
                </table>
                <div id="loaderTableRoles" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Rol -->
<div id="modalRegistrarRol"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-2xl font-bold text-gray-800">Registrar Rol</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarRol" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <div class="mb-4">
                <label for="nombreRol" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol <span class="text-red-500">*</span></label>
                <input type="text" id="nombreRol" name="nombre" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="mb-4">
                <label for="descripcionRol" class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                <textarea id="descripcionRol" name="descripcion" rows="3" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required></textarea>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="mb-4">
                <label for="estatusRol" class="block text-sm font-medium text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                <select id="estatusRol" name="estatus" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un estatus</option>
                    <option value="activo">ACTIVO</option>
                    <option value="inactivo">INACTIVO</option>
                </select>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarRol" form="formRegistrarRol" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Rol
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Rol -->
<div id="modalActualizarRol"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-2xl font-bold text-gray-800">Actualizar Rol</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarRol" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="idRolActualizar" name="idrol">
            
            <div class="mb-4">
                <label for="nombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol <span class="text-red-500">*</span></label>
                <input type="text" id="nombreActualizar" name="nombre" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="mb-4">
                <label for="descripcionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                <textarea id="descripcionActualizar" name="descripcion" rows="3" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required></textarea>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="mb-4">
                <label for="estatusActualizar" class="block text-sm font-medium text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                <select id="estatusActualizar" name="estatus" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Seleccione un estatus</option>
                    <option value="activo">ACTIVO</option>
                    <option value="inactivo">INACTIVO</option>
                </select>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>
        </form>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarRol" form="formActualizarRol" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Actualizar Rol
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Rol -->
<div id="modalVerRol" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh]">
            
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Rol
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-user-tag mr-2 text-green-600"></i>
                        Información del Rol
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</label>
                            <p id="verNombre" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Estatus</label>
                            <p id="verEstatus" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</label>
                            <p id="verFechaCreacion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Última Modificación</label>
                            <p id="verUltimaModificacion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</label>
                            <p id="verDescripcion" class="text-gray-900 dark:text-white font-medium">-</p>
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

<!-- Modal Confirmar Eliminar -->
<div id="modalConfirmarEliminar" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-[60]">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
    <div class="px-8 py-6 border-b">
      <h3 class="text-2xl font-bold text-gray-800">Confirmar Desactivación</h3>
    </div>
    <div class="px-8 py-6">
      <p class="text-gray-700 text-lg">
        ¿Estás seguro de que deseas desactivar el rol <strong id="nombreRolEliminar" class="font-semibold"></strong>? Esta acción cambiará su estatus a INACTIVO.
      </p>
    </div>
    <div class="px-8 py-6 border-t flex justify-end space-x-4">
      <button type="button" id="btnCancelarEliminacion" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnConfirmarAccionEliminar" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-base font-medium">
        Desactivar
      </button>
    </div>
  </div>
</div>

<?php footerAdmin($data); ?>
