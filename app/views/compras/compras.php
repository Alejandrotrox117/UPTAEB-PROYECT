<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Compras</h1>
    <p class="text-green-500 text-lg">Compras de Materiales</p>

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
            <th class="py-2">Fecha</th>
            <th class="py-2">Proveedor</th>
            <th class="py-2">Material</th>
            <th class="py-2">Peso Neto</th>
            <th class="py-2">% de Descuento</th>
            <th class="py-2">Total</th>
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
<div id="registrationModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl">
    <!-- Encabezado del Modal -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Compra</h3>
      <button id="registrationCloseBtn" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <!-- Formulario de Registro dividido en dos columnas -->
    <form id="registrationForm" class="px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Columna 1 -->
        <div>
          <!-- Proveedor -->
          <div class="mb-6">
            <label for="proveedor" class="block text-gray-700 font-medium mb-2">Proveedor</label>
            <select id="proveedor" name="proveedor" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
              <option value="">Selecciona un proveedor</option>
              <option value="1">Proveedor 1</option>
              <option value="proveedor2">Proveedor 2</option>
              <option value="proveedor3">Proveedor 3</option>
            </select>
          </div>
          <!-- Peso Bruto -->
          <div class="mb-6">
            <label for="peso_bruto" class="block text-gray-700 font-medium mb-2">Peso Bruto</label>
            <input type="number" id="peso_bruto" name="peso_bruto" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" placeholder="Ingrese el peso bruto">
          </div>
          <!-- Peso Neto -->
          <div class="mb-6">
            <label for="peso_neto" class="block text-gray-700 font-medium mb-2">Peso Neto</label>
            <input type="number" id="peso_neto" name="peso_neto" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" placeholder="Ingrese el peso neto">
          </div>
          <!-- Subtotal -->
          <div class="mb-6">
            <label for="subtotal" class="block text-gray-700 font-medium mb-2">Subtotal</label>
            <input type="number" id="subtotal" name="subtotal" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" placeholder="Subtotal">
          </div>
        </div>
        <!-- Columna 2 -->
        <div>
          <!-- Tipo de Material -->
          <div class="mb-6">
            <label for="tipo_material" class="block text-gray-700 font-medium mb-2">Tipo de Material</label>
            <select id="tipo_material" name="tipo_material" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
              <option value="">Selecciona un material</option>
              <option value="1">Material 1</option>
              <option value="material2">Material 2</option>
              <option value="material3">Material 3</option>
            </select>
          </div>
          <!-- Peso del Vehículo -->
          <div class="mb-6">
            <label for="peso_vehiculo" class="block text-gray-700 font-medium mb-2">Peso del Vehículo</label>
            <input type="number" id="peso_vehiculo" name="peso_vehiculo" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" placeholder="Ingrese el peso del vehículo">
          </div>
          <!-- Porcentaje de Descuento -->
          <div class="mb-6">
            <label for="porcentaje_descuento" class="block text-gray-700 font-medium mb-2">Porcentaje de Descuento</label>
            <input type="number" id="porcentaje_descuento" name="porcentaje_descuento" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" placeholder="Ingrese el porcentaje de descuento" step="0.01" min="0" max="100">
          </div>
          <!-- Total -->
          <div class="mb-6">
            <label for="total" class="block text-gray-700 font-medium mb-2">Total</label>
            <input type="number" id="total" name="total" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none" placeholder="Total">
          </div>
        </div>
      </div>
      <!-- Acciones del formulario -->
      <div class="flex justify-end space-x-6 mt-6">
        <button type="button" id="registrationCancelBtn" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
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





