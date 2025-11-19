import { expresiones, validarCamposVacios ,inicializarValidaciones} from "./validaciones.js";


const camposLogin = [
  {
    id: "txtEmail",
    tipo: "input",
    regex: expresiones.email,
    mensajes: {
      vacio: "El correo es obligatorio.",
      formato: "Ingrese un correo válido.",
    },
  },
  {
    id: "txtPass",
    tipo: "input",
    mensajes: {
      vacio: "La contraseña es obligatoria.",
    },
  },
];
inicializarValidaciones(camposLogin, "formLogin");

document.getElementById('formLogin').addEventListener('submit', function(e) {
    e.preventDefault();

    
    if (!validarCamposVacios(camposLogin, "formLogin")) return;

    let form = e.target;
    let formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())    .then(data => {
        if(data.status){
        
            Swal.fire({
                title: "¡Bienvenido!",
                text: "Inicio de sesión exitoso",
                icon: "success",
                timer: 1500,
                showConfirmButton: false,
                allowOutsideClick: false
            }).then(() => {
                window.location.href = base_url + "/dashboard";
            });
        } else {
         
            let icon = "error";
            let title = "Error de autenticación";
            
            if (data.msg.includes("no está registrado") || data.msg.includes("no existe")) {
                icon = "warning";
                title = "Correo no encontrado";
            } else if (data.msg.includes("contraseña") && data.msg.includes("incorrecta")) {
                icon = "error";
                title = "Contraseña incorrecta";
            } else if (data.msg.includes("inactiva")) {
                icon = "info";
                title = "Cuenta inactiva";
            } else if (data.msg.includes("robot")) {
                icon = "warning";
                title = "Verificación requerida";
            }
            
            Swal.fire({
                title: title,
                text: data.msg,
                icon: icon,
                confirmButtonText: "Entendido",
                confirmButtonColor: "#3b82f6"
            }).then(() => {
                // Recargar la página después de cerrar el mensaje de error
                window.location.reload();
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: "Error de conexión",
            text: "No se pudo conectar con el servidor. Por favor, intente nuevamente.",
            icon: "error",
            confirmButtonText: "Reintentar",
            confirmButtonColor: "#3b82f6"
        });
    });
});