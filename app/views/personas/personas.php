<?php require_once('helpers/helpers.php'); ?>
<?php headerAdmin($data); ?>


<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Personas</h2>
        <input type="text" placeholder="Buscar persona"
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900">Personas Registradas</h1>
        <p class="text-green-500 text-lg">Gestión de personas</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button onclick="abrirModalUsuario()"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    Registrar Persona
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="TablaPersonas" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <th class="py-3">Nº</th>
                            <th class="py-3">Nombre</th>
                            <th class="py-3">Apellido</th>
                            <th class="py-3">Cédula</th>
                            <th class="py-3">RIF</th>
                            <th class="py-3">Tipo</th>
                            <th class="py-3">Género</th>
                            <th class="py-3">Correo</th>
                            <th class="py-3">Teléfono</th>
                            <th class="py-3">Estatus</th>
                            <th class="py-3">Acciones</th>
                        </tr>
                        <div id="loader" class="flex justify-center items-center my-4" style="display: none;">
                            <div class="dot-flashing"></div>
                        </div>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Aquí se cargarán las personas dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>


<!-- Modal Registrar Usuario -->
<div id="usuarioModal"
    class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <!-- CAMBIOS AQUÍ: max-w-3xl, h-auto, max-h-[90vh] -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-3xl h-[93vh]">
        <div class="px-4 py-4 border-b flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-800">Registrar Persona</h3>
            <button onclick="cerrarModalUsuario()" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-6">
            <!-- El formulario ya tiene max-h-[70vh] overflow-y-auto, lo cual es bueno -->
            <form id="formRegistrarUsuario" class="px-8 py-6 h-[65vh] overflow-y-auto">
                <div class="flex flex-wrap gap-4">
                    <!-- Campos para persona -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="nombrePersona" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" id="nombrePersona" name="nombre"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>

                    <div class="flex-1 min-w-[45%]">
                        <label for="apellidoPersona"
                            class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                        <input type="text" id="apellidoPersona" name="apellido"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="cedulaPersona" class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                        <input type="text" id="cedulaPersona" name="cedula"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>

                    <div class="flex-1 min-w-[45%]">
                        <label for="rifPersona" class="block text-sm font-medium text-gray-700 mb-1">RIF</label>
                        <input type="text" id="rifPersona" name="rif"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="telefonoPersona"
                            class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" id="telefonoPersona" name="telefono_principal"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>

                    <div class="flex-1 min-w-[45%]">
                        <label for="tipoPersona" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select id="tipoPersona" name="tipo"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                            <option selected>seleccione un tipo</option>
                            <option value="comprador">Comprador</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="empleado">Empleado</option>
                        </select>
                    </div>
                </div>

                <!-- Nuevos Campos -->
                <div class="flex flex-wrap gap-4">
                    <!-- Género -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="generoPersona" class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                        <select id="generoPersona" name="genero"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                            <option selected> seleccione un genero</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <!-- Fecha de Nacimiento -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="fechaNacimientoPersona" class="block text-sm font-medium text-gray-700 mb-1">Fecha
                            de Nacimiento</label>
                        <input type="date" id="fechaNacimientoPersona" name="fecha_nacimiento"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <!-- Estado -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="estadoPersona" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <input type="text" id="estadoPersona" name="estado"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>

                    <!-- Ciudad -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="ciudadPersona" class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" id="ciudadPersona" name="ciudad"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <!-- País -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="paisPersona" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" id="paisPersona" name="pais"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>

                    <!-- Observaciones -->
                    <div class="flex-1 min-w-[45%]">
                        <label for="observacionesPersona"
                            class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea id="observacionesPersona" name="observaciones"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-4 w-full">
                    <div class="flex items-center">
                        <label for="crearUsuario" class="block text-sm font-medium text-gray-700 mb-1 mr-2">¿Crear
                            Usuario?</label>
                        <input type="checkbox" id="crearUsuario" name="crear_usuario"
                            class="w-auto border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            onchange="toggleUsuarioCampos()">
                    </div>
                </div>

                <!-- Campos de Correo, Contraseña y Rol, ocultos hasta marcar el checkbox -->
                <div id="usuarioCampos" class="hidden">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[45%]">
                            <label for="correoPersona" class="block text-sm font-medium text-gray-700 mb-1">Correo
                                Electrónico</label>
                            <input type="email" id="correoPersona" name="correo_electronico"
                                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>

                        <div class="flex-1 min-w-[45%]">
                            <label for="clavePersona"
                                class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                            <input type="password" id="clavePersona" name="clave"
                                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[45%]">
                            <label for="rol" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                            <select id="rol" name="rol"
                                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                                required>
                                <option selected>seleccione un Rol</option>
                                <option value="1">Administrador</option>
                                <option value="2">Empleado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
            <!-- Pie del Modal (Acciones) -->
                <div class=" mr-4 bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="cerrarModalUsuario()" class="btn-neutral px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
                        Cancelar
                    </button>
                    <button type="button" id="submitUsuario" class="btn-success px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
                        <i class="fas fa-save mr-2"> </i> Guardar Compra
                    </button>
                </div>
        </div>
    </div>
