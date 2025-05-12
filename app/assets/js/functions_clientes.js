document.addEventListener("DOMContentLoaded", function () {
  $("#Tablaclientes").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "clientes/getclientesData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idcliente", title: "Nro" },
      { data: "cedula", title: "Cédula" },
      { data: "nombre", title: "Nombre" },
      { data: "apellido", title: "Apellido" },
      { data: "telefono_principal", title: "Teléfono" },
      { data: "direccion", title: "Dirección" },
     
      { data: "estatus", title: "Estatus" },
      { data: "observaciones", title: "Observaciones" },

      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          // Generar botones con íconos de Font Awesome
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idcliente="${row.idcliente}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idcliente="${row.idcliente}">
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
});

document.addEventListener("DOMContentLoaded", function () {
  let formCliente = document.querySelector("#clienteForm");

  if (formCliente) {
    formCliente.onsubmit = function (e) {
      e.preventDefault(); // Evita que el formulario se envíe de forma tradicional

       try {
        // Obtener los valores de los campos para validar
        let campos = [
          { id: "cedula", nombre: "Cédula" },
          { id: "nombre", nombre: "Nombre" },
          { id: "apellido", nombre: "Apellido" },
          { id: "telefono_principal", nombre: "Teléfono Principal" },
          { id: "direccion", nombre: "Dirección" },
          { id: "estatus", nombre: "Estatus" },
          { id: "observaciones", nombre: "Observaciones" },
        ];

        // Validar campos vacíos
        for (let campo of campos) {
          let valor = document.getElementById(campo.id).value.trim();
          if (valor === "") {
            Swal.fire({
              title: "¡Error!",
              text: `El campo "${campo.nombre}" no puede estar vacío.`,
              icon: "error",
              confirmButtonText: "Aceptar",
            });
            return;
          }
        }

        let datosFormulario = {};
        for (let campo of campos) {
          datosFormulario[campo.id] = document
            .getElementById(campo.id)
            .value.trim();
        }
        datosFormulario["cedula"] = document
          .getElementById("cedula")
          .value.trim();
        datosFormulario["nombre"] = document
          .getElementById("nombre")
          .value.trim();
        datosFormulario["apellido"] = document
          .getElementById("apellido")
          .value.trim();
        datosFormulario["telefono_principal"] = document
          .getElementById("telefono_principal")
          .value.trim();
        datosFormulario["direccion"] = document
          .getElementById("direccion")
          .value.trim();
        datosFormulario["observaciones"] = document
          .getElementById("observaciones")
          .value.trim();
        datosFormulario["estatus"] = document
          .getElementById("estatus")
          .value.trim();


        console.log("Datos del formulario:", datosFormulario); // Depuración
         console.log("Valor de cédula:", datosFormulario["cedula"]);
    
   

  
  
        fetch("clientes/createcliente",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify(datosFormulario),
          })
            .then((response) => response.json())
            .then((result) => {
              if (result.status) {
                Swal.fire({
                  title: "¡Éxito!",
                  text: result.message,
                  icon: "success",
                  confirmButtonText: "Aceptar",
                }).then(() => {
                  $("#Tablaclientes").DataTable().ajax.reload();
                  cerrarModalcliente();
                });
              } else {
                Swal.fire({
                  title: "¡Error!",
                  text: result.message,
                  icon: "error",
                  confirmButtonText: "Aceptar",
                });
              }
            })
            .catch((error) => {
              console.error("Error:", error);

              Swal.fire({
                title: "¡Error!",
                text: "Ocurrió un error al guardar los datos.",
                icon: "error",
                confirmButtonText: "Aceptar",
              });
            });
      } catch (error) {
        console.error("Error al procesar el formulario:", error);
        Swal.fire({
          title: "¡Error!",
          text: "Ocurrió un error al procesar el formulario.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        
      }
    };
  } else {
    console.error(
      "El formulario con ID 'clienteForm' no se encontró en el DOM."
    );
  }
 });

function eliminarcliente(idcliente) {
  fetch(`clientes/deletecliente`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idcliente }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); // Muestra mensaje de éxito
        $("#Tablaclientes").DataTable().ajax.reload(); // Recarga la tabla
      } else {
        alert(result.message); // Muestra mensaje de error
      }
    })
    .catch((error) => console.error("Error:", error));
}

document.addEventListener("click", function (e) {
  if (e.target.closest(".eliminar-btn")) {
    const idcliente = e.target
      .closest(".eliminar-btn")
      .getAttribute("data-idcliente");
    if (confirm("¿Estás seguro de desactivar esta cliente?")) {
      eliminarcliente(idcliente);
    }
  }
});

function abrirModalclienteParaEdicion(idcliente) {
  console.log("ID de cliente recibido:", idcliente); // Depuración

  fetch(`clientes/getclienteById/${idcliente}`)
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

      const cliente = data.data;

      // Asigna los valores a los campos del modal formulario
      document.getElementById("idcliente").value = cliente.idcliente || "";
      document.getElementById("nombre").value = cliente.nombre || "";
      document.getElementById("apellido").value = cliente.apellido || "";
      document.getElementById("cedula").value = cliente.cedula || "";
      document.getElementById("telefono_principal").value =
        cliente.telefono_principal || "";
      document.getElementById("correo_electronico").value =
        cliente.correo_electronico || "";

      document.getElementById("direccion").value = cliente.direccion || "";
      document.getElementById("observaciones").value =
        cliente.observaciones || "";

      document.getElementById("estatus").value = cliente.estatus || "";

      // Abre el modal
      abrirModalcliente();
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
    const idcliente = e.target
      .closest(".editar-btn")
      .getAttribute("data-idcliente");

    if (!idcliente || isNaN(idcliente)) {
      alert("ID de cliente no válido.");
      return;
    }

    abrirModalclienteParaEdicion(idcliente);
  }
});
function abrirModalcliente() {
  const modal = document.getElementById("clienteModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}

function cerrarModalcliente() {
  const modal = document.getElementById("clienteModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("clienteForm").reset();
}
