<?php headerAdmin($data);?>

<!-- Input hidden para el rol del usuario actual (usado por JS) -->
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Usuarios</h2>
        <input type="text" placeholder="Buscar en página..."
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-500 text-lg">Listado de usuarios registrados en el sistema</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button id="btnAbrirModalRegistrarUsuario"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="mr-2"></i> Registrar Usuario
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="TablaUsuarios" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <!-- Los títulos se definen en la configuración de DataTable en JS -->
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Las filas se cargarán dinámicamente con DataTables -->
                    </tbody>
                </table>
                <div id="loaderTableUsuarios" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Usuario -->
<div id="modalRegistrarUsuario"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-2xl font-bold text-gray-800">Registrar Usuario</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarUsuario" class="px-8 py-6 max-h-[70vh] overflow-y-auto">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioNombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" id="usuarioNombre" name="usuario" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioCorreo" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="usuarioCorreo" name="correo" placeholder="usuario@dominio.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioClave" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="usuarioClave" name="clave" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioRol" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select id="usuarioRol" name="idrol" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un rol</option>

                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>


            <div class="mb-4">
                <label for="usuarioPersona" class="block text-sm font-medium text-gray-700 mb-1">Persona Asociada (Opcional)</label>
                <select id="usuarioPersona" name="personaId" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">Sin persona asociada</option>

                </select>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500">Puede asociar este usuario a una persona existente en el sistema</small>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarUsuario" form="formRegistrarUsuario" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Usuario
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Usuario -->
<div id="modalActualizarUsuario"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-2xl font-bold text-gray-800">Actualizar Usuario</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarUsuario" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="idUsuarioActualizar" name="idusuario">
            
            <!-- Fila 1: Usuario y Correo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioNombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" id="usuarioNombreActualizar" name="usuario" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioCorreoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="usuarioCorreoActualizar" name="correo" placeholder="usuario@dominio.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <!-- Fila 2: Contraseña y Rol -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioClaveActualizar" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <small>(dejar en blanco para no cambiar)</small></label>
                    <input type="password" id="usuarioClaveActualizar" name="clave" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioRolActualizar" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select id="usuarioRolActualizar" name="idrol" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un rol</option>
                        
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

           
            <div class="mb-4">
                <label for="usuarioPersonaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Persona Asociada (Opcional)</label>
                <select id="usuarioPersonaActualizar" name="personaId" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">Sin persona asociada</option>
                    
                </select>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500">Puede asociar este usuario a una persona existente en el sistema</small>
            </div>
        </form>

        
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarUsuario" form="formActualizarUsuario" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Actualizar Usuario
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Usuario -->
<div id="modalVerUsuario" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh]">
            
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Usuario
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[70vh]">
                
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-user mr-2 text-green-600"></i>
                        Información del Usuario
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre de Usuario</label>
                            <p id="verUsuarioNombre" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Correo Electrónico</label>
                            <p id="verUsuarioCorreo" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Rol</label>
                            <p id="verUsuarioRol" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Estatus</label>
                            <p id="verUsuarioEstatus" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-id-card mr-2 text-purple-600"></i>
                        Persona Asociada
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre Completo</label>
                            <p id="verPersonaNombre" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Cédula</label>
                            <p id="verPersonaCedula" class="text-gray-900 dark:text-white font-medium">-</p>
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
