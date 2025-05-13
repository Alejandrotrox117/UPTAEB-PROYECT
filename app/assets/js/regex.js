export const reglasValidacion = {
  cedula: /^[0-9]{7,12}$/, // Cédula: entre 7 y 10 dígitos
  nombre: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, // Nombre: solo letras y espacios
  apellido: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, // Apellido: solo letras y espacios
  telefono_principal: /^[0-9]{10}$/, // Teléfono: exactamente 10 dígitos
  direccion: /^.{5,100}$/, // Dirección: entre 5 y 100 caracteres
  estatus: /^(Activo|Inactivo)$/, // Estatus: solo "Activo" o "Inactivo"
  observaciones: /^.{0,200}$/, // Observaciones: máximo 200 caracteres
};