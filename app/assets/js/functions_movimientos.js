import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
  registrarEntidad
} from "./validaciones.js";

let tablaMovimientos;
let tiposMovimientoDisponibles = []; 


const expresionesMovimientos = {
  numeros_decimales: /^\d+(\.\d{1,4})?$/,
  cantidad_positiva: /^[1-9]\d*(\.\d{1,3})?$/,
  stock: /^\d+(\.\d{1,3})?$/
};


const camposFormularioMovimiento = [
  {
    id: "idproducto",
    tipo: "select",
    mensajes: { vacio: "Seleccione un producto." },
  },
  {
    id: "idtipomovimiento",
    tipo: "select",
    mensajes: { vacio: "Seleccione un tipo de movimiento." },
  },
  {
    id: "cantidad_entrada",
    tipo: "input",
    regex: expresionesMovimientos.numeros_decimales,
    mensajes: { formato: "Cantidad de entrada inválida." },
    opcional: true
  },
  {
    id: "cantidad_salida",
    tipo: "input",
    regex: expresionesMovimientos.numeros_decimales,
    mensajes: { formato: "Cantidad de salida inválida." },
    opcional: true
  },
  {
    id: "stock_anterior",
    tipo: "input",
    regex: expresionesMovimientos.stock,
    mensajes: { formato: "Stock anterior inválido." },
    opcional: true
  },
  {
    id: "stock_resultante",
    tipo: "input",
    regex: expresionesMovimientos.stock,
    mensajes: { formato: "Stock resultante inválido." },
    opcional: true
  },
  {
    id: "observaciones",
    tipo: "textarea",
    mensajes: {},
    opcional: true
  }
];

const camposFormularioActualizarMovimiento = [
  {
    id: "idproductoActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un producto." },
  },
  {
    id: "idtipomovimientoActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un tipo de movimiento." },
  },
  {
    id: "cantidad_entradaActualizar",
    tipo: "input",
    regex: expresionesMovimientos.numeros_decimales,
    mensajes: { formato: "Cantidad de entrada inválida." },
    opcional: true
  },
  {
    id: "cantidad_salidaActualizar",
    tipo: "input",
    regex: expresionesMovimientos.numeros_decimales,
    mensajes: { formato: "Cantidad de salida inválida." },
    opcional: true
  },
  {
    id: "stock_anteriorActualizar",
    tipo: "input",
    regex: expresionesMovimientos.stock,
    mensajes: { formato: "Stock anterior inválido." },
    opcional: true
  },
  {
    id: "stock_resultanteActualizar",
    tipo: "input",
    regex: expresionesMovimientos.stock,
    mensajes: { formato: "Stock resultante inválido." },
    opcional: true
  },
  {
    id: "observacionesActualizar",
    tipo: "textarea",
    mensajes: {},
    opcional: true
  },
  {
    id: "estatusActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un estatus." },
  }
];


function mostrarModalPermisosDenegados(mensaje = "No tienes permisos para realizar esta acción.") {
  const modal = document.getElementById('modalPermisosDenegados');
  const mensajeElement = document.getElementById('mensajePermisosDenegados');
  
  if (modal && mensajeElement) {
    mensajeElement.textContent = mensaje;
    modal.classList.remove('opacity-0', 'pointer-events-none');
  } else {
    
    Swal.fire({
      icon: 'warning',
      title: 'Acceso Denegado',
      text: mensaje,
      confirmButtonColor: '#d33'
    });
  }
}


function cerrarModalPermisosDenegados() {
  const modal = document.getElementById('modalPermisosDenegados');
  if (modal) {
    modal.classList.add('opacity-0', 'pointer-events-none');
  }
}


function tienePermiso(accion) {
  return window.permisosMovimientos && window.permisosMovimientos[accion] === true;
}


function validarFormularioMovimiento(camposArray, formId) {
  const form = document.getElementById(formId);
  if (!form) return false;

  let formularioValido = true;
  let tieneEntrada = false;
  let tieneSalida = false;

  
  for (const campo of camposArray) {
    if (campo.opcional) continue;

    const inputElement = form.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValido = true;
    if (campo.tipo === "select") {
      esValido = validarSelect(campo.id, campo.mensajes, formId);
    } else if (["input", "textarea"].includes(campo.tipo)) {
      if (inputElement.value.trim() === "" && campo.mensajes && campo.mensajes.vacio) {
        esValido = false;
        mostrarErrorCampo(inputElement, campo.mensajes.vacio);
      }
    }
    
    if (!esValido) formularioValido = false;
  }

  
  const entradaField = form.querySelector('#cantidad_entrada, #cantidad_entradaActualizar');
  const salidaField = form.querySelector('#cantidad_salida, #cantidad_salidaActualizar');

  if (entradaField && entradaField.value.trim() !== '') {
    const valorEntrada = parseFloat(entradaField.value);
    if (valorEntrada > 0) {
      tieneEntrada = true;
      if (!expresionesMovimientos.numeros_decimales.test(entradaField.value)) {
        mostrarErrorCampo(entradaField, "Cantidad de entrada inválida");
        formularioValido = false;
      }
    }
  }

  if (salidaField && salidaField.value.trim() !== '') {
    const valorSalida = parseFloat(salidaField.value);
    if (valorSalida > 0) {
      tieneSalida = true;
      if (!expresionesMovimientos.numeros_decimales.test(salidaField.value)) {
        mostrarErrorCampo(salidaField, "Cantidad de salida inválida");
        formularioValido = false;
      }
    }
  }

  
  if (!tieneEntrada && !tieneSalida) {
    Swal.fire("Atención", "Debe especificar al menos una cantidad (entrada o salida).", "warning");
    formularioValido = false;
  }

  
  if (tieneEntrada && tieneSalida) {
    Swal.fire("Atención", "No puede tener cantidad de entrada y salida al mismo tiempo.", "warning");
    formularioValido = false;
  }

  return formularioValido;
}


