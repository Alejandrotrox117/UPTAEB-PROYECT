
import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
  validarCampo,
  validarCampoNumerico,
  validarRango
} from "./validaciones.js";


let tablaLotes, tablaProcesos, tablaNomina;
let configuracionActual = {};
let preciosProceso = [];
let registrosProduccionLote = [];


let empleadosValidos = [];
let productosValidos = [];
let lotesValidos = [];
let supervisoresValidos = [];

function validarElementoSeguro(elemento, arrayValidos, nombreCampo) {
  const valor = elemento.value;
  
  if (!valor || valor === '') return true;
  
  const id = parseInt(valor);
  
  if (isNaN(id) || id <= 0) {
    Swal.fire({
      icon: 'error',
      title: 'Valor Inválido',
      text: `Valor inválido detectado en ${nombreCampo}. Solo se permiten números positivos.`,
      confirmButtonColor: '#dc2626'
    }).then(() => {
      elemento.value = '';
      recargarElementos();
    });
    return false;
  }
  
  if (!arrayValidos.includes(id)) {
    Swal.fire({
      icon: 'error',
      title: 'Elemento No Válido',
      text: `El ${nombreCampo} seleccionado no existe o fue manipulado.`,
      confirmButtonColor: '#dc2626'
    }).then(() => {
      elemento.value = '';
      recargarElementos();
    });
    return false;
  }
  
  return true;
}

// Función para recargar elementos después de operaciones
function recargarElementos() {
  setTimeout(() => {
    if (typeof cargarEmpleadosParaProduccion === 'function') {
      cargarEmpleadosParaProduccion();
    }
    if (typeof cargarProductosParaProduccion === 'function') {
      cargarProductosParaProduccion();
    }
  }, 500);
}

// Función para recargar elementos del modal de lotes
function recargarElementosLote() {
  setTimeout(() => {
    if (typeof cargarEmpleadosParaRegistrosLote === 'function') {
      cargarEmpleadosParaRegistrosLote();
    }
    if (typeof cargarProductosParaRegistrosLote === 'function') {
      cargarProductosParaRegistrosLote();
    }
    if (typeof cargarEmpleadosActivos === 'function') {
      cargarEmpleadosActivos('lote_supervisor');
    }
  }, 500);
}

// Validadores específicos
function validarEmpleado(event) {
  return validarElementoSeguro(event.target, empleadosValidos, 'Empleado');
}

function validarProducto(event) {
  const nombreCampo = event.target.id.includes('producir') ? 'Producto a Producir' : 'Producto Terminado';
  return validarElementoSeguro(event.target, productosValidos, nombreCampo);
}

// Validador específico para productos en modal de lotes
function validarProductoLote(event) {
  const nombreCampo = event.target.id.includes('inicial') ? 'Producto Inicial' : 'Producto Final';
  return validarElementoSeguro(event.target, productosValidos, nombreCampo);
}

// Función para validar formulario completo antes de enviar
function validarFormularioCompleto() {
  const empleado = document.getElementById('prod_empleado');
  const producir = document.getElementById('prod_producto_producir');
  const terminado = document.getElementById('prod_producto_terminado');
  
  if (empleado && empleado.value && !validarElementoSeguro(empleado, empleadosValidos, 'Empleado')) {
    return false;
  }
  
  if (producir && producir.value && !validarElementoSeguro(producir, productosValidos, 'Producto a Producir')) {
    return false;
  }
  
  if (terminado && terminado.value && !validarElementoSeguro(terminado, productosValidos, 'Producto Terminado')) {
    return false;
  }
  
  return true;
}

// Función para validar formulario de lotes
function validarFormularioLoteCompleto() {
  const empleado = document.getElementById('lote_prod_empleado');
  const productoInicial = document.getElementById('lote_prod_producto_inicial');
  const productoFinal = document.getElementById('lote_prod_producto_final');
  const supervisor = document.getElementById('lote_supervisor');
  
  if (empleado && empleado.value && !validarElementoSeguro(empleado, empleadosValidos, 'Empleado de Lote')) {
    return false;
  }
  
  if (productoInicial && productoInicial.value && !validarElementoSeguro(productoInicial, productosValidos, 'Producto Inicial')) {
    return false;
  }
  
  if (productoFinal && productoFinal.value && !validarElementoSeguro(productoFinal, productosValidos, 'Producto Final')) {
    return false;
  }
  
  if (supervisor && supervisor.value && !validarElementoSeguro(supervisor, supervisoresValidos, 'Supervisor')) {
    return false;
  }
  
  return true;
}



window.verificarArrays = function() {
  console.log('Empleados:', empleadosValidos.length, 'Productos:', productosValidos.length);
}; 


const camposFormularioLote = [
  {
    id: "lote_fecha_jornada",
    tipo: "fecha",
    mensajes: {
      vacio: "La fecha de jornada es obligatoria",
      formato: "Formato de fecha inválido"
    }
  },
  {
    id: "lote_volumen_estimado",
    tipoNumerico: "decimal2",
    min: 0.01,
    max: 999999.99,
    mensajes: {
      vacio: "El volumen estimado es obligatorio",
      formato: "Debe ser un número con hasta 2 decimales",
      rango: "El volumen debe estar entre 0.01 y 999,999.99 kg"
    }
  },
  {
    id: "lote_supervisor",
    regex: expresiones.enteroPositivo,
    mensajes: {
      vacio: "Debes seleccionar un supervisor",
      formato: "Selección inválida"
    }
  },
  {
    id: "lote_observaciones",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas (solo letras, números y puntuación básica)"
    },
    opcional: true
  }
];


const camposRegistroProduccionLote = [
  {
    id: "lote_prod_empleado",
    regex: expresiones.enteroPositivo,
    mensajes: {
      vacio: "Debes seleccionar un empleado",
      formato: "Selección inválida"
    }
  },
  {
    id: "lote_prod_fecha",
    tipo: "fecha",
    mensajes: {
      vacio: "La fecha del proceso es obligatoria",
      formato: "Formato de fecha inválido"
    }
  },
  {
    id: "lote_prod_tipo",
    regex: /^(CLASIFICACION|EMPAQUE)$/,
    mensajes: {
      vacio: "Debes seleccionar el tipo de proceso",
      formato: "Tipo de proceso inválido"
    }
  },
  {
    id: "lote_prod_producto_inicial",
    regex: expresiones.enteroPositivo,
    mensajes: {
      vacio: "Debes seleccionar el producto inicial",
      formato: "Selección inválida"
    }
  },
  {
    id: "lote_prod_cantidad_inicial",
    tipoNumerico: "decimal2",
    min: 0.01,
    max: 999999.99,
    mensajes: {
      vacio: "La cantidad inicial es obligatoria",
      formato: "Debe ser un número con hasta 2 decimales",
      rango: "La cantidad debe estar entre 0.01 y 999,999.99 kg"
    }
  },
  {
    id: "lote_prod_producto_final",
    regex: expresiones.enteroPositivo,
    mensajes: {
      vacio: "Debes seleccionar el producto final",
      formato: "Selección inválida"
    }
  },
  {
    id: "lote_prod_cantidad_producida",
    tipoNumerico: "decimal2",
    min: 0.01,
    max: 999999.99,
    mensajes: {
      vacio: "La cantidad producida es obligatoria",
      formato: "Debe ser un número con hasta 2 decimales",
      rango: "La cantidad debe estar entre 0.01 y 999,999.99 kg"
    }
  },
  {
    id: "lote_prod_observaciones",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas"
    },
    opcional: true
  }
];


function validarCantidadProducida() {
  const cantidadInicial = parseFloat(document.getElementById("lote_prod_cantidad_inicial")?.value) || 0;
  const cantidadProducida = parseFloat(document.getElementById("lote_prod_cantidad_producida")?.value) || 0;
  const errorDiv = document.getElementById("error-cantidad-producida");
  
  if (!errorDiv && cantidadProducida > cantidadInicial) {
    const inputProducida = document.getElementById("lote_prod_cantidad_producida");
    if (inputProducida) {
      const newErrorDiv = document.createElement("small");
      newErrorDiv.id = "error-cantidad-producida";
      newErrorDiv.className = "text-yellow-500 text-xs mt-1";
      inputProducida.parentNode.appendChild(newErrorDiv);
    }
  }
  
  const errorElement = document.getElementById("error-cantidad-producida");
  const inputElement = document.getElementById("lote_prod_cantidad_producida");
  
  if (cantidadProducida > cantidadInicial && cantidadInicial > 0) {
    if (errorElement) {
      errorElement.textContent = `La cantidad producida (${cantidadProducida.toFixed(2)} kg) excede la inicial (${cantidadInicial.toFixed(2)} kg)`;
      errorElement.classList.remove("hidden");
    }
    
    if (inputElement) {
      inputElement.classList.add("border-yellow-400");
      inputElement.classList.remove("border-green-300");
    }
    
    return true; 
  } else if (cantidadProducida > 0) {
   
    if (errorElement) {
      errorElement.classList.add("hidden");
    }
    
    if (inputElement) {
      inputElement.classList.remove("border-yellow-400");
      inputElement.classList.add("border-green-300");
    }
    
    return true;
  }
  
  return true;
}


function inicializarValidacionesRegistrosProduccion() {
 
  
  const cantidadInicial = document.getElementById("lote_prod_cantidad_inicial");
  const cantidadProducida = document.getElementById("lote_prod_cantidad_producida");
  
  if (cantidadInicial && cantidadProducida) {
    cantidadProducida.addEventListener("input", validarCantidadProducida);
    cantidadInicial.addEventListener("input", validarCantidadProducida);
  }
}


document.addEventListener("DOMContentLoaded", function () {
  inicializarPestañas();
  inicializarTablas();
  inicializarEventos();
  cargarConfiguracionInicial();
  fetch("Productos/getProductosData").then(r=>r.json()).then((data)=>{
    const sel = document.getElementById('idproducto_precio');
    if (sel && data.status && Array.isArray(data.data)){
      sel.innerHTML = '<option value="">Seleccionar producto...</option>';
      data.data.forEach(p=>{
        const opt = document.createElement('option');
        opt.value = p.idproducto;
        opt.textContent = p.descripcion || p.nombre || (`Producto ${p.idproducto}`);
        sel.appendChild(opt);
      });
    }
  }).catch(()=>{});
}); // Ensure this closing bracket matches the corresponding opening bracket
 document.addEventListener('DOMContentLoaded', function() {
            const botones = document.querySelectorAll('.btnUltimoPesoRomanaClasificacion');
            
            botones.forEach(boton => {
                boton.addEventListener('click', function() {
                    const campo = this.getAttribute('data-campo');
                    manejarPesoRomanaClasificacion(campo);
                });
            });
        });
