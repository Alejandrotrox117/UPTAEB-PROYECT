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


export function obtenerPermisosUsuario() {
  const permisosDiv = document.getElementById("permisosUsuario");
  let permisos = {};
  if (permisosDiv) {
    try {
      permisos = JSON.parse(permisosDiv.dataset.permisos);
    } catch (e) {
      permisos = {};
    }
  }
  return permisos;
}