</div>



<script>
    function toggleUsuarioCampos() {
        var checkBox = document.getElementById("crearUsuario");
        var camposUsuario = document.getElementById("usuarioCampos");

        if (checkBox.checked) {
            camposUsuario.classList.remove("hidden");
        } else {
            camposUsuario.classList.add("hidden");
        }
    }
</script>


<!-- Modal Actualizar Usuario -->
<div id="actualizarUsuarioModal"
    class="fixed inset-0 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
    <!-- CAMBIOS AQUÍ: h-auto, max-h-[90vh] -->
    <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-2xl relative h-auto max-h-[90vh]">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-2xl font-bold text-gray-800">Actualizar Usuario</h3>
            <button onclick="cerrarModaleditpersona()" class="text-gray-600 hover:text-gray-800 absolute top-4 right-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-6">
             <!-- CAMBIOS AQUÍ: max-h-[70vh] overflow-y-auto -->
            <form id="formActualizarUsuario" class="space-y-4 max-h-[70vh] overflow-y-auto">
                <input type="hidden" id="idUsuario" name="id">

                <!-- Nombre y Apellido -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="nombreActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" id="nombreActualizar" name="nombre"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                    <div class="flex-1 min-w-[45%]">
                        <label for="apellidoActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                        <input type="text" id="apellidoActualizar" name="apellido"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                </div>

                <!-- Cédula y RIF -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="cedulaActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                        <input type="text" id="cedulaActualizar" name="cedula"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                    <div class="flex-1 min-w-[45%]">
                        <label for="rifActualizar" class="block text-sm font-medium text-gray-700 mb-1">RIF</label>
                        <input type="text" id="rifActualizar" name="rif"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                </div>

                <!-- Teléfono y Tipo -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="telefonoActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" id="telefonoActualizar" name="telefono_principal"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                    <div class="flex-1 min-w-[45%]">
                        <label for="tipoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select id="tipoActualizar" name="tipo"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                            <option selected disabled>Seleccione un tipo</option>
                            <option value="comprador">Comprador</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="empleado">Empleado</option>
                        </select>
                    </div>
                </div>

                <!-- Género y Fecha de Nacimiento -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="generoActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Género</label>
                        <select id="generoActualizar" name="genero"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                            <option selected disabled>Seleccione un género</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[45%]">
                        <label for="fechaNacimientoActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                        <input type="date" id="fechaNacimientoActualizar" name="fecha_nacimiento"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                </div>

                <!-- Estado y Ciudad -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="estadoActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <input type="text" id="estadoActualizar" name="estado"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                    <div class="flex-1 min-w-[45%]">
                        <label for="ciudadActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" id="ciudadActualizar" name="ciudad"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                </div>

                <!-- País y Observaciones -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="paisActualizar" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" id="paisActualizar" name="pais"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>
                    <div class="flex-1 min-w-[45%]">
                        <label for="observacionesActualizar"
                            class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea id="observacionesActualizar" name="observaciones"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                    </div>
                </div>

                <!-- Campos de Usuario -->
                <div id="usuarioActualizarCampos">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[45%]">
                            <label for="correoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Correo
                                Electrónico</label>
                            <input type="email" id="correoActualizar" name="correo_electronico"
                                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div class="flex-1 min-w-[45%]">
                            <label for="claveActualizar"
                                class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                            <input type="password" id="claveActualizar" name="clave"
                                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                        <input type="hidden" id="usuarioRol" value="root" />
                    <div id="rolContainer" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[45%]">
                            <label for="rolActualizar" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                            <select id="rolActualizar" name="rol"
                                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option selected disabled>Seleccione un Rol</option>
                                <option value="1">Administrador</option>
                                <option value="2">Empleado</option>
                            </select>
                        </div>
                    </div>

                </div>

                <!-- Botones -->
                <div class="flex justify-end pt-4">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('formActualizarUsuario').addEventListener('submit', async function (e) {
        e.preventDefault();

        const datos = {
            id: document.getElementById('idUsuario').value.trim(),
            nombre: document.getElementById('nombreActualizar').value.trim(),
            apellido: document.getElementById('apellidoActualizar').value.trim(),
            cedula: document.getElementById('cedulaActualizar').value.trim(),
            rif: document.getElementById('rifActualizar').value.trim(),
            telefono_principal: document.getElementById('telefonoActualizar').value.trim(),
            tipo: document.getElementById('tipoActualizar').value,
            genero: document.getElementById('generoActualizar').value,
            fecha_nacimiento: document.getElementById('fechaNacimientoActualizar').value,
            estado: document.getElementById('estadoActualizar').value.trim(),
            ciudad: document.getElementById('ciudadActualizar').value.trim(),
            pais: document.getElementById('paisActualizar').value.trim(),
            observaciones: document.getElementById('observacionesActualizar').value.trim(),
            correo_electronico: document.getElementById('correoActualizar').value.trim(),
            clave: document.getElementById('claveActualizar').value,
            rol: document.getElementById('rolActualizar').value
        };

        // Reglas de validación
        const regexNombre = /^[a-zA-ZÁÉÍÓÚÑáéíóúñ\s]{2,50}$/;
        const regexCedula = /^[0-9]{5,12}$/;
        const regexRif = /^[VEJPG]-\d{6,10}$/;
        const regexTelefono = /^[0-9]{10,15}$/;
        const regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const regexClave = /^.{6,16}$/;

        if (!datos.nombre || !regexNombre.test(datos.nombre)) {
            alert('Nombre inválido. Solo letras y espacios (2-50 caracteres).');
            return;
        }

        if (!datos.apellido || !regexNombre.test(datos.apellido)) {
            alert('Apellido inválido. Solo letras y espacios (2-50 caracteres).');
            return;
        }

        if (!datos.cedula || !regexCedula.test(datos.cedula)) {
            alert('Cédula inválida. Solo números (5-12 dígitos).');
            return;
        }

        if (!datos.rif || !regexRif.test(datos.rif)) {
            alert('RIF inválido. Ejemplo válido: V-12345678.');
            return;
        }

        if (!datos.telefono_principal || !regexTelefono.test(datos.telefono_principal)) {
            alert('Teléfono inválido. Solo números (10-15 dígitos).');
            return;
        }

        if (!datos.correo_electronico || !regexCorreo.test(datos.correo_electronico)) {
            alert('Correo electrónico inválido.');
            return;
        }

        if (!datos.clave || !regexClave.test(datos.clave)) {
            alert('Clave inválida. Debe tener entre 6 y 16 caracteres.');
            return;
        }

      

        // Enviar si pasa todas las validaciones
        try {
            const response = await fetch('personas/editar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(datos)
            });

            if (response.ok) {
                const resultado = await response.json();
                console.log('Usuario actualizado correctamente:', resultado);
                alert('Usuario actualizado exitosamente');
                // Puedes cerrar el modal aquí
            } else {
                const error = await response.json();
                console.error('Error al actualizar:', error);
                alert('Error al actualizar el usuario');
            }
        } catch (err) {
            console.error('Error de red:', err);
            alert('Error de red al enviar la solicitud.');
        }
    });
