import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

// Variables globales
let tablaLotes, tablaProcesos, tablaNomina;
let operariosAsignados = [];
let configuracionActual = {};

// Configuración de campos de validación
const camposFormularioLote = [
  {
    id: "lote_fecha_jornada",
    tipo: "fecha",
    mensajes: {
      vacio: "La fecha de jornada es obligatoria.",
      fechaAnterior: "La fecha no puede ser anterior a hoy.",
    },
  },
  {
    id: "lote_volumen_estimado",
    tipo: "numero",
    mensajes: {
      vacio: "El volumen estimado es obligatorio.",
      formato: "Debe ser un número válido mayor a 0.",
    },
  },
  {
    id: "lote_supervisor",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un supervisor.",
    },
  },
];

const camposFormularioClasificacion = [
  {
    id: "clas_lote",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un lote.",
    },
  },
  {
    id: "clas_operario",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un operario.",
    },
  },
  {
    id: "clas_producto_origen",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar el producto origen.",
    },
  },
  {
    id: "clas_producto_clasificado",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar el producto clasificado.",
    },
  },
  {
    id: "clas_kg_procesados",
    tipo: "numero",
    mensajes: {
      vacio: "Los kg procesados son obligatorios.",
      formato: "Debe ser un número válido mayor a 0.",
    },
  },
  {
    id: "clas_kg_limpios",
    tipo: "numero",
    mensajes: {
      vacio: "Los kg limpios son obligatorios.",
      formato: "Debe ser un número válido mayor o igual a 0.",
    },
  },
  {
    id: "clas_kg_contaminantes",
    tipo: "numero",
    mensajes: {
      vacio: "Los kg contaminantes son obligatorios.",
      formato: "Debe ser un número válido mayor o igual a 0.",
    },
  },
];

const camposFormularioEmpaque = [
  {
    id: "emp_lote",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un lote.",
    },
  },
  {
    id: "emp_operario",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un operario.",
    },
  },
  {
    id: "emp_producto_clasificado",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar el material clasificado.",
    },
  },
  {
    id: "emp_peso_paca",
    tipo: "numero",
    mensajes: {
      vacio: "El peso de la paca es obligatorio.",
      formato: "Debe ser un número válido mayor a 0.",
    },
  },
  {
    id: "emp_calidad",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar la calidad.",
    },
  },
];

// ========================================
// FUNCIONES DE INICIALIZACIÓN
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  inicializarPestañas();
  inicializarTablas();
  inicializarEventos();
  cargarConfiguracionInicial();
  actualizarEstadisticas();
});

function inicializarPestañas() {
  const botonesPestaña = document.querySelectorAll(".tab-button");
  const contenidoPestañas = document.querySelectorAll(".tab-content");

  botonesPestaña.forEach((boton) => {
    boton.addEventListener("click", function () {
      const pestañaId = this.id.replace("tab-", "content-");

      // Remover clase activa de todos los botones y contenidos
      botonesPestaña.forEach((b) => {
        b.classList.remove("active", "border-green-500", "text-green-600");
        b.classList.add("border-transparent", "text-gray-500");
      });
      contenidoPestañas.forEach((c) => c.classList.add("hidden"));

      // Activar pestaña seleccionada
      this.classList.add("active", "border-green-500", "text-green-600");
      this.classList.remove("border-transparent", "text-gray-500");
      document.getElementById(pestañaId).classList.remove("hidden");

      // Recargar tabla específica si es necesario
      if (pestañaId === "content-lotes" && tablaLotes) {
        setTimeout(() => tablaLotes.columns.adjust().draw(), 100);
      } else if (pestañaId === "content-nomina" && tablaNomina) {
        setTimeout(() => tablaNomina.columns.adjust().draw(), 100);
      }
    });
  });
}

function inicializarTablas() {
  inicializarTablaLotes();
  inicializarTablaProcesos();
  inicializarTablaNomina();
}

function inicializarTablaLotes() {
  if ($.fn.DataTable.isDataTable("#TablaLotes")) {
    $("#TablaLotes").DataTable().destroy();
  }

  tablaLotes = $("#TablaLotes").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: "./Produccion/getLotesData",
      type: "GET",
      dataSrc: function (json) {
        if (json && Array.isArray(json.data)) {
          return json.data;
        } else {
          console.error("Respuesta del servidor no válida:", json);
          mostrarError("No se pudieron cargar los datos de lotes.");
          return [];
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Error AJAX:", textStatus, errorThrown);
        mostrarError("Error al cargar los lotes. Intente más tarde.");
      },
    },
    columns: [
      {
        data: "numero_lote",
        title: "Número Lote",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "fecha_jornada_formato",
        title: "Fecha",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "supervisor",
        title: "Supervisor",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "volumen_estimado",
        title: "Vol. Est. (kg)",
        className: "tablet-l text-right py-2 px-3 text-gray-700",
        render: function (data) {
          return parseFloat(data || 0).toLocaleString("es-ES", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        },
      },
      {
        data: "operarios_requeridos",
        title: "Op. Req.",
        className: "tablet-l text-center py-2 px-3 text-gray-700",
      },
      {
        data: "operarios_asignados",
        title: "Op. Asig.",
        className: "tablet-l text-center py-2 px-3 text-gray-700",
      },
      {
        data: "estatus_lote",
        title: "Estado",
        className: "min-tablet-p text-center py-2 px-3",
        render: function (data) {
          const colores = {
            PLANIFICADO: "bg-yellow-100 text-yellow-800",
            EN_PROCESO: "bg-blue-100 text-blue-800",
            FINALIZADO: "bg-green-100 text-green-800",
            CANCELADO: "bg-red-100 text-red-800",
          };
          const color = colores[data] || "bg-gray-100 text-gray-800";
          return `<span class="${color} text-xs font-semibold px-2.5 py-1 rounded-full">${data}</span>`;
        },
      },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        searchable: false,
        className: "all text-center py-1 px-2",
        render: function (data, type, row) {
          const idlote = row.idlote || "";
          const numeroLote = row.numero_lote || "";
          const estatus = row.estatus_lote || "";
          
          let acciones = `
            <div class="inline-flex items-center space-x-1">
              <button class="ver-lote-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" title="Ver detalles">
                <i class="fas fa-eye fa-fw text-base"></i>
              </button>`;

          if (estatus === "PLANIFICADO") {
            acciones += `
              <button class="asignar-operarios-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" title="Asignar operarios">
                <i class="fas fa-users fa-fw text-base"></i>
              </button>
              <button class="iniciar-lote-btn text-orange-600 hover:text-orange-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" title="Iniciar lote">
                <i class="fas fa-play fa-fw text-base"></i>
              </button>`;
          }

          if (estatus === "EN_PROCESO") {
            acciones += `
              <button class="cerrar-lote-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" data-numero="${numeroLote}" title="Cerrar lote">
                <i class="fas fa-stop fa-fw text-base"></i>
              </button>`;
          }

          acciones += `</div>`;
          return acciones;
        },
      },
    ],
    language: {
      processing: `
        <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]">
          <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
            <span class="text-lg font-medium text-gray-700">Cargando lotes...</span>
          </div>
        </div>`,
      emptyTable: `
        <div class="text-center py-4">
          <i class="fas fa-box-open fa-2x text-gray-400 mb-2"></i>
          <p class="text-gray-600">No hay lotes registrados.</p>
        </div>`,
      info: "Mostrando _START_ a _END_ de _TOTAL_ lotes",
      infoEmpty: "Mostrando 0 lotes",
      infoFiltered: "(filtrado de _MAX_ lotes totales)",
      lengthMenu: "Mostrar _MENU_ lotes",
      search: "_INPUT_",
      searchPlaceholder: "Buscar lote...",
      zeroRecords: `
        <div class="text-center py-4">
          <i class="fas fa-search fa-2x text-gray-400 mb-2"></i>
          <p class="text-gray-600">No se encontraron coincidencias.</p>
        </div>`,
      paginate: {
        first: '<i class="fas fa-angle-double-left"></i>',
        last: '<i class="fas fa-angle-double-right"></i>',
        next: '<i class="fas fa-angle-right"></i>',
        previous: '<i class="fas fa-angle-left"></i>',
      },
    },
    destroy: true,
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
    order: [[1, "desc"]],
    scrollX: true,
    drawCallback: function (settings) {
      $(settings.nTableWrapper)
        .find('.dataTables_filter input[type="search"]')
        .addClass(
          "py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white"
        )
        .removeClass("form-control-sm");
    },
  });

  // Event listeners para acciones de la tabla
  $("#TablaLotes tbody").on("click", ".ver-lote-btn", function () {
    const idlote = $(this).data("idlote");
    verDetallesLote(idlote);
  });

  $("#TablaLotes tbody").on("click", ".asignar-operarios-btn", function () {
    const idlote = $(this).data("idlote");
    abrirModalAsignarOperarios(idlote);
  });

  $("#TablaLotes tbody").on("click", ".iniciar-lote-btn", function () {
    const idlote = $(this).data("idlote");
    iniciarLote(idlote);
  });

  $("#TablaLotes tbody").on("click", ".cerrar-lote-btn", function () {
    const idlote = $(this).data("idlote");
    const numeroLote = $(this).data("numero");
    cerrarLote(idlote, numeroLote);
  });
}

