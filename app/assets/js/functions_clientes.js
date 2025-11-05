import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaClientes;

const camposFormularioCliente = [
  {
    id: "cedula",
    tipo: "input",
    regex: expresiones.cedula,
    mensajes: {
      vacio: "La cédula es obligatoria.",
      formato: "La Cédula debe contener la estructura V-/J-/E- No debe contener espacios y solo números.",
    },
  },
  {
    id: "nombre",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre debe tener entre 3 y 50 caracteres alfabéticos.",
    },
  },
  {
    id: "apellido",
    tipo: "input",
    regex: expresiones.apellido,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido debe tener entre 3 y 50 caracteres alfabéticos.",
    },
  },
  {
    id: "telefono_principal",
    tipo: "input",
    regex: expresiones.telefono_principal,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras. Debe comenzar con 0412, 0414, 0424 o 0416.",
    },
  },
  {
    id: "direccion",
    tipo: "input",
    regex: expresiones.direccion,
    mensajes: {
      formato: "La dirección debe tener entre 5 y 100 caracteres.",
    },
  },
  {
    id: "observaciones",
    tipo: "input",
    regex: expresiones.observaciones,
    mensajes: {
      formato: "Las observaciones no deben exceder los 200 caracteres.",
    },
  },
];

const camposFormularioActualizarCliente = [
  {
    id: "cedulaActualizar",
    tipo: "input",
    regex: expresiones.cedula,
    mensajes: {
      vacio: "La cédula es obligatoria.",
      formato: "La Cédula debe contener la estructura V-XXXXX No debe contener espacios y solo números.",
    },
  },
  {
    id: "nombreActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre debe tener entre 2 y 50 caracteres alfabéticos.",
    },
  },
  {
    id: "apellidoActualizar",
    tipo: "input",
    regex: expresiones.apellido,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido debe tener entre 2 y 50 caracteres alfabéticos.",
    },
  },
  {
    id: "telefono_principalActualizar",
    tipo: "input",
    regex: expresiones.telefono_principal,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras.",
    },
  },
  {
    id: "direccionActualizar",
    tipo: "input",
    regex: expresiones.direccion,
    mensajes: {
      formato: "La dirección debe tener entre 5 y 100 caracteres.",
    },
  },
  {
    id: "estatusActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un estatus." },
  },
  {
    id: "observacionesActualizar",
    tipo: "input",
    regex: expresiones.observaciones,
    mensajes: {
      formato: "Las observaciones no deben exceder los 200 caracteres.",
    },
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
  return window.permisosClientes && window.permisosClientes[accion] === true;
}

