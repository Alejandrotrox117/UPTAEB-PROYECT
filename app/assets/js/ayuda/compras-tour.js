/**
 * Tour específico para el módulo de Compras
 * Se carga automáticamente cuando el usuario visita el módulo de compras
 */

// Función para iniciar el tour del módulo de compras
function iniciarTourCompras() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('compras-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de compras.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de compras cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de compras
    tour.addStep({
        title: '¡Bienvenido al Módulo de Compras! 🛒',
        text: 'Te guiaremos por las principales funcionalidades del módulo de compras. Aquí puedes registrar y gestionar todas las compras de materiales, llevar control de proveedores y mantener un historial detallado de transacciones.',
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
        text: 'Aquí puedes ver el título del módulo y una descripción de sus funcionalidades. En este módulo puedes registrar compras, consultar el historial y gestionar proveedores.',
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

    // Paso 3: Botón registrar nueva compra (solo si existe)
    const btnNuevaCompra = document.querySelector('#btnAbrirModalNuevaCompra');
    if (btnNuevaCompra) {
        tour.addStep({
            title: 'Registrar Nueva Compra ➕',
            text: 'Con este botón puedes registrar una nueva compra. Al hacer clic se abrirá un formulario donde podrás ingresar la fecha, seleccionar el proveedor, agregar productos y especificar cantidades y precios.',
            attachTo: {
                element: '#btnAbrirModalNuevaCompra',
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

    // Paso 4: Tabla de compras
    tour.addStep({
        title: 'Tabla de Compras 📊',
        text: 'En esta tabla puedes ver todas las compras registradas. Cada fila muestra el número de compra, fecha, proveedor, total y estado. Las columnas son ordenables y puedes buscar compras específicas.',
        attachTo: {
            element: '#TablaCompras',
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
        text: 'En la columna de acciones de cada compra encontrarás botones para:<br><br>' +
              '• <strong>Ver detalles:</strong> Consulta la información completa de la compra<br>' +
              '• <strong>Editar:</strong> Modifica información de la compra (si tienes permisos)<br>' +
              '• <strong>Eliminar:</strong> Elimina una compra (si tienes permisos)<br><br>' +
              'Los botones disponibles dependen de tus permisos de usuario.',
        attachTo: {
            element: '#TablaCompras',
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
        text: 'Utiliza la barra de búsqueda de DataTables para encontrar compras específicas. Puedes buscar por número de compra, proveedor, fecha o cualquier otro dato visible en la tabla. También puedes ordenar las columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#TablaCompras_filter',
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
                    const filterElement = document.querySelector('#TablaCompras_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#TablaCompras_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 7: Registro de compras - información general
    tour.addStep({
        title: 'Proceso de Registro 📝',
        text: 'Al registrar una nueva compra, el sistema te guiará paso a paso:<br><br>' +
              '1. <strong>Fecha de compra:</strong> Selecciona la fecha de la transacción<br>' +
              '2. <strong>Proveedor:</strong> Busca y selecciona el proveedor<br>' +
              '3. <strong>Productos:</strong> Agrega los productos comprados con cantidades y precios<br>' +
              '4. <strong>Total:</strong> El sistema calcula automáticamente el total<br><br>' +
              'También puedes registrar nuevos proveedores si no están en el sistema.',
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

    // Paso 8: Conversión de moneda
    tour.addStep({
        title: 'Conversión de Moneda 💱',
        text: 'El sistema utiliza tasas de cambio automáticas para convertir precios entre diferentes monedas. Al seleccionar una fecha, se carga la tasa del día correspondiente. Esto permite registrar compras en la moneda original y mantener el historial preciso.',
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

    // Paso 9: Permisos
    tour.addStep({
        title: 'Sistema de Permisos 🔐',
        text: 'Los botones y funcionalidades que ves dependen de tus permisos de usuario:<br><br>' +
              '• <strong>Ver:</strong> Consultar la lista de compras<br>' +
              '• <strong>Crear:</strong> Registrar nuevas compras<br>' +
              '• <strong>Editar:</strong> Modificar compras existentes<br>' +
              '• <strong>Eliminar:</strong> Eliminar compras del sistema<br><br>' +
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
        text: 'Para aprovechar al máximo el módulo de compras:<br><br>' +
              '• Registra las compras lo antes posible para mantener el inventario actualizado<br>' +
              '• Verifica que el proveedor esté registrado antes de crear una compra<br>' +
              '• Revisa los detalles antes de guardar para evitar errores<br>' +
              '• Utiliza el buscador para encontrar compras anteriores rápidamente<br>' +
              '• Consulta el historial regularmente para análisis de gastos<br><br>' +
              '¡Ya estás listo para gestionar las compras del sistema!',
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
function agregarBotonAyudaCompras() {
    // Verificar si ya existe el botón
    if (document.querySelector('#compras-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'compras-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de compras');
    helpButton.addEventListener('click', iniciarTourCompras);
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
    agregarBotonAyudaCompras();
    
    // Esperar a que la página cargue completamente
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('compras-tour-completed');
        
        // Si es la primera vez o el usuario eliminó el registro, iniciar el tour
        if (!tourCompleted) {
            iniciarTourCompras();
        }
    }, 1000);
});

// Exponer la función globalmente por si se quiere iniciar manualmente
window.iniciarTourCompras = iniciarTourCompras;
