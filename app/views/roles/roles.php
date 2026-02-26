<?php 
use App\Helpers\PermisosModuloVerificar;
use App\Models\RolesModel;

headerAdmin($data);

$permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('Roles');

$rolesModel = new RolesModel(); 
$idUsuarioSesion = $_SESSION['usuario_id'] ?? 0;
$esSuperUsuario = $rolesModel->verificarEsSuperUsuario($idUsuarioSesion);
?>

<?= renderJavaScriptData('permisosRoles', $permisos); ?>
<?= renderJavaScriptData('esSuperUsuario', $esSuperUsuario); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de roles registrados en el sistema</p>
    </div>

    <!-- Mensaje si no tiene permisos para ver -->
    <?php if (!$permisos['ver']): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 mt-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700 font-medium">
                    <strong>Acceso Restringido:</strong> No tienes permisos para ver la lista de roles.
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <!-- Botón Crear solo si tiene permisos -->
            <?php if ($permisos['crear']): ?>
            <button id="btnAbrirModalRegistrarRol"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="fas fa-user-tag mr-1 md:mr-2"></i> Registrar Rol
            </button>
            <?php else: ?>
            <div class="text-gray-500 text-sm">
                <i class="fas fa-lock mr-1"></i> No tiene permisos para crear roles
            </div>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaRoles" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 600px;">
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>


<?php if ($permisos['crear']): ?>
<!-- Modal Registrar Rol -->
<div id="modalRegistrarRol"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Rol</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarRol" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <div class="mb-4">
                <label for="nombreRol" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol <span class="text-red-500">*</span></label>
                <input type="text" id="nombreRol" name="nombre" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" >
                <div class="text-red-500 text-xs mt-1 error-nombreRol-vacio hidden">El nombre del rol es obligatorio</div>
                <div class="text-red-500 text-xs mt-1 error-nombreRol-formato hidden">El nombre solo puede contener letras, números, espacios y guiones</div>
                <div class="text-red-500 text-xs mt-1 error-nombreRol-longitud hidden">El nombre debe tener entre 3 y 50 caracteres</div>
            </div>

            <div class="mb-4">
                <label for="descripcionRol" class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                <textarea id="descripcionRol" name="descripcion" rows="3" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" ></textarea>
                <div class="text-red-500 text-xs mt-1 error-descripcionRol-vacio hidden">La descripción es obligatoria</div>
                <div class="text-red-500 text-xs mt-1 error-descripcionRol-longitud hidden">La descripción no puede superar los 255 caracteres</div>
            </div>

            <div class="mb-4">
                <label for="estatusRol" class="block text-sm font-medium text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                <select id="estatusRol" name="estatus" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" >
                    <option value="">Seleccione un estatus</option>
                    <option value="ACTIVO">ACTIVO</option>
                    <option value="INACTIVO">INACTIVO</option>
                </select>
                <div class="text-red-500 text-xs mt-1 error-estatusRol-vacio hidden">El estatus es obligatorio</div>
                <div class="text-red-500 text-xs mt-1 error-estatusRol-formato hidden">El estatus debe ser ACTIVO o INACTIVO</div>
            </div>
        </form>

        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarRol" form="formRegistrarRol" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Rol
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permisos['editar']): ?>
<!-- Modal Actualizar Rol -->
<div id="modalActualizarRol"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Rol</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarRol" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idRolActualizar" name="idrol">
            
            <div class="mb-4">
                <label for="nombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol <span class="text-red-500">*</span></label>
                <input type="text" id="nombreActualizar" name="nombre" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" >
                <div class="text-red-500 text-xs mt-1 error-nombreActualizar-vacio hidden">El nombre del rol es obligatorio</div>
                <div class="text-red-500 text-xs mt-1 error-nombreActualizar-formato hidden">El nombre solo puede contener letras, números, espacios y guiones</div>
                <div class="text-red-500 text-xs mt-1 error-nombreActualizar-longitud hidden">El nombre debe tener entre 3 y 50 caracteres</div>
            </div>

            <div class="mb-4">
                <label for="descripcionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                <textarea id="descripcionActualizar" name="descripcion" rows="3" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" ></textarea>
                <div class="text-red-500 text-xs mt-1 error-descripcionActualizar-vacio hidden">La descripción es obligatoria</div>
                <div class="text-red-500 text-xs mt-1 error-descripcionActualizar-longitud hidden">La descripción no puede superar los 255 caracteres</div>
            </div>

            <div class="mb-4">
                <label for="estatusActualizar" class="block text-sm font-medium text-gray-700 mb-1">Estatus <span class="text-red-500">*</span></label>
                <select id="estatusActualizar" name="estatus" class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" >
                    <option value="">Seleccione un estatus</option>
                    <option value="ACTIVO">ACTIVO</option>
                    <option value="INACTIVO">INACTIVO</option>
                </select>
                <div class="text-red-500 text-xs mt-1 error-estatusActualizar-vacio hidden">El estatus es obligatorio</div>
                <div class="text-red-500 text-xs mt-1 error-estatusActualizar-formato hidden">El estatus debe ser ACTIVO o INACTIVO</div>
            </div>
        </form>

        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarRol" form="formActualizarRol" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Rol
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permisos['ver']): ?>
<!-- Modal Ver Rol -->
<div id="modalVerRol" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-2xl max-h-[95vh]">
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                <i class="fas fa-eye mr-2 text-green-600"></i>
                Detalles del Rol
            </h3>
            <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Nombre</label>
                        <p id="verNombre" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Estatus</label>
                        <p id="verEstatus" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500">Descripción</label>
                        <p id="verDescripcion" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Fecha de Creación</label>
                        <p id="verFechaCreacion" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Última Modificación</label>
                        <p id="verUltimaModificacion" class="text-gray-900 font-medium">-</p>
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

<!-- Modal Confirmar Eliminar - Solo si tiene permisos -->
<?php if ($permisos['eliminar']): ?>
<div id="modalConfirmarEliminar" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-[60] p-4">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-md max-h-[95vh]">
    <div class="px-4 md:px-6 py-4 border-b border-gray-200">
      <h3 class="text-xl md:text-2xl font-bold text-gray-800">Confirmar Desactivación</h3>
    </div>
    <div class="px-4 md:px-8 py-6">
      <p class="text-gray-700 text-base md:text-lg">
        ¿Estás seguro de que deseas desactivar el rol <strong id="nombreRolEliminar" class="font-semibold"></strong>? Esta acción cambiará su estatus a INACTIVO.
      </p>
    </div>
    <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
      <button type="button" id="btnCancelarEliminacion" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnConfirmarAccionEliminar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm md:text-base font-medium">
        Desactivar
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Scripts específicos del módulo de roles -->
<script src="<?= base_url('app/assets/js/ayuda/roles-tour.js'); ?>"></script>

<?php footerAdmin($data); ?>