document.addEventListener("DOMContentLoaded", function () {
  
  const btnCerrarModalPermisos = document.getElementById('btnCerrarModalPermisos');
  
  if (btnCerrarModalPermisos) {
    btnCerrarModalPermisos.addEventListener('click', cerrarModalPermisosDenegados);
  }

  $(document).ready(function () {
    
    if (!tienePermiso('ver')) {
      console.warn('Sin permisos para ver clientes');
      return;
    }

    
    if ($.fn.DataTable.isDataTable("#Tablaclientes")) {
      $("#Tablaclientes").DataTable().destroy();
    }

    tablaClientes = $("#Tablaclientes").DataTable({
      processing: true,
      ajax: {
        url: "clientes/getClientesData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json
            );
            $("#Tablaclientes_processing").css("display", "none"); 
            
            
            if (json && json.message && json.message.includes('permisos')) {
              mostrarModalPermisosDenegados(json.message);
            } else {
              alert("Error: No se pudieron cargar los datos de clientes correctamente.");
            }
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para Tablaclientes: ",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#Tablaclientes_processing").css("display", "none"); 
          
          
          try {
            const response = JSON.parse(jqXHR.responseText);
            if (response && response.message && response.message.includes('permisos')) {
              mostrarModalPermisosDenegados(response.message);
              return;
            }
          } catch (e) {
            
          }
          
          alert("Error de comunicación al cargar los datos de clientes. Por favor, intente más tarde.");
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
          data: "cedula",
          title: "Cédula",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700 break-all",
        },
        {
          data: "nombre",
          title: "Nombre",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "apellido",
          title: "Apellido",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "telefono_principal",
          title: "Teléfono",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "direccion",
          title: "Dirección",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data && data.length > 30) {
              return data.substring(0, 30) + '...';
            }
            return data || '';
          },
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
          data: "observaciones",
          title: "Observaciones",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data && data.length > 30) {
              return data.substring(0, 30) + '...';
            }
            return data || '';
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
            const nombreClienteParaEliminar = `${row.nombre} ${row.apellido}` || row.cedula;
            let acciones = '<div class="inline-flex items-center space-x-1">';
            
            
            if (tienePermiso('ver')) {
              acciones += `
                <button class="ver-cliente-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idcliente="${row.idcliente}" 
                        title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>`;
            }
            
            
            if (tienePermiso('editar')) {
              acciones += `
                <button class="editar-cliente-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                        data-idcliente="${row.idcliente}" 
                        title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>`;
            }
            
            
            if (tienePermiso('eliminar')) {
              acciones += `
                <button class="eliminar-cliente-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                        data-idcliente="${row.idcliente}" 
                        data-nombre="${nombreClienteParaEliminar}" 
                        title="Desactivar">
                    <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>`;
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
        emptyTable:
          '<div class="text-center py-4"><i class="fas fa-users-slash fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay clientes disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ clientes",
        infoEmpty: "Mostrando 0 clientes",
        infoFiltered: "(filtrado de _MAX_ clientes totales)",
        lengthMenu: "Mostrar _MENU_ clientes",
        search: "_INPUT_",
        searchPlaceholder: "Buscar cliente...",
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
        window.tablaClientes = this.api();
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

    
    $("#Tablaclientes tbody").on("click", ".ver-cliente-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('ver')) {
        mostrarModalPermisosDenegados("No tienes permisos para ver detalles de clientes.");
        return;
      }
      
      const idCliente = $(this).data("idcliente");
      if (idCliente) {
        verCliente(idCliente);
      } else {
        console.error("ID de cliente no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del cliente.", "error");
      }
    });

    $("#Tablaclientes tbody").on("click", ".editar-cliente-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('editar')) {
        mostrarModalPermisosDenegados("No tienes permisos para editar clientes.");
        return;
      }
      
      const idCliente = $(this).data("idcliente");
      if (idCliente) {
        editarCliente(idCliente);
      } else {
        console.error("ID de cliente no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del cliente.", "error");
      }
    });

    $("#Tablaclientes tbody").on("click", ".eliminar-cliente-btn", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('eliminar')) {
        mostrarModalPermisosDenegados("No tienes permisos para eliminar clientes.");
        return;
      }
      
      const idCliente = $(this).data("idcliente");
      const nombreCliente = $(this).data("nombre"); 
      if (idCliente) {
        eliminarCliente(idCliente, nombreCliente);
      } else {
        console.error("ID de cliente no encontrado.");
        Swal.fire("Error", "No se pudo obtener el ID del cliente.", "error");
      }
    });
  });

  
  const btnAbrirModalRegistro = document.getElementById("abrirModalBtn");
  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('crear')) {
        mostrarModalPermisosDenegados("No tienes permisos para crear clientes.");
        return;
      }
      
      const formRegistrar = document.getElementById("clienteForm");
      abrirModal("clienteModal");
      if (formRegistrar) formRegistrar.reset();
      limpiarValidaciones(camposFormularioCliente, "clienteForm");
      inicializarValidaciones(camposFormularioCliente, "clienteForm");
    });
  }

 
  const btnExportarClientes = document.getElementById("btnExportarClientes");
  if (btnExportarClientes) {
    btnExportarClientes.addEventListener("click", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('exportar')) {
        mostrarModalPermisosDenegados("No tienes permisos para exportar clientes.");
        return;
      }
      
      exportarClientes();
    });
  }

  
  const formRegistrar = document.getElementById("clienteForm");
  const btnCerrarModalRegistro = document.getElementById("cerrarModalBtn");
  const btnCerrarModalRegistroX = document.getElementById("cerrarModalBtnX");

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", () => cerrarModal("clienteModal"));
  }
  
  if (btnCerrarModalRegistroX) {
    btnCerrarModalRegistroX.addEventListener("click", () => cerrarModal("clienteModal"));
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", (e) => { 
      e.preventDefault(); 
      registrarCliente(); 
    });
  }

  
  const btnRegistrarCliente = document.getElementById("registrarClienteBtn");
  if (btnRegistrarCliente) {
    btnRegistrarCliente.addEventListener("click", (e) => {
      e.preventDefault();
      registrarCliente();
    });
  }

  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarCliente");

  if (btnCerrarModalActualizar) btnCerrarModalActualizar.addEventListener("click", () => cerrarModal("modalActualizarCliente"));
  if (btnCancelarModalActualizar) btnCancelarModalActualizar.addEventListener("click", () => cerrarModal("modalActualizarCliente"));
  if (formActualizar) formActualizar.addEventListener("submit", (e) => { e.preventDefault(); actualizarCliente(); });

  const btnCerrarModalVer = document.getElementById("btnCerrarModalVerCliente");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVerCliente2");
  
  if (btnCerrarModalVer) btnCerrarModalVer.addEventListener("click", () => cerrarModal("modalVerCliente"));
  if (btnCerrarModalVer2) btnCerrarModalVer2.addEventListener("click", () => cerrarModal("modalVerCliente"));
});

