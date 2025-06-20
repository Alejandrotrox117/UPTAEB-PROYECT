import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

let tablaProduccion;

const camposFormularioProduccion = [
  {
    id: "fecha_inicio",
    tipo: "fechaInicio",
    mensajes: {
      vacio: "La fecha de inicio es obligatoria.",
      fechaPosterior: "La fecha de inicio no puede ser posterior a hoy.",
    },
  },
  {
    id: "fecha_fin",
    tipo: "fechaFin",
    mensajes: {
      fechaPosterior: "La fecha fin no puede ser posterior a hoy.",
      fechaAnterior: "La fecha fin no puede ser anterior a la fecha de inicio.",
    },
  },
  {
    id: "estado",
    tipo: "select",
    regex: expresiones.estado_produccion,
    mensajes: {
      vacio: "Debe seleccionar un estado.",
      formato: "Estado no válido.",
    },
  },
  {
    id: "idempleado_seleccionado",
    tipo: "hidden",
    mensajes: {
      vacio: "Debe seleccionar un empleado.",
    },
  },
  {
    id: "idproducto",
    tipo: "hidden",
    mensajes: {
      vacio: "Debe seleccionar un producto.",
    },
  },
  {
    id: "cantidad_a_realizar",
    tipo: "input",
    regex: expresiones.numero_decimal,
    mensajes: {
      vacio: "La cantidad a realizar es obligatoria.",
      formato: "La cantidad debe ser un número válido mayor a cero.",
    },
  },
];

