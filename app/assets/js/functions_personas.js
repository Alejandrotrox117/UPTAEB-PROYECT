import { abrirModal, cerrarModal, } from "./exporthelpers.js"; // Ajusta la ruta
import { expresiones, inicializarValidaciones, validarCamposVacios, validarCampo, validarSelect, limpiarValidaciones,} from "./validaciones.js";

let tablaPersonas;
// Definición de campos para validación (ejemplo, ajústalo a tus necesidades)
const camposFormularioPersona = [
  { id: "nombrePersona", tipo: "input", regex: expresiones.nombre, mensajes: { vacio: "El nombre es obligatorio.", formato: "Nombre inválido." } },
  { id: "apellidoPersona", tipo: "input", regex: expresiones.nombre, mensajes: { vacio: "El apellido es obligatorio.", formato: "Apellido inválido." } },
  { id: "cedulaPersona", tipo: "input", regex: expresiones.cedula, mensajes: { vacio: "La cédula es obligatoria.", formato: "Cédula inválida." } },
  { id: "rifPersona", tipo: "input", regex: expresiones.rif, mensajes: { vacio: "El RIF es obligatorio.", formato: "RIF inválido (Ej: V-12345678-9)." } },
  { id: "telefonoPersona", tipo: "input", regex: expresiones.telefono_principal, mensajes: { vacio: "El teléfono es obligatorio.", formato: "Teléfono inválido." } },
  { id: "tipoPersona", tipo: "select", mensajes: { vacio: "Seleccione un tipo de persona." } },
  { id: "generoPersona", tipo: "select", mensajes: { vacio: "Seleccione un género." } },
  { id: "fechaNacimientoPersona", tipo: "date", mensajes: { vacio: "La fecha de nacimiento es obligatoria." } }, 
  { id: "estadoPersona", tipo: "input", regex: expresiones.textoGeneral, mensajes: { vacio: "El estado es obligatorio." } },
  { id: "ciudadPersona", tipo: "input", regex: expresiones.textoGeneral, mensajes: { vacio: "La ciudad es obligatoria." } },
  { id: "paisPersona", tipo: "input", regex: expresiones.textoGeneral, mensajes: { vacio: "El país es obligatorio." } },
  { id: "correoPersona", tipo: "input", regex: expresiones.email, mensajes: { vacio: "El correo es obligatorio para el usuario.", formato: "Correo inválido." } },
  { id: "clavePersona", tipo: "input", regex: expresiones.password, mensajes: { vacio: "La clave es obligatoria para el usuario.", formato: "Clave inválida (6-16 caracteres)." } }, // Define expresiones.password
  { id: "rol", tipo: "select", mensajes: { vacio: "Seleccione un rol para el usuario." } },
];
// Campos para el formulario de actualización (similar, pero con IDs diferentes)
const camposFormularioActualizarPersona = [
    { id: "nombreActualizar", tipo: "input", regex: expresiones.nombre, mensajes: { vacio: "El nombre es obligatorio.", formato: "Nombre inválido." } },
    { id: "apellidoActualizar", tipo: "input", regex: expresiones.nombre, mensajes: { vacio: "El apellido es obligatorio.", formato: "Apellido inválido." } },
    { id: "cedulaActualizar", tipo: "input", regex: expresiones.cedula, mensajes: { vacio: "La cédula es obligatoria.", formato: "Cédula inválida." } },
    { id: "rifActualizar", tipo: "input", regex: expresiones.rif, mensajes: { vacio: "El RIF es obligatorio.", formato: "RIF inválido." } },
    { id: "telefonoActualizar", tipo: "input", regex: expresiones.telefono_principal, mensajes: { vacio: "El teléfono es obligatorio.", formato: "Teléfono inválido." } },
    { id: "tipoActualizar", tipo: "select", mensajes: { vacio: "Seleccione un tipo de persona." } },
    { id: "generoActualizar", tipo: "select", mensajes: { vacio: "Seleccione un género." } },
    { id: "fechaNacimientoActualizar", tipo: "date", mensajes: { vacio: "La fecha de nacimiento es obligatoria." } },
    { id: "estadoActualizar", tipo: "input", regex: expresiones.textoGeneral, mensajes: { vacio: "El estado es obligatorio." } },
    { id: "ciudadActualizar", tipo: "input", regex: expresiones.textoGeneral, mensajes: { vacio: "La ciudad es obligatoria." } },
    { id: "paisActualizar", tipo: "input", regex: expresiones.textoGeneral, mensajes: { vacio: "El país es obligatorio." } },
    { id: "correoActualizar", tipo: "input", regex: expresiones.email, mensajes: { /* opcional si no se crea usuario */ formato: "Correo inválido." } },
    { id: "claveActualizar", tipo: "input", regex: expresiones.password, mensajes: { /* opcional */ formato: "Clave inválida (dejar en blanco para no cambiar)." } },
    { id: "rolActualizar", tipo: "select", mensajes: { /* opcional */ } },
];


