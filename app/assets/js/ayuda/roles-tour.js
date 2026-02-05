/**
 * Tour específico para el módulo de Roles
 * Se carga automáticamente cuando el usuario visita el módulo de roles
 */

// Función para iniciar el tour del módulo de roles
function iniciarTourRoles() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function () {
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de roles.',
                icon: 'success',
                confirmButtonText: 'Excelente'
            });
        },
        onCancel: function () {
            console.log('Tour del módulo de roles cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de roles
    tour.addStep({
        title: '¡Bienvenido al Módulo de Roles! 🛡️',
        text: 'Te guiaremos por las principales funcionalidades del módulo de roles. Aquí puedes gestionar todos los roles del sistema, crear nuevos roles y definir permisos básicos.',
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
        text: 'Aquí puedes ver el título del módulo y una descripción de lo que puedes hacer. Los roles son fundamentales para controlar el acceso a las diferentes funcionalidades del sistema.',
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

    // Paso 3: Botón crear rol (solo si existe)
    const btnCrear = document.querySelector('#btnAbrirModalRegistrarRol');
    if (btnCrear) {
        tour.addStep({
            title: 'Crear Nuevo Rol ➕',
            text: 'Con este botón puedes agregar nuevos roles al sistema. Al hacer clic se abrirá un formulario donde podrás definir el nombre, descripción y estado del rol.',
            attachTo: {
                element: '#btnAbrirModalRegistrarRol',
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

    // Paso 4: Tabla de roles
    tour.addStep({
        title: 'Lista de Roles ',
        text: 'Esta tabla muestra todos los roles registrados en el sistema. Puedes ver información como nombre del rol, descripción, estado (activo/inactivo) y fecha de creación.',
        attachTo: {
            element: '#TablaRoles',
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

    // Paso 5: Funciones de la tabla
    tour.addStep({
        title: 'Funciones de la Tabla 🔧',
        text: 'En cada fila de la tabla encontrarás botones de acción: Ver detalles, Editar información, Cambiar estado (activar/desactivar) y Eliminar rol. Estas opciones aparecen según tus permisos.',
        attachTo: {
            element: '#TablaRoles tbody',
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
    const searchInput = document.querySelector('#TablaRoles_filter input');
    if (searchInput) {
        tour.addStep({
            title: 'Búsqueda y Filtros 🔍',
            text: 'Utiliza el campo de búsqueda para encontrar roles específicos. Puedes buscar por nombre, descripción o estado del rol.',
            attachTo: {
                element: '#TablaRoles_filter',
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

    // Paso 7: Explicar formularios
    tour.addStep({
        title: 'Formularios de Rol 📝',
        text: 'Cuando crees o edites un rol, se abrirá un formulario modal donde podrás ingresar: nombre del rol, descripción detallada y establecer si está activo o inactivo.',
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

    // Paso 8: Explicar roles y permisos
    tour.addStep({
        title: 'Roles y Permisos 🔐',
        text: 'Los roles son la base del sistema de seguridad. Cada rol define qué puede hacer un usuario. Para asignar permisos específicos a cada rol, utiliza el módulo "Gestión Integral de Permisos".',
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

    // Paso 9: Explicar permisos del usuario
    tour.addStep({
        title: 'Tus Permisos 👤',
        text: 'Las funciones disponibles (crear, editar, eliminar) dependen de tus permisos de usuario. Si no ves ciertos botones o opciones, es porque tu rol no tiene acceso a esas funcionalidades.',
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

    // Paso 10: Menú lateral
    tour.addStep({
        title: 'Navegación del Sistema 🧭',
        text: 'Desde el menú lateral puedes acceder a otros módulos de seguridad: usuarios, gestión integral de permisos, módulos y bitácora. Todos estos trabajan en conjunto para la seguridad del sistema.',
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

    // Paso 11: Notificaciones (solo si existen)
    const notificationBtn = document.querySelector('#desktop-notifications-toggle');
    if (notificationBtn) {
        tour.addStep({
            title: 'Centro de Notificaciones ',
            text: 'Aquí recibirás notificaciones importantes del sistema, incluyendo alertas sobre cambios en roles, permisos y otros eventos de seguridad.',
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

    // Paso 12: Finalización
    tour.addStep({
        title: '¡Listo para Gestionar Roles! 🚀',
        text: 'Ya conoces las principales funcionalidades del módulo de roles. Recuerda que los roles son la base del sistema de permisos. Puedes volver a ejecutar este tour desde el botón de ayuda cuando lo necesites.',
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
function agregarBotonAyudaRoles() {
    // Verificar si ya existe el botón
    if (document.querySelector('#roles-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'roles-help-btn';
    helpButton.innerHTML = '<i class="fas fa-question-circle"></i>';
    helpButton.className = 'fixed bottom-6 right-6 bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-full shadow-lg z-50 transition-all duration-300 hover:scale-110';
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

    helpButton.setAttribute('title', 'Iniciar tour del módulo de roles');
    helpButton.addEventListener('click', iniciarTourRoles);
    helpButton.addEventListener('mouseenter', function () {
        this.style.transform = 'scale(1.1)';
        this.style.backgroundColor = '#15803d';
    });
    helpButton.addEventListener('mouseleave', function () {
        this.style.transform = 'scale(1)';
        this.style.backgroundColor = '#16a34a';
    });

    document.body.appendChild(helpButton);
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.location.pathname.includes('roles') && !window.location.pathname.includes('RolesIntegrado')) {
        setTimeout(function () {
            agregarBotonAyudaRoles();
        }, 500);
    }
});

window.reiniciarTourRoles = function () {
    iniciarTourRoles();
};
