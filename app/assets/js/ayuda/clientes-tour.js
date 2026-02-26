/**
 * Tour específico para el módulo de Clientes
 * Se carga automáticamente cuando el usuario visita el módulo de clientes
 */

// Función para iniciar el tour del módulo de clientes
function iniciarTourClientes() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('clientes-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de clientes.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de clientes cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de clientes
    tour.addStep({
        title: '¡Bienvenido al Módulo de Clientes! 👥',
        text: 'Te guiaremos por las principales funcionalidades del módulo de clientes. Aquí puedes gestionar toda la información de tus clientes, registrar nuevos clientes, actualizar sus datos y consultar su historial.',
        buttons: [
            {
                text: 'Omitir Tour',
                action: tour.cancel,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Comenzar Tour',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 2: Título y descripción
    tour.addStep({
        title: 'Área de Información 📋',
        text: 'Aquí puedes ver el título del módulo y una descripción de sus funcionalidades. Este módulo te permite la gestión integral de clientes del sistema.',
        attachTo: {
            element: 'h1',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 3: Botón registrar cliente (solo si existe)
    const btnRegistrar = document.querySelector('#abrirModalBtn');
    if (btnRegistrar) {
        tour.addStep({
            title: 'Registrar Nuevo Cliente ➕',
            text: 'Con este botón puedes agregar nuevos clientes al sistema. Al hacer clic se abrirá un formulario donde podrás ingresar la cédula, nombre, apellido, teléfono, dirección y observaciones del cliente.',
            attachTo: {
                element: '#abrirModalBtn',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Anterior',
                    action: tour.back,
                    classes: 'shepherd-button-secondary'
                },
                {
                    text: 'Siguiente',
                    action: tour.next,
                    classes: 'shepherd-button-primary'
                }
            ]
        });
    }

    // Paso 4: Botón exportar (solo si existe)
    const btnExportar = document.querySelector('#btnExportarClientes');
    if (btnExportar) {
        tour.addStep({
            title: 'Exportar Datos 📄',
            text: 'Con este botón puedes exportar la lista de clientes a diferentes formatos como PDF o Excel. Esto es útil para generar reportes, realizar respaldos o analizar la información fuera del sistema.',
            attachTo: {
                element: '#btnExportarClientes',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Anterior',
                    action: tour.back,
                    classes: 'shepherd-button-secondary'
                },
                {
                    text: 'Siguiente',
                    action: tour.next,
                    classes: 'shepherd-button-primary'
                }
            ]
        });
    }

    // Paso 5: Tabla de clientes
    tour.addStep({
        title: 'Tabla de Clientes 📊',
        text: 'En esta tabla puedes ver todos los clientes registrados en el sistema. Cada fila muestra la información del cliente incluyendo cédula, nombre, apellido, teléfono y dirección. Las columnas son ordenables y puedes buscar clientes específicos.',
        attachTo: {
            element: '#Tablaclientes',
            on: 'top'
        },
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 6: Acciones disponibles
    tour.addStep({
        title: 'Acciones en la Tabla 🔧',
        text: 'En la columna de acciones de cada cliente encontrarás botones para:<br><br>' +
              '• <strong>Ver detalles:</strong> Consulta toda la información del cliente<br>' +
              '• <strong>Editar:</strong> Modifica los datos del cliente (si tienes permisos)<br>' +
              '• <strong>Eliminar:</strong> Elimina un cliente del sistema (si tienes permisos)<br><br>' +
              'Los botones disponibles dependen de tus permisos de usuario.',
        attachTo: {
            element: '#Tablaclientes',
            on: 'top'
        },
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 7: Búsqueda y filtros
    tour.addStep({
        title: 'Búsqueda y Filtros 🔍',
        text: 'Utiliza la barra de búsqueda de DataTables para encontrar clientes específicos. Puedes buscar por cédula, nombre, apellido, teléfono o cualquier otro dato visible en la tabla. También puedes ordenar las columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#Tablaclientes_filter',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ],
        when: {
            show: function() {
                // Esperar a que DataTables inicialice el filtro
                setTimeout(() => {
                    const filterElement = document.querySelector('#Tablaclientes_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#Tablaclientes_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 8: Registro de clientes - información
    tour.addStep({
        title: 'Proceso de Registro 📝',
        text: 'Al registrar un nuevo cliente, debes completar los siguientes campos:<br><br>' +
              '• <strong>Cédula:</strong> Formato V-XXXXXXXX o E-XXXXXXXX (campo obligatorio)<br>' +
              '• <strong>Nombre:</strong> Nombre del cliente (campo obligatorio)<br>' +
              '• <strong>Apellido:</strong> Apellido del cliente (campo obligatorio)<br>' +
              '• <strong>Teléfono Principal:</strong> Formato 04XX-XXXXXXX (campo obligatorio)<br>' +
              '• <strong>Dirección:</strong> Dirección del cliente (opcional)<br>' +
              '• <strong>Observaciones:</strong> Notas adicionales (opcional)<br><br>' +
              'El sistema valida automáticamente los formatos de los datos.',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 9: Validaciones
    tour.addStep({
        title: 'Validaciones Automáticas ✓',
        text: 'El sistema valida automáticamente la información que ingresas:<br><br>' +
              '• <strong>Cédula:</strong> Debe seguir el formato venezolano (V o E seguido de números)<br>' +
              '• <strong>Nombres y apellidos:</strong> Solo se permiten letras y espacios<br>' +
              '• <strong>Teléfono:</strong> Debe seguir el formato 04XX-XXXXXXX<br>' +
              '• <strong>Duplicados:</strong> No se permiten cédulas duplicadas<br><br>' +
              'Si hay algún error, el sistema te indicará qué corregir.',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 10: Permisos
    tour.addStep({
        title: 'Sistema de Permisos 🔐',
        text: 'Los botones y funcionalidades que ves dependen de tus permisos de usuario:<br><br>' +
              '• <strong>Ver:</strong> Consultar la lista de clientes<br>' +
              '• <strong>Crear:</strong> Registrar nuevos clientes<br>' +
              '• <strong>Editar:</strong> Modificar información de clientes existentes<br>' +
              '• <strong>Eliminar:</strong> Eliminar clientes del sistema<br>' +
              '• <strong>Exportar:</strong> Exportar datos a archivos externos<br><br>' +
              'Si no tienes algún permiso, los botones correspondientes no estarán disponibles.',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 11: Historial y ventas
    tour.addStep({
        title: 'Historial de Clientes 📈',
        text: 'La información de los clientes está integrada con otros módulos del sistema. Desde este módulo puedes gestionar los datos básicos, y el sistema mantiene automáticamente un registro de:<br><br>' +
              '• Ventas realizadas al cliente<br>' +
              '• Pagos y saldos<br>' +
              '• Historial de transacciones<br><br>' +
              'Esta información se puede consultar desde los módulos correspondientes.',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 12: Consejos finales
    tour.addStep({
        title: 'Consejos y Buenas Prácticas 💡',
        text: 'Para aprovechar al máximo el módulo de clientes:<br><br>' +
              '• Verifica que la cédula esté correcta antes de registrar (no se puede cambiar después)<br>' +
              '• Mantén actualizada la información de contacto de tus clientes<br>' +
              '• Utiliza el campo de observaciones para notas importantes<br>' +
              '• Revisa los datos antes de guardar para evitar duplicados<br>' +
              '• Exporta regularmente la información como respaldo<br>' +
              '• Utiliza el buscador para encontrar clientes rápidamente<br><br>' +
              '¡Ya estás listo para gestionar los clientes del sistema!',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Finalizar',
                action: tour.complete,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    tour.start();
}

// Función para mostrar el botón de ayuda
function agregarBotonAyudaClientes() {
    // Verificar si ya existe el botón
    if (document.querySelector('#clientes-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'clientes-help-btn';
    helpButton.innerHTML = '<i class="fas fa-question-circle"></i>';
    helpButton.className = 'fixed bottom-6 right-6 bg-green-600 hover:bg-green-700 text-white p-4 rounded-full shadow-lg z-50 transition-all duration-300 hover:scale-110';
    helpButton.style.cssText = `
        position: fixed !important;
        bottom: 24px !important;
        right: 24px !important;
        width: 56px !important;
        height: 56px !important;
        border-radius: 50% !important;
        background-color: #16a34a !important;
        color: white !important;
        border: none !important;
        cursor: pointer !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
        z-index: 1000 !important;
        transition: all 0.3s ease !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 20px !important;
    `;
    
    helpButton.setAttribute('title', 'Iniciar tour de clientes');
    helpButton.addEventListener('click', iniciarTourClientes);
    helpButton.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.backgroundColor = '#15803d';
    });
    helpButton.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.backgroundColor = '#16a34a';
    });
    
    // Añadir una pequeña animación al botón para hacerlo más visible
    helpButton.style.animation = 'bounce 2s infinite';
    
    // Añadir estilo para la animación si no existe
    if (!document.querySelector('#tour-animations')) {
        const styleEl = document.createElement('style');
        styleEl.id = 'tour-animations';
        styleEl.textContent = `
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
                40% {transform: translateY(-12px);}
                60% {transform: translateY(-5px);}
            }
        `;
        document.head.appendChild(styleEl);
    }
    
    document.body.appendChild(helpButton);
}

// Verificar si el tour debe iniciarse automáticamente
document.addEventListener('DOMContentLoaded', function() {
    // Agregar botón de ayuda
    agregarBotonAyudaClientes();
    
    // Esperar a que la página cargue completamente
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('clientes-tour-completed');
        
        // Si es la primera vez o el usuario eliminó el registro, iniciar el tour
        if (!tourCompleted) {
            iniciarTourClientes();
        }
    }, 1000);
});

// Exponer la función globalmente por si se quiere iniciar manualmente
window.iniciarTourClientes = iniciarTourClientes;
