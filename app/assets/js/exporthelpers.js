export function abrirModal(modalId) {
  const modal = document.getElementById(modalId);
  
  if (!modal) {
    console.error(`abrirModal - Modal no encontrado: ${modalId}`);
    return false;
  }
  
  console.log(`abrirModal - Modal encontrado: ${modalId}`);
  modal.classList.remove("opacity-0", "pointer-events-none");
  return true;
}

export function cerrarModal(modalId) {
  const modal = document.getElementById(modalId);
  
  if (!modal) {
    console.error(`cerrarModal - Modal no encontrado: ${modalId}`);
    return false;
  }
  
  modal.classList.add("opacity-0", "pointer-events-none");
  const form = modal.querySelector("form");
  if (form) form.reset();
  return true;
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