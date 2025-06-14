import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
  validarCamposVacios,
  validarFecha,
  validarSelect
} from "./validaciones.js";

let tablaPagos;
let tiposPago = [];

// Configuración de campos para validación
const camposFormularioPago = [
  {
    id: "tipoPago",
    tipo: "radio",
    mensajes: {
      vacio: "Debe seleccionar un tipo de pago.",
    },
  },
  {
    id: "pagoMonto",
    tipo: "input",
    regex: /^\d+(\.\d{1,2})?$/, // Números decimales con hasta 2 decimales
    mensajes: {
      vacio: "El monto es obligatorio.",
      formato: "El monto debe ser un número válido con hasta 2 decimales.",
    },
  },
  {
    id: "pagoMetodoPago",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un método de pago.",
    },
  },
  {
    id: "pagoFecha",
    tipo: "fecha",
    mensajes: {
      vacio: "La fecha de pago es obligatoria.",
      fechaPosterior: "La fecha no puede ser posterior a hoy.",
      fechaInvalida: "Formato de fecha inválido.",
    },
  },
  {
    id: "pagoReferencia",
    tipo: "input",
    regex: expresiones.textoGeneral, // Texto general opcional
    mensajes: {
      formato: "La referencia debe tener entre 2 y 100 caracteres.",
    },
  },
  {
    id: "pagoObservaciones",
    tipo: "textarea",
    regex: /^.{0,200}$/, // Observaciones opcionales hasta 200 caracteres
    mensajes: {
      formato: "Las observaciones no pueden exceder 200 caracteres.",
    },
  }
];

// Campos dinámicos según el tipo de pago
const camposDinamicos = {
  compra: [
    {
      id: "pagoCompra",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar una compra.",
      },
    }
  ],
  venta: [
    {
      id: "pagoVenta",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar una venta.",
      },
    }
  ],
  sueldo: [
    {
      id: "pagoSueldo",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un sueldo.",
      },
    }
  ],
  otro: [
    {
      id: "pagoDescripcion",
      tipo: "textarea",
      regex: expresiones.textoGeneral,
      mensajes: {
        vacio: "La descripción es obligatoria para otros pagos.",
        formato: "La descripción debe tener entre 2 y 100 caracteres.",
      },
    }
  ]
};

document.addEventListener("DOMContentLoaded", function () {
  inicializarModulo();
});

function inicializarModulo() {
  inicializarTablaPagos();
  configurarEventos();
  cargarTiposPago();
  // Inicializar validaciones básicas
  inicializarValidaciones(camposFormularioPago, "formRegistrarPago");
}

