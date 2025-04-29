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
    
    <div class="px-6 py-6">
      <div id="modalEditarContenido" class="space-y-4">
        <!-- Aquí se llenará el contenido dinámicamente -->
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar Rol -->
<div id="modalEliminar" class="fixed inset-0 flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl w-11/12 max-w-2xl relative">
    <div class="flex justify-between items-center px-6 py-4 border-b">
      <h3 class="text-2xl font-bold text-gray-800">Eliminar Rol</h3>
      <button onclick="cerrarModalEliminar()" class="text-gray-600 hover:text-gray-800 absolute top-4 right-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="px-6 py-6">
      <div id="modalEliminarContenido" class="space-y-4">
        <!-- Aquí se llenará el contenido dinámicamente -->
      </div>
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

 

  <!-- Scripts personalizados -->
  <script>

function abrirModalEditar() {
  const modal = document.getElementById('modalEditar');
  modal.classList.remove('opacity-0', 'pointer-events-none');
}

function cerrarModalEditar() {
  const modal = document.getElementById('modalEditar');
  modal.classList.add('opacity-0', 'pointer-events-none');
}

function abrirModalEditar(id) {
  const modal = document.getElementById('modalEditar');
  modal.classList.remove('opacity-0', 'pointer-events-none');

  console.log('ID recibido para editar:', id);

  // Limpiar campos mientras se carga
  document.getElementById('nombreRolEditar').value = '';
  document.getElementById('estatusRolEditar').value = '';
  document.getElementById('descripcionRolEditar').value = '';

  // Ahora hacemos una petición al servidor para traer los datos del rol por id
  fetch(`Roles/consultarunrol/${id}`, {
    method: 'GET',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const rol = data.rol;  // Asumo que tu backend responde con un objeto { rol: {id, nombre, estatus, descripcion} }
      
      document.getElementById('nombreRolEditar').value = rol.nombre;
      document.getElementById('estatusRolEditar').value = rol.estatus;
      document.getElementById('descripcionRolEditar').value = rol.descripcion;
      document.getElementById('idRolEditar').value = rol.id; // Guardar el id oculto
    } else {
      console.error('Error en backend:', data.message);
      alert('No se pudo cargar la información del rol.');
    }
  })
  .catch(error => {
    console.error('Error de conexión al buscar rol:', error);
    alert('Error de conexión al buscar datos del rol.');
  });
}

function cerrarModalEliminar() {
  const modal = document.getElementById('modalEliminar');
  modal.classList.add('opacity-0', 'pointer-events-none');
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
                  data-id="${rol.id}" 
                  data-nombre="${rol.nombre}" 
                  data-estatus="${rol.estatus}" 
                  data-descripcion="${rol.descripcion}"
                  onclick="abrirModalEditar(${rol.id})">
                  Editar
                </button>
                <button class="bg-red-500 text-white px-2 py-1 rounded eliminar-rol" 
                  data-id="${rol.id}" 
                  data-nombre="${rol.nombre}"
                  onclick="abrirModalEliminar(${rol.id})">
                  Eliminar
                </button>
              </td>
            </tr>`;
          tbody.insertAdjacentHTML('beforeend', row); // Insertar fila
        });
        agregarEventos(); // Agregar eventos a los botones de la tabla
      } else {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4">No hay roles disponibles.</td></tr>`;
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