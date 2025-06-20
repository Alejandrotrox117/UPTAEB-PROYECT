



let graficoIngresos,
  graficoEgresos,
  graficoVentas,
  graficoResumenTorta,
  graficoComprasVentas;
let graficoTendenciasVentas,
  graficoRentabilidadProductos,
  graficoEficienciaEmpleados;
let graficoEstadosProduccion,
  graficoCumplimientoTareas,
  graficoTopClientes,
  graficoTopProveedores;
let graficoStockCritico,
  graficoValorCategoria,
  graficoMovimientosInventario,
  graficoProductosMasVendidos;






document.addEventListener("DOMContentLoaded", inicializarDashboard);

/**
 * Función principal que orquesta toda la inicialización del dashboard.
 * Está envuelta en un try...catch para capturar cualquier error y evitar
 * que el script falle silenciosamente.
 */
function inicializarDashboard() {
  try {
    
    console.log("✅ Inicializando Dashboard...");

    configurarFechasPorDefecto();
    configurarEventListeners();

    
    cargarDatosDashboard();
    cargarDashboardAvanzado();
    cargarReporteCompras();

    
    setInterval(cargarDashboardAvanzado, 300000); 

    console.log("🚀 Dashboard inicializado correctamente.");
  } catch (error) {
    
    console.error("❌ Error fatal durante la inicialización del dashboard:", error);
  }
}





/**
 * Establece las fechas de inicio y fin del mes actual en todos los
 * campos de fecha del dashboard.
 */
function configurarFechasPorDefecto() {
  const hoy = new Date();
  const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
    .toISOString()
    .split("T")[0];
  const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)
    .toISOString()
    .split("T")[0];

  const fechaInputs = document.querySelectorAll('input[type="date"]');
  fechaInputs.forEach(input => {
    if (input.id.includes("_desde") || input.id.includes("_inicio")) {
      input.value = primerDiaMes;
    } else {
      input.value = ultimoDiaMes;
    }
  });
}

/**
 * Añade todos los event listeners a los botones y filtros de forma segura.
 */
function configurarEventListeners() {
  /**
   * Helper para añadir listeners de forma segura.
   * Verifica si el elemento existe antes de añadir el listener.
   * @param {string} id - El ID del elemento HTML.
   * @param {string} event - El tipo de evento (ej. 'click', 'change').
   * @param {Function} handler - La función a ejecutar.
   */
  const addSafeListener = (id, event, handler) => {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener(event, handler);
    } else {
      
      console.warn(`Elemento para listener no encontrado: #${id}`);
    }
  };

  
  addSafeListener("fecha_desde_ingresos", "change", cargarDatosDashboard);
  addSafeListener("fecha_hasta_ingresos", "change", cargarDatosDashboard);
  addSafeListener("filtro_tipo_pago_ingresos", "change", cargarDatosDashboard);
  addSafeListener("fecha_desde_egresos", "change", cargarDatosDashboard);
  addSafeListener("fecha_hasta_egresos", "change", cargarDatosDashboard);
  addSafeListener("filtro_tipo_pago_egresos", "change", cargarDatosDashboard);
  addSafeListener("filtro_tipo_egreso", "change", cargarDatosDashboard);

  
  addSafeListener("prod_fecha_desde", "change", cargarDashboardAvanzado);
  addSafeListener("prod_fecha_hasta", "change", cargarDashboardAvanzado);
  addSafeListener("prod_empleado", "change", cargarDashboardAvanzado);
  addSafeListener("prod_estado", "change", cargarDashboardAvanzado);

  
  addSafeListener("fecha_desde_compras", "change", cargarReporteCompras);
  addSafeListener("fecha_hasta_compras", "change", cargarReporteCompras);
  addSafeListener("filtro_proveedor_compras", "change", cargarReporteCompras);
  addSafeListener("filtro_producto_compras", "change", cargarReporteCompras);

  
  addSafeListener("btnGenerarReporteCompras", "click", cargarReporteCompras);
  addSafeListener("btnDescargarIngresos", "click", descargarIngresosPDF);
  addSafeListener("btnDescargarEgresos", "click", descargarEgresosPDF);
  addSafeListener("btnDescargarReporteCompras", "click", descargarReporteCompras);
  addSafeListener("btnDescargarReporteEjecutivo", "click", descargarReporteEjecutivo);
}






