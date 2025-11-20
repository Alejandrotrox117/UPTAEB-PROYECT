/**
 * Tour específico para el módulo de Gestión Integral de Permisos (RolesIntegrado)
 * Se carga automáticamente cuando el usuario visita el módulo
 */

// Función para iniciar el tour del módulo de roles integrado
function iniciarTourRolesIntegrado() {
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
            localStorage.setItem('rolesintegrado-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades de la gestión integral de permisos.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour de gestión integral de permisos cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo
    tour.addStep({
        title: '¡Bienvenido a la Gestión Integral de Permisos! 🛡️',
        text: 'Te guiaremos por las principales funcionalidades de este módulo. Aquí puedes configurar de forma detallada qué módulos y acciones específicas puede realizar cada rol en el sistema.',
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
        title: 'Gestión Integral de Permisos 📋',
        text: 'Esta herramienta te permite configurar los permisos del sistema con máximo detalle. A diferencia del módulo básico de roles, aquí puedes asignar permisos específicos por cada acción dentro de cada módulo.',
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

    // Paso 3: Selector de roles con acción interactiva
    tour.addStep({
        id: 'seleccion-rol',
        title: 'Selección de Rol 👥',
        text: 'Primero debes seleccionar un rol para configurar sus permisos. En este desplegable encontrarás todos los roles disponibles en el sistema. <br><br><b>¡Acción requerida!</b> Por favor, selecciona un rol del desplegable para continuar y ver las opciones de configuración.',
        attachTo: {
            element: '#selectRol',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Anterior',
                action: tour.back,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Ya seleccioné un rol',
                action: function() {
                    // Verificar si se ha seleccionado un rol
                    const selectRol = document.querySelector('#selectRol');
                    const selectedRole = selectRol.value;
                    
                    if (selectedRole && selectedRole !== "") {
                        // Si ya hay un rol seleccionado, continuar
                        // Añadir un pequeño retraso para que la interfaz se actualice
                        setTimeout(() => {
                            tour.next();
                        }, 800);
                    } else {
                        // Si no hay rol seleccionado, mostrar mensaje
                        Swal.fire({
                            title: 'Selecciona un rol',
                            text: 'Por favor, selecciona un rol para continuar con el tour',
                            icon: 'info',
                            confirmButtonColor: '#16a34a'
                        });
                        
                        // Resaltar el selector para llamar la atención
                        selectRol.style.transition = 'box-shadow 0.3s ease-in-out';
                        selectRol.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.5)';
                        setTimeout(() => {
                            selectRol.style.boxShadow = 'none';
                        }, 2000);
                    }
                },
                classes: 'shepherd-button-primary'
            }
        ],
        beforeShowPromise: function() {
            return new Promise(function(resolve) {
                // Destacar visualmente el selector
                const selectRol = document.querySelector('#selectRol');
                if (selectRol) {
                    selectRol.style.transition = 'box-shadow 0.3s ease-in-out';
                    selectRol.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.5)';
                    
                    // Añadir un event listener para detectar cuando el usuario selecciona un rol
                    const originalOnChange = selectRol.onchange;
                    selectRol.onchange = function(e) {
                        // Ejecutar el comportamiento original primero
                        if (originalOnChange) originalOnChange.call(this, e);
                        
                        // Después actualizar el botón del paso actual
                        if (tour.getCurrentStep() && tour.getCurrentStep().id === 'seleccion-rol') {
                            const selectedValue = this.value;
                            if (selectedValue && selectedValue !== "") {
                                // Destacar el botón de "Ya seleccioné un rol" para indicar que puede continuar
                                const nextButton = document.querySelector('.shepherd-button-primary');
                                if (nextButton) {
                                    nextButton.style.animation = 'pulse 1s infinite';
                                    nextButton.style.backgroundColor = '#15803d';
                                }
                            }
                        }
                    };
                    
                    setTimeout(() => {
                        selectRol.style.boxShadow = 'none';
                    }, 2000);
                }
                resolve();
            });
        }
    });

    // Paso 4: Resumen de asignaciones
    tour.addStep({
        id: 'resumen-asignaciones',
        title: 'Resumen de Asignaciones ',
        text: 'Este panel muestra un resumen de los permisos asignados al rol seleccionado: el nombre del rol, cuántos módulos tiene acceso y cuántos permisos específicos están configurados.',
        attachTo: {
            element: '#resumenContainer',
            on: 'top'
        },
        beforeShowPromise: function() {
            return new Promise(function(resolve) {
                // Verificamos en tiempo real si el contenedor está visible
                const container = document.querySelector('#resumenContainer');
                
                // Si el contenedor está oculto, esperamos hasta 3 segundos a que aparezca
                if (container.classList.contains('hidden')) {
                    let attempts = 0;
                    const checkVisibility = setInterval(() => {
                        attempts++;
                        if (!container.classList.contains('hidden') || attempts > 30) {
                            clearInterval(checkVisibility);
                            resolve();
                        }
                    }, 100);
                } else {
                    resolve();
                }
            });
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

    // Paso 5: Contenedor de módulos y permisos
    tour.addStep({
        id: 'panel-configuracion',
        title: 'Panel de Configuración 🧩',
        text: 'En esta área verás todos los módulos del sistema. Para cada uno, podrás activar o desactivar el acceso completo o configurar permisos específicos como crear, editar, eliminar y ver.',
        attachTo: {
            element: '#modulosPermisosContainer',
            on: 'top'
        },
        beforeShowPromise: function() {
            return new Promise(function(resolve) {
                // Verificamos en tiempo real si el contenedor está visible
                const container = document.querySelector('#modulosPermisosContainer');
                
                // Si el contenedor está oculto, esperamos hasta 3 segundos a que aparezca
                if (container.classList.contains('hidden')) {
                    let attempts = 0;
                    const checkVisibility = setInterval(() => {
                        attempts++;
                        if (!container.classList.contains('hidden') || attempts > 30) {
                            clearInterval(checkVisibility);
                            resolve();
                        }
                    }, 100);
                } else {
                    resolve();
                }
            });
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

    // Paso 6: Lista de módulos
    tour.addStep({
        id: 'tarjetas-modulos',
        title: 'Tarjetas de Módulos 📱',
        text: 'Cada tarjeta representa un módulo del sistema. Al activar un módulo, el rol tendrá acceso a él. Puedes también expandir las opciones para configurar permisos específicos dentro de cada módulo.<br><br><i>Prueba a hacer clic en alguno de los módulos para ver sus permisos específicos.</i>',
        attachTo: {
            element: '#listaModulosPermisos',
            on: 'top'
        },
        beforeShowPromise: function() {
            return new Promise(function(resolve) {
                // Verificamos en tiempo real si el contenedor está visible
                const container = document.querySelector('#listaModulosPermisos');
                
                if (container) {
                    // Esperamos a que se carguen los módulos (al menos uno)
                    let attempts = 0;
                    const checkContent = setInterval(() => {
                        attempts++;
                        
                        // Verificar si hay algún módulo cargado (excluyendo el mensaje de selección)
                        const childrenCount = container.children.length;
                        const hasModules = childrenCount > 0 && 
                            (!container.querySelector('.text-center.py-8') || 
                             container.children.length > 1);
                        
                        if (hasModules || attempts > 50) {  // Ampliamos el tiempo de espera
                            clearInterval(checkContent);
                            
                            // Resaltamos un módulo para llamar la atención
                            setTimeout(() => {
                                const firstModule = Array.from(container.children).find(el => 
                                    el.classList.contains('bg-white') || 
                                    el.querySelector('.bg-white'));
                                
                                if (firstModule) {
                                    firstModule.style.transition = 'all 0.5s ease';
                                    firstModule.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.5)';
                                    setTimeout(() => {
                                        firstModule.style.boxShadow = '';
                                    }, 2000);
                                }
                            }, 500);
                            
                            resolve();
                        }
                    }, 100);
                } else {
                    resolve();
                }
            });
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

    // Paso 7: Permisos específicos de un módulo
    tour.addStep({
        id: 'permisos-especificos',
        title: 'Permisos Específicos ✅',
        text: `<p>Cuando expandes un módulo, puedes configurar estos permisos específicos:</p>
              <ul class="list-disc pl-5 text-left">
                <li class="mb-1"><b>Ver:</b> Permite acceder al módulo.</li>
                <li class="mb-1"><b>Crear:</b> Permite añadir nuevos registros.</li>
                <li class="mb-1"><b>Editar:</b> Permite modificar registros existentes.</li>
                <li class="mb-1"><b>Eliminar:</b> Permite borrar registros.</li>
              </ul>
              <p class="mt-2">Si activas "Acceso Completo", se otorgan todos los permisos automáticamente.</p>`,
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
                const container = document.querySelector('#listaModulosPermisos');
                
                if (container) {
                    // Intentar encontrar un módulo que ya tenga los permisos expandidos
                    let expandedModule = null;
                    const allModules = container.querySelectorAll('[data-module-id]');
                    
                    for (const module of allModules) {
                        const permissionsContainer = module.querySelector('.permissions-container');
                        if (permissionsContainer && !permissionsContainer.classList.contains('hidden')) {
                            expandedModule = module;
                            break;
                        }
                    }
                    
                    // Si no hay ninguno expandido, intentamos expandir el primero
                    if (!expandedModule && allModules.length > 0) {
                        const firstModule = allModules[0];
                        const toggleButton = firstModule.querySelector('button[data-toggle-id]');
                        
                        if (toggleButton) {
                            toggleButton.click();
                            setTimeout(() => {
                                const permissionsContainer = firstModule.querySelector('.permissions-container');
                                if (permissionsContainer) {
                                    permissionsContainer.style.transition = 'all 0.5s ease';
                                    permissionsContainer.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.5)';
                                    setTimeout(() => {
                                        permissionsContainer.style.boxShadow = '';
                                    }, 2000);
                                }
                                resolve();
                            }, 500);
                        } else {
                            resolve();
                        }
                    } else if (expandedModule) {
                        // Si ya hay uno expandido, lo resaltamos
                        const permissionsContainer = expandedModule.querySelector('.permissions-container');
                        if (permissionsContainer) {
                            permissionsContainer.style.transition = 'all 0.5s ease';
                            permissionsContainer.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.5)';
                            setTimeout(() => {
                                permissionsContainer.style.boxShadow = '';
                            }, 2000);
                        }
                        resolve();
                    } else {
                        resolve();
                    }
                } else {
                    resolve();
                }
            });
        }
    });

    // Paso 8: Botones de acción
    const btnGuardar = document.querySelector('#btnGuardarAsignaciones');
    if (btnGuardar) {
        tour.addStep({
            id: 'boton-guardar',
            title: 'Guardar Configuración 💾',
            text: 'Una vez que hayas configurado todos los permisos, haz clic en este botón para guardar los cambios. Recuerda que los cambios no se aplicarán hasta que confirmes con este botón.',
            attachTo: {
                element: '#btnGuardarAsignaciones',
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

    // Paso 8: Botón cancelar
    const btnCancelar = document.querySelector('#btnCancelar');
    if (btnCancelar) {
        tour.addStep({
            title: 'Cancelar Cambios ↩️',
            text: 'Si quieres descartar los cambios realizados y volver al estado anterior, puedes usar este botón.',
            attachTo: {
                element: '#btnCancelar',
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
    }

    // Paso 9: Notificaciones
    const notificationToast = document.querySelector('#notificationToast');
    if (notificationToast) {
        tour.addStep({
            title: 'Sistema de Notificaciones ',
            text: 'Recibirás notificaciones sobre el éxito o fallo de tus operaciones en este área. Te informará cuando los permisos se guarden correctamente o si ocurre algún error.',
            attachTo: {
                element: '#notificationToast',
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

    // Paso 10: Consejos de uso
    tour.addStep({
        id: 'consejos-uso',
        title: 'Consejos para la Gestión de Permisos 💡',
        text: '<ul class="list-disc pl-5 text-left">' +
              '<li class="mb-2">Usa la opción "Acceso Completo" solo para roles que necesiten control total sobre un módulo.</li>' +
              '<li class="mb-2">El permiso "ver" es fundamental; sin él, el usuario no podrá acceder al módulo aunque tenga otros permisos.</li>' +
              '<li class="mb-2">Recuerda guardar los cambios antes de cambiar de rol o salir del módulo.</li>' +
              '<li>Revisa periódicamente los permisos para mantener la seguridad del sistema.</li>' +
              '</ul>',
        attachTo: {
            element: '#listaModulosPermisos',
            on: 'top'
        },
        popperOptions: {
            modifiers: [{
                name: 'offset',
                options: {
                    offset: [0, 20]
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

    // Paso 11: Finalización
    tour.addStep({
        id: 'finalizacion',
        title: '¡Configuración Completa! 🚀',
        text: 'Ya conoces las principales funcionalidades de la Gestión Integral de Permisos. Recuerda que una buena configuración de permisos es fundamental para la seguridad del sistema. Puedes volver a ejecutar este tour desde el botón de ayuda cuando lo necesites.',
        attachTo: {
            element: '#btnGuardarAsignaciones',
            on: 'top-end'
        },
        popperOptions: {
            modifiers: [{
                name: 'offset',
                options: {
                    offset: [20, 20]
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
function agregarBotonAyudaRolesIntegrado() {
    // Verificar si ya existe el botón
    if (document.querySelector('#rolesintegrado-help-btn')) {
        return;
    }

    // Crear botón de ayuda flotante
    const helpButton = document.createElement('button');
    helpButton.id = 'rolesintegrado-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de gestión integral de permisos');
    helpButton.addEventListener('click', iniciarTourRolesIntegrado);
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
    // Verificar si estamos en el módulo de roles integrado
    if (window.location.pathname.includes('RolesIntegrado')) {
        
        // Esperar a que todos los elementos se carguen completamente
        setTimeout(function() {
            // Agregar botón de ayuda
            agregarBotonAyudaRolesIntegrado();
            
            // Verificar si el sistema está listo (roles cargados en el selector)
            function verificarSistemaListo() {
                const selectRol = document.querySelector('#selectRol');
                return selectRol && selectRol.options.length > 1; // Más de la opción por defecto
            }
            
            // Función para verificar y mostrar el tour
            function mostrarTourSiNecesario() {
                if (verificarSistemaListo()) {
                    // Auto-iniciar tour solo la primera vez
                    if (!localStorage.getItem('rolesintegrado-tour-completed')) {
                        // Preguntar si quiere hacer el tour
                        Swal.fire({
                            title: '¿Quieres hacer un tour del módulo?',
                            text: 'Te mostramos las principales funcionalidades de la gestión integral de permisos',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, empezar tour',
                            cancelButtonText: 'Ahora no',
                            confirmButtonColor: '#16a34a',
                            cancelButtonColor: '#6b7280'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                iniciarTourRolesIntegrado();
                            } else {
                                // Si dice que no, marcar como completado para no molestarlo más
                                localStorage.setItem('rolesintegrado-tour-completed', 'true');
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
window.reiniciarTourRolesIntegrado = function() {
    localStorage.removeItem('rolesintegrado-tour-completed');
    iniciarTourRolesIntegrado();
};
