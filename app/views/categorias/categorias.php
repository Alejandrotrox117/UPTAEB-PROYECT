<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Gestion de Productos</h1>
        <p class="text-green-500 text-lg">Productos</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <!-- Botón para abrir el modal de Registro -->
                <button onclick="abrirModalCategoria()" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
                    Registrar
                </button>
            </div>

            <table id="TablaCategorias" class="w-full text-left border-collapse mt-6">
                <thead>
                    <tr class="text-gray-500 text-sm border-b">
                        <th class="py-2">Nro</th>
                        <th class="py-2">Nombre </th>
                        <th class="py-2">Descripcion </th>
                        <th class="py-2">status </th>
                  



                    </tr>
                </thead>
                <tbody class="text-gray-900">
                    <td>

                    </td>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<?php footerAdmin($data); ?>


<!-- Modal -->
<div id="categoriaModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-5xl">
        <!-- Encabezado -->
        <div class="px-8 py-6 border-b flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-800">Registrar Producto</h3>
            <button onclick="cerrarModalCategoria()" class="text-gray-600 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Formulario -->
        <form id="CategoriaForm" class="px-8 py-6">
            <!-- Campo oculto para el ID del producto (usado en actualizaciones) -->
            <input type="text" id="idcategoria" name="idcategoria">

            <div class="grid grid-cols-1 md:grid-cols-2  gap-6">
                <!-- Primera columna -->
                <div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none"></textarea>
                    </div>
                </div>

                <!-- Segunda columna -->
                <div>
    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Estatus</label>
                        <select id="estatus" name="estatus" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                  

      
            </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-6 mt-6">
                <button type="button" onclick="cerrarModalCategoria()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
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