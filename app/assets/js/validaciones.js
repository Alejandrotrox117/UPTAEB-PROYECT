const expresiones = {
  nombre: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,50}$/,
  apellido: /^[a-zA-Z\s]{3,20}$/,
  telefono_principal: /^(0414|0424|0426|0416|0412)\d{7}$/,
  direccion: /^.{5,100}$/,
  estatus: /^(Activo|Inactivo)$/,
  observaciones: /^.{0,50}$/,
  email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
  fecha: /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/,
  fechaNacimiento: /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/,
  cedula: /^(V|E|J)-\d{7,9}$/i,
  password: /^.{6,16}$/,
  textoGeneral: /^.{2,100}$/,
  genero: /^(MASCULINO|FEMENINO|OTRO)$/,
  usuario: /^[a-zA-Z0-9_.-]{3,15}$/,

  precio: /^\d+(\.\d{1,4})?$/,
  cantidad: /^\d+(\.\d{1,3})?$/,
  porcentajeDescuento: /^(0|[1-9]\d?|100)(\.\d{1,2})?$/,
  subtotal: /^\d+(\.\d{1,2})?$/,
  total: /^\d+(\.\d{1,2})?$/,
  peso: /^\d+(\.\d{1,3})?$/,
  tasa: /^\d+(\.\d{1,4})?$/,
  montoDescuento: /^\d+(\.\d{1,2})?$/,


  cantidadMinima: /^([1-9]\d*(\.\d{1,3})?|0\.[0-9]{1,3})$/,
  precioMinimo: /^([1-9]\d*(\.\d{1,4})?|0\.[0-9]{1,4})$/,


  codigoMoneda: /^[A-Z]{3}$/,


  numeroVenta: /^VT\d{6}$/,


  decimal2: /^\d+(\.\d{1,2})?$/,
  decimal3: /^\d+(\.\d{1,3})?$/,
  decimal4: /^\d+(\.\d{1,4})?$/,


  enteroPositivo: /^[1-9]\d*$/,
  enteroNoNegativo: /^(0|[1-9]\d*)$/
};