function mostrarErrorCampo(inputElement, mensaje) {
  
  inputElement.classList.remove('border-green-500', 'ring-green-500');
  
  
  inputElement.classList.add('border-red-500', 'ring-red-500');
  
  
  let errorElement = inputElement.parentNode.querySelector('.error-message');
  if (!errorElement) {
    errorElement = document.createElement('span');
    errorElement.className = 'error-message text-red-500 text-sm mt-1 block';
    inputElement.parentNode.appendChild(errorElement);
  }
  
  errorElement.textContent = mensaje;
  errorElement.style.display = 'block';
}


function limpiarErroresCampo(inputElement) {
  inputElement.classList.remove('border-red-500', 'ring-red-500');
  inputElement.classList.add('border-green-500', 'ring-green-500');
  
  const errorElement = inputElement.parentNode.querySelector('.error-message');
  if (errorElement) {
    errorElement.style.display = 'none';
  }
}



/**
 * Cargar tipos de movimiento disponibles para filtros
 */
function cargarTiposMovimientoParaFiltros() {
  console.log("Cargando tipos de movimiento para select...");
  
  fetch("movimientos/getTiposMovimiento")
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        tiposMovimientoDisponibles = result.data;
        console.log(" Tipos de movimiento cargados:", tiposMovimientoDisponibles);
        
        
        llenarSelectFiltroTipos();
        
        
        mostrarEstadisticasMovimientos();
      } else {
        console.error("Error al cargar tipos de movimiento:", result.message);
      }
    })
    .catch(error => {
      console.error("Error en la solicitud de tipos:", error);
    });
}

/**
 * Llenar select de filtro por tipos
 */
function llenarSelectFiltroTipos() {
  const selectFiltro = document.getElementById('filtro-tipo-movimiento');
  if (!selectFiltro) {
    console.warn("No se encontró el select de filtro");
    return;
  }

  selectFiltro.innerHTML = '<option value="">Todos los tipos de movimiento</option>' + 
    tiposMovimientoDisponibles.map(t => `<option value="${t.idtipomovimiento}">${t.nombre}${t.descripcion ? ` - ${t.descripcion}` : ''}</option>`).join('');

  selectFiltro.addEventListener('change', function() {
    filtrarMovimientosPorTipo(this.value, this.options[this.selectedIndex].text);
  });
}

/**
 * Filtrar movimientos por tipo
 */
function filtrarMovimientosPorTipo(tipoId, nombreTipo) {
  console.log(`🔍 Filtrando por tipo: ${nombreTipo} (ID: ${tipoId})`);
  
  const indicadorFiltro = document.getElementById('indicador-filtro-actual');
  if (indicadorFiltro) {
    indicadorFiltro.innerHTML = tipoId 
      ? `<i class="fas fa-filter mr-1"></i>Filtrado por: ${nombreTipo}`
      : '<i class="fas fa-list mr-1"></i>Mostrando todos los movimientos';
    indicadorFiltro.className = `text-sm ${tipoId ? 'text-blue-600 font-medium' : 'text-gray-600'} flex items-center`;
  }

  if (tablaMovimientos) {
    if (!tipoId) {
      tablaMovimientos.column(3).search('').draw();
    } else {
      const tipoMovimiento = tiposMovimientoDisponibles.find(t => t.idtipomovimiento == tipoId);
      if (tipoMovimiento) {
        tablaMovimientos.column(3).search('^' + tipoMovimiento.nombre + '$', true, false).draw();
      }
    }
  }

  actualizarContadoresSimples();
}

/**
 * Mostrar estadísticas simples
 */
function mostrarEstadisticasMovimientos() {
  const contenedorStats = document.getElementById('estadisticas-movimientos');
  if (!contenedorStats) return;

  let statsHTML = `
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-exchange-alt text-blue-500 text-xl"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-blue-600">Total Movimientos</p>
            <p class="text-2xl font-bold text-blue-800" id="stat-total">0</p>
          </div>
        </div>
      </div>
      
      <div class="bg-green-50 p-4 rounded-lg border border-green-200">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-arrow-up text-green-500 text-xl"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-green-600">Entradas</p>
            <p class="text-2xl font-bold text-green-800" id="stat-entradas">0</p>
          </div>
        </div>
      </div>
      
      <div class="bg-red-50 p-4 rounded-lg border border-red-200">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-arrow-down text-red-500 text-xl"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-600">Salidas</p>
            <p class="text-2xl font-bold text-red-800" id="stat-salidas">0</p>
          </div>
        </div>
      </div>
      
      <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-tags text-purple-500 text-xl"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-purple-600">Tipos Activos</p>
            <p class="text-2xl font-bold text-purple-800" id="stat-tipos">${tiposMovimientoDisponibles.length}</p>
          </div>
        </div>
      </div>
    </div>
  `;
  
  contenedorStats.innerHTML = statsHTML;
}

/**
 * Actualizar contadores simples
 */
