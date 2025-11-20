import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

let tablaCategorias;
let esSuperUsuarioActual = false;
let idUsuarioActual = 0;

// IDs de categorias del sistema que no se pueden eliminar
const CATEGORIAS_SISTEMA = [1, 2, 3]; // 1=Pacas, 2=Materiales, 3=Consumibles

const camposFormularioCategoria = [
  {
    id: "categoriaNombre",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  },
  {
    id: "categoriaDescripcion",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    opcional: true,
    mensajes: {
      formato: "Descripci�n inv�lida.",
    },
  }
];

const camposFormularioActualizarCategoria = [
  {
    id: "categoriaNombreActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  },
  {
    id: "categoriaDescripcionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    opcional: true,
    mensajes: {
      formato: "Descripci�n inv�lida.",
    },
  }
];

function recargarTablaCategorias() {
  try {
    if (tablaCategorias && tablaCategorias.ajax && typeof tablaCategorias.ajax.reload === 'function') {
      tablaCategorias.ajax.reload(null, false);
      return true;
    }

    if ($.fn.DataTable.isDataTable('#TablaCategorias')) {
      const tabla = $('#TablaCategorias').DataTable();
      tabla.ajax.reload(null, false);
      return true;
    }

    window.location.reload();
    return true;

  } catch (error) {
    window.location.reload();
    return false;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
    // Verificar si el usuario es super usuario antes de inicializar la tabla
    fetch("./usuarios/verificarSuperUsuario")
      .then((response) => {
        return response.json();
      })
      .then((data) => {
        esSuperUsuarioActual = data.esSuperUsuario || data.es_super_usuario || false;
        idUsuarioActual = data.idUsuario || data.usuario_id || 0;

        // Inicializar la tabla despu�s de verificar el estado de super usuario
        inicializarTablaCategorias();

        // Forzar actualizaci�n despu�s de inicializar
        setTimeout(() => {
          if (tablaCategorias && typeof tablaCategorias.draw === 'function') {
            tablaCategorias.draw(false);
          }
        }, 500);
      })
      .catch((error) => {
        console.error("Error en verificaci�n de super usuario:", error);
        esSuperUsuarioActual = false;
        idUsuarioActual = 0;

        // A�n as� intentar inicializar la tabla en caso de error
        inicializarTablaCategorias();
      });
  });
});

