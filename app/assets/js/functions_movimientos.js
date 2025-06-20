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
let tiposMovimientoDisponibles = []; // ✅ VARIABLE GLOBAL

// ✅ MANTENER VALIDACIONES Y FUNCIONES DE PERMISOS (igual que antes)
const expresionesMovimientos = {
  numeros_decimales: /^\d+(\.\d{1,4})?$/,
  cantidad_positiva: /^[1-9]\d*(\.\d{1,3})?$/,
  stock: /^\d+(\.\d{1,3})?$/
};

// ✅ CAMPOS DE VALIDACIÓN (mantener igual)
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

// ✅ FUNCIÓN PARA MOSTRAR MODAL DE PERMISOS DENEGADOS
function mostrarModalPermisosDenegados(mensaje = "No tienes permisos para realizar esta acción.") {
  const modal = document.getElementById('modalPermisosDenegados');
  const mensajeElement = document.getElementById('mensajePermisosDenegados');
  
  if (modal && mensajeElement) {
    mensajeElement.textContent = mensaje;
    modal.classList.remove('opacity-0', 'pointer-events-none');
  } else {
    // Fallback con SweetAlert si no existe el modal
    Swal.fire({
      icon: 'warning',
      title: 'Acceso Denegado',
      text: mensaje,
      confirmButtonColor: '#d33'
    });
  }
}

// ✅ FUNCIÓN PARA CERRAR MODAL DE PERMISOS DENEGADOS
function cerrarModalPermisosDenegados() {
  const modal = document.getElementById('modalPermisosDenegados');
  if (modal) {
    modal.classList.add('opacity-0', 'pointer-events-none');
  }
}

// ✅ VERIFICAR SI TIENE PERMISOS
function tienePermiso(accion) {
  return window.permisosMovimientos && window.permisosMovimientos[accion] === true;
}

// ✅ VALIDACIÓN PERSONALIZADA PARA MOVIMIENTOS
function validarFormularioMovimiento(camposArray, formId) {
  const form = document.getElementById(formId);
  if (!form) return false;

  let formularioValido = true;
  let tieneEntrada = false;
  let tieneSalida = false;

  // ✅ VALIDAR CAMPOS OBLIGATORIOS PRIMERO
  for (const campo of camposArray) {
    if (campo.opcional) continue;

    const inputElement = form.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValido = true;
    if (campo.tipo === "select") {
      esValido = validarSelect(campo.id, campo.mensajes, formId);
    } else if (["input", "textarea"].includes(campo.tipo)) {
      if (inputElement.value.trim() === "" && campo.mensajes?.vacio) {
        esValido = false;
        mostrarErrorCampo(inputElement, campo.mensajes.vacio);
      }
    }
    
    if (!esValido) formularioValido = false;
  }

  // ✅ VALIDAR CANTIDADES ESPECÍFICAS
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

  // ✅ VALIDAR QUE TENGA AL MENOS UNA CANTIDAD
  if (!tieneEntrada && !tieneSalida) {
    Swal.fire("Atención", "Debe especificar al menos una cantidad (entrada o salida).", "warning");
    formularioValido = false;
  }

  // ✅ VALIDAR QUE NO TENGA AMBAS CANTIDADES
  if (tieneEntrada && tieneSalida) {
    Swal.fire("Atención", "No puede tener cantidad de entrada y salida al mismo tiempo.", "warning");
    formularioValido = false;
  }

  return formularioValido;
}

// ✅ FUNCIÓN PARA MOSTRAR ERROR EN CAMPO
function mostrarErrorCampo(inputElement, mensaje) {
  // Remover clases de éxito
  inputElement.classList.remove('border-green-500', 'ring-green-500');
  
  // Agregar clases de error
  inputElement.classList.add('border-red-500', 'ring-red-500');
  
  // Buscar o crear elemento de mensaje de error
  let errorElement = inputElement.parentNode.querySelector('.error-message');
  if (!errorElement) {
    errorElement = document.createElement('span');
    errorElement.className = 'error-message text-red-500 text-sm mt-1 block';
    inputElement.parentNode.appendChild(errorElement);
  }
  
  errorElement.textContent = mensaje;
  errorElement.style.display = 'block';
}