document.addEventListener("DOMContentLoaded", function () {

  $(document).ready(function () {
    //DATATABLE PERSONAS
    let tablaPersonas = $("#TablaPersonas").DataTable({
      processing: true, 
      ajax: {
        url: "Personas/getPersonasData", 
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data; 
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json,
            );
            $("#TablaPersonas_processing").hide();
            alert(
              "Error: No se pudieron cargar los datos de personas correctamente. La respuesta del servidor no es válida.",
            );
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para TablaPersonas: ",
            textStatus,
            errorThrown,
            jqXHR.responseText,
          );
          $("#TablaPersonas_processing").hide();
          alert(
            "Error de comunicación al cargar los datos de personas. Por favor, intente más tarde o contacte al administrador. Detalles en la consola.",
          );
        },
      },
      columns: [
        { data: "persona_nombre", title: "Nombre" },
        { data: "persona_apellido", title: "Apellido" },
        { data: "persona_cedula", title: "Cédula" },
        {
          data: "persona_genero",
          title: "Género",
          render: function (data, type, row) {
            if (data) {
              return data.charAt(0).toUpperCase() + data.slice(1);
            }
            return '<i style="color: silver;">N/A</i>';
          },
        },
        { data: "telefono_principal", title: "Teléfono" },
        {
          data: "persona_estatus",
          title: "Estatus",
          render: function (data, type, row) {
            if (data) {
              const estatusUpper = String(data).toUpperCase();
              if (estatusUpper === "ACTIVO") {
                return `<span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">${data}</span>`;
              } else {
                return `<span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">${data}</span>`;
              }
            }
            return '<i style="color: silver;">N/A</i>';
          },
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            const nombreCompleto = `${row.persona_nombre || ""} ${
              row.persona_apellido || ""
            }`.trim();
            return `
              <button class="editar-persona-btn text-blue-500 hover:text-blue-700 p-1" data-idpersona-pk="${row.idpersona_pk}" title="Editar">
                  <i class="fas fa-edit fa-lg"></i>
              </button>
              <button class="eliminar-persona-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idpersona-pk="${row.idpersona_pk}" data-nombre="${nombreCompleto}" title="Eliminar">
                  <i class="fas fa-trash fa-lg"></i>
              </button>
            `;
          },
          width: "80px",
          className: "text-center",
        },
      ],
      language: {
        decimal: "",
        emptyTable: "No hay información disponible en la tabla",
        info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 entradas",
        infoFiltered: "(filtrado de _MAX_ entradas totales)",
        lengthMenu: "Mostrar _MENU_ entradas",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior",
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendentemente",
          sortDescending: ": activar para ordenar la columna descendentemente",
        },
      },
      destroy: true,
      responsive: true,
      pageLength: 10,
      order: [[1, "asc"]], // Ordenar por Nombre (segunda columna, índice 1)
    });

  //Click abrir para editar persona
  $("#TablaPersonas tbody").on("click", ".editar-persona-btn", function () {
    var idPersona = $(this).data("idpersona-pk");
    alert("Editar persona ID: " + idPersona);
  });

  //Click eliminar persona
  $("#TablaPersonas tbody").on("click",".eliminar-persona-btn",function () {
      var idPersona = $(this).data("idpersona-pk");
      var nombrePersona = $(this).data("nombre");
      if (
        confirm(
          "¿Estás seguro de que deseas eliminar a " + nombrePersona + "?",
        )
      ) {
        alert("Eliminar persona ID: " + idPersona);
      }
    },
  );
});

  const btnAbrirModalRegistro = document.getElementById("btnAbrirModalRegistrarPersona",);
  const formRegistrar = document.getElementById("formRegistrarPersona");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalRegistrar",);
  const checkboxCrearUsuario = document.getElementById('crearUsuario');
  const camposUsuarioContainer = document.getElementById('usuarioCamposRegistrar');
  const btnCancelarModalRegistro = document.getElementById("btnCancelarModalRegistrar",);
  const btnGuardarPersona = document.getElementById("btnGuardarPersona"); 
  const tituloModalRegistrar = document.getElementById("tituloModalRegistrar");

  btnAbrirModalRegistro.addEventListener("click", function () {
    abrirModal("modalRegistrarPersona");
    checkboxCrearUsuario.checked = false; // Reiniciar el checkbox al abrir el modal
    camposUsuarioContainer.classList.add('hidden');
    formRegistrar.reset();
  });
  btnCerrarModalRegistro.addEventListener("click", function () {
    cerrarModal("modalRegistrarPersona");
  }); 
  btnCancelarModalRegistro.addEventListener("click", function () {
    cerrarModal("modalRegistrarPersona");
  }); 
  if (checkboxCrearUsuario && camposUsuarioContainer) {
    function actualizarVisibilidadCamposUsuario() {
      if (checkboxCrearUsuario.checked) {
        camposUsuarioContainer.classList.remove('hidden');
      } else {
        camposUsuarioContainer.classList.add('hidden');
      }
    }
    actualizarVisibilidadCamposUsuario();

    checkboxCrearUsuario.addEventListener('change', actualizarVisibilidadCamposUsuario);

  } else {
    if (!checkboxCrearUsuario) {
      console.warn("Checkbox con ID 'crearUsuario' no encontrado.");
    }
    if (!camposUsuarioContainer) {
      console.warn("Contenedor con ID 'usuarioCamposRegistrar' no encontrado.");
    }
  }


});


