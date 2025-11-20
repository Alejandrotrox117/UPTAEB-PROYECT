import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  expresiones
} from "./validaciones.js";

let tablaRoles;

const camposFormularioRol = [
  {
    id: "nombreRol",
    tipo: "input",
    mensajes: { vacio: "El nombre del rol es obligatorio.", formato: "El nombre debe tener entre 3 y 50 caracteres, solo letras y espacios." },
    expresion: expresiones.nombre
  },
  {
    id: "descripcionRol",
    tipo: "textarea",
    opcional:true,
    mensajes: { vacio: "La descripción es obligatoria." },
    expresion: expresiones.textoGeneral
  },
  {
    id: "estatusRol",
    tipo: "select",
    mensajes: { vacio: "Seleccione un estatus." },
  },
];

const camposFormularioActualizarRol = [
  {
    id: "nombreActualizar",
    tipo: "input",
    mensajes: { vacio: "El nombre del rol es obligatorio.", formato: "El nombre debe tener entre 3 y 50 caracteres, solo letras y espacios." },
    expresion: expresiones.nombre
  },
  {
    id: "descripcionActualizar",
    tipo: "textarea",
    mensajes: { vacio: "La descripción del rol es obligatoria.", formato: "La descripción debe tener entre 10 y 255 caracteres." },
    expresion: expresiones.textoGeneral
  },
  {
    id: "estatusActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un estatus." },
  },
];

function generarBotonesAccion(row) {
  const permisos = window.permisosRoles || {};
  const esSuper = window.esSuperUsuario || false;
  const estatus = (row.estatus || "").toUpperCase();
  let botones = "";

  if (row.idrol == 1) {
    return '<span class="text-gray-400 text-xs">Sin acciones</span>';
  }

  if (permisos.ver) {
    botones += `
      <button class="ver-rol-btn text-green-600 hover:text-green-700 p-1" 
              data-idrol="${row.idrol}" title="Ver detalles">
        <i class="fas fa-eye fa-fw text-base"></i>
      </button>`;
  }

  if (estatus === "ACTIVO") {
    if (permisos.editar) {
      botones += `
        <button class="editar-rol-btn text-blue-600 hover:text-blue-700 p-1" 
                data-idrol="${row.idrol}" title="Editar">
          <i class="fas fa-edit fa-fw text-base"></i>
        </button>`;
    }
    if (permisos.eliminar) {
      botones += `
        <button class="eliminar-rol-btn text-red-600 hover:text-red-700 p-1" 
                data-idrol="${row.idrol}" data-nombre="${row.nombre}" title="Desactivar">
          <i class="fas fa-trash-alt fa-fw text-base"></i>
        </button>`;
    }
  } else if (estatus === "INACTIVO" && esSuper && permisos.editar) {
    botones += `
      <button class="reactivar-rol-btn text-teal-600 hover:text-teal-700 p-1" 
              data-idrol="${row.idrol}" data-nombre="${row.nombre}" title="Reactivar rol">
        <i class="fas fa-undo fa-fw text-base"></i>
      </button>`;
  }

  return `<div class="inline-flex items-center space-x-1">${botones}</div>`;
}

