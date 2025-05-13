import { reglasValidacion } from "./regex.js";


export function validarCampo(input) {
  const expresion = reglasValidacion[input.name];
  const errorSpan = input.nextElementSibling; // Asegúrate de tener un span después del input
  if (input.value.trim() === "") {
    input.classList.add("is-invalid");
    errorSpan.textContent = "Este campo es obligatorio";
  } else if (expresion && !expresion.test(input.value.trim())) {
    input.classList.add("is-invalid");
    errorSpan.textContent = "Formato inválido";
  } else {
    input.classList.remove("is-invalid");
    input.classList.add("is-valid");
    errorSpan.textContent = "";
  }
}

export function validarFormularioCompleto() {
  let formularioValido = true;
  inputs.forEach((input) => {
    if (!input.classList.contains("is-valid")) {
      formularioValido = false;
    }
  });
  document.getElementById("registrarClienteBtn").disabled = !formularioValido;
}