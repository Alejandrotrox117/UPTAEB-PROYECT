<?php require_once('helpers/helpers.php');
headerAdmin($data); ?>

<!-- Scripts y estilos externos -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Administración de Modulos</h2>
        <input type="text" placeholder="Buscar permiso"
            class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
        <h1 class="text-3xl font-bold text-gray-900">Modulos</h1>
        <p class="text-green-500 text-lg">Gestión de Modulos</p>

        <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <button onclick="abrirModalmodulo()"
                    class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    Registrar Modulos
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="TablaRoles" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <th class="py-3">Nº</th>
                            <th class="py-3">Titulo</th>
                            <th class="py-3">Descripcion</th>
                            <th class="py-3">estatus</th>
                            <th class="py-3">Fecha de creacion </th>
                            <th class="py-3">fecha de modificacion</th>
                            <th class="py-3">Acciones</th>
                        </tr>
                        <!-- Loader -->
                        <div id="loader" class="flex justify-center items-center my-4" style="display: none;">
                            <div class="dot-flashing"></div>
                        </div>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Aquí se llenarán los roles dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php footerAdmin($data); ?>



<!-- Modal Editar Módulo -->
<div id="moduloEditarModal"
    class="fixed inset-0 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-2xl relative">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-2xl font-bold text-gray-800">Editar Módulo</h3>
            <button onclick="cerrarModalEditarModulo()" class="text-gray-600 hover:text-gray-800 absolute top-4 right-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

         <div class="modal-body mb-2">
           
        </div>

        <div class="px-6 py-6">
            <form id="formEditarModulo" class="space-y-4">
                <input type="hidden" id="moduloIdEditar" name="id">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="tituloModuloEditar" class="block text-sm font-medium text-gray-700 mb-1">Título del Módulo</label>
                        <input type="text" id="tituloModuloEditar" name="titulo" maxlength="20"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                    </div>

                    <div class="flex-1 min-w-[45%]">
                        <label for="estatusModuloEditar" class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
                        <select id="estatusModuloEditar" name="estatus"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="descripcionModuloEditar" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="descripcionModuloEditar" name="descripcion" rows="3"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        required></textarea>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" id="submitEditarModulo"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                        Actualizar Módulo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Modal -->
<div id="modalEliminar" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center z-50">

    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">

        <!-- div class="modal-body mb-2">
           
        </div> -->

        <!-- Loader mientras carga -->
        <div id="loaderEliminar" class="flex justify-center items-center my-4 hidden">
            <div class="dot-flashing"></div>
        </div>

        <!-- Texto con el nombre del módulo -->
        <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">
            ¿Seguro que quieres desactivar el Módulo
            <span id="nombreRolEliminar" class="text-red-500 font-bold"></span>?
        </h2>

        <!-- Botones -->
        <div class="flex justify-end space-x-4 mt-6">
            <button class="px-4 py-2 bg-gray-400 text-white rounded" onclick="cerrarModalEliminar()">Cancelar</button>
            <button id="botonEliminar" class="px-4 py-2 bg-red-600 text-white rounded" onclick="confirmarEliminar()">Eliminar</button>
        </div>
    </div>
</div>







