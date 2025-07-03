/**
 * Tour específico para el módulo de Módulos
 * Se carga automáticamente cuando el usuario visita el módulo
 */

// Función para iniciar el tour del módulo de módulos
function iniciarTourModulos() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            scrollToHandler: function(el) {
                // Mejorar el scroll para asegurar que el elemento esté bien visible
                if (el) {
                    // Calcular la posición para centrar el elemento en la ventana visible
                    const rect = el.getBoundingClientRect();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const elementTop = rect.top + scrollTop;
                    const offset = 150; // Espacio adicional para ver mejor el elemento
                    
                    // Hacer scroll con comportamiento suave
                    window.scrollTo({
                        top: elementTop - offset,
                        behavior: 'smooth'
                    });
                    
                    // Resaltar temporalmente el elemento para mejor visibilidad
                    el.style.transition = 'box-shadow 0.3s ease-in-out';
                    el.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.5)';
                    setTimeout(() => {
                        el.style.boxShadow = 'none';
                    }, 1500);
                }
            },
            cancelIcon: {
                enabled: true
            },
            // Hacer que los tooltips sean arrastrables
            floatingUIOptions: {
                middleware: [{
                    name: 'draggable',
                    options: {
                        draggable: true,
                        getDragContainer: () => document.body
                    }
                }]
            },
            // Añadir un texto para indicar que se puede arrastrar
            when: {
                show: function() {
                    // Añadir indicador de arrastrable después de mostrar el paso
                    setTimeout(() => {
                        const currentStep = tour.currentStep;
                        if (currentStep && currentStep.el) {
                            const header = currentStep.el.querySelector('.shepherd-header');
                            
                            if (header && !header.querySelector('.drag-indicator')) {
                                const dragIndicator = document.createElement('div');
                                dragIndicator.className = 'drag-indicator';
                                dragIndicator.innerHTML = '<i class="fas fa-arrows-alt"></i>';
                                dragIndicator.style.cssText = `
                                    font-size: 12px;
                                    color: #888;
                                    margin-left: 10px;
                                    cursor: move;
                                `;
                                dragIndicator.title = "Arrastrar para mover";
                                
                                if (header.querySelector('.shepherd-cancel-icon')) {
                                    header.insertBefore(dragIndicator, header.querySelector('.shepherd-cancel-icon'));
                                } else {
                                    header.appendChild(dragIndicator);
                                }
                            }
                        }
                    }, 100);
                }
            }
        },
        onComplete: function() {
            localStorage.setItem('modulos-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades de la gestión de módulos del sistema.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour de módulos cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo
    tour.addStep({
        title: '¡Bienvenido a la Gestión de Módulos! 🧩',
        text: 'Te guiaremos por las principales funcionalidades de este módulo. Aquí puedes administrar los módulos del sistema, que representan las diferentes funcionalidades y secciones disponibles.',
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
        id: 'titulo-modulo',
        title: 'Gestión de Módulos 📋',
        text: 'En esta sección puedes visualizar y administrar todos los módulos del sistema. Los módulos son componentes esenciales que representan cada funcionalidad disponible para los usuarios según sus permisos.',
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

    // Paso 3: Botón Registrar Módulo
    tour.addStep({
        id: 'registrar-modulo',
        title: 'Registrar Nuevo Módulo 📝',
        text: 'Con este botón puedes crear un nuevo módulo en el sistema. Recuerda que para crear un módulo, el controlador correspondiente debe existir previamente en la carpeta de controladores.',
        attachTo: {
            element: '#btnAbrirModalRegistrarModulo',
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

    // Paso 4: Botón Ver Controladores
    tour.addStep({
        id: 'ver-controladores',
        title: 'Ver Controladores Disponibles 🔍',
        text: 'Este botón muestra los controladores existentes en el sistema que aún no han sido registrados como módulos. Es útil para identificar qué funcionalidades pueden añadirse al sistema.',
        attachTo: {
            element: '#btnVerControladores',
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

    // Paso 5: Tabla de Módulos
    tour.addStep({
        id: 'tabla-modulos',
        title: 'Listado de Módulos 📊',
        text: 'En esta tabla se muestran todos los módulos registrados en el sistema. Puedes ver información como el título, descripción, estado y opciones para gestionar cada módulo.',
        attachTo: {
            element: '#TablaModulos',
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

    // Paso 6: Opciones de la tabla
    tour.addStep({
        id: 'opciones-tabla',
        title: 'Opciones de Gestión ⚙️',
        text: 'En cada fila de la tabla encontrarás opciones para: <br>' +
              '<ul class="list-disc pl-5 text-left">' +
              '<li class="mb-1"><b>Ver:</b> Consulta los detalles completos de un módulo.</li>' +
              '<li class="mb-1"><b>Editar:</b> Modifica la información de un módulo existente.</li>' +
              '<li class="mb-1"><b>Activar/Desactivar:</b> Cambia el estado del módulo en el sistema.</li>' +
              '</ul>',
        attachTo: {
            element: '#TablaModulos',
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

    // Paso 7: Modal de Registro
    tour.addStep({
        id: 'modal-registro',
        title: 'Formulario de Registro ✏️',
        text: 'Al hacer clic en "Registrar Módulo", se abre este formulario donde puedes introducir el título y la descripción del nuevo módulo. Recuerda que el título debe coincidir con el nombre del controlador existente.',
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
        beforeShowPromise: function() {
            return new Promise(function(resolve) {
                // Verificamos si el modal ya está abierto
                const modal = document.querySelector('#modalRegistrarModulo');
                if (modal && !modal.classList.contains('opacity-0')) {
                    // El modal ya está abierto
                    resolve();
                } else {
                    // Abrimos el modal para mostrar su contenido
                    const btnAbrirModal = document.querySelector('#btnAbrirModalRegistrarModulo');
                    if (btnAbrirModal) {
                        btnAbrirModal.click();
                        // Esperamos a que se abra el modal
                        setTimeout(() => {
                            resolve();
                        }, 500);
                    } else {
                        resolve();
                    }
                }
            });
        }
    });

    // Paso 8: Importancia del Controlador
    tour.addStep({
        id: 'controlador-info',
        title: 'Controlador Asociado ⚠️',
        text: 'Es fundamental que antes de registrar un módulo, exista un controlador correspondiente en el sistema. El título del módulo debe coincidir exactamente con el nombre del controlador (sin la extensión .php).',
        attachTo: {
            element: '.bg-blue-50',
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
        ],
        beforeShowPromise: function() {
            return new Promise(function(resolve) {
                // Verificamos si hay un elemento de alerta azul visible
                const alert = document.querySelector('.bg-blue-50');
                if (alert && window.getComputedStyle(alert).display !== 'none') {
                    resolve();
                } else {
                    // Si no hay alerta visible, seguimos adelante
                    resolve();
                }
            });
        }
    });

    // Paso 9: Cerrar Modal
    tour.addStep({
        id: 'cerrar-modal',
        title: 'Cerrar Formulario ❌',
        text: 'Para cerrar este formulario sin guardar cambios, puedes usar el botón "Cancelar" o hacer clic en la X en la esquina superior derecha.',
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Siguiente',
                action: function() {
                    // Cerrar el modal si está abierto
                    const btnCancelar = document.querySelector('#btnCancelarModalRegistrar');
                    if (btnCancelar) {
                        btnCancelar.click();
                    }
                    setTimeout(() => {
                        tour.next();
                    }, 300);
                },
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Paso 10: Consejos finales
    tour.addStep({
        title: 'Consejos para la Gestión de Módulos 💡',
        text: '<ul class="list-disc pl-5 text-left">' +
              '<li class="mb-2">Los módulos activos estarán disponibles para asignar permisos en los roles.</li>' +
              '<li class="mb-2">Mantén nombres descriptivos para facilitar la asignación de permisos.</li>' +
              '<li class="mb-2">Revisa periódicamente la lista de controladores disponibles para mantener el sistema actualizado.</li>' +
              '<li>Los módulos desactivados no serán accesibles para ningún usuario, independientemente de sus permisos.</li>' +
              '</ul>',
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
function agregarBotonAyudaModulos() {
    // Verificar si ya existe el botón
    if (document.querySelector('#modulos-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'modulos-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de gestión de módulos');
    helpButton.addEventListener('click', iniciarTourModulos);
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
            @keyframes pulse {
                0% {transform: scale(1);}
                50% {transform: scale(1.05);}
                100% {transform: scale(1);}
            }
        `;
        document.head.appendChild(styleEl);
    }
    
    document.body.appendChild(helpButton);
}

// Auto-inicializar cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en el módulo de módulos
    if (window.location.pathname.includes('modulos')) {
        
        // Esperar a que todos los elementos se carguen completamente
        setTimeout(function() {
            // Agregar botón de ayuda
            agregarBotonAyudaModulos();
            
            // Verificar si el sistema está listo (tabla cargada)
            function verificarSistemaListo() {
                const tabla = document.querySelector('#TablaModulos');
                return tabla && tabla.querySelector('tbody tr');
            }
            
            // Función para verificar y mostrar el tour
            function mostrarTourSiNecesario() {
                if (verificarSistemaListo()) {
                    // Auto-iniciar tour solo la primera vez
                    if (!localStorage.getItem('modulos-tour-completed')) {
                        // Preguntar si quiere hacer el tour
                        Swal.fire({
                            title: '¿Quieres hacer un tour del módulo?',
                            text: 'Te mostramos las principales funcionalidades de la gestión de módulos',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, empezar tour',
                            cancelButtonText: 'Ahora no',
                            confirmButtonColor: '#16a34a',
                            cancelButtonColor: '#6b7280'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                iniciarTourModulos();
                            } else {
                                // Si dice que no, marcar como completado para no molestarlo más
                                localStorage.setItem('modulos-tour-completed', 'true');
                            }
                        });
                    }
                } else {
                    // Si aún no está listo, intentar de nuevo en un momento
                    setTimeout(mostrarTourSiNecesario, 500);
                }
            }
            
            // Iniciar el proceso de verificación
            setTimeout(mostrarTourSiNecesario, 1000);
            
        }, 700); // Un poco más de tiempo para cargar los componentes dinámicos
    }
});

// También agregar función global para reiniciar el tour
window.reiniciarTourModulos = function() {
    localStorage.removeItem('modulos-tour-completed');
    iniciarTourModulos();
};
