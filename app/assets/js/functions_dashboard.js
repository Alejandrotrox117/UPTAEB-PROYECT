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

let datosGraficos = {
  ingresos: null,
  egresos: null,
  tendenciasVentas: null,
  rentabilidadProductos: null,
  eficienciaEmpleados: null,
  estadosProduccion: null,
  cumplimientoTareas: null,
  topClientes: null,
  topProveedores: null,
  analisisInventario: null
};






document.addEventListener("DOMContentLoaded", inicializarDashboard);



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

  addSafeListener("tipoGraficoIngresos", "change", cambiarTipoGraficoIngresos);
  addSafeListener("tipoGraficoEgresos", "change", cambiarTipoGraficoEgresos);
  addSafeListener("tipoGraficoTendenciasVentas", "change", cambiarTipoGraficoTendenciasVentas);
  addSafeListener("tipoGraficoRentabilidadProductos", "change", cambiarTipoGraficoRentabilidadProductos);
  addSafeListener("tipoGraficoEficienciaEmpleados", "change", cambiarTipoGraficoEficienciaEmpleados);
  addSafeListener("tipoGraficoEstadosProduccion", "change", cambiarTipoGraficoEstadosProduccion);
  addSafeListener("tipoGraficoTopClientes", "change", cambiarTipoGraficoTopClientes);
  addSafeListener("tipoGraficoTopProveedores", "change", cambiarTipoGraficoTopProveedores);
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

      datosGraficos.ingresos = data.reporteIngresos;
      datosGraficos.egresos = data.reporteEgresos;

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

      datosGraficos.tendenciasVentas = data.tendenciasVentas;
      datosGraficos.rentabilidadProductos = data.rentabilidadProductos;
      datosGraficos.eficienciaEmpleados = data.eficienciaEmpleados;
      datosGraficos.estadosProduccion = data.estadosProduccion;
      datosGraficos.cumplimientoTareas = data.cumplimientoTareas;
      datosGraficos.topClientes = data.topClientes;
      datosGraficos.topProveedores = data.topProveedores;
      datosGraficos.analisisInventario = data.analisisInventario;

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
  const selector = document.getElementById("tipoGraficoIngresos");
  const tipoSeleccionado = selector ? selector.value || 'pie' : 'pie';
  renderizarGraficoIngresosConTipo(datos, tipoSeleccionado);
}

function renderizarGraficoEgresos(datos) {
  const selector = document.getElementById("tipoGraficoEgresos");
  const tipoSeleccionado = selector ? selector.value || 'pie' : 'pie';
  renderizarGraficoEgresosConTipo(datos, tipoSeleccionado);
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
  if (!resumen || typeof resumen !== 'object') {
    console.error("Datos de resumen no válidos recibidos.");
    return;
  }

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


  document.getElementById("ventasHoyComparacion").className = `text-xs ${ventasComparacion >= 0 ? "text-green-600" : "text-red-600"
    }`;
  document.getElementById("comprasHoyComparacion").className = `text-xs ${comprasComparacion >= 0 ? "text-green-600" : "text-red-600"
    }`;
}