function actualizarContadoresSimples() {
  if (!tablaMovimientos) return;

  const datos = tablaMovimientos.rows({ search: 'applied' }).data().toArray();
  const stats = datos.reduce((acc, mov) => {
    if (parseFloat(mov.cantidad_entrada || 0) > 0) acc.entradas++;
    if (parseFloat(mov.cantidad_salida || 0) > 0) acc.salidas++;
    return acc;
  }, { entradas: 0, salidas: 0 });

  const statTotal = document.getElementById('stat-total');
  const statEntradas = document.getElementById('stat-entradas');
  const statSalidas = document.getElementById('stat-salidas');
  
  if (statTotal) statTotal.textContent = datos.length;
  if (statEntradas) statEntradas.textContent = stats.entradas;
  if (statSalidas) statSalidas.textContent = stats.salidas;

  console.log(`📊 Estadísticas actualizadas: Total: ${datos.length}, Entradas: ${stats.entradas}, Salidas: ${stats.salidas}`);
}

/**
 * Buscar movimientos con input de búsqueda
 */
function buscarMovimientosPersonalizado() {
  const inputBusqueda = document.getElementById('busqueda-movimientos');
  const criterio = inputBusqueda ? inputBusqueda.value.trim() : '';
  if (tablaMovimientos) {
    tablaMovimientos.search(criterio || '').draw();
    if (criterio) console.log(`🔍 Búsqueda personalizada: ${criterio}`);
  }
}

