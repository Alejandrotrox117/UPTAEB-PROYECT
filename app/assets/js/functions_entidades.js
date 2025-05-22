import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarDetalleVenta,
  validarSelect,
  validarFecha,
  limpiarValidaciones,
} from "./validaciones.js";


//REGISTRAR
export function registrarEntidad({ formId, endpoint, campos, onSuccess, onError }) {
  let formularioValido = true;

  const form = document.getElementById(formId);

  campos.forEach((campo) => {
    let input = form ? form.querySelector(`#${campo.id}`) : null;
    if (input) {
      let esValido = false;
      if (campo.tipo === "date") {
        esValido = validarFecha(input, campo.mensajes);
      } else if (campo.tipo === "select") {
        esValido = validarSelect(input, campo.mensajes); // <-- pásale el elemento, no el id
      } else {
        esValido = validarCamposVacios([campo], formId);
      }
      if (!esValido) formularioValido = false;
    }
  });

  if (!formularioValido) return;

  const formData = new FormData(form);
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