// ✅ FUNCIÓN PARA LIMPIAR ERRORES DE CAMPO
function limpiarErroresCampo(inputElement) {
  inputElement.classList.remove('border-red-500', 'ring-red-500');
  inputElement.classList.add('border-green-500', 'ring-green-500');
  
  const errorElement = inputElement.parentNode.querySelector('.error-message');
  if (errorElement) {
    errorElement.style.display = 'none';
  }
}

// ✅ NUEVAS FUNCIONES PARA FILTROS POR TIPO DE MOVIMIENTO

/**
 * Cargar tipos de movimiento disponibles para filtros
 */
function cargarTiposMovimientoParaFiltros() {
  console.log("🔄 Cargando tipos de movimiento para select...");
  
  fetch("movimientos/getTiposMovimiento")
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        tiposMovimientoDisponibles = result.data;
        console.log("📋 Tipos de movimiento cargados:", tiposMovimientoDisponibles);
        
        // ✅ LLENAR SELECT
        llenarSelectFiltroTipos();
        
        // ✅ MOSTRAR ESTADÍSTICAS SIMPLES
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

  // ✅ OPCIÓN "TODOS"
  let optionsHTML = '<option value="">Todos los tipos de movimiento</option>';
  
  // ✅ OPCIONES POR CADA TIPO
  tiposMovimientoDisponibles.forEach(tipo => {
    const descripcion = tipo.descripcion ? ` - ${tipo.descripcion}` : '';
    optionsHTML += `<option value="${tipo.idtipomovimiento}">${tipo.nombre}${descripcion}</option>`;
  });

  selectFiltro.innerHTML = optionsHTML;

  // ✅ EVENT LISTENER PARA FILTRAR
  selectFiltro.addEventListener('change', function() {
    const tipoSeleccionado = this.value;
    const nombreTipo = this.options[this.selectedIndex].text;
    
    filtrarMovimientosPorTipo(tipoSeleccionado, nombreTipo);
  });
}

/**
 * Filtrar movimientos por tipo (simplificado)
 */
function filtrarMovimientosPorTipo(tipoId, nombreTipo) {
  console.log(`🔍 Filtrando por tipo: ${nombreTipo} (ID: ${tipoId})`);
  
  // ✅ ACTUALIZAR INDICADOR
  const indicadorFiltro = document.getElementById('indicador-filtro-actual');
  if (indicadorFiltro) {
    if (!tipoId) {
      indicadorFiltro.innerHTML = '<i class="fas fa-list mr-1"></i>Mostrando todos los movimientos';
      indicadorFiltro.className = 'text-sm text-gray-600 flex items-center';
    } else {
      indicadorFiltro.innerHTML = `<i class="fas fa-filter mr-1"></i>Filtrado por: ${nombreTipo}`;
      indicadorFiltro.className = 'text-sm text-blue-600 font-medium flex items-center';
    }
  }

  // ✅ FILTRAR DATATABLE
  if (tablaMovimientos) {
    if (!tipoId) {
      // Mostrar todos
      tablaMovimientos.column(3).search('').draw(); // Columna de tipo de movimiento
    } else {
      // Buscar por nombre del tipo
      const tipoMovimiento = tiposMovimientoDisponibles.find(t => t.idtipomovimiento == tipoId);
      if (tipoMovimiento) {
        tablaMovimientos.column(3).search('^' + tipoMovimiento.nombre + '$', true, false).draw();
      }
    }
  }

  // ✅ ACTUALIZAR CONTADORES
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
  
  // ✅ CONTAR TOTALES
  const totalMovimientos = datos.length;
  
  let totalEntradas = 0;
  let totalSalidas = 0;
  
  datos.forEach(movimiento => {
    const entrada = parseFloat(movimiento.cantidad_entrada || 0);
    const salida = parseFloat(movimiento.cantidad_salida || 0);
    
    if (entrada > 0) totalEntradas++;
    if (salida > 0) totalSalidas++;
  });

  // ✅ ACTUALIZAR DOM
  const statTotal = document.getElementById('stat-total');
  const statEntradas = document.getElementById('stat-entradas');
  const statSalidas = document.getElementById('stat-salidas');
  
  if (statTotal) statTotal.textContent = totalMovimientos;
  if (statEntradas) statEntradas.textContent = totalEntradas;
  if (statSalidas) statSalidas.textContent = totalSalidas;

  console.log(`📊 Estadísticas actualizadas: Total: ${totalMovimientos}, Entradas: ${totalEntradas}, Salidas: ${totalSalidas}`);
}

