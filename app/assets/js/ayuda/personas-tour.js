/**
 * Tour específico para el módulo de Personas
 * Se carga automáticamente cuando el usuario visita el módulo de personas
 */

// Función para iniciar el tour del módulo de personas
function iniciarTourPersonas() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('personas-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de personas.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de personas cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de personas
    tour.addStep({
        title: '¡Bienvenido al Módulo de Personas! 👤',
        text: 'Te guiaremos por las principales funcionalidades del módulo de personas. Aquí puedes registrar y gestionar información de personas externas que tengan relación con tu empresa, como trabajadores freelance, contratistas, o cualquier otra persona que no sea empleado fijo.',
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
        title: 'Administración de Personas 📋',
        text: 'Este módulo te permite gestionar un registro completo de personas que no son empleados fijos pero que interactúan con tu empresa. Es diferente del módulo de empleados y del de clientes.',
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

    // Paso 3: Botón registrar persona
    const btnRegistrar = document.querySelector('#btnAbrirModalRegistrarPersona');
    if (btnRegistrar) {
        tour.addStep({
            title: 'Registrar Nueva Persona ➕',
            text: 'Con este botón puedes agregar nuevas personas al sistema. Podrás ingresar información completa como nombre, identificación, contacto, dirección y más.',
            attachTo: {
                element: '#btnAbrirModalRegistrarPersona',
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

    // Paso 4: Tabla de personas
    tour.addStep({
        title: 'Tabla de Personas 📊',
        text: 'En esta tabla puedes ver todas las personas registradas. Se muestran datos como nombre completo, identificación (cédula), teléfono, correo electrónico, dirección y las acciones disponibles para cada registro.',
        attachTo: {
            element: '#TablaPersonas',
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

    // Paso 5: Campos de información personal
    tour.addStep({
        title: 'Información Personal Completa 📝',
        text: 'Al registrar una persona, puedes capturar:<br><br>' +
              '• <strong>Datos básicos:</strong> Nombre, apellido, identificación<br>' +
              '• <strong>Información de contacto:</strong> Teléfono principal, alternativo y correo<br>' +
              '• <strong>Datos demográficos:</strong> Género, fecha de nacimiento<br>' +
              '• <strong>Ubicación:</strong> Dirección completa<br>' +
              '• <strong>Observaciones:</strong> Notas adicionales relevantes<br><br>' +
              'Esto permite mantener un registro organizado y completo.',
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

    // Paso 6: Validación de identificación
    tour.addStep({
        title: 'Validación de Identificación 🆔',
        text: 'El sistema valida la cédula venezolana automáticamente:<br><br>' +
              '• Puedes ingresar con o sin prefijo (V-, E-, J-, etc.)<br>' +
              '• Se valida el formato y que no esté duplicada<br>' +
              '• Se almacena de forma estandarizada<br><br>' +
              'Esto garantiza que no haya registros duplicados y mantiene la integridad de los datos.',
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

    // Paso 7: Diferencia con empleados y clientes
    tour.addStep({
        title: 'Personas vs Empleados vs Clientes 🔄',
        text: 'Es importante entender las diferencias:<br><br>' +
              '• <strong>Personas:</strong> Trabajadores externos, contratistas, freelancers<br>' +
              '• <strong>Empleados:</strong> Personal fijo con contrato laboral<br>' +
              '• <strong>Clientes:</strong> Compradores de productos/servicios<br><br>' +
              'Una persona en este módulo puede recibir pagos por servicios prestados sin ser empleado fijo de la empresa.',
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

    // Paso 8: Acciones disponibles
    tour.addStep({
        title: 'Acciones en la Tabla 🔧',
        text: 'En la columna de acciones de cada persona encontrarás botones para:<br><br>' +
              '• <strong>Ver detalles:</strong> Consulta toda la información de la persona<br>' +
              '• <strong>Editar:</strong> Modifica datos personales o de contacto (si tienes permisos)<br>' +
              '• <strong>Eliminar:</strong> Elimina un registro del sistema (si tienes permisos)<br><br>' +
              'Los botones disponibles dependen de tus permisos de usuario.',
        attachTo: {
            element: '#TablaPersonas',
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

    // Paso 9: Búsqueda y filtros
    tour.addStep({
        title: 'Búsqueda y Filtros 🔍',
        text: 'Utiliza la barra de búsqueda de DataTables para encontrar personas específicas. Puedes buscar por nombre, apellido, identificación, teléfono o cualquier otro dato. También puedes ordenar las columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#TablaPersonas_filter',
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
                    const filterElement = document.querySelector('#TablaPersonas_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#TablaPersonas_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 10: Integración con otros módulos
    tour.addStep({
        title: 'Integración con Otros Módulos 🔗',
        text: 'El módulo de personas se integra con:<br><br>' +
              '• <strong>Sueldos:</strong> Para asignar pagos a personas externas<br>' +
              '• <strong>Pagos:</strong> Para registrar transacciones a personas<br>' +
              '• <strong>Dashboard:</strong> Para reportes y estadísticas<br><br>' +
              'Esto permite una gestión integral sin necesidad de convertir a la persona en empleado formal.',
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

    // Paso 11: Campos obligatorios
    tour.addStep({
        title: 'Campos Obligatorios ⚠️',
        text: 'Para registrar una persona, debes completar los campos marcados con asterisco (*):<br><br>' +
              '• <strong>Nombre</strong><br>' +
              '• <strong>Apellido</strong><br>' +
              '• <strong>Identificación (Cédula)</strong><br>' +
              '• <strong>Teléfono Principal</strong><br>' +
              '• <strong>Género</strong><br><br>' +
              'Los demás campos son opcionales pero recomendados para tener información completa.',
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
        text: 'Para aprovechar al máximo el módulo de personas:<br><br>' +
              '• Ingresa información completa desde el inicio<br>' +
              '• Verifica que la cédula sea correcta antes de guardar<br>' +
              '• Mantén actualizados los datos de contacto<br>' +
              '• Usa el campo de observaciones para notas importantes<br>' +
              '• Revisa periódicamente que no haya duplicados<br>' +
              '• Considera si la persona debería ser empleado formal<br><br>' +
              '¡Ya estás listo para gestionar el registro de personas!',
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
function agregarBotonAyudaPersonas() {
    if (document.querySelector('#personas-help-btn')) {
        return;
    }

    const helpButton = document.createElement('button');
    helpButton.id = 'personas-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de personas');
    helpButton.addEventListener('click', iniciarTourPersonas);
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
    agregarBotonAyudaPersonas();
    
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('personas-tour-completed');
        if (!tourCompleted) {
            iniciarTourPersonas();
        }
    }, 1000);
});

window.iniciarTourPersonas = iniciarTourPersonas;
