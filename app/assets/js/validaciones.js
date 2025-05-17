// Expresiones regulares para validación
const expresiones = {
  cedula: /^(V|E|J)-\d{8}$/, // Formato de cédula
  nombre: /^[a-zA-Z\s]{2,20}$/, // Nombre
  apellido: /^[a-zA-Z\s]{2,50}$/, // Apellido
  telefono_principal: /^\d{11}$/, // Teléfono
  direccion: /^.{5,100}$/, // Dirección
  estatus: /^(Activo|Inactivo)$/, // Estatus
  observaciones: /^.{0,200}$/, // Observaciones
  email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/, // Email
  fecha: /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/, // Fecha
  
};
function validarCampo(input, regex, mensajes) {
  const errorDiv = input.nextElementSibling; // Div donde se muestra el mensaje de error
  const valor = input.value.trim();

  // Limpiar mensajes de error previos
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");

  // Validar si el campo está vacío
  if (valor === "") {
    if (mensajes.vacio) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.vacio;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }

  // Validar si el valor cumple con la expresión regular
  if (regex && !regex.test(valor)) {
    if (mensajes.formato) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.formato;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }

  // Si pasa todas las validaciones, limpiar errores
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}

function validarSelect(select, mensajes) {
  const errorDiv = select.nextElementSibling; // Div donde se muestra el mensaje de error
  const valor = select.value.trim();

  // Limpiar mensajes de error previos
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  select.classList.remove("border-red-500", "focus:ring-red-500");
  select.classList.add("border-gray-300", "focus:ring-green-400");

  // Validar si el campo está vacío
  if (valor === "") {
    if (mensajes.vacio) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.vacio;
        errorDiv.classList.remove("hidden");
      }
      select.classList.add("border-red-500", "focus:ring-red-500");
      select.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }

  // Si pasa la validación, limpiar errores
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  select.classList.remove("border-red-500", "focus:ring-red-500");
  select.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}

function validarFecha(input, mensajes) {
  const errorDiv = input.nextElementSibling; // Div donde se muestra el mensaje de error
  const valor = input.value.trim();
  const fechaSeleccionada = new Date(valor);
  const fechaHoy = new Date();
  fechaHoy.setHours(0, 0, 0, 0); // Asegurarse de que la hora sea 00:00:00 para comparar solo fechas

  // Limpiar mensajes de error previos
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");

  // Validar si el campo está vacío
  if (valor === "") {
    if (mensajes.vacio) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.vacio;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }

  // Validar si la fecha es posterior a hoy
  if (fechaSeleccionada > fechaHoy) {
    if (mensajes.fechaPosterior) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.fechaPosterior;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }

  // Si pasa todas las validaciones, limpiar errores
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}
// function validarCampo(input, regex, mensaje) {
//   const errorDiv = input.nextElementSibling; // Div donde se muestra el mensaje de error

//   if (!regex.test(input.value.trim())) {
//     if (errorDiv) {
//       errorDiv.textContent = mensaje;
//       errorDiv.classList.remove("hidden");
//     }
//     input.classList.add("focus:invalid:border-red-500", "focus:ring-red-700");
//     input.classList.remove("border-gray-300", "focus:ring-green-400");
//     return false;
//   } else {
//     if (errorDiv) {
//       errorDiv.textContent = "";
//       errorDiv.classList.add("hidden");
//     }
//     input.classList.remove("border-red-500", "focus:ring-red-500");
//     input.classList.add("border-green-300", "focus:ring-green-400");
//     return true;
//   }
// }

// // Función para inicializar las validaciones en tiempo real
// const inicializarValidaciones = (campos) => {
//   campos.forEach((campo) => {
//     const input = document.getElementById(campo.id);
//     if (input) {
//       input.addEventListener("input", () => {
//         validarCampo(input, campo.regex, campo.mensaje);
//       });
//     }
//   });
// };

const inicializarValidaciones = (campos) => {
  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      input.addEventListener("blur", () => {
        validarCampo(input, campo.regex, campo.mensajes);
      });
    }
  });
};7


//FUNCIONES DE VALIDACIONES
function validarCamposVacios(campos) {
  let formularioValido = true; // Variable para rastrear si el formulario es válido

  // Validar campos vacíos
  for (let campo of campos) {
    // Omitir la validación del campo idventa
    if (campo.id === "idventa") {
      continue;
    }

    // Obtener el valor del campo
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
      formularioValido = false; // Marcar el formulario como no válido
    }
  }

  return formularioValido; // Retornar true si todos los campos son válidos, false si no
}

// Exportar las funciones y expresiones
export { expresiones, validarCampo, inicializarValidaciones,validarCamposVacios,validarFecha,validarSelect };