function validarCampoNumerico(input, tipo, mensajes) {
  if (!input || input.offsetParent === null) {
    return true;
  }

  const errorDiv = input.nextElementSibling;
  const valor = parseFloat(input.value.trim());
  const valorTexto = input.value.trim();


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");


  if (valorTexto === "") {
    if (mensajes.vacio) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.vacio;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
    return true;
  }


  const regex = expresiones[tipo];
  if (regex && !regex.test(valorTexto)) {
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


  switch (tipo) {
    case 'precio':
    case 'precioMinimo':
      if (valor <= 0) {
        if (mensajes.minimo || mensajes.formato) {
          if (errorDiv) {
            errorDiv.textContent = mensajes.minimo || "El precio debe ser mayor a 0";
            errorDiv.classList.remove("hidden");
          }
          input.classList.add("border-red-500", "focus:ring-red-500");
          input.classList.remove("border-gray-300", "focus:ring-green-400");
          return false;
        }
      }
      break;

    case 'cantidad':
    case 'cantidadMinima':
      if (valor <= 0) {
        if (mensajes.minimo || mensajes.formato) {
          if (errorDiv) {
            errorDiv.textContent = mensajes.minimo || "La cantidad debe ser mayor a 0";
            errorDiv.classList.remove("hidden");
          }
          input.classList.add("border-red-500", "focus:ring-red-500");
          input.classList.remove("border-gray-300", "focus:ring-green-400");
          return false;
        }
      }
      break;

    case 'porcentajeDescuento':
      if (valor < 0 || valor > 100) {
        if (mensajes.rango || mensajes.formato) {
          if (errorDiv) {
            errorDiv.textContent = mensajes.rango || "El descuento debe estar entre 0% y 100%";
            errorDiv.classList.remove("hidden");
          }
          input.classList.add("border-red-500", "focus:ring-red-500");
          input.classList.remove("border-gray-300", "focus:ring-green-400");
          return false;
        }
      }
      break;

    case 'peso':
      if (valor < 0) {
        if (mensajes.minimo || mensajes.formato) {
          if (errorDiv) {
            errorDiv.textContent = mensajes.minimo || "El peso no puede ser negativo";
            errorDiv.classList.remove("hidden");
          }
          input.classList.add("border-red-500", "focus:ring-red-500");
          input.classList.remove("border-gray-300", "focus:ring-green-400");
          return false;
        }
      }
      break;

    case 'tasa':
      if (valor <= 0) {
        if (mensajes.minimo || mensajes.formato) {
          if (errorDiv) {
            errorDiv.textContent = mensajes.minimo || "La tasa debe ser mayor a 0";
            errorDiv.classList.remove("hidden");
          }
          input.classList.add("border-red-500", "focus:ring-red-500");
          input.classList.remove("border-gray-300", "focus:ring-green-400");
          return false;
        }
      }
      break;
  }


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}


function validarRango(input, min, max, mensajes) {
  if (!input || input.offsetParent === null) {
    return true;
  }

  const valor = parseFloat(input.value.trim());
  const errorDiv = input.nextElementSibling;

  if (isNaN(valor)) {
    return true;
  }

  if (valor < min || valor > max) {
    if (mensajes.rango) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.rango;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }

  return true;
}


function validarCampo(input, regex, mensajes = {}, esOpcional = false) {
  // Si no hay input o no está visible, retornar verdadero
  if (!input || input.offsetParent === null) {
    return true;
  }

  const errorDiv = input.nextElementSibling;
  const valor = input.value.trim();

  // Limpiar estados de error previos
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");

  // Si el valor está vacío
  if (valor === "") {
    // Si es opcional y está vacío, es válido
    if (esOpcional) {
      return true;
    }

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

  // Validar formato solo si hay valor
  if (valor !== "" && regex && !regex.test(valor)) {
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

  // Campo válido
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


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");


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

  // Verificar si el valor existe en las opciones del select
  const options = Array.from(input.options);
  const optionExists = options.some(option => option.value === valor);
  if (valor !== "" && !optionExists) {
    if (mensajes.invalido || mensajes.formato) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.invalido || mensajes.formato || "Valor seleccionado inválido.";
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}
function validarFecha(input, mensajes) {
  const errorDiv = input.nextElementSibling;
  const valor = input.value.trim();


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");


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
    return true;
  }


  const fechaSeleccionada = new Date(valor);
  if (isNaN(fechaSeleccionada.getTime())) {
    if (mensajes.formato || mensajes.fechaInvalida) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.formato || mensajes.fechaInvalida || "Formato de fecha inválido";
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }


  const fechaHoy = new Date();
  fechaHoy.setHours(23, 59, 59, 999);

  const fechaAyer = new Date();
  fechaAyer.setDate(fechaAyer.getDate() - 1);
  fechaAyer.setHours(0, 0, 0, 0);


  if (fechaSeleccionada < fechaAyer || fechaSeleccionada > fechaHoy) {
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


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}

export function validarFechaNacimiento(input, mensajes) {
  const errorDiv = input.nextElementSibling;
  const valor = input.value.trim();


  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");


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
    return true;
  }


  const fechaSeleccionada = new Date(valor);
  if (isNaN(fechaSeleccionada.getTime())) {
    if (mensajes.formato || mensajes.fechaInvalida) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.formato || mensajes.fechaInvalida || "Formato de fecha inválido";
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }


  const fechaHoy = new Date();
  fechaHoy.setHours(23, 59, 59, 999);

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


  const fechaMinima = new Date();
  fechaMinima.setFullYear(fechaMinima.getFullYear() - 120);

  if (fechaSeleccionada < fechaMinima) {
    if (mensajes.fechaMuyAntigua) {
      if (errorDiv) {
        errorDiv.textContent = mensajes.fechaMuyAntigua;
        errorDiv.classList.remove("hidden");
      }
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");
      return false;
    }
  }


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

      if (campo.tipo === "fecha") {
        input.addEventListener("input", () => {
          if (input.offsetParent !== null) {
            validarFecha(input, campo.mensajes);
          }
        });
        input.addEventListener("blur", () => {
          if (input.offsetParent !== null) {
            validarFecha(input, campo.mensajes);
          }
        });
        input.addEventListener("change", () => {
          if (input.offsetParent !== null) {
            validarFecha(input, campo.mensajes);
          }
        });
      } else if (campo.tipo === "fechaNacimiento") {

        input.addEventListener("input", () => {
          if (input.offsetParent !== null) {
            validarFechaNacimiento(input, campo.mensajes);
          }
        });
        input.addEventListener("blur", () => {
          if (input.offsetParent !== null) {
            validarFechaNacimiento(input, campo.mensajes);
          }
        });
        input.addEventListener("fechaNacimiento", () => {
          if (input.offsetParent !== null) {
            validarFechaNacimiento(input, campo.mensajes);
          }
        });
        input.addEventListener("change", () => {
          if (input.offsetParent !== null) {
            validarFechaNacimiento(input, campo.mensajes);
          }
        });
      } else if (campo.tipoNumerico) {

        input.addEventListener("input", () => {
          if (input.offsetParent !== null) {
            validarCampoNumerico(input, campo.tipoNumerico, campo.mensajes);
            if (campo.min !== undefined && campo.max !== undefined) {
              validarRango(input, campo.min, campo.max, campo.mensajes);
            }
          }
        });
        input.addEventListener("blur", () => {
          if (input.offsetParent !== null) {
            validarCampoNumerico(input, campo.tipoNumerico, campo.mensajes);
            if (campo.min !== undefined && campo.max !== undefined) {
              validarRango(input, campo.min, campo.max, campo.mensajes);
            }
          }
        });
      } else {

        input.addEventListener("input", () => {
          if (input.offsetParent !== null) {
            validarCampo(input, campo.regex, campo.mensajes, campo.opcional || false);
          }
        });
        input.addEventListener("blur", () => {
          if (input.offsetParent !== null) {
            validarCampo(input, campo.regex, campo.mensajes, campo.opcional || false);
          }
        });
      }
    }
  });
};