function actualizarKPIsEjecutivos(kpis) {
  console.log("Actualizando KPIs Ejecutivos:", kpis);
  if (!kpis || typeof kpis !== 'object') {
    console.error("Datos de KPIs no válidos recibidos.");
    document.getElementById("margenGanancia").textContent = "0.0%";
    document.getElementById("roiMes").textContent = "0.0%";
    document.getElementById("rotacionInventario").textContent = "0 días";
    document.getElementById("productividadGeneral").textContent = "0.0 kg/día";
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
  const selector = document.getElementById("tipoGraficoTendenciasVentas");
  const tipoSeleccionado = selector ? selector.value || 'line' : 'line';
  renderizarGraficoTendenciasVentasConTipo(datos, tipoSeleccionado);
}

function renderizarGraficoRentabilidadProductos(datos) {
  const selector = document.getElementById("tipoGraficoRentabilidadProductos");
  const tipoSeleccionado = selector ? selector.value || 'bar' : 'bar';
  renderizarGraficoRentabilidadProductosConTipo(datos, tipoSeleccionado);
}

function renderizarGraficoEficienciaEmpleados(datos) {
  const selector = document.getElementById("tipoGraficoEficienciaEmpleados");
  const tipoSeleccionado = selector ? selector.value || 'horizontalBar' : 'horizontalBar';
  renderizarGraficoEficienciaEmpleadosConTipo(datos, tipoSeleccionado);
}

function renderizarGraficoEstadosProduccion(datos) {
  const selector = document.getElementById("tipoGraficoEstadosProduccion");
  const tipoSeleccionado = selector ? selector.value || 'doughnut' : 'doughnut';
  renderizarGraficoEstadosProduccionConTipo(datos, tipoSeleccionado);
}

function renderizarGraficoCumplimientoTareas(datos) {
  if (!datos || typeof datos !== 'object' || datos.total_tareas === undefined) {
    console.warn("No hay datos de cumplimiento de tareas disponibles");
    return;
  }

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
  const selector = document.getElementById("tipoGraficoTopClientes");
  const tipoSeleccionado = selector ? selector.value || 'horizontalBar' : 'horizontalBar';
  renderizarGraficoTopClientesConTipo(datos, tipoSeleccionado);
}

function renderizarGraficoTopProveedores(datos) {
  const selector = document.getElementById("tipoGraficoTopProveedores");
  const tipoSeleccionado = selector ? selector.value || 'horizontalBar' : 'horizontalBar';
  renderizarGraficoTopProveedoresConTipo(datos, tipoSeleccionado);
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

    if (!valorCategoria.categorias || !Array.isArray(valorCategoria.categorias) || valorCategoria.categorias.length === 0) {
      console.warn("No hay datos de categorías disponibles para el gráfico");
      return;
    }

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

    if (!productosMasVendidos.productos || !Array.isArray(productosMasVendidos.productos) || productosMasVendidos.productos.length === 0) {
      console.warn("No hay datos de productos más vendidos disponibles para el gráfico");
      return;
    }

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
  if (!tbody) {
    console.warn("Elemento tablaKPIs no encontrado");
    return;
  }

  tbody.innerHTML = "";

  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de KPIs en tiempo real disponibles");
    tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-500">No hay datos disponibles</td></tr>';
    return;
  }

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
document.addEventListener('DOMContentLoaded', function () {
  const selector = document.getElementById('selectorReporte');
  const secciones = document.querySelectorAll('.report-section');

  secciones.forEach(sec => sec.style.display = 'none');

  selector.addEventListener('change', function () {
    const valorSeleccionado = this.value;

    secciones.forEach(sec => {
      if (valorSeleccionado === '') {
        sec.style.display = 'none';
      } else if (sec.id === valorSeleccionado) {
        sec.style.display = 'block';
      } else {
        sec.style.display = 'none';
      }
    });
  });
});

/**
 * Función genérica para renderizar gráficos con diferentes tipos
 */
function renderizarGraficoGenerico(canvasId, datos, tipoGrafico, opciones = {}) {
  const ctx = document.getElementById(canvasId).getContext("2d");

  const graficoExistente = Chart.getChart(ctx);
  if (graficoExistente) {
    graficoExistente.destroy();
  }

  const configuracionBase = {
    type: tipoGrafico,
    data: datos,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "top" }
      },
      ...opciones
    }
  };

  return new Chart(ctx, configuracionBase);
}

/**
 * Obtiene los tipos de gráfico disponibles según el tipo de datos
 */
function obtenerTiposGraficoDisponibles(tipoDatos) {
  const tipos = {
    categorico: ['pie', 'doughnut', 'bar', 'horizontalBar'],
    temporal: ['line', 'bar', 'area'],
    comparativo: ['bar', 'horizontalBar', 'radar', 'polarArea'],
    porcentaje: ['pie', 'doughnut', 'bar']
  };

  return tipos[tipoDatos] || ['bar', 'pie', 'line'];
}

/**
 * Funciones para cambiar el tipo de gráfico
 */