document.addEventListener("DOMContentLoaded", function () {
  if ($.fn.DataTable.isDataTable("#TablaRoles")) {
    $("#TablaRoles").DataTable().destroy();
  }

  tablaRoles = $("#TablaRoles").DataTable({
    processing: true,
    ajax: {
      url: "Roles/getRolesData",
      type: "GET",
      /**
       * ✅ FUNCIÓN MODIFICADA: Filtra los datos antes de mostrarlos.
       * Si el usuario NO es superusuario, se elimina el rol con ID 1 de la lista.
       * Esto oculta el rol de "Superusuario" a todos los demás.
       */
      dataSrc: function (json) {
        if (!json.data) {
          return [];
        }

        if (!window.esSuperUsuario) {
          return json.data.filter((rol) => rol.idrol != 1);
        }

        return json.data;
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Error AJAX:", textStatus, errorThrown);
        Swal.fire(
          "Error",
          "No se pudieron cargar los datos de roles.",
          "error"
        );
      },
    },
    columns: [
      { data: "nombre", title: "Nombre" },
      { data: "descripcion", title: "Descripción" },
      {
        data: "estatus",
        title: "Estatus",
        className: "text-center",
        render: function (data) {
          const estatusUpper = String(data).toUpperCase();
          if (estatusUpper === "ACTIVO") {
            return `<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full">${data}</span>`;
          }
          return `<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-1 rounded-full">${data}</span>`;
        },
      },
      { data: "fecha_creacion", title: "Fecha Creación" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        searchable: false,
        className: "text-center",
        render: function (data, type, row) {
          return generarBotonesAccion(row);
        },
      },
    ],
    language: {
      processing: `
        <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] flex items-center justify-center z-[9999]">
            <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
                <span class="text-lg font-medium text-gray-700">Procesando...</span>
            </div>
        </div>`,
      emptyTable: "No hay roles disponibles.",
      info: "Mostrando _START_ a _END_ de _TOTAL_ roles",
      infoEmpty: "Mostrando 0 roles",
      search: "_INPUT_",
      searchPlaceholder: "Buscar rol...",
      zeroRecords: "No se encontraron coincidencias.",
      paginate: {
        first: '<i class="fas fa-angle-double-left"></i>',
        last: '<i class="fas fa-angle-double-right"></i>',
        next: '<i class="fas fa-angle-right"></i>',
        previous: '<i class="fas fa-angle-left"></i>',
      },
    },
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    order: [[0, "asc"]],
  });

  $("#TablaRoles tbody").on("click", ".ver-rol-btn", function () {
    verRol($(this).data("idrol"));
  });
  $("#TablaRoles tbody").on("click", ".editar-rol-btn", function () {
    editarRol($(this).data("idrol"));
  });
  $("#TablaRoles tbody").on("click", ".eliminar-rol-btn", function () {
    eliminarRol($(this).data("idrol"), $(this).data("nombre"));
  });
  $("#TablaRoles tbody").on("click", ".reactivar-rol-btn", function () {
    reactivarRol($(this).data("idrol"), $(this).data("nombre"));
  });

  setupModalEventListeners();
});

function setupModalEventListeners() {
  const formRegistrar = document.getElementById("formRegistrarRol");
  const formActualizar = document.getElementById("formActualizarRol");

  $("#btnAbrirModalRegistrarRol").on("click", () => {
    formRegistrar?.reset();
    abrirModal("modalRegistrarRol");
    inicializarValidaciones(camposFormularioRol, "formRegistrarRol");
  });

  $("#btnCerrarModalRegistrar, #btnCancelarModalRegistrar").on("click", () =>
    cerrarModal("modalRegistrarRol")
  );
  $("#btnCerrarModalActualizar, #btnCancelarModalActualizar").on("click", () =>
    cerrarModal("modalActualizarRol")
  );
  $("#btnCerrarModalVer, #btnCerrarModalVer2").on("click", () =>
    cerrarModal("modalVerRol")
  );

  formRegistrar?.addEventListener("submit", (e) => {
    e.preventDefault();
    registrarRol();
  });
  formActualizar?.addEventListener("submit", (e) => {
    e.preventDefault();
    actualizarRol();
  });
}