function recargarTablaProduccion() {
  try {
    if (tablaProduccion && tablaProduccion.ajax && typeof tablaProduccion.ajax.reload === 'function') {
      console.log("Recargando tabla con variable global");
      tablaProduccion.ajax.reload(null, false);
      return true;
    }

    if ($.fn.DataTable.isDataTable('#TablaProduccion')) {
      console.log("Recargando tabla con selector ID");
      const tabla = $('#TablaProduccion').DataTable();
      tabla.ajax.reload(null, false);
      return true;
    }

    console.log("Recargando página completa");
    window.location.reload();
    return true;

  } catch (error) {
    console.error("Error al recargar tabla:", error);
    window.location.reload();
    return false;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // Filtro para excluir producciones inactivas
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== "TablaProduccion") {
      return true;
    }
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    return rowData && rowData.estado && rowData.estado.toLowerCase() !== "inactivo";
  });

  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#TablaProduccion')) {
      $('#TablaProduccion').DataTable().destroy();
    }
    
    tablaProduccion = $("#TablaProduccion").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "./Produccion/getProduccionData",
        type: "GET",
        dataSrc: function (json) {
          if (json && Array.isArray(json.data)) {
            return json.data;
          } else {
            console.error("Respuesta del servidor no tiene la estructura esperada:", json);
            $("#TablaProduccion_processing").css("display", "none");
            Swal.fire({
              icon: "error",
              title: "Error de Datos",
              text: "No se pudieron cargar los datos. Respuesta inválida.",
            });
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
          $("#TablaProduccion_processing").css("display", "none");
          Swal.fire({
            icon: "error",
            title: "Error de Comunicación",
            text: "Fallo al cargar datos. Intente más tarde.",
            footer: `Detalle: ${textStatus} - ${errorThrown}`,
          });
        },
      },
      columns: [
        { 
          data: "idproduccion", 
          title: "ID", 
          className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background" 
        },
        { 
          data: "nombre_producto", 
          title: "Producto", 
          className: "all whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        { 
          data: "nombre_empleado", 
          title: "Empleado", 
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        { 
          data: "cantidad_a_realizar", 
          title: "Cantidad", 
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        { 
          data: "fecha_inicio", 
          title: "F. Inicio", 
          className: "min-tablet-p whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        { 
          data: "fecha_fin", 
          title: "F. Fin", 
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            return data || '<span class="text-gray-400 italic">Sin definir</span>';
          }
        },
        {
          data: "estado",
          title: "Estado",
          className: "min-tablet-p text-center py-2 px-3",
          render: function (data, type, row) {
            if (data) {
              const estadoNormalizado = String(data).trim().toLowerCase();
              let badgeClass = "bg-gray-200 text-gray-800";
              
              switch (estadoNormalizado) {
                case "borrador":
                  badgeClass = "bg-gray-100 text-gray-800";
                  break;
                case "planificado":
                  badgeClass = "bg-blue-100 text-blue-800";
                  break;
                case "en_proceso":
                  badgeClass = "bg-yellow-100 text-yellow-800";
                  break;
                case "en_clasificacion":
                  badgeClass = "bg-orange-100 text-orange-800";
                  break;
                case "empacando":
                  badgeClass = "bg-purple-100 text-purple-800";
                  break;
                case "realizado":
                  badgeClass = "bg-green-100 text-green-800";
                  break;
                case "inactivo":
                  badgeClass = "bg-red-100 text-red-800";
                  break;
              }
              return `<span class="${badgeClass} text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
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
          render: function (data, type, row) {
            const idProduccion = row.idproduccion || "";
            const nombreProducto = row.nombre_producto || "";
            return `
              <div class="inline-flex items-center space-x-1">
                <button class="ver-produccion-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idproduccion="${idProduccion}" title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                <button class="editar-produccion-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idproduccion="${idProduccion}" title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button class="eliminar-produccion-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idproduccion="${idProduccion}" data-nombre="${nombreProducto}" title="Desactivar">
                    <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>
              </div>`;
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
        emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay producciones disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ producciones",
        infoEmpty: "Mostrando 0 producciones",
        infoFiltered: "(filtrado de _MAX_ producciones totales)",
        lengthMenu: "Mostrar _MENU_ producciones",
        search: "_INPUT_",
        searchPlaceholder: "Buscar producción...",
        zeroRecords: '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
        paginate: { 
          first: '<i class="fas fa-angle-double-left"></i>', 
          last: '<i class="fas fa-angle-double-right"></i>', 
          next: '<i class="fas fa-angle-right"></i>', 
          previous: '<i class="fas fa-angle-left"></i>' 
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
      lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, "Todos"] ],
      order: [[0, "desc"]],
      scrollX: true,
      fixedColumns: {
          left: 1
      },
      initComplete: function (settings, json) {
        console.log("DataTable inicializado correctamente");
        window.tablaProduccion = this.api();
      },
      drawCallback: function (settings) {
        $(settings.nTableWrapper).find('.dataTables_filter input[type="search"]')
          .addClass("py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white")
          .removeClass("form-control-sm");

        var api = new $.fn.dataTable.Api(settings); 
        if (api.fixedColumns && typeof api.fixedColumns === 'function' && api.fixedColumns().relayout) {
          api.fixedColumns().relayout();
        }
      },
    });

    // Event listeners para botones de la tabla
    $("#TablaProduccion tbody").on("click", ".ver-produccion-btn", function () {
      const idProduccion = $(this).data("idproduccion");
      if (idProduccion && typeof verProduccion === "function") {
        verProduccion(idProduccion);
      } else {
        console.error("Función verProduccion no definida o idProduccion no encontrado.", idProduccion);
        Swal.fire("Error", "No se pudo obtener el ID de la producción.", "error");
      }
    });

    $("#TablaProduccion tbody").on("click", ".editar-produccion-btn", function () {
      const idProduccion = $(this).data("idproduccion");
      if (idProduccion && typeof editarProduccion === "function") {
        editarProduccion(idProduccion);
      } else {
        console.error("Función editarProduccion no definida o idProduccion no encontrado.", idProduccion);
        Swal.fire("Error", "No se pudo obtener el ID de la producción.", "error");
      }
    });

    $("#TablaProduccion tbody").on("click", ".eliminar-produccion-btn", function () {
      const idProduccion = $(this).data("idproduccion");
      const nombreProducto = $(this).data("nombre");
      if (idProduccion && typeof eliminarProduccion === "function") {
        eliminarProduccion(idProduccion, nombreProducto);
      } else {
        console.error("Función eliminarProduccion no definida o idProduccion no encontrado.", idProduccion);
        Swal.fire("Error", "No se pudo obtener el ID de la producción.", "error");
      }
    });
  });

  // Variables globales para el manejo de datos
  let detalleProduccionItems = [];
  let listaProductos = [];
  let listaEmpleados = [];

  // MODAL REGISTRAR PRODUCCIÓN
  const btnAbrirModalRegistro = document.getElementById("abrirModalProduccion");
  const formRegistrar = document.getElementById("formRegistrarProduccion");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalProduccion");
  const btnCancelarModalRegistro = document.getElementById("btnCancelarProduccion");

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("produccionModal");
      if (formRegistrar) formRegistrar.reset();
      detalleProduccionItems = [];
      limpiarFormularioProduccion();
      cargarSelectsIniciales();
      inicializarBuscadorEmpleado();
      inicializarValidaciones(camposFormularioProduccion, "formRegistrarProduccion");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("produccionModal");
      limpiarValidaciones(camposFormularioProduccion, "formRegistrarProduccion");
      limpiarFormularioProduccion();
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("produccionModal");
      limpiarValidaciones(camposFormularioProduccion, "formRegistrarProduccion");
      limpiarFormularioProduccion();
    });
  }

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarProduccion();
    });
  }

  // MODAL VER DETALLE
  const btnCerrarModalDetalle = document.getElementById("cerrarDetalleProduccion");
  if (btnCerrarModalDetalle) {
    btnCerrarModalDetalle.addEventListener("click", function () {
      cerrarModal("detalleModal");
    });
  }

  // Cargar estadísticas al inicializar
  cargarEstadisticas();

  // Inicializar componentes
  inicializarEventosFormulario();
  inicializarEventosTablas();
});

// FUNCIONES PRINCIPALES

function registrarProduccion() {
  const btnGuardarProduccion = document.getElementById("btnGuardarProduccion");

  if (btnGuardarProduccion) {
    btnGuardarProduccion.disabled = true;
    btnGuardarProduccion.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  // Validar que se haya seleccionado al menos un insumo para producciones nuevas
  const idproduccion = document.getElementById("idproduccion").value.trim();
  if (!idproduccion && detalleProduccionItems.length === 0) {
    Swal.fire("Atención", "Debe agregar al menos un insumo al detalle.", "warning");
    if (btnGuardarProduccion) {
      btnGuardarProduccion.disabled = false;
      btnGuardarProduccion.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Producción`;
    }
    return;
  }

  registrarEntidad({
    formId: "formRegistrarProduccion",
    endpoint: idproduccion ? "Produccion/updateProduccion" : "Produccion/createProduccion",
    campos: camposFormularioProduccion,
    mapeoNombres: {
      "idproduccion": "idproduccion",
      "idempleado_seleccionado": "idempleado",
      "idproducto": "idproducto",
      "cantidad_a_realizar": "cantidad_a_realizar",
      "fecha_inicio": "fecha_inicio",
      "fecha_fin": "fecha_fin",
      "estado": "estado"
    },
    datosAdicionales: {
      insumos: detalleProduccionItems
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("produccionModal");
        recargarTablaProduccion();
        cargarEstadisticas();
        limpiarFormularioProduccion();
        limpiarValidaciones(camposFormularioProduccion, "formRegistrarProduccion");
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo procesar la producción.",
        "error"
      );
    }
  }).finally(() => {
    if (btnGuardarProduccion) {
      btnGuardarProduccion.disabled = false;
      btnGuardarProduccion.innerHTML = idproduccion 
        ? `<i class="fas fa-save mr-2"></i> Actualizar Producción`
        : `<i class="fas fa-save mr-2"></i> Guardar Producción`;
    }
  });
}

function editarProduccion(idProduccion) {
  fetch(`Produccion/getProduccionById/${idProduccion}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const produccion = result.data;
        mostrarModalEditarProduccion(produccion);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarProduccion(produccion) {
  // Cargar selects primero
  cargarSelectsIniciales().then(() => {
    // Llenar los campos del formulario
    document.getElementById("idproduccion").value = produccion.idproduccion || "";
    document.getElementById("idempleado_seleccionado").value = produccion.idempleado || "";
    document.getElementById("idproducto").value = produccion.idproducto || "";
    document.getElementById("select_producto").value = produccion.idproducto || "";
    document.getElementById("cantidad_a_realizar").value = produccion.cantidad_a_realizar || "";
    document.getElementById("fecha_inicio").value = produccion.fecha_inicio || "";
    document.getElementById("fecha_fin").value = produccion.fecha_fin || "";
    document.getElementById("estado").value = produccion.estado || "";

    // Mostrar información del empleado seleccionado
    const divInfoEmpleado = document.getElementById("empleado_seleccionado_info");
    if (divInfoEmpleado) {
      divInfoEmpleado.innerHTML = `Empleado: <strong>${produccion.nombre_empleado}</strong>`;
      divInfoEmpleado.classList.remove("hidden");
    }

    // Cargar detalle de insumos
    cargarDetalleProduccion(produccion.idproduccion);

    // Inicializar validaciones
    inicializarValidaciones(camposFormularioProduccion, "formRegistrarProduccion");

    abrirModal("produccionModal");
  });
}

function verProduccion(idProduccion) {
  const modal = document.getElementById("detalleModal");
  const contenido = document.getElementById("contenidoDetalleProduccion");
  
  abrirModal("detalleModal");

  Promise.all([
    fetch(`Produccion/getProduccionById/${idProduccion}`).then((res) => res.json()),
    fetch(`Produccion/getDetalleProduccionData/${idProduccion}`).then((res) => res.json()),
  ])
    .then(([produccionRes, detalleRes]) => {
      if (!produccionRes.status) throw new Error("Error al cargar datos de producción");
      
      const prod = produccionRes.data;
      const detalle = detalleRes.status ? detalleRes.data : [];

      let html = `
        <div class="space-y-6">
          <div>
            <h4 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Datos Generales</h4>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
              <li><span class="font-medium text-gray-500">ID:</span> ${prod.idproduccion}</li>
              <li><span class="font-medium text-gray-500">Producto:</span> ${prod.nombre_producto}</li>
              <li><span class="font-medium text-gray-500">Cantidad a realizar:</span> ${prod.cantidad_a_realizar}</li>
              <li><span class="font-medium text-gray-500">Empleado:</span> ${prod.nombre_empleado}</li>
              <li><span class="font-medium text-gray-500">Fecha inicio:</span> ${prod.fecha_inicio}</li>
              <li><span class="font-medium text-gray-500">Fecha fin:</span> ${prod.fecha_fin || 'Sin definir'}</li>
              <li><span class="font-medium text-gray-500">Estado:</span>
                <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold 
                  ${getEstadoBadgeClass(prod.estado)}">
                  ${prod.estado}
                </span>
              </li>
            </ul>
          </div>`;

      if (detalle.length > 0) {
        html += `
          <div>
            <h4 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Insumos</h4>
            <div class="overflow-x-auto rounded-lg shadow-sm">
              <table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-700 text-left uppercase tracking-wider">
                  <tr>
                    <th class="px-4 py-3 border-b">Producto</th>
                    <th class="px-4 py-3 border-b">Cantidad</th>
                    <th class="px-4 py-3 border-b">Consumida</th>
                    <th class="px-4 py-3 border-b">Observaciones</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">`;

        detalle.forEach((insumo) => {
          html += `
            <tr>
              <td class="px-4 py-3">${insumo.nombre_producto}</td>
              <td class="px-4 py-3">${insumo.cantidad}</td>
              <td class="px-4 py-3">${insumo.cantidad_consumida}</td>
              <td class="px-4 py-3">${insumo.observaciones || "-"}</td>
            </tr>`;
        });

        html += `
                </tbody>
              </table>
            </div>
          </div>`;
      }

      html += `</div>`;
      contenido.innerHTML = html;
    })
    .catch((err) => {
      console.error("Error al cargar detalle:", err);
      contenido.innerHTML = "<p class='text-red-600'>Error al cargar los detalles.</p>";
    });
}

