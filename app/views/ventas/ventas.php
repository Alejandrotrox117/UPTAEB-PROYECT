<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Ventas</h1>
        <p class="text-green-500 text-lg">Ventas</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <!-- Botón para abrir el modal de Registro -->
                <button id="abrirModalBtn" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
                    Registrar
                </button>
            </div>
            <div style="overflow-x: auto;">
                <table id="Tablaventas" class="w-full text-left border-collapse mt-6 ">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <th class="py-2">Nro</th>
                            <th class="py-2">Producto </th>
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Cantidad </th>


                            <th class="py-2">Descuento</th>

                            <th class="py-2">Total</th>

                            <th class="py-2">Estatus</th>



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
<div id="ventaModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
        <!-- Encabezado -->
        <div class="px-4 py-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Registrar Venta</h3>
        </div>
        <!-- Formulario -->
        <form id="ventaForm" class="px-4 py-4">
          <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
          <div>      
          <input type="hidden" id="idventa" name="idventa">
                <label for="nro_venta" class="block font-medium mb-2">Nro Venta</label>
                <input type="text" id="nro_venta" name="nro_venta"
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400">
            </div>
            
            <div class="">
                <label for="subtotal_general" class="block font-medium mb-2">Subtotal</label>
                <input type="number" id="subtotal_general" name="subtotal_general"
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400">
            </div>
            <div class="">
                <label for="descuento_porcentaje_general" class="block font-medium mb-2">Descuento (%)</label>
                <input type="number" id="descuento_porcentaje_general" name="descuento_porcentaje_general"
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400">
            </div>
            <div class="">
                <label for="monto_descuento_general" class="block font-medium mb-2">Monto Descuento</label>
                <input type="number" id="monto_descuento_general" name="monto_descuento_general"
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400">
            </div>
            <div class="">
                <label for="estatus" class="block font-medium mb-2">Estatus</label>
                <select id="estatus" name="estatus"
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="">
                <label for="total_general" class="block font-medium mb-2">Total</label>
                <input type="number" id="total_general" name="total_general"
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400">
            </div>
            
    </div>
     <div class="grid-flow-row mt-4">
                <label for="observaciones" class="block text-gray-700 font-medium mb-2">Observaciones</label>
                <input type="text" id="observaciones" name="observaciones" 
                    class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                <div id="error-observaciones-formato" class="text-red-500 text-sm mt-1 hidden">
                  
                </div>
            </div>
    <div class="grid grid-cols-2 gap-4 mt-4">
        <div class="">
            <button type="button" id="cerrarModalBtn"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
                Cancelar
            </button>
        </div>
        <div class="flex justify-end">
            <button type="button" id="registrarVentaBtn"
                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-lg">
                Registrar
            </button>
        </div>
    </div>
    </form>
</div>
</div>