/**
 * Buscar movimientos con input de búsqueda
 */
function buscarMovimientosPersonalizado() {
  const criterio = document.getElementById('busqueda-movimientos')?.value?.trim();
  
  if (!criterio) {
    if (tablaMovimientos) {
      tablaMovimientos.search('').draw();
    }
    return;
  }

  console.log(`🔍 Búsqueda personalizada: ${criterio}`);
  
  if (tablaMovimientos) {
    tablaMovimientos.search(criterio).draw();
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // ✅ MANTENER EVENT LISTENERS DE PERMISOS
  const btnCerrarModalPermisos = document.getElementById('btnCerrarModalPermisos');
  
  if (btnCerrarModalPermisos) {
    btnCerrarModalPermisos.addEventListener('click', cerrarModalPermisosDenegados);
  }

  $(document).ready(function () {
    // ✅ VERIFICAR PERMISOS
    if (!tienePermiso('ver')) {
      console.warn('Sin permisos para ver movimientos');
      return;
    }

    // ✅ CARGAR TIPOS PARA SELECT
    cargarTiposMovimientoParaFiltros();

    // ✅ INICIALIZAR DATATABLE (mantener igual pero agregar callback)
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
          
          if (json && json.status && json.data) {
            // ✅ ACTUALIZAR CONTADORES DESPUÉS DE CARGAR
            setTimeout(() => {
              actualizarContadoresSimples();
            }, 500);
            
            return json.data;
          } else {
            console.error("Error en respuesta:", json);
            $("#TablaMovimiento_processing").css("display", "none");
            
            if (json && json.message && json.message.includes('permisos')) {
              mostrarModalPermisosDenegados(json.message);
            } else {
              Swal.fire("Error", json?.message || "No se pudieron cargar los datos de movimientos.", "error");
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
            // No es JSON válido, continuar con error genérico
          }
          
          Swal.fire("Error", "Error de comunicación al cargar movimientos.", "error");
        },
      },
      columns: [
        // ✅ MANTENER TODAS LAS COLUMNAS IGUAL
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
            
            // ✅ VER - Solo si tiene permisos
            if (tienePermiso('ver')) {
              acciones += `
                <button class="ver-detalle-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idmovimiento="${row.idmovimiento}" 
                        title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>`;
            }
            
            // ✅ EDITAR - Solo si tiene permisos
            if (tienePermiso('editar')) {
              acciones += `
                <button class="editar-movimiento-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                        data-idmovimiento="${row.idmovimiento}" 
                        title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>`;
            }
            
            // ✅ ELIMINAR - Solo si tiene permisos
            if (tienePermiso('eliminar')) {
              acciones += `
                <button class="eliminar-movimiento-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                        data-idmovimiento="${row.idmovimiento}" 
                        title="Eliminar">
                    <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>`;
            }
            
            // Si no tiene permisos para ninguna acción, mostrar mensaje
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
        console.log("✅ DataTable inicializada correctamente");
        
        // ✅ ACTUALIZAR CONTADORES
        setTimeout(() => {
          actualizarContadoresSimples();
        }, 1000);
      },
      // ✅ CALLBACK PARA ACTUALIZAR CONTADORES AL FILTRAR
      drawCallback: function(settings) {
        actualizarContadoresSimples();
      }
    });

    // ✅ EVENT LISTENERS CON VERIFICACIÓN DE PERMISOS (mantener igual)
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

  // ✅ BOTÓN REGISTRAR CON VERIFICACIÓN DE PERMISOS (mantener igual)
  const btnAbrirModalMovimiento = document.getElementById("btnAbrirModalMovimiento");
  if (btnAbrirModalMovimiento) {
    btnAbrirModalMovimiento.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('crear')) {
        mostrarModalPermisosDenegados("No tienes permisos para crear movimientos.");
        return;
      }
      
      // ✅ CARGAR DATOS PARA FORMULARIO
      cargarDatosFormulario('registrar');
    });
  }

  // ✅ BOTÓN EXPORTAR CON VERIFICACIÓN DE PERMISOS (mantener igual)
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

  // ✅ CAMPO DE BÚSQUEDA PERSONALIZADA
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

  // ✅ EVENT LISTENERS PARA MODAL VER DETALLE
  const btnCerrarModalDetalle = document.getElementById("btnCerrarModalDetalle");
  const btnCerrarModalDetalle2 = document.getElementById("btnCerrarModalDetalle2");
  
  if (btnCerrarModalDetalle) {
    btnCerrarModalDetalle.addEventListener("click", function(e) {
      e.preventDefault();
      console.log("🔒 Cerrando modal de detalle");
      cerrarModal("modalVerDetalleMovimiento");
    });
  }
  
  if (btnCerrarModalDetalle2) {
    btnCerrarModalDetalle2.addEventListener("click", function(e) {
      e.preventDefault();
      console.log("🔒 Cerrando modal de detalle (botón 2)");
      cerrarModal("modalVerDetalleMovimiento");
    });
  }

  // ✅ EVENT LISTENERS PARA MODAL REGISTRAR
  const btnCerrarModalRegistrar = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistrar = document.getElementById("btnCancelarModalRegistrar");
  const formRegistrar = document.getElementById("formRegistrarMovimiento");

  if (btnCerrarModalRegistrar) {
    btnCerrarModalRegistrar.addEventListener("click", function(e) {
      e.preventDefault();
      console.log("🔒 Cerrando modal de registro");
      cerrarModal("modalRegistrarMovimiento");
    });
  }

  if (btnCancelarModalRegistrar) {
    btnCancelarModalRegistrar.addEventListener("click", function(e) {
      e.preventDefault();
      console.log("❌ Cancelando registro de movimiento");
      
      Swal.fire({
        title: '¿Cancelar registro?',
        text: 'Se perderán los datos ingresados',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Continuar editando'
      }).then((result) => {
        if (result.isConfirmed) {
          cerrarModal("modalRegistrarMovimiento");
          
          const form = document.getElementById("formRegistrarMovimiento");
          if (form) {
            form.reset();
            limpiarValidaciones(camposFormularioMovimiento, "formRegistrarMovimiento");
          }
        }
      });
    });
  }

  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('crear')) {
        mostrarModalPermisosDenegados("No tienes permisos para crear movimientos.");
        return;
      }
      
      console.log("💾 Enviando formulario de registro");
      registrarMovimiento();
    });
  }

  // ✅ EVENT LISTENERS PARA MODAL ACTUALIZAR
  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarMovimiento");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function(e) {
      e.preventDefault();
      console.log("🔒 Cerrando modal de actualización");
      cerrarModal("modalActualizarMovimiento");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function(e) {
      e.preventDefault();
      console.log("❌ Cancelando actualización de movimiento");
      
      Swal.fire({
        title: '¿Cancelar actualización?',
        text: 'Se perderán los cambios realizados',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Continuar editando'
      }).then((result) => {
        if (result.isConfirmed) {
          cerrarModal("modalActualizarMovimiento");
          
          const form = document.getElementById("formActualizarMovimiento");
          if (form) {
            form.reset();
            limpiarValidaciones(camposFormularioActualizarMovimiento, "formActualizarMovimiento");
          }
        }
      });
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('editar')) {
        mostrarModalPermisosDenegados("No tienes permisos para editar movimientos.");
        return;
      }
      
      console.log("💾 Enviando formulario de actualización");
      actualizarMovimiento();
    });
  }

  // ✅ EVENT LISTENERS PARA VALIDACIÓN EN TIEMPO REAL
  
  // Validación de cantidad entrada/salida en registro
  const cantidadEntradaReg = document.getElementById('cantidad_entrada');
  const cantidadSalidaReg = document.getElementById('cantidad_salida');
  
  if (cantidadEntradaReg) {
    cantidadEntradaReg.addEventListener('input', function() {
      const valor = parseFloat(this.value) || 0;
      if (valor > 0 && cantidadSalidaReg) {
        cantidadSalidaReg.value = '';
        limpiarErroresCampo(this);
      }
    });
  }
  
  if (cantidadSalidaReg) {
    cantidadSalidaReg.addEventListener('input', function() {
      const valor = parseFloat(this.value) || 0;
      if (valor > 0 && cantidadEntradaReg) {
        cantidadEntradaReg.value = '';
        limpiarErroresCampo(this);
      }
    });
  }

  // Validación de cantidad entrada/salida en actualización
  const cantidadEntradaAct = document.getElementById('cantidad_entradaActualizar');
  const cantidadSalidaAct = document.getElementById('cantidad_salidaActualizar');
  
  if (cantidadEntradaAct) {
    cantidadEntradaAct.addEventListener('input', function() {
      const valor = parseFloat(this.value) || 0;
      if (valor > 0 && cantidadSalidaAct) {
        cantidadSalidaAct.value = '';
        limpiarErroresCampo(this);
      }
    });
  }
  
  if (cantidadSalidaAct) {
    cantidadSalidaAct.addEventListener('input', function() {
      const valor = parseFloat(this.value) || 0;
      if (valor > 0 && cantidadEntradaAct) {
        cantidadEntradaAct.value = '';
        limpiarErroresCampo(this);
      }
    });
  }

  // ✅ EVENT LISTENERS PARA CALCULAR STOCK AUTOMÁTICAMENTE
  
  // Cálculo automático de stock en registro
  const stockAnteriorReg = document.getElementById('stock_anterior');
  const stockResultanteReg = document.getElementById('stock_resultante');
  
  if (stockAnteriorReg && stockResultanteReg) {
    const calcularStockRegistro = function() {
      const stockAnterior = parseFloat(stockAnteriorReg.value) || 0;
      const entrada = parseFloat(cantidadEntradaReg?.value) || 0;
      const salida = parseFloat(cantidadSalidaReg?.value) || 0;
      
      let nuevoStock = stockAnterior;
      if (entrada > 0) {
        nuevoStock = stockAnterior + entrada;
      } else if (salida > 0) {
        nuevoStock = stockAnterior - salida;
      }
      
      stockResultanteReg.value = Math.max(0, nuevoStock).toFixed(2);
    };
    
    stockAnteriorReg.addEventListener('input', calcularStockRegistro);
    if (cantidadEntradaReg) cantidadEntradaReg.addEventListener('input', calcularStockRegistro);
    if (cantidadSalidaReg) cantidadSalidaReg.addEventListener('input', calcularStockRegistro);
  }

  // Cálculo automático de stock en actualización
  const stockAnteriorAct = document.getElementById('stock_anteriorActualizar');
  const stockResultanteAct = document.getElementById('stock_resultanteActualizar');
  
  if (stockAnteriorAct && stockResultanteAct) {
    const calcularStockActualizacion = function() {
      const stockAnterior = parseFloat(stockAnteriorAct.value) || 0;
      const entrada = parseFloat(cantidadEntradaAct?.value) || 0;
      const salida = parseFloat(cantidadSalidaAct?.value) || 0;
      
      let nuevoStock = stockAnterior;
      if (entrada > 0) {
        nuevoStock = stockAnterior + entrada;
      } else if (salida > 0) {
        nuevoStock = stockAnterior - salida;
      }
      
      stockResultanteAct.value = Math.max(0, nuevoStock).toFixed(2);
    };
    
    stockAnteriorAct.addEventListener('input', calcularStockActualizacion);
    if (cantidadEntradaAct) cantidadEntradaAct.addEventListener('input', calcularStockActualizacion);
    if (cantidadSalidaAct) cantidadSalidaAct.addEventListener('input', calcularStockActualizacion);
  }

  // ✅ EVENT LISTENERS PARA LIMPIAR ERRORES AL ESCRIBIR
  
  // Limpiar errores en campos de formulario de registro
  camposFormularioMovimiento.forEach(campo => {
    const elemento = document.getElementById(campo.id);
    if (elemento) {
      elemento.addEventListener('input', function() {
        if (this.value.trim() !== '') {
          limpiarErroresCampo(this);
        }
      });
      
      if (campo.tipo === 'select') {
        elemento.addEventListener('change', function() {
          if (this.value !== '') {
            limpiarErroresCampo(this);
          }
        });
      }
    }
  });

  // Limpiar errores en campos de formulario de actualización
  camposFormularioActualizarMovimiento.forEach(campo => {
    const elemento = document.getElementById(campo.id);
    if (elemento) {
      elemento.addEventListener('input', function() {
        if (this.value.trim() !== '') {
          limpiarErroresCampo(this);
        }
      });
      
      if (campo.tipo === 'select') {
        elemento.addEventListener('change', function() {
          if (this.value !== '') {
            limpiarErroresCampo(this);
          }
        });
      }
    }
  });

  console.log("✅ Todos los event listeners de movimientos han sido configurados");
});