document.addEventListener("DOMContentLoaded", function () {
  
  const btnCerrarModalPermisos = document.getElementById('btnCerrarModalPermisos');
  
  if (btnCerrarModalPermisos) {
    btnCerrarModalPermisos.addEventListener('click', cerrarModalPermisosDenegados);
  }

  $(document).ready(function () {
    
    if (!tienePermiso('ver')) {
      console.warn('Sin permisos para ver movimientos');
      return;
    }

    
    cargarTiposMovimientoParaFiltros();

    
    if ($.fn.DataTable.isDataTable("#TablaMovimiento")) {
      $("#TablaMovimiento").DataTable().destroy();
    }

    tablaMovimientos = $("#TablaMovimiento").DataTable({
      processing: true,
      ajax: {
        url: "movimientos/getMovimientos",
        type: "GET",
        dataSrc: function (json) {
          console.log("📊 Respuesta del servidor:", json);
          
          if (json && json.status) {
            // Respuesta exitosa - puede tener datos o estar vacío
            setTimeout(() => {
              actualizarContadoresSimples();
            }, 500);
            
            return json.data || [];
          } else {
            // Error real o sin permisos
            console.error("Error en respuesta:", json);
            $("#TablaMovimiento_processing").css("display", "none");
            
            if (json && json.message && json.message.includes('permisos')) {
              mostrarModalPermisosDenegados(json.message);
            } else if (json && json.message) {
              // Solo mostrar alerta si hay un mensaje de error real
              Swal.fire("Error", json.message, "error");
            }
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Error AJAX:", textStatus, errorThrown);
          $("#TablaMovimiento_processing").css("display", "none");
          
          try {
            const response = JSON.parse(jqXHR.responseText);
            if (response && response.message && response.message.includes('permisos')) {
              mostrarModalPermisosDenegados(response.message);
              return;
            }
          } catch (e) {
            
          }
          
          Swal.fire("Error", "Error de comunicación al cargar movimientos.", "error");
        },
      },
      columns: [
        
        {
          data: null,
          title: "Nro",
          className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background break-all",
          render: function (data, type, row, meta) {
            return meta.row + 1;
          }
        },
        {
          data: "numero_movimiento",
          title: "Nro. Movimiento",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700 break-all",
          render: function (data, type, row) {
            return data || `MOV-${row.idmovimiento}`;
          }
        },
        {
          data: "producto_nombre",
          title: "Producto",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "tipo_movimiento",
          title: "Tipo",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "cantidad_entrada",
          title: "Entrada",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700 text-right",
          render: function (data, type, row) {
            return data && parseFloat(data) > 0 ? parseFloat(data).toFixed(2) : '-';
          },
        },
        {
          data: "cantidad_salida",
          title: "Salida",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700 text-right",
          render: function (data, type, row) {
            return data && parseFloat(data) > 0 ? parseFloat(data).toFixed(2) : '-';
          },
        },
        {
          data: "stock_resultante",
          title: "Stock Resultante",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700 text-right",
          render: function (data, type, row) {
            return data ? parseFloat(data).toFixed(2) : '0.00';
          }
        },
        {
          data: "estatus",
          title: "Estatus",
          className: "min-tablet-p text-center py-2 px-3",
          render: function (data, type, row) {
            if (data) {
              const estatusLower = String(data).toLowerCase();
              if (estatusLower === "activo") {
                return `<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
              } else {
                return `<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
              }
            }
            return '<span class="text-xs italic text-gray-500">N/A</span>';
          },
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          className: "all text-center actions-column py-1 px-2",
          width: "auto",
          render: function (data, type, row) {
            let acciones = '<div class="inline-flex items-center space-x-1">';
            
            const esAnulado = row.estatus === 'inactivo';
            const esVinculado = row.idcompra || row.idventa || row.idproduccion;
            const esMovimientoAutomatico = row.observaciones && (
              row.observaciones.includes('[ANULACIÓN AUTOMÁTICA]') || 
              row.observaciones.includes('[CORRECCIÓN AUTOMÁTICA]')
            );
            
            if (tienePermiso('ver')) {
              acciones += `
                <button class="ver-detalle-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idmovimiento="${row.idmovimiento}" 
                        title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>`;
            }
            
            if (!esAnulado && tienePermiso('editar')) {
              let tituloEditar = 'Editar';
              let claseEditar = 'text-blue-600 hover:text-blue-700';
              let deshabilitado = false;
              
              if (esMovimientoAutomatico) {
                tituloEditar = 'No editable (movimiento de anulación/corrección automática)';
                claseEditar = 'text-gray-400 cursor-not-allowed';
                deshabilitado = true;
              } else if (esVinculado) {
                tituloEditar = 'No editable (vinculado a operación)';
                claseEditar = 'text-gray-400 cursor-not-allowed';
                deshabilitado = true;
              }
              
              acciones += `
                <button class="editar-movimiento-btn ${claseEditar} p-1 transition-colors duration-150" 
                        data-idmovimiento="${row.idmovimiento}" 
                        ${deshabilitado ? 'disabled' : ''}
                        title="${tituloEditar}">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>`;
            }
            
            if (!esAnulado && tienePermiso('eliminar')) {
              let tituloEliminar = 'Anular movimiento';
              let claseEliminar = 'text-red-600 hover:text-red-700';
              let deshabilitado = false;
              
              if (esMovimientoAutomatico) {
                tituloEliminar = 'No anulable (movimiento de anulación/corrección automática)';
                claseEliminar = 'text-gray-400 cursor-not-allowed';
                deshabilitado = true;
              } else if (esVinculado) {
                tituloEliminar = 'No anulable (vinculado a operación)';
                claseEliminar = 'text-gray-400 cursor-not-allowed';
                deshabilitado = true;
              }
              
              acciones += `
                <button class="eliminar-movimiento-btn ${claseEliminar} p-1 transition-colors duration-150" 
                        data-idmovimiento="${row.idmovimiento}" 
                        ${deshabilitado ? 'disabled' : ''}
                        title="${tituloEliminar}">
                    <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>`;
            }
            
            if (esAnulado) {
              acciones += '<span class="text-xs text-gray-500 italic px-2">Anulado</span>';
            }
            
            if (!tienePermiso('ver') && !tienePermiso('editar') && !tienePermiso('eliminar')) {
              acciones += '<span class="text-gray-400 text-xs">Sin permisos</span>';
            }
            
            acciones += '</div>';
            return acciones;
          },
        },
      ],
      language: {
        processing: `
          <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]" style="margin-left:0;">
              <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                  <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
                  <span class="text-lg font-medium text-gray-700">Procesando...</span>
              </div>
          </div>`,
        emptyTable: '<div class="text-center py-4"><i class="fas fa-exchange-alt fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay movimientos disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ movimientos",
        infoEmpty: "Mostrando 0 movimientos",
        infoFiltered: "(filtrado de _MAX_ movimientos totales)",
        lengthMenu: "Mostrar _MENU_ movimientos",
        search: "_INPUT_",
        searchPlaceholder: "Buscar movimiento...",
        zeroRecords: '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
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
      order: [[0, "asc"]],
      scrollX: true,
      initComplete: function (settings, json) {
        window.tablaMovimientos = this.api();
        console.log(" DataTable inicializada correctamente");
        
        
        setTimeout(() => {
          actualizarContadoresSimples();
        }, 1000);
      },
      
      drawCallback: function(settings) {
        actualizarContadoresSimples();
      }
    });

    
    $("#TablaMovimiento tbody").on("click", ".ver-detalle-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('ver')) {
        mostrarModalPermisosDenegados("No tienes permisos para ver detalles de movimientos.");
        return;
      }
      
      const idMovimiento = $(this).data("idmovimiento");
      if (idMovimiento) {
        verDetalleMovimiento(idMovimiento);
      }
    });

    $("#TablaMovimiento tbody").on("click", ".editar-movimiento-btn", function (e) {
      e.preventDefault();
      
      if ($(this).prop('disabled')) {
        const titulo = $(this).attr('title');
        Swal.fire({
          icon: 'warning',
          title: 'Acción no permitida',
          text: titulo || 'Este movimiento no puede ser editado.',
          timer: 3000
        });
        return;
      }
      
      if (!tienePermiso('editar')) {
        mostrarModalPermisosDenegados("No tienes permisos para editar movimientos.");
        return;
      }
      
      const idMovimiento = $(this).data("idmovimiento");
      if (idMovimiento) {
        editarMovimiento(idMovimiento);
      }
    });

    $("#TablaMovimiento tbody").on("click", ".eliminar-movimiento-btn", function (e) {
      e.preventDefault();
      
      if ($(this).prop('disabled')) {
        const titulo = $(this).attr('title');
        Swal.fire({
          icon: 'warning',
          title: 'Acción no permitida',
          text: titulo || 'Este movimiento no puede ser anulado.',
          timer: 3000
        });
        return;
      }
      
      if (!tienePermiso('eliminar')) {
        mostrarModalPermisosDenegados("No tienes permisos para eliminar movimientos.");
        return;
      }
      
      const idMovimiento = $(this).data("idmovimiento");
      if (idMovimiento) {
        eliminarMovimiento(idMovimiento);
      }
    });
  });

  
  const btnAbrirModalMovimiento = document.getElementById("btnAbrirModalMovimiento");
  if (btnAbrirModalMovimiento) {
    btnAbrirModalMovimiento.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('crear')) {
        mostrarModalPermisosDenegados("No tienes permisos para crear movimientos.");
        return;
      }
      
      
      cargarDatosFormulario('registrar');
    });
  }

  
  const btnExportarMovimientos = document.getElementById("btnExportarMovimientos");
  if (btnExportarMovimientos) {
    btnExportarMovimientos.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('exportar')) {
        mostrarModalPermisosDenegados("No tienes permisos para exportar movimientos.");
        return;
      }
      
      exportarMovimientos();
    });
  }

  
  const busquedaPersonalizada = document.getElementById('busqueda-movimientos');
  if (busquedaPersonalizada) {
    busquedaPersonalizada.addEventListener('input', function() {
      buscarMovimientosPersonalizado();
    });
    
    busquedaPersonalizada.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        buscarMovimientosPersonalizado();
      }
    });
  }