function eliminarProduccion(idProduccion, nombreProducto) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar la producción "${nombreProducto}"? Esta acción cambiará su estado a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idproduccion: idProduccion,
      };

      fetch("Produccion/deleteProduccion", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(dataParaEnviar),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Desactivado!", result.message, "success").then(() => {
              recargarTablaProduccion();
              cargarEstadisticas();
            });
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar la producción.",
              "error"
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}

// FUNCIONES AUXILIARES

function cargarEstadisticas() {
  fetch("Produccion/getEstadisticas")
    .then((res) => res.json())
    .then((data) => {
      if (data.status) {
        const stats = data.data;
        document.querySelector("#total-producciones").textContent = stats.total || 0;
        document.querySelector("#en-clasificacion").textContent = stats.clasificacion || 0;
        document.querySelector("#producidas").textContent = stats.finalizadas || 0;
      } else {
        console.error("Error al cargar estadísticas:", data.message);
      }
    })
    .catch((err) => {
      console.error("Error de red al cargar estadísticas:", err);
    });
}

function cargarSelectsIniciales() {
  return Promise.all([
    cargarSelect({
      selectId: "select_producto",
      endpoint: "Produccion/getProductos",
      optionTextFn: (p) => p.nombre,
      optionValueFn: (p) => p.idproducto || "",
      placeholder: "Seleccione un producto...",
      onLoaded: (productos) => {
        listaProductos = productos;
      },
    }),
    cargarSelect({
      selectId: "select_producto_agregar_detalle",
      endpoint: "Produccion/getProductos",
      optionTextFn: (p) => p.nombre,
      optionValueFn: (p) => p.idproducto || "",
      placeholder: "Seleccione un insumo...",
    }),
  ]);
}