function inicializarTablaProcesos() {
  // Implementar tabla de procesos recientes
  if ($.fn.DataTable.isDataTable("#TablaProcesos")) {
    $("#TablaProcesos").DataTable().destroy();
  }

  tablaProcesos = $("#TablaProcesos").DataTable({
    processing: true,
    serverSide: false,
    data: [], // Se cargará dinámicamente
    columns: [
      { data: "fecha", title: "Fecha", className: "all" },
      { data: "operario", title: "Operario", className: "all" },
      { data: "proceso", title: "Proceso", className: "desktop" },
      { data: "cantidad", title: "Cantidad", className: "tablet-l" },
      { data: "observaciones", title: "Observaciones", className: "desktop" },
    ],
    language: {
      emptyTable: "No hay procesos registrados recientemente.",
      info: "Mostrando _START_ a _END_ de _TOTAL_ procesos",
      infoEmpty: "Mostrando 0 procesos",
      lengthMenu: "Mostrar _MENU_ procesos",
      search: "_INPUT_",
      searchPlaceholder: "Buscar proceso...",
      zeroRecords: "No se encontraron coincidencias.",
    },
    destroy: true,
    responsive: true,
    pageLength: 5,
    lengthMenu: [[5, 10, 25], [5, 10, 25]],
    order: [[0, "desc"]],
  });
}

function inicializarTablaNomina() {
  // Implementar tabla de nómina
  if ($.fn.DataTable.isDataTable("#TablaNomina")) {
    $("#TablaNomina").DataTable().destroy();
  }

  tablaNomina = $("#TablaNomina").DataTable({
    processing: true,
    serverSide: false,
    data: [], // Se cargará dinámicamente
    columns: [
      { data: "fecha", title: "Fecha", className: "all" },
      { data: "operario", title: "Operario", className: "all" },
      { data: "kg_clasificados", title: "Kg Clasificados", className: "desktop" },
      { data: "pacas_armadas", title: "Pacas", className: "tablet-l" },
      { data: "salario_total", title: "Salario", className: "all" },
      { data: "estatus", title: "Estado", className: "desktop" },
    ],
    language: {
      emptyTable: "No hay registros de nómina.",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 registros",
      lengthMenu: "Mostrar _MENU_ registros",
      search: "_INPUT_",
      searchPlaceholder: "Buscar registro...",
      zeroRecords: "No se encontraron coincidencias.",
    },
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "desc"]],
  });
}

// ========================================
// FUNCIONES DE EVENTOS
// ========================================

function inicializarEventos() {
  // Eventos de modales
  inicializarEventosLotes();
  inicializarEventosAsignacion();
  inicializarEventosProcesos();
  inicializarEventosNomina();
  inicializarEventosConfiguracion();
  inicializarEventosReportes();
}

function inicializarEventosLotes() {
  // Modal registrar lote
  const btnAbrirModalLote = document.getElementById("btnAbrirModalRegistrarLote");
  const btnCerrarModalLote = document.getElementById("btnCerrarModalRegistrarLote");
  const btnCancelarModalLote = document.getElementById("btnCancelarModalRegistrarLote");
  const formLote = document.getElementById("formRegistrarLote");

  if (btnAbrirModalLote) {
    btnAbrirModalLote.addEventListener("click", function () {
      abrirModal("modalRegistrarLote");
      if (formLote) formLote.reset();
      cargarSupervisores();
      document.getElementById("lote_fecha_jornada").value = new Date().toISOString().split('T')[0];
      inicializarValidaciones(camposFormularioLote, "formRegistrarLote");
    });
  }

  if (btnCerrarModalLote) {
    btnCerrarModalLote.addEventListener("click", function () {
      cerrarModal("modalRegistrarLote");
    });
  }

  if (btnCancelarModalLote) {
    btnCancelarModalLote.addEventListener("click", function () {
      cerrarModal("modalRegistrarLote");
    });
  }

  if (formLote) {
    formLote.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarLote();
    });
  }

  // Calcular operarios requeridos en tiempo real
  const volumenInput = document.getElementById("lote_volumen_estimado");
  if (volumenInput) {
    volumenInput.addEventListener("input", function () {
      calcularOperariosRequeridos();
    });
  }
}

function inicializarEventosAsignacion() {
  const btnCerrarModalAsignar = document.getElementById("btnCerrarModalAsignarOperarios");
  const btnCancelarModalAsignar = document.getElementById("btnCancelarModalAsignarOperarios");
  const btnGuardarAsignaciones = document.getElementById("btnGuardarAsignaciones");

  if (btnCerrarModalAsignar) {
    btnCerrarModalAsignar.addEventListener("click", function () {
      cerrarModal("modalAsignarOperarios");
    });
  }

  if (btnCancelarModalAsignar) {
    btnCancelarModalAsignar.addEventListener("click", function () {
      cerrarModal("modalAsignarOperarios");
    });
  }

  if (btnGuardarAsignaciones) {
    btnGuardarAsignaciones.addEventListener("click", function () {
      guardarAsignacionesOperarios();
    });
  }
}

