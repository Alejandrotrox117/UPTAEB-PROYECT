// Expresiones regulares para validación
const expresiones = {
  cedula: /^(V|E|J)-\d{8}$/, // Formato de cédula
  nombre: /^[a-zA-Z\s]{2,20}$/, // Nombre
  apellido: /^[a-zA-Z\s]{2,50}$/, // Apellido
  telefono_principal: /^\d{11}$/, // Teléfono
  direccion: /^.{5,100}$/, // Dirección
  estatus: /^(Activo|Inactivo)$/, // Estatus
  observaciones: /^.{0,200}$/, // Observaciones
};

function validarCampo(input, regex, mensaje) {
  const errorDiv = input.nextElementSibling; // Div donde se muestra el mensaje de error

  if (!regex.test(input.value.trim())) {
    if (errorDiv) {
      errorDiv.textContent = mensaje;
      errorDiv.classList.remove("hidden");
    }
    input.classList.add("focus:invalid:border-red-500", "focus:ring-red-700");
    input.classList.remove("border-gray-300", "focus:ring-green-400");
    return false;
  } else {
    if (errorDiv) {
      errorDiv.textContent = "";
      errorDiv.classList.add("hidden");
    }
    input.classList.remove("border-red-500", "focus:ring-red-500");
    input.classList.add("border-green-300", "focus:ring-green-400");
    return true;
  }
}

// Función para inicializar las validaciones en tiempo real
const inicializarValidaciones = (campos) => {
  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      input.addEventListener("input", () => {
        validarCampo(input, campo.regex, campo.mensaje);
      });
    }
  });
};

// Exportar las funciones y expresiones
export { expresiones, validarCampo, inicializarValidaciones };