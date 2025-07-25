<?php headerAdmin($data); ?>

<!-- Contenedor principal con fondo gris claro y espaciado -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 lg:p-8">
  <!-- Saludo y Título de la Página -->
  <div id="dashboard-header" class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
      Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋
    </h1>
    <p class="mt-1 text-base text-gray-500">
      <?php echo $data['page_title']; ?> - Reportes Estadísticos Avanzados
    </p>
  </div>

  <!-- Tarjetas de Métricas Principales MEJORADAS -->
  <div id="dashboard-metrics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 flex items-center gap-4 border-l-4 border-blue-500">
      <div class="p-3 bg-blue-100 text-blue-600 rounded-full">
        <i class="fas fa-dollar-sign text-xl"></i>
      </div>
      <div>
        <h2 class="text-gray-500 text-sm font-medium">Ventas de Hoy</h2>
        <p id="ventasHoy" class="text-2xl font-bold text-gray-800">$0.00</p>
        <p id="ventasHoyComparacion" class="text-xs text-green-600">↑ +0% vs ayer</p>
      </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 flex items-center gap-4 border-l-4 border-red-500">
      <div class="p-3 bg-red-100 text-red-600 rounded-full">
        <i class="fas fa-shopping-cart text-xl"></i>
      </div>
      <div>
        <h2 class="text-gray-500 text-sm font-medium">Compras de Hoy</h2>
        <p id="comprasHoy" class="text-2xl font-bold text-gray-800">$0.00</p>
        <p id="comprasHoyComparacion" class="text-xs text-red-600">↓ -0% vs ayer</p>
      </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 flex items-center gap-4 border-l-4 border-green-500">
      <div class="p-3 bg-green-100 text-green-600 rounded-full">
        <i class="fas fa-boxes text-xl"></i>
      </div>
      <div>
        <h2 class="text-gray-500 text-sm font-medium">Valor Inventario</h2>
        <p id="inventarioTotal" class="text-2xl font-bold text-gray-800">$0.00</p>
        <!-- CORREGIDO: ID añadido para que JS pueda actualizarlo -->
        <p id="productosRotacion" class="text-xs text-blue-600">0 productos en rotación</p>
      </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 flex items-center gap-4 border-l-4 border-purple-500">
      <div class="p-3 bg-purple-100 text-purple-600 rounded-full">
        <i class="fas fa-cogs text-xl"></i>
      </div>
      <div>
        <h2 class="text-gray-500 text-sm font-medium">Producciones Activas</h2>
        <p id="empleadosActivos" class="text-2xl font-bold text-gray-800">0</p>
        <!-- CORREGIDO: ID añadido para que JS pueda actualizarlo -->
        <p id="eficienciaPromedio" class="text-xs text-purple-600">0% eficiencia promedio</p>
      </div>
    </div>
  </div>

  <!-- MANTENER TODA LA SECCIÓN ORIGINAL DE REPORTES FINANCIEROS -->
  <div id="dashboard-reports" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Columna de Ingresos - SIN CAMBIOS -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Reporte de Ingresos (Conciliados)</h2>
        <button id="btnDescargarIngresos" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
          <i class="fas fa-download"></i>
          <span>Descargar PDF</span>
        </button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label for="fecha_desde_ingresos" class="text-sm font-medium text-gray-700">Desde:</label>
          <input type="date" id="fecha_desde_ingresos" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"/>
        </div>
        <div>
          <label for="fecha_hasta_ingresos" class="text-sm font-medium text-gray-700">Hasta:</label>
          <input type="date" id="fecha_hasta_ingresos" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"/>
        </div>
        <div>
          <label for="filtro_tipo_pago_ingresos" class="text-sm font-medium text-gray-700">Tipo de Pago:</label>
          <select id="filtro_tipo_pago_ingresos" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">Todos</option>
            <?php foreach ($data['tipos_pago'] as $tipo): ?>
            <option value="<?php echo $tipo['idtipo_pago']; ?>"><?php echo $tipo['nombre']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div id="error-ingresos" class="text-red-600 text-sm mb-2 h-4"></div>
      <div class="h-64 w-full"><canvas id="graficoIngresos"></canvas></div>
      <p class="text-right mt-4 font-bold text-lg text-gray-700">
        Total Ingresos: <span id="totalIngresos">$0.00</span>
      </p>
    </div>

    <!-- Columna de Egresos - SIN CAMBIOS -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Reporte de Egresos (Conciliados)</h2>
        <button id="btnDescargarEgresos" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
          <i class="fas fa-download"></i>
          <span>Descargar PDF</span>
        </button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <input type="date" id="fecha_desde_egresos" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Desde"/>
        <input type="date" id="fecha_hasta_egresos" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Hasta"/>
        <select id="filtro_tipo_pago_egresos" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <option value="">Tipo de Pago (Todos)</option>
          <?php foreach ($data['tipos_pago'] as $tipo): ?>
          <option value="<?php echo $tipo['idtipo_pago']; ?>"><?php echo $tipo['nombre']; ?></option>
          <?php endforeach; ?>
        </select>
        <select id="filtro_tipo_egreso" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <option value="">Tipo de Egreso (Todos)</option>
          <?php foreach ($data['tipos_egreso'] as $tipo): ?>
          <option value="<?php echo $tipo; ?>"><?php echo $tipo; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="error-egresos" class="text-red-600 text-sm mb-2 h-4"></div>
      <div class="h-64 w-full"><canvas id="graficoEgresos"></canvas></div>
      <p class="text-right mt-4 font-bold text-lg text-gray-700">
        Total Egresos: <span id="totalEgresos">$0.00</span>
      </p>
    </div>
  </div>

  <!-- MANTENER Reporte de Compras Finalizadas - SIN CAMBIOS -->
  <div class="bg-white p-6 rounded-xl shadow-sm mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h2 class="text-xl font-semibold text-gray-800">Reporte de Compras Finalizadas</h2>
      <div class="flex items-center gap-4">
        <button id="btnGenerarReporteCompras" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition">Generar Reporte</button>
        <button id="btnDescargarReporteCompras" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
          <i class="fas fa-download"></i>
          <span>PDF</span>
        </button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      <select id="filtro_proveedor_compras" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <option value="">Proveedor (Todos)</option>
        <?php foreach ($data['proveedores'] as $proveedor): ?>
        <option value="<?php echo $proveedor['idproveedor']; ?>"><?php echo $proveedor['nombre_completo']; ?></option>
        <?php endforeach; ?>
      </select>
      <select id="filtro_producto_compras" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <option value="">Producto (Todos)</option>
        <?php foreach ($data['productos'] as $producto): ?>
        <option value="<?php echo $producto['idproducto']; ?>"><?php echo $producto['nombre']; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" id="fecha_desde_compras" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"/>
      <input type="date" id="fecha_hasta_compras" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"/>
    </div>
    <div id="error-compras" class="text-red-600 text-sm mb-2 h-4"></div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Nro. Compra</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Producto</th>
            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
          </tr>
        </thead>
        <tbody id="comprasReporteBody" class="bg-white divide-y divide-gray-200">
          <tr><td colspan="7" class="py-4 text-center text-gray-500">Filtra y genera el reporte para ver los datos.</td></tr>
        </tbody>
        <tfoot class="bg-gray-100 font-bold">
          <tr>
            <td colspan="6" class="px-4 py-3 text-right text-gray-700">TOTAL GENERAL:</td>
            <td id="comprasReporteTotal" class="px-4 py-3 text-right text-gray-800">$0.00</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Panel Ejecutivo Mejorado -->
