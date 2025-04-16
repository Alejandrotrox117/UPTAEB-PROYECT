<!-- Modal de Registro (modal más grande, inputs ampliados, fondo transparente con desenfoque leve) -->
<div id="registrationModal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-75 transition-opacity duration-300 opacity-0 pointer-events-none z-50">
  <div class="bg-white rounded-lg shadow-lg overflow-hidden w-11/12 max-w-lg">
    <!-- Encabezado del Modal -->
    <div class="px-4 py-3 border-b flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-800">Registrar Inventario</h3>
      <button id="registrationCloseBtn" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <!-- Formulario de Registro -->
    <form id="registrationForm" class="px-4 py-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Inventario Inicial -->
        <div>
          <label for="inventario_inicial" class="block text-gray-700 font-medium mb-1 text-sm">Inventario Inicial</label>
          <input type="number" id="inventario_inicial" name="inventario_inicial" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Ingrese el inventario inicial">
        </div>
        <!-- Ajuste de Inventario -->
        <div>
          <label for="ajuste_inventario" class="block text-gray-700 font-medium mb-1 text-sm">Ajuste de Inventario</label>
          <input type="number" id="ajuste_inventario" name="ajuste_inventario" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Ingrese el ajuste de inventario">
        </div>
        <!-- Material Compras -->
        <div>
          <label for="material_compras" class="block text-gray-700 font-medium mb-1 text-sm">Material Compras</label>
          <input type="number" id="material_compras" name="material_compras" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Ingrese el material de compras">
        </div>
        <!-- Despacho -->
        <div>
          <label for="despacho" class="block text-gray-700 font-medium mb-1 text-sm">Despacho</label>
          <input type="number" id="despacho" name="despacho" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Ingrese el despacho">
        </div>
        <!-- Descuento -->
        <div>
          <label for="descuento" class="block text-gray-700 font-medium mb-1 text-sm">Descuento</label>
          <input type="number" id="descuento" name="descuento" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Ingrese el descuento">
        </div>
        <!-- Final -->
        <div>
          <label for="final" class="block text-gray-700 font-medium mb-1 text-sm">Final</label>
          <input type="number" id="final" name="final" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none" placeholder="Ingrese el valor final">
        </div>
        <!-- Fecha -->
        <div class="md:col-span-2">
          <label for="fecha" class="block text-gray-700 font-medium mb-1 text-sm">Fecha</label>
          <input type="date" id="fecha" name="fecha" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none">
        </div>
      </div>
      <!-- Acciones del formulario -->
      <div class="flex justify-end space-x-3 mt-4">
        <button type="button" id="registrationCancelBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-sm">
          Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-sm">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>


