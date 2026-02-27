document.addEventListener('DOMContentLoaded', function () {
    // Inject the Help Button
    const faqBtn = document.createElement('button');
    faqBtn.id = 'faq-global-help-btn';
    faqBtn.innerHTML = '<i class="fas fa-info"></i>';
    faqBtn.className = 'fixed hover:bg-blue-700 text-white p-4 rounded-full shadow-lg z-50 transition-all duration-300 hover:scale-110 flex items-center justify-center';
    faqBtn.style.cssText = `
        position: fixed !important;
        bottom: 90px !important;
        right: 24px !important;
        width: 56px !important;
        height: 56px !important;
        border-radius: 50% !important;
        background-color: #2563eb !important;
        color: white !important;
        border: none !important;
        cursor: pointer !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
        z-index: 1000 !important;
        font-size: 20px !important;
    `;
    faqBtn.setAttribute('title', 'Preguntas Frecuentes y Ayuda');

    // FAQ Data definition
    const faqs = [
        {
            module: 'productos',
            title: '¿Cómo crear o editar un producto?',
            content: 'Diríjase a <strong>Gestionar Producción > Productos</strong>. Haga clic en <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs"><i class="fas fa-plus"></i> Agregar Producto</span> si desea uno nuevo, o en el icono de edición de la tabla para modificar uno existente.'
        },
        {
            module: 'compras',
            title: '¿Cómo registro una nueva compra?',
            content: 'Vaya a <strong>Gestionar Compras > Compras</strong> y haga clic en <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs"><i class="fas fa-plus"></i> Nueva Compra</span>. Seleccione su proveedor, agregue los productos, ajuste cantidades y presione finalizar.'
        },
        {
            module: 'pagos',
            title: '¿Cómo realizar pagos a proveedores o empleados?',
            content: 'Vaya a <strong>Gestionar Pagos > Pagos</strong>. Haga clic en <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs"><i class="fas fa-plus"></i> Registrar Pago</span>. Puede realizar pagos a empleados (Sueldo) o proveedores. Indique el monto y seleccione la moneda.'
        },
        {
            module: 'ventas',
            title: '¿Cómo registrar ventas?',
            content: 'Vaya a <strong>Gestionar Ventas > Ventas</strong>. Haga clic en <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs"><i class="fas fa-plus"></i> Nueva Venta</span>. Seleccione su cliente, añada productos al carrito, procese el pago y emita la factura.'
        },
        {
            module: 'roles',
            moduleAlt: 'usuarios',
            title: '¿Cómo gestiono los permisos y roles?',
            content: 'Navegue a <strong>Gestionar Seguridad > Roles</strong> para crear perfiles, y en <strong>Usuarios</strong> asigne esos perfiles a las personas. También puede usar la opción "Gestión Integral" para configurar con más detalle los permisos.'
        },
        {
            module: 'backup',
            title: '¿Cómo crear copias de seguridad?',
            content: 'Acceda a <strong>Gestionar Seguridad > Backups</strong>. Allí puede generar un archivo de respaldo de la base de datos o restaurar un respaldo anterior haciendo clic en <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs"><i class="fas fa-database"></i> Crear Backup</span>.'
        },
        {
            module: 'movimientos',
            title: '¿Qué son los movimientos de inventario?',
            content: 'En el módulo <strong>Gestionar Movimientos</strong> puede registrar de forma manual mermas, ajustes de inventario o entradas/salidas de mercancía, indicando el motivo e impacto en el stock. Use el botón <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs"><i class="fas fa-plus"></i> Nuevo Movimiento</span> para registrar la acción.'
        },
        {
            module: 'dashboard',
            title: 'Consulta de reportes y gráficas',
            content: 'El <strong>Dashboard</strong> incluye múltiples reportes como Ingresos/Egresos, KPI\'s, Inventario y Producción. Seleccione el reporte deseado en la lista desplegable superior. Desde el dashboard también es posible aplicar filtros de fecha por rango.',
            alwaysVisible: true
        },
        {
            module: 'tour',
            title: '¿Qué es el Tour Interactivo?',
            content: 'Es una guía paso a paso asociada a la pantalla que esté visualizando, y sirve como tutorial. Puede iniciarse con el botón flotante en ciertas pantallas o pulsando el botón al final de esta lista.',
            alwaysVisible: true
        }
    ];

    // Filter FAQs based on user permissions (visible in the sidebar links)
    const renderFAQs = () => {
        let html = '';
        let counter = 1;

        faqs.forEach(faq => {
            let hasAccess = faq.alwaysVisible || false;

            // Check if the user has a menu link for this module
            if (!hasAccess && faq.module) {
                const link = document.querySelector(`aside#sidebar a[href*="${faq.module}"]`);
                const linkAlt = faq.moduleAlt ? document.querySelector(`aside#sidebar a[href*="${faq.moduleAlt}"]`) : null;

                if (link || linkAlt) hasAccess = true;
            }

            if (hasAccess) {
                html += `
                    <div class="faq-item bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 hover:shadow-md">
                    <button class="faq-toggle w-full text-left p-4 focus:outline-none flex justify-between items-center bg-white hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-gray-800 text-sm">${counter}. ${faq.title}</span>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-300"></i>
                    </button>
                    <div class="faq-content hidden px-4 pb-4 pt-1 text-gray-600 text-sm leading-relaxed border-t border-gray-50">
                        ${faq.content}
                    </div>
                    </div>
                `;
                counter++;
            }
        });

        if (html === '') {
            html = '<p class="text-sm text-gray-500 text-center py-4">No hay ayuda disponible para los módulos actuales.</p>';
        }

        return html;
    };

    // Create the Sidebar HTML
    const modalHTML = `
      <div id="faq-modal-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-40 backdrop-blur-[3px] z-[1050] hidden transition-opacity duration-300 opacity-0" style="z-index: 1050;"></div>
      
      <!-- Usamos inline styles para width para forzar el 20% y min/max resolviendo problemas de JIT -->
      <div id="faq-sidebar" class="fixed top-0 right-0 h-full bg-gray-50 shadow-2xl z-[1060] transform translate-x-full transition-transform duration-300 flex flex-col" 
           style="z-index: 1060; width: 22%; min-width: 330px; max-width: 450px;">
        
        <!-- Header -->
        <div class="px-6 py-8 border-b border-blue-700 flex justify-between items-center bg-blue-600 text-white shadow-md z-10 shrink-0">
          <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-question-circle mr-3 text-3xl"></i> Centro de Ayuda
          </h2>
          <button id="faq-close-btn" class="text-white hover:text-gray-200 focus:outline-none text-3xl transition-transform hover:rotate-90">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <!-- Body -->
        <div class="p-5 overflow-y-auto flex-1 custom-scrollbar w-full">
          <p class="text-gray-600 mb-5 text-sm font-medium">
            Encuentre respuestas rápidas a las preguntas más comunes sobre el uso de la plataforma.
          </p>
          
          <div class="space-y-3 w-full" id="faq-list-container">
            <!-- Dynamic Content Injected Here -->
            ${renderFAQs()}
          </div>
          
          <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-5 shadow-sm mb-4">
            <div class="flex flex-col">
              <div class="flex items-center mb-2">
                <i class="fas fa-compass text-blue-500 mr-2 text-xl"></i>
                <h4 class="font-bold text-blue-900 text-sm">Tour Paso a Paso</h4>
              </div>
              <p class="text-blue-800 text-xs mb-4 leading-relaxed">Revisa de forma interactiva la funcionalidad de esta pantalla y aprende rápidamente donde está cada opción directamente sobre la interfaz visual de la plataforma.</p>
              <button id="faq-start-tour-btn" class="w-full text-sm bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold transition-colors shadow flex items-center justify-center group">
                <i class="fas fa-play mr-2 group-hover:scale-110 transition-transform"></i> Iniciar Tour Pantalla Actual
              </button>
            </div>
          </div>
        </div>
      </div>

      <style>
        /* Custom scrollbar para el sidebar */
        .custom-scrollbar::-webkit-scrollbar {
          width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
          background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
        
        /* Asegurar que el overlay no bloquee cuando está oculto */
        #faq-modal-overlay.hidden {
            pointer-events: none;
        }
      </style>
    `;

    // Inject into body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.appendChild(faqBtn);

    // Logic bindings
    const overlay = document.getElementById('faq-modal-overlay');
    const sidebar = document.getElementById('faq-sidebar');
    const closeBtn = document.getElementById('faq-close-btn');

    function openFAQ() {
        overlay.classList.remove('hidden');
        // Trigger reflow
        void overlay.offsetWidth;
        overlay.classList.remove('opacity-0');
        overlay.classList.add('opacity-100');
        sidebar.classList.remove('translate-x-full');
    }

    function closeFAQ() {
        sidebar.classList.add('translate-x-full');
        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0');
        setTimeout(() => {
            overlay.classList.add('hidden');
        }, 300);
    }

    faqBtn.addEventListener('click', openFAQ);
    closeBtn.addEventListener('click', closeFAQ);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeFAQ();
        }
    });

    // Accordion Logic re-bind
    const bindAccordions = () => {
        const faqToggles = document.querySelectorAll('.faq-toggle');
        faqToggles.forEach(toggle => {
            toggle.addEventListener('click', function () {
                const content = this.nextElementSibling;
                const icon = this.querySelector('i');

                // Close all others
                faqToggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        otherToggle.nextElementSibling.classList.add('hidden');
                        otherToggle.querySelector('i').classList.remove('rotate-180');
                    }
                });

                // Toggle current
                content.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
        });
    };

    // Bind the first time
    bindAccordions();

    // Start tour button
    document.getElementById('faq-start-tour-btn').addEventListener('click', () => {
        closeFAQ();
        // Buscar el botón de tour normal
        const tourBtn = document.querySelector('[id$="-help-btn"]:not(#faq-global-help-btn)');
        if (tourBtn) {
            setTimeout(() => {
                tourBtn.click();
            }, 350); // esperar a que cierre la animación de la barra lateral
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Tour no disponible',
                text: 'No hay un tour configurado para la pantalla actual.',
                confirmButtonColor: '#2563eb'
            });
        }
    });
});