function cambiarTipoGraficoIngresos() {
  const selector = document.getElementById("tipoGraficoIngresos");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.ingresos && tipoSeleccionado) {
    renderizarGraficoIngresosConTipo(datosGraficos.ingresos, tipoSeleccionado);
  }
}

function cambiarTipoGraficoEgresos() {
  const selector = document.getElementById("tipoGraficoEgresos");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.egresos && tipoSeleccionado) {
    renderizarGraficoEgresosConTipo(datosGraficos.egresos, tipoSeleccionado);
  }
}

function cambiarTipoGraficoTendenciasVentas() {
  const selector = document.getElementById("tipoGraficoTendenciasVentas");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.tendenciasVentas && tipoSeleccionado) {
    renderizarGraficoTendenciasVentasConTipo(datosGraficos.tendenciasVentas, tipoSeleccionado);
  }
}

function cambiarTipoGraficoRentabilidadProductos() {
  const selector = document.getElementById("tipoGraficoRentabilidadProductos");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.rentabilidadProductos && tipoSeleccionado) {
    renderizarGraficoRentabilidadProductosConTipo(datosGraficos.rentabilidadProductos, tipoSeleccionado);
  }
}

function cambiarTipoGraficoEficienciaEmpleados() {
  const selector = document.getElementById("tipoGraficoEficienciaEmpleados");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.eficienciaEmpleados && tipoSeleccionado) {
    renderizarGraficoEficienciaEmpleadosConTipo(datosGraficos.eficienciaEmpleados, tipoSeleccionado);
  }
}

function cambiarTipoGraficoEstadosProduccion() {
  const selector = document.getElementById("tipoGraficoEstadosProduccion");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.estadosProduccion && tipoSeleccionado) {
    renderizarGraficoEstadosProduccionConTipo(datosGraficos.estadosProduccion, tipoSeleccionado);
  }
}

function cambiarTipoGraficoTopClientes() {
  const selector = document.getElementById("tipoGraficoTopClientes");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.topClientes && tipoSeleccionado) {
    renderizarGraficoTopClientesConTipo(datosGraficos.topClientes, tipoSeleccionado);
  }
}

function cambiarTipoGraficoTopProveedores() {
  const selector = document.getElementById("tipoGraficoTopProveedores");
  const tipoSeleccionado = selector.value;

  if (datosGraficos.topProveedores && tipoSeleccionado) {
    renderizarGraficoTopProveedoresConTipo(datosGraficos.topProveedores, tipoSeleccionado);
  }
}

/**
 * Funciones para renderizar gráficos con tipos específicos
 */
function renderizarGraficoIngresosConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de ingresos disponibles");
    document.getElementById("totalIngresos").textContent = "0.00";
    return;
  }

  const labels = datos.map(d => d.categoria);
  const valores = datos.map(d => d.total);
  const totalIngresos = valores.reduce((sum, val) => sum + parseFloat(val), 0);

  document.getElementById("totalIngresos").textContent =
    totalIngresos.toLocaleString("es-VE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const datosGrafico = {
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
        borderColor: [
          "#059669",
          "#2563EB",
          "#D97706",
          "#7C3AED",
          "#DC2626",
        ],
        borderWidth: 1
      },
    ],
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } }
  } : {};

  graficoIngresos = renderizarGraficoGenerico("graficoIngresos", datosGrafico, tipoGrafico, opciones);
}

function renderizarGraficoEgresosConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de egresos disponibles");
    document.getElementById("totalEgresos").textContent = "0.00";
    return;
  }

  const labels = datos.map(d => d.categoria);
  const valores = datos.map(d => d.total);
  const totalEgresos = valores.reduce((sum, val) => sum + parseFloat(val), 0);

  document.getElementById("totalEgresos").textContent =
    totalEgresos.toLocaleString("es-VE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const datosGrafico = {
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
        borderColor: [
          "#DC2626",
          "#D97706",
          "#4B5563",
          "#2563EB",
          "#059669",
        ],
        borderWidth: 1
      },
    ],
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } }
  } : {};

  graficoEgresos = renderizarGraficoGenerico("graficoEgresos", datosGrafico, tipoGrafico, opciones);
}

function renderizarGraficoTendenciasVentasConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de tendencias de ventas disponibles");
    return;
  }

  let datosGrafico;
  let opciones = {};

  if (tipoGrafico === 'line' || tipoGrafico === 'area') {
    datosGrafico = {
      labels: datos.map(d => d.periodo),
      datasets: [
        {
          label: "Ventas Totales",
          data: datos.map(d => d.total_ventas),
          borderColor: "#3B82F6",
          backgroundColor: tipoGrafico === 'area' ? "rgba(59, 130, 246, 0.3)" : "rgba(59, 130, 246, 0.1)",
          tension: 0.4,
          fill: tipoGrafico === 'area',
        },
        {
          label: "Número de Ventas",
          data: datos.map(d => d.num_ventas),
          borderColor: "#10B981",
          backgroundColor: tipoGrafico === 'area' ? "rgba(16, 185, 129, 0.3)" : "rgba(16, 185, 129, 0.1)",
          tension: 0.4,
          yAxisID: "y1",
          fill: tipoGrafico === 'area',
        },
      ],
    };
    opciones = {
      scales: {
        y: { beginAtZero: true, position: "left" },
        y1: { type: "linear", display: true, position: "right", beginAtZero: true },
      }
    };
  } else {
    datosGrafico = {
      labels: datos.map(d => d.periodo),
      datasets: [
        {
          label: "Ventas Totales",
          data: datos.map(d => d.total_ventas),
          backgroundColor: "#3B82F6",
        }
      ],
    };
    opciones = { scales: { y: { beginAtZero: true } } };
  }

  const tipoChart = tipoGrafico === 'area' ? 'line' : tipoGrafico;
  graficoTendenciasVentas = renderizarGraficoGenerico("graficoTendenciasVentas", datosGrafico, tipoChart, opciones);
}

function renderizarGraficoRentabilidadProductosConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de rentabilidad de productos disponibles");
    return;
  }

  const datosGrafico = {
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
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } }
  } : { scales: { y: { beginAtZero: true } } };

  graficoRentabilidadProductos = renderizarGraficoGenerico("graficoRentabilidadProductos", datosGrafico, tipoGrafico, opciones);
}

function renderizarGraficoEficienciaEmpleadosConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de eficiencia de empleados disponibles");
    return;
  }

  const datosGrafico = {
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
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true, max: 100 } }
  } : { scales: { y: { beginAtZero: true, max: 100 } } };

  graficoEficienciaEmpleados = renderizarGraficoGenerico("graficoEficienciaEmpleados", datosGrafico, tipoGrafico, opciones);
}

function renderizarGraficoEstadosProduccionConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de estados de producción disponibles");
    return;
  }

  const datosGrafico = {
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
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } }
  } : {};

  graficoEstadosProduccion = renderizarGraficoGenerico("graficoEstadosProduccion", datosGrafico, tipoGrafico, opciones);
}

function renderizarGraficoTopClientesConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de top clientes disponibles");
    return;
  }

  const datosGrafico = {
    labels: datos.map(d => d.cliente_nombre),
    datasets: [
      {
        label: "Total Comprado",
        data: datos.map(d => d.total_comprado),
        backgroundColor: "#10B981",
      },
    ],
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } }
  } : { scales: { y: { beginAtZero: true } } };

  graficoTopClientes = renderizarGraficoGenerico("graficoTopClientes", datosGrafico, tipoGrafico, opciones);
}

function renderizarGraficoTopProveedoresConTipo(datos, tipoGrafico) {
  if (!datos || !Array.isArray(datos) || datos.length === 0) {
    console.warn("No hay datos de top proveedores disponibles");
    return;
  }

  const datosGrafico = {
    labels: datos.map(d => d.proveedor_nombre),
    datasets: [
      {
        label: "Total Comprado",
        data: datos.map(d => d.total_comprado),
        backgroundColor: "#3B82F6",
      },
    ],
  };

  const opciones = tipoGrafico === 'horizontalBar' ? {
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } }
  } : { scales: { y: { beginAtZero: true } } };

  graficoTopProveedores = renderizarGraficoGenerico("graficoTopProveedores", datosGrafico, tipoGrafico, opciones);
}

