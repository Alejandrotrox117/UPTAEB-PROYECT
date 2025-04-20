document.addEventListener("DOMContentLoaded", function () {
  $("#TablaPersonas").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "personas/getPersonasData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idpersona", title: "Nro" },
      { data: "nombre", title: "Nombre" },
      { data: "apellido", title: "Apellido" },
      { data: "cedula", title: "Cédula" },
      { data: "rif", title: "Rif" },
      { data: "tipo", title: "Tipo" },
      { data: "genero", title: "Genero" },
      { data: "fecha_nacimiento", title: "Fecha de Nacimiento" },
      { data: "telefono_principal", title: "Teléfono" },
      { data: "correo_electronico", title: "Correo Electrónico" },
      { data: "direccion", title: "Dirección" },
      { data: "ciudad", title: "Ciudad" },
      { data: "estado", title: "Estado" },
      { data: "pais", title: "País" },
      { data: "estatus", title: "Status" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          // Generar botones con íconos de Font Awesome
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idpersona="${row.idpersona}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idpersona="${row.idpersona}">
                  <i class="fas fa-trash"></i>
                </button>
              `;
        },
      },
    ],
    language: {
      decimal: "",
      emptyTable: "No hay información",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
      infoEmpty: "Mostrando 0 to 0 of 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
      infoPostFix: "",
      thousands: ",",
      lengthMenu: "Mostrar _MENU_ Entradas",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "Sin resultados encontrados",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior",
      },
    },
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "asc"]],
  });

  const personaForm = document.getElementById("personaForm");
  personaForm.addEventListener("submit", function (e) {
    e.preventDefault();

    // Convertir los datos del formulario en un objeto JSON
    const formData = new FormData(personaForm);
    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

    console.log("Datos a enviar:", data); // Depuración: Verifica los datos antes de enviar

    // Determinar si es una edición o una creación
    const idpersona = document.getElementById("idpersona").value;
    const url = idpersona ? "personas/updatePersona" : "personas/setPersona";
    const method = idpersona ? "PUT" : "POST";

    fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json", // Indica que enviamos JSON
      },
      body: JSON.stringify(data), // Convierte los datos a JSON
    })
      .then((response) => response.json()) // Parsea la respuesta como JSON
      .then((result) => {
        if (result.status) {
          alert(result.message); // Muestra mensaje de éxito
          cerrarModalPersona(); // Cierra el modal
          $("#TablaPersonas").DataTable().ajax.reload(); // Recarga la tabla
        } else {
          alert(result.message); // Muestra mensaje de error
        }
      })
      .catch((error) => {
        console.error("Error:", error); // Maneja errores de red
        alert("Ocurrió un error al procesar la solicitud.");
      });
  });

  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idpersona = e.target
        .closest(".editar-btn")
        .getAttribute("data-idpersona");
      console.log("Botón de edición clicado. ID de persona:", idpersona); // Depuración

      if (!idpersona || isNaN(idpersona)) {
        alert("ID de persona no válido.");
        return;
      }

      abrirModalPersonaParaEdicion(idpersona);
    }
  });
});

function eliminarPersona(idpersona) {
  fetch(`personas/deletePersona`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idpersona }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); // Muestra mensaje de éxito
        $("#TablaPersonas").DataTable().ajax.reload(); // Recarga la tabla
      } else {
        alert(result.message); // Muestra mensaje de error
      }
    })
    .catch((error) => console.error("Error:", error));
}

document.addEventListener("click", function (e) {
  if (e.target.closest(".eliminar-btn")) {
    const idpersona = e.target
      .closest(".eliminar-btn")
      .getAttribute("data-idpersona");
    if (confirm("¿Estás seguro de desactivar esta persona?")) {
      eliminarPersona(idpersona);
    }
  }
});

function abrirModalPersonaParaEdicion(idpersona) {
  console.log("ID de persona recibido:", idpersona); // Depuración

  fetch(`personas/getPersonaById/${idpersona}`)
    .then((response) => {
      console.log("Respuesta HTTP:", response); // Depuración
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos del backend:", data); // Depuración

      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const persona = data.data;

      // Asigna los valores a los campos del modal formulario
      document.getElementById("idpersona").value = persona.idpersona || "";
      document.getElementById("nombre").value = persona.nombre || "";
      document.getElementById("apellido").value = persona.apellido || "";
      document.getElementById("cedula").value = persona.cedula || "";
      document.getElementById("rif").value = persona.rif || "";
      document.getElementById("tipo").value = persona.tipo || "";
      document.getElementById("genero").value = persona.genero || "";
      document.getElementById("fecha_nacimiento").value =
        persona.fecha_nacimiento || "";
      document.getElementById("telefono_principal").value =
        persona.telefono_principal || "";
      document.getElementById("correo_electronico").value =
        persona.correo_electronico || "";
      document.getElementById("direccion").value = persona.direccion || "";
      document.getElementById("ciudad").value = persona.ciudad || "";
      document.getElementById("estado").value = persona.estado || "";
      document.getElementById("pais").value = persona.pais || "";
      document.getElementById("estatus").value = persona.estatus || "";

      // Abre el modal
      abrirModalPersona();
    })
    .catch((error) => {
      console.error("Error capturado:", error.message); // Depuración
      alert(
        "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente."
      );
    });
}

document.addEventListener("click", function (e) {
  if (e.target.closest(".editar-btn")) {
    const idpersona = e.target
      .closest(".editar-btn")
      .getAttribute("data-idpersona");

    if (!idpersona || isNaN(idpersona)) {
      alert("ID de persona no válido.");
      return;
    }

    abrirModalPersonaParaEdicion(idpersona);
  }
});
function abrirModalPersona() {
  const modal = document.getElementById("personaModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}

function cerrarModalPersona() {
  const modal = document.getElementById("personaModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("personaForm").reset();
}
