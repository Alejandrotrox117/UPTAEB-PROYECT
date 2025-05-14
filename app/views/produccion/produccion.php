<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Módulo de Producción</h2>
    <input type="text" placeholder="Buscar producción..." class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>
  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Producciones</h1>
    <p class="text-green-500 text-lg">Gestión de procesos de producción</p>
    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button   onclick="abrirModalProduccion()"  class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar Producción
        </button>
      </div>
      <table id="TablaProduccion" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2 px-3">ID Producción</th>
            <th class="py-2 px-3">Producto</th>
            <th class="py-2 px-3">Empleado</th>
            <th class="py-2 px-3">Cantidad</th>
            <th class="py-2 px-3">Fecha Inicio</th>
            <th class="py-2 px-3">Fecha Fin</th>
            <th class="py-2 px-3">Estado</th>
            <th class="py-2 px-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-gray-900">
          
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php footerAdmin($data); ?>
<!-- Modal para Registrar Producción -->
<div id="produccionModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-5xl">
    <!-- Encabezado -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Producción</h3>
      <button onclick="cerrarModalProduccion()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <!-- Formulario -->
    <form id="produccionForm" class="px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Producto</label>
          <select id="idproducto" name="idproducto" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="">Seleccione un producto</option>
            <!-- Opciones de productos se cargarán dinámicamente -->
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Empleado</label>
          <select id="idempleado" name="idempleado" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="">Seleccione un empleado</option>
            <!-- Opciones de empleados se cargarán dinámicamente -->
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Cantidad a Producir</label>
          <input type="number" id="cantidad_a_realizar" name="cantidad_a_realizar" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Fecha de Inicio</label>
          <input type="date" id="fecha_inicio" name="fecha_inicio" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Fecha de Fin</label>
          <input type="date" id="fecha_fin" name="fecha_fin" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Estado</label>
          <select id="estado" name="estado" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="borrador">Borrador</option>
            <option value="en clasificacion">En Clasificación</option>
            <option value="empacando">Empacando</option>
            <option value="realizado">Realizado</option>
          </select>
        </div>
      </div>
      <!-- Botones -->
      <div class="flex justify-end space-x-6 mt-6">
        <button type="button" onclick="cerrarModalProduccion()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
          Cancelar
        </button>
        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600 transition text-xl">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal para Agregar Detalles de Producción -->
<div id="detalleModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-5xl">
    <!-- Encabezado -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Agregar Detalle de Producción</h3>
      <button onclick="cerrarModalDetalle()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <!-- Formulario -->
    <form id="detalleForm" class="px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Material</label>
          <select id="idmaterial" name="idmaterial" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="">Seleccione un material</option>
            <!-- Opciones de materiales se cargarán dinámicamente -->
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Cantidad Requerida</label>
          <input type="number" id="cantidad" name="cantidad" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Unidad de Medida</label>
          <input type="text" id="unidad_medida" name="unidad_medida" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
      </div>
      <!-- Botones -->
      <div class="flex justify-end space-x-6 mt-6">
        <button type="button" onclick="cerrarModalDetalle()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
          Cancelar
        </button>
        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600 transition text-xl">
          Agregar
        </button>
      </div>
    </form>
  </div>
</div>