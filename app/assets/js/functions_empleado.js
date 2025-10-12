import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";

document.addEventListener("DOMContentLoaded", function () {
  
  // ========================================
  // MANEJO DE FORMULARIO DINÁMICO
  // ========================================
  
  /**
   * Controla la visualización de campos según el tipo de empleado
   */
  function actualizarCamposFormulario() {
    const tipoOperario = document.getElementById('tipo_operario');
    const tipoAdministrativo = document.getElementById('tipo_administrativo');
    const camposOperario = document.getElementById('campos_operario');
    const camposAdministrativo = document.getElementById('campos_administrativo');
    
    if (tipoOperario.checked) {
      // OPERARIO: Mostrar solo campos básicos
      camposOperario.classList.remove('hidden');
      camposAdministrativo.classList.add('hidden');
      
      // Limpiar campos administrativos
      limpiarCamposAdministrativos();
      
      // Los campos de operario NO son obligatorios (solo nombre, apellido, CI)
      deshabilitarValidacionesAdministrativas();
      
    } else if (tipoAdministrativo.checked) {
      // ADMINISTRATIVO: Mostrar todos los campos
      camposOperario.classList.add('hidden');
      camposAdministrativo.classList.remove('hidden');
      
      // Limpiar campos de operario
      limpiarCamposOperario();
      
      // Activar validaciones para administrativos
      habilitarValidacionesAdministrativas();
    }
  }
  
  /**
   * Limpia los campos específicos de operarios
   */
  function limpiarCamposOperario() {
    const puestoOperario = document.getElementById('puesto_operario');
    const fechaInicioOperario = document.getElementById('fecha_inicio_operario');
    
    if (puestoOperario) puestoOperario.value = '';
    if (fechaInicioOperario) fechaInicioOperario.value = '';
  }
  
  /**
   * Limpia los campos específicos de administrativos
   */
  function limpiarCamposAdministrativos() {
    const camposAdmin = [
      'genero', 'fecha_nacimiento', 'telefono_principal',
      'correo_electronico', 'direccion', 'puesto_administrativo',
      'salario', 'fecha_inicio_admin', 'fecha_fin'
    ];
    
    camposAdmin.forEach(id => {
      const campo = document.getElementById(id);
      if (campo) campo.value = '';
    });
  }
  
  /**
   * Deshabilita validaciones para campos administrativos cuando es operario
   */
  function deshabilitarValidacionesAdministrativas() {
    const camposAdmin = [
      'genero', 'fecha_nacimiento', 'telefono_principal',
      'correo_electronico', 'direccion', 'puesto_administrativo',
      'salario', 'fecha_inicio_admin', 'fecha_fin'
    ];
    
    camposAdmin.forEach(id => {
      const campo = document.getElementById(id);
      if (campo) {
        campo.removeAttribute('required');
      }
    });
  }
  
  /**
   * Habilita validaciones para campos administrativos
   */
  function habilitarValidacionesAdministrativas() {
    const camposObligatorios = ['puesto_administrativo'];
    
    camposObligatorios.forEach(id => {
      const campo = document.getElementById(id);
      if (campo) {
        campo.setAttribute('required', 'required');
      }
    });
  }
  
  /**
   * Prepara los datos del formulario según el tipo de empleado
   */
  function prepararDatosFormulario() {
    const tipoEmpleado = document.querySelector('input[name="tipo_empleado"]:checked').value;
    const formData = new FormData(document.getElementById('empleadoForm'));
    
    // Agregar el tipo de empleado
    formData.append('tipo_empleado', tipoEmpleado);
    
    // Si es operario, obtener puesto del select de operario
    if (tipoEmpleado === 'OPERARIO') {
      const puestoOperario = document.getElementById('puesto_operario').value;
      formData.set('puesto', puestoOperario || 'Operario General');
      
      const fechaInicioOperario = document.getElementById('fecha_inicio_operario').value;
      if (fechaInicioOperario) {
        formData.set('fecha_inicio', fechaInicioOperario);
      }
      
      // Campos que no aplican para operarios, enviar como NULL o vacío
      formData.set('salario', '0.00');
      formData.set('genero', '');
      formData.set('fecha_nacimiento', '');
      formData.set('correo_electronico', '');
      formData.set('telefono_principal', '');
      formData.set('direccion', '');
      formData.set('fecha_fin', '');
    } else {
      // ADMINISTRATIVO: Obtener puesto del input de administrativo
      const puestoAdmin = document.getElementById('puesto_administrativo').value;
      formData.set('puesto', puestoAdmin);
      
      const fechaInicioAdmin = document.getElementById('fecha_inicio_admin').value;
      if (fechaInicioAdmin) {
        formData.set('fecha_inicio', fechaInicioAdmin);
      }
    }
    
    return formData;
  }
  
  /**
   * Limpia todo el formulario y resetea al estado inicial
   */
  function limpiarFormulario() {
    document.getElementById('empleadoForm').reset();
    document.getElementById('idempleado').value = '';
    document.getElementById('tipo_operario').checked = true;
    actualizarCamposFormulario();
    
    // Limpiar mensajes de error
    const mensajesError = document.querySelectorAll('small[id^="error-"]');
    mensajesError.forEach(msg => msg.classList.add('hidden'));
  }
  
  // Event Listeners para cambio de tipo de empleado
  const radioTipos = document.querySelectorAll('input[name="tipo_empleado"]');
  radioTipos.forEach(radio => {
    radio.addEventListener('change', function() {
      console.log('Cambio de tipo:', this.value);
      actualizarCamposFormulario();
    });
  });
  
  // Inicializar el estado del formulario al cargar
  setTimeout(() => {
    actualizarCamposFormulario();
  }, 100);
  
  // ========================================
  // FIN MANEJO DE FORMULARIO DINÁMICO
  // ========================================
   
  const campos = [
    { 
      id: "nombre", 
      regex: expresiones.nombre, 
      mensajes: {
        vacio: "El nombre es obligatorio",
        formato: "El nombre debe tener entre 2 y 50 caracteres alfabéticos"
      }
    },
    { 
      id: "apellido", 
      regex: expresiones.apellido, 
      mensajes: {
        vacio: "El apellido es obligatorio",
        formato: "El apellido debe tener entre 2 y 20 caracteres alfabéticos"
      }
    },
    { 
      id: "telefono_principal", 
      regex: expresiones.telefono_principal, 
      mensajes: {
        formato: "El teléfono debe comenzar con código de operadora venezolana (0414, 0424, etc.)"
      },
      opcional: true 
    },
    { 
      id: "direccion", 
      regex: expresiones.direccion, 
      mensajes: {
        formato: "La dirección debe tener entre 5 y 100 caracteres"
      },
      opcional: true 
    },
    { 
      id: "correo_electronico", 
      regex: expresiones.email, 
      mensajes: {
        formato: "Ingrese un correo electrónico válido"
      },
      opcional: true 
    },
    { 
      id: "puesto_administrativo", 
      regex: expresiones.textoGeneral, 
      mensajes: {
        vacio: "El puesto es obligatorio para administrativos",
        formato: "El puesto debe tener entre 2 y 100 caracteres"
      },
      opcional: true 
    }
   ];

  inicializarValidaciones(campos);
  $("#TablaEmpleado").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "empleados/getEmpleadoData", 
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idempleado", title: "Nro" },
      { data: "nombre", title: "Nombre" },
      { data: "apellido", title: "Apellido" },
      { data: "identificacion", title: "Identificación" },
      { data: "telefono_principal", title: "Teléfono" },
      { data: "correo_electronico", title: "Correo Electrónico" },
      { data: "direccion", title: "Dirección" },
      { data: "fecha_nacimiento", title: "Fecha de Nacimiento" },
      { data: "genero", title: "Género" },
      { data: "puesto", title: "Puesto" },
      { data: "salario", title: "Salario" },
      { data: "estatus", title: "Estatus" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idempleado="${row.idempleado}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idempleado="${row.idempleado}">
                  <i class="fas fa-trash"></i>
                </button>
              `;
        },
      },
    ],
    language: {
      decimal: "",
      emptyTable: "No hay información",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
      infoEmpty: "Mostrando 0 to 0 of 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
      infoPostFix: "",
      thousands: ",",
      lengthMenu: "Mostrar _MENU_ Entradas",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "Sin resultados encontrados",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior",
      },
    },
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "asc"]],
  });







  
  // ========================================
  // FUNCIÓN DE REGISTRO/ACTUALIZACIÓN
  // ========================================
  
  function manejarRegistro(campos) {
    
    // Validar solo los campos VISIBLES según el tipo de empleado
    const tipoEmpleado = document.querySelector('input[name="tipo_empleado"]:checked').value;
    
    // Validar campos básicos (obligatorios para todos)
    const nombre = document.getElementById('nombre').value.trim();
    const apellido = document.getElementById('apellido').value.trim();
    const identificacion = document.getElementById('identificacion').value.trim();
    
    if (!nombre || nombre.length < 2) {
      Swal.fire({
        title: "¡Error!",
        text: "El nombre es obligatorio y debe tener al menos 2 caracteres.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      return;
    }
    
    if (!apellido || apellido.length < 2) {
      Swal.fire({
        title: "¡Error!",
        text: "El apellido es obligatorio y debe tener al menos 2 caracteres.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      return;
    }
    
    if (!identificacion || !/^\d{7,10}$/.test(identificacion)) {
      Swal.fire({
        title: "¡Error!",
        text: "La cédula debe tener entre 7 y 10 dígitos.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      return;
    }
    
    // Validaciones específicas por tipo
    if (tipoEmpleado === 'ADMINISTRATIVO') {
      const puestoAdmin = document.getElementById('puesto_administrativo').value.trim();
      if (!puestoAdmin || puestoAdmin.length < 3) {
        Swal.fire({
          title: "¡Error!",
          text: "Para empleados administrativos, el puesto es obligatorio (mínimo 3 caracteres).",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }
    }

    // Preparar datos según el tipo de empleado
    const formData = prepararDatosFormulario();
    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });
    
    const idempleado = document.getElementById("idempleado").value;
    const url = idempleado
          ? "empleados/updateEmpleado"
          : "empleados/createEmpleado";
    const method = idempleado ? "PUT" : "POST";

    fetch(url, {
      method: method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
      .then((response) => {
        console.log("Respuesta HTTP:", response.status, response.statusText);
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.text(); // Obtener como texto primero
      })
      .then((text) => {
        console.log("Respuesta RAW del servidor:", text);
        
        // Intentar parsear como JSON
        let result;
        try {
          result = JSON.parse(text);
        } catch (e) {
          console.error("Error al parsear JSON:", e);
          console.error("Texto recibido:", text.substring(0, 500));
          throw new Error("El servidor devolvió una respuesta inválida. Revisa la consola para más detalles.");
        }
        
        console.log("Datos parseados:", result);
        
        if (result.status) {
          Swal.fire({
            title: "¡Éxito!",
            text: result.message || "Empleado guardado correctamente.",
            icon: "success",
            confirmButtonText: "Aceptar",
          }).then(() => {
            $("#TablaEmpleado").DataTable().ajax.reload();
            cerrarModal("empleadoModal");
            limpiarFormulario();
          });
        } else {
          Swal.fire({
            title: "¡Error!",
            text: result.message || "No se pudo guardar el empleado.",
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        }
      })
      .catch((error) => {
        console.error("Error capturado:", error);
        Swal.fire({
          title: "¡Error!",
          text: error.message || "Ocurrió un error al procesar la solicitud.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      });
  }
  
  // ========================================
  // EVENT LISTENERS
  // ========================================

  document
    .getElementById("registrarEmpleadoBtn")
    .addEventListener("click", function () {
      manejarRegistro(campos);
    });
  
  document
    .getElementById("abrirModalBtn")
    .addEventListener("click", function () {
      abrirModal("empleadoModal");
    });

  
  document
    .getElementById("cerrarModalBtn")
    .addEventListener("click", function () {
      cerrarModal("empleadoModal");
    });
  
  document
    .getElementById("empleadoForm")
    .addEventListener("submit", function (e) {
      e.preventDefault(); 

      
      const formData = new FormData(this);
      const data = {};
      formData.forEach((value, key) => {
        data[key] = value;
      });

      console.log("Datos a enviar:", data); 

      
      if (!data.nombre || !data.apellido || !data.identificacion) {
        alert("Por favor, completa todos los campos obligatorios.");
        return;
      }

      
      const idempleado = document.getElementById("idempleado").value;
      const url = idempleado
        ? "empleados/updateEmpleado"
        : "empleados/createEmpleado";
      const method = idempleado ? "PUT" : "POST";

      fetch(url, {
        method: method,
        headers: { "Content-Type": "application/json" }, 
        body: JSON.stringify(data), 
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
          }
          return response.json();
        })
        .then((result) => {
          if (result.status) {
            alert(result.message);
            cerrarModalEmpleado();
            $("#TablaEmpleado").DataTable().ajax.reload(); 
          } else {
            alert(result.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Ocurrió un error al procesar la solicitud.");
        });
    });

  
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idempleado = e.target
        .closest(".editar-btn")
        .getAttribute("data-idempleado");
      console.log("Botón de edición clicado. ID de empleado:", idempleado); 

      if (!idempleado || isNaN(idempleado)) {
        alert("ID de empleado no válido.");
        return;
      }

      abrirModalEmpleadoParaEdicion(idempleado);
    }
  });

  
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idempleado = e.target
        .closest(".eliminar-btn")
        .getAttribute("data-idempleado");
      if (confirm("¿Estás seguro de desactivar este empleado?")) {
        eliminarEmpleado(idempleado);
      }
    }
  });
});


function eliminarEmpleado(idempleado) {
  fetch(`empleados/deleteEmpleado`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idempleado }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); 
        $("#TablaEmpleado").DataTable().ajax.reload(); 
      } else {
        alert(result.message); 
      }
    })
    .catch((error) => console.error("Error:", error));
}


function abrirModalEmpleadoParaEdicion(idempleado) {
  console.log("ID de empleado recibido:", idempleado); 

  fetch(`empleados/getEmpleadoById/${idempleado}`)
    .then((response) => {
      console.log("Respuesta HTTP:", response); 
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.text(); // Cambiar a text() primero para ver qué llega
    })
    .then((text) => {
      console.log("Respuesta RAW del servidor:", text); // Ver qué está devolviendo
      
      // Intentar parsear como JSON
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("Error al parsear JSON:", e);
        console.error("Texto recibido:", text.substring(0, 500)); // Mostrar primeros 500 caracteres
        throw new Error("El servidor devolvió una respuesta inválida (HTML en lugar de JSON). Revisa el backend.");
      }
      
      console.log("Datos parseados:", data); 

      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const empleado = data.data;
      
      // Asignar campos básicos
      document.getElementById("idempleado").value = empleado.idempleado || "";
      document.getElementById("nombre").value = empleado.nombre || "";
      document.getElementById("apellido").value = empleado.apellido || "";
      document.getElementById("identificacion").value = empleado.identificacion || "";
      
      // Seleccionar el tipo de empleado y actualizar campos
      const tipoEmpleado = empleado.tipo_empleado || 'OPERARIO';
      if (tipoEmpleado === 'OPERARIO') {
        document.getElementById('tipo_operario').checked = true;
      } else {
        document.getElementById('tipo_administrativo').checked = true;
      }
      
      // Trigger del evento change para actualizar visibilidad de campos
      const event = new Event('change');
      document.querySelector('input[name="tipo_empleado"]:checked').dispatchEvent(event);
      
      // Esperar a que se actualicen los campos visibles
      setTimeout(() => {
        if (tipoEmpleado === 'OPERARIO') {
          // Campos de operario
          const puestoOperario = document.getElementById("puesto_operario");
          if (puestoOperario) puestoOperario.value = empleado.puesto || "";
          
          const fechaInicioOperario = document.getElementById("fecha_inicio_operario");
          if (fechaInicioOperario) fechaInicioOperario.value = empleado.fecha_inicio || "";
          
        } else {
          // Campos de administrativo
          const genero = document.getElementById("genero");
          if (genero) genero.value = empleado.genero || "";
          
          const fechaNacimiento = document.getElementById("fecha_nacimiento");
          if (fechaNacimiento) fechaNacimiento.value = empleado.fecha_nacimiento || "";
          
          const telefonoPrincipal = document.getElementById("telefono_principal");
          if (telefonoPrincipal) telefonoPrincipal.value = empleado.telefono_principal || "";
          
          const correo = document.getElementById("correo_electronico");
          if (correo) correo.value = empleado.correo_electronico || "";
          
          const direccion = document.getElementById("direccion");
          if (direccion) direccion.value = empleado.direccion || "";
          
          const puestoAdmin = document.getElementById("puesto_administrativo");
          if (puestoAdmin) puestoAdmin.value = empleado.puesto || "";
          
          const salario = document.getElementById("salario");
          if (salario) salario.value = empleado.salario || "";
          
          const fechaInicioAdmin = document.getElementById("fecha_inicio_admin");
          if (fechaInicioAdmin) fechaInicioAdmin.value = empleado.fecha_inicio || "";
          
          const fechaFin = document.getElementById("fecha_fin");
          if (fechaFin) fechaFin.value = empleado.fecha_fin || "";
        }
        
        // Campo estatus (común para ambos)
        const estatus = document.getElementById("estatus");
        if (estatus) estatus.value = empleado.estatus || "activo";
        
      }, 200); // Esperar a que los campos sean visibles
      
      // Abrir modal
      abrirModalEmpleado();
    })
    .catch((error) => {
      console.error("Error capturado:", error); 
      Swal.fire({
        title: "¡Error!",
        text: error.message || "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}


function abrirModalEmpleado() {
  const modal = document.getElementById("empleadoModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}


function cerrarModalEmpleado() {
  const modal = document.getElementById("empleadoModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("empleadoForm").reset();
}


function validarCamposVacios(campos) {
  let formularioValido = true; 

  
  for (let campo of campos) {
    
    if (campo.id === "idempleado") {
      continue;
    }

    
    const input = document.getElementById(campo.id);
    if (!input) {
      console.warn(`El campo con ID "${campo.id}" no existe en el DOM.`);
      continue;
    }

    let valor = input.value.trim();
    if (valor === "") {
      Swal.fire({
        title: "¡Error!",
        text: `El campo "${campo.id}" no puede estar vacío.`,
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      formularioValido = false; 
    }
  }

  return formularioValido; 
}