function cargarSelect({ selectId, endpoint, optionTextFn, optionValueFn, placeholder, onLoaded }) {
  return fetch(endpoint)
    .then(response => response.json())
    .then(data => {
      const select = document.getElementById(selectId);
      if (!select) return;

      select.innerHTML = placeholder ? `<option value="">${placeholder}</option>` : '';
      
      if (data.status && Array.isArray(data.data)) {
        data.data.forEach(item => {
          const option = document.createElement('option');
          option.value = optionValueFn(item);
          option.textContent = optionTextFn(item);
          select.appendChild(option);
        });
        
        if (onLoaded) onLoaded(data.data);
      }
    })
    .catch(error => {
      console.error(`Error al cargar ${selectId}:`, error);
    });
}

function inicializarBuscadorEmpleado() {
  const inputCriterio = document.getElementById("inputCriterioEmpleado");
  const btnBuscar = document.getElementById("btnBuscarEmpleado");
  const listaResultados = document.getElementById("listaResultadosEmpleado");
  const inputIdEmpleado = document.getElementById("idempleado_seleccionado");
  const divInfo = document.getElementById("empleado_seleccionado_info");

  if (!btnBuscar || !inputCriterio) return;

  btnBuscar.addEventListener("click", async function () {
    const criterio = inputCriterio.value.trim();
    if (criterio.length < 2) {
      Swal.fire("Atención", "Ingrese al menos 2 caracteres para buscar.", "warning");
      return;
    }

    listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
    listaResultados.classList.remove("hidden");

    try {
      const response = await fetch("Produccion/getEmpleado");
      const data = await response.json();
      
      listaResultados.innerHTML = "";
      
      if (data.status && Array.isArray(data.data)) {
        const empleadosFiltrados = data.data.filter(emp => 
          emp.nombre.toLowerCase().includes(criterio.toLowerCase()) ||
          emp.apellido.toLowerCase().includes(criterio.toLowerCase()) ||
          emp.identificacion.toLowerCase().includes(criterio.toLowerCase())
        );

        if (empleadosFiltrados.length > 0) {
          empleadosFiltrados.forEach((emp) => {
            const itemDiv = document.createElement("div");
            itemDiv.className = "p-2 hover:bg-gray-100 cursor-pointer text-sm border-b";
            itemDiv.textContent = `${emp.nombre} ${emp.apellido} (${emp.identificacion})`;
            itemDiv.dataset.id = emp.idempleado;
            itemDiv.dataset.nombre = emp.nombre;
            itemDiv.dataset.apellido = emp.apellido;
            itemDiv.dataset.cedula = emp.identificacion;
            
            itemDiv.addEventListener("click", function () {
              inputIdEmpleado.value = this.dataset.id;
              divInfo.innerHTML = `Empleado: <strong>${this.dataset.nombre} ${this.dataset.apellido}</strong> (C.I.: ${this.dataset.cedula})`;
              divInfo.classList.remove("hidden");
              inputCriterio.value = this.textContent;
              listaResultados.classList.add("hidden");
              listaResultados.innerHTML = "";
            });
            
            listaResultados.appendChild(itemDiv);
          });
        } else {
          listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">No se encontraron empleados con ese criterio.</div>';
        }
      } else {
        listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">No se encontraron empleados.</div>';
      }
    } catch (error) {
      console.error("Error al buscar empleados:", error);
      listaResultados.innerHTML = '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
    }
  });

  inputCriterio.addEventListener("input", function () {
    inputIdEmpleado.value = "";
    divInfo.classList.add("hidden");
    listaResultados.classList.add("hidden");
  });
}