</script>









<!-- Modal Eliminar -->
<div id="modalEliminar" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <div id="loaderEliminar" class="flex justify-center items-center my-4" style="display: none;">
            <div class="dot-flashing"></div>
        </div>

        <h2 class="text-xl font-semibold text-gray-800 mb-4">¿Seguro que quieres desactivar la persona <span
                id="nombrePersonaEliminar" class="text-red-500 font-bold"></span>?</h2>

        <div class="flex justify-end space-x-4 mt-6">
            <button class="px-4 py-2 bg-gray-400 text-white rounded" onclick="cerrarModalEliminar()">Cancelar</button>
            <button id="botonEliminar" class="px-4 py-2 bg-red-600 text-white rounded"
                onclick="confirmarEliminar()">Eliminar</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', cargarPersonas);

    function cargarPersonas() {
        const loader = document.getElementById('loader');
        const tbody = document.querySelector('#TablaPersonas tbody');

        loader.style.display = 'flex';
        tbody.innerHTML = '';  // Limpiar el contenido de la tabla

        fetch('Personas/ConsultarPersonas', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';

                // Verificar si la respuesta es exitosa
                if (data.success) {
                    if (data.personas && data.personas.length > 0) {
                        // Iterar sobre cada persona en el array de personas
                        data.personas.forEach((persona, index) => {
                            let row = `
                        <tr class="border-b">
                            <td class="py-2">${index + 1}</td>
                            <td class="py-2">${persona.persona_nombre}</td>
                            <td class="py-2">${persona.persona_apellido}</td>
                            <td class="py-2">${persona.cedula || 'N/A'}</td>
                            <td class="py-2">${persona.rif || 'N/A'}</td>
                            <td class="py-2">${persona.tipo || 'N/A'}</td>
                            <td class="py-2">${persona.genero || 'N/A'}</td>
                            <td class="py-2">${persona.correo}</td>
                            <td class="py-2">${persona.telefono || 'N/A'}</td>
                            <td class="py-2">${persona.persona_estatus}</td>
                            <td class="py-2">
                                <button class="bg-blue-500 text-white px-2 py-1 rounded editar-persona" 
                                        data-id="${persona.idpersona}" 
                                        data-nombre="${persona.persona_nombre}" 
                                        data-apellido="${persona.persona_apellido}" 
                                        onclick="obtenerDatosUsuario(${persona.idpersona})">
                                    <i class="fas fa-pencil-alt"></i> <!-- Icono de lápiz -->
                                </button>`;

                            // Mostrar el botón de eliminar solo si la persona tiene permiso
                            if (persona.mostrar_boton_eliminar) {
                                row += `
                            <button class="bg-red-500 text-white px-2 py-1 rounded eliminar-persona" 
                                    data-id="${persona.idpersona}" 
                                    data-nombre="${persona.persona_nombre}" 
                                    onclick="modalEliminar(${persona.idpersona})">
                                <i class="fas fa-trash-alt"></i> <!-- Icono de basurero -->
                            </button>`;
                            } else {
                                // Si no puede eliminar, no mostrar el botón o poner un texto
                                row += `<span class="text-gray-500"></span>`;
                            }

                            row += `</td></tr>`;
                            tbody.insertAdjacentHTML('beforeend', row);
                        });
                    } else {
                        tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4">No Hay Personas Registradas.</td></tr>`;
                    }
                } else {
                    // Si la respuesta del backend no es exitosa, mostrar el mensaje de error
                    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-red-500">${data.message}</td></tr>`;
                    console.error('Error en backend:', data.message);
                }
            })
            .catch(error => {
                loader.style.display = 'none';
                tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-red-500">Error al cargar los datos. Intenta nuevamente.</td></tr>`;
                console.error('Error de conexión:', error);
            });
    }

    function abrirModalUsuario() {
        const modal = document.getElementById("usuarioModal");
        modal.classList.remove("opacity-0", "pointer-events-none");
        modal.classList.add("opacity-100", "pointer-events-auto");
    }


    function cerrarModalUsuario() {
        const modal = document.getElementById("usuarioModal");
        modal.classList.remove("opacity-100", "pointer-events-auto");
        modal.classList.add("opacity-0", "pointer-events-none");
    }

 async function obtenerDatosUsuario(id) {
    const loader = document.getElementById('loader');  // Asume que tienes un loader en tu HTML
    const modal = document.getElementById("actualizarUsuarioModal");

    try {
        console.log('ID recibido:', id);

        // Mostrar loader y limpiar campos
        loader.style.display = 'flex';

        // Limpia los campos antes de cargar
        document.getElementById('idUsuario').value = '';
        document.getElementById('nombreActualizar').value = '';
        document.getElementById('apellidoActualizar').value = '';
        document.getElementById('cedulaActualizar').value = '';
        document.getElementById('rifActualizar').value = '';
        document.getElementById('telefonoActualizar').value = '';
        document.getElementById('tipoActualizar').value = '';
        document.getElementById('generoActualizar').value = '';
        document.getElementById('fechaNacimientoActualizar').value = '';
        document.getElementById('estadoActualizar').value = '';
        document.getElementById('ciudadActualizar').value = '';
        document.getElementById('paisActualizar').value = '';
        document.getElementById('observacionesActualizar').value = '';
        document.getElementById('correoActualizar').value = '';
        document.getElementById('rolActualizar').value = '';

        // Fetch datos
        const response = await fetch(`personas/obtenerPersonaPorId?id=${id}`);

        if (!response.ok) {
            throw new Error('Error al obtener los datos del usuario');
        }

        const data = await response.json();

        loader.style.display = 'none';  // Ocultar loader

        if (data.success) {
            const usuario = data.usuario;
            // Rellenar campos con la data recibida
            document.getElementById('idUsuario').value = usuario.idpersona || '';
            document.getElementById('nombreActualizar').value = usuario.persona_nombre || '';
            document.getElementById('apellidoActualizar').value = usuario.persona_apellido || '';
            document.getElementById('cedulaActualizar').value = usuario.persona_cedula || '';
            document.getElementById('rifActualizar').value = usuario.persona_rif || '';
            document.getElementById('telefonoActualizar').value = usuario.telefono_principal || '';
            document.getElementById('tipoActualizar').value = usuario.persona_tipo || '';
            document.getElementById('generoActualizar').value = usuario.persona_genero || '';
            document.getElementById('fechaNacimientoActualizar').value = usuario.persona_fecha || '';
            document.getElementById('estadoActualizar').value = usuario.persona_estado || '';
            document.getElementById('ciudadActualizar').value = usuario.persona_ciudad || '';
            document.getElementById('paisActualizar').value = usuario.persona_pais || '';
            document.getElementById('observacionesActualizar').value = usuario.persona_observaciones || '';
            document.getElementById('correoActualizar').value = usuario.persona_correo || '';

            // Mapa de roles con keys en minúsculas
            const rolesMap = {
                'root': '3',
                'administrador': '1',
                'empleado': '2'
            };
            document.getElementById('rolActualizar').value = rolesMap[(usuario.rol || '').toLowerCase()] || '';

            // Control de visibilidad del contenedor del rol
            const rolContainer = document.getElementById('rolContainer'); // El contenedor del campo "Rol"
            const rolUsuarioAutenticado = (document.getElementById('usuarioRol').value || '').toLowerCase(); // Rol del usuario logueado
            const rolUsuarioEditado = (usuario.rol || '').toLowerCase(); // Rol del usuario que se edita

            if (rolUsuarioAutenticado === 'root' && rolUsuarioEditado !== 'root') {
                rolContainer.style.display = 'flex';  // Mostrar si soy root y el usuario editado NO es root
            } else {
                rolContainer.style.display = 'none';  // Ocultar en cualquier otro caso
            }

            abrirModaleditpersona();
        } else {
            alert('No se encontró la persona: ' + (data.message || ''));
        }
    } catch (error) {
        loader.style.display = 'none';
        alert('Error al obtener los datos del usuario');
        console.error(error);
    }
}





    function abrirModaleditpersona() {
        const modal = document.getElementById("actualizarUsuarioModal");
        modal.classList.remove("opacity-0", "pointer-events-none");
        modal.classList.add("opacity-100", "pointer-events-auto");
    }


    function cerrarModaleditpersona() {
        const modal = document.getElementById("actualizarUsuarioModal");
        modal.classList.remove("opacity-100", "pointer-events-auto");
        modal.classList.add("opacity-0", "pointer-events-none");
    }




    function modalEliminar(id) {
        const modal = document.getElementById('modalEliminar');
        const nombrePersonaEliminar = document.getElementById('nombrePersonaEliminar');
        const loaderEliminar = document.getElementById('loaderEliminar');
        const botonEliminar = document.getElementById('botonEliminar');

        modal.classList.remove('opacity-0', 'pointer-events-none'); // Abrir modal

        if (loaderEliminar) loaderEliminar.style.display = 'flex';
        if (nombrePersonaEliminar) nombrePersonaEliminar.textContent = ''; // Limpiar nombre de la persona

        fetch(`Personas/consultarunaPersona?id=${id}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (loaderEliminar) loaderEliminar.style.display = 'none'; // Ocultar el loader

                if (data.success) {
                    const persona = data.persona;
                    if (nombrePersonaEliminar) {
                        nombrePersonaEliminar.textContent = `${persona.nombre} ${persona.apellido}`;
                    }
                    if (botonEliminar) {
                        botonEliminar.setAttribute('data-id', id);
                    }
                } else {
                    console.error('Error al cargar la persona para eliminar:', data.message);
                    if (nombrePersonaEliminar) {
                        nombrePersonaEliminar.textContent = 'Error al cargar el nombre de la persona.';
                    }
                }
            })
            .catch(error => {
                console.error('Error de conexión al buscar persona para eliminar:', error);
                if (loaderEliminar) loaderEliminar.style.display = 'none';
                if (nombrePersonaEliminar) {
                    nombrePersonaEliminar.textContent = 'Error de conexión.';
                }
            });
    }

    function cerrarModalEliminar() {
        const modal = document.getElementById('modalEliminar');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    function confirmarEliminar() {
        const botonEliminar = document.getElementById('botonEliminar');
        const idPersona = botonEliminar.getAttribute('data-id');

        if (!idPersona) {
            alert('No se pudo obtener el ID de la persona.');
            return;
        }

        fetch(`Personas/eliminar?id=${idPersona}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Persona desactivada correctamente.');
                    cargarPersonas(); // Recargar las personas
                    cerrarModalEliminar(); // Cerrar modal
                } else {
                    alert('Error al eliminar la persona: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error de conexión al eliminar persona:', error);
                alert('Error de conexión al eliminar la persona.');
            });
    }
