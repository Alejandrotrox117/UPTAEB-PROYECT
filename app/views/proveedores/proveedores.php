<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
    <input type="text" placeholder="Buscar..." class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Gestión de Proveedores</h1>
    <p class="text-green-500 text-lg">Lista de Proveedores Registrados</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <button onclick="abrirModalProveedor('Registrar Proveedor', 'proveedorForm', 'POST', 'proveedores/createProveedor')" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar Proveedor
        </button>
      </div>

      <div class="overflow-x-auto">
        <table id="TablaProveedores" class="w-full text-left border-collapse mt-6">
          <thead>
            <tr class="text-gray-500 text-sm border-b">
              <th class="py-2 px-3">ID</th>
              <th class="py-2 px-3">Nombre/Razón Social</th>
              <th class="py-2 px-3">Apellido (Contacto)</th>
              <th class="py-2 px-3">Identificación (RIF/CI)</th>
              <th class="py-2 px-3">Teléfono</th>
              <th class="py-2 px-3">Correo</th>
              <th class="py-2 px-3">Estatus</th>
              <th class="py-2 px-3">Acciones</th>
            </tr>
          </thead>
          <tbody class="text-gray-900">
            <!-- Las filas se llenarán con DataTables -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
</div>
<?php footerAdmin($data); ?>


<!-- Modal para Registrar/Editar Proveedor -->
<div id="proveedorModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-4xl"> 
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 id="modalProveedorTitulo" class="text-2xl font-bold text-gray-800">Registrar Proveedor</h3>
      <button onclick="cerrarModalProveedor()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <form id="proveedorForm" class="px-8 py-6 max-h-[70vh] overflow-y-auto">
      <input type="hidden" id="idproveedor" name="idproveedor"> 
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
        <div>
          <label class="block text-gray-700 font-medium mb-1">Nombre o Razón Social <span class="text-red-500">*</span></label>
          <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Apellido (Contacto, si aplica)</label>
          <input type="text" id="apellido" name="apellido" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Identificación (RIF/CI) <span class="text-red-500">*</span></label>
          <input type="text" id="identificacion" name="identificacion" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Teléfono Principal <span class="text-red-500">*</span></label>
          <input type="text" id="telefono_principal" name="telefono_principal" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500" required>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Correo Electrónico</label>
          <input type="email" id="correo_electronico" name="correo_electronico" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
         <div>
          <label class="block text-gray-700 font-medium mb-1">Género (Contacto, si aplica)</label>
          <select id="genero" name="genero" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">Seleccione...</option>
            <option value="masculino">Masculino</option>
            <option value="femenino">Femenino</option>
            <option value="no_aplica">No Aplica</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="md:col-span-2"> 
          <label class="block text-gray-700 font-medium mb-1">Dirección</label>
          <textarea id="direccion" name="direccion" rows="2" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Fecha de Nacimiento/Constitución</label>
          <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Estatus</label>
          <select id="estatus" name="estatus" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="ACTIVO">Activo</option>
            <option value="INACTIVO">Inactivo</option>
          </select>
        </div>
        <div class="md:col-span-2"> 
          <label class="block text-gray-700 font-medium mb-1">Observaciones</label>
          <textarea id="observaciones" name="observaciones" rows="3" class="w-full border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
      </div>

      <div class="flex justify-end space-x-4 mt-8">
        <button type="button" onclick="cerrarModalProveedor()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
          Cancelar
        </button>
        <button type="submit" id="btnSubmitProveedor" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-base font-medium">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>


<div id="modalConfirmarEliminar" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-[60]">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
    <div class="px-8 py-6 border-b">
      <h3 id="modalEliminarTitulo" class="text-2xl font-bold text-gray-800">Confirmar Eliminación</h3>
    </div>
    <div class="px-8 py-6">
      <p id="modalEliminarMensaje" class="text-gray-700 text-lg">
        ¿Estás seguro de que deseas eliminar este proveedor? Esta acción cambiará su estatus a INACTIVO.
      </p>
    </div>
    <div class="px-8 py-6 border-t flex justify-end space-x-4">
      <button type="button" onclick="cerrarModalConfirmarEliminar()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-base font-medium">
        Cancelar
      </button>
      <button type="button" id="btnConfirmarEliminacion" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-base font-medium">
        Eliminar
      </button>
    </div>
  </div>
</div>