function inicializarTablaPagos() {
  if ($.fn.DataTable.isDataTable('#TablaPagos')) {
    $('#TablaPagos').DataTable().destroy();
  }

  tablaPagos = $('#TablaPagos').DataTable({
    ajax: {
      url: 'Pagos/getPagosData',
      type: 'GET',
      dataSrc: function(json) {
        if (json.status === true && Array.isArray(json.data)) {
          return json.data;
        }
        console.error('Error en la respuesta del servidor:', json);
        mostrarNotificacion('Error al cargar los datos', 'error');
        return [];
      },
      error: function(xhr, error, thrown) {
        console.error('Error en la petición AJAX:', error);
        mostrarNotificacion('Error al cargar los datos', 'error');
      }
    },
    columns: [
      { 
        data: null,
        title: '#',
        render: function(data, type, row, meta) {
          return meta.row + 1;
        },
        orderable: false,
        searchable: false,
        width: '50px'
      },
      { 
        data: 'tipo_pago_texto',
        title: 'Tipo',
        render: function(data) {
          const badges = {
            'Compra': '<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Compra</span>',
            'Venta': '<span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Venta</span>',
            'Sueldo': '<span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">Sueldo</span>',
            'Otro': '<span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Otro</span>'
          };
          return badges[data] || data;
        }
      },
      { 
        data: 'destinatario',
        title: 'Destinatario'
      },
      { 
        data: 'monto',
        title: 'Monto',
        render: function(data) {
          return `<span class="font-semibold text-green-600">$${parseFloat(data).toFixed(2)}</span>`;
        }
      },
      { 
        data: 'metodo_pago',
        title: 'Método'
      },
      { 
        data: 'fecha_pago_formato',
        title: 'Fecha'
      },
      { 
        data: 'estatus',
        title: 'Estatus',
        render: function(data) {
          return data === 'activo' ? 
            '<span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">ACTIVO</span>' :
            '<span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">INACTIVO</span>';
        }
      },
      {
        data: null,
        title: 'Acciones',
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
          return `
            <div class="flex space-x-2">
              <button onclick="verPago(${row.idpago})" 
                      class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs transition-colors"
                      title="Ver detalles">
                <i class="fas fa-eye"></i>
              </button>
              ${row.estatus === 'activo' ? `
                <button onclick="eliminarPago(${row.idpago}, '${row.destinatario}')" 
                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs transition-colors"
                        title="Desactivar">
                  <i class="fas fa-ban"></i>
                </button>
              ` : ''}
            </div>
          `;
        },
        width: '120px'
      }
    ],
    responsive: true,
    language: {
      processing: "Procesando...",
      lengthMenu: "Mostrar _MENU_ registros",
      zeroRecords: "No se encontraron resultados",
      emptyTable: "Ningún dato disponible en esta tabla",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
      infoFiltered: "(filtrado de un total de _MAX_ registros)",
      search: "Buscar:",
      loadingRecords: "Cargando...",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      },
      aria: {
        sortAscending: ": Activar para ordenar la columna de manera ascendente",
        sortDescending: ": Activar para ordenar la columna de manera descendente"
      }
    },
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
    order: [[5, 'desc']],
    dom: 'Bfrtip',
    buttons: [
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel"></i> Excel',
        className: 'btn-excel',
        title: 'Reporte_Pagos'
      },
      {
        extend: 'pdfHtml5',
        text: '<i class="fas fa-file-pdf"></i> PDF',
        className: 'btn-pdf',
        title: 'Reporte de Pagos',
        orientation: 'landscape'
      }
    ]
  });
}

function configurarEventos() {
  // Modal eventos
  const btnAbrirModal = document.getElementById("btnAbrirModalRegistrarPago");
  const btnCerrarModal = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModal = document.getElementById("btnCancelarModalRegistrar");
  const formRegistrar = document.getElementById("formRegistrarPago");

  if (btnAbrirModal) {
    btnAbrirModal.addEventListener("click", abrirModalRegistro);
  }

  if (btnCerrarModal) {
    btnCerrarModal.addEventListener("click", () => {
      limpiarValidaciones([...camposFormularioPago, ...obtenerCamposDinamicos()], "formRegistrarPago");
      cerrarModal("modalRegistrarPago");
    });
  }

  if (btnCancelarModal) {
    btnCancelarModal.addEventListener("click", () => {
      limpiarValidaciones([...camposFormularioPago, ...obtenerCamposDinamicos()], "formRegistrarPago");
      cerrarModal("modalRegistrarPago");
    });
  }

  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function(e) {
      e.preventDefault();
      registrarPago();
    });
  }

  // Eventos para cerrar modal de ver pago
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVerFooter = document.getElementById("btnCerrarModalVerFooter");
  
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", () => cerrarModal("modalVerPago"));
  }
  
  if (btnCerrarModalVerFooter) {
    btnCerrarModalVerFooter.addEventListener("click", () => cerrarModal("modalVerPago"));
  }

  // También permitir cerrar con clic fuera del modal
  const modalVerPago = document.getElementById("modalVerPago");
  if (modalVerPago) {
    modalVerPago.addEventListener("click", function(e) {
      if (e.target === this) {
        cerrarModal("modalVerPago");
      }
    });
  }
}

function obtenerCamposDinamicos() {
  const tipoPago = document.querySelector('input[name="tipoPago"]:checked')?.value;
  return tipoPago ? (camposDinamicos[tipoPago] || []) : [];
}

