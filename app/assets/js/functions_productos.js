import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

let tablaProductos;
let categorias = [];

const camposFormularioProducto = [
  {
    id: "productoNombre",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras, números y espacios.",
    },
  },
  {
    id: "productoDescripcion",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Descripción inválida.",
    },
  },
  {
    id: "productoUnidadMedida",
    tipo: "select",
    mensajes: {
      vacio: "La unidad de medida es obligatoria.",
      formato: "Unidad de medida inválida.",
    },
  },
  {
    id: "productoPrecio",
    tipo: "number",
    mensajes: {
      vacio: "El precio es obligatorio.",
      formato: "El precio debe ser mayor a 0.",
    },
  },
  {
    id: "productoCategoria",
    tipo: "select",
    mensajes: {
      vacio: "La categoría es obligatoria.",
      formato: "Categoría inválida.",
    },
  },
  {
    id: "productoMoneda",
    tipo: "select",
    mensajes: {
      vacio: "La moneda es obligatoria.",
      formato: "Moneda inválida.",
    },
  },
];

const camposFormularioActualizarProducto = [
  {
    id: "productoNombreActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras, números y espacios.",
    },
  },
  {
    id: "productoDescripcionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Descripción inválida.",
    },
  },
  {
    id: "productoUnidadMedidaActualizar",
    tipo: "select",
    mensajes: {
      vacio: "La unidad de medida es obligatoria.",
      formato: "Unidad de medida inválida.",
    },
  },
  {
    id: "productoPrecioActualizar",
    tipo: "number",
    mensajes: {
      vacio: "El precio es obligatorio.",
      formato: "El precio debe ser mayor a 0.",
    },
  },
  {
    id: "productoCategoriaActualizar",
    tipo: "select",
    mensajes: {
      vacio: "La categoría es obligatoria.",
      formato: "Categoría inválida.",
    },
  },
  {
    id: "productoMonedaActualizar",
    tipo: "select",
    mensajes: {
      vacio: "La moneda es obligatoria.",
      formato: "Moneda inválida.",
    },
  },
];