// ✅ FUNCIONES ESPECÍFICAS

/**
 * Cargar datos para formularios (productos y tipos de movimiento)
 */
function cargarDatosFormulario(modo = 'registrar') {
  console.log(`🔄 Cargando datos para formulario en modo: ${modo}`);
  
  fetch("movimientos/getDatosFormulario")
    .then(response => response.json())
    .then(result => {
      console.log("📋 Datos del formulario recibidos:", result);
      
      if (result.status && result.data) {
        const { productos, tipos_movimiento } = result.data;
        
        // ✅ LLENAR SELECTS SEGÚN EL MODO
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
 * Llenar select de productos - CORREGIDO
 */
function llenarSelectProductos(selectId, productos) {
  const select = document.getElementById(selectId);
  if (select) {
    select.innerHTML = '<option value="">Seleccione un producto</option>';
    productos.forEach(producto => {
      // ✅ NO USAR CAMPO 'codigo' QUE NO EXISTE
      select.innerHTML += `<option value="${producto.idproducto}">${producto.nombre} - Stock: ${producto.stock_actual || 0}</option>`;
    });
    console.log(`✅ Select ${selectId} poblado con ${productos.length} productos`);
  } else {
    console.error(`❌ No se encontró el select: ${selectId}`);
  }
}

/**
 * Llenar select de tipos de movimiento
 */
function llenarSelectTiposMovimiento(selectId, tipos) {
  const select = document.getElementById(selectId);
  if (select) {
    select.innerHTML = '<option value="">Seleccione un tipo</option>';
    tipos.forEach(tipo => {
      const descripcion = tipo.descripcion ? ` - ${tipo.descripcion}` : '';
      select.innerHTML += `<option value="${tipo.idtipomovimiento}">${tipo.nombre}${descripcion}</option>`;
    });
    console.log(`✅ Select ${selectId} poblado con ${tipos.length} tipos`);
  } else {
    console.error(`❌ No se encontró el select: ${selectId}`);
  }
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
      console.log("📋 Detalle del movimiento:", result);
      
      if (result.status && result.data) {
        const movimiento = result.data;
        
        // ✅ ASIGNAR DATOS USANDO LA ESTRUCTURA CORRECTA
        document.getElementById("verMovimientoNumero").textContent = movimiento.numero_movimiento || `MOV-${movimiento.idmovimiento}`;
        document.getElementById("verMovimientoProducto").textContent = movimiento.producto_nombre || "N/A";
        document.getElementById("verMovimientoTipo").textContent = movimiento.tipo_movimiento || "N/A";
        
        // ✅ MOSTRAR CANTIDAD CORRECTA (ENTRADA O SALIDA)
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
 * Registrar nuevo movimiento - CORREGIDO
 */
function registrarMovimiento() {
  const btnRegistrarMovimiento = document.getElementById("btnRegistrarMovimiento");
  
  if (btnRegistrarMovimiento) {
    btnRegistrarMovimiento.disabled = true;
    btnRegistrarMovimiento.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Registrando...`;
  }

  // ✅ USAR VALIDACIÓN PERSONALIZADA
  if (!validarFormularioMovimiento(camposFormularioMovimiento, "formRegistrarMovimiento")) {
    if (btnRegistrarMovimiento) {
      btnRegistrarMovimiento.disabled = false;
      btnRegistrarMovimiento.innerHTML = `<i class="fas fa-save mr-2"></i> Registrar Movimiento`;
    }
    return;
  }

  registrarEntidad({
    formId: "formRegistrarMovimiento",
    endpoint: "movimientos/createMovimiento",
    campos: camposFormularioMovimiento,
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarMovimiento");
        if (tablaMovimientos && tablaMovimientos.ajax) {
          tablaMovimientos.ajax.reload(null, false);
        }

        const formRegistrar = document.getElementById("formRegistrarMovimiento");
        if (formRegistrar) {
          formRegistrar.reset();
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
  }).finally(() => {
    if (btnRegistrarMovimiento) {
      btnRegistrarMovimiento.disabled = false;
      btnRegistrarMovimiento.innerHTML = `<i class="fas fa-save mr-2"></i> Registrar Movimiento`;
    }
  });
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
 * Mostrar modal de editar movimiento - CORREGIDO
 */
function mostrarModalEditarMovimiento(movimiento) {
  console.log("📋 Datos del movimiento recibidos:", movimiento);
  
  // ✅ CARGAR DATOS PRIMERO
  cargarDatosFormulario('actualizar');
  
  // ✅ ESPERAR A QUE SE CARGUEN LOS SELECTS Y LUEGO ASIGNAR VALORES
  setTimeout(() => {
    const formActualizar = document.getElementById("formActualizarMovimiento");
    if (formActualizar) formActualizar.reset();
    limpiarValidaciones(camposFormularioActualizarMovimiento, "formActualizarMovimiento");

    // ✅ ASIGNAR VALORES A LOS CAMPOS
    const elementos = {
      idmovimientoActualizar: document.getElementById("idmovimientoActualizar"),
      numeroMovimientoActualizar: document.getElementById("numeroMovimientoActualizar"),
      idproductoActualizar: document.getElementById("idproductoActualizar"),
      idtipomovimientoActualizar: document.getElementById("idtipomovimientoActualizar"),
      cantidad_entradaActualizar: document.getElementById("cantidad_entradaActualizar"),
      cantidad_salidaActualizar: document.getElementById("cantidad_salidaActualizar"),
      stock_anteriorActualizar: document.getElementById("stock_anteriorActualizar"),
      stock_resultanteActualizar: document.getElementById("stock_resultanteActualizar"),
      observacionesActualizar: document.getElementById("observacionesActualizar"),
      estatusActualizar: document.getElementById("estatusActualizar")
    };

    // ✅ VERIFICAR ELEMENTOS FALTANTES
    const elementosFaltantes = [];
    for (const [nombre, elemento] of Object.entries(elementos)) {
      if (!elemento) {
        elementosFaltantes.push(nombre);
      }
    }

    if (elementosFaltantes.length > 0) {
      console.error("❌ Elementos faltantes en el modal:", elementosFaltantes);
      Swal.fire("Error", "Error en la estructura del formulario. Faltan elementos: " + elementosFaltantes.join(', '), "error");
      return;
    }

    // ✅ ASIGNAR VALORES
    elementos.idmovimientoActualizar.value = movimiento.idmovimiento || "";
    elementos.numeroMovimientoActualizar.value = movimiento.numero_movimiento || "";
    elementos.cantidad_entradaActualizar.value = movimiento.cantidad_entrada || "";
    elementos.cantidad_salidaActualizar.value = movimiento.cantidad_salida || "";
    elementos.stock_anteriorActualizar.value = movimiento.stock_anterior || "";
    elementos.stock_resultanteActualizar.value = movimiento.stock_resultante || "";
    elementos.observacionesActualizar.value = movimiento.observaciones || "";
    elementos.estatusActualizar.value = movimiento.estatus || "";

    // ✅ ESTABLECER VALORES SELECCIONADOS
    if (movimiento.idproducto) {
      elementos.idproductoActualizar.value = movimiento.idproducto;
    }
    if (movimiento.idtipomovimiento) {
      elementos.idtipomovimientoActualizar.value = movimiento.idtipomovimiento;
    }
    
    console.log("✅ Valores asignados al formulario de actualización");
    
    abrirModal("modalActualizarMovimiento");
    inicializarValidaciones(camposFormularioActualizarMovimiento, "formActualizarMovimiento");
  }, 1000); // ✅ AUMENTAR TIEMPO DE ESPERA
}

/**
 * Actualizar movimiento - CORREGIDO
 */
function actualizarMovimiento() {
  const btnActualizarMovimiento = document.getElementById("btnActualizarMovimiento");
  
  if (btnActualizarMovimiento) {
    btnActualizarMovimiento.disabled = true;
    btnActualizarMovimiento.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  // ✅ USAR VALIDACIÓN PERSONALIZADA 
  if (!validarFormularioMovimiento(camposFormularioActualizarMovimiento, "formActualizarMovimiento")) {
    if (btnActualizarMovimiento) {
      btnActualizarMovimiento.disabled = false;
      btnActualizarMovimiento.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Movimiento`;
    }
    return;
  }

  const form = document.getElementById("formActualizarMovimiento");
  if (!form) {
    console.error("Formulario de actualización no encontrado");
    if (btnActualizarMovimiento) {
      btnActualizarMovimiento.disabled = false;
      btnActualizarMovimiento.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Movimiento`;
    }
    return;
  }

  // ✅ PREPARAR DATOS CON MAPEO
  const formData = new FormData(form);
  const dataParaEnviar = {};
  
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
  
  for (let [key, value] of formData.entries()) {
    const nombreFinal = mapeoNombres[key] || key;
    dataParaEnviar[nombreFinal] = value || "";
  }

  console.log("📤 Datos a enviar:", dataParaEnviar);

  // ✅ ENVIAR DATOS
  fetch("movimientos/updateMovimiento", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(dataParaEnviar),
  })
  .then((response) => {
    if (!response.ok) {
      return response.json().then((errData) => {
        throw { status: response.status, data: errData };
      });
    }
    return response.json();
  })
  .then((result) => {
    console.log("📥 Respuesta del servidor:", result);
    
    if (result.status) {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalActualizarMovimiento");
        if (tablaMovimientos && tablaMovimientos.ajax) {
          tablaMovimientos.ajax.reload(null, false);
        }

        const formActualizar = document.getElementById("formActualizarMovimiento");
        if (formActualizar) {
          formActualizar.reset();
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
    let errorMessage = "Ocurrió un error de conexión.";
    if (error.data?.message) {
      errorMessage = error.data.message;
    } else if (error.status) {
      errorMessage = `Error del servidor: ${error.status}.`;
    }
    
    if (error.data?.message && error.data.message.includes('permisos')) {
      mostrarModalPermisosDenegados(error.data.message);
    } else {
      Swal.fire("Error", errorMessage, "error");
    }
  })
  .finally(() => {
    if (btnActualizarMovimiento) {
      btnActualizarMovimiento.disabled = false;
      btnActualizarMovimiento.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Movimiento`;
    }
  });
}

/**
 * Eliminar movimiento
 */
function eliminarMovimiento(idMovimiento) {
  if (!tienePermiso('eliminar')) {
    mostrarModalPermisosDenegados("No tienes permisos para eliminar movimientos.");
    return;
  }
  
  Swal.fire({
    title: "¿Estás seguro?",
    text: "¿Deseas eliminar este movimiento?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      console.log(`🗑️ Eliminando movimiento: ${idMovimiento}`);
      
      fetch("movimientos/deleteMovimiento", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify({ idmovimiento: idMovimiento }),
      })
        .then(response => response.json())
        .then(result => {
          console.log("📥 Respuesta eliminación:", result);
          
          if (result.status) {
            Swal.fire("¡Eliminado!", result.message, "success");
            if (tablaMovimientos && tablaMovimientos.ajax) tablaMovimientos.ajax.reload(null, false);
          } else {
            if (result.message && result.message.includes('permisos')) {
              mostrarModalPermisosDenegados(result.message);
            } else {
              Swal.fire("Error", result.message || "No se pudo eliminar.", "error");
            }
          }
        })
        .catch(error => {
          console.error("Error en eliminación:", error);
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
    willOpen: () => {
      Swal.showLoading();
    }
  });

  fetch("movimientos/exportarMovimientos", {
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then(response => response.json())
    .then(result => {
      Swal.close();
      
      if (result.status && result.data) {
        // Crear y descargar archivo CSV
        const csvContent = generarCSVMovimientos(result.data);
        descargarCSV(csvContent, 'movimientos_export.csv');
        
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

/**
 * Generar CSV de movimientos
 */
function generarCSVMovimientos(datos) {
  const headers = ['ID', 'Nro. Movimiento', 'Producto', 'Tipo', 'Entrada', 'Salida', 'Stock Resultante', 'Estatus', 'Fecha'];
  const csvContent = [
    headers.join(','),
    ...datos.map(movimiento => [
      movimiento.idmovimiento || '',
      `"${movimiento.numero_movimiento || `MOV-${movimiento.idmovimiento}`}"`,
      `"${movimiento.producto_nombre || ''}"`,
      `"${movimiento.tipo_movimiento || ''}"`,
      movimiento.cantidad_entrada || '',
      movimiento.cantidad_salida || '',
      movimiento.stock_resultante || '',
      `"${movimiento.estatus || ''}"`,
      `"${movimiento.fecha_creacion_formato || ''}"`,
    ].join(','))
  ].join('\n');
  
  return csvContent;
}

/**
 * Descargar CSV
 */
function descargarCSV(csvContent, filename) {
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  
  if (link.download !== undefined) {
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}

// ✅ EXPORTAR FUNCIONES PARA USO EXTERNO
window.obtenerTiposMovimientoActivos = obtenerTiposMovimientoActivos;
window.actualizarContadoresTipos = actualizarContadoresTipos;