function inicializarEventosProcesos() {
  // Modal clasificación
  const btnAbrirModalClasificacion = document.getElementById("btnAbrirModalClasificacion");
  const btnCerrarModalClasificacion = document.getElementById("btnCerrarModalClasificacion");
  const btnCancelarModalClasificacion = document.getElementById("btnCancelarModalClasificacion");
  const formClasificacion = document.getElementById("formRegistrarClasificacion");

  if (btnAbrirModalClasificacion) {
    btnAbrirModalClasificacion.addEventListener("click", function () {
      abrirModal("modalRegistrarClasificacion");
      if (formClasificacion) formClasificacion.reset();
      cargarDatosParaClasificacion();
      inicializarValidaciones(camposFormularioClasificacion, "formRegistrarClasificacion");
    });
  }

  if (btnCerrarModalClasificacion) {
    btnCerrarModalClasificacion.addEventListener("click", function () {
      cerrarModal("modalRegistrarClasificacion");
    });
  }

  if (btnCancelarModalClasificacion) {
    btnCancelarModalClasificacion.addEventListener("click", function () {
      cerrarModal("modalRegistrarClasificacion");
    });
  }

  if (formClasificacion) {
    formClasificacion.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarClasificacion();
    });
  }

  // Modal empaque
  const btnAbrirModalEmpaque = document.getElementById("btnAbrirModalEmpaque");
  const btnCerrarModalEmpaque = document.getElementById("btnCerrarModalEmpaque");
  const btnCancelarModalEmpaque = document.getElementById("btnCancelarModalEmpaque");
  const formEmpaque = document.getElementById("formRegistrarEmpaque");

  if (btnAbrirModalEmpaque) {
    btnAbrirModalEmpaque.addEventListener("click", function () {
      abrirModal("modalRegistrarEmpaque");
      if (formEmpaque) formEmpaque.reset();
      cargarDatosParaEmpaque();
      inicializarValidaciones(camposFormularioEmpaque, "formRegistrarEmpaque");
    });
  }

  if (btnCerrarModalEmpaque) {
    btnCerrarModalEmpaque.addEventListener("click", function () {
      cerrarModal("modalRegistrarEmpaque");
    });
  }

  if (btnCancelarModalEmpaque) {
    btnCancelarModalEmpaque.addEventListener("click", function () {
      cerrarModal("modalRegistrarEmpaque");
    });
  }

  if (formEmpaque) {
    formEmpaque.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarEmpaque();
    });
  }

  // Validación en tiempo real para clasificación
  const kgProcesados = document.getElementById("clas_kg_procesados");
  const kgLimpios = document.getElementById("clas_kg_limpios");
  const kgContaminantes = document.getElementById("clas_kg_contaminantes");

  if (kgProcesados && kgLimpios && kgContaminantes) {
    [kgProcesados, kgLimpios, kgContaminantes].forEach(input => {
      input.addEventListener("input", validarSumaClasificacion);
    });
  }

  // Validación de peso para empaque
  const pesoPaca = document.getElementById("emp_peso_paca");
  if (pesoPaca) {
    pesoPaca.addEventListener("input", validarPesoPaca);
  }
}

function inicializarEventosNomina() {
  const btnCalcularNomina = document.getElementById("btnCalcularNomina");
  const btnRegistrarProduccionDiaria = document.getElementById("btnRegistrarProduccionDiaria");
  const btnCerrarModalProduccionDiaria = document.getElementById("btnCerrarModalProduccionDiaria");
  const btnCancelarModalProduccionDiaria = document.getElementById("btnCancelarModalProduccionDiaria");
  const btnGuardarProduccionDiaria = document.getElementById("btnGuardarProduccionDiaria");
  const btnCargarOperarios = document.getElementById("btnCargarOperarios");

  if (btnCalcularNomina) {
    btnCalcularNomina.addEventListener("click", function () {
      abrirModalCalcularNomina();
    });
  }

  if (btnRegistrarProduccionDiaria) {
    btnRegistrarProduccionDiaria.addEventListener("click", function () {
      abrirModal("modalRegistrarProduccionDiaria");
      cargarLotesParaProduccionDiaria();
    });
  }

  if (btnCerrarModalProduccionDiaria) {
    btnCerrarModalProduccionDiaria.addEventListener("click", function () {
      cerrarModal("modalRegistrarProduccionDiaria");
    });
  }

  if (btnCancelarModalProduccionDiaria) {
    btnCancelarModalProduccionDiaria.addEventListener("click", function () {
      cerrarModal("modalRegistrarProduccionDiaria");
    });
  }

  if (btnGuardarProduccionDiaria) {
    btnGuardarProduccionDiaria.addEventListener("click", function () {
      guardarProduccionDiaria();
    });
  }

  if (btnCargarOperarios) {
    btnCargarOperarios.addEventListener("click", function () {
      cargarOperariosProduccionDiaria();
    });
  }

  // Cambio de lote en producción diaria
  const selectLote = document.getElementById("selectLoteProduccionDiaria");
  if (selectLote) {
    selectLote.addEventListener("change", function () {
      cargarOperariosProduccionDiaria();
    });
  }
}

function inicializarEventosConfiguracion() {
  const formConfiguracion = document.getElementById("formConfiguracionProduccion");
  const btnCargarConfiguracion = document.getElementById("btnCargarConfiguracion");
  const btnGuardarConfiguracion = document.getElementById("btnGuardarConfiguracion");

  if (formConfiguracion) {
    formConfiguracion.addEventListener("submit", function (e) {
      e.preventDefault();
      guardarConfiguracion();
    });
  }

  if (btnCargarConfiguracion) {
    btnCargarConfiguracion.addEventListener("click", function () {
      cargarConfiguracionInicial();
    });
  }

  if (btnGuardarConfiguracion) {
    btnGuardarConfiguracion.addEventListener("click", function () {
      guardarConfiguracion();
    });
  }
}

function inicializarEventosReportes() {
  const btnGenerarReporte = document.getElementById("btnGenerarReporte");
  const btnExportarReporte = document.getElementById("btnExportarReporte");

  if (btnGenerarReporte) {
    btnGenerarReporte.addEventListener("click", function () {
      generarReporte();
    });
  }

  if (btnExportarReporte) {
    btnExportarReporte.addEventListener("click", function () {
      exportarReporte();
    });
  }

  // Inicializar fechas por defecto
  const fechaInicio = document.getElementById("filtroFechaInicio");
  const fechaFin = document.getElementById("filtroFechaFin");

  if (fechaInicio && fechaFin) {
    const hoy = new Date();
    const primerDiaDelMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    
    fechaInicio.value = primerDiaDelMes.toISOString().split('T')[0];
    fechaFin.value = hoy.toISOString().split('T')[0];
  }
}

// ========================================
// FUNCIONES DE LOTES
// ========================================

function calcularOperariosRequeridos() {
  const volumen = parseFloat(document.getElementById("lote_volumen_estimado").value || 0);
  const infoCalculada = document.getElementById("infoCalculada");
  
  if (volumen > 0 && configuracionActual.productividad_clasificacion) {
    const operariosRequeridos = Math.ceil(volumen / configuracionActual.productividad_clasificacion);
    const capacidadMaxima = configuracionActual.capacidad_maxima_planta || 50;
    
    document.getElementById("operariosCalculados").textContent = operariosRequeridos;
    document.getElementById("capacidadMaxima").textContent = capacidadMaxima;
    
    infoCalculada.classList.remove("hidden");
    
    // Mostrar alerta si excede capacidad
    if (operariosRequeridos > capacidadMaxima) {
      infoCalculada.classList.add("bg-red-50", "border-red-200");
      infoCalculada.classList.remove("bg-blue-50", "border-blue-200");
      document.getElementById("operariosCalculados").classList.add("text-red-600");
    } else {
      infoCalculada.classList.add("bg-blue-50", "border-blue-200");
      infoCalculada.classList.remove("bg-red-50", "border-red-200");
      document.getElementById("operariosCalculados").classList.remove("text-red-600");
    }
  } else {
    infoCalculada.classList.add("hidden");
  }
}

function registrarLote() {
  const btnGuardarLote = document.getElementById("btnGuardarLote");

  if (btnGuardarLote) {
    btnGuardarLote.disabled = true;
    btnGuardarLote.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Creando...`;
  }

  registrarEntidad({
    formId: "formRegistrarLote",
    endpoint: "Produccion/createLote",
    campos: camposFormularioLote,
    mapeoNombres: {
      "lote_fecha_jornada": "fecha_jornada",
      "lote_volumen_estimado": "volumen_estimado",
      "lote_supervisor": "idsupervisor",
      "lote_observaciones": "observaciones",
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarLote");
        recargarTablaLotes();
        
        const formLote = document.getElementById("formRegistrarLote");
        if (formLote) {
          formLote.reset();
          limpiarValidaciones(camposFormularioLote, "formRegistrarLote");
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo crear el lote.",
        "error"
      );
    },
  }).finally(() => {
    if (btnGuardarLote) {
      btnGuardarLote.disabled = false;
      btnGuardarLote.innerHTML = `<i class="fas fa-save mr-2"></i> Crear Lote`;
    }
  });
}
function verDetallesLote(idlote) {
  fetch(`Produccion/getLoteById/${idlote}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        mostrarDetallesLote(result.data);
      } else {
        mostrarError("No se pudieron cargar los datos del lote.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error de conexión.");
    });
}

