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
let esSuperUsuarioActual = false;
let idUsuarioActual = 0;

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
  // Si es super usuario, siempre tiene permisos
  if (esSuperUsuarioActual) {
    return true;
  }
  
  return window.permisosUsuarios && window.permisosUsuarios[accion] === true;
}

/**
 * Verificar si el usuario actual es super usuario
 */
function verificarSuperUsuario() {
  return fetch("Usuarios/verificarSuperUsuario", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then(response => response.json())
    .then(result => {
      if (result.status) {
        esSuperUsuarioActual = result.es_super_usuario;
        idUsuarioActual = result.usuario_id;
        return result;
      } else {
        console.error('Error al verificar super usuario:', result.message);
        return { es_super_usuario: false, usuario_id: 0 };
      }
    })
    .catch(error => {
      console.error("Error en verificarSuperUsuario:", error);
      return { es_super_usuario: false, usuario_id: 0 };
    });
}

/**
 * Verificar si un usuario es super usuario y si se puede eliminar
 */
function puedeEliminarUsuario(idUsuario, rolId) {
  // No puede eliminarse a sí mismo
  if (idUsuario === idUsuarioActual) {
    return {
      puede_eliminar: false,
      razon: 'No puedes eliminarte a ti mismo.'
    };
  }

  // NUNCA se puede eliminar un super usuario
  if (rolId === 1) {
    return {
      puede_eliminar: false,
      razon: 'Los super usuarios no pueden ser eliminados.'
    };
  }

  return {
    puede_eliminar: true,
    razon: ''
  };
}

/**
 * Verificar si un usuario es super usuario y si se puede editar
 */
function puedeEditarUsuario(idUsuario, rolId) {
  // Si es super usuario
  if (rolId === 1) {
    // Solo puede editarse a sí mismo
    if (idUsuario === idUsuarioActual && esSuperUsuarioActual) {
      return {
        puede_editar: true,
        solo_password: true,
        razon: ''
      };
    } else {
      return {
        puede_editar: false,
        solo_password: false,
        razon: 'Los super usuarios solo pueden editar su propia información.'
      };
    }
  }

  // Usuario normal, puede ser editado normalmente
  return {
    puede_editar: true,
    solo_password: false,
    razon: ''
  };
}

/**
 * Verificar si un usuario es super usuario y si se puede ver
 */
function puedeVerUsuario(idUsuario, rolId) {
  // Si es super usuario
  if (rolId === 1) {
    // Solo puede verse a sí mismo
    if (idUsuario === idUsuarioActual && esSuperUsuarioActual) {
      return {
        puede_ver: true,
        razon: ''
      };
    } else {
      return {
        puede_ver: false,
        razon: 'Los super usuarios solo pueden ver su propia información.'
      };
    }
  }

  // Usuario normal, puede ser visto normalmente
  return {
    puede_ver: true,
    razon: ''
  };
}

function inicializarTablaUsuarios() {
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
          
          try {
            const response = JSON.parse(jqXHR.responseText);
            if (response && response.message && response.message.includes('permisos')) {
              mostrarModalPermisosDenegados(response.message);
              return;
            }
          } catch (e) {
            // JSON no válido, continuar con manejo de error general
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
            const idUsuario = parseInt(row.idusuario);
            const rolId = parseInt(row.idrol);
            const estatusUsuario = (row.estatus || '').toUpperCase();
            const esUsuarioInactivo = estatusUsuario === 'INACTIVO';
            
            // Verificar permisos específicos para este usuario
            const puedeVer = puedeVerUsuario(idUsuario, rolId);
            const puedeEditar = puedeEditarUsuario(idUsuario, rolId);
            const puedeEliminar = puedeEliminarUsuario(idUsuario, rolId);
            
            let acciones = '<div class="inline-flex items-center space-x-1">';
            
            // Botón Ver - solo si tiene permisos generales y específicos
            if (tienePermiso('ver') && puedeVer.puede_ver) {
              acciones += `
                <button class="ver-usuario-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idusuario="${row.idusuario}" 
                        title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>`;
            }
            
            // Botón Editar - solo si tiene permisos generales y específicos y el usuario está activo
            if (tienePermiso('editar') && puedeEditar.puede_editar && !esUsuarioInactivo) {
              acciones += `
                <button class="editar-usuario-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                        data-idusuario="${row.idusuario}" 
                        title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>`;
            }
            
            if (esUsuarioInactivo) {
              // Para usuarios inactivos, mostrar botón de reactivar (solo super usuarios)
              if (esSuperUsuarioActual && tienePermiso('editar')) {
                acciones += `
                  <button class="reactivar-usuario-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                          data-idusuario="${row.idusuario}" 
                          data-nombre="${nombreUsuarioParaEliminar}" 
                          title="Reactivar usuario">
                      <i class="fas fa-undo fa-fw text-base"></i>
                  </button>`;
              }
            } else {
              // Para usuarios activos, mostrar botón de eliminar
              if (tienePermiso('eliminar') && puedeEliminar.puede_eliminar) {
                acciones += `
                  <button class="eliminar-usuario-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                          data-idusuario="${row.idusuario}" 
                          data-nombre="${nombreUsuarioParaEliminar}" 
                          title="Desactivar">
                      <i class="fas fa-trash-alt fa-fw text-base"></i>
                  </button>`;
              }
            }
            
            // Si no tiene ningún permiso, mostrar mensaje
            const tieneAlgunPermiso = (tienePermiso('ver') && puedeVer.puede_ver) || 
                                     (tienePermiso('editar') && puedeEditar.puede_editar && (!esUsuarioInactivo || esSuperUsuarioActual)) || 
                                     (tienePermiso('eliminar') && puedeEliminar.puede_eliminar && !esUsuarioInactivo);
            
            if (!tieneAlgunPermiso) {
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

    // Event handlers para los botones de acciones
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
    });    $("#TablaUsuarios tbody").on("click", ".eliminar-usuario-btn", function (e) {
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

    // Event handler para reactivar usuarios (solo super usuarios)
    $("#TablaUsuarios tbody").on("click", ".reactivar-usuario-btn", function (e) {
      e.preventDefault();
      
      if (!esSuperUsuarioActual) {
        mostrarModalPermisosDenegados("Solo los super usuarios pueden reactivar usuarios.");
        return;
      }
      
      if (!tienePermiso('editar')) {
        mostrarModalPermisosDenegados("No tienes permisos para reactivar usuarios.");
        return;
      }
      
      const idUsuario = $(this).data("idusuario");
      const nombreUsuario = $(this).data("nombre");
      if (idUsuario) {
        reactivarUsuario(idUsuario, nombreUsuario);
      } else {
        console.error("ID de usuario no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del usuario.", "error");
      }
    });
}

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
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else if (result.message && result.message.includes('no tienes permisos para verlo')) {
          mostrarModalPermisosDenegados("No tienes permisos para editar este usuario.");
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
  if (!formActualizar) {
    console.error("Formulario de actualizar no encontrado");
    return;
  }
  
  formActualizar.reset();
  limpiarValidaciones(camposFormularioActualizarUsuario, "formActualizarUsuario");

  const esSuperUsuarioEditandose = (usuario.idrol === 1 && usuario.idusuario === idUsuarioActual && esSuperUsuarioActual);

  // Verificar que todos los elementos existen antes de usarlos
  const elementos = {
    id: document.getElementById("idUsuarioActualizar"),
    nombre: document.getElementById("usuarioNombreActualizar"),
    correo: document.getElementById("usuarioCorreoActualizar"),
    rol: document.getElementById("usuarioRolActualizar"),
    clave: document.getElementById("usuarioClaveActualizar"),
    persona: document.getElementById("usuarioPersonaActualizar"),
    titulo: document.getElementById("tituloModalActualizar"),
    btnActualizar: document.getElementById("btnActualizarUsuario")
  };

  // Verificar que los elementos críticos existen
  if (!elementos.id || !elementos.clave) {
    console.error("Elementos críticos del formulario no encontrados");
    return;
  }

  elementos.id.value = usuario.idusuario || "";
  
  if (esSuperUsuarioEditandose) {
    // Para super usuario editándose: guardar valores originales en campos ocultos
    if (elementos.nombre) {
      elementos.nombre.value = usuario.usuario || "";
      elementos.nombre.name = "usuario"; // Asegurar que el name esté correcto
      elementos.nombre.required = false; // Remover required de campos ocultos
    }
    if (elementos.correo) {
      elementos.correo.value = usuario.correo || "";
      elementos.correo.name = "correo"; // Asegurar que el name esté correcto
      elementos.correo.required = false; // Remover required de campos ocultos
    }
    if (elementos.rol) {
      elementos.rol.value = usuario.idrol || "";
      elementos.rol.name = "idrol"; // Asegurar que el name esté correcto
      elementos.rol.required = false; // Remover required de campos ocultos
    }
    if (elementos.persona) {
      elementos.persona.value = usuario.personaId || "";
      elementos.persona.name = "personaId"; // Asegurar que el name esté correcto
      elementos.persona.required = false; // Remover required de campos ocultos
    }
    elementos.clave.value = "";
    elementos.clave.name = "clave"; // Asegurar que el name esté correcto
    
    // Ocultar TODOS los contenedores de campos excepto contraseña
    const todosLosCampos = formActualizar.querySelectorAll('.mb-4, .grid > div');
    todosLosCampos.forEach(campo => {
      const inputDentro = campo.querySelector('input, select');
      if (inputDentro && inputDentro.id !== 'usuarioClaveActualizar') {
        campo.style.display = 'none';
        // Remover required de cualquier input oculto
        if (inputDentro.hasAttribute('required')) {
          inputDentro.required = false;
        }
      }
    });
    
    // Ocultar grids completos si no contienen el campo de contraseña
    const gridsContenedores = formActualizar.querySelectorAll('.grid');
    gridsContenedores.forEach(grid => {
      const tieneClaveInput = grid.querySelector('#usuarioClaveActualizar');
      if (!tieneClaveInput) {
        grid.style.display = 'none';
        // Remover required de todos los inputs dentro del grid oculto
        const inputsEnGrid = grid.querySelectorAll('input[required], select[required]');
        inputsEnGrid.forEach(input => {
          input.required = false;
        });
      }
    });
    
    // Asegurar que SOLO la contraseña sea visible y requerida
    const claveContainer = elementos.clave.closest('.mb-4') || elementos.clave.closest('div') || elementos.clave.parentNode;
    if (claveContainer) {
      claveContainer.style.display = 'block';
      claveContainer.style.width = '100%';
      if (!claveContainer.className.includes('mb-4')) {
        claveContainer.className += ' mb-4';
      }
    }
    
    elementos.clave.required = true;
    elementos.clave.placeholder = "Nueva contraseña (requerida)";
    elementos.clave.style.display = 'block';
    elementos.clave.style.width = '100%';
    
    // Cambiar el título del modal
    if (elementos.titulo) {
      elementos.titulo.textContent = "Cambiar Contraseña";
    }
    
    // Agregar mensaje informativo
    const existingMessage = document.querySelector('.super-user-edit-message');
    if (!existingMessage) {
      const message = document.createElement('div');
      message.className = 'super-user-edit-message bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6';
      message.innerHTML = `
        <div class="flex items-center">
          <i class="fas fa-shield-alt text-blue-600 mr-3 text-lg"></i>
          <div>
            <div class="text-blue-800 text-sm font-semibold">Modo Super Usuario</div>
            <div class="text-blue-700 text-xs mt-1">Como super usuario, solo puedes cambiar tu contraseña por seguridad.</div>
          </div>
        </div>
      `;
      formActualizar.insertBefore(message, formActualizar.firstChild);
    }
    
    // Cambiar el texto del botón
    if (elementos.btnActualizar) {
      elementos.btnActualizar.innerHTML = '<i class="fas fa-key mr-1 md:mr-2"></i> Cambiar Contraseña';
    }
    
  } else {
    // Usuario normal: mostrar todos los campos normalmente
    if (elementos.nombre) {
      elementos.nombre.value = usuario.usuario || "";
      elementos.nombre.required = true; // Restaurar required para usuarios normales
    }
    if (elementos.correo) {
      elementos.correo.value = usuario.correo || "";
      elementos.correo.required = true; // Restaurar required para usuarios normales
    }
    if (elementos.rol) {
      elementos.rol.value = usuario.idrol || "";
      elementos.rol.required = true; // Restaurar required para usuarios normales
    }
    elementos.clave.value = "";
    
    // Mostrar TODOS los campos y contenedores
    const todosLosCampos = formActualizar.querySelectorAll('[style*="display: none"]');
    todosLosCampos.forEach(campo => {
      campo.style.display = '';
    });
    
    // Restaurar grids
    const gridsContenedores = formActualizar.querySelectorAll('.grid');
    gridsContenedores.forEach(grid => {
      if (grid) {
        grid.style.display = '';
      }
    });
    
    // También restaurar por clase
    const camposPorClase = formActualizar.querySelectorAll('.mb-4, [class*="grid"] > div');
    camposPorClase.forEach(campo => {
      if (campo && campo.style.display === 'none') {
        campo.style.display = '';
      }
    });
    
    // Restaurar atributos required según la configuración original
    const camposQueDerianSerRequired = formActualizar.querySelectorAll('#usuarioNombreActualizar, #usuarioCorreoActualizar, #usuarioRolActualizar');
    camposQueDerianSerRequired.forEach(campo => {
      if (campo) {
        campo.required = true;
      }
    });
    
    // Rehabilitar el campo rol para usuarios normales
    if (elementos.rol) {
      elementos.rol.disabled = false;
      elementos.rol.style.pointerEvents = '';
      elementos.rol.style.opacity = '';
    }
    
    // Contraseña opcional para usuarios normales
    elementos.clave.required = false;
    elementos.clave.placeholder = "";
    
    // Título normal
    if (elementos.titulo) {
      elementos.titulo.textContent = "Actualizar Usuario";
    }
    
    // Remover mensaje si existe
    const existingMessage = document.querySelector('.super-user-edit-message');
    if (existingMessage) {
      existingMessage.remove();
    }
    
    // Restaurar texto del botón
    if (elementos.btnActualizar) {
      elementos.btnActualizar.innerHTML = '<i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Usuario';
    }
    
    // Cargar personas solo para usuarios normales
    cargarPersonas(usuario.personaId || 0, "usuarioPersonaActualizar");
  }

  inicializarValidaciones(camposFormularioActualizarUsuario, "formActualizarUsuario");
  abrirModal("modalActualizarUsuario");
}

function actualizarUsuario() {
  
  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para editar usuarios.");
    return;
  }

  const formActualizar = document.getElementById("formActualizarUsuario");
  const btnActualizarUsuario = document.getElementById("btnActualizarUsuario");
  const idUsuarioElement = document.getElementById("idUsuarioActualizar");
  const rolUsuarioElement = document.getElementById("usuarioRolActualizar");

  if (!formActualizar || !btnActualizarUsuario || !idUsuarioElement) {
    console.error("Elementos críticos del formulario no encontrados");
    Swal.fire("Error", "Error en el formulario. Por favor, recarga la página.", "error");
    return;
  }

  const idUsuario = idUsuarioElement.value;
  
  // Obtener el rol del campo oculto o del elemento visible
  let rolUsuario = 0;
  if (rolUsuarioElement) {
    rolUsuario = parseInt(rolUsuarioElement.value) || 0;
  }
  
  // Si no se pudo obtener el rol, intentar desde los campos ocultos
  if (rolUsuario === 0) {
    const nombreElement = document.getElementById("usuarioNombreActualizar");
    const correoElement = document.getElementById("usuarioCorreoActualizar");
    // Si los campos están ocultos, asumir que es super usuario editándose
    if (nombreElement && nombreElement.style.display === 'none') {
      rolUsuario = 1;
    }
  }
  
  // Si aún no tenemos el rol pero sabemos que es super usuario editándose, forzar rol 1
  if (rolUsuario === 0 && esSuperUsuarioActual && parseInt(idUsuario) === idUsuarioActual) {
    rolUsuario = 1;
  }

  // Verificar si es super usuario editándose a sí mismo
  const esSuperUsuarioEditandose = (rolUsuario === 1 && parseInt(idUsuario) === idUsuarioActual && esSuperUsuarioActual);


  if (esSuperUsuarioEditandose) {
    // Para super usuario: solo validar contraseña
    const claveElement = document.getElementById("usuarioClaveActualizar");
    if (!claveElement) {
      console.error('Campo de contraseña no encontrado');
      Swal.fire("Error", "Campo de contraseña no encontrado.", "error");
      return;
    }
    
    const clave = claveElement.value;
    
    if (!clave || clave.trim() === "") {
      Swal.fire("Atención", "Debe proporcionar una nueva contraseña.", "warning");
      return;
    }
    
    if (clave.length < 6) {
      Swal.fire("Atención", "La contraseña debe tener al menos 6 caracteres.", "warning");
      return;
    }
    
  } else {
    // Para usuario normal: validaciones normales pero solo campos visibles
    const camposObligatoriosActualizar = camposFormularioActualizarUsuario.filter(c => {
      const elemento = document.getElementById(c.id);
      const container = elemento?.closest('.mb-4') || elemento?.parentNode;
      const estaVisible = elemento && container && container.style.display !== 'none' && !elemento.disabled;
      return c.mensajes && c.mensajes.vacio && estaVisible;
    });
    
    if (!validarCamposVaciosCondicional(camposObligatoriosActualizar, "formActualizarUsuario")) return;

    let formularioConErroresEspecificos = false;
    for (const campo of camposFormularioActualizarUsuario) { 
      const inputElement = formActualizar.querySelector(`#${campo.id}`);
      if (!inputElement) continue;
      
      // Verificar si el campo está visible y habilitado
      const container = inputElement.closest('.mb-4') || inputElement.parentNode;
      const estaVisible = container && container.style.display !== 'none' && !inputElement.disabled;
      
      if (!estaVisible) continue; // Saltar campos ocultos o deshabilitados
      
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
  }

  const formData = new FormData(formActualizar);
  let dataParaEnviar;

  if (esSuperUsuarioEditandose) {
    
    // Para super usuario: obtener valores directamente de los elementos ocultos
    const usuarioElement = document.getElementById("usuarioNombreActualizar");
    const correoElement = document.getElementById("usuarioCorreoActualizar");
    const rolElement = document.getElementById("usuarioRolActualizar");
    const personaElement = document.getElementById("usuarioPersonaActualizar");
    const claveElement = document.getElementById("usuarioClaveActualizar");
    
    dataParaEnviar = {
      idusuario: idUsuario,
      usuario: usuarioElement ? usuarioElement.value : "", // Valor directo del elemento
      correo: correoElement ? correoElement.value : "", // Valor directo del elemento
      idrol: rolElement ? rolElement.value : rolUsuario, // Si el elemento está vacío, usar rolUsuario calculado
      personaId: personaElement ? personaElement.value || null : null, // Valor directo del elemento
      clave: claveElement ? claveElement.value : "", // Solo la contraseña nueva
      solo_password: true // Indicador para el backend
    };
    
    // Si idrol aún está vacío, forzar valor 1 para super usuario
    if (!dataParaEnviar.idrol || dataParaEnviar.idrol === "") {
      dataParaEnviar.idrol = "1";
    }

  } else {
    // Para usuario normal: enviar todos los datos
    dataParaEnviar = {
      idusuario: idUsuario,
      usuario: formData.get("usuario") || "",
      correo: formData.get("correo") || "",
      idrol: formData.get("idrol") || "",
      personaId: formData.get("personaId") || null, 
      clave: formData.get("clave") || "", 
    };
  }

  btnActualizarUsuario.disabled = true;
  const textoOriginalBoton = btnActualizarUsuario.innerHTML;
  btnActualizarUsuario.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;

  fetch("Usuarios/updateUsuario", { 
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify(dataParaEnviar) 
  })
    .then(response => response.ok ? response.json() : response.json().then(err => { throw err; }))
    .then(result => {

      if (result.status) {
        const mensaje = esSuperUsuarioEditandose ? "Contraseña actualizada exitosamente." : result.message;

        Swal.fire("¡Éxito!", mensaje, "success");
        cerrarModal("modalActualizarUsuario");
        if (tablaUsuarios && tablaUsuarios.ajax) tablaUsuarios.ajax.reload(null, false);
      } else {
        console.error('Error del servidor:', result.message);
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
      console.error('=== ERROR EN PETICIÓN ===');
      console.error('Error:', error);
      
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", error.message || "Error de conexión.", "error");
      }
    })
    .finally(() => {
      btnActualizarUsuario.disabled = false;
      btnActualizarUsuario.innerHTML = textoOriginalBoton;
    });
}

// Función auxiliar para validar campos vacíos pero solo los visibles
function validarCamposVaciosCondicional(campos, formId) {
  let todosValidos = true;
  
  campos.forEach(campo => {
    const elemento = document.getElementById(campo.id);
    if (!elemento) return;
    
    // Verificar si el campo está visible
    const container = elemento.closest('.mb-4') || elemento.parentNode;
    const estaVisible = container && container.style.display !== 'none' && !elemento.disabled;
    
    if (!estaVisible) return; // Saltar campos ocultos
    
    if (campo.tipo === "select") {
      if (!elemento.value || elemento.value.trim() === "") {
        todosValidos = false;
        mostrarError(elemento, campo.mensajes.vacio);
      } else {
        limpiarError(elemento);
      }
    } else {
      if (!elemento.value || elemento.value.trim() === "") {
        todosValidos = false;
        mostrarError(elemento, campo.mensajes.vacio);
      } else {
        limpiarError(elemento);
      }
    }
  });
  
  return todosValidos;
}

// Funciones auxiliares para mostrar/limpiar errores
function mostrarError(elemento, mensaje) {
  const errorDiv = elemento.parentNode.querySelector('.error-message');
  if (errorDiv) {
    errorDiv.textContent = mensaje;
    errorDiv.style.display = 'block';
  }
  elemento.classList.add('border-red-500');
}

function limpiarError(elemento) {
  const errorDiv = elemento.parentNode.querySelector('.error-message');
  if (errorDiv) {
    errorDiv.textContent = '';
    errorDiv.style.display = 'none';
  }
  elemento.classList.remove('border-red-500');
}

function verUsuario(idUsuario) {
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
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else if (result.message && result.message.includes('no tienes permisos para verlo')) {
          mostrarModalPermisosDenegados("No tienes permisos para ver este usuario.");
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

function reactivarUsuario(idUsuario, nombreUsuario) {
  if (!esSuperUsuarioActual) {
    mostrarModalPermisosDenegados("Solo los super usuarios pueden reactivar usuarios.");
    return;
  }

  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para reactivar usuarios.");
    return;
  }

  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas reactivar al usuario ${nombreUsuario}? Esta acción cambiará su estatus a ACTIVO.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Sí, reactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Usuarios/reactivarUsuario", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify({ idusuario: idUsuario }),
      })
        .then(response => response.json())
        .then(result => {
          if (result.status) {
            Swal.fire("¡Reactivado!", result.message, "success");
            if (tablaUsuarios && tablaUsuarios.ajax) tablaUsuarios.ajax.reload(null, false);
          } else {
            if (result.message && result.message.includes('permisos')) {
              mostrarModalPermisosDenegados(result.message);
            } else {
              Swal.fire("Error", result.message || "No se pudo reactivar.", "error");
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

// Inicialización del módulo de usuarios
$(document).ready(function() {
  
  // Verificar el estado de super usuario antes de inicializar la tabla
  verificarSuperUsuario().then((result) => {
    if (result && result.status) {
      esSuperUsuarioActual = result.es_super_usuario;
      idUsuarioActual = result.usuario_id;
      
      // Inicializar la tabla después de verificar el estado de super usuario
      inicializarTablaUsuarios();
    } else {
      console.error('Error al verificar super usuario:', result ? result.message : 'Sin respuesta');
      // Aún así intentar inicializar la tabla en caso de error
      inicializarTablaUsuarios();
    }
  }).catch(error => {
    console.error('Error en verificación de super usuario:', error);
    // Aún así intentar inicializar la tabla en caso de error
    inicializarTablaUsuarios();
  });
  
  // Cargar datos iniciales
  cargarRoles();
  cargarPersonas();
  
  // Event handlers para modales de permisos
  const btnCerrarModalPermisos = document.getElementById('btnCerrarModalPermisos');
  const btnCerrarModalPermisos2 = document.getElementById('btnCerrarModalPermisos2');
  
  if (btnCerrarModalPermisos) {
    btnCerrarModalPermisos.addEventListener('click', cerrarModalPermisosDenegados);
  }
  
  if (btnCerrarModalPermisos2) {
    btnCerrarModalPermisos2.addEventListener('click', cerrarModalPermisosDenegados);
  }

  // Event handlers para botones principales
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

  // Event handlers para formularios
  const formRegistrar = document.getElementById("formRegistrarUsuario");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistro = document.getElementById("btnCancelarModalRegistrar");

  if (btnCerrarModalRegistro) btnCerrarModalRegistro.addEventListener("click", () => cerrarModal("modalRegistrarUsuario"));
  if (btnCancelarModalRegistro) btnCancelarModalRegistro.addEventListener("click", () => cerrarModal("modalRegistrarUsuario"));
  if (formRegistrar) formRegistrar.addEventListener("submit", (e) => { e.preventDefault(); registrarUsuario(); });

  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarUsuario");
  const btnActualizarUsuario = document.getElementById("btnActualizarUsuario");

  if (btnCerrarModalActualizar) btnCerrarModalActualizar.addEventListener("click", () => cerrarModal("modalActualizarUsuario"));
  if (btnCancelarModalActualizar) btnCancelarModalActualizar.addEventListener("click", () => cerrarModal("modalActualizarUsuario"));
  if (formActualizar) formActualizar.addEventListener("submit", (e) => { e.preventDefault(); actualizarUsuario(); });
  
  // Event handler directo para el botón de actualizar (por si no hace submit del formulario)
  if (btnActualizarUsuario) {
    btnActualizarUsuario.addEventListener("click", function(e) {
      e.preventDefault();
      actualizarUsuario();
    });
  }

  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) btnCerrarModalVer.addEventListener("click", () => cerrarModal("modalVerUsuario"));
  if (btnCerrarModalVer2) btnCerrarModalVer2.addEventListener("click", () => cerrarModal("modalVerUsuario"));
});