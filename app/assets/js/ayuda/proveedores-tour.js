/**
 * Tour específico para el módulo de Proveedores
 * Se carga automáticamente cuando el usuario visita el módulo de proveedores
 */

// Función para iniciar el tour del módulo de proveedores
function iniciarTourProveedores() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('proveedores-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de proveedores.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            // Tour cancelado
        }
    });

    // Paso 1: Bienvenida al módulo de proveedores
    tour.addStep({
        title: '¡Bienvenido al Módulo de Proveedores! 🏢',
        text: 'Te guiaremos por las principales funcionalidades del módulo de proveedores. Aquí puedes gestionar toda la información de tus proveedores, registrar nuevos proveedores y mantener actualizada su información de contacto.',
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
        text: 'Aquí puedes ver el título del módulo y una descripción de sus funcionalidades. Este módulo te permite gestionar todos los proveedores con los que trabaja tu empresa.',
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

    // Paso 3: Botón registrar proveedor
    const btnRegistrar = document.querySelector('#btnAbrirModalRegistrarProveedor');
    if (btnRegistrar) {
        tour.addStep({
            title: 'Registrar Nuevo Proveedor ➕',
            text: 'Con este botón puedes agregar nuevos proveedores al sistema. Al hacer clic se abrirá un formulario donde podrás ingresar toda la información necesaria del proveedor.',
            attachTo: {
                element: '#btnAbrirModalRegistrarProveedor',
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

    // Paso 4: Tabla de proveedores
    tour.addStep({
        title: 'Tabla de Proveedores 📊',
        text: 'En esta tabla puedes ver todos los proveedores registrados. Cada fila muestra información importante como nombre, identificación, teléfono y dirección. Las columnas son ordenables y puedes buscar proveedores específicos.',
        attachTo: {
            element: '#TablaProveedores',
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
        text: 'En la columna de acciones de cada proveedor encontrarás botones para:<br><br>' +
              '• <strong>Ver detalles:</strong> Consulta toda la información del proveedor<br>' +
              '• <strong>Editar:</strong> Modifica los datos del proveedor<br>' +
              '• <strong>Eliminar:</strong> Elimina un proveedor del sistema<br><br>' +
              'Estas opciones te permiten mantener actualizada la información de tus proveedores.',
        attachTo: {
            element: '#TablaProveedores',
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
        text: 'Utiliza la barra de búsqueda de DataTables para encontrar proveedores específicos. Puedes buscar por nombre, identificación, teléfono o cualquier otro dato visible en la tabla. También puedes ordenar las columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#TablaProveedores_filter',
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
                    const filterElement = document.querySelector('#TablaProveedores_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#TablaProveedores_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 7: Registro de proveedores
    tour.addStep({
        title: 'Proceso de Registro 📝',
        text: 'Al registrar un nuevo proveedor, debes completar los siguientes campos:<br><br>' +
              '• <strong>Nombre:</strong> Nombre del proveedor (obligatorio)<br>' +
              '• <strong>Apellido:</strong> Apellido del proveedor (opcional)<br>' +
              '• <strong>Identificación:</strong> RIF, CI o Pasaporte (obligatorio)<br>' +
              '• <strong>Teléfono Principal:</strong> Número de contacto (obligatorio)<br>' +
              '• <strong>Correo, Dirección:</strong> Información de contacto adicional<br>' +
              '• <strong>Observaciones:</strong> Notas importantes sobre el proveedor<br><br>' +
              'El sistema valida automáticamente los datos ingresados.',
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

    // Paso 8: Importancia de los proveedores
    tour.addStep({
        title: 'Gestión de Proveedores 🤝',
        text: 'Los proveedores son esenciales para el sistema porque:<br><br>' +
              '• Se vinculan automáticamente con las <strong>compras</strong> que realizas<br>' +
              '• Permiten llevar un historial de transacciones por proveedor<br>' +
              '• Facilitan el control de pagos y cuentas por pagar<br>' +
              '• Te ayudan a analizar qué proveedores son más confiables<br><br>' +
              'Mantener esta información actualizada es clave para un buen control de inventario.',
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
        text: 'El sistema valida automáticamente la información:<br><br>' +
              '• <strong>Identificación:</strong> Debe seguir formatos válidos (RIF, CI)<br>' +
              '• <strong>Teléfono:</strong> Debe seguir el formato correcto<br>' +
              '• <strong>Correo:</strong> Debe ser una dirección de email válida<br>' +
              '• <strong>Duplicados:</strong> No se permiten identificaciones duplicadas<br><br>' +
              'Si hay algún error, el sistema te indicará qué corregir antes de guardar.',
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
        text: 'Para aprovechar al máximo el módulo de proveedores:<br><br>' +
              '• Registra los proveedores antes de hacer compras<br>' +
              '• Mantén actualizada la información de contacto<br>' +
              '• Utiliza el campo de observaciones para notas importantes<br>' +
              '• Revisa los datos antes de guardar para evitar duplicados<br>' +
              '• Verifica que la identificación (RIF/CI) sea correcta<br>' +
              '• Utiliza el buscador para encontrar proveedores rápidamente<br><br>' +
              '¡Ya estás listo para gestionar los proveedores del sistema!',
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
function agregarBotonAyudaProveedores() {
    // Verificar si ya existe el botón
    if (document.querySelector('#proveedores-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'proveedores-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de proveedores');
    helpButton.addEventListener('click', iniciarTourProveedores);
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
    agregarBotonAyudaProveedores();
    
    // Auto-inicio del tour deshabilitado - solo se inicia manualmente desde el botón de ayuda
    // setTimeout(() => {
    //     const tourCompleted = localStorage.getItem('proveedores-tour-completed');
    //     
    //     // Si es la primera vez o el usuario eliminó el registro, iniciar el tour
    //     if (!tourCompleted) {
    //         iniciarTourProveedores();
    //     }
    // }, 1000);
});

// Exponer la función globalmente por si se quiere iniciar manualmente
window.iniciarTourProveedores = iniciarTourProveedores;
