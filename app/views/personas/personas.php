<?php headerAdmin($data);?>
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Personas</h2>
        <input type="text" placeholder="Buscar en página..."
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-500 text-lg">Listado de personas registradas en el sistema</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button id="btnAbrirModalRegistrarPersona"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    <i class="fas fa-plus mr-2"></i> Registrar Persona
                </button>
            </div>

            <div class="overflow-x-auto">
                <!-- DatatABLE-->
                <table id="TablaPersonas" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                    </tbody>
                </table>
                <div id="loaderTablePersonas" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registrar Persona -->
<div id="modalRegistrarPersona"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-2xl font-bold text-gray-800">Registrar Persona</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formRegistrarPersona" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="nombrePersona" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="nombrePersona" name="nombre" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="apellidoPersona" class="block text-sm font-medium text-gray-700 mb-1">Apellido <span class="text-red-500">*</span></label>
                    <input type="text" id="apellidoPersona" name="apellido" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="identificacionPersona" class="block text-sm font-medium text-gray-700 mb-1">Identificación (Cédula) <span class="text-red-500">*</span></label>
                    <input type="text" id="identificacionPersona" name="identificacion" placeholder="Ej: V-12345678 o 12345678" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="telefonoPersona" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
                    <input type="text" id="telefonoPersona" name="telefono_principal" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="generoPersona" class="block text-sm font-medium text-gray-700 mb-1">Género <span class="text-red-500">*</span></label>
                    <select id="generoPersona" name="genero" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un género</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="otro">Otro</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="fechaNacimientoPersona" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" id="fechaNacimientoPersona" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="mb-4">
                <label for="correoElectronicoPersona" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico (Personal) <span class="text-red-500">*</span></label>
                <input type="email" id="correoElectronicoPersona" name="correo_electronico" placeholder="ejemplo@dominio.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="direccionPersona" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="direccionPersona" name="direccion" rows="3" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="observacionesPersona" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observacionesPersona" name="observaciones" rows="3" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <!-- Checkbox para Crear Usuario -->
            <div class="flex items-center mb-6">
                <input type="checkbox" id="crearUsuario" name="crear_usuario_flag" value="1" class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500"> <!-- onchange eliminado -->
                <label for="crearUsuario" class="ml-2 block text-sm text-gray-900">¿Crear un Usuario para esta Persona?</label>
            </div>
            <div id="usuarioCamposRegistrar" class="hidden border-t border-gray-200 pt-6 mt-6">
                <h4 class="text-lg font-semibold text-gray-700 mb-4">Datos de Acceso del Usuario</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                    <div>
                        <label for="correoUsuarioPersona" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico (Login) <span class="text-red-500">*</span></label>
                        <input type="email" id="correoUsuarioPersona" name="correo_electronico_usuario" placeholder="usuario@dominio.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <div class="text-red-500 text-xs mt-1 error-message"></div>
                    </div>
                    <div>
                        <label for="claveUsuarioPersona" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <span class="text-red-500">*</span></label>
                        <input type="password" id="claveUsuarioPersona" name="clave_usuario" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <div class="text-red-500 text-xs mt-1 error-message"></div>
                    </div>
                </div>
                <div>
                    <label for="rolUsuarioPersona" class="block text-sm font-medium text-gray-700 mb-1">Rol de Usuario <span class="text-red-500">*</span></label>
                    <select id="rolUsuarioPersona" name="idrol_usuario" class="w-full md:w-1/2 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Seleccione un Rol</option>
                        <option value="1">Administrador</option>
                        <option value="2">Empleado</option>
                        <option value="3">Cliente</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>


        </form>
        <!-- Pie del Modal -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarPersona" form="formRegistrarPersona" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Guardar Persona
            </button>
        </div>
    </div>
</div>

