/**
 * Tour específico para el módulo de Sueldos
 * Se carga automáticamente cuando el usuario visita el módulo de sueldos
 */

// Función para iniciar el tour del módulo de sueldos
function iniciarTourSueldos() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('sueldos-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de sueldos.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de sueldos cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de sueldos
    tour.addStep({
        title: '¡Bienvenido al Módulo de Sueldos! 💵',
        text: 'Te guiaremos por las principales funcionalidades del módulo de sueldos. Aquí puedes registrar y gestionar los pagos de sueldos tanto a empleados como a personas externas que presten servicios.',
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
        text: 'Aquí puedes ver el título del módulo y una descripción de sus funcionalidades. Este módulo centraliza el registro de todos los sueldos y compensaciones económicas.',
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

    // Paso 3: Botón registrar sueldo
    const btnRegistrar = document.querySelector('#btnAbrirModalRegistrarSueldo');
    if (btnRegistrar) {
        tour.addStep({
            title: 'Registrar Nuevo Sueldo ➕',
            text: 'Con este botón puedes registrar nuevos sueldos en el sistema. Podrás asignar sueldos tanto a empleados de la empresa como a personas externas que presten servicios.',
            attachTo: {
                element: '#btnAbrirModalRegistrarSueldo',
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

    // Paso 4: Tabla de sueldos
    tour.addStep({
        title: 'Tabla de Sueldos 📊',
        text: 'En esta tabla puedes ver todos los sueldos registrados. Cada fila muestra información como la persona/empleado que recibe el sueldo, el monto, la moneda, la fecha de registro y las acciones disponibles.',
        attachTo: {
            element: '#TablaSueldos',
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

    // Paso 5: Tipos de registro
    tour.addStep({
        title: 'Tipos de Registro 👥',
        text: 'El sistema permite registrar sueldos para dos tipos de beneficiarios:<br><br>' +
              '• <strong>Empleados:</strong> Personal fijo de la empresa registrado en el módulo de empleados<br>' +
              '• <strong>Personas:</strong> Trabajadores externos o freelancers registrados en el módulo de personas<br><br>' +
              'Esta flexibilidad permite gestionar tanto nómina fija como pagos puntuales.',
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

    // Paso 6: Monedas disponibles
    tour.addStep({
        title: 'Gestión Multi-Moneda 💱',
        text: 'Los sueldos pueden registrarse en diferentes monedas:<br><br>' +
              '• <strong>Bolívares (VES):</strong> Moneda local<br>' +
              '• <strong>Dólares (USD):</strong> Para pagos en divisa<br>' +
              '• <strong>Euros (EUR):</strong> Otra opción de divisa<br><br>' +
              'El sistema convierte automáticamente usando las tasas del módulo de Tasas, permitiendo llevar un control preciso del costo real en cualquier moneda.',
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

    // Paso 7: Acciones disponibles
    tour.addStep({
        title: 'Acciones en la Tabla 🔧',
        text: 'En la columna de acciones de cada sueldo encontrarás botones para:<br><br>' +
              '• <strong>Ver detalles:</strong> Consulta toda la información del sueldo<br>' +
              '• <strong>Editar:</strong> Modifica montos u observaciones (si tienes permisos)<br>' +
              '• <strong>Eliminar:</strong> Elimina un registro de sueldo (si tienes permisos)<br><br>' +
              'Los botones disponibles dependen de tus permisos de usuario.',
        attachTo: {
            element: '#TablaSueldos',
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

    // Paso 8: Búsqueda y filtros
    tour.addStep({
        title: 'Búsqueda y Filtros 🔍',
        text: 'Utiliza la barra de búsqueda de DataTables para encontrar sueldos específicos. Puedes buscar por nombre de la persona/empleado, monto, moneda o fecha. También puedes ordenar las columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#TablaSueldos_filter',
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
                    const filterElement = document.querySelector('#TablaSueldos_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#TablaSueldos_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 9: Campo de observaciones
    tour.addStep({
        title: 'Campo de Observaciones 📝',
        text: 'Al registrar un sueldo, el campo de observaciones es muy útil para:<br><br>' +
              '• Especificar el período que cubre el pago<br>' +
              '• Indicar si incluye bonificaciones o deducciones<br>' +
              '• Agregar notas sobre horas extras o comisiones<br>' +
              '• Documentar cualquier detalle relevante del pago<br><br>' +
              'Mantener buenas observaciones facilita futuras auditorías y consultas.',
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

    // Paso 10: Integración con otros módulos
    tour.addStep({
        title: 'Integración con Otros Módulos 🔗',
        text: 'El módulo de sueldos se integra con:<br><br>' +
              '• <strong>Empleados:</strong> Para acceder a la lista de empleados activos<br>' +
              '• <strong>Personas:</strong> Para personas externas que reciban pagos<br>' +
              '• <strong>Moneda:</strong> Para gestionar diferentes divisas<br>' +
              '• <strong>Tasas:</strong> Para convertir montos a la moneda local<br>' +
              '• <strong>Dashboard:</strong> Los sueldos impactan en reportes financieros<br><br>' +
              'Esto garantiza consistencia en toda la gestión financiera.',
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

    // Paso 11: Consejos finales
    tour.addStep({
        title: 'Consejos y Buenas Prácticas 💡',
        text: 'Para aprovechar al máximo el módulo de sueldos:<br><br>' +
              '• Registra los sueldos con regularidad y puntualidad<br>' +
              '• Verifica siempre que el monto y la moneda sean correctos<br>' +
              '• Utiliza observaciones detalladas para cada pago<br>' +
              '• Revisa periódicamente los registros para detectar inconsistencias<br>' +
              '• Mantén actualizada la información de empleados y personas<br>' +
              '• Asegúrate que las tasas de cambio estén actualizadas<br><br>' +
              '¡Ya estás listo para gestionar los sueldos del sistema!',
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
function agregarBotonAyudaSueldos() {
    if (document.querySelector('#sueldos-help-btn')) {
        return;
    }

    const helpButton = document.createElement('button');
    helpButton.id = 'sueldos-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de sueldos');
    helpButton.addEventListener('click', iniciarTourSueldos);
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
    agregarBotonAyudaSueldos();
    
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('sueldos-tour-completed');
        if (!tourCompleted) {
            iniciarTourSueldos();
        }
    }, 1000);
});

window.iniciarTourSueldos = iniciarTourSueldos;
