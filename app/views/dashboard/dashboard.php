<?php headerAdmin($data); ?>


<main class="flex-1 p-6 bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['nombre'] ?? 'Usuario' ?> 👋</h2>
        <input type="text" placeholder="Buscar..." class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none w-64">
    </div>

    <!-- Cards Resumen -->
   <div class="flex justify-between bg-white p-6 rounded-2xl shadow-md space-x-8 mt-5">
    <!-- Tarjeta: Ventas de Hoy -->
    <div  class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full">
            <i class="fa-solid fa-cart-shopping text-blue-500 text-3xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Ventas de Hoy</p>
            <p id="ventasHoy" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
    </div>

  
    <div class="border-l border-gray-300 h-16"></div>

  
    <div  class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full">
            <i class="fa-solid fa-truck text-purple-500 text-3xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Compras de Hoy</p>
            <p id="comprasHoy" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
    </div>

   
    <div class="border-l border-gray-300 h-16"></div>

    <div  class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full">
            <i class="fa-solid fa-boxes-stacked text-yellow-500 text-3xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Inventario Disponible</p>
            <p id="inventarioTotal" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
    </div>

   
    <div class="border-l border-gray-300 h-16"></div>

    
    <div  class="flex items-center space-x-4">
        <div class="flex items-center justify-center w-16 h-16 bg-red-100 rounded-full">
            <i class="fa-solid fa-users text-red-500 text-3xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Empleados Activos</p>
            <p id="empleadosActivos" class="text-gray-900 text-2xl font-bold">0</p>
        </div>
    </div>
</div>
<br><br><hr>
   
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Últimas Ventas</h3>
            <table id="tablaVentas" class="w-full text-left table-auto">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-2">Nro Venta</th>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody id="ventasBody">
                    <tr><td colspan="4" class="text-center py-4">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>

       
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Tareas Pendientes</h3>
            <table id="tablaTareas" class="w-full text-left table-auto">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Empleado</th>
                        <th class="px-4 py-2">Cantidad</th>
                        <th class="px-4 py-2">Estado</th>
                    </tr>
                </thead>
                <tbody id="tareasBody">
                    <tr><td colspan="4" class="text-center py-4">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

   
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Ventas Mensuales</h3>
            <canvas id="graficoVentas"></canvas>
        </div>

       
    </section>
</main>



<?php footerAdmin($data); ?>