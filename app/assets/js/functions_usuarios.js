import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaUsuarios;

const camposFormularioUsuario = [
  {
    id: "usuarioNombre",
    tipo: "input",
    regex: expresiones.usuario,
    mensajes: {
      vacio: "El nombre de usuario es obligatorio.",
      formato: "Nombre de usuario inválido.",
    },
  },
  {
    id: "usuarioCorreo",
    tipo: "input",
    regex: expresiones.email,
    mensajes: {
      vacio: "El correo es obligatorio.",
      formato: "Correo inválido.",
    },
  },
  {
    id: "usuarioClave",
    tipo: "input",
    regex: expresiones.password,
    mensajes: {
      vacio: "La contraseña es obligatoria.",
      formato: "Contraseña inválida (mínimo 6 caracteres).",
    },
  },
  {
    id: "usuarioRol",
    tipo: "select",
    mensajes: { vacio: "Seleccione un rol." },
  },
  { 
    id: "usuarioPersona",
    tipo: "select",
    mensajes: {}, 
  },
];

const camposFormularioActualizarUsuario = [
  {
    id: "usuarioNombreActualizar",
    tipo: "input",
    regex: expresiones.usuario,
    mensajes: {
      vacio: "El nombre de usuario es obligatorio.",
      formato: "Nombre de usuario inválido.",
    },
  },
  {
    id: "usuarioCorreoActualizar",
    tipo: "input",
    regex: expresiones.email,
    mensajes: {
      vacio: "El correo es obligatorio.",
      formato: "Correo inválido.",
    },
  },
  {
    id: "usuarioClaveActualizar", 
    tipo: "input",
    regex: expresiones.password, 
    mensajes: {
      formato: "Contraseña inválida (dejar en blanco para no cambiar, mínimo 6 caracteres).",
    },
  },
  {
    id: "usuarioRolActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un rol." },
  },
  { 
    id: "usuarioPersonaActualizar",
    tipo: "select",
    mensajes: {}, 
  },
];


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


function cerrarModalPermisosDenegados() {
  const modal = document.getElementById('modalPermisosDenegados');
  if (modal) {
    modal.classList.add('opacity-0', 'pointer-events-none');
  }
}

function tienePermiso(accion) {
  return window.permisosUsuarios && window.permisosUsuarios[accion] === true;
}

