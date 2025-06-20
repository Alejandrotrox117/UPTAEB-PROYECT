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
    .then(res => res.json())
    .then(data => {
        if(data.status){
            window.location.href =base_url+"/dashboard";
        }else{
           Swal.fire({
            title: "¡Error!",
            text: data.msg ,
            icon: "error",
            confirmButtonText: "Aceptar",
          }); 
        }
    });
});