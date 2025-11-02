/**
 * Ejemplo de uso de Shepherd.js en el proyecto
 * Coloca este código en tu archivo JavaScript específico de la página
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
        }
    });

    // Paso 1: Bienvenida
    tour.addStep({
        title: '¡Bienvenido al Dashboard!',
        text: 'Te guiaremos por las principales funcionalidades del sistema.',
        buttons: [
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 2: Sidebar
    tour.addStep({
        title: 'Menú de Navegación',
        text: 'Aquí puedes acceder a todos los módulos del sistema.',
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

    // Paso 3: Notificaciones
    tour.addStep({
        title: 'Notificaciones',
        text: 'Aquí recibirás alertas importantes del sistema.',
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
                text: 'Finalizar',
                action: tour.complete,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Iniciar el tour
    tour.start();
}

// Función para tour de productos
function iniciarTourProductos() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true
        }
    });

    tour.addStep({
        title: 'Gestión de Productos',
        text: 'Desde aquí puedes agregar, editar y eliminar productos.',
        attachTo: {
            element: '[href*="productos"]',
            on: 'right'
        },
        buttons: [
            {
                text: 'Entendido',
                action: tour.complete,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    tour.start();
}

// Función para tour de formularios
function iniciarTourFormulario() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true
    });

    // Ejemplo para formularios
    tour.addStep({
        title: 'Llenar Formulario',
        text: 'Completa todos los campos requeridos marcados con asterisco (*).',
        attachTo: {
            element: 'form',
            on: 'top'
        },
        buttons: [
            {
                text: 'Siguiente',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    tour.addStep({
        title: 'Guardar Cambios',
        text: 'No olvides hacer clic en "Guardar" para conservar los cambios.',
        attachTo: {
            element: 'button[type="submit"]',
            on: 'top'
        },
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

// Ejemplo de tour con callbacks personalizados
function tourAvanzado() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        onComplete: function() {
            // Marcar que el usuario completó el tour
            localStorage.setItem('dashboard-tour-completed', 'true');
            console.log('Tour completado!');
        },
        onCancel: function() {
            console.log('Tour cancelado');
        }
    });

    tour.addStep({
        title: 'Tour Interactivo',
        text: 'Este tour guarda tu progreso y puede personalizar la experiencia.',
        when: {
            show: function() {
                console.log('Mostrando paso...');
                // Puedes ejecutar código personalizado aquí
            }
        },
        buttons: [
            {
                text: 'Comenzar',
                action: tour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    tour.start();
}

// Auto-iniciar tour si es la primera vez
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si es la primera visita al dashboard
    if (!localStorage.getItem('dashboard-tour-completed') && 
        window.location.pathname.includes('dashboard')) {
        
        // Esperar un poco para que se cargue completamente la página
        setTimeout(function() {
            if (confirm('¿Te gustaría hacer un tour por el sistema?')) {
                iniciarTourDashboard();
            }
        }, 1000);
    }
});

// Ejemplo de botón para iniciar tours manualmente
// Agregar estos botones en tus vistas HTML:
/*
<button onclick="iniciarTourDashboard()" class="btn btn-info">
    <i class="fas fa-question-circle"></i> Tour del Dashboard
</button>

<button onclick="iniciarTourProductos()" class="btn btn-info">
    <i class="fas fa-info-circle"></i> Ayuda Productos
</button>
*/