function abrirModalRegistro() {
  resetearFormulario();
  limpiarValidaciones([...camposFormularioPago, ...obtenerCamposDinamicos()], "formRegistrarPago");
  configurarEventosTipoPago();
  establecerFechaActual();
  cargarMetodosPago();
  abrirModal("modalRegistrarPago");
}

function cargarTiposPago() {
  fetch('Pagos/getTiposPago')
    .then(response => response.json())
    .then(result => {
      if (result.status && result.data) {
        tiposPago = result.data;
      }
    })
    .catch(error => {
      console.error('Error al cargar tipos de pago:', error);
    });
}

function cargarMetodosPago() {
  fetch('Pagos/getTiposPago')
    .then(response => response.json())
    .then(result => {
      const select = document.getElementById('pagoMetodoPago');
      if (!select) return;
      
      select.innerHTML = '<option value="">Seleccionar método...</option>';
      
      if (result.status && result.data) {
        result.data.forEach(tipo => {
          const option = document.createElement('option');
          option.value = tipo.idtipo_pago;
          option.textContent = tipo.nombre;
          select.appendChild(option);
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarNotificacion('Error al cargar métodos de pago', 'error');
    });
}

function resetearFormulario() {
  const form = document.getElementById("formRegistrarPago");
  if (form) form.reset();
  
  // Ocultar todos los containers
  ['containerCompras', 'containerVentas', 'containerSueldos', 'containerDescripcion', 'containerDestinatario'].forEach(id => {
    const element = document.getElementById(id);
    if (element) element.classList.add("hidden");
  });
  
  // Limpiar selects
  ['pagoCompra', 'pagoVenta', 'pagoSueldo'].forEach(id => {
    const element = document.getElementById(id);
    if (element) element.innerHTML = '<option value="">Seleccionar...</option>';
  });

  // Limpiar validaciones
  limpiarValidaciones([...camposFormularioPago, ...obtenerCamposDinamicos()], "formRegistrarPago");
}

function configurarEventosTipoPago() {
  const radioButtons = document.querySelectorAll('input[name="tipoPago"]');
  
  radioButtons.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.checked) {
        // Limpiar validaciones anteriores
        limpiarValidaciones([...camposFormularioPago, ...obtenerCamposDinamicos()], "formRegistrarPago");
        
        manejarCambioTipoPago(this.value);
        
        // Inicializar validaciones para los nuevos campos dinámicos
        const camposDinamicosActuales = obtenerCamposDinamicos();
        if (camposDinamicosActuales.length > 0) {
          inicializarValidaciones(camposDinamicosActuales, "formRegistrarPago");
        }
      }
    });
  });
}

function manejarCambioTipoPago(tipoPago) {
  // Ocultar todos los containers
  ['containerCompras', 'containerVentas', 'containerSueldos', 'containerDescripcion', 'containerDestinatario'].forEach(id => {
    const element = document.getElementById(id);
    if (element) element.classList.add('hidden');
  });
  
  // Limpiar monto
  const montoInput = document.getElementById("pagoMonto");
  if (montoInput) montoInput.value = "";
  
  switch(tipoPago) {
    case 'compra':
      mostrarContainer('containerCompras');
      cargarComprasPendientes();
      break;
    case 'venta':
      mostrarContainer('containerVentas');
      cargarVentasPendientes();
      break;
    case 'sueldo':
      mostrarContainer('containerSueldos');
      cargarSueldosPendientes();
      break;
    case 'otro':
      mostrarContainer('containerDescripcion');
      break;
  }
}

function mostrarContainer(containerId) {
  const container = document.getElementById(containerId);
  if (container) container.classList.remove('hidden');
}