function inicializarPestañas() { const botonesPestaña = document.querySelectorAll(".tab-button");
  const contenidoPestañas = document.querySelectorAll(".tab-content");

  botonesPestaña.forEach((boton) => {
    boton.addEventListener("click", function () {
      const pestañaId = this.id.replace("tab-", "content-");

      botonesPestaña.forEach((b) => {
        b.classList.remove("active", "border-green-500", "text-green-600");
        b.classList.add("border-transparent", "text-gray-500");
      });
      contenidoPestañas.forEach((c) => c.classList.add("hidden"));

      this.classList.add("active", "border-green-500", "text-green-600");
      this.classList.remove("border-transparent", "text-gray-500");
      document.getElementById(pestañaId).classList.remove("hidden");

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
          mostrarError("No se pudieron cargar los datos de lotes.");
          return [];
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
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
                <i class="fas fa-eye text-sm"></i>
              </button>`;

          if (estatus === "PLANIFICADO") {
            acciones += `
              <button class="editar-lote-btn text-green-500 hover:text-blue-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" title="Editar lote">
                <i class="fas fa-edit text-sm"></i>
              </button>
              <button class="eliminar-lote-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" data-numero="${numeroLote}" title="Eliminar lote">
                <i class="fas fa-trash text-sm"></i>
              </button>`;

            acciones += `
              <button class="iniciar-lote-btn text-orange-600 hover:text-orange-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" title="Iniciar lote">
                <i class="fas fa-play text-sm"></i>
              </button>`;
          }

          if (estatus === "EN_PROCESO") {
            acciones += `
              <button class="cerrar-lote-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                      data-idlote="${idlote}" data-numero="${numeroLote}" title="Cerrar lote">
                <i class="fas fa-stop text-sm"></i>
              </button>`;
          }

          acciones += `</div>`;
          return acciones;
        },
      },
      {
        data: null,
        className: "control",
        orderable: false,
        defaultContent: "",
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
    responsive: {
      details: {
        type: "column",
        target: -1,
        renderer: function (api, rowIdx, columns) {
          var data = $.map(columns, function (col, i) {
            return col.hidden && col.title
              ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}" class="bg-gray-50 hover:bg-gray-100">
                   <td class="font-semibold pr-2 py-1.5 text-sm text-gray-700 w-1/3">${col.title}:</td>
                   <td class="py-1.5 text-sm text-gray-900">${col.data}</td>
                 </tr>`
              : "";
          }).join("");
          return data
            ? $('<table class="w-full table-fixed details-table border-t border-gray-200"/>').append(data)
            : false;
        },
      },
    },
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

  $("#TablaLotes tbody").on("click", ".ver-lote-btn", function () {
    const idlote = $(this).data("idlote");
    verDetallesLote(idlote);
  });

  $("#TablaLotes tbody").on("click", ".editar-lote-btn", function () {
    const idlote = $(this).data("idlote");
    editarLote(idlote);
  });

  $("#TablaLotes tbody").on("click", ".eliminar-lote-btn", function () {
    const idlote = $(this).data("idlote");
    const numeroLote = $(this).data("numero");
    eliminarLote(idlote, numeroLote);
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
    serverSide: false,
    ajax: {
      url: `./Produccion/getRegistrosProduccion`,
      type: "GET",
      dataSrc: function (json) {
        if (json && json.status && Array.isArray(json.data)) {
          return json.data;
        }
        return [];
      },
      error: function (jqXHR, textStatus, errorThrown) {
      }
    },
    columns: [
      {
        data: "numero_lote",
        title: "Lote",
        className: "all whitespace-nowrap py-2 px-2 text-gray-700 text-sm"
      },
      {
        data: "fecha_jornada_formato",
        title: "Fecha",
        className: "min-tablet-p whitespace-nowrap py-2 px-2 text-gray-700 text-sm"
      },
      {
        data: "nombre_empleado",
        title: "Empleado",
        className: "desktop whitespace-nowrap py-2 px-2 text-gray-700 text-sm",
        render: function(data) {
          return data || '<span class="text-gray-400 text-xs">Sin asignar</span>';
        }
      },
      {
        data: null,
        title: "Proceso",
        className: "all py-2 px-2",
        render: function(data, type, row) {
          const tipo = row.tipo_movimiento === 'CLASIFICACION' 
            ? '<i class="fas fa-filter text-green-500 mr-1"></i>Clasif.' 
            : '<i class="fas fa-cube text-purple-600 mr-1"></i>Empaq.';
          
          return `
            <div class="text-xs">
              <div class="font-medium mb-1">${tipo}</div>
              <div class="text-gray-600">${row.producto_terminado_nombre}</div>
            </div>
          `;
        }
      },
      {
        data: "cantidad_producida",
        title: "Producido (kg)",
        className: "all text-right font-semibold text-green-600 py-2 px-2 text-sm whitespace-nowrap",
        render: function(data) {
          return parseFloat(data).toFixed(2);
        }
      },
      {
        data: null,
        title: "Detalles",
        className: "none",
        render: function(data, type, row) {
          return `
            <div class="text-xs space-y-1">
              <div><strong>Prod. Inicial:</strong> ${row.producto_producir_nombre} (${row.producto_producir_codigo})</div>
              <div><strong>Cant. Inicial:</strong> ${parseFloat(row.cantidad_producir).toFixed(2)} kg</div>
              <div><strong>Prod. Final:</strong> ${row.producto_terminado_nombre} (${row.producto_terminado_codigo})</div>
              <div><strong>Salario:</strong> $${parseFloat(row.salario_total).toFixed(2)}</div>
            </div>
          `;
        }
      },
      {
        data: null,
        title: "Acciones",
        className: "all text-center py-1 px-2",
        orderable: false,
        render: function(data, type, row) {
          const estatusRegistro = row.estatus || 'BORRADOR';
          let acciones = '';
          
          if (estatusRegistro === 'BORRADOR') {
            acciones += `
              <button onclick="editarRegistroProduccion(${row.idregistro})" 
                      class="text-green-500 hover:text-blue-700 p-1 transition-colors duration-150" 
                      title="Editar registro">
                <i class="fas fa-edit text-sm"></i>
              </button>
            `;
          }
          
          if (estatusRegistro === 'BORRADOR') {
            acciones += `
              <button onclick="eliminarRegistroProduccion(${row.idregistro}, '${row.nombre_empleado || 'N/A'}', '${row.numero_lote}')" 
                      class="text-red-600 hover:text-red-700 p-1 transition-colors duration-150 ml-1" 
                      title="Eliminar registro">
                <i class="fas fa-trash text-sm"></i>
              </button>
            `;
          }
          
          if (estatusRegistro !== 'BORRADOR') {
            acciones = `<span class="text-xs text-gray-500 italic">No editable (${estatusRegistro})</span>`;
          }
          
          return acciones || '<span class="text-gray-400 text-xs">-</span>';
        }
      },
      {
        data: null,
        className: "control",
        orderable: false,
        defaultContent: "",
      }
    ],
    language: {
      processing: `
        <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]">
          <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
            <span class="text-lg font-medium text-gray-700">Cargando procesos...</span>
          </div>
        </div>`,
      emptyTable: `
        <div class="text-center py-4">
          <i class="fas fa-clipboard-list fa-2x text-gray-400 mb-2"></i>
          <p class="text-gray-600">No hay registros de producción.</p>
        </div>`,
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      lengthMenu: "Mostrar _MENU_ registros",
      search: "_INPUT_",
      searchPlaceholder: "Buscar proceso...",
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
      }
    },
    destroy: true,
    responsive: {
      details: {
        type: "column",
        target: -1,
        renderer: function (api, rowIdx, columns) {
          var data = $.map(columns, function (col, i) {
            return col.hidden && col.title
              ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}" class="bg-gray-50">
                   <td colspan="2" class="py-2 px-3 text-sm text-gray-900">${col.data}</td>
                 </tr>`
              : "";
          }).join("");
          return data
            ? $('<table class="w-full border-t border-gray-200"/>').append(data)
            : false;
        },
      },
    },
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
    order: [[1, "desc"]],
    scrollX: false,
    drawCallback: function (settings) {
      $(settings.nTableWrapper)
        .find('.dataTables_filter input[type="search"]')
        .addClass(
          "py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white"
        )
        .removeClass("form-control-sm");
    },
  });
}

function inicializarTablaNomina() {
  
  if ($.fn.DataTable.isDataTable("#TablaNomina")) {
    $("#TablaNomina").DataTable().destroy();
  }

  if (!document.getElementById("btnRegistrarSalario")) {
    const btn = document.createElement("button");
    btn.id = "btnRegistrarSalario";
    btn.className = "mb-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition";
    btn.innerHTML = '<i class="fas fa-money-check-alt mr-2"></i>Registrar Salario';
    btn.disabled = true;
    btn.style.display = "block";
    const handleRegistrarSalario = function () {
      
      const seleccionados = [];
      $('#TablaNomina tbody input.nomina-checkbox:checked').each(function () {
        const id = $(this).data('id');
        if (id) {
          seleccionados.push(id);
        }
      });

      const totalRegistros = tablaNomina ? tablaNomina.rows().count() : 0;

      if (totalRegistros === 0) {
        Swal.fire({
          title: "Sin Registros",
          text: "No hay registros de producción para procesar. Primero consulta los registros por fecha.",
          icon: "warning",
          confirmButtonColor: "#00c950"
        });
        return;
      }

      let mensaje = "";
      let cantidadAProcesar = 0;
      
      if (seleccionados.length === 0) {
        mensaje = "No seleccionó ningún registro. ¿Desea registrar la solicitud de pago para TODOS los registros en estado BORRADOR?";
        cantidadAProcesar = totalRegistros;
      } else {
        mensaje = `Se crearán ${seleccionados.length} registros de sueldo y se cambiará el estado a 'ENVIADO'.`;
        cantidadAProcesar = seleccionados.length;
      }

      Swal.fire({
        title: "¿Registrar Solicitud de Pago?",
       
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#00c950",
        cancelButtonColor: "#6b7280",
        confirmButtonText: 'Sí, registrar',
        cancelButtonText: 'Cancelar',
        customClass: {
          popup: 'text-left'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          
          Swal.fire({
            title: 'Procesando...',
            html: `Registrando ${cantidadAProcesar} solicitudes de pago...`,
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch(base_url + "Produccion/registrarSolicitudPago", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({ registros: seleccionados }),
          })
            .then((response) => {
              return response.json();
            })
            .then((result) => {
              
              if (result.status) {
                Swal.fire({
                  title: "¡Solicitudes Registradas!",
                  html: `
                    <div class="text-center">
                      <i class="fas fa-check-circle text-green-500 text-5xl mb-3"></i>
                      <p class="text-lg mb-2">${result.message}</p>
                      <div class="bg-green-50 border border-green-200 rounded p-3 mt-3">
                        <p class="text-sm text-green-800">Los registros ahora están en estado <strong>ENVIADO</strong> y pueden ser procesados en el módulo de Pagos.</p>
                      </div>
                    </div>
                  `,
                  icon: "success",
                  confirmButtonColor: "#00c950"
                }).then(() => {
                  if (tablaNomina) {
                    tablaNomina.ajax.reload(null, false);
                  }
                  
                  if (window.tablaSueldo && typeof window.tablaSueldo.ajax?.reload === "function") {
                    window.tablaSueldo.ajax.reload(null, false);
                  }
                  
                });
              } else {
                Swal.fire({
                  title: "Error al Registrar",
                  html: `
                    <p class="mb-3">${result.message || "No se pudo registrar la solicitud de pago."}</p>
                    <div class="bg-red-50 border border-red-200 rounded p-3 text-sm text-left">
                      <p class="font-semibold text-red-800 mb-1">Posibles causas:</p>
                      <ul class="list-disc list-inside text-red-700 space-y-1">
                        <li>Los registros ya fueron enviados anteriormente</li>
                        <li>No hay registros en estado BORRADOR</li>
                        <li>Error en la base de datos</li>
                      </ul>
                    </div>
                  `,
                  icon: "error",
                  confirmButtonColor: "#dc2626"
                });
              }
            })
            .catch((error) => {
              Swal.fire({
                title: "Error de Conexión",
                html: `
                  <p class="mb-3">No se pudo conectar con el servidor.</p>
                  <div class="bg-orange-50 border border-orange-200 rounded p-3 text-sm">
                    <p class="text-orange-800"><strong>Error técnico:</strong> ${error.message}</p>
                  </div>
                `,
                icon: "error",
                confirmButtonColor: "#dc2626"
              });
            });
        } else {
        }
      });
    };
    const tabla = document.getElementById("TablaNomina");
    if (tabla) {
      tabla.parentNode.insertBefore(btn, tabla);
    }
    btn.addEventListener("click", handleRegistrarSalario);
  }

  tablaNomina = $("#TablaNomina").DataTable({
    processing: true,
    ajax: {
      url: "./Produccion/getRegistrosProduccion",
      type: "GET",
      dataSrc: function (json) {
        if (json && json.status && Array.isArray(json.data)) {
          
          setTimeout(() => actualizarContadorEstados(json.data), 100);
          
          return json.data;
        }
        return [];
      },
      error: function (jqXHR, textStatus, errorThrown) {
      }
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center all py-2 px-2",
        render: function (data, type, row) {
          const estatus = row.estatus || 'BORRADOR';
          if (estatus === 'BORRADOR') {
            return `<input type="checkbox" class="nomina-checkbox w-4 h-4" data-id="${row.idregistro || ''}">`;
          } else {
            return `<input type="checkbox" disabled class="opacity-50 cursor-not-allowed w-4 h-4" title="Solo registros en BORRADOR pueden ser seleccionados">`;
          }
        }
      },
      { 
        data: "fecha_jornada_formato", 
        title: "Fecha", 
        className: "all whitespace-nowrap py-2 px-2 text-gray-700 text-xs" 
      },
      { 
        data: "nombre_empleado", 
        title: "Empleado", 
        className: "all whitespace-nowrap py-2 px-2 text-gray-700 text-xs",
        render: function(data) {
          return data || '<span class="text-gray-400 text-xs">Sin asignar</span>';
        }
      },
      { 
        data: "numero_lote", 
        title: "Lote", 
        className: "none whitespace-nowrap py-2 px-2 text-gray-700 text-xs" 
      },
      { 
        data: "tipo_movimiento", 
        title: "Tipo", 
        className: "none py-2 px-2",
        render: function(data) {
          if (data === 'CLASIFICACION') {
            return '<span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">Clasificación</span>';
          } else {
            return '<span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800">Empaque</span>';
          }
        }
      },
      { 
        data: "cantidad_producida", 
        title: "Cant. Producida (kg)", 
        className: "none text-right py-2 px-2 text-gray-700 text-xs",
        render: function(data) {
          return parseFloat(data).toFixed(2);
        }
      },
      { 
        data: "salario_base_dia", 
        title: "Salario Base", 
        className: "none text-right py-2 px-2",
        render: function(data) {
          return `$${parseFloat(data).toFixed(2)}`;
        }
      },
      { 
        data: "pago_clasificacion_trabajo", 
        title: "Pago Trabajo", 
        className: "none text-right py-2 px-2",
        render: function(data) {
          return `$${parseFloat(data).toFixed(2)}`;
        }
      },
      { 
        data: "salario_total", 
        title: "Total", 
        className: "all text-right font-bold text-green-700 py-2 px-2 text-xs whitespace-nowrap",
        render: function(data) {
          return `$${parseFloat(data).toFixed(2)}`;
        }
      },
      { 
        data: "estatus", 
        title: "Estado", 
        className: "all text-center py-2 px-2",
        render: function(data) {
          const estatus = data || 'BORRADOR';
          const badges = {
            'BORRADOR': '<span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Borrador</span>',
            'ENVIADO': '<span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Enviado</span>',
            'PAGADO': '<span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>',
            'CANCELADO': '<span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Cancelado</span>'
          };
          return badges[estatus] || badges['BORRADOR'];
        }
      },
      { 
        data: null, 
        title: "Acciones", 
        orderable: false,
        searchable: false,
        className: "all text-center py-1 px-1",
        render: function(data, type, row) {
          const estatus = row.estatus || 'BORRADOR';
          const idregistro = row.idregistro || '';
          const nombreEmpleado = row.nombre_empleado || 'Sin asignar';
          const salarioTotal = parseFloat(row.salario_total || 0).toFixed(2);
          
          let botones = '<div class="inline-flex items-center space-x-1">';
          
          botones += `
            <button class="btn-ver-detalle-nomina text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                    data-id="${idregistro}"
                    title="Ver Detalles">
              <i class="fas fa-eye text-sm"></i>
            </button>`;
          
          if (estatus === 'ENVIADO') {
            botones += `
              <button class="btn-marcar-pagado text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                      data-id="${idregistro}" 
                      data-empleado="${nombreEmpleado}" 
                      data-salario="${salarioTotal}"
                      title="Marcar como Pagado">
                <i class="fas fa-check-circle text-sm"></i>
              </button>`;
          }
          
          if (estatus === 'BORRADOR' || estatus === 'ENVIADO') {
            botones += `
              <button class="btn-cancelar-nomina text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                      data-id="${idregistro}" 
                      data-empleado="${nombreEmpleado}"
                      title="Cancelar Registro">
                <i class="fas fa-times-circle text-sm"></i>
              </button>`;
          }
          
          botones += '</div>';
          return botones;
        }
      },
      {
        data: null,
        className: "control",
        orderable: false,
        defaultContent: "",
      }
    ],
    language: {
      processing: `
        <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]">
          <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
            <span class="text-lg font-medium text-gray-700">Cargando nómina...</span>
          </div>
        </div>`,
      emptyTable: `
        <div class="text-center py-4">
          <i class="fas fa-file-invoice-dollar fa-2x text-gray-400 mb-2"></i>
          <p class="text-gray-600">No hay registros de nómina.</p>
        </div>`,
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      lengthMenu: "Mostrar _MENU_ registros",
      search: "_INPUT_",
      searchPlaceholder: "Buscar...",
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
    responsive: {
      details: {
        type: "column",
        target: -1,
        renderer: function (api, rowIdx, columns) {
          var data = $.map(columns, function (col, i) {
            return col.hidden && col.title
              ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}" class="bg-gray-50">
                   <td class="font-semibold pr-2 py-1.5 text-xs text-gray-700">${col.title}:</td>
                   <td class="py-1.5 text-xs text-gray-900">${col.data}</td>
                 </tr>`
              : "";
          }).join("");
          return data
            ? $('<table class="w-full border-t border-gray-200"/>').append(data)
            : false;
        },
      },
    },
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
    order: [[1, "desc"]],
    scrollX: false,
    drawCallback: function (settings) {
      $(settings.nTableWrapper)
        .find('.dataTables_filter input[type="search"]')
        .addClass(
          "py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white"
        )
        .removeClass("form-control-sm");
    },
  });


  $('#TablaNomina tbody').on('change', 'input.nomina-checkbox', function () {
    const seleccionados = $('#TablaNomina tbody input.nomina-checkbox:checked').length;
    const btn = document.getElementById("btnRegistrarSalario");
    if (btn) {
      btn.disabled = seleccionados === 0;
      btn.classList.toggle("opacity-50", seleccionados === 0);
      btn.classList.toggle("cursor-not-allowed", seleccionados === 0);
    }
  });

  $('#TablaNomina tbody').on('click', '.btn-marcar-pagado', function () {
    const idregistro = $(this).data('id');
    const empleado = $(this).data('empleado');
    const salario = $(this).data('salario');
    marcarComoPagado(idregistro, empleado, salario);
  });

  $('#TablaNomina tbody').on('click', '.btn-ver-detalle-nomina', function () {
    const idregistro = $(this).data('id');
    verDetalleRegistroNomina(idregistro);
  });

  $('#TablaNomina tbody').on('click', '.btn-cancelar-nomina', function () {
    const idregistro = $(this).data('id');
    const empleado = $(this).data('empleado');
    cancelarRegistroNomina(idregistro, empleado);
  });
  
}

