<?php headerAdmin($data);?>

<!-- Input hidden para el rol del usuario actual (usado por JS) -->
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
        <input type="text" placeholder="Buscar en página..."
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-500 text-lg">Listado de proveedores registrados en el sistema</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button id="btnAbrirModalRegistrarProveedor"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="mr-2"></i> Registrar Proveedor
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="TablaProveedores" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <!-- Los títulos se definen en la configuración de DataTable en JS -->
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Las filas se cargarán dinámicamente con DataTables -->
                    </tbody>
                </table>
                <div id="loaderTableProveedores" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Proveedor -->
<div id="modalRegistrarProveedor"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-2xl font-bold text-gray-800">Registrar Proveedor</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarProveedor" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nombre -->
                <div class="mb-4">
                    <label for="proveedorNombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorNombre" name="nombre" placeholder="Nombre del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Apellido -->
                <div class="mb-4">
                    <label for="proveedorApellido" class="block text-sm font-medium text-gray-700 mb-1">Apellido <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorApellido" name="apellido" placeholder="Apellido del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Identificación -->
                <div class="mb-4">
                    <label for="proveedorIdentificacion" class="block text-sm font-medium text-gray-700 mb-1">Identificación <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorIdentificacion" name="identificacion" placeholder="CI, RIF, Pasaporte" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Teléfono -->
                <div class="mb-4">
                    <label for="proveedorTelefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorTelefono" name="telefono_principal" placeholder="0000-0000000" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Fecha de Nacimiento -->
                <div class="mb-4">
                    <label for="proveedorFechaNacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" id="proveedorFechaNacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Género -->
                <div class="mb-4">
                    <label for="proveedorGenero" class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                    <select id="proveedorGenero" name="genero" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Seleccionar...</option>
                        <option value="MASCULINO">Masculino</option>
                        <option value="FEMENINO">Femenino</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Correo Electrónico -->
                <div class="mb-4 md:col-span-2">
                    <label for="proveedorCorreo" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" id="proveedorCorreo" name="correo_electronico" placeholder="correo@ejemplo.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Dirección -->
                <div class="mb-4 md:col-span-2">
                    <label for="proveedorDireccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="proveedorDireccion" name="direccion" rows="3" placeholder="Dirección completa del proveedor..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Observaciones -->
                <div class="mb-4 md:col-span-2">
                    <label for="proveedorObservaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="proveedorObservaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarProveedor" form="formRegistrarProveedor" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Proveedor
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Proveedor -->
<div id="modalActualizarProveedor"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-2xl font-bold text-gray-800">Actualizar Proveedor</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarProveedor" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="idProveedorActualizar" name="idproveedor">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nombre -->
                <div class="mb-4">
                    <label for="proveedorNombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorNombreActualizar" name="nombre" placeholder="Nombre del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Apellido -->
                <div class="mb-4">
                    <label for="proveedorApellidoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Apellido <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorApellidoActualizar" name="apellido" placeholder="Apellido del proveedor" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Identificación -->
                <div class="mb-4">
                    <label for="proveedorIdentificacionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Identificación <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorIdentificacionActualizar" name="identificacion" placeholder="CI, RIF, Pasaporte" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Teléfono -->
                <div class="mb-4">
                    <label for="proveedorTelefonoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
                    <input type="text" id="proveedorTelefonoActualizar" name="telefono_principal" placeholder="0000-0000000" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Fecha de Nacimiento -->
                <div class="mb-4">
                    <label for="proveedorFechaNacimientoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" id="proveedorFechaNacimientoActualizar" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Género -->
                <div class="mb-4">
                    <label for="proveedorGeneroActualizar" class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                    <select id="proveedorGeneroActualizar" name="genero" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Seleccionar...</option>
                        <option value="MASCULINO">Masculino</option>
                        <option value="FEMENINO">Femenino</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Correo Electrónico -->
                <div class="mb-4 md:col-span-2">
                    <label for="proveedorCorreoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" id="proveedorCorreoActualizar" name="correo_electronico" placeholder="correo@ejemplo.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Dirección -->
                <div class="mb-4 md:col-span-2">
                    <label for="proveedorDireccionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="proveedorDireccionActualizar" name="direccion" rows="3" placeholder="Dirección completa del proveedor..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Observaciones -->
                <div class="mb-4 md:col-span-2">
                    <label for="proveedorObservacionesActualizar" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="proveedorObservacionesActualizar" name="observaciones" rows="3" placeholder="Observaciones adicionales..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>

        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarProveedor" form="formActualizarProveedor" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Actualizar Proveedor
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Proveedor -->
<div id="modalVerProveedor" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl max-h-[95vh]">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh]">
            <!-- Header del Modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Proveedor
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <!-- Información del Proveedor -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-user-tie mr-2 text-green-600"></i>
                        Información Personal
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</label>
                            <p id="verProveedorNombre" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Apellido</label>
                            <p id="verProveedorApellido" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Identificación</label>
                            <p id="verProveedorIdentificacion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono</label>
                            <p id="verProveedorTelefono" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Nacimiento</label>
                            <p id="verProveedorFechaNacimiento" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Género</label>
                            <p id="verProveedorGenero" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Estatus</label>
                            <p id="verProveedorEstatus" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Correo Electrónico</label>
                            <p id="verProveedorCorreo" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Dirección</label>
                            <p id="verProveedorDireccion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Observaciones</label>
                            <p id="verProveedorObservaciones" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</label>
                            <p id="verProveedorFechaCreacion" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Última Modificación</label>
                            <p id="verProveedorFechaModificacion" class="text-gray-900 dark:text-white font-medium">-</p>
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