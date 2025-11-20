/**
 * Tour específico para el módulo de Tasas
 * Se carga automáticamente cuando el usuario visita el módulo de tasas
 */

// Función para iniciar el tour del módulo de tasas
function iniciarTourTasas() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('tasas-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de tasas.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de tasas cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de tasas
    tour.addStep({
        title: '¡Bienvenido al Módulo de Tasas! 💱',
        text: 'Te guiaremos por las principales funcionalidades del módulo de tasas de cambio. Aquí puedes consultar y actualizar las tasas oficiales del Banco Central de Venezuela (BCV) para realizar conversiones precisas de moneda.',
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
        title: 'Historial de Tasas de Cambio 📋',
        text: 'Este módulo muestra las tasas oficiales del BCV. Es fundamental para todas las operaciones que involucren conversión de monedas en el sistema, como compras, ventas y cálculo de inventario.',
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

    // Paso 3: Botón actualizar tasas
    const btnActualizar = document.querySelector('#moneda');
    if (btnActualizar) {
        tour.addStep({
            title: 'Actualizar Tasas 🔄',
            text: 'Con este botón puedes actualizar las tasas de cambio obteniendo la información más reciente del BCV. Es importante mantener las tasas actualizadas para que los cálculos del sistema sean precisos.',
            attachTo: {
                element: '#formActualizarUSD',
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

    // Paso 4: Pestañas de monedas
    const tabsHistorial = document.querySelector('#tabsHistorial');
    if (tabsHistorial) {
        tour.addStep({
            title: 'Selección de Moneda 💵💶',
            text: 'Utiliza estas pestañas para cambiar entre las diferentes monedas disponibles:<br><br>' +
                  '• <strong>Dólar ($):</strong> Tasas USD a Bolívares<br>' +
                  '• <strong>Euro (€):</strong> Tasas EUR a Bolívares<br><br>' +
                  'Cada pestaña muestra el historial completo de tasas para esa moneda.',
            attachTo: {
                element: '#tabsHistorial',
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

    // Paso 5: Tabla de tasas
    tour.addStep({
        title: 'Tabla de Tasas 📊',
        text: 'Esta tabla muestra el historial de tasas de cambio. Cada fila incluye:<br><br>' +
              '• <strong>Código:</strong> Código de la moneda (USD, EUR)<br>' +
              '• <strong>Tasa a VES:</strong> Valor de conversión a Bolívares<br>' +
              '• <strong>Fecha Publicación BCV:</strong> Fecha oficial del BCV<br>' +
              '• <strong>Fecha Captura:</strong> Cuándo se obtuvo en el sistema<br><br>' +
              'Las tasas están ordenadas de la más reciente a la más antigua.',
        attachTo: {
            element: '#historialUSD',
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

    // Paso 6: Importancia de las tasas
    tour.addStep({
        title: 'Importancia de las Tasas 🎯',
        text: 'Las tasas de cambio son cruciales porque:<br><br>' +
              '• Se usan automáticamente en el módulo de <strong>Compras</strong><br>' +
              '• Permiten registrar precios en moneda extranjera<br>' +
              '• Facilitan la conversión para reportes y análisis<br>' +
              '• Mantienen el historial de variación de precios<br>' +
              '• Ayudan en la toma de decisiones financieras<br><br>' +
              'Sin tasas actualizadas, los cálculos pueden no ser precisos.',
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

    // Paso 7: Actualización automática
    tour.addStep({
        title: 'Actualización de Tasas 🔄',
        text: 'El sistema puede obtener las tasas directamente del BCV:<br><br>' +
              '• Haz clic en el botón "Actualizar Tasas"<br>' +
              '• El sistema consulta la página del BCV<br>' +
              '• Se guarda automáticamente la tasa más reciente<br>' +
              '• Se mantiene el historial de todas las tasas<br><br>' +
              '<strong>Nota:</strong> La obtención depende de que el sitio del BCV esté disponible y mantenga su estructura.',
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

    // Paso 8: Uso en otros módulos
    tour.addStep({
        title: 'Integración con Otros Módulos 🔗',
        text: 'Las tasas se integran automáticamente con:<br><br>' +
              '• <strong>Compras:</strong> Al registrar una compra, se usa la tasa del día<br>' +
              '• <strong>Productos:</strong> Para calcular costos en diferentes monedas<br>' +
              '• <strong>Reportes:</strong> Para análisis financieros precisos<br>' +
              '• <strong>Dashboard:</strong> Para mostrar valores actualizados<br><br>' +
              'El sistema selecciona automáticamente la tasa apropiada según la fecha.',
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

    // Paso 9: Consejos finales
    tour.addStep({
        title: 'Consejos y Buenas Prácticas 💡',
        text: 'Para aprovechar al máximo el módulo de tasas:<br><br>' +
              '• Actualiza las tasas regularmente (diariamente si es posible)<br>' +
              '• Verifica que la tasa se actualizó correctamente después de cada actualización<br>' +
              '• Revisa el historial para analizar tendencias de cambio<br>' +
              '• Si hay problemas de conexión con el BCV, puedes registrar tasas manualmente<br>' +
              '• Mantén un respaldo del historial de tasas para auditorías<br><br>' +
              '¡Ya estás listo para gestionar las tasas de cambio!',
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
function agregarBotonAyudaTasas() {
    if (document.querySelector('#tasas-help-btn')) {
        return;
    }

    const helpButton = document.createElement('button');
    helpButton.id = 'tasas-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de tasas');
    helpButton.addEventListener('click', iniciarTourTasas);
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
    agregarBotonAyudaTasas();
    
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('tasas-tour-completed');
        if (!tourCompleted) {
            iniciarTourTasas();
        }
    }, 1000);
});

window.iniciarTourTasas = iniciarTourTasas;
