<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de proveedores registrados en el sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <button id="btnAbrirModalRegistrarProveedor"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="mr-1 md:mr-2"></i> Registrar Proveedor
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaProveedores" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
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

<div id="modalRegistrarProveedor"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Proveedor</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarProveedor" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="proveedorNombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorNombre" name="nombre" placeholder="Nombre del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorApellido" class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input type="text" id="proveedorApellido" name="apellido" placeholder="Apellido del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" >
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorIdentificacion" class="block text-sm font-medium text-gray-700 mb-1">Identificación <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorIdentificacion" name="identificacion" placeholder="CI, RIF, Pasaporte" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" >
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorTelefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorTelefono" name="telefono_principal" placeholder="0000-0000000" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" >
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorFechaNacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" id="proveedorFechaNacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorGenero" class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                    <select id="proveedorGenero" name="genero" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar...</option>
                        <option value="MASCULINO">Masculino</option>
                        <option value="FEMENINO">Femenino</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="proveedorCorreo" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" id="proveedorCorreo" name="correo_electronico" placeholder="correo@ejemplo.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="proveedorDireccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="proveedorDireccion" name="direccion" rows="3" placeholder="Dirección completa del proveedor..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="proveedorObservaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="proveedorObservaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarProveedor" form="formRegistrarProveedor" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Proveedor
            </button>
        </div>
    </div>
</div>

<div id="modalActualizarProveedor"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Proveedor</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formActualizarProveedor" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idProveedorActualizar" name="idproveedor">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                 <div>
                    <label for="proveedorNombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorNombreActualizar" name="nombre" placeholder="Nombre del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorApellidoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input type="text" id="proveedorApellidoActualizar" name="apellido" placeholder="Apellido del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorIdentificacionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Identificación <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorIdentificacionActualizar" name="identificacion" placeholder="CI, RIF, Pasaporte" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorTelefonoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorTelefonoActualizar" name="telefono_principal" placeholder="0000-0000000" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorFechaNacimientoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" id="proveedorFechaNacimientoActualizar" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="proveedorGeneroActualizar" class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                    <select id="proveedorGeneroActualizar" name="genero" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar...</option>
                        <option value="MASCULINO">Masculino</option>
                        <option value="FEMENINO">Femenino</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="proveedorCorreoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" id="proveedorCorreoActualizar" name="correo_electronico" placeholder="correo@ejemplo.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="proveedorDireccionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="proveedorDireccionActualizar" name="direccion" rows="3" placeholder="Dirección completa del proveedor..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="proveedorObservacionesActualizar" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="proveedorObservacionesActualizar" name="observaciones" rows="3" placeholder="Observaciones adicionales..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarProveedor" form="formActualizarProveedor" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Proveedor
            </button>
        </div>
    </div>
</div>

<div id="modalVerProveedor" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">
        <div class="bg-white rounded-lg shadow-xl w-full max-h-[calc(95vh-80px)] sm:max-h-[90vh]">
            <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
                <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Proveedor
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
                <div class="mb-6">
                    <h4 class="text-base md:text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-user-tie mr-2 text-green-600"></i>
                        Información Personal
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Nombre</label>
                            <p id="verProveedorNombre" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Apellido</label>
                            <p id="verProveedorApellido" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Identificación</label>
                            <p id="verProveedorIdentificacion" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Teléfono</label>
                            <p id="verProveedorTelefono" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Fecha de Nacimiento</label>
                            <p id="verProveedorFechaNacimiento" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Género</label>
                            <p id="verProveedorGenero" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Estatus</label>
                            <p id="verProveedorEstatus" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Correo Electrónico</label>
                            <p id="verProveedorCorreo" class="text-gray-900 font-medium break-all">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-500">Dirección</label>
                            <p id="verProveedorDireccion" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-500">Observaciones</label>
                            <p id="verProveedorObservaciones" class="text-gray-900 font-medium">-</p>
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