function inicializarEventos() {
  inicializarEventosLotes();
  inicializarEventosProcesos();
  inicializarEventosNomina();
  inicializarEventosConfiguracion();
}
function inicializarEventosLotes() {
  const btnAbrirModalLote = document.getElementById("btnAbrirModalRegistrarLote");
  const btnCerrarModalLote = document.getElementById("btnCerrarModalRegistrarLote");
  const btnCancelarModalLote = document.getElementById("btnCancelarModalRegistrarLote");
  const formLote = document.getElementById("formRegistrarLote");

  if (btnAbrirModalLote) {
    btnAbrirModalLote.addEventListener("click", function () {
      abrirModal("modalRegistrarLote");
      if (formLote) formLote.reset();
      
      registrosProduccionLote = [];
      actualizarTablaRegistrosProduccionLote();
      
      cargarEmpleadosActivos();
      cargarEmpleadosParaRegistrosLote();
      cargarProductosParaRegistrosLote();
      
      document.getElementById("lote_fecha_jornada").value = new Date().toISOString().split('T')[0];
      document.getElementById("lote_prod_fecha").value = new Date().toISOString().split('T')[0];
      
      inicializarValidaciones(camposFormularioLote, "formRegistrarLote");
      
      inicializarValidacionesRegistrosProduccion();
      
      cargarConfiguracionProduccion();
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

  const btnGuardarLote = document.getElementById("btnGuardarLote");
  if (btnGuardarLote) {
    btnGuardarLote.addEventListener("click", function (e) {
      e.preventDefault();
      registrarLote();
    });
  }

  const volumenInput = document.getElementById("lote_volumen_estimado");
  if (volumenInput) {
    volumenInput.addEventListener("input", function () {
      calcularOperariosRequeridos();
    });
  }

  const btnAgregarRegistroProd = document.getElementById("btnAgregarRegistroProduccionLote");
  if (btnAgregarRegistroProd) {
    btnAgregarRegistroProd.addEventListener("click", agregarRegistroProduccionLote);
  }

  const cantidadProducidaInput = document.getElementById("lote_prod_cantidad_producida");
  const tipoMovimientoSelect = document.getElementById("lote_prod_tipo");
  
  if (cantidadProducidaInput) {
    cantidadProducidaInput.addEventListener("input", calcularSalariosRegistroLote);
  }
  
  if (tipoMovimientoSelect) {
    tipoMovimientoSelect.addEventListener("change", calcularSalariosRegistroLote);
  }

  const btnCerrarModalVerLote = document.getElementById("btnCerrarModalVerLote");
  const btnCerrarModalVerLote2 = document.getElementById("btnCerrarModalVerLote2");

  if (btnCerrarModalVerLote) {
    btnCerrarModalVerLote.addEventListener("click", function () {
      cerrarModal("modalVerLote");
    });
  }

  if (btnCerrarModalVerLote2) {
    btnCerrarModalVerLote2.addEventListener("click", function () {
      cerrarModal("modalVerLote");
    });
  }
}

function inicializarEventosProcesos() {
  const btnAbrirModalRegistrarProduccion = document.getElementById("btnAbrirModalRegistrarProduccion");
  const btnCerrarModalRegistrarProduccion = document.getElementById("btnCerrarModalRegistrarProduccion");
  const btnCancelarRegistrarProduccion = document.getElementById("btnCancelarRegistrarProduccion");
  const formRegistrarProduccion = document.getElementById("formRegistrarProduccion");

  if (btnAbrirModalRegistrarProduccion) {
    btnAbrirModalRegistrarProduccion.addEventListener("click", function () {
      abrirModalRegistrarProduccion();
    });
  }

  if (btnCerrarModalRegistrarProduccion) {
    btnCerrarModalRegistrarProduccion.addEventListener("click", function () {
      cerrarModal("modalRegistrarProduccion");
    });
  }

  if (btnCancelarRegistrarProduccion) {
    btnCancelarRegistrarProduccion.addEventListener("click", function () {
      cerrarModal("modalRegistrarProduccion");
    });
  }

  if (formRegistrarProduccion) {
    formRegistrarProduccion.addEventListener("submit", function (e) {
      e.preventDefault();
      guardarRegistroProduccion();
    });
  }

  const prod_cantidad_producida = document.getElementById("prod_cantidad_producida");
  const prod_tipo_movimiento = document.getElementById("prod_tipo_movimiento");

  if (prod_cantidad_producida) {
    prod_cantidad_producida.addEventListener("input", calcularSalariosAutomaticamente);
  }

  if (prod_tipo_movimiento) {
    prod_tipo_movimiento.addEventListener("change", calcularSalariosAutomaticamente);
  }
}

function inicializarEventosNomina() {
  
  const btnCalcularNomina = document.getElementById("btnCalcularNomina");

  if (btnCalcularNomina) {
    btnCalcularNomina.addEventListener("click", function () {
      abrirModalConsultarPorFecha();
    });
  } else {
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

  const btnAgregarSalario = document.getElementById("btnAgregarSalario");
  if (btnAgregarSalario) {
    btnAgregarSalario.addEventListener("click", function(e) {
      e.preventDefault();
      e.stopPropagation();
      crearPrecioProceso();
    });
  }
}

function calcularOperariosRequeridos() {
  const volumen = parseFloat(document.getElementById("lote_volumen_estimado").value || 0);
  const infoCalculada = document.getElementById("infoCalculada");
  
  if (volumen > 0 && configuracionActual.productividad_clasificacion) {
    const operariosRequeridos = Math.ceil(volumen / configuracionActual.productividad_clasificacion);
    const capacidadMaxima = configuracionActual.capacidad_maxima_planta || 50;
    
    document.getElementById("operariosCalculados").textContent = operariosRequeridos;
    document.getElementById("capacidadMaxima").textContent = capacidadMaxima;
    
    infoCalculada.classList.remove("hidden");
    
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



async function cargarEmpleadosParaRegistrosLote() {
  try {
    const response = await fetch("Produccion/getEmpleadosActivos");
    const data = await response.json();
    
    const select = document.getElementById("lote_prod_empleado");
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar empleado...</option>';
    
    if (data.status && Array.isArray(data.data)) {
      // Cargar IDs válidos para validación
      data.data.forEach(empleado => {
        empleadosValidos.push(parseInt(empleado.idempleado));
        
        const option = document.createElement("option");
        option.value = empleado.idempleado;
        option.textContent = empleado.nombre_completo;
        option.dataset.nombre = empleado.nombre_completo;
        select.appendChild(option);
      });
      
      // Agregar validador de seguridad
      if (!select.hasAttribute('data-validador-agregado')) {
        select.addEventListener('change', validarEmpleado);
        select.setAttribute('data-validador-agregado', 'true');
      }
    }
  } catch (error) {
  }
}


async function cargarProductosParaRegistrosLote() {
  try {
    const response = await fetch("Productos/getProductosData");
    const data = await response.json();
    
    const selectInicial = document.getElementById("lote_prod_producto_inicial");
    const selectFinal = document.getElementById("lote_prod_producto_final");
    
    if (!selectInicial || !selectFinal) return;
    
    selectInicial.innerHTML = '<option value="">Seleccionar producto...</option>';
    selectFinal.innerHTML = '<option value="">Seleccionar producto...</option>';
    
    if (data.status && Array.isArray(data.data)) {
      data.data.forEach(producto => {
        // Cargar IDs válidos para validación
        productosValidos.push(parseInt(producto.idproducto));
        
        const option1 = document.createElement("option");
        option1.value = producto.idproducto;
        option1.textContent = producto.nombre || producto.descripcion;
        option1.dataset.nombre = producto.nombre || producto.descripcion;
        option1.dataset.codigo = producto.codigo || '';
        selectInicial.appendChild(option1);
        
        const option2 = document.createElement("option");
        option2.value = producto.idproducto;
        option2.textContent = producto.nombre || producto.descripcion;
        option2.dataset.nombre = producto.nombre || producto.descripcion;
        option2.dataset.codigo = producto.codigo || '';
        selectFinal.appendChild(option2);
      });
      
      // Agregar validadores de seguridad
      if (!selectInicial.hasAttribute('data-validador-agregado')) {
        selectInicial.addEventListener('change', validarProductoLote);
        selectInicial.setAttribute('data-validador-agregado', 'true');
      }
      
      if (!selectFinal.hasAttribute('data-validador-agregado')) {
        selectFinal.addEventListener('change', validarProductoLote);
        selectFinal.setAttribute('data-validador-agregado', 'true');
      }
    }
  } catch (error) {
  }
}


function calcularSalariosRegistroLote() {
  const cantidadProducida = parseFloat(document.getElementById("lote_prod_cantidad_producida").value) || 0;
  const tipoMovimiento = document.getElementById("lote_prod_tipo").value;
  
  if (cantidadProducida <= 0 || !tipoMovimiento || !configuracionActual) {
    limpiarSalariosRegistroLote();
    return;
  }
  
  const salarioBase = parseFloat(configuracionActual.salario_base || 30);
  let precioUnit = 0;
  
  const productoBaseId = tipoMovimiento === 'CLASIFICACION'
    ? parseInt(document.getElementById('lote_prod_producto_inicial')?.value || 0)
    : parseInt(document.getElementById('lote_prod_producto_final')?.value || 0);
  
  if (productoBaseId && preciosProceso.length) {
    const match = preciosProceso.find(p => 
      String(p.tipo_proceso) === String(tipoMovimiento) && 
      parseInt(p.idproducto) === productoBaseId && 
      p.estatus === 'activo'
    );
    if (match) precioUnit = parseFloat(match.salario_unitario || 0);
  }
  
  if (precioUnit <= 0) {
    precioUnit = tipoMovimiento === 'CLASIFICACION'
      ? parseFloat(configuracionActual.beta_clasificacion || 0.25)
      : parseFloat(configuracionActual.gamma_empaque || 5.00);
  }
  
  const pagoTrabajo = precioUnit * cantidadProducida;
  const salarioTotal = salarioBase + pagoTrabajo;
  
  document.getElementById("lote_prod_salario_base").value = `$${salarioBase.toFixed(2)}`;
  document.getElementById("lote_prod_pago_trabajo").value = `$${pagoTrabajo.toFixed(2)}`;
  document.getElementById("lote_prod_salario_total").value = `$${salarioTotal.toFixed(2)}`;
}

function limpiarSalariosRegistroLote() {
  document.getElementById("lote_prod_salario_base").value = '$0.00';
  document.getElementById("lote_prod_pago_trabajo").value = '$0.00';
  document.getElementById("lote_prod_salario_total").value = '$0.00';
}


async function agregarRegistroProduccionLote() {
  
  const idempleado = document.getElementById("lote_prod_empleado").value;
  const fecha = document.getElementById("lote_prod_fecha").value;
  const tipo = document.getElementById("lote_prod_tipo").value;
  const idproductoInicial = document.getElementById("lote_prod_producto_inicial").value;
  const cantidadInicial = parseFloat(document.getElementById("lote_prod_cantidad_inicial").value);
  const idproductoFinal = document.getElementById("lote_prod_producto_final").value;
  const cantidadProducida = parseFloat(document.getElementById("lote_prod_cantidad_producida").value);
  const observaciones = document.getElementById("lote_prod_observaciones").value;
  
  if (!idempleado || !fecha || !tipo || !idproductoInicial || !idproductoFinal) {
    mostrarAdvertencia("Por favor completa todos los campos obligatorios");
    return;
  }
  
  if (isNaN(cantidadInicial) || cantidadInicial <= 0 || isNaN(cantidadProducida) || cantidadProducida <= 0) {
    mostrarAdvertencia("Las cantidades deben ser mayores a cero");
    return;
  }
  
  // VALIDAR STOCK DISPONIBLE ANTES DE AGREGAR
  try {
    const response = await fetch(`Productos/getProductosData`);
    const data = await response.json();
    
    if (data.status && Array.isArray(data.data)) {
      const productoInicial = data.data.find(p => p.idproducto == idproductoInicial);
      
      if (!productoInicial) {
        Swal.fire({
          icon: 'error',
          title: 'Producto no encontrado',
          text: 'No se pudo validar el stock del producto inicial.',
          confirmButtonColor: '#dc2626'
        });
        return;
      }
      
      const stockDisponible = parseFloat(productoInicial.existencia || 0);
      
      const consumoAcumulado = registrosProduccionLote
        .filter(r => r.idproducto_producir == idproductoInicial)
        .reduce((sum, r) => sum + parseFloat(r.cantidad_producir), 0);
      
      const stockDisponibleReal = stockDisponible - consumoAcumulado;
      
      if (stockDisponibleReal < cantidadInicial) {
        Swal.fire({
          icon: 'error',
          title: 'Stock Insuficiente',
          html: `
            <div class="text-left space-y-3">
              <p class="text-gray-700">No hay suficiente stock del producto inicial para crear este registro.</p>
              <div class="bg-red-50 border border-red-200 rounded p-3">
                <p class="text-sm text-red-800 mb-2"><strong>Producto:</strong> ${productoInicial.nombre || productoInicial.descripcion}</p>
                <p class="text-sm text-red-800"><strong>Stock disponible:</strong> ${stockDisponible.toFixed(2)} kg</p>
                <p class="text-sm text-red-800"><strong>Ya consumido en registros:</strong> ${consumoAcumulado.toFixed(2)} kg</p>
                <p class="text-sm text-red-800"><strong>Stock real disponible:</strong> ${stockDisponibleReal.toFixed(2)} kg</p>
                <p class="text-sm font-bold text-red-900 mt-2"><strong>Requerido:</strong> ${cantidadInicial.toFixed(2)} kg</p>
              </div>
              <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                <p class="text-xs text-yellow-800">
                  <i class="fas fa-exclamation-triangle mr-1"></i>
                  <strong>No se puede agregar el registro.</strong> Por favor, reduce la cantidad o verifica el inventario.
                </p>
              </div>
            </div>
          `,
          confirmButtonColor: '#dc2626',
          confirmButtonText: 'Entendido'
        });
        return;
      }
      
      // Si hay advertencia pero no bloqueo total, mostrarla
      if (stockDisponibleReal < cantidadInicial * 1.1) { // Advertencia si está cerca del límite (menos del 10% de margen)
        const continuar = await Swal.fire({
          icon: 'warning',
          title: 'Advertencia de Stock',
          html: `
            <div class="text-left space-y-3">
              <p class="text-gray-700">El stock disponible está cerca del límite.</p>
              <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                <p class="text-sm text-yellow-800"><strong>Producto:</strong> ${productoInicial.nombre || productoInicial.descripcion}</p>
                <p class="text-sm text-yellow-800"><strong>Stock real disponible:</strong> ${stockDisponibleReal.toFixed(2)} kg</p>
                <p class="text-sm text-yellow-800"><strong>Requerido:</strong> ${cantidadInicial.toFixed(2)} kg</p>
                <p class="text-sm text-yellow-800"><strong>Margen restante:</strong> ${(stockDisponibleReal - cantidadInicial).toFixed(2)} kg</p>
              </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Continuar de todas formas',
          cancelButtonText: 'Cancelar'
        });
        
        if (!continuar.isConfirmed) {
          return;
        }
      }
    }
  } catch (error) {
    console.error('Error al validar stock:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error de validación',
      text: 'No se pudo validar el stock disponible. Por favor, intenta de nuevo.',
      confirmButtonColor: '#dc2626'
    });
    return;
  }
  
  const empleadoSelect = document.getElementById("lote_prod_empleado");
  const nombreEmpleado = empleadoSelect.options[empleadoSelect.selectedIndex].dataset.nombre;
  
  const productoInicialSelect = document.getElementById("lote_prod_producto_inicial");
  const nombreProductoInicial = productoInicialSelect.options[productoInicialSelect.selectedIndex].dataset.nombre;
  
  const productoFinalSelect = document.getElementById("lote_prod_producto_final");
  const nombreProductoFinal = productoFinalSelect.options[productoFinalSelect.selectedIndex].dataset.nombre;
  
  const salarioBase = parseFloat(configuracionActual.salario_base || 30);
  let precioUnit = 0;
  const productoBaseId = tipo === 'CLASIFICACION'
    ? parseInt(document.getElementById('lote_prod_producto_inicial')?.value || 0)
    : parseInt(document.getElementById('lote_prod_producto_final')?.value || 0);
  if (productoBaseId && preciosProceso.length) {
    const m = preciosProceso.find(p => String(p.tipo_proceso) === String(tipo) && parseInt(p.idproducto) === productoBaseId && p.estatus === 'activo');
    if (m) precioUnit = parseFloat(m.precio_unitario || 0);
  }
  if (precioUnit <= 0) {
    precioUnit = tipo === 'CLASIFICACION' 
      ? parseFloat(configuracionActual.beta_clasificacion || 0.25)
      : parseFloat(configuracionActual.gamma_empaque || 5.00);
  }
  const pagoTrabajo = precioUnit * cantidadProducida;
  
  const salarioTotal = salarioBase + pagoTrabajo;
  
  const registro = {
    idempleado,
    nombreEmpleado,
    fecha_jornada: fecha,
    fecha_jornada_formato: new Date(fecha).toLocaleDateString('es-ES'),
    idproducto_producir: idproductoInicial,
    nombreProductoInicial,
    cantidad_producir: cantidadInicial,
    idproducto_terminado: idproductoFinal,
    nombreProductoFinal,
    cantidad_producida: cantidadProducida,
    tipo_movimiento: tipo,
    salario_base_dia: salarioBase,
    pago_clasificacion_trabajo: pagoTrabajo,
    salario_total: salarioTotal,
    observaciones: observaciones || ''
  };
  
  registrosProduccionLote.push(registro);
  
  actualizarTablaRegistrosProduccionLote();
  
  limpiarFormularioRegistroLote();
  
  mostrarExito("Registro agregado correctamente");
}


function actualizarTablaRegistrosProduccionLote() {
  const tbody = document.getElementById("cuerpoTablaRegistrosProduccionLote");
  const mensaje = document.getElementById("noRegistrosProdMensaje");
  
  if (!tbody) return;
  
  if (registrosProduccionLote.length === 0) {
    tbody.innerHTML = '';
    if (mensaje) mensaje.style.display = 'block';
    return;
  }
  
  if (mensaje) mensaje.style.display = 'none';
  
  tbody.innerHTML = registrosProduccionLote.map((reg, index) => `
    <tr class="hover:bg-gray-50">
      <td class="px-2 py-2 text-xs">${reg.nombreEmpleado}</td>
      <td class="px-2 py-2 text-xs">${reg.fecha_jornada_formato}</td>
      <td class="px-2 py-2 text-xs">
        ${reg.tipo_movimiento === 'CLASIFICACION' 
          ? '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">🔵 Clasificación</span>'
          : '<span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">🟣 Empaque</span>'
        }
      </td>
      <td class="px-2 py-2 text-xs">${reg.nombreProductoInicial}</td>
      <td class="px-2 py-2 text-xs text-right font-semibold">${reg.cantidad_producir.toFixed(2)} kg</td>
      <td class="px-2 py-2 text-xs">${reg.nombreProductoFinal}</td>
      <td class="px-2 py-2 text-xs text-right font-semibold text-green-600">${reg.cantidad_producida.toFixed(2)} kg</td>
      <td class="px-2 py-2 text-xs text-right font-bold text-green-700">$${reg.salario_total.toFixed(2)}</td>
      <td class="px-2 py-2 text-center">
        <button type="button" onclick="eliminarRegistroProduccionLote(${index})" class="text-red-600 hover:text-red-800 transition">
          <i class="fas fa-trash-alt"></i>
        </button>
      </td>
    </tr>
  `).join('');
}


window.eliminarRegistroProduccionLote = function(index) {
  registrosProduccionLote.splice(index, 1);
  actualizarTablaRegistrosProduccionLote();
  mostrarExito("Registro eliminado");
}


function limpiarFormularioRegistroLote() {
  document.getElementById("lote_prod_empleado").value = '';
  document.getElementById("lote_prod_tipo").value = '';
  document.getElementById("lote_prod_producto_inicial").value = '';
  document.getElementById("lote_prod_cantidad_inicial").value = '';
  document.getElementById("lote_prod_producto_final").value = '';
  document.getElementById("lote_prod_cantidad_producida").value = '';
  document.getElementById("lote_prod_observaciones").value = '';
  limpiarSalariosRegistroLote();
}


async function registrarLote() {
  
  // Validar seguridad antes de procesar
  if (!validarFormularioLoteCompleto()) {
    return;
  }
  
  // VALIDAR QUE HAYA REGISTROS
  if (!registrosProduccionLote || registrosProduccionLote.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Sin registros",
      text: "Debes agregar al menos un registro de producción antes de crear el lote",
      confirmButtonColor: "#f59e0b"
    });
    return;
  }
  
  // VALIDAR STOCK DISPONIBLE PARA TODOS LOS REGISTROS
  try {
    const response = await fetch(`Productos/getProductosData`);
    const data = await response.json();
    
    if (data.status && Array.isArray(data.data)) {
      const productosInsuficientes = [];
      const consumoPorProducto = {};
      
      // Calcular consumo total por producto
      registrosProduccionLote.forEach(reg => {
        const idProducto = reg.idproducto_producir;
        const cantidad = parseFloat(reg.cantidad_producir || 0);
        
        if (!consumoPorProducto[idProducto]) {
          consumoPorProducto[idProducto] = 0;
        }
        consumoPorProducto[idProducto] += cantidad;
      });
      
      // Validar cada producto
      for (const [idProducto, cantidadRequerida] of Object.entries(consumoPorProducto)) {
        const producto = data.data.find(p => p.idproducto == idProducto);
        
        if (!producto) {
          productosInsuficientes.push({
            nombre: `Producto ID ${idProducto}`,
            disponible: 0,
            requerido: cantidadRequerida
          });
          continue;
        }
        
        const stockDisponible = parseFloat(producto.existencia || 0);
        
        if (stockDisponible < cantidadRequerida) {
          productosInsuficientes.push({
            nombre: producto.nombre || producto.descripcion,
            disponible: stockDisponible,
            requerido: cantidadRequerida,
            faltante: cantidadRequerida - stockDisponible
          });
        }
      }
      
      // Si hay productos con stock insuficiente, mostrar error y bloquear
      if (productosInsuficientes.length > 0) {
        const listaProductos = productosInsuficientes.map(p => `
          <div class="bg-red-50 border border-red-200 rounded p-2 mb-2">
            <p class="text-sm text-red-800"><strong>${p.nombre}</strong></p>
            <p class="text-xs text-red-700">Disponible: ${p.disponible.toFixed(2)} kg</p>
            <p class="text-xs text-red-700">Requerido: ${p.requerido.toFixed(2)} kg</p>
            <p class="text-xs font-bold text-red-900">Faltante: ${p.faltante.toFixed(2)} kg</p>
          </div>
        `).join('');
        
        Swal.fire({
          icon: 'error',
          title: 'No se puede crear el lote',
          html: `
            <div class="text-left space-y-3">
              <p class="text-gray-700 mb-3">Los siguientes productos tienen stock insuficiente:</p>
              ${listaProductos}
              <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                <p class="text-xs text-yellow-800">
                  <i class="fas fa-exclamation-triangle mr-1"></i>
                  <strong>No se puede crear el lote.</strong> Elimina los registros problemáticos o ajusta las cantidades.
                </p>
              </div>
            </div>
          `,
          confirmButtonColor: '#dc2626',
          confirmButtonText: 'Entendido',
          width: '600px'
        });
        return;
      }
    }
  } catch (error) {
    console.error('Error al validar stock del lote:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error de validación',
      text: 'No se pudo validar el stock disponible. Por favor, intenta de nuevo.',
      confirmButtonColor: '#dc2626'
    });
    return;
  }
  
  const btnGuardarLote = document.getElementById("btnGuardarLote");

  if (btnGuardarLote) {
    btnGuardarLote.disabled = true;
    btnGuardarLote.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Creando...`;
  }

  try {
    const formLote = document.getElementById("formRegistrarLote");
    
    const formData = {
      fecha_jornada: document.getElementById("lote_fecha_jornada").value,
      volumen_estimado: document.getElementById("lote_volumen_estimado").value,
      idsupervisor: document.getElementById("lote_supervisor").value,
      observaciones: document.getElementById("lote_observaciones").value || ''
    };


    if (!formData.fecha_jornada || !formData.volumen_estimado || !formData.idsupervisor) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Por favor completa todos los campos obligatorios (Fecha, Volumen y Supervisor)",
        confirmButtonColor: "#dc2626"
      });
      return;
    }

    Swal.fire({
      title: 'Creando lote...',
      text: 'Por favor espera',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const responseLote = await fetch(base_url + "Produccion/createLote", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(formData)
    });

    const resultLote = await responseLote.json();

    if (!resultLote.status) {
      throw new Error(resultLote.msg || resultLote.message || "Error al crear el lote");
    }

    const idlote = resultLote.idlote || resultLote.lote_id;

    if (registrosProduccionLote.length > 0) {
      
      Swal.update({
        title: 'Guardando registros de producción...',
        text: `Guardando ${registrosProduccionLote.length} registros...`
      });

      let registrosExitosos = 0;
      let registrosConError = 0;

      for (let i = 0; i < registrosProduccionLote.length; i++) {
        const registro = registrosProduccionLote[i];
        
        try {
          const formDataRegistro = new FormData();
          formDataRegistro.append("idlote", idlote);
          formDataRegistro.append("idempleado", registro.idempleado);
          formDataRegistro.append("fecha", registro.fecha_jornada);
          formDataRegistro.append("tipo_proceso", registro.tipo_movimiento);
          formDataRegistro.append("idproducto_inicial", registro.idproducto_producir);
          formDataRegistro.append("idproducto_final", registro.idproducto_terminado);
          formDataRegistro.append("cantidad_producida", registro.cantidad_producida);
          formDataRegistro.append("cantidad_rechazada", 0);
          formDataRegistro.append("observaciones", registro.observaciones || "");
          formDataRegistro.append("observaciones", registro.observaciones || "");

          const responseRegistro = await fetch(base_url + "Produccion/crearRegistroProduccion", {
            method: "POST",
            body: formDataRegistro
          });

          const resultRegistro = await responseRegistro.json();

          if (resultRegistro.status) {
            registrosExitosos++;
          } else {
            registrosConError++;
          }
        } catch (error) {
          registrosConError++;
        }
      }


      Swal.fire({
        icon: registrosConError === 0 ? "success" : "warning",
        title: "¡Lote creado exitosamente!",
        html: `
          <p><strong>Lote:</strong> ${resultLote.numero_lote || idlote}</p>
          <p><strong>Registros de producción:</strong> ${registrosExitosos} de ${registrosProduccionLote.length} guardados</p>
          ${registrosConError > 0 ? `<p class="text-orange-600"><strong>Advertencia:</strong> ${registrosConError} registros con error</p>` : ''}
        `,
        confirmButtonColor: "#00c950"
      }).then(() => {
        cerrarModal("modalRegistrarLote");
        
        // Recargar elementos para limpiar manipulaciones
        recargarElementosLote();
        
        if (typeof tablaLotes !== "undefined" && tablaLotes.ajax) {
          tablaLotes.ajax.reload();
        }
        if (typeof tablaRegistrosProcesos !== "undefined" && tablaRegistrosProcesos.ajax) {
          tablaRegistrosProcesos.ajax.reload();
        }
        
        formLote.reset();
        registrosProduccionLote = [];
        actualizarTablaRegistrosProduccionLote();
        limpiarFormularioRegistroLote();
      });

    } else {
      Swal.fire({
        icon: "success",
        title: "¡Lote creado!",
        text: resultLote.msg || resultLote.message || "El lote se creó correctamente",
        confirmButtonColor: "#00c950"
      }).then(() => {
        cerrarModal("modalRegistrarLote");
        if (typeof tablaLotes !== "undefined" && tablaLotes.ajax) {
          tablaLotes.ajax.reload();
        }
        
        formLote.reset();
      });
    }

  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: error.message || "No se pudo crear el lote",
      confirmButtonColor: "#dc2626"
    });
  } finally {
    if (btnGuardarLote) {
      btnGuardarLote.disabled = false;
      btnGuardarLote.innerHTML = `<i class="fas fa-save mr-1 md:mr-2"></i> Crear Lote con Procesos`;
    }
  }
}

function verDetallesLote(idlote) {
  
  const modalAbierto = abrirModal("modalVerLote");
  
  if (!modalAbierto) {
    mostrarError("Error: No se pudo abrir el modal de detalle del lote.");
    return;
  }
  
  
  mostrarLoadingEnModalVerDetalle();
  
  cargarInfoBasicaLote(idlote);
  
  cargarRegistrosProduccionLote(idlote);
}


function mostrarLoadingEnModalVerDetalle() {
  const tbody = document.getElementById('verRegistrosProduccion');
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="text-gray-500 mt-2">Cargando...</p></td></tr>';
  }
  
  const mensaje = document.getElementById('mensajeNoRegistros');
  if (mensaje) mensaje.style.display = 'none';
}


async function cargarInfoBasicaLote(idlote) {
  try {
    const response = await fetch(`Produccion/getLotesData`);
    const data = await response.json();
    
    if (data.status && Array.isArray(data.data)) {
      const lote = data.data.find(l => l.idlote == idlote);
      
      if (lote) {
        document.getElementById('verLoteNumero').textContent = lote.numero_lote || '-';
        document.getElementById('verLoteFecha').textContent = lote.fecha_jornada_formato || '-';
        document.getElementById('verLoteVolumen').textContent = lote.volumen_estimado ? `${lote.volumen_estimado} kg` : '-';
        document.getElementById('verLoteSupervisor').textContent = lote.supervisor || '-';
        document.getElementById('verLoteEstado').innerHTML = obtenerBadgeEstado(lote.estatus_lote);
        document.getElementById('verLoteOperarios').textContent = `${lote.operarios_asignados || 0} / ${lote.operarios_requeridos || 0}`;
        document.getElementById('verLoteObservaciones').textContent = lote.observaciones || 'Sin observaciones';
      }
    }
  } catch (error) {
  }
}


async function cargarRegistrosProduccionLote(idlote) {
  try {
    const response = await fetch(`Produccion/getRegistrosPorLote/${idlote}`);
    
    const responseText = await response.text();
    
    let result;
    try {
      result = JSON.parse(responseText);
    } catch (parseError) {
      throw new Error('El servidor no devolvió JSON válido. Ver consola para más detalles.');
    }
    
    const tbody = document.getElementById('verRegistrosProduccion');
    const seccionRegistros = document.getElementById('seccionRegistrosProduccion');
    const mensajeNoRegistros = document.getElementById('mensajeNoRegistros');
    
    if (!tbody) {
      return;
    }
    
    if (result.status && result.data && result.data.length > 0) {
      if (seccionRegistros) seccionRegistros.style.display = 'block';
      if (mensajeNoRegistros) mensajeNoRegistros.style.display = 'none';
      
      tbody.innerHTML = result.data.map(registro => `
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2">${registro.fecha_jornada_formato}</td>
          <td class="px-3 py-2">
            <div class="text-sm font-medium">${registro.nombre_empleado || 'Sin asignar'}</div>
          </td>
          <td class="px-3 py-2">
            <div class="text-sm font-medium">${registro.producto_producir_nombre}</div>
            <div class="text-xs text-gray-500">${registro.producto_producir_codigo}</div>
          </td>
          <td class="px-3 py-2 text-right font-semibold">${parseFloat(registro.cantidad_producir).toFixed(2)}</td>
          <td class="px-3 py-2">
            <div class="text-sm font-medium">${registro.producto_terminado_nombre}</div>
            <div class="text-xs text-gray-500">${registro.producto_terminado_codigo}</div>
          </td>
          <td class="px-3 py-2 text-right font-semibold text-green-600">${parseFloat(registro.cantidad_producida).toFixed(2)}</td>
          <td class="px-3 py-2 text-center">
            ${registro.tipo_movimiento === 'CLASIFICACION' 
              ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-filter mr-1"></i>Clasificación</span>'
              : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800"><i class="fas fa-cube mr-1"></i>Empaque</span>'
            }
          </td>
          <td class="px-3 py-2 text-right font-bold text-green-700">$${parseFloat(registro.salario_total).toFixed(2)}</td>
        </tr>
      `).join('');
      
      actualizarTotalesRegistros(result.totales);
      
    } else {
      if (seccionRegistros) seccionRegistros.style.display = 'none';
      if (mensajeNoRegistros) mensajeNoRegistros.style.display = 'block';
      tbody.innerHTML = '';
      limpiarTotalesRegistros();
    }
    
  } catch (error) {
    const tbody = document.getElementById('verRegistrosProduccion');
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-red-500">Error al cargar registros</td></tr>';
    }
  }
}


function actualizarTotalesRegistros(totales) {
  
  if (!totales) {
    return;
  }
  
  const elementos = {
    verTotalRegistros: document.getElementById('verTotalRegistros'),
    verTotalProducido: document.getElementById('verTotalProducido'),
    verTotalSalariosBase: document.getElementById('verTotalSalariosBase'),
    verTotalSalariosGeneral: document.getElementById('verTotalSalariosGeneral'),
    verCantidadClasificacion: document.getElementById('verCantidadClasificacion'),
    verCantidadEmpaque: document.getElementById('verCantidadEmpaque'),
    verTotalKgClasificacion: document.getElementById('verTotalKgClasificacion'),
    verTotalKgEmpaque: document.getElementById('verTotalKgEmpaque')
  };
  
  for (const [key, elemento] of Object.entries(elementos)) {
    if (!elemento) {
    }
  }
  
  if (elementos.verTotalRegistros) {
    elementos.verTotalRegistros.textContent = totales.total_registros || 0;
  }
  
  if (elementos.verTotalProducido) {
    elementos.verTotalProducido.textContent = `${parseFloat(totales.total_cantidad_producida || 0).toFixed(2)} kg`;
  }
  
  if (elementos.verTotalSalariosBase) {
    elementos.verTotalSalariosBase.textContent = `$${parseFloat(totales.total_salario_base || 0).toFixed(2)}`;
  }
  
  if (elementos.verTotalSalariosGeneral) {
    elementos.verTotalSalariosGeneral.textContent = `$${parseFloat(totales.total_salario_general || 0).toFixed(2)}`;
  }
  
  if (elementos.verCantidadClasificacion) {
    elementos.verCantidadClasificacion.textContent = totales.registros_clasificacion || 0;
  }
  
  if (elementos.verCantidadEmpaque) {
    elementos.verCantidadEmpaque.textContent = totales.registros_empaque || 0;
  }
  
  if (elementos.verTotalKgClasificacion) {
    elementos.verTotalKgClasificacion.textContent = '0.00 kg'; // Placeholder
  }
  
  if (elementos.verTotalKgEmpaque) {
    elementos.verTotalKgEmpaque.textContent = '0.00 kg'; // Placeholder
  }
  
}


function limpiarTotalesRegistros() {
  document.getElementById('verTotalRegistros').textContent = '0';
  document.getElementById('verTotalProducido').textContent = '0.00 kg';
  document.getElementById('verTotalSalariosBase').textContent = '$0.00';
  document.getElementById('verTotalSalariosGeneral').textContent = '$0.00';
  document.getElementById('verCantidadClasificacion').textContent = '0';
  document.getElementById('verCantidadEmpaque').textContent = '0';
  document.getElementById('verTotalKgClasificacion').textContent = '0.00 kg';
  document.getElementById('verTotalKgEmpaque').textContent = '0.00 kg';
}


function obtenerBadgeEstado(estatus) {
  const badges = {
    'ACTIVO': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>',
    'EN_PROCESO': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">En Proceso</span>',
    'COMPLETADO': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Completado</span>',
    'CANCELADO': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Cancelado</span>'
  };
  
  return badges[estatus] || '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">-</span>';
}

function mostrarLoadingEnModal() {
  
  limpiarModalVerLote();
  
  const numeroElement = document.getElementById('verLoteNumero');
  const mensajeElement = document.getElementById('mensajeNoProcesos');
  

  
  if (!numeroElement || !mensajeElement) {
    return;
  }
  
  numeroElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  mensajeElement.style.display = 'block';
  mensajeElement.innerHTML = `
    <div class="flex justify-center items-center py-8">
      <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mr-3"></i>
      <span class="text-gray-600">Cargando información del lote...</span>
    </div>
  `;
  
}

function cargarDatosLoteEnModal(lote) {
  
  document.getElementById('verLoteNumero').textContent = lote.numero_lote || '-';
  document.getElementById('verLoteFecha').textContent = lote.fecha_jornada_formato || '-';
  document.getElementById('verLoteVolumen').textContent = lote.volumen_estimado 
    ? `${parseFloat(lote.volumen_estimado).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2})} kg` 
    : '-';
  document.getElementById('verLoteSupervisor').textContent = lote.supervisor || '-';
  document.getElementById('verLoteOperarios').textContent = lote.operarios_asignados || '0';
  document.getElementById('verLoteObservaciones').textContent = lote.observaciones || 'Sin observaciones';
  
  const estadoElement = document.getElementById('verLoteEstado');
  const estado = lote.estatus_lote || lote.estado || '-';
  estadoElement.textContent = estado;
  
  estadoElement.className = 'text-sm sm:text-base md:text-lg font-semibold';
  
  if (estado === 'ACTIVO' || estado === 'EN PROCESO') {
    estadoElement.classList.add('text-green-600');
  } else if (estado === 'FINALIZADO' || estado === 'COMPLETADO') {
    estadoElement.classList.add('text-green-500');
  } else if (estado === 'CANCELADO') {
    estadoElement.classList.add('text-red-600');
  } else if (estado === 'PENDIENTE') {
    estadoElement.classList.add('text-yellow-600');
  } else {
    estadoElement.classList.add('text-gray-900');
  }
}

function cargarProcesosEnModal(procesos) {
  const clasificacion = procesos.clasificacion || [];
  const empaque = procesos.empaque || [];
  
  const seccionClasificacion = document.getElementById('seccionClasificacion');
  const seccionEmpaque = document.getElementById('seccionEmpaque');
  const mensajeNoProcesos = document.getElementById('mensajeNoProcesos');
  
  if (clasificacion.length === 0 && empaque.length === 0) {
    mensajeNoProcesos.style.display = 'block';
    mensajeNoProcesos.innerHTML = `
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
        </div>
        <div class="ml-3">
          <p class="text-sm text-yellow-700 font-medium">
            Este lote aún no tiene procesos registrados.
          </p>
          <p class="text-xs text-yellow-600 mt-1">
            Los procesos de clasificación y empaque se registran durante la jornada de producción.
          </p>
        </div>
      </div>
    `;
    seccionClasificacion.style.display = 'none';
    seccionEmpaque.style.display = 'none';
    return;
  }
  
  mensajeNoProcesos.style.display = 'none';
  
  if (clasificacion.length > 0) {
    seccionClasificacion.style.display = 'block';
    const tbodyClasificacion = document.getElementById('verDetalleClasificacion');
    tbodyClasificacion.innerHTML = '';
    
    clasificacion.forEach(proceso => {
      const kgProcesados = parseFloat(proceso.kg_procesados) || 0;
      const kgLimpios = parseFloat(proceso.kg_limpios) || 0;
      const kgContaminantes = parseFloat(proceso.kg_contaminantes) || 0;
      const eficiencia = kgProcesados > 0 ? ((kgLimpios / kgProcesados) * 100).toFixed(2) : 0;
      
      const row = document.createElement('tr');
      row.innerHTML = `
        <td class="px-3 py-2">${proceso.operario_nombre || proceso.empleado_nombre || '-'}</td>
        <td class="px-3 py-2">${proceso.producto_nombre || proceso.nombre_producto || '-'}</td>
        <td class="px-3 py-2 text-right font-semibold">${kgProcesados.toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
        <td class="px-3 py-2 text-right font-semibold text-green-600">${kgLimpios.toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
        <td class="px-3 py-2 text-right font-semibold text-red-600">${kgContaminantes.toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
        <td class="px-3 py-2 text-right">
          <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold ${
            eficiencia >= 90 ? 'bg-green-100 text-green-800' :
            eficiencia >= 75 ? 'bg-yellow-100 text-yellow-800' :
            'bg-red-100 text-red-800'
          }">
            ${eficiencia}%
          </span>
        </td>
      `;
      tbodyClasificacion.appendChild(row);
    });
  } else {
    seccionClasificacion.style.display = 'none';
  }
  
  if (empaque.length > 0) {
    seccionEmpaque.style.display = 'block';
    const tbodyEmpaque = document.getElementById('verDetalleEmpaque');
    tbodyEmpaque.innerHTML = '';
    
    empaque.forEach(proceso => {
      const pesoPaca = parseFloat(proceso.peso_paca) || 0;
      const calidad = proceso.calidad || 'ESTANDAR';
      
      const row = document.createElement('tr');
      row.innerHTML = `
        <td class="px-3 py-2">${proceso.operario_nombre || proceso.empleado_nombre || '-'}</td>
        <td class="px-3 py-2">${proceso.producto_nombre || proceso.nombre_producto || '-'}</td>
        <td class="px-3 py-2 text-right font-semibold">${pesoPaca.toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
        <td class="px-3 py-2 text-center">
          <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold ${
            calidad === 'PREMIUM' ? 'bg-purple-100 text-purple-800' :
            calidad === 'ESTANDAR' ? 'bg-blue-100 text-blue-800' :
            'bg-gray-100 text-gray-800'
          }">
            ${calidad}
          </span>
        </td>
        <td class="px-3 py-2 text-sm text-gray-600">${proceso.observaciones || '-'}</td>
      `;
      tbodyEmpaque.appendChild(row);
    });
  } else {
    seccionEmpaque.style.display = 'none';
  }
}

function calcularResumenProduccion(procesos) {
  const clasificacion = procesos.clasificacion || [];
  const empaque = procesos.empaque || [];
  
  if (clasificacion.length > 0) {
    const totalClasificado = clasificacion.reduce((sum, p) => sum + (parseFloat(p.kg_limpios) || 0), 0);
    const totalContaminantes = clasificacion.reduce((sum, p) => sum + (parseFloat(p.kg_contaminantes) || 0), 0);
    const totalProcesado = clasificacion.reduce((sum, p) => sum + (parseFloat(p.kg_procesados) || 0), 0);
    
    document.getElementById('contenedorTotalClasificado').style.display = 'block';
    document.getElementById('contenedorTotalContaminantes').style.display = 'block';
    document.getElementById('verTotalClasificado').textContent = 
      `${totalClasificado.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2})} kg`;
    document.getElementById('verTotalContaminantes').textContent = 
      `${totalContaminantes.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2})} kg`;
    
    const eficienciaGeneral = totalProcesado > 0 ? ((totalClasificado / totalProcesado) * 100).toFixed(2) : 0;
    document.getElementById('verEficienciaGeneral').textContent = `${eficienciaGeneral}%`;
  } else {
    document.getElementById('contenedorTotalClasificado').style.display = 'none';
    document.getElementById('contenedorTotalContaminantes').style.display = 'none';
    document.getElementById('verEficienciaGeneral').textContent = 'N/A';
  }
  
  if (empaque.length > 0) {
    const totalPacas = empaque.length;
    const pesoTotalPacas = empaque.reduce((sum, p) => sum + (parseFloat(p.peso_paca) || 0), 0);
    
    document.getElementById('contenedorTotalPacas').style.display = 'block';
    document.getElementById('contenedorPesoTotalPacas').style.display = 'block';
    document.getElementById('verTotalPacas').textContent = totalPacas;
    document.getElementById('verPesoTotalPacas').textContent = 
      `${pesoTotalPacas.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2})} kg`;
  } else {
    document.getElementById('contenedorTotalPacas').style.display = 'none';
    document.getElementById('contenedorPesoTotalPacas').style.display = 'none';
  }
}

function limpiarModalVerLote() {
  document.getElementById('verLoteNumero').textContent = '-';
  document.getElementById('verLoteFecha').textContent = '-';
  document.getElementById('verLoteVolumen').textContent = '-';
  document.getElementById('verLoteSupervisor').textContent = '-';
  document.getElementById('verLoteEstado').textContent = '-';
  document.getElementById('verLoteOperarios').textContent = '-';
  document.getElementById('verLoteObservaciones').textContent = '-';
  
  document.getElementById('verDetalleClasificacion').innerHTML = '';
  document.getElementById('verDetalleEmpaque').innerHTML = '';
  
  document.getElementById('seccionClasificacion').style.display = 'none';
  document.getElementById('seccionEmpaque').style.display = 'none';
  document.getElementById('mensajeNoProcesos').style.display = 'none';
  
  document.getElementById('contenedorTotalClasificado').style.display = 'none';
  document.getElementById('contenedorTotalContaminantes').style.display = 'none';
  document.getElementById('contenedorTotalPacas').style.display = 'none';
  document.getElementById('contenedorPesoTotalPacas').style.display = 'none';
}

function mostrarDetallesLote(lote) {
  verDetallesLote(lote.idlote);
}

function iniciarLote(idlote) {
  Swal.fire({
    title: "¿Iniciar lote de producción?",
    text: "Esta acción cambiará el estado del lote a 'EN PROCESO'.",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#00c950",
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
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}


function editarLote(idlote) {

  fetch(`Produccion/getLoteById/${idlote}`, {
    method: "GET",
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (!result.status || !result.data) {
        Swal.fire("Error", "No se pudo obtener la información del lote", "error");
        return;
      }

      const lote = result.data;

      if (lote.estatus_lote !== "PLANIFICADO") {
        Swal.fire({
          title: "No editable",
          text: `Solo se pueden editar lotes en estado PLANIFICADO. Este lote está en estado: ${lote.estatus_lote}`,
          icon: "warning",
          confirmButtonColor: "#3b82f6",
        });
        return;
      }

      fetch("Produccion/getEmpleadosActivos", {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => response.json())
        .then((empleadosResult) => {
          if (!empleadosResult.status || !empleadosResult.data) {
            Swal.fire("Error", "No se pudo cargar la lista de supervisores", "error");
            return;
          }

          const supervisores = empleadosResult.data;
          
          // Llenar array de supervisores válidos para validación de seguridad
          const supervisoresValidosEdicion = supervisores.map(emp => parseInt(emp.idempleado));

          let optionsSupervisores = supervisores
            .map((emp) => {
              const selected = emp.idempleado == lote.idsupervisor ? "selected" : "";
              return `<option value="${emp.idempleado}" ${selected}>${emp.nombre_completo}</option>`;
            })
            .join("");

          Swal.fire({
            title: `<h3 class="text-xl font-bold text-gray-800">Editar Lote ${lote.numero_lote}</h3>`,
            html: `
              <div class="text-left space-y-4 max-w-2xl mx-auto">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                  <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Editando lote en estado PLANIFICADO
                  </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Fecha Jornada <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="edit-fecha-jornada" 
                           value="${lote.fecha_jornada}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           required>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Volumen Estimado (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="edit-volumen-estimado" 
                           value="${lote.volumen_estimado}"
                           min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           required>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Supervisor <span class="text-red-500">*</span>
                    </label>
                    <select id="edit-supervisor" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            required>
                      <option value="">Seleccionar supervisor</option>
                      ${optionsSupervisores}
                    </select>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Observaciones
                    </label>
                    <textarea id="edit-observaciones" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Observaciones adicionales...">${lote.observaciones || ""}</textarea>
                  </div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                  <p class="text-xs text-gray-600">
                    <i class="fas fa-calculator mr-1"></i>
                    Los operarios requeridos se calcularán automáticamente según el volumen estimado
                  </p>
                </div>
              </div>
            `,
            width: "700px",
            showCancelButton: true,
            confirmButtonColor: "#3b82f6",
            cancelButtonColor: "#6b7280",
            confirmButtonText: 'Guardar Cambios',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            didOpen: () => {
              // Agregar validación de seguridad al select de supervisor
              const supervisorSelect = document.getElementById("edit-supervisor");
              if (supervisorSelect) {
                supervisorSelect.addEventListener('change', function() {
                  const valorSeleccionado = parseInt(this.value);
                  if (valorSeleccionado && !supervisoresValidosEdicion.includes(valorSeleccionado)) {
                    Swal.fire({
                      icon: 'error',
                      title: 'Elemento inválido detectado',
                      text: 'El supervisor seleccionado no es válido. La página se recargará por seguridad.',
                      showConfirmButton: false,
                      timer: 2000
                    }).then(() => {
                      location.reload();
                    });
                  }
                });
              }
            },
            preConfirm: () => {
              const fechaJornada = document.getElementById("edit-fecha-jornada").value;
              const volumenEstimado = document.getElementById("edit-volumen-estimado").value;
              const idsupervisor = document.getElementById("edit-supervisor").value;
              const observaciones = document.getElementById("edit-observaciones").value;

              if (!fechaJornada) {
                Swal.showValidationMessage("La fecha de jornada es obligatoria");
                return false;
              }

              if (!volumenEstimado || parseFloat(volumenEstimado) <= 0) {
                Swal.showValidationMessage("El volumen estimado debe ser mayor a 0");
                return false;
              }

              if (!idsupervisor) {
                Swal.showValidationMessage("Debe seleccionar un supervisor");
                return false;
              }

              // Validación de seguridad para evitar manipulación de IDs
              if (idsupervisor && !supervisoresValidosEdicion.includes(parseInt(idsupervisor))) {
                Swal.showValidationMessage("El supervisor seleccionado no es válido");
                setTimeout(() => location.reload(), 2000);
                return false;
              }

              const datos = {
                fecha_jornada: fechaJornada,
                volumen_estimado: parseFloat(volumenEstimado),
                idsupervisor: parseInt(idsupervisor),
                observaciones: observaciones.trim(),
              };


              return fetch(`Produccion/actualizarLote/${idlote}`, {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                  "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(datos),
              })
                .then((response) => {
                  if (!response.ok) {
                    throw new Error("Error en la respuesta del servidor");
                  }
                  return response.json();
                })
                .then((data) => {
                  if (!data.status) {
                    throw new Error(data.message || "Error al actualizar el lote");
                  }
                  return data;
                })
                .catch((error) => {
                  Swal.showValidationMessage(`Error: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading(),
          }).then((result) => {
            if (result.isConfirmed && result.value) {
              Swal.fire({
                title: "¡Actualizado!",
                text: result.value.message || "El lote ha sido actualizado exitosamente",
                icon: "success",
                confirmButtonColor: "#00c950",
              }).then(() => {
                recargarTablaLotes();
              });
            }
          });
        })
        .catch((error) => {
          Swal.fire("Error", "Error al cargar la lista de supervisores", "error");
        });
    })
    .catch((error) => {
      Swal.fire("Error", "Error al cargar los datos del lote", "error");
    });
}


function eliminarLote(idlote, numeroLote) {

  Swal.fire({
    title: "¿Eliminar lote?",
    html: `
      <div class="text-left">
        <p class="text-gray-700 mb-3">
          ¿Está seguro de eliminar el lote <strong>${numeroLote}</strong>?
        </p>
        <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded">
          <p class="text-sm text-red-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Esta acción es <strong>irreversible</strong> y solo se permite para lotes en estado PLANIFICADO.
          </p>
        </div>
      </div>
    `,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    showLoaderOnConfirm: true,
    preConfirm: () => {
      return fetch(`Produccion/eliminarLote/${idlote}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Error en la respuesta del servidor");
          }
          return response.json();
        })
        .then((data) => {
          if (!data.status) {
            throw new Error(data.message || "Error al eliminar el lote");
          }
          return data;
        })
        .catch((error) => {
          Swal.showValidationMessage(`Error: ${error.message}`);
        });
    },
    allowOutsideClick: () => !Swal.isLoading(),
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      Swal.fire({
        title: "¡Eliminado!",
        text: result.value.message || "El lote ha sido eliminado exitosamente",
        icon: "success",
        confirmButtonColor: "#00c950",
      }).then(() => {
        recargarTablaLotes();
      });
    }
  });
}



function cargarConfiguracionInicial() {
  fetch("Produccion/getConfiguracionProduccion")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        configuracionActual = result.data;
        mostrarConfiguracion(result.data);
        cargarPreciosProceso();
      } else {
        mostrarError("No se pudo cargar la configuración.");
      }
    })
    .catch((error) => {
      mostrarError("Error al cargar configuración.");
    });
}

async function cargarPreciosProceso() {
  try {
    const resp = await fetch("Produccion/getPreciosProceso");
    const data = await resp.json();
    if (data.status) {
      preciosProceso = data.data || [];
      renderTablaPreciosProceso();
    }
  } catch(e) {
  }
}

function renderTablaPreciosProceso() {
  const tbody = document.getElementById("tabla-precios-proceso-body");
  if (!tbody) return;
  tbody.innerHTML = "";
  preciosProceso.forEach((p) => {
    const tr = document.createElement("tr");
    tr.className = "hover:bg-gray-50";
    const estadoClass = p.estatus === 'activo' ? 'text-green-600' : 'text-gray-500';
    tr.innerHTML = `
      <td class="px-2 py-2 text-xs">${p.tipo_proceso}</td>
      <td class="px-2 py-2 text-xs">${p.producto_nombre || p.idproducto}</td>
      <td class="px-2 py-2 text-xs text-right font-semibold">$${parseFloat(p.salario_unitario).toFixed(4)}</td>
      <td class="px-2 py-2 text-xs">${p.moneda || 'USD'}</td>
      <td class="px-2 py-2 text-xs ${estadoClass}">${p.estatus}</td>
      <td class="px-2 py-2 text-xs text-right">
        <button class="text-red-600 hover:text-red-800" data-action="borrar-precio" data-id="${p.idconfig_salario}"><i class="fas fa-trash"></i></button>
      </td>`;
    tbody.appendChild(tr);
  });
}

async function crearPrecioProceso() {
  
  const tipo = document.getElementById("tipo_proceso_salario")?.value || '';
  const idproducto = parseInt(document.getElementById("idproducto_precio")?.value || 0);
  const salario = parseFloat(document.getElementById("salario_unitario_input")?.value || 0);
  
  const data = {
    tipo_proceso: tipo.toUpperCase(),
    idproducto: idproducto,
    salario_unitario: salario,
    moneda: 'USD',
    unidad_base: null
  };
  
  
  if (!data.tipo_proceso || !data.idproducto || data.salario_unitario <= 0) {
    Swal.fire("Datos inválidos", "Completa tipo de proceso, producto y salario válido.", "warning");
    return;
  }
  
  try {
    const resp = await fetch("Produccion/createPrecioProceso", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    });
    const json = await resp.json();
    
    if (json.status) {
      await cargarPreciosProceso();
      Swal.fire("Creado", json.message || "Salario configurado", "success");
      document.getElementById("tipo_proceso_salario").value = '';
      document.getElementById("idproducto_precio").value = '';
      document.getElementById("salario_unitario_input").value = '';
    } else {
      Swal.fire("Error", json.message || "No se pudo crear", "error");
    }
  } catch (e) {
    Swal.fire("Error", "Fallo de red: " + e.message, "error");
  }
}

document.addEventListener("click", async (e) => {
  const target = e.target.closest("[data-action='borrar-precio']");
  if (!target) return;
  const id = parseInt(target.getAttribute("data-id"));
  if (!id) return;
  const ok = await Swal.fire({
    title: "¿Desactivar precio?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí",
    cancelButtonText: "No",
  });
  if (ok.isConfirmed) {
    try {
      const resp = await fetch(`Produccion/deletePrecioProceso/${id}`, { method: "POST" });
      const json = await resp.json();
      if (json.status) {
        await cargarPreciosProceso();
        Swal.fire("Hecho", json.message || "Precio desactivado", "success");
      } else {
        Swal.fire("Error", json.message || "No se pudo desactivar", "error");
      }
    } catch (e2) {
      Swal.fire("Error", "Fallo de red", "error");
    }
  }
});

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
      Swal.fire("Error", "Error de conexión.", "error");
    })
    .finally(() => {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Configuración`;
      }
    });
}



function abrirModalConsultarPorFecha() {
  Swal.fire({
    title: "Consultar Registros por Fecha",
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
        <div class="bg-blue-50 border border-blue-200 rounded p-3">
          <p class="text-blue-800 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            Se mostrarán los registros de producción en el periodo seleccionado con sus salarios calculados.
          </p>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonColor: "#00c950",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Consultar Registros",
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
      consultarRegistrosPorFecha(result.value.fechaInicio, result.value.fechaFin);
    }
  });
}


function consultarRegistrosPorFecha(fechaInicio, fechaFin) {
  
  if (!tablaNomina) {
    Swal.fire({
      title: "Error",
      text: "La tabla de nómina no está inicializada. Por favor, recarga la página.",
      icon: "error"
    });
    return;
  }

  
  Swal.fire({
    title: "Consultando Registros...",
    html: `Buscando registros desde <strong>${fechaInicio}</strong> hasta <strong>${fechaFin}</strong>`,
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  const tabNomina = document.getElementById("tab-nomina");
  if (tabNomina) {
    tabNomina.click();
  }

  setTimeout(() => {
    if (tablaNomina && tablaNomina.ajax) {
      const urlConFiltros = `./Produccion/getRegistrosProduccion?fecha_desde=${fechaInicio}&fecha_hasta=${fechaFin}`;
      
      tablaNomina.ajax.url(urlConFiltros).load(function(json) {
        
        const cantidadRegistros = json && json.data ? json.data.length : 0;
        
        Swal.close();
        
        if (cantidadRegistros > 0) {
          Swal.fire({
            title: "¡Registros Encontrados!",
            html: `Se encontraron <strong>${cantidadRegistros}</strong> registros<br>desde ${fechaInicio} hasta ${fechaFin}`,
            icon: "success",
            confirmButtonColor: "#00c950"
          });
        } else {
          Swal.fire({
            title: "Sin Resultados",
            html: `No se encontraron registros<br>desde ${fechaInicio} hasta ${fechaFin}.<br><br>Verifica que existan producciones registradas en ese rango de fechas.`,
            icon: "info",
            confirmButtonColor: "#00c950"
          });
        }
        
        if (tablaNomina.columns) {
          setTimeout(() => {
            tablaNomina.columns.adjust().draw();
          }, 100);
        }
      }, function(xhr, error, thrown) {
        
        Swal.close();
        Swal.fire({
          title: "Error al Consultar",
          html: `No se pudo cargar los registros.<br>Error: ${error}<br><br>Revisa la consola (F12) para más detalles.`,
          icon: "error",
          confirmButtonColor: "#dc2626"
        });
      });
    } else {
      
      Swal.close();
      Swal.fire({
        title: "Error de Configuración",
        text: "La tabla de nómina no está configurada correctamente. Por favor, recarga la página.",
        icon: "error"
      });
    }
  }, 300);
}



function marcarComoPagado(idregistro, empleado, salario) {
  
  Swal.fire({
    title: "¿Marcar como Pagado?",
    html: `
      <div class="text-left">
        <p class="mb-4">¿Confirmas que el pago ha sido realizado?</p>
        <div class="bg-blue-50 border border-blue-200 rounded p-3">
          <p class="text-sm"><strong>Empleado:</strong> ${empleado}</p>
          <p class="text-sm"><strong>Salario:</strong> $${salario}</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
          <p class="text-xs text-yellow-800">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Esta acción cambiará el estado de <strong>ENVIADO</strong> a <strong>PAGADO</strong>
          </p>
        </div>
      </div>
    `,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#00c950",
    cancelButtonColor: "#6b7280",
    confirmButtonText: 'Sí, marcar como pagado',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Procesando...',
        text: 'Marcando registro como pagado...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });


      fetch(base_url + "Produccion/marcarComoPagado", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ idregistro: idregistro }),
      })
        .then((response) => response.json())
        .then((result) => {
          
          if (result.status) {
            Swal.fire({
              title: "¡Marcado como Pagado!",
              html: `
                <div class="text-center">
                  <i class="fas fa-check-circle text-green-500 text-5xl mb-3"></i>
                  <p class="text-lg mb-2">${result.message}</p>
                  <div class="bg-green-50 border border-green-200 rounded p-3 mt-3">
                    <p class="text-sm text-green-800">El registro ahora está en estado <strong>PAGADO</strong></p>
                  </div>
                </div>
              `,
              icon: "success",
              confirmButtonColor: "#00c950"
            }).then(() => {
              if (tablaNomina) {
                tablaNomina.ajax.reload(null, false);
              }
            });
          } else {
            Swal.fire({
              title: "Error",
              text: result.message || "No se pudo marcar el registro como pagado.",
              icon: "error",
              confirmButtonColor: "#dc2626"
            });
          }
        })
        .catch((error) => {
          Swal.fire({
            title: "Error de Conexión",
            text: "No se pudo conectar con el servidor.",
            icon: "error",
            confirmButtonColor: "#dc2626"
          });
        });
    }
  });
}


function verDetalleRegistroNomina(idregistro) {
  
  const datos = tablaNomina.rows().data().toArray();
  const registro = datos.find(r => r.idregistro == idregistro);
  
  if (!registro) {
    Swal.fire({
      title: "Error",
      text: "No se encontró el registro seleccionado.",
      icon: "error"
    });
    return;
  }

  const estatus = registro.estatus || 'BORRADOR';
  const estatusBadge = {
    'BORRADOR': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800"><i class="fas fa-edit mr-1"></i>Borrador</span>',
    'ENVIADO': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-paper-plane mr-1"></i>Enviado</span>',
    'PAGADO': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Pagado</span>',
    'CANCELADO': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Cancelado</span>'
  };

  Swal.fire({
    title: "Detalle de Registro de Nómina",
    html: `
      <div class="text-left space-y-4">
        <!-- Estado -->
        <div class="flex justify-between items-center pb-3 border-b">
          <span class="text-sm text-gray-600">Estado:</span>
          ${estatusBadge[estatus] || estatusBadge['BORRADOR']}
        </div>

        <!-- Información del Empleado -->
        <div class="bg-blue-50 border border-blue-200 rounded p-3">
          <p class="font-semibold text-blue-800 mb-2"><i class="fas fa-user mr-2"></i>Información del Empleado</p>
          <p class="text-sm"><strong>Nombre:</strong> ${registro.nombre_empleado || 'Sin asignar'}</p>
          <p class="text-sm"><strong>Fecha de Jornada:</strong> ${registro.fecha_jornada_formato}</p>
          <p class="text-sm"><strong>Lote:</strong> ${registro.numero_lote}</p>
        </div>

        <!-- Detalles de Producción -->
        <div class="bg-purple-50 border border-purple-200 rounded p-3">
          <p class="font-semibold text-purple-800 mb-2"><i class="fas fa-box mr-2"></i>Detalles de Producción</p>
          <p class="text-sm"><strong>Tipo:</strong> ${registro.tipo_movimiento === 'CLASIFICACION' ? 'Clasificación' : 'Empaque'}</p>
          <p class="text-sm"><strong>Producto Inicial:</strong> ${registro.producto_producir_nombre}</p>
          <p class="text-sm"><strong>Producto Final:</strong> ${registro.producto_terminado_nombre}</p>
          <p class="text-sm"><strong>Cantidad Producida:</strong> ${parseFloat(registro.cantidad_producida).toFixed(2)} kg</p>
        </div>

        <!-- Detalles de Salario -->
        <div class="bg-green-50 border border-green-200 rounded p-3">
          <p class="font-semibold text-green-800 mb-2"><i class="fas fa-dollar-sign mr-2"></i>Detalles de Salario</p>
          <div class="space-y-1">
            <div class="flex justify-between text-sm">
              <span>Salario Base:</span>
              <span class="font-semibold">$${parseFloat(registro.salario_base_dia).toFixed(2)}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span>Pago por Trabajo:</span>
              <span class="font-semibold">$${parseFloat(registro.pago_clasificacion_trabajo).toFixed(2)}</span>
            </div>
            <div class="flex justify-between text-base border-t pt-2 mt-2">
              <span class="font-bold">Salario Total:</span>
              <span class="font-bold text-green-700 text-lg">$${parseFloat(registro.salario_total).toFixed(2)}</span>
            </div>
          </div>
        </div>

        ${registro.observaciones ? `
          <div class="bg-gray-50 border border-gray-200 rounded p-3">
            <p class="font-semibold text-gray-800 mb-1"><i class="fas fa-sticky-note mr-2"></i>Observaciones</p>
            <p class="text-sm text-gray-700">${registro.observaciones}</p>
          </div>
        ` : ''}
      </div>
    `,
    icon: "info",
    confirmButtonColor: "#00c950",
    confirmButtonText: 'Cerrar',
    width: '600px'
  });
}


function cancelarRegistroNomina(idregistro, empleado) {
  
  Swal.fire({
    title: "¿Cancelar Registro?",
    html: `
      <div class="text-left">
        <p class="mb-4">¿Estás seguro de que deseas cancelar este registro?</p>
        <div class="bg-blue-50 border border-blue-200 rounded p-3">
          <p class="text-sm"><strong>Empleado:</strong> ${empleado}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded p-3 mt-3">
          <p class="text-xs text-red-800">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            El registro cambiará a estado <strong>CANCELADO</strong> y no podrá ser procesado para pago.
          </p>
        </div>
      </div>
    `,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Procesando...',
        text: 'Cancelando registro...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch(base_url + "Produccion/cancelarRegistroNomina", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ idregistro: idregistro }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire({
              title: "¡Registro Cancelado!",
              text: result.message,
              icon: "success",
              confirmButtonColor: "#00c950"
            }).then(() => {
              if (tablaNomina) {
                tablaNomina.ajax.reload(null, false);
              }
            });
          } else {
            Swal.fire({
              title: "Error",
              text: result.message || "No se pudo cancelar el registro.",
              icon: "error",
              confirmButtonColor: "#dc2626"
            });
          }
        })
        .catch((error) => {
          Swal.fire({
            title: "Error de Conexión",
            text: "No se pudo conectar con el servidor.",
            icon: "error",
            confirmButtonColor: "#dc2626"
          });
        });
    }
  });
}

function actualizarContadorEstados(datos) {
  if (!datos || !Array.isArray(datos)) {
    return;
  }

  const contador = {
    borrador: 0,
    enviado: 0,
    pagado: 0,
    cancelado: 0
  };

  datos.forEach(registro => {
    const estatus = (registro.estatus || 'BORRADOR').toUpperCase();
    switch(estatus) {
      case 'BORRADOR':
        contador.borrador++;
        break;
      case 'ENVIADO':
        contador.enviado++;
        break;
      case 'PAGADO':
        contador.pagado++;
        break;
      case 'CANCELADO':
        contador.cancelado++;
        break;
    }
  });


  const btnRegistrarSalario = document.getElementById('btnRegistrarSalario');
  if (btnRegistrarSalario && contador.borrador > 0) {
    btnRegistrarSalario.innerHTML = `<i class="fas fa-money-check-alt mr-2"></i>Registrar Salario (${contador.borrador} disponibles)`;
  }
}

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
        // Limpiar y llenar el array de supervisores válidos
        supervisoresValidos.length = 0;
        
        const select = document.getElementById(selectId);
        if (select) {
          let options = '<option value="">Seleccionar empleado...</option>';
          
          result.data.forEach(empleado => {
            // Agregar ID al array de válidos
            supervisoresValidos.push(parseInt(empleado.idempleado));
            options += `<option value="${empleado.idempleado}">${empleado.nombre_completo}</option>`;
          });
          
          select.innerHTML = options;
          
          // Agregar listener para validación de seguridad
          select.removeEventListener('change', validarSupervisorChange);
          select.addEventListener('change', validarSupervisorChange);
        }
      }
    })
    .catch((error) => {
    });
}

// Función específica para validar cambio de supervisor
function validarSupervisorChange(event) {
  return validarElementoSeguro(event.target, supervisoresValidos, 'Supervisor');
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
    confirmButtonColor: "#00c950"
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

async function manejarPesoRomanaClasificacion(campo) {
            try {
                const response = await fetch("Compras/getUltimoPesoRomana");
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                
                if (data.status) {
                    document.getElementById(campo).value = data.peso;
                    
                    await fetch("Compras/guardarPesoRomana", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            peso: data.peso,
                            fecha: new Date().toISOString().slice(0, 19).replace("T", " "),
                        }),
                    });

                    Swal.fire({
                        title: 'Éxito',
                        text: `Peso actualizado: ${data.peso} kg`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire(
                        "Atención",
                        data.message || "No se pudo obtener el peso.",
                        "warning"
                    );
                }
            } catch (e) {
                Swal.fire("Error", "Error al consultar la romana: " + e.message, "error");
            }
        }



function abrirModalRegistrarProduccion() {
  const modal = abrirModal("modalRegistrarProduccion");
  
  if (!modal) {
    mostrarError("No se pudo abrir el modal");
    return;
  }

  const form = document.getElementById("formRegistrarProduccion");
  if (form) form.reset();

  const fechaInput = document.getElementById("prod_fecha_jornada");
  if (fechaInput) {
    fechaInput.value = new Date().toISOString().split('T')[0];
  }

  cargarLotesActivos();
  cargarEmpleadosParaProduccion();
  cargarProductosParaProduccion();
  limpiarCamposSalarios();
}


async function cargarLotesActivos() {
  try {
    const response = await fetch("Produccion/getLotesData");
    const data = await response.json();

    const selectLote = document.getElementById("prod_lote");
    if (!selectLote) return;

    selectLote.innerHTML = '<option value="">Seleccionar lote...</option>';

    if (data.status && Array.isArray(data.data)) {
      data.data.forEach(lote => {
        if (lote.estatus_lote === 'ACTIVO' || lote.estatus_lote === 'EN_PROCESO') {
          const option = document.createElement("option");
          option.value = lote.idlote;
          option.textContent = `${lote.numero_lote} - ${lote.fecha_jornada_formato}`;
          selectLote.appendChild(option);
        }
      });
    }
  } catch (error) {
    mostrarError("Error al cargar lotes activos");
  }
}


async function cargarEmpleadosParaProduccion() {
  try {
    const response = await fetch("Produccion/getEmpleadosActivos");
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();

    const selectEmpleado = document.getElementById("prod_empleado");

    if (!selectEmpleado) {
      return;
    }

    selectEmpleado.innerHTML = '<option value="">Seleccionar empleado...</option>';

    if (!data || !data.data) {
      mostrarAdvertencia("No se recibieron empleados del servidor");
      return;
    }

    if (!Array.isArray(data.data)) {
      mostrarAdvertencia("Formato de datos incorrecto");
      return;
    }


    let empleadosActivos = 0;
    
    // Limpiar y cargar IDs válidos para validación
    empleadosValidos = [];
    
    data.data.forEach((empleado, index) => {
      
      empleadosActivos++;
      
      // Guardar ID válido
      empleadosValidos.push(parseInt(empleado.idempleado));
      
      const option = document.createElement("option");
      option.value = empleado.idempleado;
      
      option.textContent = empleado.nombre_completo || `${empleado.nombre || ''} ${empleado.apellido || ''}`.trim() || `Empleado ${empleado.idempleado}`;
      
      selectEmpleado.appendChild(option);
    });
    
    // Agregar validador de seguridad
    if (selectEmpleado && !selectEmpleado.hasAttribute('data-validador-agregado')) {
      selectEmpleado.addEventListener('change', validarEmpleado);
      selectEmpleado.setAttribute('data-validador-agregado', 'true');
    }
    
    
    if (empleadosActivos === 0) {
      mostrarAdvertencia("No hay empleados activos disponibles");
    }
    
  } catch (error) {
    mostrarError("Error al cargar empleados: " + error.message);
  }
}


async function cargarProductosParaProduccion() {
  try {
    
    const response = await fetch("Productos/getProductosData");
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();

    const selectProducir = document.getElementById("prod_producto_producir");
    const selectTerminado = document.getElementById("prod_producto_terminado");

    if (!selectProducir || !selectTerminado) {
      return;
    }

    selectProducir.innerHTML = '<option value="">Seleccionar producto...</option>';
    selectTerminado.innerHTML = '<option value="">Seleccionar producto...</option>';

    if (!data || !data.data) {
      mostrarAdvertencia("No se recibieron productos del servidor");
      return;
    }

    if (!Array.isArray(data.data)) {
      mostrarAdvertencia("Formato de datos incorrecto");
      return;
    }


    let productosActivos = 0;
    
    // Limpiar y cargar IDs válidos para validación
    productosValidos = [];
    
    data.data.forEach((producto, index) => {
      
      const estaActivo = producto.estatus == 'ACTIVO' || 
                        producto.estatus == 1 || 
                        producto.estatus == '1' ||
                        producto.estatus === true;
      
      if (estaActivo) {
        productosActivos++;
        
        // Guardar ID válido
        productosValidos.push(parseInt(producto.idproducto));
        
        const option1 = document.createElement("option");
        option1.value = producto.idproducto;
        option1.textContent = producto.nombre || producto.descripcion || 'Sin nombre';
        selectProducir.appendChild(option1);

        const option2 = document.createElement("option");
        option2.value = producto.idproducto;
        option2.textContent = producto.nombre || producto.descripcion || 'Sin nombre';
        selectTerminado.appendChild(option2);
      }
    });
    
    // TAMBIÉN cargar productos inactivos si no hay activos
    if (productosActivos === 0) {
      data.data.forEach(producto => {
        // Guardar ID válido incluso si está inactivo
        productosValidos.push(parseInt(producto.idproducto));
        
        const option1 = document.createElement("option");
        option1.value = producto.idproducto;
        option1.textContent = producto.nombre || producto.descripcion || 'Sin nombre';
        selectProducir.appendChild(option1);

        const option2 = document.createElement("option");
        option2.value = producto.idproducto;
        option2.textContent = producto.nombre || producto.descripcion || 'Sin nombre';
        selectTerminado.appendChild(option2);
      });
    }
    
    // Agregar validadores de seguridad
    if (selectProducir && !selectProducir.hasAttribute('data-validador-agregado')) {
      selectProducir.addEventListener('change', validarProducto);
      selectProducir.setAttribute('data-validador-agregado', 'true');
    }
    
    if (selectTerminado && !selectTerminado.hasAttribute('data-validador-agregado')) {
      selectTerminado.addEventListener('change', validarProducto);
      selectTerminado.setAttribute('data-validador-agregado', 'true');
    }
    
    
  } catch (error) {
    mostrarError("Error al cargar productos: " + error.message);
  }
}


async function calcularSalariosAutomaticamente() {
  try {
    const cantidadProducida = parseFloat(document.getElementById("prod_cantidad_producida").value) || 0;
    const tipoMovimiento = document.getElementById("prod_tipo_movimiento").value;

    if (cantidadProducida <= 0 || !tipoMovimiento) {
      limpiarCamposSalarios();
      return;
    }

    if (!configuracionActual || Object.keys(configuracionActual).length === 0) {
      await cargarConfiguracionInicial();
    }

    const salarioBase = parseFloat(configuracionActual.salario_base || 30.00);
    let precioUnit = 0;
    const productoBaseId = tipoMovimiento === 'CLASIFICACION'
      ? parseInt(document.getElementById('prod_producto_producir')?.value || 0)
      : parseInt(document.getElementById('prod_producto_terminado')?.value || 0);
    if (productoBaseId && preciosProceso.length) {
      const match = preciosProceso.find(p => String(p.tipo_proceso) === String(tipoMovimiento) && parseInt(p.idproducto) === productoBaseId && p.estatus === 'activo');
      if (match) precioUnit = parseFloat(match.salario_unitario || 0);
    }
    if (precioUnit <= 0) {
      precioUnit = tipoMovimiento === 'CLASIFICACION'
        ? parseFloat(configuracionActual.beta_clasificacion || 0.25)
        : parseFloat(configuracionActual.gamma_empaque || 5.00);
    }
    const pagoTrabajo = precioUnit * cantidadProducida;

    const salarioTotal = salarioBase + pagoTrabajo;

    document.getElementById("prod_salario_base_dia").value = salarioBase.toFixed(2);
    document.getElementById("prod_pago_clasificacion").value = pagoTrabajo.toFixed(2);
    document.getElementById("prod_salario_total").value = salarioTotal.toFixed(2);

  } catch (error) {
  }
}


function limpiarCamposSalarios() {
  document.getElementById("prod_salario_base_dia").value = "0.00";
  document.getElementById("prod_pago_clasificacion").value = "0.00";
  document.getElementById("prod_salario_total").value = "0.00";
}


async function guardarRegistroProduccion() {
  try {
    // Validar seguridad antes de procesar
    if (!validarFormularioCompleto()) {
      return; // La validación ya mostró el error
    }
    
    const idlote = document.getElementById("prod_lote").value;
    const idempleado = document.getElementById("prod_empleado").value;
    const fecha_jornada = document.getElementById("prod_fecha_jornada").value;
    const idproducto_producir = document.getElementById("prod_producto_producir").value;
    const cantidad_producir = document.getElementById("prod_cantidad_producir").value;
    const idproducto_terminado = document.getElementById("prod_producto_terminado").value;
    const cantidad_producida = document.getElementById("prod_cantidad_producida").value;
    const tipo_movimiento = document.getElementById("prod_tipo_movimiento").value;
    const observaciones = document.getElementById("prod_observaciones").value.trim();

    if (!idlote) {
      mostrarAdvertencia("Debe seleccionar un lote");
      return;
    }

    if (!idempleado) {
      mostrarAdvertencia("Debe seleccionar un empleado");
      return;
    }

    if (!fecha_jornada) {
      mostrarAdvertencia("Debe ingresar la fecha de jornada");
      return;
    }

    if (!idproducto_producir) {
      mostrarAdvertencia("Debe seleccionar el producto a producir");
      return;
    }

    if (!idproducto_terminado) {
      mostrarAdvertencia("Debe seleccionar el producto terminado");
      return;
    }

    if (parseFloat(cantidad_producir) <= 0) {
      mostrarAdvertencia("La cantidad a producir debe ser mayor a cero");
      return;
    }

    if (parseFloat(cantidad_producida) <= 0) {
      mostrarAdvertencia("La cantidad producida debe ser mayor a cero");
      return;
    }

    if (!tipo_movimiento) {
      mostrarAdvertencia("Debe seleccionar el tipo de movimiento");
      return;
    }

    Swal.fire({
      title: "Guardando...",
      text: "Por favor espere",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append("idlote", idlote);
    formData.append("idempleado", idempleado);
    formData.append("fecha", fecha_jornada);
    formData.append("tipo_proceso", tipo_movimiento);
    formData.append("idproducto_inicial", idproducto_producir);
    formData.append("idproducto_final", idproducto_terminado);
    formData.append("cantidad_producida", cantidad_producida);
    formData.append("observaciones", observaciones);

    const response = await fetch(base_url + "Produccion/crearRegistroProduccion", {
      method: "POST",
      body: formData
    });

    const result = await response.json();

    if (result.status) {
      Swal.fire({
        icon: "success",
        title: "¡Éxito!",
        text: result.msg || result.message || "Registro de producción guardado correctamente",
        confirmButtonColor: "#00c950"
      }).then(() => {
        cerrarModal("modalRegistrarProduccion");
        
        // Recargar elementos para limpiar manipulaciones
        recargarElementos();
        
        if (typeof tablaRegistrosProcesos !== "undefined" && tablaRegistrosProcesos.ajax) {
          tablaRegistrosProcesos.ajax.reload(null, false);
        }
        if (typeof tablaProcesos !== "undefined" && tablaProcesos.ajax) {
          tablaProcesos.ajax.reload(null, false);
        }
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: result.msg || result.message || "No se pudo guardar el registro",
        confirmButtonColor: "#dc2626"
      });
    }

  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al guardar el registro de producción",
      confirmButtonColor: "#dc2626"
    });
  }
}



async function editarRegistroProduccion(idregistro) {
  try {
    
    const response = await fetch(`Produccion/getRegistroById/${idregistro}`);
    const result = await response.json();
    
    if (!result.status || !result.data) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: result.message || 'No se pudo obtener el registro',
        confirmButtonColor: '#dc2626'
      });
      return;
    }
    
    const registro = result.data;
    
    if (registro.estatus !== 'BORRADOR') {
      Swal.fire({
        icon: 'warning',
        title: 'No editable',
        text: `Solo se pueden editar registros en estado BORRADOR. Este registro está en estado: ${registro.estatus}`,
        confirmButtonColor: '#f59e0b'
      });
      return;
    }
    
    const { value: formValues } = await Swal.fire({
      title: `<div class="text-left">
                <i class="fas fa-edit text-emerald-600 mr-2"></i>
                Editar Registro de Producción
              </div>`,
      html: `
        <div class="text-left space-y-3 sm:space-y-4 max-h-[70vh] overflow-y-auto px-2 sm:px-3">
          <div class="bg-emerald-50 border-l-4 border-emerald-500 p-2 sm:p-3 mb-3 sm:mb-4 rounded-r">
            <p class="text-xs sm:text-sm"><strong>Lote:</strong> ${registro.numero_lote}</p>
            <p class="text-xs sm:text-sm"><strong>Empleado:</strong> ${registro.nombre_empleado || 'Sin asignar'}</p>
            <p class="text-xs sm:text-sm"><strong>Estado:</strong> <span class="text-emerald-700 font-semibold">${registro.estatus}</span></p>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-calendar-alt text-green-500 mr-1"></i>Fecha de Jornada:
              </label>
              <input id="edit_fecha_jornada" type="date" value="${registro.fecha_jornada_input}" 
                     class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
            </div>
            
            <div class="sm:col-span-2">
              <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-tasks text-green-500 mr-1"></i>Tipo de Movimiento:
              </label>
              <select id="edit_tipo_movimiento" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <option value="CLASIFICACION" ${registro.tipo_movimiento === 'CLASIFICACION' ? 'selected' : ''}> Clasificación</option>
                <option value="EMPAQUE" ${registro.tipo_movimiento === 'EMPAQUE' ? 'selected' : ''}>Empaque</option>
              </select>
            </div>
            
            <div>
              <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-box text-green-500 mr-1"></i>Cantidad a Producir (kg):
              </label>
              <input id="edit_cantidad_producir" type="number" step="0.01" value="${registro.cantidad_producir}" 
                     class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" 
                     placeholder="0.00">
            </div>
            
            <div>
              <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-check-circle text-green-500 mr-1"></i>Cantidad Producida (kg):
              </label>
              <input id="edit_cantidad_producida" type="number" step="0.01" value="${registro.cantidad_producida}" 
                     class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" 
                     placeholder="0.00">
            </div>
            
            <div class="sm:col-span-2">
              <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-comment-alt text-green-500 mr-1"></i>Observaciones:
              </label>
              <textarea id="edit_observaciones" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none" 
                        rows="3" placeholder="Observaciones opcionales...">${registro.observaciones || ''}</textarea>
            </div>
          </div>
          
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 sm:p-3 mt-3">
            <p class="text-xs text-blue-800 flex items-start">
              <i class="fas fa-info-circle mt-0.5 mr-2 text-green-500"></i>
              <span>Los salarios se calcularán automáticamente al guardar los cambios</span>
            </p>
          </div>
        </div>
      `,
      width: window.innerWidth < 640 ? '95%' : window.innerWidth < 768 ? '85%' : '650px',
      showCancelButton: true,
      confirmButtonText: '<i class="fas fa-save mr-2"></i>Guardar Cambios',
      cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
      confirmButtonColor: '#00ca54',
      cancelButtonColor: '#6b7280',
      focusConfirm: false,
      preConfirm: () => {
        const fecha_jornada = document.getElementById('edit_fecha_jornada').value;
        const tipo_movimiento = document.getElementById('edit_tipo_movimiento').value;
        const cantidad_producir = document.getElementById('edit_cantidad_producir').value;
        const cantidad_producida = document.getElementById('edit_cantidad_producida').value;
        const observaciones = document.getElementById('edit_observaciones').value;
        
        if (!fecha_jornada) {
          Swal.showValidationMessage('La fecha es requerida');
          return false;
        }
        
        if (!cantidad_producir || parseFloat(cantidad_producir) <= 0) {
          Swal.showValidationMessage('La cantidad a producir debe ser mayor a 0');
          return false;
        }
        
        if (!cantidad_producida || parseFloat(cantidad_producida) <= 0) {
          Swal.showValidationMessage('La cantidad producida debe ser mayor a 0');
          return false;
        }
        
        return {
          fecha_jornada,
          tipo_movimiento,
          idproducto_producir: registro.idproducto_producir,
          idproducto_terminado: registro.idproducto_terminado,
          cantidad_producir: parseFloat(cantidad_producir),
          cantidad_producida: parseFloat(cantidad_producida),
          observaciones
        };
      }
    });
    
    if (!formValues) return; // Usuario canceló
    
    const updateResponse = await fetch(`Produccion/actualizarRegistroProduccion/${idregistro}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formValues)
    });
    
    const updateResult = await updateResponse.json();
    
    if (updateResult.status) {
      Swal.fire({
        icon: 'success',
        title: '¡Actualizado!',
        text: updateResult.message || 'Registro actualizado correctamente',
        confirmButtonColor: '#00c950'
      });
      
      if (typeof tablaProcesos !== 'undefined' && tablaProcesos.ajax) {
        tablaProcesos.ajax.reload(null, false);
      }
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: updateResult.message || 'No se pudo actualizar el registro',
        confirmButtonColor: '#dc2626'
      });
    }
    
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al editar el registro',
      confirmButtonColor: '#dc2626'
    });
  }
}