function inicializarEventosFormulario() {
  // Evento para selección de producto principal
  const selectProducto = document.getElementById("select_producto");
  if (selectProducto) {
    selectProducto.addEventListener("change", function () {
      const hiddenInput = document.getElementById("idproducto");
      hiddenInput.value = this.value;
    });
  }

  // Evento para agregar insumos
  const btnAgregarInsumo = document.getElementById("btnAgregarProductoDetalleProduccion");
  if (btnAgregarInsumo) {
    btnAgregarInsumo.addEventListener("click", function () {
      const selectInsumo = document.getElementById("select_producto_agregar_detalle");
      const selectedOption = selectInsumo.options[selectInsumo.selectedIndex];
      
      if (!selectedOption.value) {
        Swal.fire("Atención", "Seleccione un insumo.", "warning");
        return;
      }

      const idproducto = selectedOption.value;
      const nombreProducto = selectedOption.textContent;
      
      if (detalleProduccionItems.some((item) => item.idproducto === idproducto)) {
        Swal.fire("Atención", "Este insumo ya fue agregado.", "warning");
        return;
      }

      detalleProduccionItems.push({
        idproducto: idproducto,
        nombre: nombreProducto,
        cantidad: 1,
        cantidad_consumida: 0,
        observaciones: "",
      });

      renderizarTablaDetalleProduccion();
      selectInsumo.value = "";
    });
  }
}

