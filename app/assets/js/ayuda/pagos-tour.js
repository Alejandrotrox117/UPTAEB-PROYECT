/**
 * Tour específico para el módulo de Pagos
 * Se carga automáticamente cuando el usuario visita el módulo de pagos
 */

// Función para iniciar el tour del módulo de pagos
function iniciarTourPagos() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('pagos-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de pagos.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            localStorage.setItem('pagos-tour-completed', 'true');
            console.log('Tour del módulo de pagos cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de pagos
    tour.addStep({
        title: '¡Bienvenido al Módulo de Pagos! 💰',
        text: 'Te guiaremos por las principales funcionalidades del módulo de pagos. Aquí puedes registrar y gestionar todos los pagos del sistema, ya sean pagos a proveedores, empleados o cualquier otro tipo de transacción.',
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
        text: 'Aquí puedes ver el título del módulo y una descripción de sus funcionalidades. Este módulo centraliza toda la gestión de pagos de tu empresa.',
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

    // Paso 3: Botón registrar pago
    const btnRegistrar = document.querySelector('#btnAbrirModalRegistrarPago');
    if (btnRegistrar) {
        tour.addStep({
            title: 'Registrar Nuevo Pago ➕',
            text: 'Con este botón puedes registrar nuevos pagos en el sistema. Podrás especificar el destinatario, monto, método de pago y toda la información necesaria para llevar un control preciso.',
            attachTo: {
                element: '#btnAbrirModalRegistrarPago',
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

    // Paso 4: Tabla de pagos
    tour.addStep({
        title: 'Tabla de Pagos 📊',
        text: 'En esta tabla puedes ver todos los pagos registrados. Cada fila muestra información importante como destinatario, tipo de pago, monto, método de pago, fecha y estatus. Las columnas son ordenables para facilitar tu búsqueda.',
        attachTo: {
            element: '#TablaPagos',
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

    // Paso 5: Acciones disponibles
    tour.addStep({
        title: 'Acciones en la Tabla 🔧',
        text: 'En la columna de acciones de cada pago encontrarás botones para:<br><br>' +
              '• <strong>Ver detalles:</strong> Consulta la información completa del pago<br>' +
              '• <strong>Editar:</strong> Modifica información del pago (si tienes permisos)<br>' +
              '• <strong>Eliminar:</strong> Elimina un pago del sistema (si tienes permisos)<br>' +
              '• <strong>Cambiar estatus:</strong> Actualiza el estado del pago<br><br>' +
              'Los botones disponibles dependen de tus permisos de usuario.',
        attachTo: {
            element: '#TablaPagos',
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

    // Paso 6: Búsqueda y filtros
    tour.addStep({
        title: 'Búsqueda y Filtros 🔍',
        text: 'Utiliza la barra de búsqueda de DataTables para encontrar pagos específicos. Puedes buscar por destinatario, tipo de pago, método, fecha o cualquier otro dato. También puedes ordenar las columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#TablaPagos_filter',
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
                setTimeout(() => {
                    const filterElement = document.querySelector('#TablaPagos_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#TablaPagos_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 7: Tipos de pagos
    tour.addStep({
        title: 'Tipos de Pagos 💳',
        text: 'El sistema soporta diferentes tipos de pagos:<br><br>' +
              '• <strong>Pagos a proveedores:</strong> Por compras realizadas<br>' +
              '• <strong>Pagos a empleados:</strong> Sueldos y bonificaciones<br>' +
              '• <strong>Otros pagos:</strong> Servicios, impuestos, etc.<br><br>' +
              'Cada tipo de pago puede tener diferentes métodos: efectivo, transferencia, tarjeta, etc.',
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

    // Paso 8: Control de estatus
    tour.addStep({
        title: 'Control de Estatus 📌',
        text: 'Los pagos pueden tener diferentes estados:<br><br>' +
              '• <strong>Pendiente:</strong> Pago programado pero no realizado<br>' +
              '• <strong>Completado:</strong> Pago realizado exitosamente<br>' +
              '• <strong>Cancelado:</strong> Pago anulado<br><br>' +
              'Esto te permite llevar un control preciso de las transacciones y planificar el flujo de caja.',
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

    // Paso 9: Sistema de permisos
    tour.addStep({
        title: 'Sistema de Permisos 🔐',
        text: 'Los botones y funcionalidades que ves dependen de tus permisos de usuario:<br><br>' +
              '• <strong>Ver:</strong> Consultar la lista de pagos<br>' +
              '• <strong>Crear:</strong> Registrar nuevos pagos<br>' +
              '• <strong>Editar:</strong> Modificar pagos existentes<br>' +
              '• <strong>Eliminar:</strong> Eliminar pagos del sistema<br><br>' +
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

    // Paso 10: Consejos finales
    tour.addStep({
        title: 'Consejos y Buenas Prácticas 💡',
        text: 'Para aprovechar al máximo el módulo de pagos:<br><br>' +
              '• Registra los pagos inmediatamente después de realizarlos<br>' +
              '• Verifica que el monto y el destinatario sean correctos<br>' +
              '• Utiliza el campo de observaciones para notas importantes<br>' +
              '• Mantén actualizado el estatus de los pagos<br>' +
              '• Revisa regularmente los pagos pendientes<br>' +
              '• Utiliza el buscador para encontrar transacciones específicas<br><br>' +
              '¡Ya estás listo para gestionar los pagos del sistema!',
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
function agregarBotonAyudaPagos() {
    if (document.querySelector('#pagos-help-btn')) {
        return;
    }

    const helpButton = document.createElement('button');
    helpButton.id = 'pagos-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de pagos');
    helpButton.addEventListener('click', iniciarTourPagos);
    helpButton.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.backgroundColor = '#15803d';
    });
    helpButton.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.backgroundColor = '#16a34a';
    });
    
    helpButton.style.animation = 'bounce 2s infinite';
    
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

document.addEventListener('DOMContentLoaded', function() {
    agregarBotonAyudaPagos();
    
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('pagos-tour-completed');
        if (!tourCompleted) {
            iniciarTourPagos();
        }
    }, 1000);
});

window.iniciarTourPagos = iniciarTourPagos;
