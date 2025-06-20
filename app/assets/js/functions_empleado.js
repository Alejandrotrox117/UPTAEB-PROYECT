import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";
document.addEventListener("DOMContentLoaded", function () {
  
   
  const campos = [
    { id: "nombre", regex: expresiones.nombre, mensaje: "El nombre debe tener entre 10 y 20 caracteres alfabéticos." },
    { id: "apellido", regex: expresiones.apellido, mensaje: "El apellido debe tener entre 10 y 20 caracteres alfabéticos." },
    { id: "telefono_principal", regex: expresiones.telefono_principal, mensaje: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras." },
    { id: "direccion", regex: expresiones.direccion, mensaje: "La dirección debe tener entre 20 y 50 caracteres." },
   ];

  inicializarValidaciones(campos);
  $("#TablaEmpleado").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "empleados/getEmpleadoData", 
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idempleado", title: "Nro" },
      { data: "nombre", title: "Nombre" },
      { data: "apellido", title: "Apellido" },
      { data: "identificacion", title: "Identificación" },
      { data: "telefono_principal", title: "Teléfono" },
      { data: "correo_electronico", title: "Correo Electrónico" },
      { data: "direccion", title: "Dirección" },
      { data: "fecha_nacimiento", title: "Fecha de Nacimiento" },
      { data: "genero", title: "Género" },
      { data: "puesto", title: "Puesto" },
      { data: "salario", title: "Salario" },
      { data: "estatus", title: "Estatus" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idempleado="${row.idempleado}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idempleado="${row.idempleado}">
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







  
  document
    .getElementById("registrarEmpleadoBtn")
    .addEventListener("click", function () {
      manejarRegistro(campos);
    });
  
  document
    .getElementById("abrirModalBtn")
    .addEventListener("click", function () {
      abrirModal("empleadoModal");
    });

  
  document
    .getElementById("cerrarModalBtn")
    .addEventListener("click", function () {
      cerrarModal("empleadoModal");
    });
  
  document
    .getElementById("empleadoForm")
    .addEventListener("submit", function (e) {
      e.preventDefault(); 

      
      const formData = new FormData(this);
      const data = {};
      formData.forEach((value, key) => {
        data[key] = value;
      });

      console.log("Datos a enviar:", data); 

      
      if (!data.nombre || !data.apellido || !data.identificacion) {
        alert("Por favor, completa todos los campos obligatorios.");
        return;
      }

      
      const idempleado = document.getElementById("idempleado").value;
      const url = idempleado
        ? "empleados/updateEmpleado"
        : "empleados/createEmpleado";
      const method = idempleado ? "PUT" : "POST";

      fetch(url, {
        method: method,
        headers: { "Content-Type": "application/json" }, 
        body: JSON.stringify(data), 
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
          }
          return response.json();
        })
        .then((result) => {
          if (result.status) {
            alert(result.message);
            cerrarModalEmpleado();
            $("#TablaEmpleado").DataTable().ajax.reload(); 
          } else {
            alert(result.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Ocurrió un error al procesar la solicitud.");
        });
    });

  
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idempleado = e.target
        .closest(".editar-btn")
        .getAttribute("data-idempleado");
      console.log("Botón de edición clicado. ID de empleado:", idempleado); 

      if (!idempleado || isNaN(idempleado)) {
        alert("ID de empleado no válido.");
        return;
      }

      abrirModalEmpleadoParaEdicion(idempleado);
    }
  });

  
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idempleado = e.target
        .closest(".eliminar-btn")
        .getAttribute("data-idempleado");
      if (confirm("¿Estás seguro de desactivar este empleado?")) {
        eliminarEmpleado(idempleado);
      }
    }
  });
});


function eliminarEmpleado(idempleado) {
  fetch(`empleados/deleteEmpleado`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idempleado }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); 
        $("#TablaEmpleado").DataTable().ajax.reload(); 
      } else {
        alert(result.message); 
      }
    })
    .catch((error) => console.error("Error:", error));
}


function abrirModalEmpleadoParaEdicion(idempleado) {
  console.log("ID de empleado recibido:", idempleado); 

  fetch(`empleados/getEmpleadoById/${idempleado}`)
    .then((response) => {
      console.log("Respuesta HTTP:", response); 
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos del backend:", data); 

      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const empleado = data.data;

      
      document.getElementById("idempleado").value = empleado.idempleado || "";
      document.getElementById("nombre").value = empleado.nombre || "";
      document.getElementById("apellido").value = empleado.apellido || "";
      document.getElementById("identificacion").value =
        empleado.identificacion || "";
      document.getElementById("telefono_principal").value =
        empleado.telefono_principal || "";
      document.getElementById("correo_electronico").value =
        empleado.correo_electronico || "";
      document.getElementById("direccion").value = empleado.direccion || "";
      document.getElementById("fecha_nacimiento").value =
        empleado.fecha_nacimiento || "";
     
      document.getElementById("puesto").value = empleado.puesto || "";
      document.getElementById("salario").value = empleado.salario || "";
      setTimeout(() => { 
      document.getElementById("estatus").value = empleado.estatus || "";
       document.getElementById("genero").value = empleado.genero || "";
      }, 5000);
      document.getElementById("fecha_inicio").value =
        empleado.fecha_inicio || "";
      document.getElementById("fecha_fin").value = empleado.fecha_fin || "";

      
      abrirModalEmpleado();
    })
    .catch((error) => {
      console.error("Error capturado:", error.message); 
      alert(
        "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente."
      );
    });
}


function abrirModalEmpleado() {
  const modal = document.getElementById("empleadoModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}


function cerrarModalEmpleado() {
  const modal = document.getElementById("empleadoModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("empleadoForm").reset();
}


function validarCamposVacios(campos) {
  let formularioValido = true; 

  
  for (let campo of campos) {
    
    if (campo.id === "idempleado") {
      continue;
    }

    
    const input = document.getElementById(campo.id);
    if (!input) {
      console.warn(`El campo con ID "${campo.id}" no existe en el DOM.`);
      continue;
    }

    let valor = input.value.trim();
    if (valor === "") {
      Swal.fire({
        title: "¡Error!",
        text: `El campo "${campo.id}" no puede estar vacío.`,
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      formularioValido = false; 
    }
  }

  return formularioValido; 
}

function manejarRegistro(campos) {
  
  const formularioValido = validarCamposVacios(campos);
  if (!formularioValido) {
    return; 
  }

  
  let formatoValido = true;
  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      const valido = validarCampo(input, campo.regex, campo.mensaje);
      if (!valido) formatoValido = false;
    }
  });

  
  if (!formatoValido) {
    Swal.fire({
      title: "¡Error!",
      text: "Por favor, corrige los errores en el formulario.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    return;
  }

  
  const formData = new FormData(document.getElementById("empleadoForm"));
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });
  const idempleado = document.getElementById("idempleado").value;
 const url = idempleado
        ? "empleados/updateEmpleado"
        : "empleados/createEmpleado";
  const method = idempleado ? "PUT" : "POST";

  fetch(url, {
    method: method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "Cliente registrado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#TablaEmpleado").DataTable().ajax.reload();
          cerrarModal("empleadoModal");
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "No se pudo registrar el cliente.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al procesar la solicitud.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });

}
