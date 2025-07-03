// ========================================
// IMPORTACIONES
// ========================================
import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

// ========================================
// VARIABLES GLOBALES
// ========================================
let tablaLotes, tablaProcesos, tablaNomina;
let operariosAsignados = [];
let configuracionActual = {};
let operariosDisponibles = [];
let loteActual = null;

// ========================================
// CONFIGURACIÓN DE CAMPOS DE FORMULARIO
// ========================================
const camposFormularioLote = [ /* ... */ ];
const camposFormularioClasificacion = [ /* ... */ ];
const camposFormularioEmpaque = [ /* ... */ ];

// ========================================
// INICIALIZACIÓN GENERAL
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  inicializarPestañas();
  inicializarTablas();
  inicializarEventos();
  cargarConfiguracionInicial();
});

// ========================================
// INICIALIZACIÓN DE COMPONENTES
// ========================================
function inicializarPestañas() { const botonesPestaña = document.querySelectorAll(".tab-button");
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
      } else if (pestañaId === "content-procesos" && tablaProcesos) {
        setTimeout(() => tablaProcesos.ajax.reload(null, false), 100);
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
  if ($.fn.DataTable.isDataTable("#TablaProcesos")) {
    $("#TablaProcesos").DataTable().destroy();
  }

  tablaProcesos = $("#TablaProcesos").DataTable({
    processing: true,
    ajax: {
      url: "./Produccion/getProcesosRecientes",
      type: "GET",
      dataSrc: function (json) {
        if (json && json.status && Array.isArray(json.data)) {
          return json.data;
        }
        return [];
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Error cargando procesos:", textStatus, errorThrown);
      }
    },
    columns: [
      { data: "fecha", title: "Fecha", className: "all" },
      { data: "operario", title: "Operario", className: "all" },
      { data: "proceso", title: "Proceso", className: "desktop" },
      { data: "cantidad", title: "Cantidad", className: "tablet-l" },
      { data: "observaciones", title: "Observaciones", className: "desktop" }
    ],
    language: {
      emptyTable: "No hay procesos registrados.",
      processing: "Cargando procesos..."
    },
    pageLength: 5,
    order: [[0, "desc"]]
  });
}

function inicializarTablaNomina() {
  if ($.fn.DataTable.isDataTable("#TablaNomina")) {
    $("#TablaNomina").DataTable().destroy();
  }

  tablaNomina = $("#TablaNomina").DataTable({
    processing: true,
    ajax: {
      url: "./Produccion/getRegistrosNomina",
      type: "GET",
      dataSrc: function (json) {
        if (json && json.status && Array.isArray(json.data)) {
          return json.data;
        }
        return [];
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Error cargando nómina:", textStatus, errorThrown);
      }
    },
    columns: [
      { data: "fecha", title: "Fecha", className: "all" },
      { data: "operario", title: "Operario", className: "all" },
      { data: "kg_clasificados", title: "Kg Clasificados", className: "desktop" },
      { data: "pacas_armadas", title: "Pacas", className: "tablet-l" },
      { data: "salario_total", title: "Salario", className: "all" },
      { data: "estatus", title: "Estado", className: "desktop" }
    ],
    language: {
      emptyTable: "No hay registros de nómina.",
      processing: "Cargando nómina..."
    },
    pageLength: 10,
    order: [[0, "desc"]]
  });
}

