/**
 * Tour específico para el módulo de Bitácora
 * Se carga automáticamente cuando el usuario visita el módulo
 */

// Función para iniciar el tour del módulo de bitácora
function iniciarTourBitacora() {
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
            localStorage.setItem('bitacora-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de bitácora.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour de bitácora cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo
    tour.addStep({
        title: '¡Bienvenido a la Bitácora del Sistema! 📝',
        text: 'Te guiaremos por las principales funcionalidades de este módulo. La bitácora registra todas las acciones importantes realizadas en el sistema para mantener un historial detallado y facilitar la auditoría.',
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
        title: 'Bitácora del Sistema 📋',
        text: 'Este módulo muestra el registro cronológico de todas las operaciones importantes realizadas en el sistema. Es una herramienta fundamental para la auditoría y seguimiento de actividades.',
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

    // Paso 3: Filtros
    tour.addStep({
        id: 'filtros',
        title: 'Filtros de Búsqueda 🔍',
        text: 'En esta sección puedes filtrar los registros de la bitácora por módulo y rango de fechas para encontrar rápidamente la información que necesitas.',
        attachTo: {
            element: '.bg-white.p-4.rounded-lg.shadow-md.mb-6',
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

    // Paso 4: Filtro por Módulo
    tour.addStep({
        id: 'filtro-modulo',
        title: 'Filtro por Módulo 📊',
        text: 'Selecciona un módulo específico para ver solo las acciones relacionadas con él. Por ejemplo, puedes filtrar para ver solo cambios en usuarios o productos.',
        attachTo: {
            element: '#filtroModulo',
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

    // Paso 5: Filtros de Fecha
    tour.addStep({
        id: 'filtro-fechas',
        title: 'Rango de Fechas 📅',
        text: 'Establece un período específico para consultar los eventos. Puedes ver actividades de hoy, de la semana pasada o de cualquier período que necesites analizar.',
        attachTo: {
            element: '#filtroFechaDesde',
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

    // Paso 6: Botones de Actualizar y Limpiar
    tour.addStep({
        id: 'botones-filtro',
        title: 'Botones de Control ⚙️',
        text: 'Usa "Actualizar" para aplicar los filtros seleccionados y "Limpiar" para restaurar todos los filtros a sus valores predeterminados.',
        attachTo: {
            element: '#btnActualizarBitacora',
            on: 'top-start'
        },
        popperOptions: {
            modifiers: [{
                name: 'offset',
                options: {
                    offset: [0, 10]
                }
            }]
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

    // Paso 7: Tabla de Registros
    tour.addStep({
        id: 'tabla-bitacora',
        title: 'Registros de Actividad 📜',
        text: 'Esta tabla muestra todas las actividades registradas en el sistema. Puedes ver detalles como el tipo de acción, el usuario que la realizó, el módulo afectado y la fecha.',
        attachTo: {
            element: '#TablaBitacora',
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

    // Paso 8: Botón de Estadísticas
    const btnEstadisticas = document.querySelector('#btnEstadisticas');
    if (btnEstadisticas) {
        tour.addStep({
            id: 'btn-estadisticas',
            title: 'Estadísticas 📊',
            text: 'Este botón abre un resumen visual de la actividad del sistema. Podrás ver gráficos que muestran las acciones más frecuentes, los módulos más activos y otros datos útiles para el análisis.',
            attachTo: {
                element: '#btnEstadisticas',
                on: 'left'
            },
            popperOptions: {
                modifiers: [{
                    name: 'offset',
                    options: {
                        offset: [0, 10]
                    }
                }]
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

    // Paso 9: Botón de Limpiar Bitácora (si existe)
    const btnLimpiarBitacora = document.querySelector('#btnLimpiarBitacora');
    if (btnLimpiarBitacora) {
        tour.addStep({
            id: 'btn-limpiar',
            title: 'Limpiar Registros Antiguos 🗑️',
            text: 'Si tienes permisos adecuados, este botón te permite eliminar registros antiguos para mantener la bitácora optimizada. Esta acción no puede deshacerse, así que úsala con precaución.',
            attachTo: {
                element: '#btnLimpiarBitacora',
                on: 'bottom-end'
            },
            popperOptions: {
                modifiers: [{
                    name: 'offset',
                    options: {
                        offset: [20, 10]
                    }
                }]
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

    // Paso 10: Modal de Detalles
    tour.addStep({
        id: 'modal-detalles',
        title: 'Detalles del Registro 🔎',
        text: 'Al hacer clic en "Ver" en cualquier registro, se abrirá una ventana con información detallada sobre esa acción, incluyendo datos específicos sobre qué se modificó.',
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

    // Paso 11: Consejos de uso
    tour.addStep({
        title: 'Consejos para el Uso de la Bitácora 💡',
        text: '<ul class="list-disc pl-5 text-left">' +
              '<li class="mb-2">Consulta la bitácora regularmente para monitorear actividades inusuales.</li>' +
              '<li class="mb-2">Utiliza los filtros para encontrar información específica rápidamente.</li>' +
              '<li class="mb-2">Las estadísticas son útiles para identificar tendencias de uso del sistema.</li>' +
              '<li>Si necesitas guardar un registro importante, usa la opción de exportar en la vista detallada.</li>' +
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
function agregarBotonAyudaBitacora() {
    // Verificar si ya existe el botón
    if (document.querySelector('#bitacora-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'bitacora-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de bitácora del sistema');
    helpButton.addEventListener('click', iniciarTourBitacora);
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
    // Verificar si estamos en el módulo de bitácora
    if (window.location.pathname.includes('bitacora')) {
        
        // Verificar si el usuario tiene permisos para ver la bitácora
        const permisoVer = document.querySelector('#permisoVer');
        if (permisoVer && permisoVer.value === '0') {
            // El usuario no tiene permiso, no mostrar el tour
            return;
        }
        
        // Esperar a que todos los elementos se carguen completamente
        setTimeout(function() {
            // Agregar botón de ayuda
            agregarBotonAyudaBitacora();
            
            // Verificar si el sistema está listo (tabla cargada)
            function verificarSistemaListo() {
                const tabla = document.querySelector('#TablaBitacora');
                return tabla && tabla.querySelector('tbody tr');
            }
            
            // Función para verificar y mostrar el tour
            function mostrarTourSiNecesario() {
                if (verificarSistemaListo()) {
                    // Auto-iniciar tour solo la primera vez
                    if (!localStorage.getItem('bitacora-tour-completed')) {
                        // Preguntar si quiere hacer el tour
                        Swal.fire({
                            title: '¿Quieres hacer un tour del módulo?',
                            text: 'Te mostramos las principales funcionalidades de la bitácora del sistema',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, empezar tour',
                            cancelButtonText: 'Ahora no',
                            confirmButtonColor: '#16a34a',
                            cancelButtonColor: '#6b7280'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                iniciarTourBitacora();
                            } else {
                                // Si dice que no, marcar como completado para no molestarlo más
                                localStorage.setItem('bitacora-tour-completed', 'true');
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
window.reiniciarTourBitacora = function() {
    localStorage.removeItem('bitacora-tour-completed');
    iniciarTourBitacora();
};
