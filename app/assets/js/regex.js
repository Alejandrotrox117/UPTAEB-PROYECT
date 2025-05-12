// filepath: c:\xampp\htdocs\project\app\assets\js\regex.js

// Expresiones regulares comunes
const REGEX = {
  cedula: /^[0-9]{7,10}$/, // Solo números, entre 7 y 10 dígitos
  nombre: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, // Letras y espacios, entre 2 y 50 caracteres
  telefono: /^[0-9]{10}$/, // Solo números, exactamente 10 dígitos
  direccion: /^.{5,100}$/, // Cualquier carácter, entre 5 y 100 caracteres
  correo: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/, // Correo electrónico válido
  observaciones: /^.{0,200}$/, // Opcional, hasta 200 caracteres
};

// Exportar las expresiones regulares
export default REGEX;