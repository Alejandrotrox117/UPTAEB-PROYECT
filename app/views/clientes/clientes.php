<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Clientes</h1>
        <p class="text-green-500 text-lg">Clientes</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <!-- Botón para abrir el modal de Registro -->
                <button id="abrirModalBtn"   class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
                    Registrar
                </button>
            </div>
            <div style="overflow-x: auto;">
                <table id="Tablaclientes" class="w-full text-left border-collapse mt-6 ">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <th class="py-2">Nro</th>
                            <th class="py-2">Cédula </th>
                            <th class="py-2">Nombre </th>
                            <th class="py-2">Apellido </th>

                           
                            <th class="py-2">Teléfono</th>

                            <th class="py-2">Direccion</th>

                            <th class="py-2">Estatus</th>
                            <th class="py-2">Observaciones</th>


                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        <td>
                            <button class="editar-btn bg-blue-500 text-white px-4 py-2 rounded" data-idcliente="1">Editar</button>
                        </td>
                    </tbody>
                </table>
            </div>
        </div>
</main>
</div>
<?php footerAdmin($data); ?>


<!-- Modal -->
<div id="clienteModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
        <!-- Encabezado -->
        <div class="px-4 py-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Registrar cliente</h3>
            <button onclick="cerrarModalCliente()"  class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Formulario -->
        <form id="clienteForm" class="px-4 py-4">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                <div class="">
                    <input type="hidden" id="idcliente" name="idcliente" value="">
                    <label for="cedula" class="block text-gray-700 font-medium mb-2">Cédula</label>
                    <input type="text" id="cedula" name="cedula" 
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div id="error-cedula-vacio" class="text-red-500 text-sm mt-1 hidden">
                        El campo Cédula es obligatorio.
                    </div>
                    <div id="error-cedula-formato" class="text-red-500 text-sm mt-1 hidden">
                        La Cédula debe contener la estructura V-XXXXX, E-XXXXX, J-XXXXX. No debe contener espacios y solo números. Máximo 10 dígitos.
                    </div>
                </div>
                <div class="">
                    <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre</label>
                    <input type="text" id="nombre" name="nombre" 
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div id="error-nombre-vacio" class="text-red-500 text-sm mt-1 hidden">
                        El campo Nombre es obligatorio.
                    </div>
                    <div id="error-nombre-formato" class="text-red-500 text-sm mt-1 hidden">
                        El Nombre debe tener entre 2 y 50 caracteres alfabéticos.
                    </div>
                </div>
                <div class="">
                    <label for="apellido" class="block text-gray-700 font-medium mb-2">Apellido</label>
                    <input type="text" id="apellido" name="apellido" 
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div id="error-apellido-vacio" class="text-red-500 text-sm mt-1 hidden">
                        El campo Apellido es obligatorio.
                    </div>
                    <div id="error-apellido-formato" class="text-red-500 text-sm mt-1 hidden">
                        El Apellido debe tener entre 2 y 50 caracteres alfabéticos.
                    </div>
                </div>
                <div class="">
                    <label for="telefono_principal" class="block text-gray-700 font-medium mb-2">Teléfono Principal</label>
                    <input type="text" id="telefono_principal" name="telefono_principal" 
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div id="error-telefono_principal-vacio" class="text-red-500 text-sm mt-1 hidden">
                        El campo Teléfono Principal es obligatorio.
                    </div>
                    <div id="error-telefono_principal-formato" class="text-red-500 text-sm mt-1 hidden">
                        El Teléfono debe tener exactamente 10 dígitos.
                    </div>
                </div>
                <div class="flex-1 min-w-[150%]">
                    <label for="estatus" class="block text-gray-700 font-medium mb-2">Estatus</label>
                    <select id="estatus" name="estatus" 
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                    <div id="error-estatus-vacio" class="text-red-500 text-sm mt-1 hidden">
                        El campo Estatus es obligatorio.
                    </div>
                </div>
                <div class="">
                    <label for="direccion" class="block text-gray-700 font-medium mb-2">Dirección</label>
                    <input type="text" id="direccion" name="direccion" 
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    <div id="error-direccion-vacio" class="text-red-500 text-sm mt-1 hidden">
                        El campo Dirección es obligatorio.
                    </div>
                    <div id="error-direccion-formato" class="text-red-500 text-sm mt-1 hidden">
                        La Dirección debe tener entre 5 y 100 caracteres.
                    </div>
                </div>
            </div>
            <div class="grid-flow-row mt-4">
                <label for="observaciones" class="block text-gray-700 font-medium mb-2">Observaciones</label>
                <input type="text" id="observaciones" name="observaciones" 
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                <div id="error-observaciones-formato" class="text-red-500 text-sm mt-1 hidden">
                    Las Observaciones no deben exceder los 200 caracteres.
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mt-4">
                <div class="">
                    <button type="button" onclick="cerrarModalCliente()" 
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
                        Cancelar
                    </button>
                </div>
                <div class="flex justify-end">
                    <button type="submit" 
                        class="px-4 ml-10 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-lg">
                        Registrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