document.addEventListener("DOMContentLoaded", function () {

  const btnCerrarModalPermisos = document.getElementById('btnCerrarModalPermisos');
  const btnCerrarModalPermisos2 = document.getElementById('btnCerrarModalPermisos2');
  
  if (btnCerrarModalPermisos) {
    btnCerrarModalPermisos.addEventListener('click', cerrarModalPermisosDenegados);
  }
  
  if (btnCerrarModalPermisos2) {
    btnCerrarModalPermisos2.addEventListener('click', cerrarModalPermisosDenegados);
  }

  cargarRoles();
  cargarPersonas(); 

  $(document).ready(function () {
  
    if (!tienePermiso('ver')) {
      console.warn('Sin permisos para ver usuarios');
      return;
    }

    if ($.fn.DataTable.isDataTable("#TablaUsuarios")) {
      $("#TablaUsuarios").DataTable().destroy();
    }

    tablaUsuarios = $("#TablaUsuarios").DataTable({
      processing: true,
      ajax: {
        url: "Usuarios/getUsuariosData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json
            );
            $("#TablaUsuarios_processing").css("display", "none"); 
            
            //  VERIFICAR SI ES ERROR DE PERMISOS
            if (json && json.message && json.message.includes('permisos')) {
              mostrarModalPermisosDenegados(json.message);
            } else {
              alert("Error: No se pudieron cargar los datos de usuarios correctamente.");
            }
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para TablaUsuarios: ",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#TablaUsuarios_processing").css("display", "none"); 
          
          //  VERIFICAR SI ES ERROR DE PERMISOS
          try {
            const response = JSON.parse(jqXHR.responseText);
            if (response && response.message && response.message.includes('permisos')) {
              mostrarModalPermisosDenegados(response.message);
              return;
            }
          } catch (e) {
            // No es JSON válido, continuar con error genérico
          }
          
          alert("Error de comunicación al cargar los datos de usuarios. Por favor, intente más tarde.");
        },
      },
      columns: [
        {
          data: "usuario", 
          title: "Usuario",
          className:
            "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background break-all",
        },
        {
          data: "correo",
          title: "Correo",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700 break-all",
        },
        {
          data: "rol_nombre",
          title: "Rol",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "persona_nombre_completo",
          title: "Persona Asociada",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data) {
              return data;
            }
            return '<i class="text-gray-400">Sin asociar</i>';
          },
        },
        {
          data: "estatus",
          title: "Estatus",
          className: "min-tablet-p text-center py-2 px-3",
          render: function (data, type, row) {
            if (data) {
              const estatusUpper = String(data).toUpperCase();
              if (estatusUpper === "ACTIVO") {
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
            const nombreUsuarioParaEliminar = row.usuario || row.correo;
            let acciones = '<div class="inline-flex items-center space-x-1">';
            
            //  VER - Solo si tiene permisos
            if (tienePermiso('ver')) {
              acciones += `
                <button class="ver-usuario-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idusuario="${row.idusuario}" 
                        title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>`;
            }
            
            //  EDITAR - Solo si tiene permisos
            if (tienePermiso('editar')) {
              acciones += `
                <button class="editar-usuario-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                        data-idusuario="${row.idusuario}" 
                        title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>`;
            }
            
            //  ELIMINAR - Solo si tiene permisos
            if (tienePermiso('eliminar')) {
              acciones += `
                <button class="eliminar-usuario-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                        data-idusuario="${row.idusuario}" 
                        data-nombre="${nombreUsuarioParaEliminar}" 
                        title="Desactivar">
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
        emptyTable:
          '<div class="text-center py-4"><i class="fas fa-users-slash fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay usuarios disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
        infoEmpty: "Mostrando 0 usuarios",
        infoFiltered: "(filtrado de _MAX_ usuarios totales)",
        lengthMenu: "Mostrar _MENU_ usuarios",
        search: "_INPUT_",
        searchPlaceholder: "Buscar usuario...",
        zeroRecords:
          '<div class="text-center py-4"><i class="fas fa-user-times fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
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
              ? $(
                  '<table class="w-full table-fixed details-table border-t border-gray-200"/>'
                ).append(data)
              : false;
          },
        },
      },
      autoWidth: false,
      pageLength: 10,
      lengthMenu: [
        [10, 25, 50, -1],
        [10, 25, 50, "Todos"],
      ],
      order: [[0, "asc"]], 
      scrollX: true,
      fixedColumns: {
        left: 1, 
      },
      className: "compact",
      initComplete: function (settings, json) {
        window.tablaUsuarios = this.api();
      },
      drawCallback: function (settings) {
        $(settings.nTableWrapper)
          .find('.dataTables_filter input[type="search"]')
          .addClass(
            "py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white"
          )
          .removeClass("form-control-sm");

        var api = new $.fn.dataTable.Api(settings);
        if (
          api.fixedColumns &&
          typeof api.fixedColumns === "function" &&
          api.fixedColumns().relayout
        ) {
          api.fixedColumns().relayout();
        }
      },
    });

    //  EVENT LISTENERS CON VERIFICACIÓN DE PERMISOS
    $("#TablaUsuarios tbody").on("click", ".ver-usuario-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('ver')) {
        mostrarModalPermisosDenegados("No tienes permisos para ver detalles de usuarios.");
        return;
      }
      
      const idUsuario = $(this).data("idusuario");
      if (idUsuario) {
        verUsuario(idUsuario);
      } else {
        console.error("ID de usuario no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del usuario.", "error");
      }
    });

    $("#TablaUsuarios tbody").on("click", ".editar-usuario-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('editar')) {
        mostrarModalPermisosDenegados("No tienes permisos para editar usuarios.");
        return;
      }
      
      const idUsuario = $(this).data("idusuario");
      if (idUsuario) {
        editarUsuario(idUsuario);
      } else {
        console.error("ID de usuario no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del usuario.", "error");
      }
    });

    $("#TablaUsuarios tbody").on("click", ".eliminar-usuario-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('eliminar')) {
        mostrarModalPermisosDenegados("No tienes permisos para eliminar usuarios.");
        return;
      }
      
      const idUsuario = $(this).data("idusuario");
      const nombreUsuario = $(this).data("nombre"); 
      if (idUsuario) {
        eliminarUsuario(idUsuario, nombreUsuario);
      } else {
        console.error("ID de usuario no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del usuario.", "error");
      }
    });
  });

  //  BOTÓN REGISTRAR CON VERIFICACIÓN DE PERMISOS
  const btnAbrirModalRegistro = document.getElementById("btnAbrirModalRegistrarUsuario");
  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('crear')) {
        mostrarModalPermisosDenegados("No tienes permisos para crear usuarios.");
        return;
      }
      
      const formRegistrar = document.getElementById("formRegistrarUsuario");
      abrirModal("modalRegistrarUsuario");
      if (formRegistrar) formRegistrar.reset();
      limpiarValidaciones(camposFormularioUsuario, "formRegistrarUsuario");
      inicializarValidaciones(camposFormularioUsuario, "formRegistrarUsuario");
      cargarPersonas(0, "usuarioPersona"); 
    });
  }

  //  BOTÓN EXPORTAR CON VERIFICACIÓN DE PERMISOS
  const btnExportarUsuarios = document.getElementById("btnExportarUsuarios");
  if (btnExportarUsuarios) {
    btnExportarUsuarios.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('exportar')) {
        mostrarModalPermisosDenegados("No tienes permisos para exportar usuarios.");
        return;
      }
      
      exportarUsuarios();
    });
  }

  // Event listeners para modales (sin cambios)
  const formRegistrar = document.getElementById("formRegistrarUsuario");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistro = document.getElementById("btnCancelarModalRegistrar");

  if (btnCerrarModalRegistro) btnCerrarModalRegistro.addEventListener("click", () => cerrarModal("modalRegistrarUsuario"));
  if (btnCancelarModalRegistro) btnCancelarModalRegistro.addEventListener("click", () => cerrarModal("modalRegistrarUsuario"));
  if (formRegistrar) formRegistrar.addEventListener("submit", (e) => { e.preventDefault(); registrarUsuario(); });

  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarUsuario");

  if (btnCerrarModalActualizar) btnCerrarModalActualizar.addEventListener("click", () => cerrarModal("modalActualizarUsuario"));
  if (btnCancelarModalActualizar) btnCancelarModalActualizar.addEventListener("click", () => cerrarModal("modalActualizarUsuario"));
  if (formActualizar) formActualizar.addEventListener("submit", (e) => { e.preventDefault(); actualizarUsuario(); });

  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) btnCerrarModalVer.addEventListener("click", () => cerrarModal("modalVerUsuario"));
  if (btnCerrarModalVer2) btnCerrarModalVer2.addEventListener("click", () => cerrarModal("modalVerUsuario"));
});

function cargarRoles() {
  fetch("Usuarios/getRoles", { 
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
   })
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        const selects = ["usuarioRol", "usuarioRolActualizar"];
        selects.forEach(selectId => {
          const selectElement = document.getElementById(selectId);
          if (selectElement) {
            selectElement.innerHTML = '<option value="">Seleccione un rol</option>';
            result.data.forEach(rol => {
              selectElement.innerHTML += `<option value="${rol.idrol}">${rol.nombre}</option>`;
            });
          }
        });
      } else {
        //  VERIFICAR SI ES ERROR DE PERMISOS
        if (result && result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        }
      }
    })
    .catch(error => console.error("Error al cargar roles:", error));
}

function cargarPersonas(idPersonaActual = 0, targetSelectId = null) {
  let url = "Usuarios/getPersonas";
  if (idPersonaActual && idPersonaActual > 0) {
    url += `?idPersonaActual=${idPersonaActual}`;
  }

  fetch(url, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const selectPersonaRegistrar = document.getElementById("usuarioPersona");
        const selectPersonaActualizar = document.getElementById("usuarioPersonaActualizar");

        const populateSelect = (selectElement, currentIdToSelect) => {
          if (selectElement) {
            selectElement.innerHTML = '<option value="">Sin persona asociada</option>'; 
            result.data.forEach((persona) => {
              const option = document.createElement('option');
              option.value = persona.idpersona;
              option.textContent = persona.nombre_completo; 
              selectElement.appendChild(option);
            });
            if (currentIdToSelect && result.data.some(p => p.idpersona == currentIdToSelect)) {
              selectElement.value = currentIdToSelect;
            } else {
              selectElement.value = ""; 
            }
          }
        };
        
        if (targetSelectId) { 
            populateSelect(document.getElementById(targetSelectId), idPersonaActual);
        } else { 
            if(selectPersonaRegistrar) populateSelect(selectPersonaRegistrar, 0); 
            if(selectPersonaActualizar) populateSelect(selectPersonaActualizar, 0); 
        }

      } else {
        //  VERIFICAR SI ES ERROR DE PERMISOS
        if (result && result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          console.error("Error al cargar personas:", result.message || "Datos no válidos");
        }
      }
    })
    .catch((error) => {
      console.error("Error en fetch al cargar personas:", error);
    });
}

function registrarUsuario() {
  //  VERIFICAR PERMISOS ANTES DE PROCESAR
  if (!tienePermiso('crear')) {
    mostrarModalPermisosDenegados("No tienes permisos para crear usuarios.");
    return;
  }

  const formRegistrar = document.getElementById("formRegistrarUsuario");
  const btnGuardarUsuario = document.getElementById("btnGuardarUsuario");

  const camposObligatoriosRegistrar = camposFormularioUsuario.filter(c => c.mensajes && c.mensajes.vacio);
  if (!validarCamposVacios(camposObligatoriosRegistrar, "formRegistrarUsuario")) return;

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioUsuario) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;
    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) { 
        esValidoEsteCampo = validarSelect(campo.id, campo.mensajes, "formRegistrarUsuario");
      }
    } else if (["input", "email", "password", "text"].includes(campo.tipo)) {
      esValidoEsteCampo = validarCampo(inputElement, campo.regex, campo.mensajes);
    }
    if (!esValidoEsteCampo) formularioConErroresEspecificos = true;
  }

  if (formularioConErroresEspecificos) {
    Swal.fire("Atención", "Por favor, corrija los campos marcados.", "warning");
    return;
  }

  const formData = new FormData(formRegistrar);
  const dataParaEnviar = {
    usuario: formData.get("usuario") || "",
    clave: formData.get("clave") || "",
    correo: formData.get("correo") || "",
    idrol: formData.get("idrol") || "",
    personaId: formData.get("personaId") || null, 
  };

  btnGuardarUsuario.disabled = true;
  btnGuardarUsuario.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;

  fetch("Usuarios/createUsuario", { 
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify(dataParaEnviar) 
  })
    .then(response => response.ok ? response.json() : response.json().then(err => { throw err; }))
    .then(result => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalRegistrarUsuario");
        if (tablaUsuarios && tablaUsuarios.ajax) tablaUsuarios.ajax.reload(null, false);
      } else {
        //  VERIFICAR SI ES ERROR DE PERMISOS
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: result.message || "No se pudo registrar el usuario.",
            confirmButtonColor: "#3085d6"
          });
        }
      }
    })
    .catch(error => {
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", error.message || "Error de conexión.", "error");
      }
    })
    .finally(() => {
      btnGuardarUsuario.disabled = false;
      btnGuardarUsuario.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Usuario`;
    });
}

function editarUsuario(idUsuario) {
  //  VERIFICAR PERMISOS ANTES DE PROCESAR
  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para editar usuarios.");
    return;
  }

  fetch(`Usuarios/getUsuarioById/${idUsuario}`, { 
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
   })
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        mostrarModalEditarUsuario(result.data);
      } else {
        //  VERIFICAR SI ES ERROR DE PERMISOS
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "No se pudieron cargar los datos.", "error");
        }
      }
    })
    .catch(error => {
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", "Error de conexión.", "error");
      }
    });
}