function mostrarDetallesLote(lote) {
  const html = `
    <div class="bg-white p-6 rounded-lg">
      <h3 class="text-lg font-semibold mb-4">Detalles del Lote ${lote.numero_lote}</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><strong>Fecha Jornada:</strong> ${lote.fecha_jornada_formato}</div>
        <div><strong>Supervisor:</strong> ${lote.supervisor}</div>
        <div><strong>Volumen Estimado:</strong> ${parseFloat(lote.volumen_estimado).toLocaleString()} kg</div>
        <div><strong>Operarios Requeridos:</strong> ${lote.operarios_requeridos}</div>
        <div><strong>Operarios Asignados:</strong> ${lote.operarios_asignados}</div>
        <div><strong>Estado:</strong> <span class="font-semibold">${lote.estatus_lote}</span></div>
        <div><strong>Total Clasificado:</strong> ${parseFloat(lote.total_kg_clasificados || 0).toLocaleString()} kg</div>
        <div><strong>Total Pacas:</strong> ${lote.total_pacas_armadas || 0}</div>
        <div><strong>Tasa Error Promedio:</strong> ${parseFloat(lote.promedio_tasa_error || 0).toFixed(2)}%</div>
        <div><strong>Total Nómina:</strong> ${parseFloat(lote.total_nomina || 0).toLocaleString()}</div>
      </div>
      ${lote.observaciones ? `<div class="mt-4"><strong>Observaciones:</strong> ${lote.observaciones}</div>` : ''}
    </div>
  `;

  Swal.fire({
    title: "Información del Lote",
    html: html,
    width: "800px",
    showCloseButton: true,
    confirmButtonText: "Cerrar",
    confirmButtonColor: "#059669",
  });
}