function registrarCliente() {
 
  if (!tienePermiso('crear')) {
    mostrarModalPermisosDenegados("No tienes permisos para crear clientes.");
    return;
  }

  const formRegistrar = document.getElementById("clienteForm");
  const btnGuardarCliente = document.getElementById("registrarClienteBtn");

  const camposObligatoriosRegistrar = camposFormularioCliente.filter(c => c.mensajes && c.mensajes.vacio);
  if (!validarCamposVacios(camposObligatoriosRegistrar, "clienteForm")) return;

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioCliente) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;
    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) { 
        esValidoEsteCampo = validarSelect(campo.id, campo.mensajes, "clienteForm");
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
    cedula: formData.get("cedula") || "",
    nombre: formData.get("nombre") || "",
    apellido: formData.get("apellido") || "",
    telefono_principal: formData.get("telefono_principal") || "",
    direccion: formData.get("direccion") || "",
    observaciones: formData.get("observaciones") || "",
  };

  btnGuardarCliente.disabled = true;
  btnGuardarCliente.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;

  fetch("clientes/createCliente", { 
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify(dataParaEnviar) 
  })
    .then(response => response.ok ? response.json() : response.json().then(err => { throw err; }))
    .then(result => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("clienteModal");
        if (tablaClientes && tablaClientes.ajax) tablaClientes.ajax.reload(null, false);
      } else {
        
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: result.message || "No se pudo registrar el cliente.",
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
      btnGuardarCliente.disabled = false;
      btnGuardarCliente.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Cliente`;
    });
}

function editarCliente(idCliente) {
  
  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para editar clientes.");
    return;
  }

  fetch(`clientes/getClienteById/${idCliente}`, { 
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
   })
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        mostrarModalEditarCliente(result.data);
      } else {
        
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

function mostrarModalEditarCliente(cliente) {

  const formActualizar = document.getElementById("formActualizarCliente");
  if (formActualizar) formActualizar.reset();
  limpiarValidaciones(camposFormularioActualizarCliente, "formActualizarCliente");

  
  const elementos = {
    idclienteActualizar: document.getElementById("idclienteActualizar"),
    cedulaActualizar: document.getElementById("cedulaActualizar"),
    nombreActualizar: document.getElementById("nombreActualizar"),
    apellidoActualizar: document.getElementById("apellidoActualizar"),
    telefono_principalActualizar: document.getElementById("telefono_principalActualizar"),
    direccionActualizar: document.getElementById("direccionActualizar"),
    observacionesActualizar: document.getElementById("observacionesActualizar"),
    estatusActualizar: document.getElementById("estatusActualizar")
  };

  
  const elementosFaltantes = [];
  for (const [nombre, elemento] of Object.entries(elementos)) {
    if (!elemento) {
      elementosFaltantes.push(nombre);
    }
  }

  if (elementosFaltantes.length > 0) {
    console.error("❌ Elementos faltantes en el DOM:", elementosFaltantes);
    Swal.fire("Error", "Error en el formulario: elementos faltantes", "error");
    return;
  }

  
  try {
    elementos.idclienteActualizar.value = cliente.idcliente || "";
    elementos.cedulaActualizar.value = cliente.cedula || "";
    elementos.nombreActualizar.value = cliente.nombre || "";
    elementos.apellidoActualizar.value = cliente.apellido || "";
    elementos.telefono_principalActualizar.value = cliente.telefono_principal || "";
    elementos.direccionActualizar.value = cliente.direccion || "";
    elementos.observacionesActualizar.value = cliente.observaciones || "";
    elementos.estatusActualizar.value = cliente.estatus || "activo";
    
    console.log(" Valores asignados correctamente");
    
    inicializarValidaciones(camposFormularioActualizarCliente, "formActualizarCliente");
    abrirModal("modalActualizarCliente");
    
  } catch (error) {
  
    Swal.fire("Error", "Error al cargar datos en el formulario", "error");
  }
}

function actualizarCliente() {
  
  if (!tienePermiso('editar')) {
    mostrarModalPermisosDenegados("No tienes permisos para editar clientes.");
    return;
  }

  const formActualizar = document.getElementById("formActualizarCliente");
  const btnActualizarCliente = document.getElementById("btnActualizarCliente");
  const idCliente = document.getElementById("idclienteActualizar").value;

  const camposObligatoriosActualizar = camposFormularioActualizarCliente.filter(c => c.mensajes && c.mensajes.vacio);
  if (!validarCamposVacios(camposObligatoriosActualizar, "formActualizarCliente")) return;

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarCliente) { 
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;
    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(campo.id, campo.mensajes, "formActualizarCliente");
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

  const formData = new FormData(formActualizar);
  const dataParaEnviar = {
    idcliente: idCliente,
    cedula: formData.get("cedula") || "",
    nombre: formData.get("nombre") || "",
    apellido: formData.get("apellido") || "",
    telefono_principal: formData.get("telefono_principal") || "",
    direccion: formData.get("direccion") || "",
    estatus: formData.get("estatus") || "",
    observaciones: formData.get("observaciones") || "",
  };

  btnActualizarCliente.disabled = true;
  btnActualizarCliente.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;

  fetch("clientes/updateCliente", { 
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify(dataParaEnviar) 
  })
    .then(response => response.ok ? response.json() : response.json().then(err => { throw err; }))
    .then(result => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalActualizarCliente");
        if (tablaClientes && tablaClientes.ajax) tablaClientes.ajax.reload(null, false);
      } else {
        
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: result.message || "No se pudo actualizar el cliente.",
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
      btnActualizarCliente.disabled = false;
      btnActualizarCliente.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Cliente`;
    });
}