function cargarComprasPendientes() {
  fetch('Pagos/getComprasPendientes')
    .then(response => response.json())
    .then(result => {
      const select = document.getElementById('pagoCompra');
      if (!select) return;
      
      select.innerHTML = '<option value="">Seleccionar compra...</option>';
      
      if (result.status && result.data) {
        result.data.forEach(compra => {
          const option = document.createElement('option');
          option.value = compra.idcompra;
          option.textContent = `#${compra.nro_compra} - ${compra.proveedor} - $${compra.total}`;
          option.dataset.proveedor = compra.proveedor;
          option.dataset.identificacion = compra.proveedor_identificacion;
          option.dataset.total = compra.total;
          select.appendChild(option);
        });
        
        select.addEventListener('change', function() {
          if (this.value) {
            const option = this.options[this.selectedIndex];
            mostrarInformacionDestinatario(
              option.dataset.proveedor,
              option.dataset.identificacion,
              option.dataset.total
            );
            document.getElementById('pagoMonto').value = option.dataset.total;
          } else {
            ocultarInformacionDestinatario();
          }
        });
      } else {
        mostrarNotificacion('No hay compras disponibles', 'info');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarNotificacion('Error al cargar compras', 'error');
    });
}

function cargarVentasPendientes() {
  fetch('Pagos/getVentasPendientes')
    .then(response => response.json())
    .then(result => {
      const select = document.getElementById('pagoVenta');
      if (!select) return;
      
      select.innerHTML = '<option value="">Seleccionar venta...</option>';
      
      if (result.status && result.data) {
        result.data.forEach(venta => {
          const option = document.createElement('option');
          option.value = venta.idventa;
          option.textContent = `#${venta.nro_venta} - ${venta.cliente} - $${venta.total}`;
          option.dataset.cliente = venta.cliente;
          option.dataset.identificacion = venta.cliente_identificacion;
          option.dataset.total = venta.total;
          select.appendChild(option);
        });
        
        select.addEventListener('change', function() {
          if (this.value) {
            const option = this.options[this.selectedIndex];
            mostrarInformacionDestinatario(
              option.dataset.cliente,
              option.dataset.identificacion,
              option.dataset.total
            );
            document.getElementById('pagoMonto').value = option.dataset.total;
          } else {
            ocultarInformacionDestinatario();
          }
        });
      } else {
        mostrarNotificacion('No hay ventas disponibles', 'info');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarNotificacion('Error al cargar ventas', 'error');
    });
}

function cargarSueldosPendientes() {
  fetch('Pagos/getSueldosPendientes')
    .then(response => response.json())
    .then(result => {
      const select = document.getElementById('pagoSueldo');
      if (!select) return;
      
      select.innerHTML = '<option value="">Seleccionar sueldo...</option>';
      
      if (result.status && result.data) {
        result.data.forEach(sueldo => {
          const option = document.createElement('option');
          option.value = sueldo.idsueldotemp;
          option.textContent = `${sueldo.empleado} - ${sueldo.periodo} - $${sueldo.total}`;
          option.dataset.empleado = sueldo.empleado;
          option.dataset.identificacion = sueldo.empleado_identificacion;
          option.dataset.total = sueldo.total;
          select.appendChild(option);
        });
        
        select.addEventListener('change', function() {
          if (this.value) {
            const option = this.options[this.selectedIndex];
            mostrarInformacionDestinatario(
              option.dataset.empleado,
              option.dataset.identificacion,
              option.dataset.total
            );
            document.getElementById('pagoMonto').value = option.dataset.total;
          } else {
            ocultarInformacionDestinatario();
          }
        });
      } else {
        mostrarNotificacion('No hay sueldos disponibles', 'info');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      mostrarNotificacion('Error al cargar sueldos', 'error');
    });
}

function mostrarInformacionDestinatario(nombre, identificacion, total) {
  const nombreEl = document.getElementById('destinatarioNombre');
  const identificacionEl = document.getElementById('destinatarioIdentificacion');
  const totalEl = document.getElementById('destinatarioTotal');
  const containerEl = document.getElementById('containerDestinatario');
  
  if (nombreEl) nombreEl.textContent = nombre;
  if (identificacionEl) identificacionEl.textContent = identificacion;
  if (totalEl) totalEl.textContent = `$${parseFloat(total).toFixed(2)}`;
  if (containerEl) containerEl.classList.remove('hidden');
}

function ocultarInformacionDestinatario() {
  const containerEl = document.getElementById('containerDestinatario');
  const montoEl = document.getElementById('pagoMonto');
  
  if (containerEl) containerEl.classList.add('hidden');
  if (montoEl) montoEl.value = '';
}

function establecerFechaActual() {
  const fechaEl = document.getElementById('pagoFecha');
  if (fechaEl) {
    const hoy = new Date().toISOString().split('T')[0];
    fechaEl.value = hoy;
  }
}

function registrarPago() {
  const btnGuardar = document.getElementById("btnGuardarPago");
  
  // Obtener tipo de pago seleccionado
  const tipoPago = document.querySelector('input[name="tipoPago"]:checked')?.value;
  
  if (!tipoPago) {
    mostrarNotificacion('Debe seleccionar un tipo de pago', 'warning');
    return;
  }

  // Obtener campos completos (básicos + dinámicos)
  const camposCompletos = [...camposFormularioPago, ...obtenerCamposDinamicos()];

  // Validar todos los campos
  if (!validarCamposVacios(camposCompletos, "formRegistrarPago")) {
    return;
  }

  // Validar formatos específicos
  let formularioConErrores = false;
  for (const campo of camposCompletos) {
    const inputElement = document.getElementById(campo.id);
    if (!inputElement || inputElement.offsetParent === null) continue;

    let esValido = true;
    
    if (campo.tipo === "select") {
      esValido = validarSelect(inputElement, campo.mensajes, "formRegistrarPago");
    } else if (campo.tipo === "fecha") {
      esValido = validarFecha(inputElement, campo.mensajes);
    } else if (campo.tipo === "radio") {
      // Los radio buttons ya se validaron con validarCamposVacios
      continue;
    } else if (["input", "textarea"].includes(campo.tipo) && campo.regex) {
      const valor = inputElement.value.trim();
      if (valor !== "" && !campo.regex.test(valor)) {
        // Mostrar error de formato
        const errorDiv = inputElement.nextElementSibling;
        if (errorDiv && campo.mensajes.formato) {
          errorDiv.textContent = campo.mensajes.formato;
          errorDiv.classList.remove("hidden");
        }
        inputElement.classList.add("border-red-500", "focus:ring-red-500");
        esValido = false;
      }
    }
    
    if (!esValido) formularioConErrores = true;
  }

  if (formularioConErrores) {
    mostrarNotificacion('Por favor, corrija los campos marcados en rojo', 'warning');
    return;
  }

  // Deshabilitar botón y mostrar loading
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
  }

  // Preparar mapeo de nombres
  const mapeoNombres = {
    "tipoPago": "tipo_pago",
    "pagoCompra": "idcompra",
    "pagoVenta": "idventa", 
    "pagoSueldo": "idsueldotemp",
    "pagoDescripcion": "descripcion",
    "pagoMonto": "monto",
    "pagoMetodoPago": "idtipo_pago",
    "pagoReferencia": "referencia",
    "pagoFecha": "fecha_pago",
    "pagoObservaciones": "observaciones"
  };

  // Usar la función registrarEntidad
  registrarEntidad({
    formId: "formRegistrarPago",
    endpoint: "Pagos/createPago",
    campos: camposCompletos,
    mapeoNombres: mapeoNombres,
    onSuccess: (result) => {
      // Mostrar Sweet Alert de éxito
      Swal.fire({
        title: '¡Éxito!',
        text: result.message || 'Pago registrado exitosamente',
        icon: 'success',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#10B981'
      }).then(() => {
        // Limpiar validaciones y cerrar modal
        limpiarValidaciones(camposCompletos, "formRegistrarPago");
        cerrarModal('modalRegistrarPago');
        // Recargar tabla
        tablaPagos.ajax.reload();
      });
    },
    onError: (result) => {
      // Mostrar Sweet Alert de error
      Swal.fire({
        title: '¡Error!',
        text: result.message || 'Error al registrar el pago',
        icon: 'error',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#EF4444'
      });
    }
  }).finally(() => {
    // Restaurar botón
    if (btnGuardar) {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = '<i class="fas fa-save mr-1 md:mr-2"></i> Guardar Pago';
    }
  });
}

// Función para mostrar notificaciones (compatible con tu sistema)
function mostrarNotificacion(mensaje, tipo) {
  // Si existe Swal, usarlo
  if (typeof Swal !== 'undefined') {
    const iconos = {
      'success': 'success',
      'error': 'error',
      'warning': 'warning',
      'info': 'info'
    };
    
    const colores = {
      'success': '#10B981',
      'error': '#EF4444',
      'warning': '#F59E0B',
      'info': '#3B82F6'
    };

    Swal.fire({
      title: tipo === 'error' ? '¡Error!' : tipo === 'warning' ? '¡Atención!' : tipo === 'info' ? 'Información' : '¡Éxito!',
      text: mensaje,
      icon: iconos[tipo] || 'info',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: colores[tipo] || '#3B82F6'
    });
  } else {
    // Fallback a alert nativo
    alert(mensaje);
  }
}

// Funciones globales
window.verPago = function(idPago) {
  // Mostrar loading
  fetch(`Pagos/getPagoById/${idPago}`)
    .then(response => response.json())
    .then(result => {
      console.log('Respuesta del servidor:', result); // Debug
      
      if (result.status && result.data) {
        mostrarModalVerPago(result.data);
      } else {
        mostrarNotificacion(result.message || 'Error al obtener el pago', 'error');
      }
    })
    .catch(error => {
      console.error("Error:", error);
      mostrarNotificacion('Error de conexión al obtener el pago', 'error');
    });
};

window.eliminarPago = function(idPago, descripcion) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar el pago "${descripcion}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#EF4444",
    cancelButtonColor: "#6B7280",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Pagos/deletePago", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ idpago: idPago }),
      })
        .then(response => response.json())
        .then(result => {
          if (result.status) {
            Swal.fire({
              title: '¡Éxito!',
              text: result.message,
              icon: 'success',
              confirmButtonText: 'Aceptar',
              confirmButtonColor: '#10B981'
            }).then(() => {
              tablaPagos.ajax.reload();
            });
          } else {
            mostrarNotificacion(result.message, 'error');
          }
        })
        .catch(error => {
          console.error("Error:", error);
          mostrarNotificacion('Error de conexión al eliminar', 'error');
        });
    }
  });
};