function cargarDatosDashboard() {
  if (
    !validarRangoFechas(
      "fecha_desde_ingresos",
      "fecha_hasta_ingresos",
      "error-ingresos"
    ) ||
    !validarRangoFechas(
      "fecha_desde_egresos",
      "fecha_hasta_egresos",
      "error-egresos"
    )
  ) {
    return;
  }

  const params = new URLSearchParams({
    fecha_desde_ingresos: document.getElementById("fecha_desde_ingresos").value,
    fecha_hasta_ingresos: document.getElementById("fecha_hasta_ingresos").value,
    idtipo_pago_ingresos: document.getElementById("filtro_tipo_pago_ingresos")
      .value,
    fecha_desde_egresos: document.getElementById("fecha_desde_egresos").value,
    fecha_hasta_egresos: document.getElementById("fecha_hasta_egresos").value,
    idtipo_pago_egresos: document.getElementById("filtro_tipo_pago_egresos")
      .value,
    tipo_egreso: document.getElementById("filtro_tipo_egreso").value,
  });

  fetch(`dashboard/getDashboardData?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
      actualizarResumen(data.resumen);
      
      renderizarGraficoIngresos(data.reporteIngresos);
      renderizarGraficoEgresos(data.reporteEgresos);
    })
    .catch(error =>
      console.error("Error al cargar datos del dashboard:", error)
    );
}

function cargarDashboardAvanzado() {
  const params = new URLSearchParams({
    prod_fecha_desde: document.getElementById("prod_fecha_desde").value,
    prod_fecha_hasta: document.getElementById("prod_fecha_hasta").value,
    prod_empleado: document.getElementById("prod_empleado").value,
    prod_estado: document.getElementById("prod_estado").value,
  });

  fetch(`dashboard/getDashboardAvanzado?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
      actualizarKPIsEjecutivos(data.kpisEjecutivos);
      renderizarGraficoTendenciasVentas(data.tendenciasVentas);
      renderizarGraficoRentabilidadProductos(data.rentabilidadProductos);
      renderizarGraficoEficienciaEmpleados(data.eficienciaEmpleados);
      renderizarGraficoEstadosProduccion(data.estadosProduccion);
      renderizarGraficoCumplimientoTareas(data.cumplimientoTareas);
      renderizarGraficoTopClientes(data.topClientes);
      renderizarGraficoTopProveedores(data.topProveedores);
      renderizarAnalisisInventario(data.analisisInventario);
      renderizarTablaKPIs(data.kpisTiempoReal);
    })
    .catch(error =>
      console.error("Error al cargar dashboard avanzado:", error)
    );
}