/**
 * Configurar listener para botón de modal con confirmación
 */
function configurarBotonCancelar(btnId, modalId, formId, campos) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  
  btn.addEventListener("click", function(e) {
    e.preventDefault();
    
    Swal.fire({
      title: '¿Cancelar operación?',
      text: 'Se perderán los datos ingresados',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, cancelar',
      cancelButtonText: 'Continuar editando'
    }).then((result) => {
      if (result.isConfirmed) {
        cerrarModal(modalId);
        const form = document.getElementById(formId);
        if (form) {
          form.reset();
          limpiarValidaciones(campos, formId);
        }
      }
    });
  });
}

/**
 * Configurar listeners para cerrar modal simple
 */
function configurarBotonesCerrar(btnIds, modalId) {
  btnIds.forEach(btnId => {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        cerrarModal(modalId);
      });
    }
  });
}
  
  configurarBotonesCerrar(["btnCerrarModalDetalle", "btnCerrarModalDetalle2"], "modalVerDetalleMovimiento");
  configurarBotonesCerrar(["btnCerrarModalRegistrar"], "modalRegistrarMovimiento");
  configurarBotonesCerrar(["btnCerrarModalActualizar"], "modalActualizarMovimiento");
  
  configurarBotonCancelar("btnCancelarModalRegistrar", "modalRegistrarMovimiento", "formRegistrarMovimiento", camposFormularioMovimiento);
  configurarBotonCancelar("btnCancelarModalActualizar", "modalActualizarMovimiento", "formActualizarMovimiento", camposFormularioActualizarMovimiento);
  
  const formRegistrar = document.getElementById("formRegistrarMovimiento");
  const formActualizar = document.getElementById("formActualizarMovimiento");
  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!tienePermiso('crear')) {
        mostrarModalPermisosDenegados("No tienes permisos para crear movimientos.");
        return;
      }
      registrarMovimiento();
    });
  }
  
  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!tienePermiso('editar')) {
        mostrarModalPermisosDenegados("No tienes permisos para editar movimientos.");
        return;
      }
      actualizarMovimiento();
    });
  }

  
  /**
   * Configurar listeners de campos de cantidad (mutual exclusión entrada/salida)
   */
  const configurarCamposCantidad = (entradaId, salidaId) => {
    const entrada = document.getElementById(entradaId);
    const salida = document.getElementById(salidaId);
    
    if (entrada && salida) {
      entrada.addEventListener('input', function() {
        if (parseFloat(this.value) > 0) {
          salida.value = '';
          limpiarErroresCampo(this);
        }
      });
      
      salida.addEventListener('input', function() {
        if (parseFloat(this.value) > 0) {
          entrada.value = '';
          limpiarErroresCampo(this);
        }
      });
    }
  };
  
  configurarCamposCantidad('cantidad_entrada', 'cantidad_salida');
  configurarCamposCantidad('cantidad_entradaActualizar', 'cantidad_salidaActualizar');
  
  /**
   * Configurar cálculo automático de stock resultante
   */
  const configurarCalculoStock = (stockAnteriorId, stockResultanteId, entradaId, salidaId) => {
    const stockAnterior = document.getElementById(stockAnteriorId);
    const stockResultante = document.getElementById(stockResultanteId);
    const entrada = document.getElementById(entradaId);
    const salida = document.getElementById(salidaId);
    
    if (stockAnterior && stockResultante) {
      const calcular = () => {
        const anterior = parseFloat(stockAnterior.value) || 0;
        const ent = entrada ? parseFloat(entrada.value) || 0 : 0;
        const sal = salida ? parseFloat(salida.value) || 0 : 0;
        stockResultante.value = Math.max(0, anterior + ent - sal).toFixed(2);
      };
      
      stockAnterior.addEventListener('input', calcular);
      if (entrada) entrada.addEventListener('input', calcular);
      if (salida) salida.addEventListener('input', calcular);
    }
  };
  
  configurarCalculoStock('stock_anterior', 'stock_resultante', 'cantidad_entrada', 'cantidad_salida');
  configurarCalculoStock('stock_anteriorActualizar', 'stock_resultanteActualizar', 'cantidad_entradaActualizar', 'cantidad_salidaActualizar');
  
  /**
   * Limpiar errores al escribir en campos
   */
  const configurarLimpiezaErrores = (campos) => {
    campos.forEach(campo => {
      const elemento = document.getElementById(campo.id);
      if (elemento) {
        elemento.addEventListener(campo.tipo === 'select' ? 'change' : 'input', function() {
          if (this.value.trim() !== '') limpiarErroresCampo(this);
        });
      }
    });
  };
  
  configurarLimpiezaErrores(camposFormularioMovimiento);
  configurarLimpiezaErrores(camposFormularioActualizarMovimiento);

  console.log(" Todos los event listeners de movimientos han sido configurados");
});



