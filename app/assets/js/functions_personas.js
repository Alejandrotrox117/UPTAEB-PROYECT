document.addEventListener("DOMContentLoaded", function () {
    $('#TablaPersonas').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "personas/getPersonasData",
          type: "GET",
          dataSrc: "data" // Indica que los datos están en la clave "data" de la respuesta
        },
        columns: [
          { data: "idpersona", title: "Nro" }, // Nro
          { data: "nombre", title: "Nombre" }, // Nombre
          { data: "apellido", title: "Apellido" }, // Apellido
          { data: "cedula", title: "Cédula" }, // Cédula
          { data: "rif", title: "Rif" }, // Rif
          { data: "tipo", title: "Tipo" }, // Tipo
          { data: "genero", title: "Genero" }, // Genero
          { data: "fecha_nacimiento", title: "Fecha de Nacimiento" }, // Fecha de Nacimiento
          { data: "telefono_principal", title: "Teléfono" }, // Teléfono
          { data: "correo_electronico", title: "Correo Electrónico" }, // Correo Electrónico
          { data: "direccion", title: "Dirección" }, // Dirección
          { data: "ciudad", title: "Ciudad" }, // Ciudad
          { data: "estado", title: "Estado" }, // Estado
          { data: "pais", title: "País" }, // País
          { data: "estatus", title: "Status" }, // Status
          {
            data: null, // Usar `null` porque no está asociado a un campo específico
            title: "Acciones",
            orderable: false, // Desactivar ordenamiento para esta columna
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
            }
          }
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

    const personaForm = document.getElementById("personaForm"); // Asegúrate de que el ID del formulario sea correcto
    personaForm.addEventListener("submit", function (e) {
      e.preventDefault(); // Evita que el formulario se envíe de forma tradicional
  
      // Convertir los datos del formulario en un objeto JSON
      const formData = new FormData(personaForm);
      const data = {};
      formData.forEach((value, key) => {
        data[key] = value;
      });
  
      console.log(data); // Depuración: Verifica los datos antes de enviar
  
      fetch("personas/setPersona", { // Asegúrate de que la URL coincida con tu controlador
        method: "POST",
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
            $('#TablaPersonas').DataTable().ajax.reload(); // Recarga la tabla
          } else {
            alert(result.message); // Muestra mensaje de error
          }
        })
        .catch((error) => {
          console.error("Error:", error); // Maneja errores de red
          alert("Ocurrió un error al procesar la solicitud.");
        });
    });



    
    
      

      });
function abrirModalPersona() {
    const modal = document.getElementById('personaModal');
    modal.classList.remove('opacity-0', 'pointer-events-none');
  }

  function cerrarModalPersona() {
    const modal = document.getElementById('personaModal');
    modal.classList.add('opacity-0', 'pointer-events-none');
    document.getElementById('personaForm').reset();
  }

  