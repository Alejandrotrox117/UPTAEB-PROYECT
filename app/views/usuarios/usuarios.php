<?php 
use App\Helpers\PermisosModuloVerificar;

headerAdmin($data);

//  OBTENER PERMISOS DEL USUARIO PARA EL MÓDULO
$permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('Usuarios');
?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<!--  PASAR PERMISOS AL JAVASCRIPT -->
<?= renderJavaScriptData('permisosUsuarios', $permisos); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de usuarios registrados en el sistema</p>
    </div>

    <!--  MOSTRAR MENSAJE SI NO TIENE PERMISOS PARA VER -->
    <?php if (!$permisos['ver']): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 mt-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700 font-medium">
                    <strong>Acceso Restringido:</strong> No tienes permisos para ver la lista de usuarios.
                </p>
                <p class="text-xs text-yellow-600 mt-1">
                    Contacta al administrador del sistema si necesitas acceso a este módulo.
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <!--  BOTÓN CREAR SOLO SI TIENE PERMISOS -->
            <?php if ($permisos['crear']): ?>
            <button id="btnAbrirModalRegistrarUsuario"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="fas fa-user-plus mr-1 md:mr-2"></i> Registrar Usuario
            </button>
            <?php else: ?>
            <div class="bg-gray-100 px-4 py-2 md:px-6 rounded-lg text-gray-500 text-sm md:text-base">
                <i class="fas fa-lock mr-1 md:mr-2"></i> Sin permisos para crear usuarios
            </div>
            <?php endif; ?>

            <!--  BOTÓN EXPORTAR SOLO SI TIENE PERMISOS -->
            <?php if ($permisos['exportar']): ?>
            <button id="btnExportarUsuarios"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base ml-2">
                <i class="fas fa-download mr-1 md:mr-2"></i> Exportar
            </button>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaUsuarios" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 700px;">
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
            <div id="loaderTableUsuarios" class="flex justify-center items-center my-4" style="display: none;">
                <div class="dot-flashing"></div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</main>

<!--  MODALES SOLO SI TIENE PERMISOS CORRESPONDIENTES -->

<?php if ($permisos['crear']): ?>
<!-- Modal Registrar Usuario -->
<div id="modalRegistrarUsuario"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Usuario</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarUsuario" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioNombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" id="usuarioNombre" name="usuario" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioCorreo" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="usuarioCorreo" name="correo" placeholder="usuario@dominio.com" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioClave" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="usuarioClave" name="clave" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioRol" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select id="usuarioRol" name="idrol" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un rol</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="mb-4">
                <label for="usuarioPersona" class="block text-sm font-medium text-gray-700 mb-1">Persona Asociada (Opcional)</label>
                <select id="usuarioPersona" name="personaId" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">Sin persona asociada</option>
                </select>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500 text-xs">Puede asociar este usuario a una persona existente en el sistema.</small>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarUsuario" form="formRegistrarUsuario" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Usuario
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permisos['editar']): ?>
<!-- Modal Actualizar Usuario -->
<div id="modalActualizarUsuario"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Usuario</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarUsuario" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idUsuarioActualizar" name="idusuario">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioNombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" id="usuarioNombreActualizar" name="usuario" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioCorreoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="usuarioCorreoActualizar" name="correo" placeholder="usuario@dominio.com" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="usuarioClaveActualizar" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <small class="text-gray-500">(dejar en blanco para no cambiar)</small></label>
                    <input type="password" id="usuarioClaveActualizar" name="clave" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="usuarioRolActualizar" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select id="usuarioRolActualizar" name="idrol" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un rol</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
           
            <div class="mb-4">
                <label for="usuarioPersonaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Persona Asociada (Opcional)</label>
                <select id="usuarioPersonaActualizar" name="personaId" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">Sin persona asociada</option>
                </select>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
                <small class="text-gray-500 text-xs">Puede asociar este usuario a una persona existente en el sistema.</small>
            </div>
        </form>
        
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarUsuario" form="formActualizarUsuario" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Usuario
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permisos['ver']): ?>
<!-- Modal Ver Usuario -->
<div id="modalVerUsuario" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                <i class="fas fa-eye mr-2 text-green-600"></i>
                Detalles del Usuario
            </h3>
            <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
            <div class="mb-6">
                <h4 class="text-base md:text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-user mr-2 text-green-600"></i>
                    Información del Usuario
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Nombre de Usuario</label>
                        <p id="verUsuarioNombre" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Correo Electrónico</label>
                        <p id="verUsuarioCorreo" class="text-gray-900 font-medium break-all">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Rol</label>
                        <p id="verUsuarioRol" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Estatus</label>
                        <p id="verUsuarioEstatus" class="text-gray-900 font-medium">-</p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h4 class="text-base md:text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-id-card mr-2 text-purple-600"></i>
                    Persona Asociada
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Nombre Completo</label>
                        <p id="verPersonaNombre" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Cédula/Identificación</label>
                        <p id="verPersonaCedula" class="text-gray-900 font-medium">-</p>
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
<?php endif; ?>

<!--  MODAL DE MENSAJE PARA PERMISOS DENEGADOS -->
<div id="modalPermisosDenegados" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-96 max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-red-50">
            <h3 class="text-lg font-semibold text-red-800">
                <i class="fas fa-lock mr-2"></i>
                Acceso Denegado
            </h3>
            <button id="btnCerrarModalPermisos" class="text-red-600 hover:text-red-800">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 text-2xl"></i>
                </div>
                <div class="ml-3">
                    <p id="mensajePermisosDenegados" class="text-sm text-gray-700 font-medium">
                        No tienes permisos para realizar esta acción.
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="btnCerrarModalPermisos2" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium">
                    <i class="fas fa-check mr-1"></i>
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos del módulo de usuarios -->
<script src="<?= base_url('app/assets/js/ayuda/usuarios-tour.js'); ?>"></script>

<?php footerAdmin($data); ?>