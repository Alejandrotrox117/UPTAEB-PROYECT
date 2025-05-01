<?php require_once('helpers/helpers.php'); headerAdmin($data); ?>

  <!-- Scripts y estilos externos -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <main class="flex-1 p-6">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Administración de Roles</h2>
      <input type="text" placeholder="Buscar Rol"
        class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>

    <div class="min-h-screen mt-6">
      <h1 class="text-3xl font-bold text-gray-900">Roles</h1>
      <p class="text-green-500 text-lg">Gestión de Roles</p>

      <div class="bg-white p-8 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
          <button onclick="abrirModalRol()"
            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
            Registrar Rol
          </button>
        </div>

        <div class="overflow-x-auto">
          <table id="TablaRoles" class="w-full text-left border-collapse">
            <thead>
              <tr class="text-gray-500 text-sm border-b">
                <th class="py-3">Nº</th>
                <th class="py-3">Nombre</th>
                <th class="py-3">Estatus</th>
                <th class="py-3">Descripción</th>
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


<!-- Modal Editar Rol -->
<div id="modalEditar" class="fixed inset-0 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-2xl relative">
    <div class="flex justify-between items-center px-6 py-4 border-b">
      <h3 class="text-2xl font-bold text-gray-800">Editar Rol</h3>
      <button onclick="cerrarModalEditar()" class="text-gray-600 hover:text-gray-800 absolute top-4 right-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div id="loader" class="flex justify-center items-center my-4" style="display: none;">
    <div class="dot-flashing"></div>
  </div>

    <div class="px-6 py-6">
      <form id="formEditarRol" class="space-y-4">
        <!-- Campo oculto para el ID del rol -->
        <input type="hidden" id="idRolEditar" name="id">

        <div class="flex flex-wrap gap-4">
          <div class="flex-1 min-w-[45%]">
            <label for="nombreRolEditar" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol</label>
            <input type="text" id="nombreRolEditar" name="nombre"
              class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
              required>
          </div>

          <div class="flex-1 min-w-[45%]">
            <label for="estatusRolEditar" class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
            <select id="estatusRolEditar" name="estatus"
              class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
              required>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
        </div>

        <div>
          <label for="descripcionRolEditar" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
          <textarea id="descripcionRolEditar" name="descripcion" rows="3"
            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit" id="submitRol"
            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
            Actualizar 
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>


<!-- modal eliminar --> 
<div id="modalEliminar" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg w-96">
    
    <!-- Loader mientras carga (opcional) -->
    <div id="loaderEliminar" class="flex justify-center items-center my-4" style="display: none;">
      <div class="dot-flashing"></div>
    </div>

    <!-- Texto con el nombre del rol que se eliminará -->
    <h2 class="text-xl font-semibold text-gray-800 mb-4">¿Seguro que quieres desactivar el rol <span id="nombreRolEliminar" class="text-red-500 font-bold"></span>?</h2>

    <!-- Botones -->
    <div class="flex justify-end space-x-4 mt-6">
      <button class="px-4 py-2 bg-gray-400 text-white rounded" onclick="cerrarModalEliminar()">Cancelar</button>
      <!-- Botón de eliminación con el id guardado en data-id -->
      <button id="botonEliminar" class="px-4 py-2 bg-red-600 text-white rounded" onclick="confirmarEliminar()">Eliminar</button>
    </div>

  </div>