function verCliente(idCliente) {
  
  if (!tienePermiso('ver')) {
    mostrarModalPermisosDenegados("No tienes permisos para ver detalles de clientes.");
    return;
  }

  fetch(`clientes/getClienteById/${idCliente}`, { 
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
   })
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        const cliente = result.data;
        document.getElementById("verClienteCedula").textContent = cliente.cedula || "N/A";
        document.getElementById("verClienteNombre").textContent = cliente.nombre || "N/A";
        document.getElementById("verClienteApellido").textContent = cliente.apellido || "N/A";
        document.getElementById("verClienteTelefono").textContent = cliente.telefono_principal || "N/A";
        document.getElementById("verClienteDireccion").textContent = cliente.direccion || "N/A";
        document.getElementById("verClienteEstatus").textContent = cliente.estatus || "N/A";
        document.getElementById("verClienteObservaciones").textContent = cliente.observaciones || "Sin observaciones";
        
        abrirModal("modalVerCliente");
      } else {
        
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

function eliminarCliente(idCliente, nombreCliente) {
  
  if (!tienePermiso('eliminar')) {
    mostrarModalPermisosDenegados("No tienes permisos para eliminar clientes.");
    return;
  }

  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar al cliente ${nombreCliente}? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("clientes/deleteCliente", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify({ idcliente: idCliente }),
      })
        .then(response => response.json())
        .then(result => {
          if (result.status) {
            Swal.fire("¡Desactivado!", result.message, "success");
            if (tablaClientes && tablaClientes.ajax) tablaClientes.ajax.reload(null, false);
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


function exportarClientes() {

  if (!tienePermiso('exportar')) {
    mostrarModalPermisosDenegados("No tienes permisos para exportar clientes.");
    return;
  }

  
  Swal.fire({
    title: 'Exportando...',
    text: 'Preparando datos de clientes',
    icon: 'info',
    allowOutsideClick: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    }
  });

  fetch("clientes/exportarClientes", {
    method: "GET",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then(response => {
      console.log("📥 Respuesta del servidor:", response); 
      return response.json();
    })
    .then(result => {
      console.log("📋 Resultado procesado:", result); 
      Swal.close(); 
      
      if (result.status && result.data) {
     
        console.log("📊 Datos para exportar:", result.data);
        
        
        const csvContent = generarCSV(result.data);
        descargarCSV(csvContent, 'clientes_export.csv');
        
        Swal.fire("¡Éxito!", "Clientes exportados correctamente.", "success");
      } else {
        if (result.message && result.message.includes('permisos')) {
          mostrarModalPermisosDenegados(result.message);
        } else {
          Swal.fire("Error", result.message || "Error al exportar clientes.", "error");
        }
      }
    })
    .catch(error => {
      console.error("❌ Error en exportación:", error); 
      Swal.close(); 
      
      if (error.message && error.message.includes('permisos')) {
        mostrarModalPermisosDenegados(error.message);
      } else {
        Swal.fire("Error", "Error de conexión al exportar.", "error");
      }
    });
}


function generarCSV(datos) {
  const headers = ['ID', 'Cédula', 'Nombre', 'Apellido', 'Teléfono', 'Dirección', 'Estatus', 'Observaciones'];
  const csvContent = [
    headers.join(','),
    ...datos.map(cliente => [
      cliente.idcliente || '',
      `"${cliente.cedula || ''}"`,
      `"${cliente.nombre || ''}"`,
      `"${cliente.apellido || ''}"`,
      cliente.telefono_principal || '',
      `"${cliente.direccion || ''}"`,
      cliente.estatus || '',
      `"${cliente.observaciones || ''}"`,
    ].join(','))
  ].join('\n');
  
  return csvContent;
}


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