function mostrarModalEditarUsuario(usuario) {
  const formActualizar = document.getElementById("formActualizarUsuario");
  if (formActualizar) formActualizar.reset();
  limpiarValidaciones(camposFormularioActualizarUsuario, "formActualizarUsuario");

  document.getElementById("idUsuarioActualizar").value = usuario.idusuario || "";
  document.getElementById("usuarioNombreActualizar").value = usuario.usuario || "";
  document.getElementById("usuarioCorreoActualizar").value = usuario.correo || "";
  document.getElementById("usuarioRolActualizar").value = usuario.idrol || "";
  document.getElementById("usuarioClaveActualizar").value = ""; 

  cargarPersonas(usuario.personaId || 0, "usuarioPersonaActualizar");
  
  inicializarValidaciones(camposFormularioActualizarUsuario, "formActualizarUsuario");
  abrirModal("modalActualizarUsuario");
}

function actualizarUsuario() {
  //  VERIFICAR PERMISOS ANTES DE PROCESAR
  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para editar usuarios.");
    return;
  }

  const formActualizar = document.getElementById("formActualizarUsuario");
  const btnActualizarUsuario = document.getElementById("btnActualizarUsuario");
  const idUsuario = document.getElementById("idUsuarioActualizar").value;

  const camposObligatoriosActualizar = camposFormularioActualizarUsuario.filter(c => c.mensajes && c.mensajes.vacio);
  if (!validarCamposVacios(camposObligatoriosActualizar, "formActualizarUsuario")) return;

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarUsuario) { 
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;
    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(campo.id, campo.mensajes, "formActualizarUsuario");
      }
    } else if (["input", "email", "password", "text"].includes(campo.tipo)) {
      if (inputElement.value.trim() !== "" || (campo.mensajes && campo.mensajes.vacio && campo.id !== "usuarioClaveActualizar")) {
        if (campo.id === "usuarioClaveActualizar" && inputElement.value.trim() === "") {
            esValidoEsteCampo = true; 
        } else {
            esValidoEsteCampo = validarCampo(inputElement, campo.regex, campo.mensajes);
        }
      }
    }
    if (!esValidoEsteCampo) formularioConErroresEspecificos = true;
  }

  if (formularioConErroresEspecificos) {
    Swal.fire("Atención", "Por favor, corrija los campos marcados.", "warning");
    return;
  }

  const formData = new FormData(formActualizar);
  const dataParaEnviar = {
    idusuario: idUsuario,
    usuario: formData.get("usuario") || "",
    correo: formData.get("correo") || "",
    idrol: formData.get("idrol") || "",
    personaId: formData.get("personaId") || null, 
    clave: formData.get("clave") || "", 
  };

  btnActualizarUsuario.disabled = true;
  btnActualizarUsuario.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;

  fetch("Usuarios/updateUsuario", { 
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify(dataParaEnviar) 
  })
    .then(response => response.ok ? response.json() : response.json().then(err => { throw err; }))
    .then(result => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalActualizarUsuario");
        if (tablaUsuarios && tablaUsuarios.ajax) tablaUsuarios.ajax.reload(null, false);
      } else {
        //  VERIFICAR SI ES ERROR DE PERMISOS
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: result.message || "No se pudo actualizar el usuario.",
            confirmButtonColor: "#3085d6"
          });
        }
      }
    })
    .catch(error => {
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", error.message || "Error de conexión.", "error");
      }
    })
    .finally(() => {
      btnActualizarUsuario.disabled = false;
      btnActualizarUsuario.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Usuario`;
    });
}

function verUsuario(idUsuario) {
  //  VERIFICAR PERMISOS ANTES DE PROCESAR
  if (!tienePermiso('ver')) {
    mostrarModalPermisosDenegados("No tienes permisos para ver detalles de usuarios.");
    return;
  }

  fetch(`Usuarios/getUsuarioById/${idUsuario}`, { 
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
   })
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        const usuario = result.data;
        document.getElementById("verUsuarioNombre").textContent = usuario.usuario || "N/A";
        document.getElementById("verUsuarioCorreo").textContent = usuario.correo || "N/A";
        document.getElementById("verUsuarioRol").textContent = usuario.rol_nombre || "N/A";
        document.getElementById("verUsuarioEstatus").textContent = usuario.estatus || "N/A";
        document.getElementById("verPersonaNombre").textContent = usuario.persona_nombre_completo || "Sin persona asociada";
        document.getElementById("verPersonaCedula").textContent = usuario.persona_cedula || "N/A";
        abrirModal("modalVerUsuario");
      } else {
        //  VERIFICAR SI ES ERROR DE PERMISOS
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "No se pudieron cargar los datos.", "error");
        }
      }
    })
    .catch(error => {
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", "Error de conexión.", "error");
      }
    });
}

function eliminarUsuario(idUsuario, nombreUsuario) {
  //  VERIFICAR PERMISOS ANTES DE PROCESAR
  if (!tienePermiso('eliminar')) {
    mostrarModalPermisosDenegados("No tienes permisos para eliminar usuarios.");
    return;
  }

  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar al usuario ${nombreUsuario}? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Usuarios/deleteUsuario", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify({ idusuario: idUsuario }),
      })
        .then(response => response.json())
        .then(result => {
          if (result.status) {
            Swal.fire("¡Desactivado!", result.message, "success");
            if (tablaUsuarios && tablaUsuarios.ajax) tablaUsuarios.ajax.reload(null, false);
          } else {
            //  VERIFICAR SI ES ERROR DE PERMISOS
            if (result.message && result.message.includes('permisos')) {
              mostrarModalPermisosDenegados(result.message);
            } else {
              Swal.fire("Error", result.message || "No se pudo desactivar.", "error");
            }
          }
        })
        .catch(error => {
          if (error.message && error.message.includes('permisos')) {
            mostrarModalPermisosDenegados(error.message);
          } else {
            Swal.fire("Error", "Error de conexión.", "error");
          }
        });
    }
  });
}

//  FUNCIÓN DE EXPORTAR (NUEVA)
function exportarUsuarios() {
  if (!tienePermiso('exportar')) {
    mostrarModalPermisosDenegados("No tienes permisos para exportar usuarios.");
    return;
  }

  fetch("Usuarios/exportarUsuarios", {
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        // Aquí puedes implementar la lógica de exportación
        // Por ejemplo, generar CSV, Excel, PDF, etc.
        console.log("Datos para exportar:", result.data);
        Swal.fire("¡Éxito!", "Datos preparados para exportación.", "success");
      } else {
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "Error al exportar usuarios.", "error");
        }
      }
    })
    .catch(error => {
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", "Error de conexión.", "error");
      }
    });
}