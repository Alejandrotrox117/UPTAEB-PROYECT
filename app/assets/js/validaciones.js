// Expresiones regulares para validación
const expresiones = {
  cedula: /^(V|E|J)-\d{8}$/, // Formato de cédula
  nombre: /^[a-zA-Z\s]{2,20}$/, // Nombre
  apellido: /^[a-zA-Z\s]{2,50}$/, // Apellido
  telefono_principal: /^\d{10}$/, // Teléfono
  direccion: /^.{5,100}$/, // Dirección
  estatus: /^(Activo|Inactivo)$/, // Estatus
  observaciones: /^.{0,200}$/, // Observaciones
};

// Función para validar un campo en tiempo real
const validarCampo = (input, regex, mensaje) => {
  const errorDiv = input.nextElementSibling;

  if (!regex.test(input.value.trim())) {
    // Agregar clases de error
    input.classList.add("border-red-500", "focus:ring-red-500");
    input.classList.remove("border-gray-300", "focus:ring-green-400");

    // Mostrar mensaje de error
    if (errorDiv) {
      errorDiv.textContent = mensaje;
      errorDiv.classList.remove("hidden");
    }
    return false;
  } else {
    // Quitar clases de error y restaurar las clases predeterminadas
    input.classList.remove("border-red-500", "focus:ring-red-500");
    input.classList.add("border-gray-300", "focus:ring-green-400");

    // Ocultar mensaje de error
    if (errorDiv) {
      errorDiv.textContent = "";
      errorDiv.classList.add("hidden");
    }
    return true;
  }
};

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