function recargarTablaProductos() {
  try {
    if (tablaProductos && tablaProductos.ajax && typeof tablaProductos.ajax.reload === 'function') {
      console.log("Recargando tabla con variable global");
      tablaProductos.ajax.reload(null, false);
      return true;
    }

    if ($.fn.DataTable.isDataTable('#TablaProductos')) {
      console.log("Recargando tabla con selector ID");
      const tabla = $('#TablaProductos').DataTable();
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

function cargarCategorias() {
  fetch("./Productos/getCategorias", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        categorias = result.data;
        llenarSelectCategorias();
      } else {
        console.error("Error al cargar categorías:", result.message);
      }
    })
    .catch((error) => {
      console.error("Error al cargar categorías:", error);
    });
}

function llenarSelectCategorias() {
  const selectCategoria = document.getElementById("productoCategoria");
  const selectCategoriaActualizar = document.getElementById("productoCategoriaActualizar");
  
  if (selectCategoria) {
    selectCategoria.innerHTML = '<option value="">Seleccione una categoría</option>';
    categorias.forEach(categoria => {
      const option = document.createElement("option");
      option.value = categoria.idcategoria;
      option.textContent = categoria.nombre;
      selectCategoria.appendChild(option);
    });
  }
  
  if (selectCategoriaActualizar) {
    selectCategoriaActualizar.innerHTML = '<option value="">Seleccione una categoría</option>';
    categorias.forEach(categoria => {
      const option = document.createElement("option");
      option.value = categoria.idcategoria;
      option.textContent = categoria.nombre;
      selectCategoriaActualizar.appendChild(option);
    });
  }
}


document.addEventListener("DOMContentLoaded", function () {
  
  cargarCategorias();

  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== "TablaProductos") {
      return true;
    }
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    return rowData && rowData.estatus && rowData.estatus.toLowerCase() !== "inactivo";
  });

  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#TablaProductos')) {
      $('#TablaProductos').DataTable().destroy();
    }
    
    tablaProductos = $("#TablaProductos").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "./Productos/getProductosData",
        type: "GET",
        dataSrc: function (json) {
          if (json && Array.isArray(json.data)) {
            return json.data;
          } else {
            console.error("Respuesta del servidor no tiene la estructura esperada:", json);
            $("#TablaProductos_processing").css("display", "none");
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
          $("#TablaProductos_processing").css("display", "none");
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
          data: "nombre", 
          title: "Nombre", 
          className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background" 
        },
        { 
          data: "categoria_nombre", 
          title: "Categoría", 
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        { 
          data: "unidad_medida", 
          title: "Unidad", 
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        { 
          data: "precio", 
          title: "Precio", 
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data && row.moneda) {
              return `${row.moneda} ${parseFloat(data).toFixed(2)}`;
            }
            return "N/A";
          }
        },
        { 
          data: "existencia", 
          title: "Existencia", 
          className: "min-tablet-p text-center py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data !== null && data !== undefined) {
              const existencia = parseInt(data);
              let badgeClass = "bg-green-100 text-green-800";
              
              if (existencia === 0) {
                badgeClass = "bg-red-100 text-red-800";
              } else if (existencia <= 9) {
                badgeClass = "bg-yellow-100 text-yellow-800";
              }
              
              return `<span class="${badgeClass} text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${existencia}</span>`;
            }
            return '<span class="text-xs italic text-gray-500">N/A</span>';
          }
        },
        {
          data: "estatus",
          title: "Estatus",
          className: "min-tablet-p text-center py-2 px-3",
          render: function (data, type, row) {
            if (data) {
              const estatusNormalizado = String(data).trim().toUpperCase();
              let badgeClass = "bg-gray-200 text-gray-800";
              if (estatusNormalizado === "ACTIVO") {
                badgeClass = "bg-green-100 text-green-800";
              } else if (estatusNormalizado === "INACTIVO") {
                badgeClass = "bg-red-100 text-red-800";
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
            const idProducto = row.idproducto || "";
            const nombreProducto = row.nombre || "";
            return `
              <div class="inline-flex items-center space-x-1">
                <button class="ver-producto-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idproducto="${idProducto}" title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                <button class="editar-producto-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idproducto="${idProducto}" title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button class="eliminar-producto-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idproducto="${idProducto}" data-nombre="${nombreProducto}" title="Desactivar">
                    <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>
              </div>`;
          },
        },
        {
          data: "fecha_creacion",
          title: "Fecha Creación",
          visible: false,
          searchable: false,
          className: "never"
        }
      ],
      language: {
        processing: `
          <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]" style="margin-left:0;">
              <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                  <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
                  <span class="text-lg font-medium text-gray-700">Procesando...</span>
              </div>
          </div>`,
        emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay productos disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ productos",
        infoEmpty: "Mostrando 0 productos",
        infoFiltered: "(filtrado de _MAX_ productos totales)",
        lengthMenu: "Mostrar _MENU_ productos",
        search: "_INPUT_",
        searchPlaceholder: "Buscar producto...",
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
      order: [[7, "desc"]],
      scrollX: true,
      fixedColumns: {
          left: 1
      },
      initComplete: function (settings, json) {
        console.log("DataTable inicializado correctamente");
        window.tablaProductos = this.api();
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

    $("#TablaProductos tbody").on("click", ".ver-producto-btn", function () {
      const idProducto = $(this).data("idproducto");
      if (idProducto && typeof verProducto === "function") {
        verProducto(idProducto);
      } else {
        console.error("Función verProducto no definida o idProducto no encontrado.", idProducto);
        Swal.fire("Error", "No se pudo obtener el ID del producto.", "error");
      }
    });

    $("#TablaProductos tbody").on("click", ".editar-producto-btn", function () {
      const idProducto = $(this).data("idproducto");
      if (idProducto && typeof editarProducto === "function") {
        editarProducto(idProducto);
      } else {
        console.error("Función editarProducto no definida o idProducto no encontrado.", idProducto);
        Swal.fire("Error", "No se pudo obtener el ID del producto.", "error");
      }
    });

    $("#TablaProductos tbody").on("click", ".eliminar-producto-btn", function () {
      const idProducto = $(this).data("idproducto");
      const nombreProducto = $(this).data("nombre");
      if (idProducto && typeof eliminarProducto === "function") {
        eliminarProducto(idProducto, nombreProducto);
      } else {
        console.error("Función eliminarProducto no definida o idProducto no encontrado.", idProducto);
        Swal.fire("Error", "No se pudo obtener el ID del producto.", "error");
      }
    });
  });

  
  const btnAbrirModalRegistro = document.getElementById("btnAbrirModalRegistrarProducto");
  const formRegistrar = document.getElementById("formRegistrarProducto");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistro = document.getElementById("btnCancelarModalRegistrar");

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarProducto");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(camposFormularioProducto, "formRegistrarProducto");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarProducto");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarProducto");
    });
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarProducto();
    });
  }

  
  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarProducto");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarProducto");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarProducto");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarProducto();
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerProducto");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerProducto");
    });
  }

  
  const btnGenerarNotificaciones = document.getElementById("btnGenerarNotificacionesProductos");
  if (btnGenerarNotificaciones) {
    btnGenerarNotificaciones.addEventListener("click", function () {
      generarNotificacionesProductos();
    });
  }
});