function mostrarModalVerPago(pago) {
  console.log('Datos del pago a mostrar:', pago); // Debug
  
  // Llenar elementos del modal de visualización
  const elementos = {
    'verPagoId': pago.idpago || "N/A",
    'verPagoTipo': pago.tipo_pago_texto || "N/A",
    'verPagoDestinatario': pago.destinatario || "N/A",
    'verPagoMonto': `$${parseFloat(pago.monto || 0).toFixed(2)}`,
    'verPagoMetodo': pago.metodo_pago || "N/A",
    'verPagoReferencia': pago.referencia || "Sin referencia",
    'verPagoFecha': pago.fecha_pago_formato || "N/A",
    'verPagoObservaciones': pago.observaciones || "Sin observaciones",
    'verPagoFechaCreacion': pago.fecha_creacion ? 
      new Date(pago.fecha_creacion).toLocaleDateString('es-ES') : "N/A"
  };

  // Llenar los elementos del modal
  Object.entries(elementos).forEach(([id, valor]) => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.textContent = valor;
      console.log(`Llenando ${id} con: ${valor}`); // Debug
    } else {
      console.warn(`Elemento ${id} no encontrado en el DOM`); // Debug
    }
  });

  // Aplicar badge de estatus
  const estatusEl = document.getElementById('verPagoEstatus');
  if (estatusEl && pago.estatus) {
    if (pago.estatus === 'activo') {
      estatusEl.className = 'inline-flex px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full';
      estatusEl.textContent = 'ACTIVO';
    } else {
      estatusEl.className = 'inline-flex px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full';
      estatusEl.textContent = 'INACTIVO';
    }
  }

  // Mostrar información adicional si existe
  const personaEl = document.getElementById('verPagoPersona');
  if (personaEl) {
    personaEl.textContent = pago.persona_nombre || "Sin asignar";
  }

  // Abrir el modal
  abrirModal("modalVerPago");
}