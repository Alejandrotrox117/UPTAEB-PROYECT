<?php headerAdmin($data); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>
    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Reportes Estadisticos</p>
    </div>

    <!-- Resumen General -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-gray-600 text-sm font-medium">Ventas de Hoy</h2>
        <p id="ventasHoy" class="text-3xl font-bold">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-gray-600 text-sm font-medium">Compras de Hoy</h2>
        <p id="comprasHoy" class="text-3xl font-bold">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-gray-600 text-sm font-medium">Inventario Total (Uds)</h2>
        <p id="inventarioTotal" class="text-3xl font-bold">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-gray-600 text-sm font-medium">Empleados Activos</h2>
        <p id="empleadosActivos" class="text-3xl font-bold">0</p>
        </div>
    </div>

    <!-- Reportes Financieros: Ingresos y Egresos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Columna de Ingresos -->
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">
            Reporte de Ingresos (Conciliados)
        </h2>
        <div class="flex flex-wrap gap-4 mb-4 items-center">
            <div>
            <label
                for="fecha_desde_ingresos"
                class="text-sm font-medium text-gray-700"
                >Desde:</label
            >
            <input
                type="date"
                id="fecha_desde_ingresos"
                name="fecha_desde_ingresos"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            </div>
            <div>
            <label
                for="fecha_hasta_ingresos"
                class="text-sm font-medium text-gray-700"
                >Hasta:</label
            >
            <input
                type="date"
                id="fecha_hasta_ingresos"
                name="fecha_hasta_ingresos"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            </div>
        </div>
        <!-- Contenedor para el mensaje de error de ingresos -->
        <div id="error-ingresos" class="text-red-600 text-sm mb-2"></div>

        <div class="h-64 w-full"><canvas id="graficoIngresos"></canvas></div>
        <p class="text-center mt-4 font-bold text-lg">
            Total Ingresos: <span id="totalIngresos">0.00</span>
        </p>
        </div>

        <!-- Columna de Egresos -->
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">
            Reporte de Egresos (Conciliados)
        </h2>
        <div class="flex flex-wrap gap-4 mb-4 items-center">
            <div>
            <label
                for="fecha_desde_egresos"
                class="text-sm font-medium text-gray-700"
                >Desde:</label
            >
            <input
                type="date"
                id="fecha_desde_egresos"
                name="fecha_desde_egresos"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            </div>
            <div>
            <label
                for="fecha_hasta_egresos"
                class="text-sm font-medium text-gray-700"
                >Hasta:</label
            >
            <input
                type="date"
                id="fecha_hasta_egresos"
                name="fecha_hasta_egresos"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            </div>
        </div>
        <!-- Contenedor para el mensaje de error de egresos -->
        <div id="error-egresos" class="text-red-600 text-sm mb-2"></div>

        <div class="h-64 w-full"><canvas id="graficoEgresos"></canvas></div>
        <p class="text-center mt-4 font-bold text-lg">
            Total Egresos: <span id="totalEgresos">0.00</span>
        </p>
        </div>
    </div>

    <!-- Gráficos y Tablas existentes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">
            Ventas Mensuales (Últimos 6 meses)
        </h2>
        <div class="h-80 w-full"><canvas id="graficoVentas"></canvas></div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Últimas Ventas</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                <th
                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                    Nro. Venta
                </th>
                <th
                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                    Cliente
                </th>
                <th
                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                    Fecha
                </th>
                <th
                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                    Total
                </th>
                </tr>
            </thead>
            <tbody id="ventasBody" class="bg-white divide-y divide-gray-200">
                <!-- Filas de ventas se insertan aquí -->
            </tbody>
            </table>
        </div>
        </div>
    </div>

<?php footerAdmin($data); ?>