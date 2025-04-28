<?php 
require_once('helpers/helpers.php');
headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Contactos</h1>
    <p class="text-green-500 text-lg">Personas</p>

    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button onclick="abrirModalPersona()"  class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar
        </button>
      </div>

      <table id="TablaPersonas" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2">Nro</th>
            <th class="py-2">Nombre </th>
            <th class="py-2">Apellido </th>
            <th class="py-2">Cédula </th>
            <th class="py-2">Rif </th>
            <th class="py-2">Tipo </th>
            <th class="py-2">Genero </th>
            <th class="py-2">Fecha de Nacimiento </th>
            <th class="py-2">Teléfono</th>
            <th class="py-2">Correo Electronico </th>
            <th class="py-2">Direccion</th>
            <th class="py-2">Ciudad</th>
            <th class="py-2">Estado</th>
            <th class="py-2">Pais</th>
            <th class="py-2">Status</th>

           
          </tr>
        </thead>
        <tbody class="text-gray-900">
        <td>
  <button class="editar-btn bg-blue-500 text-white px-4 py-2 rounded" data-idpersona="1">Editar</button>
</td>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>
<?php footerAdmin($data); ?>


<!-- Modal -->
<div id="personaModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-5xl">
    <!-- Encabezado -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Persona</h3>
      <button onclick="cerrarModalPersona()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Formulario -->
    <form id="personaForm" class="px-8 py-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Primera columna -->
          <div>
          <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Nombre</label>
              <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
  <label class="block text-gray-700 font-medium mb-2">RIF</label>
  <div class="flex">
    <select id="tipoRif" name="tipoRif" class="border rounded-l-lg px-4 py-4 text-xl focus:outline-none">
      <option value="V">V</option>
      <option value="E">E</option>
      <option value="J">J</option>
      <option value="P">P</option>
      <option value="G">G</option>
    </select>
    <input type="text" id="rif" name="rif" class="w-full border-t border-b border-r rounded-r-lg px-6 py-4 text-xl focus:outline-none" placeholder="Número de RIF">
  </div>
</div>

            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Teléfono Principal</label>
              <input type="text" id="telefono_principal" name="telefono_principal" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Ciudad</label>
              <input type="text" id="ciudad" name="ciudad" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Tipo</label>
              <select id="tipo" name="tipo" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
                <option value="">Seleccione</option>
                <option value="cliente">Cliente</option>
                <option value="proveedor">Proveedor</option>
                <option value="empleado">Empleado</option>
              </select>
            </div>
          </div>
          
          <!-- Segunda columna -->
          <div>
          <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Apellido</label>
              <input type="text" id="apellido" name="apellido" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Género</label>
              <select id="genero" name="genero" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
                <option value="">Seleccione</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Correo Electrónico</label>
              <input type="email" id="correo_electronico" name="correo_electronico" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Estado</label>
              <input type="text" id="estado" name="estado" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Estatus</label>
              <select id="estatus" name="estatus" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
          </div>
          
          <!-- Tercera columna -->
          <div>
          <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Cédula</label>
              <input type="text" id="cedula" name="cedula" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Fecha de nacimiento</label>
              <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Dirección</label>
              <input type="text" id="direccion" name="direccion" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">País</label>
              <input type="text" id="pais" name="pais" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">id</label>
              <input type="text" id="idpersona" name="idpersona" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            </div>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-6 mt-6">
          <button type="button" onclick="cerrarModalPersona()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
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