function iniciarLote(idlote) {
  Swal.fire({
    title: "¿Iniciar lote de producción?",
    text: "Esta acción cambiará el estado del lote a 'EN PROCESO'.",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#059669",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Sí, iniciar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Produccion/iniciarLote", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ idlote: idlote }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Iniciado!", result.message, "success").then(() => {
              recargarTablaLotes();
            });
          } else {
            Swal.fire("Error", result.message || "No se pudo iniciar el lote.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}

function cerrarLote(idlote, numeroLote) {
  Swal.fire({
    title: "¿Cerrar lote de producción?",
    text: `¿Está seguro de cerrar el lote ${numeroLote}? Esta acción es irreversible.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Sí, cerrar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Produccion/cerrarLote", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ idlote: idlote }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Cerrado!", result.message, "success").then(() => {
              recargarTablaLotes();
            });
          } else {
            Swal.fire("Error", result.message || "No se pudo cerrar el lote.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}

// ========================================
// FUNCIONES DE ASIGNACIÓN DE OPERARIOS
// ========================================

function abrirModalAsignarOperarios(idlote) {
  // Cargar información del lote
  fetch(`Produccion/getLoteById/${idlote}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const lote = result.data;
        document.getElementById("idLoteAsignar").value = idlote;
        document.getElementById("infoNumeroLote").textContent = lote.numero_lote;
        document.getElementById("infoOperariosRequeridos").textContent = lote.operarios_requeridos;
        document.getElementById("infoFechaJornada").textContent = lote.fecha_jornada_formato;
        
        cargarOperariosDisponibles(lote.fecha_jornada);
        cargarAsignacionesExistentes(idlote);
        abrirModal("modalAsignarOperarios");
      } else {
        mostrarError("No se pudo cargar la información del lote.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error de conexión.");
    });
}

function cargarOperariosDisponibles(fecha) {
  fetch(`Produccion/getOperariosDisponibles?fecha=${fecha}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        mostrarOperariosDisponibles(result.data);
      } else {
        document.getElementById("bodyOperariosDisponibles").innerHTML = 
          '<tr><td colspan="3" class="text-center py-4 text-gray-500">No hay operarios disponibles</td></tr>';
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error al cargar operarios disponibles.");
    });
}

function mostrarOperariosDisponibles(operarios) {
  const tbody = document.getElementById("bodyOperariosDisponibles");
  let html = "";

  operarios.forEach((operario) => {
    const disponible = operario.estatus_disponibilidad === "DISPONIBLE";
    const claseEstado = disponible ? "text-green-600" : "text-orange-600";
    const textoEstado = disponible ? "Disponible" : `Asignado (${operario.lote_asignado})`;

    html += `
      <tr class="${disponible ? 'hover:bg-gray-50' : 'bg-gray-100'}">
        <td class="px-3 py-2">
          <input type="checkbox" class="operario-checkbox" 
                 data-idempleado="${operario.idempleado}"
                 data-nombre="${operario.nombre_completo}"
                 ${disponible ? '' : 'disabled'}
                 onchange="toggleOperarioAsignado(this)">
        </td>
        <td class="px-3 py-2">
          <div>
            <div class="font-medium">${operario.nombre_completo}</div>
            <div class="text-xs text-gray-500">${operario.puesto || 'Operario'}</div>
          </div>
        </td>
        <td class="px-3 py-2">
          <span class="${claseEstado} text-sm font-medium">${textoEstado}</span>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function toggleOperarioAsignado(checkbox) {
  const idempleado = checkbox.dataset.idempleado;
  const nombre = checkbox.dataset.nombre;

  if (checkbox.checked) {
    // Agregar a la lista de asignados
    const operarioAsignado = {
      idempleado: idempleado,
      nombre: nombre,
      tipo_tarea: 'CLASIFICACION',
      turno: 'MAÑANA',
      observaciones: ''
    };

    operariosAsignados.push(operarioAsignado);
  } else {
    // Remover de la lista de asignados
    operariosAsignados = operariosAsignados.filter(op => op.idempleado != idempleado);
  }

  actualizarListaOperariosAsignados();
}

function actualizarListaOperariosAsignados() {
  const lista = document.getElementById("listaOperariosAsignados");
  
  if (operariosAsignados.length === 0) {
    lista.innerHTML = '<p class="text-gray-500 text-center">No hay operarios asignados</p>';
    return;
  }

  let html = "";
  operariosAsignados.forEach((operario, index) => {
    html += `
      <div class="operario-asignado">
        <div class="info">
          <div class="font-medium">${operario.nombre}</div>
          <div class="flex gap-4 mt-2">
            <select class="text-xs border rounded px-2 py-1" 
                    onchange="actualizarTareaOperario(${index}, 'tipo_tarea', this.value)">
              <option value="CLASIFICACION" ${operario.tipo_tarea === 'CLASIFICACION' ? 'selected' : ''}>Clasificación</option>
              <option value="EMPAQUE" ${operario.tipo_tarea === 'EMPAQUE' ? 'selected' : ''}>Empaque</option>
            </select>
            <select class="text-xs border rounded px-2 py-1"
                    onchange="actualizarTareaOperario(${index}, 'turno', this.value)">
              <option value="MAÑANA" ${operario.turno === 'MAÑANA' ? 'selected' : ''}>Mañana</option>
              <option value="TARDE" ${operario.turno === 'TARDE' ? 'selected' : ''}>Tarde</option>
              <option value="NOCHE" ${operario.turno === 'NOCHE' ? 'selected' : ''}>Noche</option>
            </select>
          </div>
        </div>
        <div class="acciones">
          <button type="button" class="text-red-600 hover:text-red-700 p-1"
                  onclick="removerOperarioAsignado(${index})" title="Remover">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    `;
  });

  lista.innerHTML = html;
}

function actualizarTareaOperario(index, campo, valor) {
  if (operariosAsignados[index]) {
    operariosAsignados[index][campo] = valor;
  }
}

function removerOperarioAsignado(index) {
  const operario = operariosAsignados[index];
  
  // Desmarcar checkbox
  const checkbox = document.querySelector(`input[data-idempleado="${operario.idempleado}"]`);
  if (checkbox) {
    checkbox.checked = false;
  }

  // Remover de la lista
  operariosAsignados.splice(index, 1);
  actualizarListaOperariosAsignados();
}

function cargarAsignacionesExistentes(idlote) {
  fetch(`Produccion/getAsignacionesLote/${idlote}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        // Pre-cargar asignaciones existentes
        operariosAsignados = result.data.map(asignacion => ({
          idempleado: asignacion.idempleado,
          nombre: asignacion.operario,
          tipo_tarea: asignacion.tipo_tarea,
          turno: asignacion.turno,
          observaciones: asignacion.observaciones || ''
        }));

        actualizarListaOperariosAsignados();

        // Marcar checkboxes correspondientes
        operariosAsignados.forEach(operario => {
          const checkbox = document.querySelector(`input[data-idempleado="${operario.idempleado}"]`);
          if (checkbox) {
            checkbox.checked = true;
          }
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

function guardarAsignacionesOperarios() {
  const idlote = document.getElementById("idLoteAsignar").value;
  
  if (!idlote) {
    mostrarError("ID de lote no válido.");
    return;
  }

  if (operariosAsignados.length === 0) {
    mostrarError("Debe asignar al menos un operario.");
    return;
  }

  const btnGuardar = document.getElementById("btnGuardarAsignaciones");
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Produccion/asignarOperarios", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({
      idlote: idlote,
      operarios: operariosAsignados
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success").then(() => {
          cerrarModal("modalAsignarOperarios");
          recargarTablaLotes();
          operariosAsignados = [];
        });
      } else {
        Swal.fire("Error", result.message || "No se pudieron asignar los operarios.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    })
    .finally(() => {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Asignaciones`;
      }
    });
}

// ========================================
// FUNCIONES DE PROCESOS
// ========================================

function cargarDatosParaClasificacion() {
  cargarLotesEnProceso("clas_lote");
  cargarOperarios("clas_operario");
  cargarProductosPorClasificar("clas_producto_origen");
  cargarProductosClasificados("clas_producto_clasificado");
}

function cargarDatosParaEmpaque() {
  cargarLotesEnProceso("emp_lote");
  cargarOperarios("emp_operario");
  cargarProductosClasificados("emp_producto_clasificado");
  cargarConfiguracionPesos();
}

function validarSumaClasificacion() {
  const procesados = parseFloat(document.getElementById("clas_kg_procesados").value || 0);
  const limpios = parseFloat(document.getElementById("clas_kg_limpios").value || 0);
  const contaminantes = parseFloat(document.getElementById("clas_kg_contaminantes").value || 0);
  
  const suma = limpios + contaminantes;
  const diferencia = Math.abs(suma - procesados);
  
  const validacion = document.getElementById("validacionClasificacion");
  if (validacion) {
    if (diferencia < 0.01) {
      validacion.textContent = "✓ Validación correcta";
      validacion.className = "text-xs mt-1 font-semibold text-green-600";
    } else {
      validacion.textContent = `Diferencia: ${diferencia.toFixed(2)} kg`;
      validacion.className = "text-xs mt-1 font-semibold text-red-600";
    }
  }
}

function validarPesoPaca() {
  const peso = parseFloat(document.getElementById("emp_peso_paca").value || 0);
  const infoValidacion = document.getElementById("infoPesoValidacion");
  const alertaPeso = document.getElementById("alertaPeso");
  
  if (peso > 0 && configuracionActual.peso_minimo_paca && configuracionActual.peso_maximo_paca) {
    infoValidacion.classList.remove("hidden");
    
    document.getElementById("pesoMinimo").textContent = `${configuracionActual.peso_minimo_paca} kg`;
    document.getElementById("pesoMaximo").textContent = `${configuracionActual.peso_maximo_paca} kg`;
    
    if (peso < configuracionActual.peso_minimo_paca) {
      alertaPeso.textContent = "⚠️ Peso por debajo del mínimo permitido";
      alertaPeso.className = "text-xs mt-2 text-orange-600 font-medium";
      alertaPeso.classList.remove("hidden");
    } else if (peso > configuracionActual.peso_maximo_paca) {
      alertaPeso.textContent = "⚠️ Peso por encima del máximo permitido";
      alertaPeso.className = "text-xs mt-2 text-red-600 font-medium";
      alertaPeso.classList.remove("hidden");
    } else {
      alertaPeso.textContent = "✓ Peso dentro del rango permitido";
      alertaPeso.className = "text-xs mt-2 text-green-600 font-medium";
      alertaPeso.classList.remove("hidden");
    }
  } else {
    infoValidacion.classList.add("hidden");
  }
}

function registrarClasificacion() {
  const btnGuardar = document.getElementById("btnGuardarClasificacion");

  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Registrando...`;
  }

  registrarEntidad({
    formId: "formRegistrarClasificacion",
    endpoint: "Produccion/registrarClasificacion",
    campos: camposFormularioClasificacion,
    mapeoNombres: {
      "clas_lote": "idlote",
      "clas_operario": "idempleado",
      "clas_producto_origen": "idproducto_origen",
      "clas_producto_clasificado": "idproducto_clasificado",
      "clas_kg_procesados": "kg_procesados",
      "clas_kg_limpios": "kg_limpios",
      "clas_kg_contaminantes": "kg_contaminantes",
      "clas_observaciones": "observaciones",
    },
    validacionCustom: () => {
      const procesados = parseFloat(document.getElementById("clas_kg_procesados").value || 0);
      const limpios = parseFloat(document.getElementById("clas_kg_limpios").value || 0);
      const contaminantes = parseFloat(document.getElementById("clas_kg_contaminantes").value || 0);
      
      const diferencia = Math.abs((limpios + contaminantes) - procesados);
      if (diferencia > 0.01) {
        return {
          valido: false,
          mensaje: "La suma de material limpio y contaminantes debe ser igual al total procesado."
        };
      }
      
      return { valido: true };
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarClasificacion");
        actualizarEstadisticas();
        
        const form = document.getElementById("formRegistrarClasificacion");
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioClasificacion, "formRegistrarClasificacion");
        }
      });
    },
    onError: (result) => {
      Swal.fire("Error", result.message || "No se pudo registrar la clasificación.", "error");
    },
  }).finally(() => {
    if (btnGuardar) {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Registrar Clasificación`;
    }
  });
}

function registrarEmpaque() {
  const btnGuardar = document.getElementById("btnGuardarEmpaque");

  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Registrando...`;
  }

  registrarEntidad({
    formId: "formRegistrarEmpaque",
    endpoint: "Produccion/registrarEmpaque",
    campos: camposFormularioEmpaque,
    mapeoNombres: {
      "emp_lote": "idlote",
      "emp_operario": "idempleado",
      "emp_producto_clasificado": "idproducto_clasificado",
      "emp_peso_paca": "peso_paca",
      "emp_calidad": "calidad",
      "emp_observaciones": "observaciones",
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarEmpaque");
        actualizarEstadisticas();
        
        const form = document.getElementById("formRegistrarEmpaque");
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioEmpaque, "formRegistrarEmpaque");
        }
      });
    },
    onError: (result) => {
      Swal.fire("Error", result.message || "No se pudo registrar el empaque.", "error");
    },
  }).finally(() => {
    if (btnGuardar) {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Registrar Empaque`;
    }
  });
}

