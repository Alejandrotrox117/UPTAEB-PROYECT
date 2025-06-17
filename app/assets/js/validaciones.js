// Expresiones regulares para validación
const expresiones = {
  nombre: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/,
  apellido: /^[a-zA-Z\s]{3,20}$/,
  telefono_principal: /^(0414|0424|0426|0416|0412)\d{7}$/,
  direccion: /^.{5,100}$/,
  estatus: /^(Activo|Inactivo)$/,
  observaciones: /^.{0,50}$/,
  email: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
  fecha: /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/,
  fechaNacimiento: /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/,
  cedula: /^(V|E|J)?-?\d{8}$/i,
  password: /^.{6,16}$/,
  textoGeneral: /^.{2,100}$/,
  genero: /^(MASCULINO|FEMENINO|OTRO)$/,
  
  // Nuevas expresiones para campos numéricos de ventas
  precio: /^\d+(\.\d{1,4})?$/,                    // Precio: números positivos con hasta 4 decimales
  cantidad: /^\d+(\.\d{1,3})?$/,                  // Cantidad: números positivos con hasta 3 decimales
  porcentajeDescuento: /^(0|[1-9]\d?|100)(\.\d{1,2})?$/, // Descuento: 0-100 con hasta 2 decimales
  subtotal: /^\d+(\.\d{1,2})?$/,                  // Subtotal: números positivos con hasta 2 decimales
  total: /^\d+(\.\d{1,2})?$/,                     // Total: números positivos con hasta 2 decimales
  peso: /^\d+(\.\d{1,3})?$/,                      // Peso: números positivos con hasta 3 decimales
  tasa: /^\d+(\.\d{1,4})?$/,                      // Tasa de cambio: números positivos con hasta 4 decimales
  montoDescuento: /^\d+(\.\d{1,2})?$/,            // Monto descuento: números positivos con hasta 2 decimales
  
  // Validaciones específicas para rangos
  cantidadMinima: /^([1-9]\d*(\.\d{1,3})?|0\.[0-9]{1,3})$/, // Cantidad mínima 0.001
  precioMinimo: /^([1-9]\d*(\.\d{1,4})?|0\.[0-9]{1,4})$/,   // Precio mínimo 0.0001
  
  // Para campos de código de moneda
  codigoMoneda: /^[A-Z]{3}$/,                     // Código de moneda: 3 letras mayúsculas (USD, EUR, etc.)
  
  // Para números de venta/factura
  numeroVenta: /^VT\d{6}$/,                       // Número de venta: VT seguido de 6 dígitos
  
  // Para validar decimales en general
  decimal2: /^\d+(\.\d{1,2})?$/,                  // Números con hasta 2 decimales
  decimal3: /^\d+(\.\d{1,3})?$/,                  // Números con hasta 3 decimales
  decimal4: /^\d+(\.\d{1,4})?$/,                  // Números con hasta 4 decimales
  
  // Para validar enteros positivos
  enteroPositivo: /^[1-9]\d*$/,                   // Enteros mayores a 0
  enteroNoNegativo: /^(0|[1-9]\d*)$/              // Enteros mayores o iguales a 0
};

// Función para validar campos numéricos con validaciones específicas
function validarCampoNumerico(input, tipo, mensajes) {
  if (!input || input.offsetParent === null) {
    return true;
  }

  const errorDiv = input.nextElementSibling;
  const valor = parseFloat(input.value.trim());
  const valorTexto = input.value.trim();

  // Limpiar mensajes de error previos
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-gray-300", "focus:ring-green-400");

  // Validar si el campo está vacío
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

  // Validar formato numérico
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

  // Validaciones específicas por tipo
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

  // Si pasa todas las validaciones
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}

// Función para validar rangos de valores
function validarRango(input, min, max, mensajes) {
  if (!input || input.offsetParent === null) {
    return true;
  }

  const valor = parseFloat(input.value.trim());
  const errorDiv = input.nextElementSibling;

  if (isNaN(valor)) {
    return true; // Si no es número, lo maneja validarCampoNumerico
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

// Función para validar campos
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
    return true; // Si no es obligatorio y está vacío, es válido
  }

  // Validar que sea una fecha válida
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

  // ⬅️ VALIDAR QUE SOLO SEA HOY O AYER (presente o pasado inmediato)
  const fechaHoy = new Date();
  fechaHoy.setHours(23, 59, 59, 999); // Final del día de hoy (presente)
  
  const fechaAyer = new Date();
  fechaAyer.setDate(fechaAyer.getDate() - 1); // Ayer (pasado inmediato)
  fechaAyer.setHours(0, 0, 0, 0); // Inicio del día de ayer

  // Verificar que esté entre ayer y hoy (presente o pasado inmediato)
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

  // Si pasa todas las validaciones, limpiar errores
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.classList.add("hidden");
  }
  input.classList.remove("border-red-500", "focus:ring-red-500");
  input.classList.add("border-green-300", "focus:ring-green-400");
  return true;
}
// FUNCIÓN PARA FECHAS DE NACIMIENTO (solo pasado, NO futuro)
export function validarFechaNacimiento(input, mensajes) {
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
    return true; // Si no es obligatorio y está vacío, es válido
  }

  // Validar que sea una fecha válida
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

  // ⬅️ NO PERMITIR FECHAS FUTURAS (solo hoy o pasado)
  const fechaHoy = new Date();
  fechaHoy.setHours(23, 59, 59, 999); // Permitir hasta el final del día de hoy

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

  // ⬅️ VALIDAR EDAD MÁXIMA RAZONABLE (120 años)
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
        // ⬅️ NUEVO TIPO PARA FECHAS DE NACIMIENTO
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
        // Nueva validación para campos numéricos
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
        // Validación normal para otros tipos
        input.addEventListener("input", () => {
          if (input.offsetParent !== null) {
            validarCampo(input, campo.regex, campo.mensajes);
          }
        });
        input.addEventListener("blur", () => {
          if (input.offsetParent !== null) {
            validarCampo(input, campo.regex, campo.mensajes);
          }
        });
      }
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
// En validaciones.js - actualizar registrarEntidad
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

  // Validar campos vacíos
  if (!validarCamposVacios(campos, formId)) {
    return Promise.reject("Validación de campos vacíos falló");
  }

  // Validar formatos específicos
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
      // ⬅️ FECHA NORMAL
      if (inputElement.value.trim() !== "" || campo.mensajes?.vacio) {
        esValido = validarFecha(inputElement, campo.mensajes);
      }
    } else if (campo.tipo === "fechaNacimiento") {
      // ⬅️ FECHA DE NACIMIENTO
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

  // Preparar datos
  const formData = new FormData(form);
  const dataParaEnviar = {};
  
  // Aplicar mapeo de nombres si existe
  for (let [key, value] of formData.entries()) {
    const nombreFinal = mapeoNombres[key] || key;
    dataParaEnviar[nombreFinal] = value || "";
  }

  // Enviar datos
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
export { 
  expresiones, 
  validarCampo, 
  validarCampoNumerico, 
  validarRango, 
  inicializarValidaciones, 
  validarFecha, 
  validarSelect 
};