export function validarCamposVacios(campos, formId = null) {
  let formularioValido = true;

  for (let campo of campos) {
    // Saltar validación si el campo es opcional
    if (campo.opcional) {
      continue;
    }

    let input;
    if (formId) {
      const form = document.getElementById(formId);
      input = form ? form.querySelector(`#${campo.id}`) : null;
    } else {
      input = document.getElementById(campo.id);
    }

    // Si el input no existe o no está visible, continuar
    if (!input || input.offsetParent === null) {
      continue;
    }

    let valor = input.value.trim();
    let nombreCampo = campo.id;

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
      break;
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




export function registrarEntidad({
  formId,
  endpoint,
  campos,
  mapeoNombres = {},
  onSuccess,
  onError
}) {
  const form = document.getElementById(formId);
  if (!form) {
    console.error(`Formulario ${formId} no encontrado`);
    return Promise.reject("Formulario no encontrado");
  }


  if (!validarCamposVacios(campos, formId)) {
    return Promise.reject("Validación de campos vacíos falló");
  }


  let formularioConErrores = false;
  for (const campo of campos) {
    const inputElement = form.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValido = true;
    if (campo.tipo === "select") {
      if (campo.mensajes?.vacio) {
        esValido = validarSelect(campo.id, campo.mensajes, formId);
      }
    } else if (campo.tipo === "fecha") {

      if (inputElement.value.trim() !== "" || campo.mensajes?.vacio) {
        esValido = validarFecha(inputElement, campo.mensajes);
      }
    } else if (campo.tipo === "fechaNacimiento") {

      if (inputElement.value.trim() !== "" || campo.mensajes?.vacio) {
        esValido = validarFechaNacimiento(inputElement, campo.mensajes);
      }
    } else if (["input", "textarea", "text"].includes(campo.tipo)) {
      if (inputElement.value.trim() !== "" || campo.mensajes?.vacio) {
        esValido = validarCampo(inputElement, campo.regex, campo.mensajes);
      }
    }
    if (!esValido) formularioConErrores = true;
  }

  if (formularioConErrores) {
    Swal.fire("Atención", "Por favor, corrija los campos marcados.", "warning");
    return Promise.reject("Errores de validación");
  }


  const formData = new FormData(form);
  const dataParaEnviar = {};


  for (let [key, value] of formData.entries()) {
    const nombreFinal = mapeoNombres[key] || key;
    dataParaEnviar[nombreFinal] = value || "";
  }


  return fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(dataParaEnviar),
  })
    .then((response) => {
      if (!response.ok) {
        return response.json().then((errData) => {
          throw { status: response.status, data: errData };
        });
      }
      return response.json();
    })
    .then((result) => {
      if (result.status) {
        if (onSuccess) onSuccess(result);
      } else {
        if (onError) onError(result);
      }
      return result;
    })
    .catch((error) => {
      console.error("Error en fetch:", error);
      let errorMessage = "Ocurrió un error de conexión.";
      if (error.data?.message) {
        errorMessage = error.data.message;
      } else if (error.status) {
        errorMessage = `Error del servidor: ${error.status}.`;
      }

      if (onError) {
        onError({ message: errorMessage });
      } else {
        Swal.fire("Error", errorMessage, "error");
      }
      throw error;
    });
}

export function cargarSelect({ selectId, endpoint, optionTextFn, optionValueFn, placeholder = "Seleccione...", onLoaded = null }) {
  const select = document.getElementById(selectId);
  if (!select) return;

  select.innerHTML = `<option value="">${placeholder}</option>`;

  fetch(endpoint)
    .then(response => response.json())
    .then(items => {

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



export {
  expresiones,
  validarCampo,
  validarCampoNumerico,
  validarRango,
  inicializarValidaciones,
  validarFecha,
  validarSelect
};