// ========================================
// FUNCIONES DE NÓMINA
// ========================================

function cargarLotesParaProduccionDiaria() {
  fetch("Produccion/getLotesData")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const select = document.getElementById("selectLoteProduccionDiaria");
        let options = '<option value="">Seleccionar lote...</option>';
        
        result.data.forEach(lote => {
          if (lote.estatus_lote === 'EN_PROCESO') {
            options += `<option value="${lote.idlote}" data-fecha="${lote.fecha_jornada}">${lote.numero_lote} - ${lote.fecha_jornada_formato}</option>`;
          }
        });
        
        select.innerHTML = options;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error al cargar lotes.");
    });
}

function cargarOperariosProduccionDiaria() {
  const select = document.getElementById("selectLoteProduccionDiaria");
  const idlote = select.value;
  const fechaInput = document.getElementById("fechaProduccionDiaria");
  const tbody = document.getElementById("bodyProduccionDiaria");
  
  if (!idlote) {
    tbody.innerHTML = '<tr><td colspan="6" class="border border-gray-300 px-3 py-4 text-center text-gray-500">Seleccione un lote para cargar los operarios</td></tr>';
    return;
  }

  // Establecer fecha
  const fechaOption = select.options[select.selectedIndex].dataset.fecha;
  if (fechaOption) {
    fechaInput.value = fechaOption;
  }

  document.getElementById("idLoteProduccionDiaria").value = idlote;

  // Cargar operarios asignados al lote
  fetch(`Produccion/getAsignacionesLote/${idlote}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        mostrarOperariosProduccionDiaria(result.data);
      } else {
        tbody.innerHTML = '<tr><td colspan="6" class="border border-gray-300 px-3 py-4 text-center text-gray-500">No hay operarios asignados a este lote</td></tr>';
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      tbody.innerHTML = '<tr><td colspan="6" class="border border-gray-300 px-3 py-4 text-center text-red-500">Error al cargar operarios</td></tr>';
    });
}

function mostrarOperariosProduccionDiaria(operarios) {
  const tbody = document.getElementById("bodyProduccionDiaria");
  let html = "";

  operarios.forEach((operario, index) => {
    html += `
      <tr>
        <td class="border border-gray-300 px-3 py-2">
          <input type="hidden" name="operarios[${index}][idempleado]" value="${operario.idempleado}">
          <div class="font-medium">${operario.operario}</div>
          <div class="text-xs text-gray-500">${operario.tipo_tarea}</div>
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <input type="number" step="0.01" min="0" 
                 name="operarios[${index}][kg_clasificados]"
                 class="w-full text-center border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                 placeholder="0.00"
                 onchange="calcularTasaError(${index})">
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <input type="number" step="0.01" min="0" 
                 name="operarios[${index}][kg_contaminantes]"
                 class="w-full text-center border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                 placeholder="0.00"
                 onchange="calcularTasaError(${index})">
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <input type="number" min="0" 
                 name="operarios[${index}][pacas_armadas]"
                 class="w-full text-center border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                 placeholder="0">
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <span id="tasaError_${index}" class="font-medium">0.00%</span>
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <input type="text" 
                 name="operarios[${index}][observaciones]"
                 class="w-full border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                 placeholder="Observaciones...">
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function calcularTasaError(index) {
  const kgClasificados = parseFloat(document.querySelector(`input[name="operarios[${index}][kg_clasificados]"]`).value || 0);
  const kgContaminantes = parseFloat(document.querySelector(`input[name="operarios[${index}][kg_contaminantes]"]`).value || 0);
  
  let tasaError = 0;
  if (kgClasificados > 0) {
    tasaError = (kgContaminantes / kgClasificados) * 100;
  }

  const spanTasa = document.getElementById(`tasaError_${index}`);
  if (spanTasa) {
    spanTasa.textContent = `${tasaError.toFixed(2)}%`;
    
    // Colorear según la tasa de error
     spanTasa.className = "font-medium";
    if (tasaError > (configuracionActual.umbral_error_maximo || 5)) {
      spanTasa.classList.add("text-red-600");
    } else if (tasaError > (configuracionActual.umbral_error_maximo || 5) * 0.8) {
      spanTasa.classList.add("text-orange-600");
    } else {
      spanTasa.classList.add("text-green-600");
    }
  }
}

function guardarProduccionDiaria() {
  const idlote = document.getElementById("idLoteProduccionDiaria").value;
  
  if (!idlote) {
    mostrarError("Debe seleccionar un lote.");
    return;
  }

  // Recopilar datos de todos los operarios
  const tabla = document.getElementById("tablaProduccionDiaria");
  const filas = tabla.querySelectorAll("tbody tr");
  const registros = [];

  filas.forEach((fila, index) => {
    const idempleado = fila.querySelector(`input[name="operarios[${index}][idempleado]"]`)?.value;
    const kgClasificados = parseFloat(fila.querySelector(`input[name="operarios[${index}][kg_clasificados]"]`)?.value || 0);
    const kgContaminantes = parseFloat(fila.querySelector(`input[name="operarios[${index}][kg_contaminantes]"]`)?.value || 0);
    const pacasArmadas = parseInt(fila.querySelector(`input[name="operarios[${index}][pacas_armadas]"]`)?.value || 0);
    const observaciones = fila.querySelector(`input[name="operarios[${index}][observaciones]"]`)?.value || '';

    if (idempleado) {
      registros.push({
        idempleado: idempleado,
        kg_clasificados: kgClasificados,
        kg_contaminantes: kgContaminantes,
        pacas_armadas: pacasArmadas,
        observaciones: observaciones
      });
    }
  });

  if (registros.length === 0) {
    mostrarError("No hay registros para guardar.");
    return;
  }

  const btnGuardar = document.getElementById("btnGuardarProduccionDiaria");
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Produccion/registrarProduccionDiaria", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({
      idlote: idlote,
      registros: registros
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success").then(() => {
          cerrarModal("modalRegistrarProduccionDiaria");
          actualizarEstadisticas();
          if (tablaNomina) {
            cargarDatosNomina();
          }
        });
      } else {
        Swal.fire("Error", result.message || "No se pudo guardar la producción diaria.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    })
    .finally(() => {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Producción`;
      }
    });
}

function abrirModalCalcularNomina() {
  Swal.fire({
    title: "Calcular Nómina de Producción",
    html: `
      <div class="text-left space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
          <input type="date" id="swal-fecha-inicio" class="w-full border rounded px-3 py-2" 
                 value="${new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]}">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
          <input type="date" id="swal-fecha-fin" class="w-full border rounded px-3 py-2" 
                 value="${new Date().toISOString().split('T')[0]}">
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
          <p class="text-yellow-800 text-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Esta acción calculará los salarios para todos los registros en el periodo seleccionado.
          </p>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonColor: "#f59e0b",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Calcular Nómina",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      const fechaInicio = document.getElementById("swal-fecha-inicio").value;
      const fechaFin = document.getElementById("swal-fecha-fin").value;

      if (!fechaInicio || !fechaFin) {
        Swal.showValidationMessage("Debe seleccionar ambas fechas");
        return false;
      }

      if (fechaInicio > fechaFin) {
        Swal.showValidationMessage("La fecha de inicio no puede ser posterior a la fecha fin");
        return false;
      }

      return { fechaInicio, fechaFin };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      calcularNomina(result.value.fechaInicio, result.value.fechaFin);
    }
  });
}