/**
 * Cargar datos para formularios (productos y tipos de movimiento)
 */
function cargarDatosFormulario(modo = 'registrar') {
  console.log(`Cargando datos para formulario en modo: ${modo}`);
  
  fetch("movimientos/getDatosFormulario")
    .then(response => response.json())
    .then(result => {
      console.log(" Datos del formulario recibidos:", result);
      
      if (result.status && result.data) {
        const { productos, tipos_movimiento } = result.data;
        
        
        if (modo === 'registrar') {
          llenarSelectProductos('idproducto', productos);
          llenarSelectTiposMovimiento('idtipomovimiento', tipos_movimiento);
          abrirModal("modalRegistrarMovimiento");
          
          const form = document.getElementById("formRegistrarMovimiento");
          if (form) form.reset();
          limpiarValidaciones(camposFormularioMovimiento, "formRegistrarMovimiento");
          inicializarValidaciones(camposFormularioMovimiento, "formRegistrarMovimiento");
        } else if (modo === 'actualizar') {
          llenarSelectProductos('idproductoActualizar', productos);
          llenarSelectTiposMovimiento('idtipomovimientoActualizar', tipos_movimiento);
        }
      } else {
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "No se pudieron cargar los datos del formulario.", "error");
        }
      }
    })
    .catch(error => {
      console.error("Error al cargar datos del formulario:", error);
      Swal.fire("Error", "Error de conexión al cargar datos.", "error");
    });
}

/**
 * Llenar select de productos con auto-completado de stock
 */
function llenarSelectProductos(selectId, productos) {
  const select = document.getElementById(selectId);
  if (!select) {
    console.error(`No se encontró el select: ${selectId}`);
    return;
  }
  
  select.innerHTML = '<option value="">Seleccione un producto</option>' + 
    productos.map(p => `<option value="${p.idproducto}" data-stock="${p.stock_actual || 0}">${p.nombre} - Stock: ${p.stock_actual || 0}</option>`).join('');
  
  select.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stockActual = selectedOption ? selectedOption.getAttribute('data-stock') || 0 : 0;
    const isRegistro = selectId === 'idproducto';
    const stockAnteriorField = document.getElementById(isRegistro ? 'stock_anterior' : 'stock_anteriorActualizar');
    
    if (stockAnteriorField && this.value) {
      stockAnteriorField.value = parseFloat(stockActual).toFixed(2);
      stockAnteriorField.dispatchEvent(new Event('input'));
    }
  });
  
  console.log(`Select ${selectId} poblado con ${productos.length} productos`);
}

/**
 * Llenar select de tipos de movimiento
 */
function llenarSelectTiposMovimiento(selectId, tipos) {
  const select = document.getElementById(selectId);
  if (!select) {
    console.error(`No se encontró el select: ${selectId}`);
    return;
  }
  
  select.innerHTML = '<option value="">Seleccione un tipo</option>' + 
    tipos.map(t => `<option value="${t.idtipomovimiento}">${t.nombre}${t.descripcion ? ` - ${t.descripcion}` : ''}</option>`).join('');
  
  console.log(`Select ${selectId} poblado con ${tipos.length} tipos`);
}

/**
 * Ver detalle del movimiento
 */
function verDetalleMovimiento(idMovimiento) {
  if (!tienePermiso('ver')) {
    mostrarModalPermisosDenegados("No tienes permisos para ver detalles de movimientos.");
    return;
  }

  console.log(`👁️ Viendo detalle del movimiento: ${idMovimiento}`);

  fetch(`movimientos/getMovimientoById/${idMovimiento}`)
    .then(response => response.json())
    .then(result => {
      console.log(" Detalle del movimiento:", result);
      
      if (result.status && result.data) {
        const movimiento = result.data;
        
        
        document.getElementById("verMovimientoNumero").textContent = movimiento.numero_movimiento || `MOV-${movimiento.idmovimiento}`;
        document.getElementById("verMovimientoProducto").textContent = movimiento.producto_nombre || "N/A";
        document.getElementById("verMovimientoTipo").textContent = movimiento.tipo_movimiento || "N/A";
        
        
        let cantidadTexto = "N/A";
        if (movimiento.cantidad_entrada && parseFloat(movimiento.cantidad_entrada) > 0) {
          cantidadTexto = `${parseFloat(movimiento.cantidad_entrada).toFixed(2)} (Entrada)`;
        } else if (movimiento.cantidad_salida && parseFloat(movimiento.cantidad_salida) > 0) {
          cantidadTexto = `${parseFloat(movimiento.cantidad_salida).toFixed(2)} (Salida)`;
        }
        document.getElementById("verMovimientoCantidad").textContent = cantidadTexto;
        
        document.getElementById("verMovimientoStock").textContent = movimiento.stock_resultante ? parseFloat(movimiento.stock_resultante).toFixed(2) : "0.00";
        document.getElementById("verMovimientoEstatus").textContent = movimiento.estatus || "N/A";
        document.getElementById("verMovimientoObservaciones").textContent = movimiento.observaciones || "Sin observaciones";
        
        abrirModal("modalVerDetalleMovimiento");
      } else {
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "No se pudieron cargar los datos.", "error");
        }
      }
    })
    .catch(error => {
      console.error("Error al ver detalle:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

/**
 * Registrar nuevo movimiento
 */
function registrarMovimiento() {
  const btnRegistrar = document.getElementById("btnRegistrarMovimiento");
  
  const toggleButton = (loading) => {
    if (btnRegistrar) {
      btnRegistrar.disabled = loading;
      btnRegistrar.innerHTML = loading 
        ? `<i class="fas fa-spinner fa-spin mr-2"></i> Registrando...`
        : `<i class="fas fa-save mr-2"></i> Registrar Movimiento`;
    }
  };
  
  toggleButton(true);

  if (!validarFormularioMovimiento(camposFormularioMovimiento, "formRegistrarMovimiento")) {
    toggleButton(false);
    return;
  }

  registrarEntidad({
    formId: "formRegistrarMovimiento",
    endpoint: "movimientos/createMovimiento",
    campos: camposFormularioMovimiento,
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarMovimiento");
        if (tablaMovimientos && tablaMovimientos.ajax) tablaMovimientos.ajax.reload(null, false);

        const form = document.getElementById("formRegistrarMovimiento");
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioMovimiento, "formRegistrarMovimiento");
        }
      });
    },
    onError: (result) => {
      if (result.message && result.message.includes('permisos')) {
        mostrarModalPermisosDenegados(result.message);
      } else {
        Swal.fire("Error", result.message || "No se pudo registrar el movimiento.", "error");
      }
    }
  }).finally(() => toggleButton(false));
}

