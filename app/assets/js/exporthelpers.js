export function abrirModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.remove("opacity-0", "pointer-events-none");
}

export function cerrarModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.add("opacity-0", "pointer-events-none");
  const form = modal.querySelector("form");
  if (form) form.reset();
}

export function asignarValoresFormulario(campos, valores) {
  campos.forEach((campo) => {
    const input = document.getElementById(campo);
    if (input) input.value = valores[campo] || "";
  });
}