async function eliminarRegistroProduccion(idregistro, nombreEmpleado, numeroLote) {
  try {
    
    const confirmacion = await Swal.fire({
      title: '¿Eliminar Registro?',
      html: `
        <div class="text-left">
          <p class="mb-2">¿Está seguro de eliminar este registro de producción?</p>
          <div class="bg-red-50 border-l-4 border-red-500 p-3 mt-3">
            <p class="text-sm"><strong>Lote:</strong> ${numeroLote}</p>
            <p class="text-sm"><strong>Empleado:</strong> ${nombreEmpleado}</p>
          </div>
          <p class="text-sm text-gray-600 mt-3">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-1"></i>
            Esta acción no se puede deshacer.
          </p>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar',
      cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280'
    });
    
    if (!confirmacion.isConfirmed) return;
    
    const response = await fetch(`Produccion/eliminarRegistroProduccion/${idregistro}`, {
      method: 'POST' // Usamos POST porque DELETE puede tener problemas en algunos servidores
    });
    
    const result = await response.json();
    
    if (result.status) {
      Swal.fire({
        icon: 'success',
        title: '¡Eliminado!',
        text: result.message || 'Registro eliminado correctamente',
        confirmButtonColor: '#00c950'
      });
      
      if (typeof tablaProcesos !== 'undefined' && tablaProcesos.ajax) {
        tablaProcesos.ajax.reload(null, false);
      }
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: result.message || 'No se pudo eliminar el registro',
        confirmButtonColor: '#dc2626'
      });
    }
    
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al eliminar el registro',
      confirmButtonColor: '#dc2626'
    });
  }
}

window.editarRegistroProduccion = editarRegistroProduccion;
window.eliminarRegistroProduccion = eliminarRegistroProduccion;
window.editarLote = editarLote;
window.eliminarLote = eliminarLote;
window.toggleOperarioAsignado = toggleOperarioAsignado;
window.actualizarTareaOperario = actualizarTareaOperario;
window.removerOperarioAsignado = removerOperarioAsignado;
window.debugAsignaciones = debugAsignaciones;