function inicializarEventosTablas() {
  // Inicialización de tabs si existen
  document.querySelectorAll(".tab-button").forEach((button) => {
    button.addEventListener("click", () => {
      const tab = button.getAttribute("data-tab");
      
      document.querySelectorAll(".tab-button").forEach((btn) => {
        btn.classList.remove("active", "border-green-500", "text-green-600");
        btn.classList.add("border-transparent");
      });
      
      button.classList.add("active", "border-green-500", "text-green-600");
      button.classList.remove("border-transparent");
      
      document.querySelectorAll(".tab-content").forEach((content) => {
        content.classList.add("hidden");
      });
      
      const tabContent = document.getElementById(`tab-${tab}`);
      if (tabContent) {
        tabContent.classList.remove("hidden");
      }
    });
  });
}

function renderizarTablaDetalleProduccion() {
  const tbody = document.getElementById("cuerpoTablaDetalleProduccion");
  const noDetallesMensaje = document.getElementById("noDetallesMensajeProduccion");
  
  if (!tbody) return;

  tbody.innerHTML = "";

  if (detalleProduccionItems.length === 0) {
    if (noDetallesMensaje) noDetallesMensaje.classList.remove("hidden");
    return;
  }

  if (noDetallesMensaje) noDetallesMensaje.classList.add("hidden");

  detalleProduccionItems.forEach((item, index) => {
    const tr = document.createElement("tr");
    tr.className = "border-b hover:bg-gray-50";
    tr.innerHTML = `
      <td class="px-3 py-2 text-sm">${item.nombre}</td>
      <td class="px-3 py-2">
        <input type="number" value="${item.cantidad}" min="1" step="1" 
               class="w-24 border rounded-md px-2 py-1 text-sm cantidad-requerida-input focus:ring-2 focus:ring-green-400" 
               data-index="${index}">
      </td>
      <td class="px-3 py-2">
        <input type="number" value="${item.cantidad_consumida}" min="0" step="1" 
               class="w-24 border rounded-md px-2 py-1 text-sm cantidad-usada-input focus:ring-2 focus:ring-green-400" 
               data-index="${index}">
      </td>
      <td class="px-3 py-2">
        <input type="text" value="${item.observaciones}" 
               class="w-full border rounded-md px-2 py-1 text-sm observaciones-input focus:ring-2 focus:ring-green-400" 
               data-index="${index}" placeholder="Observaciones...">
      </td>
      <td class="px-3 py-2 text-center">
        <button class="eliminar-detalle-btn text-red-500 hover:text-red-700 p-1 rounded transition-colors" 
                data-index="${index}" title="Eliminar">
          <i class="fas fa-trash-alt"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  // Event listeners para inputs dinámicos
  document.querySelectorAll(".cantidad-requerida-input").forEach((input) => {
    input.addEventListener("input", function () {
      const index = parseInt(this.getAttribute("data-index"));
      detalleProduccionItems[index].cantidad = parseFloat(this.value) || 1;
    });
  });

  document.querySelectorAll(".cantidad-usada-input").forEach((input) => {
    input.addEventListener("input", function () {
      const index = parseInt(this.getAttribute("data-index"));
      detalleProduccionItems[index].cantidad_consumida = parseFloat(this.value) || 0;
    });
  });

  document.querySelectorAll(".observaciones-input").forEach((input) => {
    input.addEventListener("input", function () {
      const index = parseInt(this.getAttribute("data-index"));
      detalleProduccionItems[index].observaciones = this.value;
    });
  });

  document.querySelectorAll(".eliminar-detalle-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const index = parseInt(this.getAttribute("data-index"));
      
      Swal.fire({
        title: "¿Eliminar insumo?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
      }).then((result) => {
        if (result.isConfirmed) {
          detalleProduccionItems.splice(index, 1);
          renderizarTablaDetalleProduccion();
        }
      });
    });
  });
}

function cargarDetalleProduccion(idproduccion) {
  fetch(`Produccion/getDetalleProduccionData/${idproduccion}`)
    .then((res) => res.json())
    .then((detalle) => {
      detalleProduccionItems = [];
      if (detalle.status && detalle.data.length > 0) {
        detalle.data.forEach((insumo) => {
          detalleProduccionItems.push({
            idproducto: insumo.idproducto,
            nombre: insumo.nombre_producto,
            cantidad: insumo.cantidad,
            cantidad_consumida: insumo.cantidad_consumida,
            observaciones: insumo.observaciones || "",
          });
        });
      }
      renderizarTablaDetalleProduccion();
    })
    .catch(() => {
      Swal.fire("Error", "Error al cargar los insumos.", "error");
    });
}

function limpiarFormularioProduccion() {
  const form = document.getElementById("formRegistrarProduccion");
  if (form) form.reset();
  
  detalleProduccionItems = [];
  renderizarTablaDetalleProduccion();
  
  // Limpiar información de empleado seleccionado
  const divInfo = document.getElementById("empleado_seleccionado_info");
  if (divInfo) divInfo.classList.add("hidden");
  
  // Limpiar campos ocultos
  const inputIdEmpleado = document.getElementById("idempleado_seleccionado");
  const inputIdProducto = document.getElementById("idproducto");
  const inputIdProduccion = document.getElementById("idproduccion");
  
  if (inputIdEmpleado) inputIdEmpleado.value = "";
  if (inputIdProducto) inputIdProducto.value = "";
  if (inputIdProduccion) inputIdProduccion.value = "";
}

function getEstadoBadgeClass(estado) {
  switch (estado?.toLowerCase()) {
    case "borrador":
      return "bg-gray-100 text-gray-700";
    case "planificado":
      return "bg-blue-100 text-blue-700";
    case "en_proceso":
      return "bg-yellow-100 text-yellow-700";
    case "en_clasificacion":
      return "bg-orange-100 text-orange-700";
    case "empacando":
      return "bg-purple-100 text-purple-700";
    case "realizado":
      return "bg-green-100 text-green-700";
    case "inactivo":
      return "bg-red-100 text-red-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}