function cargarReporteCompras() {
  if (
    !validarRangoFechas(
      "fecha_desde_compras",
      "fecha_hasta_compras",
      "error-compras"
    )
  ) {
    return;
  }

  const params = new URLSearchParams({
    fecha_desde: document.getElementById("fecha_desde_compras").value,
    fecha_hasta: document.getElementById("fecha_hasta_compras").value,
    idproveedor: document.getElementById("filtro_proveedor_compras").value,
    idproducto: document.getElementById("filtro_producto_compras").value,
  });

  const tbody = document.getElementById("comprasReporteBody");
  tbody.innerHTML =
    '<tr><td colspan="7" class="p-4 text-center">Cargando...</td></tr>';

  fetch(`dashboard/getReporteComprasData?${params.toString()}`)
    .then(response => {
      if (!response.ok)
        throw new Error(`HTTP error! status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      renderizarTablaCompras(data);
    })
    .catch(error => {
      console.error("Error al cargar reporte de compras:", error);
      tbody.innerHTML =
        '<tr><td colspan="7" class="p-4 text-center text-red-500">Error al cargar el reporte.</td></tr>';
    });
}












function renderizarGraficoIngresos(datos) {
  const labels = datos.map(d => d.categoria);
  const valores = datos.map(d => d.total);
  const totalIngresos = valores.reduce((sum, val) => sum + parseFloat(val), 0);

  document.getElementById("totalIngresos").textContent =
    totalIngresos.toLocaleString("es-VE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const ctx = document.getElementById("graficoIngresos").getContext("2d");
  if (graficoIngresos) graficoIngresos.destroy();

  graficoIngresos = new Chart(ctx, {
    type: "pie",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Ingresos por Tipo",
          data: valores,
          backgroundColor: [
            "#10B981",
            "#3B82F6",
            "#F59E0B",
            "#8B5CF6",
            "#EF4444",
          ],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: "top" } },
    },
  });
}

function renderizarGraficoEgresos(datos) {
  const labels = datos.map(d => d.categoria);
  const valores = datos.map(d => d.total);
  const totalEgresos = valores.reduce((sum, val) => sum + parseFloat(val), 0);

  document.getElementById("totalEgresos").textContent =
    totalEgresos.toLocaleString("es-VE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const ctx = document.getElementById("graficoEgresos").getContext("2d");
  if (graficoEgresos) graficoEgresos.destroy();

  graficoEgresos = new Chart(ctx, {
    type: "pie",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Egresos por Categoría",
          data: valores,
          backgroundColor: [
            "#EF4444",
            "#F59E0B",
            "#6B7280",
            "#3B82F6",
            "#10B981",
          ],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: "top" } },
    },
  });
}




function validarRangoFechas(idDesde, idHasta, idErrorContainer) {
  const fechaDesdeInput = document.getElementById(idDesde);
  const fechaHastaInput = document.getElementById(idHasta);
  const errorContainer = document.getElementById(idErrorContainer);

  
  if (!fechaDesdeInput || !fechaHastaInput || !errorContainer) {
    console.error("Faltan elementos para validarRangoFechas:", {
      idDesde,
      idHasta,
      idErrorContainer,
    });
    return false;
  }

  const fechaDesde = fechaDesdeInput.value;
  const fechaHasta = fechaHastaInput.value;

  if (fechaDesde && fechaHasta) {
    if (fechaDesde > fechaHasta) {
      errorContainer.textContent =
        "La fecha 'Desde' no puede ser posterior a la fecha 'Hasta'.";
      fechaDesdeInput.classList.add("border-red-500");
      fechaHastaInput.classList.add("border-red-500");
      return false;
    }
  }

  errorContainer.textContent = "";
  fechaDesdeInput.classList.remove("border-red-500");
  fechaHastaInput.classList.remove("border-red-500");
  return true;
}

function renderizarTablaCompras(data) {
  const tbody = document.getElementById("comprasReporteBody");
  const tfootTotal = document.getElementById("comprasReporteTotal");
  tbody.innerHTML = "";
  let totalGeneral = 0;

  if (!data || data.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="7" class="p-4 text-center text-gray-500">No se encontraron resultados con los filtros seleccionados.</td></tr>';
    tfootTotal.textContent = "0.00";
    return;
  }

  data.forEach(item => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="px-2 py-2 whitespace-nowrap">${item.fecha}</td>
      <td class="px-2 py-2 whitespace-nowrap">${item.nro_compra}</td>
      <td class="px-2 py-2">${item.proveedor}</td>
      <td class="px-2 py-2">${item.producto}</td>
      <td class="px-2 py-2 text-right">${parseFloat(
        item.cantidad
      ).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</td>
      <td class="px-2 py-2 text-right">${parseFloat(
        item.precio_unitario_compra
      ).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</td>
      <td class="px-2 py-2 text-right">${parseFloat(
        item.subtotal_linea
      ).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</td>
    `;
    tbody.appendChild(tr);
    totalGeneral += parseFloat(item.subtotal_linea);
  });

  tfootTotal.textContent = totalGeneral.toLocaleString("es-VE", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function actualizarResumen(resumen) {
  const formatMoney = value =>
    parseFloat(value || 0).toLocaleString("es-VE", {
      style: "currency",
      currency: "VES",
    });

  
  document.getElementById("ventasHoy").textContent = formatMoney(
    resumen.ventas_hoy
  );
  document.getElementById("comprasHoy").textContent = formatMoney(
    resumen.compras_hoy
  );
  document.getElementById("inventarioTotal").textContent = formatMoney(
    resumen.valor_inventario
  );
  document.getElementById("empleadosActivos").textContent =
    resumen.producciones_activas;

  
  const ventasComparacion =
    resumen.ventas_ayer > 0
      ? ((resumen.ventas_hoy - resumen.ventas_ayer) / resumen.ventas_ayer) * 100
      : 0;
  const comprasComparacion =
    resumen.compras_ayer > 0
      ? ((resumen.compras_hoy - resumen.compras_ayer) / resumen.compras_ayer) *
        100
      : 0;

  document.getElementById(
    "ventasHoyComparacion"
  ).textContent = `${ventasComparacion >= 0 ? "↑" : "↓"} ${ventasComparacion.toFixed(
    1
  )}% vs ayer`;
  document.getElementById(
    "comprasHoyComparacion"
  ).textContent = `${comprasComparacion >= 0 ? "↑" : "↓"} ${comprasComparacion.toFixed(
    1
  )}% vs ayer`;

  
  document.getElementById("ventasHoyComparacion").className = `text-xs ${
    ventasComparacion >= 0 ? "text-green-600" : "text-red-600"
  }`;
  document.getElementById("comprasHoyComparacion").className = `text-xs ${
    comprasComparacion >= 0 ? "text-green-600" : "text-red-600"
  }`;
}



function actualizarKPIsEjecutivos(kpis) {
 console.log("Actualizando KPIs Ejecutivos:", kpis);
  if (!kpis) {
    console.error("Datos de KPIs no válidos recibidos.");
    return;
  }
  document.getElementById("margenGanancia").textContent = `${parseFloat(
    kpis.margen_ganancia || 0
  ).toFixed(1)}%`;
  document.getElementById("roiMes").textContent = `${parseFloat(
    kpis.roi_mes || 0
  ).toFixed(1)}%`;
  document.getElementById("rotacionInventario").textContent = `${Math.round(
    parseFloat(kpis.rotacion_inventario || 0)
  )} días`;
  document.getElementById("productividadGeneral").textContent = `${parseFloat(
    kpis.productividad_general || 0
  ).toFixed(1)} kg/día`;
}

function renderizarGraficoTendenciasVentas(datos) {
  const ctx = document
    .getElementById("graficoTendenciasVentas")
    .getContext("2d");
  if (graficoTendenciasVentas) graficoTendenciasVentas.destroy();

  graficoTendenciasVentas = new Chart(ctx, {
    type: "line",
    data: {
      labels: datos.map(d => d.periodo),
      datasets: [
        {
          label: "Ventas Totales",
          data: datos.map(d => d.total_ventas),
          borderColor: "#3B82F6",
          backgroundColor: "rgba(59, 130, 246, 0.1)",
          tension: 0.4,
          fill: true,
        },
        {
          label: "Número de Ventas",
          data: datos.map(d => d.num_ventas),
          borderColor: "#10B981",
          backgroundColor: "rgba(16, 185, 129, 0.1)",
          tension: 0.4,
          yAxisID: "y1",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, position: "left" },
        y1: { type: "linear", display: true, position: "right", beginAtZero: true },
      },
      plugins: { legend: { position: "top" } },
    },
  });
}

function renderizarGraficoRentabilidadProductos(datos) {
  const ctx = document
    .getElementById("graficoRentabilidadProductos")
    .getContext("2d");
  if (graficoRentabilidadProductos) graficoRentabilidadProductos.destroy();

  graficoRentabilidadProductos = new Chart(ctx, {
    type: "bar",
    data: {
      labels: datos.map(d => d.nombre),
      datasets: [
        {
          label: "Ingresos",
          data: datos.map(d => d.ingresos),
          backgroundColor: "#10B981",
        },
        {
          label: "Costos",
          data: datos.map(d => d.costos),
          backgroundColor: "#EF4444",
        },
        {
          label: "Ganancia Neta",
          data: datos.map(d => d.ganancia_neta),
          backgroundColor: "#3B82F6",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { position: "top" } },
    },
  });
}

function renderizarGraficoEficienciaEmpleados(datos) {
  const ctx = document
    .getElementById("graficoEficienciaEmpleados")
    .getContext("2d");
  if (graficoEficienciaEmpleados) graficoEficienciaEmpleados.destroy();

  graficoEficienciaEmpleados = new Chart(ctx, {
    type: "bar",
    data: {
      labels: datos.map(d => d.empleado_nombre),
      datasets: [
        {
          label: "% Eficiencia",
          data: datos.map(d =>
            d.ordenes_asignadas > 0
              ? (d.ordenes_completadas / d.ordenes_asignadas) * 100
              : 0
          ),
          backgroundColor: "#8B5CF6",
        },
      ],
    },
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      scales: { x: { beginAtZero: true, max: 100 } },
      plugins: { legend: { display: false } },
    },
  });
}

function renderizarGraficoEstadosProduccion(datos) {
  const ctx = document
    .getElementById("graficoEstadosProduccion")
    .getContext("2d");
  if (graficoEstadosProduccion) graficoEstadosProduccion.destroy();

  graficoEstadosProduccion = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: datos.map(d => d.estado),
      datasets: [
        {
          data: datos.map(d => d.cantidad),
          backgroundColor: [
            "#3B82F6",
            "#F59E0B",
            "#10B981",
            "#EF4444",
            "#8B5CF6",
          ],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: "bottom" } },
    },
  });
}

