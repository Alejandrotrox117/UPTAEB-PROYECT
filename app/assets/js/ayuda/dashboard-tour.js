/**
 * Tour específico para el Dashboard
 * Se carga automáticamente cuando el usuario visita el dashboard
 */

// Función para iniciar el tour del dashboard
function iniciarTourDashboard() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('dashboard-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del dashboard.',
                icon: 'success',
                confirmButtonText: 'Excelente'
            });
        },
        onCancel: function() {
            console.log('Tour del dashboard cancelado');
        }
    });

    // Paso 1: Bienvenida al Dashboard
    tour.addStep({
        title: '¡Bienvenido al Dashboard! 🎉',
        text: 'Te guiaremos por las principales funcionalidades de tu panel de control. Este tour te ayudará a navegar y entender mejor el sistema.',
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

    // Paso 2: Saludo personalizado
    tour.addStep({
        title: 'Tu Área Personalizada',
        text: 'Aquí puedes ver tu saludo personalizado y la fecha actual. El sistema te reconoce automáticamente.',
        attachTo: {
            element: '#dashboard-header',
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

    // Paso 3: Tarjetas de métricas
    tour.addStep({
        title: 'Métricas Principales 📊',
        text: 'Estas tarjetas muestran un resumen de las métricas más importantes: ventas del día, compras, inventario y producciones activas. Se actualizan en tiempo real.',
        attachTo: {
            element: '#dashboard-metrics',
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

    // Paso 4: Gráficos de reportes
    tour.addStep({
        title: 'Reportes Visuales 📈',
        text: 'Aquí puedes ver gráficos detallados de ingresos y egresos. Puedes filtrar por fechas y tipos de pago para análisis específicos.',
        attachTo: {
            element: '#dashboard-reports',
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

    // Paso 5: Menú lateral
    tour.addStep({
        title: 'Menú de Navegación 🧭',
        text: 'Desde este menú puedes acceder a todos los módulos del sistema: compras, ventas, productos, empleados, reportes y más.',
        attachTo: {
            element: '#sidebar',
            on: 'right'
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

    // Paso 6: Notificaciones (solo si existen)
    const notificationBtn = document.querySelector('#desktop-notifications-toggle');
    if (notificationBtn) {
        tour.addStep({
            title: 'Centro de Notificaciones 🔔',
            text: 'Aquí recibirás alertas importantes del sistema: productos con stock bajo, tareas pendientes, y actualizaciones importantes.',
            attachTo: {
                element: '#desktop-notifications-toggle',
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

    // Paso 7: Tabla de KPIs
    tour.addStep({
        title: 'Indicadores Clave (KPIs) 📋',
        text: 'Esta tabla muestra los indicadores más importantes para el seguimiento del negocio. Se actualiza automáticamente con datos en tiempo real.',
        attachTo: {
            element: '#dashboard-kpis',
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

    // Paso 8: Finalización
    tour.addStep({
        title: '¡Listo para Empezar! 🚀',
        text: 'Ya conoces las principales funcionalidades del dashboard. Puedes volver a ejecutar este tour desde el botón de ayuda cuando lo necesites.',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Finalizar Tour',
                action: tour.complete,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Iniciar el tour
    tour.start();
}

// Función para mostrar el botón de ayuda
function agregarBotonAyuda() {
    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'dashboard-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour del dashboard');
    helpButton.addEventListener('click', iniciarTourDashboard);
    helpButton.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.backgroundColor = '#15803d';
    });
    helpButton.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.backgroundColor = '#16a34a';
    });
    
    document.body.appendChild(helpButton);
}

// Auto-inicializar cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en el dashboard
    if (window.location.pathname.includes('dashboard')) {
        
        // Esperar a que todos los elementos se carguen completamente
        setTimeout(function() {
            // Agregar botón de ayuda
            agregarBotonAyuda();
            
            // Auto-iniciar tour solo la primera vez
            if (!localStorage.getItem('dashboard-tour-completed')) {
                setTimeout(function() {
                    // Preguntar si quiere hacer el tour
                    Swal.fire({
                        title: '¿Quieres hacer un tour?',
                        text: 'Te mostramos las principales funcionalidades del dashboard',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, empezar tour',
                        cancelButtonText: 'Ahora no',
                        confirmButtonColor: '#16a34a',
                        cancelButtonColor: '#6b7280'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            iniciarTourDashboard();
                        } else {
                            // Si dice que no, marcar como completado para no molestarlo más
                            localStorage.setItem('dashboard-tour-completed', 'true');
                        }
                    });
                }, 1000); // Reducir el tiempo de espera
            }
        }, 500); // Esperar menos tiempo para mejor experiencia
    }
});

// También agregar función global para reiniciar el tour
window.reiniciarTourDashboard = function() {
    localStorage.removeItem('dashboard-tour-completed');
    iniciarTourDashboard();
};
