/**
 * Tour específico para el módulo de Usuarios
 * Se carga automáticamente cuando el usuario visita el módulo de usuarios
 */

// Función para iniciar el tour del módulo de usuarios
function iniciarTourUsuarios() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('usuarios-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de usuarios.',
                icon: 'success',
                confirmButtonText: 'Excelente'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de usuarios cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de usuarios
    tour.addStep({
        title: '¡Bienvenido al Módulo de Usuarios! 👥',
        text: 'Te guiaremos por las principales funcionalidades del módulo de usuarios. Aquí puedes gestionar todos los usuarios del sistema, crear nuevos usuarios, editar información y asignar roles.',
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
        text: 'Aquí puedes ver el título del módulo y una descripción de lo que puedes hacer. Siempre encontrarás información útil sobre la sección actual.',
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

    // Paso 3: Botón crear usuario (solo si existe)
    const btnCrear = document.querySelector('#btnAbrirModalRegistrarUsuario');
    if (btnCrear) {
        tour.addStep({
            title: 'Crear Nuevo Usuario ➕',
            text: 'Con este botón puedes agregar nuevos usuarios al sistema. Al hacer clic se abrirá un formulario donde podrás ingresar toda la información necesaria.',
            attachTo: {
                element: '#btnAbrirModalRegistrarUsuario',
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

    // Paso 3.5: Botón exportar usuarios (solo si existe)
    const btnExportar = document.querySelector('#btnExportarUsuarios');
    if (btnExportar) {
        tour.addStep({
            title: 'Exportar Datos 📄',
            text: 'Con este botón puedes exportar la lista de usuarios a diferentes formatos como PDF o Excel para reportes o respaldos.',
            attachTo: {
                element: '#btnExportarUsuarios',
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

    // Paso 4: Tabla de usuarios
    tour.addStep({
        title: 'Lista de Usuarios 📊',
        text: 'Esta tabla muestra todos los usuarios registrados en el sistema. Puedes ver información como nombre, email, rol, estado y fecha de registro. También puedes buscar, filtrar y ordenar los usuarios.',
        attachTo: {
            element: '#TablaUsuarios',
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
        text: 'En cada fila de la tabla encontrarás botones de acción: Ver detalles, Editar información, Cambiar estado (activar/desactivar) y Eliminar usuario. Estas opciones aparecen según tus permisos.',
        attachTo: {
            element: '#TablaUsuarios tbody',
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
    const searchInput = document.querySelector('#TablaUsuarios_filter input');
    if (searchInput) {
        tour.addStep({
            title: 'Búsqueda y Filtros 🔍',
            text: 'Utiliza el campo de búsqueda para encontrar usuarios específicos. Puedes buscar por nombre, email, rol o cualquier información visible en la tabla.',
            attachTo: {
                element: '#TablaUsuarios_filter',
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

    // Paso 6.5: Explicar formularios
    tour.addStep({
        title: 'Formularios de Usuario 📝',
        text: 'Cuando crees o edites un usuario, se abrirá un formulario modal donde podrás ingresar datos como: nombre de usuario, correo electrónico, contraseña, rol asignado y persona asociada (opcional).',
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

    // Paso 6.8: Explicar permisos
    tour.addStep({
        title: 'Sistema de Permisos 🔐',
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

    // Paso 7: Menú lateral
    tour.addStep({
        title: 'Navegación del Sistema 🧭',
        text: 'Desde el menú lateral puedes acceder a otros módulos del sistema. El módulo de usuarios está dentro de la sección "Seguridad" junto con roles, permisos y bitácora.',
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

    // Paso 8: Notificaciones (solo si existen)
    const notificationBtn = document.querySelector('#desktop-notifications-toggle');
    if (notificationBtn) {
        tour.addStep({
            title: 'Centro de Notificaciones 🔔',
            text: 'Aquí recibirás notificaciones importantes del sistema, incluyendo alertas sobre actividades de usuarios, intentos de acceso y otros eventos de seguridad.',
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

    // Paso 9: Finalización
    tour.addStep({
        title: '¡Listo para Gestionar Usuarios! 🚀',
        text: 'Ya conoces las principales funcionalidades del módulo de usuarios. Recuerda que puedes crear, editar y gestionar usuarios según tus permisos. Puedes volver a ejecutar este tour desde el botón de ayuda cuando lo necesites.',
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
function agregarBotonAyudaUsuarios() {
    // Verificar si ya existe el botón
    if (document.querySelector('#usuarios-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'usuarios-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour del módulo de usuarios');
    helpButton.addEventListener('click', iniciarTourUsuarios);
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
    // Verificar si estamos en el módulo de usuarios
    if (window.location.pathname.includes('usuarios')) {
        
        // Esperar a que todos los elementos se carguen completamente
        setTimeout(function() {
            // Agregar botón de ayuda
            agregarBotonAyudaUsuarios();
            
            // Auto-iniciar tour solo la primera vez
            if (!localStorage.getItem('usuarios-tour-completed')) {
                setTimeout(function() {
                    // Preguntar si quiere hacer el tour
                    Swal.fire({
                        title: '¿Quieres hacer un tour del módulo?',
                        text: 'Te mostramos las principales funcionalidades del módulo de usuarios',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, empezar tour',
                        cancelButtonText: 'Ahora no',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#6b7280'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            iniciarTourUsuarios();
                        } else {
                            // Si dice que no, marcar como completado para no molestarlo más
                            localStorage.setItem('usuarios-tour-completed', 'true');
                        }
                    });
                }, 1000);
            }
        }, 500);
    }
});

// También agregar función global para reiniciar el tour
window.reiniciarTourUsuarios = function() {
    localStorage.removeItem('usuarios-tour-completed');
    iniciarTourUsuarios();
};