function renderizarGraficoCumplimientoTareas(datos) {
  const ctx = document
    .getElementById("graficoCumplimientoTareas")
    .getContext("2d");
  if (graficoCumplimientoTareas) graficoCumplimientoTareas.destroy();

  const total = datos.total_tareas || 1;
  graficoCumplimientoTareas = new Chart(ctx, {
    type: "pie",
    data: {
      labels: ["Completadas", "En Progreso", "Pendientes"],
      datasets: [
        {
          data: [
            (datos.tareas_completadas / total) * 100,
            (datos.tareas_en_progreso / total) * 100,
            (datos.tareas_pendientes / total) * 100,
          ],
          backgroundColor: ["#10B981", "#F59E0B", "#EF4444"],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "bottom" },
        tooltip: {
          callbacks: {
            label: function (context) {
              return context.label + ": " + context.parsed.toFixed(1) + "%";
            },
          },
        },
      },
    },
  });
}

function renderizarGraficoTopClientes(datos) {
  const ctx = document.getElementById("graficoTopClientes").getContext("2d");
  if (graficoTopClientes) graficoTopClientes.destroy();

  graficoTopClientes = new Chart(ctx, {
    type: "bar",
    data: {
      labels: datos.map(d => d.cliente_nombre),
      datasets: [
        {
          label: "Total Comprado",
          data: datos.map(d => d.total_comprado),
          backgroundColor: "#10B981",
        },
      ],
    },
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      scales: { x: { beginAtZero: true } },
      plugins: { legend: { display: false } },
    },
  });
}

