// Variables globales para las instancias de los gráficos
let graficoVentasMensuales;
let graficoIngresos;
let graficoEgresos;

// --- FUNCIONES DE VALIDACIÓN Y RENDERIZADO ---

function validarRangoFechas(idDesde, idHasta, idErrorContainer) {
  const fechaDesdeInput = document.getElementById(idDesde);
  const fechaHastaInput = document.getElementById(idHasta);
  const errorContainer = document.getElementById(idErrorContainer);
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

  data.forEach((item) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
            <td class="px-2 py-2 whitespace-nowrap">${item.fecha}</td>
            <td class="px-2 py-2 whitespace-nowrap">${item.nro_compra}</td>
            <td class="px-2 py-2">${item.proveedor}</td>
            <td class="px-2 py-2">${item.producto}</td>
            <td class="px-2 py-2 text-right">${parseFloat(item.cantidad).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</td>
            <td class="px-2 py-2 text-right">${parseFloat(item.precio_unitario_compra).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</td>
            <td class="px-2 py-2 text-right">${parseFloat(item.subtotal_linea).toLocaleString("es-VE", { minimumFractionDigits: 2 })}</td>
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
  document.getElementById("ventasHoy").textContent = resumen.ventas_totales;
  document.getElementById("comprasHoy").textContent = resumen.compras_totales;
  document.getElementById("inventarioTotal").textContent =
    resumen.total_inventario;
  document.getElementById("empleadosActivos").textContent =
    resumen.empleados_activos;
}

function llenarTablaVentas(ventas) {
  const ventasBody = document.getElementById("ventasBody");
  ventasBody.innerHTML = "";
  if (ventas.length === 0) {
    ventasBody.innerHTML =
      '<tr><td colspan="4" class="text-center p-4">No hay ventas recientes.</td></tr>';
    return;
  }
  ventas.forEach((v) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
            <td class="px-4 py-2">${v.nro_venta}</td>
            <td class="px-4 py-2">${v.cliente}</td>
            <td class="px-4 py-2">${v.fecha_venta}</td>
            <td class="px-4 py-2">${v.total_general}</td>
        `;
    ventasBody.appendChild(tr);
  });
}

function renderizarGraficoVentasMensuales(datos) {
  const labels = datos.map((d) => d.mes);
  const valores = datos.map((d) => d.ventas_totales);
  const ctx = document.getElementById("graficoVentas").getContext("2d");
  if (graficoVentasMensuales) {
    graficoVentasMensuales.destroy();
  }
  graficoVentasMensuales = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Ventas Mensuales",
          data: valores,
          borderColor: "#4F46E5",
          backgroundColor: "rgba(79, 70, 229, 0.2)",
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true } },
    },
  });
}

function renderizarGraficoIngresos(datos) {
  const labels = datos.map((d) => d.categoria);
  const valores = datos.map((d) => d.total);
  const totalIngresos = valores.reduce(
    (sum, val) => sum + parseFloat(val),
    0
  );
  document.getElementById("totalIngresos").textContent =
    totalIngresos.toLocaleString("es-VE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  const ctx = document.getElementById("graficoIngresos").getContext("2d");
  if (graficoIngresos) {
    graficoIngresos.destroy();
  }
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
  const labels = datos.map((d) => d.categoria);
  const valores = datos.map((d) => d.total);
  const totalEgresos = valores.reduce(
    (sum, val) => sum + parseFloat(val),
    0
  );
  document.getElementById("totalEgresos").textContent =
    totalEgresos.toLocaleString("es-VE", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  const ctx = document.getElementById("graficoEgresos").getContext("2d");
  if (graficoEgresos) {
    graficoEgresos.destroy();
  }
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

// --- FUNCIONES DE CARGA DE DATOS ---

function cargarDatosDashboard() {
  if (
    !validarRangoFechas("fecha_desde_ingresos", "fecha_hasta_ingresos", "error-ingresos") ||
    !validarRangoFechas("fecha_desde_egresos", "fecha_hasta_egresos", "error-egresos")
  ) {
    return;
  }

  const params = new URLSearchParams({
    fecha_desde_ingresos: document.getElementById("fecha_desde_ingresos").value,
    fecha_hasta_ingresos: document.getElementById("fecha_hasta_ingresos").value,
    idtipo_pago_ingresos: document.getElementById("filtro_tipo_pago_ingresos").value,
    fecha_desde_egresos: document.getElementById("fecha_desde_egresos").value,
    fecha_hasta_egresos: document.getElementById("fecha_hasta_egresos").value,
    idtipo_pago_egresos: document.getElementById("filtro_tipo_pago_egresos").value,
    tipo_egreso: document.getElementById("filtro_tipo_egreso").value,
  });

  fetch(`dashboard/getDashboardData?${params.toString()}`)
    .then((response) => response.json())
    .then((data) => {
      actualizarResumen(data.resumen);
      llenarTablaVentas(data.ventas);
      renderizarGraficoVentasMensuales(data.ventasMensuales);
      renderizarGraficoIngresos(data.reporteIngresos);
      renderizarGraficoEgresos(data.reporteEgresos);
    })
    .catch((error) => console.error("Error al cargar datos del dashboard:", error));
}

function cargarReporteCompras() {
  if (!validarRangoFechas("fecha_desde_compras", "fecha_hasta_compras", "error-compras")) {
    return;
  }

  const params = new URLSearchParams({
    fecha_desde: document.getElementById("fecha_desde_compras").value,
    fecha_hasta: document.getElementById("fecha_hasta_compras").value,
    idproveedor: document.getElementById("filtro_proveedor_compras").value,
    idproducto: document.getElementById("filtro_producto_compras").value,
  });

  const tbody = document.getElementById("comprasReporteBody");
  tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center">Cargando...</td></tr>';

  fetch(`dashboard/getReporteComprasData?${params.toString()}`)
    .then((response) => {
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos para el reporte de compras:", data);
      renderizarTablaCompras(data);
    })
    .catch((error) => {
      console.error("Error al cargar reporte de compras:", error);
      tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-red-500">Error al cargar el reporte. Verifique la consola.</td></tr>';
    });
}

// --- FUNCIONES DE DESCARGA ---

function descargarIngresosPDF() {
  if (!validarRangoFechas("fecha_desde_ingresos", "fecha_hasta_ingresos", "error-ingresos")) {
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
  if (!validarRangoFechas("fecha_desde_egresos", "fecha_hasta_egresos", "error-egresos")) {
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
  if (!validarRangoFechas("fecha_desde_compras", "fecha_hasta_compras", "error-compras")) {
    return;
  }
  const params = new URLSearchParams({
    fecha_desde: document.getElementById("fecha_desde_compras").value,
    fecha_hasta: document.getElementById("fecha_hasta_compras").value,
    idproveedor: document.getElementById("filtro_proveedor_compras").value,
    idproducto: document.getElementById("filtro_producto_compras").value,
  });
  window.open(`dashboard/descargarReporteComprasPDF?${params.toString()}`, "_blank");
}

// --- INICIALIZACIÓN Y EVENT LISTENERS ---

document.addEventListener("DOMContentLoaded", function () {
  const hoy = new Date();
  const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split("T")[0];
  const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0).toISOString().split("T")[0];

  document.getElementById("fecha_desde_ingresos").value = primerDiaMes;
  document.getElementById("fecha_hasta_ingresos").value = ultimoDiaMes;
  document.getElementById("fecha_desde_egresos").value = primerDiaMes;
  document.getElementById("fecha_hasta_egresos").value = ultimoDiaMes;
  document.getElementById("fecha_desde_compras").value = primerDiaMes;
  document.getElementById("fecha_hasta_compras").value = ultimoDiaMes;

  cargarDatosDashboard();

  // Listeners para reportes financieros
  document.getElementById("fecha_desde_ingresos").addEventListener("change", cargarDatosDashboard);
  document.getElementById("fecha_hasta_ingresos").addEventListener("change", cargarDatosDashboard);
  document.getElementById("filtro_tipo_pago_ingresos").addEventListener("change", cargarDatosDashboard);
  document.getElementById("fecha_desde_egresos").addEventListener("change", cargarDatosDashboard);
  document.getElementById("fecha_hasta_egresos").addEventListener("change", cargarDatosDashboard);
  document.getElementById("filtro_tipo_pago_egresos").addEventListener("change", cargarDatosDashboard);
  document.getElementById("filtro_tipo_egreso").addEventListener("change", cargarDatosDashboard);

  // CORRECCIÓN: Listeners para los filtros del reporte de compras
  document.getElementById("fecha_desde_compras").addEventListener("change", cargarReporteCompras);
  document.getElementById("fecha_hasta_compras").addEventListener("change", cargarReporteCompras);
  document.getElementById("filtro_proveedor_compras").addEventListener("change", cargarReporteCompras);
  document.getElementById("filtro_producto_compras").addEventListener("change", cargarReporteCompras);

  // Listeners para botones
  document.getElementById("btnDescargarIngresos").addEventListener("click", descargarIngresosPDF);
  document.getElementById("btnDescargarEgresos").addEventListener("click", descargarEgresosPDF);
  document.getElementById("btnGenerarReporteCompras").addEventListener("click", cargarReporteCompras);
  document.getElementById("btnDescargarReporteCompras").addEventListener("click", descargarReporteCompras);
});