/**
 * Tour específico para el módulo de Movimientos
 * Se carga automáticamente cuando el usuario visita el módulo de movimientos
 */

// Función para iniciar el tour del módulo de movimientos
function iniciarTourMovimientos() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            scrollTo: true,
            cancelIcon: {
                enabled: true
            }
        },
        onComplete: function() {
            localStorage.setItem('movimientos-tour-completed', 'true');
            Swal.fire({
                title: '¡Tour Completado!',
                text: 'Ya conoces las principales funcionalidades del módulo de movimientos.',
                icon: 'success',
                confirmButtonText: 'Excelente',
                confirmButtonColor: '#16a34a'
            });
        },
        onCancel: function() {
            console.log('Tour del módulo de movimientos cancelado');
        }
    });

    // Paso 1: Bienvenida al módulo de movimientos
    tour.addStep({
        title: '¡Bienvenido al Módulo de Movimientos! 📦',
        text: 'Te guiaremos por las principales funcionalidades del módulo de movimientos de inventario. Aquí puedes registrar y gestionar todas las entradas y salidas de productos, así como ajustes y transferencias de existencias.',
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
        title: 'Gestión Integral de Inventario 📋',
        text: 'Este módulo es fundamental para mantener un control preciso de tu inventario. Cada movimiento queda registrado con fecha, tipo, cantidad, motivo y responsable, garantizando trazabilidad completa.',
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

    // Paso 3: Estadísticas de movimientos
    const estadisticas = document.querySelector('#estadisticas-movimientos');
    if (estadisticas) {
        tour.addStep({
            title: 'Estadísticas en Tiempo Real 📊',
            text: 'Este panel muestra un resumen actualizado de tus movimientos de inventario:<br><br>' +
                  '• <strong>Total de movimientos:</strong> Cantidad de registros<br>' +
                  '• <strong>Por tipo:</strong> Entradas, salidas, ajustes, transferencias<br>' +
                  '• <strong>Resumen del día:</strong> Actividad reciente<br><br>' +
                  'Las estadísticas se actualizan automáticamente con cada movimiento.',
            attachTo: {
                element: '#estadisticas-movimientos',
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

    // Paso 4: Filtros de búsqueda
    const filtroTipo = document.querySelector('#filtro-tipo-movimiento');
    if (filtroTipo) {
        tour.addStep({
            title: 'Filtros Inteligentes 🔍',
            text: 'Usa estos filtros para encontrar movimientos específicos:<br><br>' +
                  '• <strong>Filtrar por Tipo:</strong> Selecciona entradas, salidas, ajustes, etc.<br>' +
                  '• <strong>Búsqueda:</strong> Busca por producto, motivo o usuario<br><br>' +
                  'Los filtros se aplican instantáneamente y se pueden combinar.',
            attachTo: {
                element: '#filtro-tipo-movimiento',
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

    // Paso 5: Indicador de filtro actual
    const indicadorFiltro = document.querySelector('#indicador-filtro-actual');
    if (indicadorFiltro) {
        tour.addStep({
            title: 'Indicador de Filtro Activo 📌',
            text: 'Este indicador te muestra qué filtros están aplicados actualmente. Te ayuda a saber exactamente qué movimientos estás visualizando en la tabla. Cuando apliques un filtro, verás aquí la descripción correspondiente.',
            attachTo: {
                element: '#indicador-filtro-actual',
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
    }

    // Paso 6: Botón registrar movimiento
    const btnRegistrar = document.querySelector('#btnAbrirModalMovimiento');
    if (btnRegistrar) {
        tour.addStep({
            title: 'Registrar Nuevo Movimiento ➕',
            text: 'Con este botón puedes registrar nuevos movimientos de inventario:<br><br>' +
                  '• <strong>Entradas:</strong> Productos que llegan al inventario<br>' +
                  '• <strong>Salidas:</strong> Productos que salen del inventario<br>' +
                  '• <strong>Ajustes:</strong> Correcciones de inventario<br>' +
                  '• <strong>Transferencias:</strong> Movimientos entre ubicaciones<br><br>' +
                  'Solo aparece si tienes permisos de creación.',
            attachTo: {
                element: '#btnAbrirModalMovimiento',
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

    // Paso 7: Botón exportar
    const btnExportar = document.querySelector('#btnExportarMovimientos');
    if (btnExportar) {
        tour.addStep({
            title: 'Exportar Movimientos 📥',
            text: 'Exporta tus movimientos a Excel para análisis externos, reportes o auditorías. El archivo incluirá todos los datos visibles según los filtros aplicados.',
            attachTo: {
                element: '#btnExportarMovimientos',
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

    // Paso 8: Tabla de movimientos
    tour.addStep({
        title: 'Tabla de Movimientos 📊',
        text: 'Esta tabla muestra todos los movimientos de inventario registrados. Incluye información como:<br><br>' +
              '• <strong>Fecha y hora:</strong> Cuándo ocurrió el movimiento<br>' +
              '• <strong>Tipo:</strong> Entrada, salida, ajuste o transferencia<br>' +
              '• <strong>Producto:</strong> Qué producto fue afectado<br>' +
              '• <strong>Cantidad:</strong> Cuántas unidades se movieron<br>' +
              '• <strong>Usuario:</strong> Quién realizó el movimiento<br>' +
              '• <strong>Motivo:</strong> Razón del movimiento<br>' +
              '• <strong>Acciones:</strong> Ver detalles o editar',
        attachTo: {
            element: '#TablaMovimiento',
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

    // Paso 9: Tipos de movimientos
    tour.addStep({
        title: 'Tipos de Movimientos Explicados 🔄',
        text: '<strong>Entradas:</strong> Cuando llegan productos (compras, devoluciones de clientes, producción)<br><br>' +
              '<strong>Salidas:</strong> Cuando salen productos (ventas, devoluciones a proveedores, mermas)<br><br>' +
              '<strong>Ajustes:</strong> Correcciones por inventarios físicos, errores o correcciones<br><br>' +
              '<strong>Transferencias:</strong> Movimientos entre bodegas, sucursales o ubicaciones<br><br>' +
              'Cada tipo afecta el inventario de forma diferente.',
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

    // Paso 10: Trazabilidad
    tour.addStep({
        title: 'Trazabilidad Completa 🔍',
        text: 'Cada movimiento queda registrado con:<br><br>' +
              '• <strong>Usuario responsable:</strong> Quién realizó el movimiento<br>' +
              '• <strong>Fecha y hora exacta:</strong> Cuándo ocurrió<br>' +
              '• <strong>Motivo detallado:</strong> Por qué se realizó<br>' +
              '• <strong>Antes y después:</strong> Existencias previas y posteriores<br><br>' +
              'Esto permite auditorías completas y detectar irregularidades.',
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

    // Paso 11: Búsqueda en DataTables
    tour.addStep({
        title: 'Búsqueda Avanzada en Tabla 🔎',
        text: 'Además de los filtros superiores, la tabla tiene su propia búsqueda DataTables que aparece cuando la tabla se carga. Puedes buscar por cualquier dato visible y ordenar columnas haciendo clic en sus encabezados.',
        attachTo: {
            element: '#TablaMovimiento_filter',
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
                    const filterElement = document.querySelector('#TablaMovimiento_filter');
                    if (filterElement) {
                        tour.currentStep.updateStepOptions({
                            attachTo: {
                                element: '#TablaMovimiento_filter',
                                on: 'bottom'
                            }
                        });
                    }
                }, 500);
            }
        }
    });

    // Paso 12: Integración con otros módulos
    tour.addStep({
        title: 'Integración con Otros Módulos 🔗',
        text: 'El módulo de movimientos se integra automáticamente con:<br><br>' +
              '• <strong>Productos:</strong> Actualiza existencias automáticamente<br>' +
              '• <strong>Compras:</strong> Genera entradas al registrar compras<br>' +
              '• <strong>Ventas:</strong> Genera salidas al realizar ventas<br>' +
              '• <strong>Dashboard:</strong> Alimenta reportes y gráficos<br>' +
              '• <strong>Bitácora:</strong> Registra todas las acciones<br><br>' +
              'Los movimientos son el corazón del control de inventario.',
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

    // Paso 13: Consejos finales
    tour.addStep({
        title: 'Consejos y Buenas Prácticas 💡',
        text: 'Para un control óptimo de inventario:<br><br>' +
              '• Registra movimientos inmediatamente cuando ocurran<br>' +
              '• Siempre especifica un motivo claro y detallado<br>' +
              '• Verifica cantidades antes de confirmar<br>' +
              '• Realiza inventarios físicos periódicos<br>' +
              '• Usa ajustes solo para correcciones reales<br>' +
              '• Revisa regularmente los movimientos para detectar patrones<br>' +
              '• Exporta datos periódicamente como respaldo<br><br>' +
              '¡Ya estás listo para gestionar movimientos de inventario!',
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
function agregarBotonAyudaMovimientos() {
    if (document.querySelector('#movimientos-help-btn')) {
        return;
    }

    const helpButton = document.createElement('button');
    helpButton.id = 'movimientos-help-btn';
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
    
    helpButton.setAttribute('title', 'Iniciar tour de movimientos');
    helpButton.addEventListener('click', iniciarTourMovimientos);
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
    agregarBotonAyudaMovimientos();
    
    setTimeout(() => {
        const tourCompleted = localStorage.getItem('movimientos-tour-completed');
        if (!tourCompleted) {
            iniciarTourMovimientos();
        }
    }, 1000);
});

window.iniciarTourMovimientos = iniciarTourMovimientos;