/**
 * Editar movimiento
 */
function editarMovimiento(idMovimiento) {
  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para editar movimientos.");
    return;
  }
  
  console.log(`✏️ Editando movimiento: ${idMovimiento}`);
  
  fetch(`movimientos/getMovimientoById/${idMovimiento}`)
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        mostrarModalEditarMovimiento(result.data);
      } else {
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "No se pudieron cargar los datos.", "error");
        }
      }
    })
    .catch(error => {
      console.error("Error al editar:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

/**
 * Mostrar modal de editar movimiento
 */
function mostrarModalEditarMovimiento(movimiento) {
  console.log(" Datos del movimiento recibidos:", movimiento);
  
  cargarDatosFormulario('actualizar');
  
  setTimeout(() => {
    const form = document.getElementById("formActualizarMovimiento");
    if (form) form.reset();
    limpiarValidaciones(camposFormularioActualizarMovimiento, "formActualizarMovimiento");

    const camposMap = {
      'idmovimientoActualizar': movimiento.idmovimiento,
      'numeroMovimientoActualizar': movimiento.numero_movimiento,
      'idproductoActualizar': movimiento.idproducto,
      'idtipomovimientoActualizar': movimiento.idtipomovimiento,
      'cantidad_entradaActualizar': movimiento.cantidad_entrada,
      'cantidad_salidaActualizar': movimiento.cantidad_salida,
      'stock_anteriorActualizar': movimiento.stock_anterior,
      'stock_resultanteActualizar': movimiento.stock_resultante,
      'observacionesActualizar': movimiento.observaciones,
      'estatusActualizar': movimiento.estatus
    };

    const elementosFaltantes = [];
    for (const [id, valor] of Object.entries(camposMap)) {
      const elemento = document.getElementById(id);
      if (!elemento) {
        elementosFaltantes.push(id);
      } else {
        elemento.value = valor || "";
      }
    }

    if (elementosFaltantes.length > 0) {
      console.error(" Elementos faltantes:", elementosFaltantes);
      Swal.fire("Error", "Error en la estructura del formulario. Faltan elementos: " + elementosFaltantes.join(', '), "error");
      return;
    }
    
    console.log(" Valores asignados al formulario de actualización");
    abrirModal("modalActualizarMovimiento");
    inicializarValidaciones(camposFormularioActualizarMovimiento, "formActualizarMovimiento");
  }, 1000);
}

/**
 * Actualizar movimiento
 */
function actualizarMovimiento() {
  const btnActualizar = document.getElementById("btnActualizarMovimiento");
  
  const toggleButton = (loading) => {
    if (btnActualizar) {
      btnActualizar.disabled = loading;
      btnActualizar.innerHTML = loading
        ? `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`
        : `<i class="fas fa-save mr-2"></i> Actualizar Movimiento`;
    }
  };
  
  toggleButton(true);

  if (!validarFormularioMovimiento(camposFormularioActualizarMovimiento, "formActualizarMovimiento")) {
    toggleButton(false);
    return;
  }

  const form = document.getElementById("formActualizarMovimiento");
  if (!form) {
    console.error("Formulario de actualización no encontrado");
    toggleButton(false);
    return;
  }

  const mapeoNombres = {
    "idmovimientoActualizar": "idmovimiento",
    "numeroMovimientoActualizar": "numero_movimiento",
    "idproductoActualizar": "idproducto",
    "idtipomovimientoActualizar": "idtipomovimiento",
    "cantidad_entradaActualizar": "cantidad_entrada",
    "cantidad_salidaActualizar": "cantidad_salida",
    "stock_anteriorActualizar": "stock_anterior",
    "stock_resultanteActualizar": "stock_resultante",
    "observacionesActualizar": "observaciones",
    "estatusActualizar": "estatus"
  };
  
  const formData = new FormData(form);
  const dataParaEnviar = {};
  for (let [key, value] of formData.entries()) {
    dataParaEnviar[mapeoNombres[key] || key] = value || "";
  }

  const idmovimiento = dataParaEnviar.idmovimiento;
  delete dataParaEnviar.idmovimiento;

  fetch("movimientos/updateMovimiento", {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify({ idmovimiento, nuevos_datos: dataParaEnviar }),
  })
  .then((response) => response.ok ? response.json() : response.json().then(err => { throw { status: response.status, data: err }; }))
  .then((result) => {
    if (result.status) {
      Swal.fire({
        icon: "success",
        title: "¡Movimiento corregido!",
        html: `
          <p>${result.message}</p>
          <p class="text-sm text-gray-600 mt-2">Movimiento anulación: ${result.data && result.data.numero_anulacion ? result.data.numero_anulacion : 'N/A'}</p>
          <p class="text-sm text-gray-600">Nuevo movimiento: ${result.data && result.data.numero_nuevo ? result.data.numero_nuevo : 'N/A'}</p>
        `,
        timer: 4000
      }).then(() => {
        cerrarModal("modalActualizarMovimiento");
        if (tablaMovimientos && tablaMovimientos.ajax) tablaMovimientos.ajax.reload(null, false);
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioActualizarMovimiento, "formActualizarMovimiento");
        }
      });
    } else {
      if (result.message && result.message.includes('permisos')) {
        mostrarModalPermisosDenegados(result.message);
      } else {
        Swal.fire("Error", result.message || "No se pudo actualizar el movimiento.", "error");
      }
    }
  })
  .catch((error) => {
    console.error("Error en actualización:", error);
    const errorMessage = (error.data && error.data.message) ? error.data.message : (error.status ? `Error del servidor: ${error.status}.` : "Ocurrió un error de conexión.");
    
    if (error.data && error.data.message && error.data.message.includes('permisos')) {
      mostrarModalPermisosDenegados(error.data.message);
    } else {
      Swal.fire("Error", errorMessage, "error");
    }
  })
  .finally(() => toggleButton(false));
}

/**
 * Eliminar movimiento
 */
function eliminarMovimiento(idMovimiento) {
  if (!tienePermiso('eliminar')) {
    mostrarModalPermisosDenegados("No tienes permisos para anular movimientos.");
    return;
  }
  
  Swal.fire({
    title: "¿Anular movimiento?",
    html: `
      <p>Se creará un movimiento de anulación automático que revertirá este movimiento.</p>
      <p class="text-sm text-gray-600 mt-2">Esta acción mantiene el historial completo para auditoría.</p>
    `,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, anular",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      console.log(`Anulando movimiento: ${idMovimiento}`);
      
      fetch("movimientos/anularMovimiento", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify({ idmovimiento: idMovimiento }),
      })
        .then(response => response.json())
        .then(result => {
          console.log("Respuesta anulación:", result);
          
          if (result.status) {
            Swal.fire({
              icon: "success",
              title: "¡Anulado!",
              html: `
                <p>${result.message}</p>
                <p class="text-sm text-gray-600 mt-2">Movimiento de anulación: ${result.data && result.data.numero_anulacion ? result.data.numero_anulacion : 'N/A'}</p>
              `,
              timer: 3000
            });
            if (tablaMovimientos && tablaMovimientos.ajax) tablaMovimientos.ajax.reload(null, false);
          } else {
            if (result.message && result.message.includes('permisos')) {
              mostrarModalPermisosDenegados(result.message);
            } else {
              Swal.fire("Error", result.message || "No se pudo anular.", "error");
            }
          }
        })
        .catch(error => {
          console.error("Error en anulación:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}

/**
 * Exportar movimientos
 */
function exportarMovimientos() {
  if (!tienePermiso('exportar')) {
    mostrarModalPermisosDenegados("No tienes permisos para exportar movimientos.");
    return;
  }

  Swal.fire({
    title: 'Exportando...',
    text: 'Preparando datos de movimientos',
    icon: 'info',
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });

  fetch("movimientos/exportarMovimientos", {
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then(response => response.json())
    .then(result => {
      Swal.close();
      
      if (result.status && result.data) {
        const headers = ['ID', 'Nro. Movimiento', 'Producto', 'Tipo', 'Entrada', 'Salida', 'Stock Resultante', 'Estatus', 'Fecha'];
        const csvContent = [
          headers.join(','),
          ...result.data.map(m => [
            m.idmovimiento || '',
            `"${m.numero_movimiento || `MOV-${m.idmovimiento}`}"`,
            `"${m.producto_nombre || ''}"`,
            `"${m.tipo_movimiento || ''}"`,
            m.cantidad_entrada || '',
            m.cantidad_salida || '',
            m.stock_resultante || '',
            `"${m.estatus || ''}"`,
            `"${m.fecha_creacion_formato || ''}"`
          ].join(','))
        ].join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
          const url = URL.createObjectURL(blob);
          link.setAttribute('href', url);
          link.setAttribute('download', 'movimientos_export.csv');
          link.style.visibility = 'hidden';
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        }
        
        Swal.fire("¡Éxito!", "Movimientos exportados correctamente.", "success");
      } else {
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "Error al exportar movimientos.", "error");
        }
      }
    })
    .catch(error => {
      console.error("Error en exportación:", error);
      Swal.close();
      Swal.fire("Error", "Error de conexión al exportar.", "error");
    });
}