<!-- Modal Registrar Módulo -->
<div id="moduloModal"
    class="fixed inset-0 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-2xl relative">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-2xl font-bold text-gray-800">Registrar Módulo</h3>
            <button onclick="cerrarModalmodulo()" class="text-gray-600 hover:text-gray-800 absolute top-4 right-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-6">
            <form id="formRegistrarModulo" class="space-y-4">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[45%]">
                        <label for="tituloModulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Módulo</label>
                        <input type="text" id="tituloModulo" name="titulo" maxlength="20"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                    </div>

                    <div class="flex-1 min-w-[45%]">
                        <label for="estatusModulo" class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
                        <select id="estatusModulo" name="estatus"
                            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="descripcionModulo" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="descripcionModulo" name="descripcion" rows="3" 
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                        required></textarea>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" id="submitModulo"
                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                        Guardar Módulo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Asegúrate de tener SweetAlert2 incluido en tu HTML -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById("formRegistrarModulo").addEventListener("submit", function (e) {
    e.preventDefault();

    const titulo = document.getElementById("tituloModulo").value.trim();
    const descripcion = document.getElementById("descripcionModulo").value.trim();
    const estatus = document.getElementById("estatusModulo").value;

    const soloLetrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s]+$/;
    const descripcionRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9.,\s]+$/;

    if (!titulo || !descripcion || !estatus) {
        return Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Todos los campos son obligatorios.'
        });
    }

    if (!soloLetrasRegex.test(titulo)) {
        return Swal.fire({
            icon: 'error',
            title: 'Título inválido',
            text: 'El título solo puede contener letras, números y espacios.'
        });
    }

    if (!descripcionRegex.test(descripcion)) {
        return Swal.fire({
            icon: 'error',
            title: 'Descripción inválida',
            text: 'La descripción solo puede contener letras, números, espacios, comas o puntos.'
        });
    }

    // Verifica que se está enviando JSON correctamente
    const payload = {
        titulo: titulo,
        descripcion: descripcion,
        estatus: estatus
    };


    fetch('Modulos/guardarModulo', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(async response => {
        const contentType = response.headers.get("content-type");

        if (contentType && contentType.includes("application/json")) {
            const data = await response.json();
            if (data.success) {
                cerrarModalmodulo();
                Swal.fire({
                    icon: 'success',
                    title: 'Registro exitoso',
                    text: 'Módulo registrado correctamente.',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    cargarModulos();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al registrar',
                    text: data.message || 'No se pudo registrar el módulo.'
                });
            }
        } else {
            const text = await response.text(); // mostrar HTML si vino eso
            console.error("Respuesta no JSON:", text);
            Swal.fire({
                icon: 'error',
                title: 'Error inesperado',
                text: 'El servidor no respondió con JSON válido.'
            });
        }
    })
    .catch(error => {
        console.error("Error de red:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'Ocurrió un error al registrar el módulo.'
        });
    });
});
</script>


