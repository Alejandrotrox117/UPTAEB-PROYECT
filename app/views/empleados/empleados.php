<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Empleado</h1>
    <p class="text-green-500 text-lg">Empleados Temporales</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button id="openRegistrationModalBtn" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar
        </button>
      </div>

      <table id="TablaCompras" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2">Nro</th>
            <th class="py-2">Nombre completo</th>
            <th class="py-2">Cédula </th>
            <th class="py-2">Teléfono</th>
            <th class="py-2">Cargo o función </th>
            <th class="py-2">Fecha de ingreso</th>
            <th class="py-2">Días trabajados</th>
            <th class="py-2">Pago por día</th>
            <th class="py-2">Total a pagar</th>
           
          </tr>
        </thead>
        <tbody class="text-gray-900">
          <!-- Aquí se pueden inyectar dinámicamente las filas con PHP -->
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>
<?php footerAdmin($data); ?>

<!-- Modal de Registro (modal más grande, inputs ampliados, fondo transparente con desenfoque leve) -->
<!-- Modal de Registro para Empleados Temporales -->
<div id="registrationModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl">
    <!-- Encabezado del Modal -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Empleado Temporal</h3>
      <button id="registrationCloseBtn" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <!-- Formulario -->
    <form id="registrationForm" class="px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <!-- Nombre -->
          <div class="mb-4">
            <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none" placeholder="Ej. Juan Pérez">
          </div>

          <!-- Cédula -->
          <div class="mb-4">
            <label for="cedula" class="block text-gray-700 font-medium mb-2">Cédula</label>
            <input type="text" id="cedula" name="cedula" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none" placeholder="Ej. 12345678">
          </div>

          <!-- Teléfono -->
          <div class="mb-4">
            <label for="telefono" class="block text-gray-700 font-medium mb-2">Teléfono</label>
            <input type="text" id="telefono" name="telefono" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none" placeholder="Ej. 0414-1234567">
          </div>

          <!-- Fecha de Ingreso -->
          <div class="mb-4">
            <label for="fecha_ingreso" class="block text-gray-700 font-medium mb-2">Fecha de Ingreso</label>
            <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none">
          </div>
        </div>
        <div>
          <!-- Cargo -->
          <div class="mb-4">
            <label for="cargo" class="block text-gray-700 font-medium mb-2">Cargo</label>
            <select id="cargo" name="cargo" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none">
              <option value="">Seleccionar</option>
              <option value="embalador">Embalador</option>
              <option value="clasificador">Clasificador</option>
            </select>
          </div>

          <!-- Días Trabajados -->
          <div class="mb-4">
            <label for="dias" class="block text-gray-700 font-medium mb-2">Días Trabajados</label>
            <input type="number" id="dias" name="dias" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none" placeholder="Ej. 5">
          </div>

          <!-- Pago por Día -->
          <div class="mb-4">
            <label for="pago_dia" class="block text-gray-700 font-medium mb-2">Pago por Día</label>
            <input type="number" id="pago_dia" name="pago_dia" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none" placeholder="Ej. 10.00">
          </div>

          <!-- Total -->
          <div class="mb-4">
            <label for="total" class="block text-gray-700 font-medium mb-2">Total</label>
            <input type="number" id="total" name="total" class="w-full border rounded-lg px-4 py-3 text-lg focus:outline-none bg-gray-100" placeholder="Se calcula automáticamente" readonly>
          </div>
        </div>
      </div>
      <div class="flex justify-end mt-6 space-x-4">
        <button type="button" id="registrationCancelBtn" class="bg-gray-300 px-6 py-3 rounded-lg text-lg">Cancelar</button>
        <button type="submit" class="bg-green-500 text-white px-6 py-3 rounded-lg text-lg">Registrar</button>
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
                d="M6 18L18 6M6 6l12 12"/>
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