function registrarProducto() {
  const btnGuardarProducto = document.getElementById("btnGuardarProducto");

  if (btnGuardarProducto) {
    btnGuardarProducto.disabled = true;
    btnGuardarProducto.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }
   
  registrarEntidad({
    formId: "formRegistrarProducto",
    endpoint: "Productos/createProducto",
    campos: camposFormularioProducto,
    mapeoNombres: {
      "productoNombre": "nombre",
      "productoDescripcion": "descripcion",
      "productoUnidadMedida": "unidad_medida",
      "productoPrecio": "precio",
      "productoCategoria": "idcategoria",
      "productoMoneda": "moneda"
    },
    validacionPersonalizada: function(formData) {
      const precio = parseFloat(formData.precio);
      if (precio <= 0) {
        return "El precio debe ser mayor a 0";
      }
      return null;
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarProducto");
        recargarTablaProductos();
        
        const formRegistrar = document.getElementById("formRegistrarProducto");
        if (formRegistrar) {
          formRegistrar.reset();
          limpiarValidaciones(camposFormularioProducto, "formRegistrarProducto");
        }

        
        if (typeof actualizarContadorNotificaciones === 'function') {
          actualizarContadorNotificaciones();
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo registrar el producto.",
        "error"
      );
    }
  }).finally(() => {
    if (btnGuardarProducto) {
      btnGuardarProducto.disabled = false;
      btnGuardarProducto.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Producto`;
    }
  });
}

function editarProducto(idProducto) {
  fetch(`Productos/getProductoById/${idProducto}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const producto = result.data;
        mostrarModalEditarProducto(producto);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarProducto(producto) {
  document.getElementById("idProductoActualizar").value = producto.idproducto || "";
  document.getElementById("productoNombreActualizar").value = producto.nombre || "";
  document.getElementById("productoDescripcionActualizar").value = producto.descripcion || "";
  document.getElementById("productoUnidadMedidaActualizar").value = producto.unidad_medida || "";
  document.getElementById("productoPrecioActualizar").value = producto.precio || "";
  document.getElementById("productoCategoriaActualizar").value = producto.idcategoria || "";
  document.getElementById("productoMonedaActualizar").value = producto.moneda || "";

  inicializarValidaciones(camposFormularioActualizarProducto, "formActualizarProducto");
  abrirModal("modalActualizarProducto");
}

function actualizarProducto() {
  const btnActualizarProducto = document.getElementById("btnActualizarProducto");

  if (btnActualizarProducto) {
    btnActualizarProducto.disabled = true;
    btnActualizarProducto.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  registrarEntidad({
    formId: "formActualizarProducto",
    endpoint: "Productos/updateProducto",
    campos: camposFormularioActualizarProducto,
    mapeoNombres: {
      "idProductoActualizar": "idproducto",
      "productoNombreActualizar": "nombre",
      "productoDescripcionActualizar": "descripcion",
      "productoUnidadMedidaActualizar": "unidad_medida",
      "productoPrecioActualizar": "precio",
      "productoCategoriaActualizar": "idcategoria",
      "productoMonedaActualizar": "moneda"
    },
    validacionPersonalizada: function(formData) {
      const precio = parseFloat(formData.precio);
      if (precio <= 0) {
        return "El precio debe ser mayor a 0";
      }
      return null;
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalActualizarProducto");
        recargarTablaProductos();

        const formActualizar = document.getElementById("formActualizarProducto");
        if (formActualizar) {
          formActualizar.reset();
          limpiarValidaciones(camposFormularioActualizarProducto, "formActualizarProducto");
        }

        
        if (typeof actualizarContadorNotificaciones === 'function') {
          actualizarContadorNotificaciones();
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo actualizar el producto.",
        "error"
      );
    }
  }).finally(() => {
    if (btnActualizarProducto) {
      btnActualizarProducto.disabled = false;
      btnActualizarProducto.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Producto`;
    }
  });
}

function verProducto(idProducto) {
  fetch(`Productos/getProductoById/${idProducto}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const producto = result.data;
        mostrarModalVerProducto(producto);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerProducto(producto) {
  document.getElementById("verProductoNombre").textContent = producto.nombre || "N/A";
  document.getElementById("verProductoDescripcion").textContent = producto.descripcion || "Sin descripción";
  document.getElementById("verProductoCategoria").textContent = producto.categoria_nombre || "N/A";
  document.getElementById("verProductoUnidadMedida").textContent = producto.unidad_medida || "N/A";
  document.getElementById("verProductoPrecio").textContent = producto.precio ? `${producto.moneda} ${parseFloat(producto.precio).toFixed(2)}` : "N/A";
  document.getElementById("verProductoExistencia").textContent = producto.existencia || "0";
  document.getElementById("verProductoEstatus").textContent = producto.estatus || "N/A";

  abrirModal("modalVerProducto");
}

function eliminarProducto(idProducto, nombreProducto) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar el producto "${nombreProducto}"? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idproducto: idProducto,
      };

      fetch("Productos/deleteProducto", {
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
              recargarTablaProductos();
              
              
              if (typeof actualizarContadorNotificaciones === 'function') {
                actualizarContadorNotificaciones();
              }
            });
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar el producto.",
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