function calcularNomina(fechaInicio, fechaFin) {
  Swal.fire({
    title: "Calculando Nómina...",
    html: "Por favor espere mientras se calculan los salarios.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  fetch("Produccion/calcularNomina", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      Swal.close();
      
      if (result.status) {
        const mensaje = `
          <div class="text-left space-y-3">
            <p class="text-green-600 font-medium">${result.message}</p>
            <div class="bg-gray-50 p-3 rounded text-sm">
              <div><strong>Registros actualizados:</strong> ${result.registros_actualizados}</div>
              <div><strong>Tasa de error general:</strong> ${result.tasa_error_general}%</div>
              <div><strong>Beta efectivo:</strong> ${result.beta_efectivo}</div>
              ${result.penalizacion_aplicada ? '<div class="text-orange-600"><strong>⚠️ Penalización aplicada por alta tasa de error</strong></div>' : ''}
            </div>
          </div>
        `;

        Swal.fire({
          title: "¡Nómina Calculada!",
          html: mensaje,
          icon: "success",
          confirmButtonColor: "#059669"
        }).then(() => {
          cargarDatosNomina();
          actualizarEstadisticas();
        });
      } else {
        Swal.fire("Error", result.message || "No se pudo calcular la nómina.", "error");
      }
    })
    .catch((error) => {
      Swal.close();
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function cargarDatosNomina() {
  // Implementar carga de datos para la tabla de nómina
  fetch("Produccion/getProductividadOperarios")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && tablaNomina) {
        tablaNomina.clear();
        
        result.data.forEach(registro => {
          tablaNomina.row.add({
            fecha: registro.ultima_jornada || '',
            operario: registro.operario,
            kg_clasificados: parseFloat(registro.total_kg_clasificados || 0).toFixed(2),
            pacas_armadas: registro.total_pacas_armadas || 0,
            salario_total: `${parseFloat(registro.total_salarios || 0).toLocaleString()}`,
            estatus: 'CALCULADO'
          });
        });
        
        tablaNomina.draw();
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

// ========================================
// FUNCIONES DE CONFIGURACIÓN
// ========================================

function cargarConfiguracionInicial() {
  fetch("Produccion/getConfiguracionProduccion")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        configuracionActual = result.data;
        mostrarConfiguracion(result.data);
      } else {
        mostrarError("No se pudo cargar la configuración.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error al cargar configuración.");
    });
}

function mostrarConfiguracion(config) {
  const campos = [
    'productividad_clasificacion',
    'capacidad_maxima_planta',
    'salario_base',
    'beta_clasificacion',
    'gamma_empaque',
    'umbral_error_maximo',
    'penalizacion_beta',
    'peso_minimo_paca',
    'peso_maximo_paca'
  ];

  campos.forEach(campo => {
    const input = document.getElementById(campo);
    if (input && config[campo] !== undefined) {
      input.value = config[campo];
    }
  });
}

function guardarConfiguracion() {
  const formData = new FormData(document.getElementById("formConfiguracionProduccion"));
  const data = {};
  
  for (let [key, value] of formData.entries()) {
    data[key] = value;
  }

  const btnGuardar = document.getElementById("btnGuardarConfiguracion");
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Produccion/updateConfiguracionProduccion", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        configuracionActual = data;
        Swal.fire("¡Éxito!", result.message, "success");
      } else {
        Swal.fire("Error", result.message || "No se pudo guardar la configuración.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    })
    .finally(() => {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Configuración`;
      }
    });
}

// ========================================
// FUNCIONES DE REPORTES
// ========================================

function generarReporte() {
  const fechaInicio = document.getElementById("filtroFechaInicio").value;
  const fechaFin = document.getElementById("filtroFechaFin").value;
  const tipoReporte = document.getElementById("filtroTipoReporte").value;

  if (!fechaInicio || !fechaFin) {
    mostrarError("Debe seleccionar el rango de fechas.");
    return;
  }

  const btnGenerar = document.getElementById("btnGenerarReporte");
  if (btnGenerar) {
    btnGenerar.disabled = true;
    btnGenerar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Generando...`;
  }

  fetch(`Produccion/exportarReporteProduccion?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&tipo=${tipoReporte}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        mostrarReporte(result.data, tipoReporte);
      } else {
        mostrarError("No se pudo generar el reporte.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error al generar reporte.");
    })
    .finally(() => {
      if (btnGenerar) {
        btnGenerar.disabled = false;
        btnGenerar.innerHTML = `<i class="fas fa-file-pdf mr-2"></i> Generar Reporte`;
      }
    });
}

function mostrarReporte(data, tipo) {
  const areaReporte = document.getElementById("areaReporte");
  const contenidoReporte = document.getElementById("contenidoReporte");
  
  let html = "";

  switch (tipo) {
    case 'general':
      html = generarReporteGeneral(data);
      break;
    case 'nomina':
      html = generarReporteNomina(data);
      break;
    case 'pacas':
      html = generarReportePacas(data);
      break;
  }

  contenidoReporte.innerHTML = html;
  areaReporte.classList.remove("hidden");
}

function generarReporteGeneral(data) {
  const resumen = data.resumen || {};
  const topOperarios = data.top_operarios || [];

  return `
    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-blue-600">${resumen.total_lotes || 0}</div>
          <div class="text-sm text-blue-800">Total Lotes</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-green-600">${parseFloat(resumen.total_kg_clasificados || 0).toLocaleString()}</div>
          <div class="text-sm text-green-800">Kg Clasificados</div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-purple-600">${resumen.total_pacas_armadas || 0}</div>
          <div class="text-sm text-purple-800">Pacas Armadas</div>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg text-center">
          <div class="text-2xl font-bold text-orange-600">${parseFloat(resumen.total_nomina || 0).toLocaleString()}</div>
          <div class="text-sm text-orange-800">Total Nómina</div>
        </div>
      </div>
      
      <div>
        <h4 class="text-lg font-semibold mb-3">Top 10 Operarios por Productividad</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse border border-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th class="border border-gray-300 px-3 py-2 text-left">Operario</th>
                <th class="border border-gray-300 px-3 py-2 text-right">Kg Clasificados</th>
                <th class="border border-gray-300 px-3 py-2 text-right">Pacas</th>
                <th class="border border-gray-300 px-3 py-2 text-right">Tasa Error</th>
                <th class="border border-gray-300 px-3 py-2 text-right">Total Salario</th>
              </tr>
            </thead>
            <tbody>
              ${topOperarios.map(operario => `
                <tr>
                  <td class="border border-gray-300 px-3 py-2">${operario.operario}</td>
                  <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(operario.total_kg).toLocaleString()}</td>
                  <td class="border border-gray-300 px-3 py-2 text-right">${operario.total_pacas}</td>
                  <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(operario.promedio_error).toFixed(2)}%</td>
                  <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(operario.total_salario).toLocaleString()}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

function generarReporteNomina(data) {
  const detalleNomina = data.detalle_nomina || [];

  return `
    <div class="space-y-4">
      <h4 class="text-lg font-semibold">Detalle de Nómina</h4>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse border border-gray-300">
          <thead class="bg-gray-50">
            <tr>
              <th class="border border-gray-300 px-3 py-2 text-left">Operario</th>
              <th class="border border-gray-300 px-3 py-2 text-left">Fecha</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Kg Clasificados</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Pacas</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Salario Base</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Bono Clasificación</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Bono Empaque</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            ${detalleNomina.map(registro => `
              <tr>
                <td class="border border-gray-300 px-3 py-2">${registro.operario}</td>
                <td class="border border-gray-300 px-3 py-2">${registro.fecha_jornada}</td>
                <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(registro.kg_clasificados).toFixed(2)}</td>
                <td class="border border-gray-300 px-3 py-2 text-right">${registro.pacas_armadas}</td>
                <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(registro.salario_base_dia).toFixed(2)}</td>
                <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(registro.bono_clasificacion).toFixed(2)}</td>
                <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(registro.bono_empaque).toFixed(2)}</td>
                <td class="border border-gray-300 px-3 py-2 text-right font-bold">${parseFloat(registro.salario_total).toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function generarReportePacas(data) {
  const pacasProducidas = data.pacas_producidas || [];

  return `
    <div class="space-y-4">
      <h4 class="text-lg font-semibold">Pacas Producidas</h4>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse border border-gray-300">
          <thead class="bg-gray-50">
            <tr>
              <th class="border border-gray-300 px-3 py-2 text-left">Código Paca</th>
              <th class="border border-gray-300 px-3 py-2 text-left">Operario</th>
              <th class="border border-gray-300 px-3 py-2 text-left">Lote</th>
              <th class="border border-gray-300 px-3 py-2 text-right">Peso (kg)</th>
              <th class="border border-gray-300 px-3 py-2 text-left">Calidad</th>
              <th class="border border-gray-300 px-3 py-2 text-left">Fecha Empaque</th>
            </tr>
          </thead>
          <tbody>
            ${pacasProducidas.map(paca => `
              <tr>
                <td class="border border-gray-300 px-3 py-2">${paca.codigo_paca}</td>
                <td class="border border-gray-300 px-3 py-2">${paca.operario_empacador}</td>
                <td class="border border-gray-300 px-3 py-2">${paca.numero_lote}</td>
                <td class="border border-gray-300 px-3 py-2 text-right">${parseFloat(paca.peso_paca).toFixed(2)}</td>
                <td class="border border-gray-300 px-3 py-2">${paca.calidad}</td>
                <td class="border border-gray-300 px-3 py-2">${paca.fecha_empaque_formato}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function exportarReporte() {
  // Implementar exportación del reporte actual
  const contenido = document.getElementById("contenidoReporte").innerHTML;
  
  if (!contenido) {
    mostrarError("No hay reporte para exportar. Genere un reporte primero.");
    return;
  }

  // Crear ventana de impresión
  const ventanaImpresion = window.open('', '_blank');
  ventanaImpresion.document.write(`
    <html>
      <head>
        <title>Reporte de Producción</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          table { border-collapse: collapse; width: 100%; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f2f2f2; }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
          .font-bold { font-weight: bold; }
          .bg-blue-50 { background-color: #eff6ff; }
          .bg-green-50 { background-color: #f0fdf4; }
          .bg-purple-50 { background-color: #faf5ff; }
          .bg-orange-50 { background-color: #fff7ed; }
          @media print {
            body { margin: 0; }
            .no-print { display: none; }
          }
        </style>
      </head>
      <body>
        <div style="text-align: center; margin-bottom: 20px;">
          <h1>Reporte de Producción</h1>
          <p>Generado el: ${new Date().toLocaleString()}</p>
        </div>
        ${contenido}
        <div class="no-print" style="margin-top: 20px; text-align: center;">
          <button onclick="window.print()">Imprimir</button>
          <button onclick="window.close()">Cerrar</button>
        </div>
      </body>
    </html>
  `);
  ventanaImpresion.document.close();
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

function recargarTablaLotes() {
  if (tablaLotes && tablaLotes.ajax && typeof tablaLotes.ajax.reload === 'function') {
    tablaLotes.ajax.reload(null, false);
  } else {
    window.location.reload();
  }
}

function cargarSupervisores() {
  fetch("Produccion/getOperariosDisponibles")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const select = document.getElementById("lote_supervisor");
        let options = '<option value="">Seleccionar supervisor...</option>';
        
        result.data.forEach(operario => {
        options += `<option value="${operario.idempleado}">${operario.nombre_completo}</option>`;
        });

        
        select.innerHTML = options;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

function cargarLotesEnProceso(selectId) {
  fetch("Produccion/getLotesData")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const select = document.getElementById(selectId);
        let options = '<option value="">Seleccionar lote...</option>';
        
        result.data.forEach(lote => {
          if (lote.estatus_lote === 'EN_PROCESO') {
            options += `<option value="${lote.idlote}">${lote.numero_lote} - ${lote.fecha_jornada_formato}</option>`;
          }
        });
        
        select.innerHTML = options;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

function cargarOperarios(selectId) {
  fetch("Produccion/getOperariosDisponibles")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const select = document.getElementById(selectId);
        let options = '<option value="">Seleccionar operario...</option>';
        
        result.data.forEach(operario => {
          options += `<option value="${operario.idempleado}">${operario.nombre_completo}</option>`;
        });
        
        select.innerHTML = options;
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

function cargarProductosPorClasificar(selectId) {
  // Implementar carga de productos por clasificar desde el endpoint de productos
  const select = document.getElementById(selectId);
  select.innerHTML = '<option value="">Cargando productos...</option>';
  
  // Simular carga - reemplazar por llamada real
  setTimeout(() => {
    select.innerHTML = `
      <option value="">Seleccionar producto...</option>
      <option value="7">Cartón por Clasificar</option>
      <option value="8">Archivo por Clasificar</option>
    `;
  }, 500);
}

function cargarProductosClasificados(selectId) {
  // Implementar carga de productos clasificados
  const select = document.getElementById(selectId);
  select.innerHTML = '<option value="">Cargando productos...</option>';
  
  // Simular carga - reemplazar por llamada real
  setTimeout(() => {
    select.innerHTML = `
      <option value="">Seleccionar producto...</option>
      <option value="1">Archivo</option>
      <option value="4">Cartón</option>
    `;
  }, 500);
}

function cargarConfiguracionPesos() {
  if (configuracionActual.peso_minimo_paca && configuracionActual.peso_maximo_paca) {
    document.getElementById("pesoMinimo").textContent = `${configuracionActual.peso_minimo_paca} kg`;
    document.getElementById("pesoMaximo").textContent = `${configuracionActual.peso_maximo_paca} kg`;
  }
}

function actualizarEstadisticas() {
  const fechaHoy = new Date().toISOString().split('T')[0];
  
  // Cargar estadísticas del día
  fetch(`Produccion/getProduccionDiaria?fecha=${fechaHoy}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data && result.data.length > 0) {
        const datos = result.data[0];
        document.getElementById("stat-produccion-hoy").textContent = 
          `${parseFloat(datos.total_kg_clasificados || 0).toLocaleString()} kg`;
        document.getElementById("stat-pacas-hoy").textContent = 
          datos.total_pacas_armadas || 0;
        document.getElementById("stat-operarios-activos").textContent = 
          datos.operarios_registrados || 0;
      } else {
        document.getElementById("stat-produccion-hoy").textContent = "0 kg";
        document.getElementById("stat-pacas-hoy").textContent = "0";
        document.getElementById("stat-operarios-activos").textContent = "0";
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}
function mostrarError(mensaje) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: mensaje,
    confirmButtonColor: "#dc2626"
  });
}

function mostrarExito(mensaje) {
  Swal.fire({
    icon: "success",
    title: "¡Éxito!",
    text: mensaje,
    confirmButtonColor: "#059669"
  });
}

function mostrarAdvertencia(mensaje) {
  Swal.fire({
    icon: "warning",
    title: "Advertencia",
    text: mensaje,
    confirmButtonColor: "#f59e0b"
  });
}

window.toggleOperarioAsignado = toggleOperarioAsignado;
window.actualizarTareaOperario = actualizarTareaOperario;
window.removerOperarioAsignado = removerOperarioAsignado;
window.calcularTasaError = calcularTasaError;

ocument.addEventListener("DOMContentLoaded", function() {
  // Cargar configuración cada 5 minutos para mantener sincronización
  setInterval(cargarConfiguracionInicial, 300000);
  
  // Actualizar estadísticas cada 2 minutos
  setInterval(actualizarEstadisticas, 120000);
});