<div class="bg-white p-6 rounded-xl shadow-xl mb-8 border border-gray-100">
  <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800">Panel de Control Ejecutivo</h2>
    <button id="btnDescargarReporteEjecutivo"
      class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow">
      <i class="fas fa-file-pdf"></i>
      <span>Reporte PDF</span>
    </button>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-gray-50 p-5 rounded-lg shadow-sm flex flex-col items-center">
      <h3 class="text-xs text-gray-500 mb-1">Margen de Ganancia</h3>
      <p id="margenGanancia" class="text-2xl font-bold text-blue-700">0%</p>
    </div>
    <div class="bg-gray-50 p-5 rounded-lg shadow-sm flex flex-col items-center">
      <h3 class="text-xs text-gray-500 mb-1">ROI del Mes</h3>
      <p id="roiMes" class="text-2xl font-bold text-green-600">0%</p>
    </div>
    <div class="bg-gray-50 p-5 rounded-lg shadow-sm flex flex-col items-center">
      <h3 class="text-xs text-gray-500 mb-1">Rotación Inventario</h3>
      <p id="rotacionInventario" class="text-2xl font-bold text-purple-600">0 días</p>
    </div>
    <div class="bg-gray-50 p-5 rounded-lg shadow-sm flex flex-col items-center">
      <h3 class="text-xs text-gray-500 mb-1">Productividad</h3>
      <p id="productividadGeneral" class="text-2xl font-bold text-orange-500">0 kg/día</p>
    </div>
  </div>