function renderizarGraficoTopProveedores(datos) {
  const ctx = document.getElementById("graficoTopProveedores").getContext("2d");
  if (graficoTopProveedores) graficoTopProveedores.destroy();

  graficoTopProveedores = new Chart(ctx, {
    type: "bar",
    data: {
      labels: datos.map(d => d.proveedor_nombre),
      datasets: [
        {
          label: "Total Comprado",
          data: datos.map(d => d.total_comprado),
          backgroundColor: "#3B82F6",
        },
      ],
    },
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      scales: { x: { beginAtZero: true } },
      plugins: { legend: { display: false } },
    },
  });
}

function renderizarAnalisisInventario(datos) {
  
  const ctxStock = document.getElementById("graficoStockCritico").getContext("2d");
  if (graficoStockCritico) graficoStockCritico.destroy();

  graficoStockCritico = new Chart(ctxStock, {
    type: "doughnut",
    data: {
      labels: ["Stock Crítico", "Stock Normal"],
      datasets: [
        {
          data: [datos.stock_critico, 100 - datos.stock_critico],
          backgroundColor: ["#EF4444", "#10B981"],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: "bottom" } },
    },
  });

  
  try {
    const valorCategoria =
      typeof datos.valor_por_categoria === "string"
        ? JSON.parse(datos.valor_por_categoria || '{"categorias":[]}')
        : datos.valor_por_categoria || { categorias: [] };

    const ctxValor = document
      .getElementById("graficoValorCategoria")
      .getContext("2d");
    if (graficoValorCategoria) graficoValorCategoria.destroy();

    graficoValorCategoria = new Chart(ctxValor, {
      type: "pie",
      data: {
        labels: valorCategoria.categorias.map(c => c.nombre),
        datasets: [
          {
            data: valorCategoria.categorias.map(c => c.valor),
            backgroundColor: [
              "#3B82F6",
              "#10B981",
              "#F59E0B",
              "#8B5CF6",
              "#EF4444",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } },
      },
    });
  } catch (e) {
    console.error("Error renderizando valor por categoría:", e);
  }

  
  try {
    const productosMasVendidos =
      typeof datos.productos_mas_vendidos === "string"
        ? JSON.parse(datos.productos_mas_vendidos || '{"productos":[]}')
        : datos.productos_mas_vendidos || { productos: [] };

    const ctxProductos = document
      .getElementById("graficoProductosMasVendidos")
      .getContext("2d");
    if (graficoProductosMasVendidos) graficoProductosMasVendidos.destroy();

    graficoProductosMasVendidos = new Chart(ctxProductos, {
      type: "bar",
      data: {
        labels: productosMasVendidos.productos.map(p => p.nombre),
        datasets: [
          {
            label: "Cantidad Vendida",
            data: productosMasVendidos.productos.map(p => p.cantidad),
            backgroundColor: "#8B5CF6",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } },
      },
    });
  } catch (e) {
    console.error("Error renderizando productos más vendidos:", e);
  }
}

function renderizarTablaKPIs(datos) {
  const tbody = document.getElementById("tablaKPIs");
  tbody.innerHTML = "";

  datos.forEach(kpi => {
    const tendencia =
      kpi.hoy > kpi.ayer
        ? "📈 Creciendo"
        : kpi.hoy < kpi.ayer
        ? "📉 Declinando"
        : "➡️ Estable";
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td class="p-3 font-medium">${kpi.metrica}</td>
      <td class="p-3 text-right">${parseFloat(kpi.hoy).toLocaleString()}</td>
      <td class="p-3 text-right">${parseFloat(kpi.ayer).toLocaleString()}</td>
      <td class="p-3 text-right">${parseFloat(
        kpi.esta_semana
      ).toLocaleString()}</td>
      <td class="p-3 text-right">${parseFloat(
        kpi.mes_pasado
      ).toLocaleString()}</td>
      <td class="p-3 text-center">${tendencia}</td>
    `;
    tbody.appendChild(tr);
  });
}



function descargarIngresosPDF() {
  if (
    !validarRangoFechas(
      "fecha_desde_ingresos",
      "fecha_hasta_ingresos",
      "error-ingresos"
    )
  ) {
    return;
  }
  const params = new URLSearchParams({
    fecha_desde: document.getElementById("fecha_desde_ingresos").value,
    fecha_hasta: document.getElementById("fecha_hasta_ingresos").value,
    idtipo_pago: document.getElementById("filtro_tipo_pago_ingresos").value,
  });
  window.open(`dashboard/descargarIngresosPDF?${params.toString()}`, "_blank");
}

function descargarEgresosPDF() {
  if (
    !validarRangoFechas(
      "fecha_desde_egresos",
      "fecha_hasta_egresos",
      "error-egresos"
    )
  ) {
    return;
  }
  const params = new URLSearchParams({
    fecha_desde: document.getElementById("fecha_desde_egresos").value,
    fecha_hasta: document.getElementById("fecha_hasta_egresos").value,
    idtipo_pago: document.getElementById("filtro_tipo_pago_egresos").value,
    tipo_egreso: document.getElementById("filtro_tipo_egreso").value,
  });
  window.open(`dashboard/descargarEgresosPDF?${params.toString()}`, "_blank");
}

function descargarReporteCompras() {
  if (
    !validarRangoFechas(
      "fecha_desde_compras",
      "fecha_hasta_compras",
      "error-compras"
    )
  ) {
    return;
  }
  const params = new URLSearchParams({
    fecha_desde: document.getElementById("fecha_desde_compras").value,
    fecha_hasta: document.getElementById("fecha_hasta_compras").value,
    idproveedor: document.getElementById("filtro_proveedor_compras").value,
    idproducto: document.getElementById("filtro_producto_compras").value,
  });
  window.open(
    `dashboard/descargarReporteComprasPDF?${params.toString()}`,
    "_blank"
  );
}


function descargarReporteEjecutivo() {
  window.open(`dashboard/descargarReporteEjecutivoPDF`, "_blank");
}