// ========================================
// INICIALIZACIÓN DE EVENTOS
// ========================================
function inicializarEventos() {
  inicializarEventosLotes();
  inicializarEventosAsignacion();
  inicializarEventosProcesos();
  inicializarEventosNomina();
  inicializarEventosConfiguracion();
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
      cargarEmpleadosActivos();
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
    console.log("🚀 Iniciando asignación para lote:", idlote);
    
    // Limpiar estado anterior
    operariosAsignados = [];
    operariosDisponibles = [];
    loteActual = null;
    
    // Mostrar estado de carga
    mostrarEstadoCarga();
    
    // Cargar información del lote
    cargarInformacionLote(idlote)
        .then(lote => {
            loteActual = lote;
            console.log("✅ Lote cargado:", lote);
            return cargarOperariosDisponibles(lote.fecha_jornada);
        })
        .then(operarios => {
            operariosDisponibles = operarios;
            console.log("✅ Operarios disponibles cargados:", operarios.length);
            return cargarAsignacionesExistentes(idlote);
        })
        .then(asignaciones => {
            console.log("✅ Asignaciones existentes cargadas:", asignaciones.length);
            abrirModal("modalAsignarOperarios");
            actualizarContadores();
        })
        .catch(error => {
            console.error("❌ Error en el proceso:", error);
            mostrarError("Error al cargar la información: " + error.message);
        });
}
function mostrarEstadoCarga() {
    const tbody = document.getElementById("bodyOperariosDisponibles");
    const lista = document.getElementById("listaOperariosAsignados");
    
    tbody.innerHTML = `
        <tr>
            <td colspan="3" class="text-center py-8 text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Cargando operarios...</p>
            </td>
        </tr>
    `;
    
    lista.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Cargando asignaciones...</p>
        </div>
    `;
}
function cargarInformacionLote(idlote) {
    return fetch(`Produccion/getLoteById/${idlote}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(result => {
            if (!result.status || !result.data) {
                throw new Error(result.message || "No se pudo obtener la información del lote");
            }
            
            const lote = result.data;
            
            // Actualizar la información en el modal
            document.getElementById("idLoteAsignar").value = idlote;
            document.getElementById("infoNumeroLote").textContent = lote.numero_lote;
            document.getElementById("infoOperariosRequeridos").textContent = lote.operarios_requeridos;
            document.getElementById("infoFechaJornada").textContent = lote.fecha_jornada_formato;
            document.getElementById("progresoRequeridos").textContent = lote.operarios_requeridos;
            
            return lote;
        });
}
function cargarOperariosDisponibles(fecha) {
    return fetch(`Produccion/getOperariosDisponibles?fecha=${fecha}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(result => {
            if (!result.status) {
                throw new Error(result.message || "Error al cargar operarios disponibles");
            }
            
            const operarios = result.data || [];
            mostrarOperariosDisponibles(operarios);
            return operarios;
        });
}

function mostrarOperariosDisponibles(operarios) {
    console.log("🎯 Mostrando operarios disponibles:", operarios);
    
    const tbody = document.getElementById("bodyOperariosDisponibles");
    
    if (!operarios || operarios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center py-8 text-gray-500">
                    <i class="fas fa-user-slash text-2xl mb-2"></i>
                    <p>No hay operarios disponibles</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = "";
    operarios.forEach((operario) => {
        const disponible = operario.estatus_disponibilidad === "DISPONIBLE";
        const badgeClass = disponible ? "badge-disponible" : "badge-asignado";
        const textoEstado = disponible ? "Disponible" : `Asignado (${operario.lote_asignado || 'N/A'})`;

        html += `
            <tr class="${disponible ? 'hover:bg-blue-50 cursor-pointer' : 'bg-gray-50 opacity-75'}" 
                data-operario-id="${operario.idempleado}">
                <td class="px-3 py-3 text-center">
                    <input type="checkbox" 
                           class="operario-checkbox" 
                           data-idempleado="${operario.idempleado}"
                           data-nombre="${operario.nombre_completo}"
                           ${disponible ? '' : 'disabled'}
                           onchange="toggleOperarioAsignado(this)">
                </td>
                <td class="px-3 py-3">
                    <div>
                        <div class="font-medium text-gray-900">${operario.nombre_completo}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-briefcase mr-1"></i>${operario.puesto || 'Operario'}
                            ${operario.telefono_principal ? `<i class="fas fa-phone ml-2 mr-1"></i>${operario.telefono_principal}` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-3 py-3">
                    <span class="${badgeClass}">${textoEstado}</span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function toggleOperarioAsignado(checkbox) {
    const idempleado = checkbox.dataset.idempleado;
    const nombre = checkbox.dataset.nombre;
    
    console.log("🔄 Toggle operario:", { idempleado, nombre, checked: checkbox.checked });

    if (checkbox.checked) {
        // Verificar si ya está asignado
        const yaAsignado = operariosAsignados.find(op => op.idempleado === idempleado);
        if (yaAsignado) {
            console.log("⚠️ Operario ya asignado:", nombre);
            return;
        }

        // Agregar a la lista de asignados
        const operarioAsignado = {
            idempleado: idempleado,
            nombre: nombre,
            tipo_tarea: 'CLASIFICACION',
            turno: 'MAÑANA',
            observaciones: ''
        };

        operariosAsignados.push(operarioAsignado);
        console.log("➕ Operario agregado:", operarioAsignado);
    } else {
        // Remover de la lista de asignados
        const index = operariosAsignados.findIndex(op => op.idempleado === idempleado);
        if (index !== -1) {
            const removido = operariosAsignados.splice(index, 1)[0];
            console.log("➖ Operario removido:", removido);
        }
    }

    actualizarListaOperariosAsignados();
    actualizarContadores();
}

function actualizarListaOperariosAsignados() {
    console.log("🔄 Actualizando lista de asignados:", operariosAsignados);
    
    const lista = document.getElementById("listaOperariosAsignados");
    
    if (!operariosAsignados || operariosAsignados.length === 0) {
        lista.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-gray-500 py-8">
                <i class="fas fa-user-plus text-3xl mb-3 text-gray-300"></i>
                <p class="text-center">No hay operarios asignados</p>
                <p class="text-xs text-center mt-1">Selecciona operarios de la lista de la izquierda</p>
            </div>
        `;
        return;
    }

    let html = "";
    operariosAsignados.forEach((operario, index) => {
        html += `
            <div class="operario-asignado" data-index="${index}">
                <div class="info">
                    <div class="font-medium">${operario.nombre}</div>
                    <div class="flex gap-2 mt-3">
                        <select class="operario-select" 
                                onchange="actualizarTareaOperario(${index}, 'tipo_tarea', this.value)"
                                title="Tipo de tarea">
                            <option value="CLASIFICACION" ${operario.tipo_tarea === 'CLASIFICACION' ? 'selected' : ''}>
                                <i class="fas fa-filter"></i> Clasificación
                            </option>
                            <option value="EMPAQUE" ${operario.tipo_tarea === 'EMPAQUE' ? 'selected' : ''}>
                                <i class="fas fa-box"></i> Empaque
                            </option>
                        </select>
                        <select class="operario-select"
                                onchange="actualizarTareaOperario(${index}, 'turno', this.value)"
                                title="Turno de trabajo">
                            <option value="MAÑANA" ${operario.turno === 'MAÑANA' ? 'selected' : ''}>🌅 Mañana</option>
                            <option value="TARDE" ${operario.turno === 'TARDE' ? 'selected' : ''}>☀️ Tarde</option>
                            <option value="NOCHE" ${operario.turno === 'NOCHE' ? 'selected' : ''}>🌙 Noche</option>
                        </select>
                    </div>
                </div>
                <div class="acciones">
                    <button type="button" 
                            class="text-red-600 hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-all duration-200"
                            onclick="removerOperarioAsignado(${index})" 
                            title="Remover operario">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });

    lista.innerHTML = html;
}

function actualizarTareaOperario(index, campo, valor) {
    console.log("🔧 Actualizando tarea:", { index, campo, valor });
    
    if (index >= 0 && index < operariosAsignados.length) {
        operariosAsignados[index][campo] = valor;
        console.log("✅ Operario actualizado:", operariosAsignados[index]);
    }
}

function removerOperarioAsignado(index) {
    console.log("🗑️ Removiendo operario en índice:", index);
    
    if (index >= 0 && index < operariosAsignados.length) {
        const operario = operariosAsignados[index];
        
        // Desmarcar checkbox
        const checkbox = document.querySelector(`input[data-idempleado="${operario.idempleado}"]`);
        if (checkbox) {
            checkbox.checked = false;
        }

        // Remover de la lista
        operariosAsignados.splice(index, 1);
        
        // Actualizar interfaz
        actualizarListaOperariosAsignados();
        actualizarContadores();
        
        console.log("✅ Operario removido. Lista actual:", operariosAsignados);
    }
}

function actualizarContadores() {
    const asignados = operariosAsignados.length;
    const requeridos = loteActual ? loteActual.operarios_requeridos : 0;
    
    document.getElementById("contadorAsignados").textContent = asignados;
    document.getElementById("progresoAsignados").textContent = asignados;
    
    // Actualizar estado del botón de guardar
    const btnGuardar = document.getElementById("btnGuardarAsignaciones");
    if (btnGuardar) {
        btnGuardar.disabled = asignados === 0;
        btnGuardar.classList.toggle("opacity-50", asignados === 0);
        btnGuardar.classList.toggle("cursor-not-allowed", asignados === 0);
    }
    
    console.log(`📊 Contadores actualizados: ${asignados}/${requeridos}`);
}
function cargarAsignacionesExistentes(idlote) {
    return fetch(`Produccion/getAsignacionesLote/${idlote}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(result => {
            if (!result.status) {
                console.log("⚠️ No hay asignaciones existentes o error:", result.message);
                return [];
            }
            
            const asignaciones = result.data || [];
            
            if (asignaciones.length > 0) {
                // Convertir asignaciones al formato interno
                operariosAsignados = asignaciones.map(asignacion => ({
                    idempleado: String(asignacion.idempleado),
                    nombre: asignacion.operario,
                    tipo_tarea: asignacion.tipo_tarea || 'CLASIFICACION',
                    turno: asignacion.turno || 'MAÑANA',
                    observaciones: asignacion.observaciones || ''
                }));
                
                console.log("📋 Asignaciones convertidas:", operariosAsignados);
                
                // Actualizar la interfaz
                setTimeout(() => {
                    actualizarListaOperariosAsignados();
                    marcarCheckboxesAsignados();
                }, 100);
            }
            
            return asignaciones;
        });
}
function marcarCheckboxesAsignados() {
    console.log("☑️ Marcando checkboxes para operarios asignados:", operariosAsignados);
    
    operariosAsignados.forEach(operario => {
        const checkbox = document.querySelector(`input[data-idempleado="${operario.idempleado}"]`);
        if (checkbox) {
            checkbox.checked = true;
            console.log("✅ Checkbox marcado para:", operario.nombre);
        } else {
            console.warn("⚠️ No se encontró checkbox para operario:", operario.nombre, operario.idempleado);
        }
    });
}
function guardarAsignacionesOperarios() {
    console.log("💾 Iniciando guardado de asignaciones");
    
    const idlote = document.getElementById("idLoteAsignar").value;
    
    // Validaciones
    if (!idlote || idlote <= 0) {
        mostrarError("ID de lote no válido.");
        return;
    }

    if (!operariosAsignados || operariosAsignados.length === 0) {
        mostrarError("Debe asignar al menos un operario.");
        return;
    }

    // Validar datos de cada operario
    for (let i = 0; i < operariosAsignados.length; i++) {
        const operario = operariosAsignados[i];
        if (!operario.idempleado || !operario.tipo_tarea || !operario.turno) {
            mostrarError(`Datos incompletos para el operario: ${operario.nombre}`);
            return;
        }
    }

    const btnGuardar = document.getElementById("btnGuardarAsignaciones");
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
    }

    const payload = {
        idlote: parseInt(idlote),
        operarios: operariosAsignados.map(op => ({
            idempleado: parseInt(op.idempleado),
            tipo_tarea: op.tipo_tarea,
            turno: op.turno,
            observaciones: op.observaciones || ''
        }))
    };

    console.log("📤 Payload a enviar:", payload);

    fetch("Produccion/asignarOperarios", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(payload),
    })
    .then(response => {
        console.log("📥 Respuesta recibida:", response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(result => {
        console.log("📋 Resultado del guardado:", result);
        
        if (result.status) {
            Swal.fire({
                icon: "success",
                title: "¡Éxito!",
                text: result.message,
                confirmButtonColor: "#059669"
            }).then(() => {
                cerrarModal("modalAsignarOperarios");
                recargarTablaLotes();
                // Limpiar estado
                operariosAsignados = [];
                operariosDisponibles = [];
                loteActual = null;
            });
        } else {
            throw new Error(result.message || "Error desconocido del servidor");
        }
    })
    .catch(error => {
        console.error("❌ Error en guardado:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error al guardar asignaciones: " + error.message,
            confirmButtonColor: "#dc2626"
        });
    })
    .finally(() => {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i>Guardar Asignaciones`;
        }
    });
}
function debugAsignaciones() {
    const debugInfo = {
        loteActual: loteActual,
        operariosAsignados: operariosAsignados,
        operariosDisponibles: operariosDisponibles.length,
        idLote: document.getElementById("idLoteAsignar").value,
        checkboxes: document.querySelectorAll('.operario-checkbox').length,
        checkboxesMarcados: document.querySelectorAll('.operario-checkbox:checked').length
    };

    console.log("🐛 DEBUG - Estado actual:", debugInfo);

    const debugContent = document.getElementById("debugContent");
    if (debugContent) {
        debugContent.innerHTML = `<pre>${JSON.stringify(debugInfo, null, 2)}</pre>`;
    }

    // Mostrar panel de debug
    const debugPanel = document.getElementById("debugPanel");
    if (debugPanel) {
        debugPanel.style.display = debugPanel.style.display === 'none' ? 'block' : 'none';
    }

    return debugInfo;
}
// ========================================
// FUNCIONES DE CLASIFICACIÓN Y EMPAQUE
// ========================================
function cargarDatosParaClasificacion() {
  cargarLotesEnProceso("clas_lote");
  cargarEmpleadosActivos("clas_operario");
  cargarProductosClasificados("clas_producto_origen");
}

