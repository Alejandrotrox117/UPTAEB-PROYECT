// Expresiones regulares para validación
const expresiones = {
  cedula: /^(V|E|J)-\d{8}$/, // Formato de cédula
  nombre: /^[a-zA-Z\s]{5,30}$/, // Nombre
  apellido: /^[a-zA-Z\s]{5,30}$/, // Apellido
  telefono_principal: /^\d{11}$/, // Teléfono
  direccion: /^.{5,100}$/, // Dirección
  estatus: /^(Activo|Inactivo)$/, // Estatus
  observaciones: /^.{0,50}$/, // Observaciones
  email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/, // Email
  fecha: /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/, // Fecha
  
};
function validarCampo(input, regex, mensajes) {
  // Solo valida si el input está visible
  if (!input || input.offsetParent === null) {
    return true; // Considera válido si está oculto
  }

  const errorDiv = input.nextElementSibling;
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

function validarSelect(select, mensajes, formId = null) {
  let input = select;
  // Si se pasa formId y select es un string (id), busca el select dentro del formulario
  if (formId && typeof select === "string") {
    const form = document.getElementById(formId);
    input = form ? form.querySelector(`#${select}`) : null;
  } else if (typeof select === "string") {
    input = document.getElementById(select);
  }

  if (!input || input.offsetParent === null) {
    return false;
  }

  const errorDiv = input.nextElementSibling;
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

  // Si pasa la validación, limpiar errores
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
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


const inicializarValidaciones = (campos, formId = null) => {
  campos.forEach((campo) => {
    let input;
    if (formId) {
      const form = document.getElementById(formId);
      input = form ? form.querySelector(`#${campo.id}`) : null;
    } else {
      input = document.getElementById(campo.id);
    }
    if (input) {
      input.addEventListener("blur", () => {
        // Solo valida si está visible
        if (input.offsetParent !== null) {
          validarCampo(input, campo.regex, campo.mensajes);
        }
      });
    }
  });
};


//FUNCIONES DE VALIDACIONES CAMPOS VACIOS
export function validarCamposVacios(campos, formId = null) {
  let formularioValido = true;

  for (let campo of campos) {
    let input;
    if (formId) {
      const form = document.getElementById(formId);
      input = form ? form.querySelector(`#${campo.id}`) : null;
    } else {
      input = document.getElementById(campo.id);
    }

    // Solo valida si el input existe y está visible
    if (!input || input.offsetParent === null) {
      continue; // Salta campos ocultos o que no existen o están ocultos
    }

    let valor = input.value.trim();
    let nombreCampo = campo.id;
    // Busca el label asociado al input
    const label = document.querySelector(`label[for="${campo.id}"]`);
    if (label) {
      nombreCampo = label.textContent.replace(/[*:]/g, "").trim();
    }

    if (valor === "") {
      Swal.fire({
        title: "¡Error!",
        text: `El campo "${nombreCampo}" no puede estar vacío.`,
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      formularioValido = false;
      input.classList.add("border-red-500");
      break; // Opcional: detener en el primer error
    } else {
      input.classList.remove("border-red-500");
    }
  }

  return formularioValido;
}

export function limpiarValidaciones(campos, formId = null) {
  campos.forEach((campo) => {
    let input;
    if (formId) {
      const form = document.getElementById(formId);
      input = form ? form.querySelector(`#${campo.id}`) : null;
    } else {
      input = document.getElementById(campo.id);
    }
    if (input) {
      const errorDiv = input.nextElementSibling;
      if (errorDiv) {
        errorDiv.textContent = "";
        errorDiv.classList.add("hidden");
      }
      input.classList.remove("border-red-500", "focus:ring-red-500");
      input.classList.add("border-gray-300", "focus:ring-green-400");
    }
  });
}

export function validarDetalleVenta() {
  const detalleVentaBody = document.getElementById("detalleVentaBody");
  const filas = detalleVentaBody ? detalleVentaBody.querySelectorAll("tr") : [];
  if (filas.length === 0) {
    Swal.fire("Atención", "Debe agregar al menos un producto al detalle.", "warning");
    return false;
  }
  let valido = true;
  filas.forEach((fila) => {
    const cantidad = fila.querySelector(".cantidad-input");
    const precio = fila.querySelector(".precio-input");
    if (!cantidad || !precio || cantidad.value <= 0 || precio.value <= 0) {
      valido = false;
      cantidad && cantidad.classList.add("border-red-500");
      precio && precio.classList.add("border-red-500");
    } else {
      cantidad.classList.remove("border-red-500");
      precio.classList.remove("border-red-500");
    }
  });
  if (!valido) {
    Swal.fire("Atención", "Verifique que todas las cantidades y precios sean mayores a 0.", "warning");
  }
  return valido;
}


//REGISTRAR
export function registrarEntidad({ formId, endpoint, campos, onSuccess, onError }) {
  let formularioValido = true;

  campos.forEach((campo) => {
    let input = null;
    if (formId) {
      const form = document.getElementById(formId);
      input = form ? form.querySelector(`#${campo.id}`) : null;
    } else {
      input = document.getElementById(campo.id);
    }
    if (input) {
      let esValido = false;
      if (campo.tipo === "date") {
        esValido = validarFecha(input, campo.mensajes);
      } else if (campo.tipo === "select") {
        esValido = validarSelect(campo.id, campo.mensajes, formId);
      } else {
        esValido = validarCamposVacios([campo], formId);
      }
      if (!esValido) formularioValido = false;
    }
  });

  if (!formularioValido) return;

  const formData = new FormData(document.getElementById(formId));
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });

  fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        if (typeof onSuccess === "function") onSuccess(result);
      } else {
        if (typeof onError === "function") onError(result);
        else {
          Swal.fire({
            title: "¡Error!",
            text: result.message || "No se pudo registrar.",
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        }
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
//CONSULTAR EN UN SELECT
export function cargarSelect({selectId, endpoint, optionTextFn, optionValueFn, placeholder = "Seleccione...", onLoaded = null}) {
  const select = document.getElementById(selectId);
  if (!select) return;

  select.innerHTML = `<option value="">${placeholder}</option>`;

  fetch(endpoint)
    .then(response => response.json())
    .then(items => {
      // Soporta respuesta tipo {data: [...]}
      if (items && typeof items === "object" && Array.isArray(items.data)) {
        items = items.data;
      }
      if (Array.isArray(items)) {
        items.forEach(item => {
          const option = document.createElement("option");
          option.value = optionValueFn(item);
          option.className = "text-gray-700";
          option.textContent = optionTextFn(item);
          select.appendChild(option);
        });
        if (typeof onLoaded === "function") onLoaded(items);
      }
    })
    .catch(error => {
      console.error(`Error al cargar ${selectId}:`, error);
    });
}


// Exportar las funciones y expresiones
export { expresiones, validarCampo, inicializarValidaciones,validarFecha,validarSelect };