</div>

  <!-- Análisis de Tendencias -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Tendencias de Ventas (6 meses)</h3>
      <div class="h-80"><canvas id="graficoTendenciasVentas"></canvas></div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Análisis de Rentabilidad por Producto</h3>
      <div class="h-80"><canvas id="graficoRentabilidadProductos"></canvas></div>
    </div>
  </div>

  <!-- Análisis de Producción Avanzado -->
  <div class="bg-white p-6 rounded-xl shadow-sm mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Centro de Control de Producción</h2>
    
    <!-- Filtros de Producción -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <input type="date" id="prod_fecha_desde" class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Fecha Desde">
      <input type="date" id="prod_fecha_hasta" class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Fecha Hasta">
      <select id="prod_empleado" class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
        <option value="">Todos los Empleados</option>
        <?php foreach ($data['empleados'] as $empleado): ?>
        <option value="<?= $empleado['idempleado'] ?>"><?= $empleado['nombre_completo'] ?></option>
        <?php endforeach; ?>
      </select>
      <select id="prod_estado" class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
        <option value="">Todos los Estados</option>
        <option value="borrador">Borrador</option>
        <option value="en_clasificacion">En Clasificación</option>
        <option value="empacando">Empacando</option>
        <option value="realizado">Realizado</option>
      </select>
    </div>

    <!-- Gráficos de Producción -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-700 mb-2">Eficiencia por Empleado</h4>
        <div class="h-64"><canvas id="graficoEficienciaEmpleados"></canvas></div>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-700 mb-2">Estados de Producción</h4>
        <div class="h-64"><canvas id="graficoEstadosProduccion"></canvas></div>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-700 mb-2">Cumplimiento de Tareas</h4>
        <div class="h-64"><canvas id="graficoCumplimientoTareas"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Análisis de Clientes y Proveedores -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Clientes -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Clientes por Valor</h3>
      <div class="h-80"><canvas id="graficoTopClientes"></canvas></div>
    </div>
    <!-- Top Proveedores -->
    <div class="bg-white p-6 rounded-xl shadow-sm">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Proveedores por Compras</h3>
      <div class="h-80"><canvas id="graficoTopProveedores"></canvas></div>
    </div>
  </div>

  <!-- Análisis de Inventario Detallado -->
  <div class="bg-white p-6 rounded-xl shadow-sm mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Centro de Control de Inventario</h2>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <div class="bg-red-50 p-4 rounded-lg">
        <h4 class="font-medium text-red-700 mb-2">Stock Crítico</h4>
        <div class="h-48"><canvas id="graficoStockCritico"></canvas></div>
      </div>
      <div class="bg-blue-50 p-4 rounded-lg">
        <h4 class="font-medium text-blue-700 mb-2">Valor por Categoría</h4>
        <div class="h-48"><canvas id="graficoValorCategoria"></canvas></div>
      </div>
      <div class="bg-green-50 p-4 rounded-lg">
        <h4 class="font-medium text-green-700 mb-2">Movimientos del Mes</h4>
        <!-- NUEVO: Canvas que faltaba -->
        <div class="h-48"><canvas id="graficoMovimientosInventario"></canvas></div>
      </div>
      <div class="bg-purple-50 p-4 rounded-lg">
        <h4 class="font-medium text-purple-700 mb-2">Productos Más Vendidos</h4>
        <div class="h-48"><canvas id="graficoProductosMasVendidos"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Tabla de KPIs en Tiempo Real -->
  <div id="dashboard-kpis" class="bg-white p-6 rounded-xl shadow-sm">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">KPIs</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-3 text-left">Métrica</th>
            <th class="p-3 text-right">Hoy</th>
            <th class="p-3 text-right">Ayer</th>
            <th class="p-3 text-right">Esta Semana</th>
            <th class="p-3 text-right">Mes Pasado</th>
            <th class="p-3 text-center">Tendencia</th>
          </tr>
        </thead>
        <tbody id="tablaKPIs">
          <!-- Datos generados por JavaScript -->
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- Scripts específicos del dashboard -->
<script src="/project/app/assets/js/ayuda/dashboard-tour.js"></script>

<?php footerAdmin($data); ?>