function registrarRol() {
  const form = document.getElementById("formRegistrarRol");
  if (!validarCamposVacios(camposFormularioRol, "formRegistrarRol")) return;

  // Validar expresiones y formatos
  let formularioValido = true;
  camposFormularioRol.forEach(campo => {
    if (campo.opcional) return;
    
    const inputElement = form.querySelector(`#${campo.id}`);
    if (!inputElement || inputElement.offsetParent === null) return;
    
    let esValido = true;
    if (campo.tipo === "select") {
      esValido = validarSelect(inputElement, campo.mensajes);
    } else if (campo.expresion) {
      esValido = validarCampo(inputElement, campo.expresion, campo.mensajes);
    }
    
    if (!esValido) formularioValido = false;
  });
  
  if (!formularioValido) return;

  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  fetch("Roles/createRol", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalRegistrarRol");
        tablaRoles.ajax.reload(null, false);
      } else {
        Swal.fire("Error", result.message, "error");
      }
    })
    .catch(() => Swal.fire("Error", "Ocurrió un error de conexión.", "error"));
}

function editarRol(idRol) {
  fetch(`Roles/getRolById/${idRol}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        const rol = result.data;
        $("#idRolActualizar").val(rol.idrol);
        $("#nombreActualizar").val(rol.nombre);
        $("#descripcionActualizar").val(rol.descripcion);
        $("#estatusActualizar").val(rol.estatus);
        abrirModal("modalActualizarRol");
        inicializarValidaciones(
          camposFormularioActualizarRol,
          "formActualizarRol"
        );
      } else {
        Swal.fire("Error", result.message, "error");
      }
    });
}

function actualizarRol() {
  const form = document.getElementById("formActualizarRol");
  if (!validarCamposVacios(camposFormularioActualizarRol, "formActualizarRol"))
    return;

  // Validar expresiones y formatos
  let formularioValido = true;
  camposFormularioActualizarRol.forEach(campo => {
    if (campo.opcional) return;
    
    const inputElement = form.querySelector(`#${campo.id}`);
    if (!inputElement || inputElement.offsetParent === null) return;
    
    let esValido = true;
    if (campo.tipo === "select") {
      esValido = validarSelect(inputElement, campo.mensajes);
    } else if (campo.expresion) {
      esValido = validarCampo(inputElement, campo.expresion, campo.mensajes);
    }
    
    if (!esValido) formularioValido = false;
  });
  
  if (!formularioValido) return;

  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  fetch("Roles/updateRol", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalActualizarRol");
        tablaRoles.ajax.reload(null, false);
      } else {
        Swal.fire("Error", result.message, "error");
      }
    })
    .catch(() => Swal.fire("Error", "Ocurrió un error de conexión.", "error"));
}

function verRol(idRol) {
  fetch(`Roles/getRolById/${idRol}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        const rol = result.data;
        $("#verNombre").text(rol.nombre || "-");
        $("#verDescripcion").text(rol.descripcion || "-");
        $("#verEstatus").text(rol.estatus || "-");
        $("#verFechaCreacion").text(rol.fecha_creacion || "-");
        $("#verUltimaModificacion").text(rol.ultima_modificacion || "-");
        abrirModal("modalVerRol");
      } else {
        Swal.fire("Error", result.message, "error");
      }
    });
}

function eliminarRol(idRol, nombreRol) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar el rol "${nombreRol}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#00c950",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Roles/deleteRol", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idrol: idRol }),
      })
        .then((response) => response.json())
        .then((res) => {
          if (res.status) {
            Swal.fire("¡Desactivado!", res.message, "success");
            tablaRoles.ajax.reload(null, false);
          } else {
            Swal.fire("Error", res.message, "error");
          }
        });
    }
  });
}

function reactivarRol(idRol, nombreRol) {
  Swal.fire({
    title: "¿Reactivar Rol?",
    text: `¿Estás seguro de que deseas reactivar el rol "${nombreRol}"?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#00c950",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Sí, reactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Roles/reactivarRol", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idrol: idRol }),
      })
        .then((response) => response.json())
        .then((res) => {
          if (res.status) {
            Swal.fire("¡Reactivado!", res.message, "success");
            tablaRoles.ajax.reload(null, false);
          } else {
            Swal.fire("Error", res.message, "error");
          }
        })
        .catch(() => Swal.fire("Error", "Ocurrió un error de conexión.", "error"));
    }
  });
}