function inicializarTablaCategorias() {
  if ($.fn.DataTable.isDataTable('#TablaCategorias')) {
    $('#TablaCategorias').DataTable().destroy();
  }
  
  tablaCategorias = $("#TablaCategorias").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "./categorias/getCategoriasData",
        type: "GET",
        dataSrc: function (json) {
          if (json && Array.isArray(json.data)) {
            // Filtrar categorias seg�n el rol del usuario
            let categoriasFiltradas = json.data;
            
            if (!esSuperUsuarioActual) {
              // Si NO es superusuario, filtrar solo las activas
              categoriasFiltradas = json.data.filter(cat => {
                return cat.estatus && cat.estatus.toLowerCase() !== 'inactivo';
              });
            }
            
            return categoriasFiltradas;
          } else {
            console.error("Respuesta del servidor no tiene la estructura esperada:", json);
            $("#TablaCategorias_processing").css("display", "none");
            Swal.fire({
              icon: "error",
              title: "Error de Datos",
              text: "No se pudieron cargar los datos. Respuesta inv�lida.",
            });
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
          $("#TablaCategorias_processing").css("display", "none");
          Swal.fire({
            icon: "error",
            title: "Error de Comunicaci�n",
            text: "Error al cargar los datos. Por favor, intenta de nuevo.",
          });
        },
      },
      columns: [
        { data: "idcategoria", title: "ID", className: "none" },
        { data: "nombre", title: "Nombre", className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background" },
        { data: "descripcion", title: "Descripci�n", className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" },
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
          className: "all text-center py-2 px-3 w-32",
          render: function (data, type, row) {
            if (!row) return "";
            
            const idCategoria = row.idcategoria || "";
            const nombreCategoria = row.nombre || "";
            const estatusCategoria = row.estatus || "";
            
            // Verificar si la categoria est� inactiva
            const esCategoriaInactiva = estatusCategoria.toUpperCase() === 'INACTIVO';
            
            // Verificar si es categoria protegida del sistema
            const esCategoriaProtegida = CATEGORIAS_SISTEMA.includes(parseInt(idCategoria));
            
            let acciones = '<div class="flex justify-center items-center space-x-1">';
            
            // Bot�n Ver - siempre visible
            acciones += `
              <button class="ver-categoria-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                      data-idcategoria="${idCategoria}" 
                      title="Ver detalles">
                  <i class="fas fa-eye text-sm"></i>
              </button>`;
            
            if (esCategoriaInactiva) {
              // Para categorias inactivas, mostrar solo el bot�n de reactivar
              acciones += `
                <button class="reactivar-categoria-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idcategoria="${idCategoria}" 
                        data-nombre="${nombreCategoria}" 
                        title="Reactivar categoria">
                    <i class="fas fa-undo text-sm"></i>
                </button>`;
            } else {
              // Para categorias activas
              // Bot�n editar - siempre visible para categorias activas
              acciones += `
                <button class="editar-categoria-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                        data-idcategoria="${idCategoria}" 
                        title="Editar categoria">
                    <i class="fas fa-edit text-sm"></i>
                </button>`;
              
              // Bot�n eliminar - solo si NO es categoria protegida
              if (esCategoriaProtegida) {
                acciones += `
                  <button class="text-gray-400 p-1 cursor-not-allowed" 
                          disabled
                          title="Esta categoria del sistema no se puede eliminar">
                      <i class="fas fa-lock text-sm"></i>
                  </button>`;
              } else {
                acciones += `
                  <button class="eliminar-categoria-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                          data-idcategoria="${idCategoria}" 
                          data-nombre="${nombreCategoria}" 
                          title="Eliminar categoria">
                      <i class="fas fa-trash text-sm"></i>
                  </button>`;
              }
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
        emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay categorias disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ categorias",
        infoEmpty: "Mostrando 0 categorias",
        infoFiltered: "(filtrado de _MAX_ categorias totales)",
        lengthMenu: "Mostrar _MENU_ categorias",
        search: "_INPUT_",
        searchPlaceholder: "Buscar categoria...",
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
      order: [[0, "asc"]],
      scrollX: true,
      fixedColumns: {
          left: 1
      },
      initComplete: function (settings, json) {
        window.tablaCategorias = this.api();
        
        setTimeout(() => {
          this.api().draw(false);
        }, 100);
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

  configurarEventosTabla();
  configurarModales();
}

function configurarEventosTabla() {
  // Ver categoria
  $(document).on("click", ".ver-categoria-btn", function () {
    const idCategoria = $(this).data("idcategoria");
    if (idCategoria) {
      abrirModalVerCategoria(idCategoria);
    }
  });

  // Editar categoria
  $(document).on("click", ".editar-categoria-btn", function () {
    const idCategoria = $(this).data("idcategoria");
    if (idCategoria) {
      abrirModalEditarCategoria(idCategoria);
    }
  });

  // Eliminar categoria
  $(document).on("click", ".eliminar-categoria-btn", function () {
    const idCategoria = $(this).data("idcategoria");
    const nombreCategoria = $(this).data("nombre");
    if (idCategoria) {
      confirmarEliminarCategoria(idCategoria, nombreCategoria);
    }
  });

  // Reactivar categoria
  $(document).on("click", ".reactivar-categoria-btn", function () {
    const idCategoria = $(this).data("idcategoria");
    const nombreCategoria = $(this).data("nombre");
    if (idCategoria) {
      confirmarReactivarCategoria(idCategoria, nombreCategoria);
    }
  });
}

function configurarModales() {
  // Modal Registrar
  const btnAbrirModalRegistrar = document.getElementById("btnAbrirModalRegistrarCategoria");
  const btnCerrarModalRegistrar = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistrar = document.getElementById("btnCancelarModalRegistrar");
  const formRegistrar = document.getElementById("formRegistrarCategoria");

  if (btnAbrirModalRegistrar) {
    btnAbrirModalRegistrar.addEventListener("click", () => {
      abrirModal("modalRegistrarCategoria");
      inicializarValidaciones(camposFormularioCategoria, "formRegistrarCategoria");
    });
  }

  if (btnCerrarModalRegistrar) {
    btnCerrarModalRegistrar.addEventListener("click", () => {
      cerrarModal("modalRegistrarCategoria");
      limpiarValidaciones(camposFormularioCategoria, "formRegistrarCategoria");
      if (formRegistrar) formRegistrar.reset();
    });
  }

  if (btnCancelarModalRegistrar) {
    btnCancelarModalRegistrar.addEventListener("click", () => {
      cerrarModal("modalRegistrarCategoria");
      limpiarValidaciones(camposFormularioCategoria, "formRegistrarCategoria");
      if (formRegistrar) formRegistrar.reset();
    });
  }

  if (formRegistrar) {
    formRegistrar.addEventListener("submit", async (e) => {
      e.preventDefault();
      await registrarCategoria();
    });
  }

  // Modal Actualizar
  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarCategoria");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", () => {
      cerrarModal("modalActualizarCategoria");
      limpiarValidaciones(camposFormularioActualizarCategoria, "formActualizarCategoria");
      if (formActualizar) formActualizar.reset();
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", () => {
      cerrarModal("modalActualizarCategoria");
      limpiarValidaciones(camposFormularioActualizarCategoria, "formActualizarCategoria");
      if (formActualizar) formActualizar.reset();
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", async (e) => {
      e.preventDefault();
      await actualizarCategoria();
    });
  }

  // Modal Ver
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", () => {
      cerrarModal("modalVerCategoria");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", () => {
      cerrarModal("modalVerCategoria");
    });
  }
}

async function registrarCategoria() {
  const btnGuardar = document.getElementById("btnGuardarCategoria");
  
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  registrarEntidad({
    formId: "formRegistrarCategoria",
    endpoint: "./categorias/crearCategoria",
    campos: camposFormularioCategoria,
    mapeoNombres: {
      "categoriaNombre": "nombre",
      "categoriaDescripcion": "descripcion"
    },
    onSuccess: (result) => {
      Swal.fire("��xito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarCategoria");
        const form = document.getElementById("formRegistrarCategoria");
        if (form) {
          form.reset();
          limpiarValidaciones(camposFormularioCategoria, "formRegistrarCategoria");
        }
        recargarTablaCategorias();
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo registrar la categoria.",
        "error"
      );
    }
  }).finally(() => {
    if (btnGuardar) {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar categoria`;
    }
  });
}

async function abrirModalEditarCategoria(idCategoria) {
  try {
    const response = await fetch(`./categorias/getCategoriaById/${idCategoria}`);
    const data = await response.json();

    if (data.status && data.data) {
      const categoria = data.data;
      
      document.getElementById("idCategoriaActualizar").value = categoria.idcategoria || "";
      document.getElementById("categoriaNombreActualizar").value = categoria.nombre || "";
      document.getElementById("categoriaDescripcionActualizar").value = categoria.descripcion || "";

      abrirModal("modalActualizarCategoria");
      inicializarValidaciones(camposFormularioActualizarCategoria, "formActualizarCategoria");
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message || "No se pudo cargar la informaci�n de la categoria.",
      });
    }
  } catch (error) {
    console.error("Error al cargar categoria:", error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al cargar los datos de la categoria.",
    });
  }
}

async function actualizarCategoria() {
  const idCategoria = document.getElementById("idCategoriaActualizar").value;
  
  const formData = new FormData(document.getElementById("formActualizarCategoria"));
  const data = {
    idcategoria: idCategoria,
    nombre: formData.get("nombre"),
    descripcion: formData.get("descripcion"),
    estatus: "activo"
  };

  try {
    const response = await fetch("./categorias/actualizarCategoria", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });

    const result = await response.json();

    if (result.status) {
      Swal.fire({
        icon: "success",
        title: "��xito!",
        text: result.message,
        timer: 2000,
        showConfirmButton: false,
      });

      const form = document.getElementById("formActualizarCategoria");
      cerrarModal("modalActualizarCategoria");
      limpiarValidaciones(camposFormularioActualizarCategoria, "formActualizarCategoria");
      if (form) form.reset();
      recargarTablaCategorias();
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: result.message,
      });
    }
  } catch (error) {
    console.error("Error:", error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al actualizar la categoria.",
    });
  }
}

async function abrirModalVerCategoria(idCategoria) {
  try {
    const response = await fetch(`./categorias/getCategoriaById/${idCategoria}`);
    const data = await response.json();

    if (data.status && data.data) {
      const categoria = data.data;
      
      document.getElementById("verCategoriaId").textContent = categoria.idcategoria || "-";
      document.getElementById("verCategoriaNombre").textContent = categoria.nombre || "-";
      document.getElementById("verCategoriaDescripcion").textContent = categoria.descripcion || "-";
      document.getElementById("verCategoriaEstatus").textContent = categoria.estatus || "-";

      abrirModal("modalVerCategoria");
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message || "No se pudo cargar la informaci�n de la categoria.",
      });
    }
  } catch (error) {
    console.error("Error al cargar categoria:", error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al cargar los datos de la categoria.",
    });
  }
}

function confirmarEliminarCategoria(idCategoria, nombreCategoria) {
  Swal.fire({
    title: "�Est�s seguro?",
    html: `�Deseas eliminar la categoria <strong>${nombreCategoria}</strong>?<br><small class="text-gray-500">Esta acci�n cambiar� el estado a inactivo.</small>`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#00c950",
    confirmButtonText: "S�, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      eliminarCategoria(idCategoria);
    }
  });
}

async function eliminarCategoria(idCategoria) {
  try {
    const response = await fetch(`./categorias/deleteCategoria/${idCategoria}`, {
      method: "DELETE",
    });

    const data = await response.json();

    if (data.status) {
      Swal.fire({
        icon: "success",
        title: "�Eliminada!",
        text: data.message,
        timer: 2000,
        showConfirmButton: false,
      });
      recargarTablaCategorias();
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message,
      });
    }
  } catch (error) {
    console.error("Error:", error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al eliminar la categoria.",
    });
  }
}

function confirmarReactivarCategoria(idCategoria, nombreCategoria) {
  Swal.fire({
    title: "�Reactivar categoria?",
    html: `�Deseas reactivar la categoria <strong>${nombreCategoria}</strong>?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#00c950",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "S�, reactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      reactivarCategoria(idCategoria);
    }
  });
}

async function reactivarCategoria(idCategoria) {
  try {
    const response = await fetch(`./categorias/reactivarCategoria`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ idcategoria: idCategoria }),
    });

    const data = await response.json();

    if (data.status) {
      Swal.fire({
        icon: "success",
        title: "�Reactivada!",
        text: data.message,
        timer: 2000,
        showConfirmButton: false,
      });
      recargarTablaCategorias();
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message,
      });
    }
  } catch (error) {
    console.error("Error:", error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al reactivar la categoria.",
    });
  }
}