</script>


<script>
    document.getElementById('formRegistrarUsuario').addEventListener('submit', async function (event) {
        event.preventDefault();

        // Obtener valores del formulario
        const nombre = document.getElementById('nombrePersona').value.trim();
        const apellido = document.getElementById('apellidoPersona').value.trim();
        const cedula = document.getElementById('cedulaPersona').value.trim();
        const rif = document.getElementById('rifPersona').value.trim();
        const telefono = document.getElementById('telefonoPersona').value.trim();
        const tipo = document.getElementById('tipoPersona').value;
        const genero = document.getElementById('generoPersona').value;
        const fechaNacimiento = document.getElementById('fechaNacimientoPersona').value;
        const estado = document.getElementById('estadoPersona').value.trim();
        const ciudad = document.getElementById('ciudadPersona').value.trim();
        const pais = document.getElementById('paisPersona').value.trim();
        const observaciones = document.getElementById('observacionesPersona').value.trim();
        const crearUsuario = document.getElementById('crearUsuario').checked;
        const correo = document.getElementById('correoPersona').value.trim();
        const clave = document.getElementById('clavePersona').value;
        const rol = document.getElementById('rol').value;

        // Validaciones simples
        if (!nombre || !apellido || !cedula || !rif || !telefono || tipo === "seleccione un tipo" || genero === "seleccione un genero" || !fechaNacimiento || !estado || !ciudad || !pais) {
            Swal.fire('Campos requeridos', 'Por favor complete todos los campos obligatorios.', 'warning');
            return;
        }

        if (crearUsuario) {
            if (!correo || !clave) {
                Swal.fire('Faltan datos de usuario', 'Si desea crear un usuario, debe ingresar el correo y la clave.', 'warning');
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(correo)) {
                Swal.fire('Correo inválido', 'Ingrese un correo electrónico válido.', 'error');
                return;
            }
            if (clave.length < 6 || clave.length > 16) {
                Swal.fire('Clave inválida', 'La contraseña debe tener entre 6 y 16 caracteres.', 'error');
                return;
            }
        }

        // Crear el objeto con los datos del formulario
        const data = {
            nombre: nombre,
            apellido: apellido,
            cedula: cedula,
            rif: rif,
            telefono: telefono,
            tipo: tipo,
            genero: genero,
            fecha_nacimiento: fechaNacimiento,
            estado: estado,
            ciudad: ciudad,
            pais: pais,
            observaciones: observaciones,
            crear_usuario: crearUsuario ? '1' : '0',
            rol: rol
        };

        if (crearUsuario) {
            data.correo_electronico = correo;
            data.clave = clave;
            data.rol = rol;
        }

        console.log(data);
        try {
            // Realizar la petición utilizando el método POST y enviar los datos como JSON
            const response = await fetch('personas/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'  // Cambiar a application/json
                },
                body: JSON.stringify(data)  // Convertir el objeto a JSON
            });

            const result = await response.json();

            console.log(result);

            if (result.success) {
                Swal.fire('¡Éxito!', result.message || 'Usuario registrado correctamente.', 'success');
                document.getElementById('formRegistrarUsuario').reset();
                cargarPersonas();
            } else {
                Swal.fire('Error', result.message || 'Ocurrió un error al registrar el usuario.', 'error');
            }

        } catch (error) {
            Swal.fire('Error de red', 'No se pudo conectar con el servidor.', 'error');
            console.error(error);
        }
    });



</script>



<style>
    .dot-flashing {
        position: relative;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background-color: #4b5563;
        animation: dot-flashing 1s infinite linear alternate;
    }

    @keyframes dot-flashing {
        0% {
            background-color: #4b5563;
        }

        50%,
        100% {
            background-color: #d1d5db;
        }
    }

    .dot-flashing::before,
    .dot-flashing::after {
        content: '';
        display: inline-block;
        position: absolute;
        top: 0;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background-color: #4b5563;
    }

    .dot-flashing::before {
        left: -1.5rem;
        animation-delay: 0s;
    }

    .dot-flashing::after {
        left: 1.5rem;
        animation-delay: 0s;
    }
</style>