function cargarDatosParaEmpaque() {
  cargarLotesEnProceso("emp_lote");
  cargarEmpleadosActivos("emp_operario");
  cargarProductosClasificados("emp_producto_clasificado");
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
  
  if (peso > 0 && configuracionActual.peso_minimo_paca && configuracionActual.peso_maximo_paca) {
    if (peso < configuracionActual.peso_minimo_paca) {
      mostrarAdvertencia("Peso por debajo del mínimo permitido");
    } else if (peso > configuracionActual.peso_maximo_paca) {
      mostrarAdvertencia("Peso por encima del máximo permitido");
    }
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
        
        const form = document.getElementById("formRegistrarClasificacion");
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioClasificacion, "formRegistrarClasificacion");
        }
        
        // Recargar tabla de procesos
        if (tablaProcesos) {
          tablaProcesos.ajax.reload(null, false);
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
        
        const form = document.getElementById("formRegistrarEmpaque");
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioEmpaque, "formRegistrarEmpaque");
        }
        
        // Recargar tabla de procesos
        if (tablaProcesos) {
          tablaProcesos.ajax.reload(null, false);
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
    tbody.innerHTML = '<tr><td colspan="5" class="border border-gray-300 px-3 py-4 text-center text-gray-500">Seleccione un lote para cargar los operarios</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="5" class="border border-gray-300 px-3 py-4 text-center text-gray-500">No hay operarios asignados a este lote</td></tr>';
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      tbody.innerHTML = '<tr><td colspan="5" class="border border-gray-300 px-3 py-4 text-center text-red-500">Error al cargar operarios</td></tr>';
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
                 placeholder="0.00">
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <input type="number" step="0.01" min="0" 
                 name="operarios[${index}][kg_contaminantes]"
                 class="w-full text-center border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                 placeholder="0.00">
        </td>
        <td class="border border-gray-300 px-3 py-2">
          <input type="number" min="0" 
                 name="operarios[${index}][pacas_armadas]"
                 class="w-full text-center border-0 bg-transparent focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                 placeholder="0">
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
          if (tablaNomina) {
            tablaNomina.ajax.reload(null, false);
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
        Swal.fire({
          title: "¡Nómina Calculada!",
          text: result.message,
          icon: "success",
          confirmButtonColor: "#059669"
        }).then(() => {
          if (tablaNomina) {
            tablaNomina.ajax.reload(null, false);
          }
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
// FUNCIONES AUXILIARES
// ========================================
function recargarTablaLotes() {
  if (tablaLotes && tablaLotes.ajax && typeof tablaLotes.ajax.reload === 'function') {
    tablaLotes.ajax.reload(null, false);
  } else {
    window.location.reload();
  }
}

function cargarEmpleadosActivos(selectId = "lote_supervisor") {
  fetch("Produccion/getEmpleadosActivos")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const select = document.getElementById(selectId);
        if (select) {
          let options = '<option value="">Seleccionar empleado...</option>';
          
          result.data.forEach(empleado => {
            options += `<option value="${empleado.idempleado}">${empleado.nombre_completo}</option>`;
          });
          
          select.innerHTML = options;
        }
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
        if (select) {
          let options = '<option value="">Seleccionar lote...</option>';
          
          result.data.forEach(lote => {
            if (lote.estatus_lote === 'EN_PROCESO') {
              options += `<option value="${lote.idlote}">${lote.numero_lote} - ${lote.fecha_jornada_formato}</option>`;
            }
          });
          
          select.innerHTML = options;
        }
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

// CAMBIAR esta función:
function cargarProductosClasificados(selectId) {
    // Implementar carga de productos clasificados
    const select = document.getElementById(selectId);
    select.innerHTML = '<option value="">Cargando productos...</option>';
    
    // REEMPLAZAR con:
    fetch(`Produccion/getProductos?tipo=clasificados`)
        .then((response) => response.json())
        .then((result) => {
            if (result.status && result.data) {
                const select = document.getElementById(selectId);
                let options = '<option value="">Seleccionar material...</option>';
                
                result.data.forEach(producto => {
                    options += `<option value="${producto.idproducto}">${producto.nombre} (${producto.existencia} ${producto.unidad_medida})</option>`;
                });
                
                select.innerHTML = options;
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
// ========================================
// EXPOSICIÓN GLOBAL
// ========================================
window.toggleOperarioAsignado = toggleOperarioAsignado;
window.actualizarTareaOperario = actualizarTareaOperario;
window.removerOperarioAsignado = removerOperarioAsignado;
window.debugAsignaciones = debugAsignaciones;
