// Variables globales para las instancias de los gráficos
let graficoVentasMensuales;
let graficoIngresos;
let graficoEgresos;

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

document.addEventListener("DOMContentLoaded", function () {
  const hoy = new Date();
  const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
    .toISOString()
    .split("T")[0];
  const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0)
    .toISOString()
    .split("T")[0];

  document.getElementById("fecha_desde_ingresos").value = primerDiaMes;
  document.getElementById("fecha_hasta_ingresos").value = ultimoDiaMes;
  document.getElementById("fecha_desde_egresos").value = primerDiaMes;
  document.getElementById("fecha_hasta_egresos").value = ultimoDiaMes;

  cargarDatosDashboard();

  // Event listeners para todos los filtros
  document
    .getElementById("fecha_desde_ingresos")
    .addEventListener("change", cargarDatosDashboard);
  document
    .getElementById("fecha_hasta_ingresos")
    .addEventListener("change", cargarDatosDashboard);
  document
    .getElementById("filtro_tipo_pago")
    .addEventListener("change", cargarDatosDashboard);
  document
    .getElementById("fecha_desde_egresos")
    .addEventListener("change", cargarDatosDashboard);
  document
    .getElementById("fecha_hasta_egresos")
    .addEventListener("change", cargarDatosDashboard);

  document
    .getElementById("btnDescargarIngresos")
    .addEventListener("click", function () {
      const esValido = validarRangoFechas(
        "fecha_desde_ingresos",
        "fecha_hasta_ingresos",
        "error-ingresos"
      );

      if (esValido) {
        const fecha_desde = document.getElementById("fecha_desde_ingresos").value;
        const fecha_hasta = document.getElementById("fecha_hasta_ingresos").value;
        const idtipo_pago = document.getElementById("filtro_tipo_pago").value;
        const url = `dashboard/descargarIngresosPDF?fecha_desde=${fecha_desde}&fecha_hasta=${fecha_hasta}&idtipo_pago=${idtipo_pago}`;
        window.open(url, "_blank");
      }
    });
});

function cargarDatosDashboard() {
  const esRangoIngresosValido = validarRangoFechas(
    "fecha_desde_ingresos",
    "fecha_hasta_ingresos",
    "error-ingresos"
  );
  const esRangoEgresosValido = validarRangoFechas(
    "fecha_desde_egresos",
    "fecha_hasta_egresos",
    "error-egresos"
  );

  if (!esRangoIngresosValido || !esRangoEgresosValido) {
    return;
  }

  // Recoger todos los filtros
  const fecha_desde_ingresos = document.getElementById(
    "fecha_desde_ingresos"
  ).value;
  const fecha_hasta_ingresos = document.getElementById(
    "fecha_hasta_ingresos"
  ).value;
  const idtipo_pago = document.getElementById("filtro_tipo_pago").value;
  const fecha_desde_egresos = document.getElementById(
    "fecha_desde_egresos"
  ).value;
  const fecha_hasta_egresos = document.getElementById(
    "fecha_hasta_egresos"
  ).value;

  const url = `dashboard/getDashboardData?fecha_desde_ingresos=${fecha_desde_ingresos}&fecha_hasta_ingresos=${fecha_hasta_ingresos}&idtipo_pago=${idtipo_pago}&fecha_desde_egresos=${fecha_desde_egresos}&fecha_hasta_egresos=${fecha_hasta_egresos}`;

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor.");
      }
      return response.json();
    })
    .then((data) => {
      actualizarResumen(data.resumen);
      llenarTablaVentas(data.ventas);
      renderizarGraficoVentasMensuales(data.ventasMensuales);
      renderizarGraficoIngresos(data.reporteIngresos);
      renderizarGraficoEgresos(data.reporteEgresos);
    })
    .catch((error) =>
      console.error("Error al cargar los datos del dashboard:", error)
    );
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