<!-- envio de formulario para editar  -->
<script>
document.getElementById("formEditarModulo").addEventListener("submit", function (e) {
    e.preventDefault();

    const titulo = document.getElementById("tituloModuloEditar").value.trim();
    const descripcion = document.getElementById("descripcionModuloEditar").value.trim();
    const estatus = document.getElementById("estatusModuloEditar").value;
    const moduloId = document.getElementById("moduloIdEditar").value;

    const soloLetrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s]+$/;
    const descripcionRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9.,\s]+$/;

    if (!titulo || !descripcion || !estatus) {
        return Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Todos los campos son obligatorios.'
        });
    }

    if (!soloLetrasRegex.test(titulo)) {
        return Swal.fire({
            icon: 'error',
            title: 'Título inválido',
            text: 'El título solo puede contener letras, números y espacios.'
        });
    }

    if (!descripcionRegex.test(descripcion)) {
        return Swal.fire({
            icon: 'error',
            title: 'Descripción inválida',
            text: 'La descripción solo puede contener letras, números, espacios, comas o puntos.'
        });
    }

    // Verifica que se está enviando JSON correctamente
    const payload = {
        id: moduloId,
        titulo: titulo,
        descripcion: descripcion,
        estatus: estatus
    };

    fetch(`Modulos/actualizarModulo/${moduloId}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(async response => {
        const contentType = response.headers.get("content-type");

        if (contentType && contentType.includes("application/json")) {
            const data = await response.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Actualización exitosa',
                    text: 'El módulo se actualizó correctamente.',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    cerrarModalEditarModulo();
                    cargarModulos();  // Esta función puede ser la que recarga la lista de módulos
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: data.message || 'No se pudo actualizar el módulo.'
                });
            }
        } else {
            const text = await response.text(); // Mostrar HTML si vino eso
            console.error("Respuesta no JSON:", text);
            Swal.fire({
                icon: 'error',
                title: 'Error inesperado',
                text: 'El servidor no respondió con JSON válido.'
            });
        }
    })
    .catch(error => {
        console.error("Error de red:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'Ocurrió un error al actualizar el módulo.'
        });
    });
});
</script>




<!-- Scripts personalizados -->
<script>
    /* eliminar roles */
    function modalEliminar(id) {
        const modal = document.getElementById('modalEliminar');
        const nombreRolEliminar = document.getElementById('nombreRolEliminar'); // Elemento para mostrar el nombre del rol
        const loaderEliminar = document.getElementById('loaderEliminar'); // Loader para cuando se esté cargando la información
        const botonEliminar = document.getElementById('botonEliminar'); // Botón de eliminar

        modal.classList.remove('opacity-0', 'pointer-events-none'); // Abrir modal

        // Mostrar loader mientras se realiza la búsqueda
        if (loaderEliminar) loaderEliminar.style.display = 'flex';
        if (nombreRolEliminar) nombreRolEliminar.textContent = ''; // Limpiar nombre del rol

        // Realizamos la consulta para obtener el nombre del rol por ID
        fetch(`modulos/consultarunmodulo?id=${id}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (loaderEliminar) loaderEliminar.style.display = 'none'; // Ocultar el loader

                if (data.success) {
                    const rol = data.rol;
                    if (nombreRolEliminar) {
                        nombreRolEliminar.textContent = rol.titulo; // Colocar el nombre del rol en el modal
                    }
                    // Asignamos el id al botón para usarlo más tarde
                    if (botonEliminar) {
                        botonEliminar.setAttribute('data-id', id);
                    }
                } else {
                    console.error('Error al cargar el Modulo para eliminar:', data.message);
                    if (nombreRolEliminar) {
                        nombreRolEliminar.textContent = 'Error al cargar el nombre del rol.';
                    }
                }
            })
            .catch(error => {
                console.error('Error de conexión al buscar rol para eliminar:', error);
                if (loaderEliminar) loaderEliminar.style.display = 'none'; // Ocultar loader en caso de error
                if (nombreRolEliminar) {
                    nombreRolEliminar.textContent = 'Error de conexión.';
                }
            });
    }



    function cerrarModalEliminar() {
        const modal = document.getElementById('modalEliminar');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    function confirmarEliminar() {
    const botonEliminar = document.getElementById('botonEliminar');
    const idRol = botonEliminar.getAttribute('data-id');

    if (!idRol) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo obtener el ID del módulo.'
        });
        return;
    }

    fetch(`modulos/eliminar?id=${idRol}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Desactivado',
                text: 'El módulo fue desactivado correctamente.'
            });

            cerrarModalEliminar();
            cargarModulos();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo desactivar el módulo.'
            });
        }
    })
    .catch(error => {
        console.error('Error al eliminar el módulo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'Hubo un problema al intentar desactivar el módulo.'
        });
    });
}




    /* registrar roles  */
    function abrirModalmodulo() {
        const modal = document.getElementById('moduloModal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }

    function cerrarModalmodulo() {
        const modal = document.getElementById('moduloModal');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }


    /* editar roles */
    function cerrarModalEditarModulo() {
        const modal = document.getElementById('moduloEditarModal');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    function abrirModalEditar(id) {
    const modal = document.getElementById('moduloEditarModal');
    const loader = document.getElementById('loader');

    modal.classList.remove('opacity-0', 'pointer-events-none');


    // Mostrar el loader
    loader.style.display = 'flex';

    // Limpiar campos mientras carga
    document.getElementById('tituloModuloEditar').value = '';
    document.getElementById('estatusModuloEditar').value = '';
    document.getElementById('descripcionModuloEditar').value = '';
    document.getElementById('moduloIdEditar').value = '';

    fetch(`Modulos/consultarunmodulo?id=${id}`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta completa del servidor:', data);

        // Ocultar el loader
        loader.style.display = 'none';

        if (data.success) {
            const modulo = data.rol;
            document.getElementById('tituloModuloEditar').value = modulo.titulo;
            document.getElementById('estatusModuloEditar').value = modulo.estatus;
            document.getElementById('descripcionModuloEditar').value = modulo.descripcion;
            document.getElementById('moduloIdEditar').value = id;
        } else {
            console.error('Error en backend:', data.message);
            alert('No se pudo cargar la información del módulo.');
        }
    })
    .catch(error => {
        console.error('Error de conexión al buscar módulo:', error);
        loader.style.display = 'none'; // Ocultar el loader si falla también
        alert('Error de conexión al buscar datos del módulo.');
    });
}







    function cargarModulos() {
        const loader = document.getElementById('loader');
        const tbody = document.querySelector('#TablaRoles tbody');

        loader.style.display = 'flex';
        tbody.innerHTML = ''; // Limpiar tabla

        fetch('modulos/Consultar_modulos', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';
                if (data.success) {
                    if (data.modulos.length > 0) {
                        data.modulos.forEach((modulo, index) => {
                            const row = `
                            <tr class="border-b">
                                <td class="py-2">${index + 1}</td>
                                <td class="py-2">${modulo.titulo}</td>
                                <td class="py-2">${modulo.descripcion}</td>
                                <td class="py-2">${modulo.estatus}</td>
                                <td class="py-2">${modulo.fecha_creacion}</td>
                                <td class="py-2">${modulo.fecha_modificacion}</td>
                                <td class="py-2">
                                   <button class="bg-blue-500 text-white px-2 py-1 rounded editar-modulo"
    data-id="${modulo.idmodulo}" 
    data-titulo="${modulo.titulo}" 
    data-estatus="${modulo.estatus}" 
    data-descripcion="${modulo.descripcion}"
    onclick="abrirModalEditar(${modulo.idmodulo})">
    <i class="fas fa-pen"></i>
</button>
<button class="bg-red-500 text-white px-2 py-1 rounded eliminar-modulo" 
    data-id="${modulo.idmodulo}" 
    data-titulo="${modulo.titulo}"
    onclick="modalEliminar(${modulo.idmodulo})"
    >
    <i class="fas fa-trash"></i>
</button>

                                </td>
                            </tr>`;
                            tbody.insertAdjacentHTML('beforeend', row);
                        });
                        agregarEventos();
                    } else {
                        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4">No hay módulos disponibles.</td></tr>`;
                    }
                } else {
                    mostrarError(tbody, data.message);
                }
            })
            .catch(error => {
                console.error('Error de conexión:', error);
                mostrarError(tbody, 'Error de conexión al cargar módulos.');
            });
    }

    function agregarEventos() {
        document.querySelectorAll('.editar-modulo').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const titulo = button.dataset.titulo;
                const estatus = button.dataset.estatus;
                const descripcion = button.dataset.descripcion;

                const modal = document.getElementById('modalEditar');
                modal.querySelector('.modal-body').innerHTML = `
                <h5>Editar Módulo: ${titulo}</h5>
                <p>ID: ${idmodulo}</p>
                <p>Estatus: ${estatus}</p>
                <p>Descripción: ${descripcion}</p>
                <button id="cerrarEditarModal">Cerrar</button>
            `;
                modal.style.display = 'block';

                modal.querySelector('#cerrarEditarModal').addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            });
        });

        document.querySelectorAll('.eliminar-modulo').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const titulo = button.dataset.titulo;

                const modal = document.getElementById('modalEliminar');
                modal.querySelector('.modal-body').innerHTML = `
                <h5>Eliminar Módulo: ${titulo}</h5>
                <p>¿Estás seguro de que quieres eliminar el módulo con ID: ${id}?</p>
                <button id="cerrarEliminarModal">Cerrar</button>
            `;
                modal.style.display = 'block';

                modal.querySelector('#cerrarEliminarModal').addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            });
        });
    }

    function mostrarError(tbody, mensaje) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">${mensaje}</td></tr>`;
    }


    document.addEventListener('DOMContentLoaded', cargarModulos);


</script>

<!-- Estilos Loader -->
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