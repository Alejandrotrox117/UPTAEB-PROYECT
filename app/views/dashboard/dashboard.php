<?php headerAdmin($data); ?>

<main class="flex-1 p-6 bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">¡Hola, <?= htmlspecialchars($data['usuario_nombre']) ?>! 👋</h2>
            <p class="text-gray-600 mt-1">Panel de control - Recuperadora de Materiales</p>
        </div>
        <div class="flex items-center space-x-4">
            <button id="btnActualizar" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Actualizar
            </button>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-8 hidden">
        <div class="inline-flex items-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500 mr-3"></div>
            <span class="text-gray-600">Cargando datos...</span>
        </div>
    </div>

    <!-- Cards Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Compras de Hoy -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mr-4">
                    <i class="fas fa-truck text-purple-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Compras de Hoy</p>
                    <p id="comprasHoy" class="text-2xl font-bold text-gray-900">
                        <span class="loading-skeleton">--</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Ventas de Hoy -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mr-4">
                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Ventas de Hoy</p>
                    <p id="ventasHoy" class="text-2xl font-bold text-gray-900">
                        <span class="loading-skeleton">--</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Inventario Total -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mr-4">
                    <i class="fas fa-boxes text-blue-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Inventario Total</p>
                    <p id="inventarioTotal" class="text-2xl font-bold text-gray-900">
                        <span class="loading-skeleton">--</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Empleados Activos -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg mr-4">
                    <i class="fas fa-users text-orange-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Empleados</p>
                    <p id="empleadosActivos" class="text-2xl font-bold text-gray-900">
                        <span class="loading-skeleton">--</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Tareas Activas -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-lg mr-4">
                    <i class="fas fa-tasks text-red-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-500 text-sm font-medium">Tareas Activas</p>
                    <p id="tareasActivas" class="text-2xl font-bold text-gray-900">
                        <span class="loading-skeleton">--</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <div id="alertasContainer" class="mb-8"></div>

    <!-- Sección Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Últimas Compras -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-truck text-purple-500 mr-2"></i>
                    Últimas Compras
                </h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Nro Compra</th>
                                <th class="pb-3">Proveedor</th>
                                <th class="pb-3">Fecha</th>
                                <th class="pb-3">Total</th>
                                <th class="pb-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="comprasBody" class="text-sm">
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Cargando datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Últimas Ventas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-shopping-cart text-green-500 mr-2"></i>
                    Últimas Ventas
                </h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Nro Venta</th>
                                <th class="pb-3">Cliente</th>
                                <th class="pb-3">Fecha</th>
                                <th class="pb-3">Total</th>
                            </tr>
                        </thead>
                        <tbody id="ventasBody" class="text-sm">
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Cargando datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tareas de Producción y Stock Bajo -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Tareas Pendientes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-tasks text-orange-500 mr-2"></i>
                    Tareas de Producción
                </h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">ID</th>
                                <th class="pb-3">Empleado</th>
                                <th class="pb-3">Producto</th>
                                <th class="pb-3">Cantidad</th>
                                <th class="pb-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tareasBody" class="text-sm">
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Cargando datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Productos con Stock Bajo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    Productos - Stock Bajo
                </h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Producto</th>
                                <th class="pb-3">Existencia</th>
                                <th class="pb-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="stockBajoBody" class="text-sm">
                            <tr>
                                <td colspan="3" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Cargando datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Gráfico de Ventas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-chart-area text-green-500 mr-2"></i>
                    Ventas Mensuales
                </h3>
            </div>
            <div class="p-6">
                <div class="relative h-80">
                    <canvas id="graficoVentas"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Compras -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-chart-bar text-purple-500 mr-2"></i>
                    Compras Mensuales
                </h3>
            </div>
            <div class="p-6">
                <div class="relative h-80">
                    <canvas id="graficoCompras"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- CSS adicional para loading skeleton -->
<style>
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
    display: inline-block;
    height: 1em;
    width: 60px;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.alerta {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php footerAdmin($data); ?>