</div>





  <!-- Modal Registrar Rol -->
  <div id="rolModal"
    class="fixed inset-0 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-2xl relative">
      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h3 class="text-2xl font-bold text-gray-800">Registrar Rol</h3>
        <button onclick="cerrarModalRol()" class="text-gray-600 hover:text-gray-800 absolute top-4 right-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-6">
        <form id="formRegistrarRol" class="space-y-4">
          <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[45%]">
              <label for="nombreRol" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol</label>
              <input type="text" id="nombreRol" name="nombre"
                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                required>
            </div>

            <div class="flex-1 min-w-[45%]">
              <label for="estatusRol" class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
              <select id="estatusRol" name="estatus"
                class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                required>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
              </select>
            </div>
          </div>

          <div>
            <label for="descripcionRol" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea id="descripcionRol" name="descripcion" rows="3"
              class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
          </div>

          <div class="flex justify-end pt-4">
            <button type="submit" id="submitRol"
              class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
              Guardar Rol
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  document.getElementById("formRegistrarRol").addEventListener("submit", function (e) {
    e.preventDefault();

    const nombre = document.getElementById("nombreRol").value.trim();
    const descripcion = document.getElementById("descripcionRol").value.trim();
    const estatus = document.getElementById("estatusRol").value;

    const soloLetrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
    const descripcionRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9.,\s]+$/;

    if (!nombre || !descripcion) {
      alert("Todos los campos son obligatorios.");
      return;
    }

    if (!soloLetrasRegex.test(nombre)) {
      alert("El nombre del rol solo debe contener letras.");
      return;
    }

    if (!descripcionRegex.test(descripcion)) {
      alert("La descripción solo puede contener letras, números, espacios, punto y coma.");
      return;
    }

    // Envío AJAX a un archivo PHP nativo
    fetch('Roles/guardarRol', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        nombre: nombre,
        descripcion: descripcion,
        estatus: estatus
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert("Rol registrado correctamente.");
        cerrarModalRol();
        document.getElementById("formRegistrarRol").reset();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error al enviar los datos:", error);
      alert("Ocurrió un error al registrar el rol.");
    });
  });



  document.getElementById('formEditarRol').addEventListener('submit', function (e) {
  e.preventDefault(); // Evita recarga

  const id = document.getElementById('idRolEditar').value;
  const nombre = document.getElementById('nombreRolEditar').value.trim();
  const estatus = document.getElementById('estatusRolEditar').value;
  const descripcion = document.getElementById('descripcionRolEditar').value.trim();

  // Expresiones regulares para validación
  const soloLetrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
  const descripcionRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9.,\s]+$/;

  // Validación de campos
  if (!nombre || !descripcion) {
    alert("Todos los campos son obligatorios.");
    return;
  }

  if (!soloLetrasRegex.test(nombre)) {
    alert("El nombre del rol solo debe contener letras.");
    return;
  }

  if (!descripcionRegex.test(descripcion)) {
    alert("La descripción solo puede contener letras, números, espacios, punto y coma.");
    return;
  }

  // Si pasa la validación, enviamos la información al backend
  fetch('Roles/actualizar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, nombre, estatus, descripcion })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Rol actualizado con éxito');
      // Cerrar modal, refrescar tabla, etc.
    } else {
      alert('Error al actualizar: ' + data.message);
    }
  })
  .catch(err => {
    console.error('Error al actualizar rol:', err);
    alert('Ocurrió un error al enviar los datos.');
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
  fetch(`Roles/consultarunrol?id=${id}`, {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(response => response.json())
  .then(data => {
    if (loaderEliminar) loaderEliminar.style.display = 'none'; // Ocultar el loader

    if (data.success) {
      const rol = data.rol;
      if (nombreRolEliminar) {
        nombreRolEliminar.textContent = rol.nombre; // Colocar el nombre del rol en el modal
      }
      // Asignamos el id al botón para usarlo más tarde
      if (botonEliminar) {
        botonEliminar.setAttribute('data-id', id);
      }
    } else {
      console.error('Error al cargar el rol para eliminar:', data.message);
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
  const idRol = botonEliminar.getAttribute('data-id'); // Obtener el ID del rol desde el atributo data-id

  if (!idRol) {
    alert('No se pudo obtener el ID del rol.');
    return;
  }

  // Realizar la eliminación (puedes hacer un fetch para eliminar el rol, por ejemplo)
  fetch(`Roles/eliminar?id=${idRol}`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json'
    },
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Rol eliminado correctamente.');
      // Cerrar el modal y actualizar la vista si es necesario
      cerrarModalEliminar();
      cargarRoles();
    } else {
      alert('Error al eliminar el rol.');
    }
  })
  .catch(error => {
    console.error('Error al eliminar el rol:', error);
    alert('Error de conexión al eliminar el rol.');
  });
}


/* registrar roles  */
function abrirModalRol() {
  const modal = document.getElementById('rolModal');
  modal.classList.remove('opacity-0', 'pointer-events-none');
}

function cerrarModalRol() {
  const modal = document.getElementById('rolModal');
  modal.classList.add('opacity-0', 'pointer-events-none');
}


/* editar roles */
function cerrarModalEditar() {
  const modal = document.getElementById('modalEditar');
  modal.classList.add('opacity-0', 'pointer-events-none');
}

function abrirModalEditar(id) {
  const modal = document.getElementById('modalEditar');
  const loader = document.getElementById('loader');

  modal.classList.remove('opacity-0', 'pointer-events-none');

  console.log('ID recibido para editar:', id);

  // Mostrar el loader
  loader.style.display = 'flex';

  // Limpiar campos mientras carga
  document.getElementById('nombreRolEditar').value = '';
  document.getElementById('estatusRolEditar').value = '';
  document.getElementById('descripcionRolEditar').value = '';
  document.getElementById('idRolEditar').value = '';

  fetch(`Roles/consultarunrol?id=${id}`, {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(response => response.json())
  .then(data => {
    console.log('Respuesta completa del servidor:', data);

    // Ocultar el loader
    loader.style.display = 'none';

    if (data.success) {
      const rol = data.rol;
      document.getElementById('nombreRolEditar').value = rol.nombre;
      document.getElementById('estatusRolEditar').value = rol.estatus;
      document.getElementById('descripcionRolEditar').value = rol.descripcion;
      document.getElementById('idRolEditar').value = id;
    } else {
      console.error('Error en backend:', data.message);
      alert('No se pudo cargar la información del rol.');
    }
  })
  .catch(error => {
    console.error('Error de conexión al buscar rol:', error);
    loader.style.display = 'none'; // Ocultar el loader si falla también
    alert('Error de conexión al buscar datos del rol.');
  });
}






function cargarRoles() {
  const loader = document.getElementById('loader');
  const tbody = document.querySelector('#TablaRoles tbody');

  loader.style.display = 'flex';
  tbody.innerHTML = '';  // Limpiar el contenido de la tabla

  fetch('Roles/ConsultarRol', {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(response => response.json())
  .then(data => {
    loader.style.display = 'none';
    if (data.success) {
      if (data.roles.length > 0) {
        data.roles.forEach((rol, index) => {
          const row = `
            <tr class="border-b">
              <td class="py-2">${index + 1}</td>
              <td class="py-2">${rol.nombre}</td>
              <td class="py-2">${rol.estatus}</td>
              <td class="py-2">${rol.descripcion}</td>
              <td class="py-2">
                <button  class="bg-blue-500 text-white px-2 py-1 rounded editar-rol" 
                  data-id="${rol.idrol}" 
                  data-nombre="${rol.nombre}" 
                  data-estatus="${rol.estatus}" 
                  data-descripcion="${rol.descripcion}"
                  onclick="abrirModalEditar(${rol.idrol})">
                  Editar
                </button>
                <button class="bg-red-500 text-white px-2 py-1 rounded eliminar-rol" 
                  data-id="${rol.idrol}" 
                  data-nombre="${rol.nombre}"
                  onclick="modalEliminar(${rol.idrol})">
                  Eliminar
                </button>
              </td>
            </tr>`;
          tbody.insertAdjacentHTML('beforeend', row); // Insertar fila
        });
        agregarEventos(); // Agregar eventos a los botones de la tabla
      } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4">No Hay Roles Disponibles.</td></tr>`;
      }
    } else {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">${data.message}</td></tr>`;
      console.error('Error en backend:', data.message);
    }
  })
  .catch(error => {
    console.error('Error de conexión al cargar roles:', error);
    loader.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Error de conexión al cargar roles.</td></tr>`;
  });
}

function agregarEventos() {
  // Agregar evento para abrir el modal de editar
  document.querySelectorAll('.editar-rol').forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const nombre = this.getAttribute('data-nombre');
      const estatus = this.getAttribute('data-estatus');
      const descripcion = this.getAttribute('data-descripcion');

      // Rellenar el contenido del modal de editar
      document.querySelector('#modalEditar .modal-body').innerHTML = `
        <h5>Editar Rol: ${nombre}</h5>
        <p>ID: ${id}</p>
        <p>Estatus: ${estatus}</p>
        <p>Descripción: ${descripcion}</p>
        <!-- Aquí puedes agregar un formulario para editar el rol -->
        <button id="cerrarEditarModal">Cerrar</button>
      `;

      // Mostrar el modal de editar
      document.getElementById('modalEditar').style.display = 'block';

      // Agregar evento para cerrar el modal de editar
      document.getElementById('cerrarEditarModal')?.addEventListener('click', function() {
        document.getElementById('modalEditar').style.display = 'none';
      });
    });
  });

  // Agregar evento para abrir el modal de eliminar
  document.querySelectorAll('.eliminar-rol').forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const nombre = this.getAttribute('data-nombre');

      // Rellenar el contenido del modal de eliminar
      document.querySelector('#modalEliminar .modal-body').innerHTML = `
        <h5>Eliminar Rol: ${nombre}</h5>
        <p>¿Estás seguro de que quieres eliminar el rol con ID: ${id}?</p>
        <button id="cerrarEliminarModal">Cerrar</button>
      `;

      // Mostrar el modal de eliminar
      document.getElementById('modalEliminar').style.display = 'block';

      // Agregar evento para cerrar el modal de eliminar
      document.getElementById('cerrarEliminarModal')?.addEventListener('click', function() {
        document.getElementById('modalEliminar').style.display = 'none';
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', cargarRoles);


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