<!-- Modal Actualizar Persona -->
<div id="modalActualizarPersona"
    class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-2xl font-bold text-gray-800">Actualizar Persona</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formActualizarPersona" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="idPersonaActualizar" name="idpersona_pk">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="nombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="nombreActualizar" name="nombre" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="apellidoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Apellido <span class="text-red-500">*</span></label>
                    <input type="text" id="apellidoActualizar" name="apellido" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="cedulaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Identificación (Cédula) <span class="text-red-500">*</span></label>
                    <input type="text" id="cedulaActualizar" name="identificacion" placeholder="Ej: V-12345678 o 12345678" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="telefonoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
                    <input type="text" id="telefonoActualizar" name="telefono_principal" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="tipoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Género <span class="text-red-500">*</span></label>
                    <select id="tipoActualizar" name="genero" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="">Seleccione un género</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="otro">Otro</option>
                        <option value="no_especificado">Prefiero no decirlo</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="fechaNacimientoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" id="fechaNacimientoActualizar" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div class="mb-4">
                <label for="correoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico (Personal)</label>
                <input type="email" id="correoActualizar" name="correo_electronico" placeholder="ejemplo@dominio.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                <div>
                    <label for="direccionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea id="direccionActualizar" name="direccion" rows="3" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="Observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observacionesActualizar" name="observaciones" rows="3" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>

            <div id="usuarioCamposActualizar" class="border-t border-gray-200 pt-6 mt-6">
                <h4 class="text-lg font-semibold text-gray-700 mb-4">Datos de Acceso del Usuario</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                    <div>
                        <label for="correoUsuarioActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico (Login)</label>
                        <input type="email" id="correoUsuarioActualizar" name="correo_electronico_usuario" placeholder="usuario@dominio.com" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <div class="text-red-500 text-xs mt-1 error-message"></div>
                    </div>
                    <div>
                        <label for="claveActualizar" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <small>(dejar en blanco para no cambiar)</small></label>
                        <input type="password" id="claveActualizar" name="clave_usuario" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <div class="text-red-500 text-xs mt-1 error-message"></div>
                    </div>
                </div>
                <div>
                    <label for="rolActualizar" class="block text-sm font-medium text-gray-700 mb-1">Rol de Usuario</label>
                    <select id="rolActualizar" name="idrol_usuario" class="w-full md:w-1/2 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Seleccione un Rol</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button type="button" id="btnCancelarModalActualizar" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnActualizarPersona" form="formActualizarPersona" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                <i class="fas fa-save mr-2"></i> Actualizar Persona
            </button>
        </div>
    </div>
</div>

<!-- MODAL VER PERSONA -->
<div id="modalVerPersona" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-2xl max-h-[95vh]">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh]">
            
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles de la Persona
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-user mr-2 text-green-600"></i>
                        Información Personal
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</label>
                            <p id="verNombre" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Apellido</label>
                            <p id="verApellido" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Cédula</label>
                            <p id="verCedula" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Género</label>
                            <p id="verGenero" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Nacimiento</label>
                            <p id="verFechaNacimiento" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono</label>
                            <p id="verTelefono" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Correo Personal</label>
                            <p id="verCorreo" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Estado</label>
                            <p id="verEstado" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Observaciones</label>
                            <p id="verObser" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Dirección</label>
                            <p id="verDire" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-key mr-2 text-purple-600"></i>
                        Información de Usuario
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Tiene Usuario</label>
                            <p id="verTieneUsuario" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Correo de Usuario</label>
                            <p id="verUsuarioCorreo" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Rol</label>
                            <p id="verUsuarioRol" class="text-gray-900 dark:text-white font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Estatus Usuario</label>
                            <p id="verUsuarioEstatus" class="text-gray-900 dark:text-white font-medium">-</p>
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
        ¿Estás seguro de que deseas desactivar a la persona <strong id="nombrePersonaEliminar" class="font-semibold"></strong>? Esta acción cambiará su estatus a INACTIVO.
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



<?php footerAdmin($data); // Asumo que $data se pasa desde el controlador ?>
