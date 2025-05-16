<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Empleados</h1>
    <p class="text-green-500 text-lg">empleados</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button onclick="abrirModalEmpleado()" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar
        </button>
      </div>

      <table id="TablaEmpleado" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2 px-3">Nr</th>
            <th class="py-2 px-3">Nombre</th>
            <th class="py-2 px-3">Apellido</th>
            <th class="py-2 px-3">Identificación</th>
            <th class="py-2 px-3">Teléfono</th>
            <th class="py-2 px-3">Correo</th>
            <th class="py-2 px-3">Estatus</th>
            <th class="py-2 px-3">Puesto</th>
            <th class="py-2 px-3">Salario</th>
            <th class="py-2 px-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-gray-900">
          <!-- Ejemplo de fila -->
          <tr>

          </tr>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>
<?php footerAdmin($data); ?>


<!-- Modal -->
<div id="empleadoModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-5xl">
    <!-- Encabezado -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Empleado</h3>
      <button onclick="cerrarModalEmpleado()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Formulario -->
    <form id="empleadoForm" class="px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Primera columna -->
        <div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Nombre</label>
            <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required minlength="3">
            <small class="text-red-500 hidden" id="error-nombre">El nombre debe tener al menos 3 caracteres.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Identificación</label>
            <input type="text" id="identificacion" name="identificacion" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required pattern="\d{7,10}">
            <small class="text-red-500 hidden" id="error-identificacion">La identificación debe tener entre 7 y 10 dígitos.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Teléfono Principal</label>
            <input type="text" id="telefono_principal" name="telefono_principal" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">

          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Dirección</label>
            <input type="text" id="direccion" name="direccion" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required minlength="5">
            <small class="text-red-500 hidden" id="error-direccion">La dirección debe tener al menos 5 caracteres.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Puesto</label>
            <input type="text" id="puesto" name="puesto" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required minlength="3">
            <small class="text-red-500 hidden" id="error-puesto">El puesto debe tener al menos 3 caracteres.</small>
          </div>
        </div>

        <!-- Segunda columna -->
        <div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Apellido</label>
            <input type="text" id="apellido" name="apellido" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required minlength="3">
            <small class="text-red-500 hidden" id="error-apellido">El apellido debe tener al menos 3 caracteres.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Género</label>
            <select id="genero" name="genero" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required>
              <option value="">Seleccione</option>
              <option value="masculino">Masculino</option>
              <option value="femenino">Femenino</option>
              <option value="otro">Otro</option>
            </select>
            <small class="text-red-500 hidden" id="error-genero">Debe seleccionar un género.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Correo Electrónico</label>
            <input type="email" id="correo_electronico" name="correo_electronico" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required>
            <small class="text-red-500 hidden" id="error-correo">Ingrese un correo electrónico válido.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Salario</label>
            <input type="number" id="salario" name="salario" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required min="0">
            <small class="text-red-500 hidden" id="error-salario">El salario debe ser un número positivo.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Estatus</label>
            <select id="estatus" name="estatus" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
            <small class="text-red-500 hidden" id="error-estatus">Debe seleccionar un estatus.</small>
          </div>
        </div>

        <!-- Tercera columna -->
        <div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Fecha de Nacimiento</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required>
            <small class="text-red-500 hidden" id="error-fecha">La fecha de nacimiento es obligatoria.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Fecha de Inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" required>
            <small class="text-red-500 hidden" id="error-fecha-inicio">La fecha de inicio es obligatoria.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Fecha de Fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <small class="text-red-500 hidden" id="error-fecha-fin">La fecha de fin no es obligatoria.</small>
          </div>
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none"></textarea>
            <small class="text-red-500 hidden" id="error-observaciones">Las observaciones son opcionales.</small>
          </div>
          <div class="mb-6">

            <input type="hidden" id="idempleado" name="idempleado" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" readonly>
          </div>
        </div>
      </div>

      <!-- Botones -->
      <div class="flex justify-end space-x-6 mt-6">
        <button type="button" onclick="cerrarModalEmpleado()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
          Cancelar
        </button>
        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600 transition text-xl">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>




<!-- Modal de Eliminación (fondo transparente con efecto de desenfoque leve) -->
<div id="deletionModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
    <!-- Encabezado -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Eliminar Elemento</h3>
      <button id="deletionCloseBtn" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <!-- Contenido -->
    <div class="px-8 py-6">
      <p class="text-gray-700 text-xl">
        ¿Estás seguro de eliminar este elemento? Esta acción <span class="font-semibold">no se puede revertir</span>.
      </p>
    </div>
    <!-- Acciones -->
    <div class="px-8 py-6 border-t flex justify-end space-x-6">
      <button id="deletionCancelBtn" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
        Cancelar
      </button>
      <button id="deletionConfirmBtn" class="px-6 py-3 bg-red-500 text-white rounded hover:bg-red-600 transition text-xl">
        Eliminar
      </button>
    </div>
  </div>
</div>