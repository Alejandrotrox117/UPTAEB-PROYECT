module.exports = {
  corePlugins: {
    preflight: false, // Desactiva el reset de estilos base
  },
  content: ["./app/views/**/*.php"], // Escanea tus vistas PHP
  theme: {
    extend: {},
  },
  plugins: [],
};