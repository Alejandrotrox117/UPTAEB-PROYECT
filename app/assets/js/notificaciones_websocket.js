// ============================================
// SISTEMA DE NOTIFICACIONES WEBSOCKET v1.0
// ============================================

console.log('📂 notificaciones_websocket.js cargado');

class SistemaNotificacionesWS {
    constructor(usuarioId, rolId, rolNombre) {
        this.usuarioId = usuarioId;
        this.rolId = rolId;
        this.rolNombre = rolNombre;
        this.socket = null;
        this.notificacionesNoLeidas = 0;
        this.reconexionIntento = 0;
        this.maxReintentos = 5;
        this.notificacionesInformativas = []; // 📦 Notificaciones informativas en memoria
        
        // Configurar base URL para API calls
        const baseUrlElement = document.querySelector('meta[name="base-url"]');
        this.baseUrl = (baseUrlElement?.content || '/project').replace(/\/$/, '');

        console.log('🚀 Inicializando Sistema de Notificaciones WebSocket (Híbrido)');
        console.log(`📍 Base URL: ${this.baseUrl}`);
        
        // 1. Cargar notificaciones persistentes desde BD
        this.cargarNotificacionesBD();
        
        // 2. Cargar notificaciones temporales desde localStorage
        this.cargarNotificacionesLocalStorage();
        
        // 3. Conectar WebSocket para tiempo real
        this.conectar();
    }

    conectar() {
        console.log(`Conectando a WebSocket (intento ${this.reconexionIntento + 1})...`);

        try {
            this.socket = new WebSocket('ws://localhost:8080');

            this.socket.onopen = () => {
                console.log(' WebSocket conectado');
                this.reconexionIntento = 0;

                // Autenticarse inmediatamente
                this.autenticar();
            };

            this.socket.onmessage = (event) => {
                this.procesarMensaje(event.data);
            };

            this.socket.onclose = () => {
                console.log('❌ WebSocket desconectado');
                this.intentarReconexion();
            };

            this.socket.onerror = (error) => {
                console.error('❌ Error WebSocket:', error);
            };

        } catch (error) {
            console.error('❌ Error al crear WebSocket:', error);
            this.intentarReconexion();
        }
    }

    autenticar() {
        const authData = {
            tipo: 'autenticar',
            usuario_id: this.usuarioId,
            rol_id: this.rolId,
            rol_nombre: this.rolNombre
        };

        console.log('🔐 Autenticando usuario:', this.usuarioId);
        this.socket.send(JSON.stringify(authData));
    }

    intentarReconexion() {
        if (this.reconexionIntento < this.maxReintentos) {
            this.reconexionIntento++;
            const delay = Math.min(1000 * Math.pow(2, this.reconexionIntento), 10000);

            console.log(`🔄 Reconectando en ${delay / 1000}s...`);
            setTimeout(() => this.conectar(), delay);
        } else {
            console.error('❌ Máximo de reintentos alcanzado. WebSocket no disponible.');
            this.mostrarErrorConexion();
        }
    }

    procesarMensaje(data) {
        try {
            const mensaje = JSON.parse(data);
            console.log('📩 Notificación recibida:', mensaje);

            // ✨ Verificar si es informativa (se guarda en localStorage)
            if (mensaje.esInformativa === true) {
                console.log('💾 Notificación informativa - Guardando en localStorage');
                this.procesarNotificacionInformativa(mensaje);
                return;
            }

            // 🚨 Notificaciones urgentes (BD + WebSocket)
            switch (mensaje.tipo) {
                case 'conexion':
                    console.log('', mensaje.mensaje);
                    break;

                case 'autenticacion':
                    console.log(' Autenticación:', mensaje.mensaje);
                    break;

                // Notificaciones de productos
                case 'TEST_STOCK_BAJO':
                case 'STOCK_BAJO':
                case 'STOCK_MINIMO':
                case 'SIN_STOCK':
                    this.mostrarNotificacionStock(mensaje.data);
                    break;

                // Notificaciones de compras
                case 'TEST_COMPRA':
                case 'COMPRA_POR_AUTORIZAR':
                case 'COMPRA_AUTORIZADA_PAGO':
                    this.mostrarNotificacionCompra(mensaje.data);
                    break;

                // Notificaciones de ventas
                case 'VENTA_CREADA_PAGO':
                    this.mostrarNotificacionVenta(mensaje.data);
                    break;

                // Notificaciones de productos (CRUD)
                case 'PRODUCTO_NUEVO':
                case 'PRODUCTO_ACTUALIZADO':
                case 'PRODUCTO_ACTIVADO':
                case 'PRODUCTO_DESACTIVADO':
                    this.mostrarNotificacionProducto(mensaje.data);
                    break;

                // Broadcast general
                case 'TEST_BROADCAST':
                default:
                    this.mostrarNotificacionGenerica(mensaje);
            }

        } catch (error) {
            console.error('❌ Error al procesar mensaje:', error);
        }
    }

    mostrarNotificacionStock(data) {
        console.log('📦 Notificación de stock recibida:', data);
        this.incrementarContador();

        // Mostrar toast de SweetAlert
        Swal.fire({
            icon: 'warning',
            title: data.titulo || 'Alerta de Stock',
            text: data.mensaje || 'Stock bajo detectado',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true
        });

        // Agregar al dropdown (siempre, no solo si está abierto)
        this.agregarNotificacionAlDropdown(data);
    }

    mostrarNotificacionCompra(data) {
        this.incrementarContador();

        Swal.fire({
            icon: 'warning',
            title: data.titulo,
            text: data.mensaje,
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            confirmButtonText: 'Ver',
            timer: 10000,
            timerProgressBar: true
        });

        this.agregarAListaVisual(data);
    }

    mostrarNotificacionVenta(data) {
        this.incrementarContador();

        Swal.fire({
            icon: 'info',
            title: data.titulo,
            text: data.mensaje,
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            confirmButtonText: 'Ver',
            timer: 8000,
            timerProgressBar: true
        });

        this.agregarAListaVisual(data);
    }

    mostrarNotificacionProducto(data) {
        console.log('📦 Notificación de producto recibida:', data);
        this.incrementarContador();

        // Agregar al dropdown si está abierto
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown && !dropdown.classList.contains('hidden')) {
            this.agregarNotificacionAlDropdown(data);
        }
    }

    /**
     * Agrega una notificación visual al dropdown
     */
    agregarNotificacionAlDropdown(data) {
        const notificationsList = document.getElementById('notifications-list');
        if (!notificationsList) {
            console.warn('⚠️ No se encontró notifications-list');
            return;
        }

        // Limpiar mensaje de "no hay notificaciones" si existe
        const emptyMessage = notificationsList.querySelector('.text-center');
        if (emptyMessage && emptyMessage.textContent.includes('No hay notificaciones')) {
            notificationsList.innerHTML = '';
        }

        // Determinar tipo de icono
        const iconClass = this.obtenerIconoPorTipo(data.tipo);
        const prioridadClass = this.obtenerClasePrioridad(data.prioridad || 'MEDIA');

        const now = new Date();
        const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
        
        const esLeida = data.leida ? 'read' : 'unread';
        const estilo = data.leida ? 'bg-gray-50' : 'bg-blue-50';

        const notificationHTML = `
            <div class="notification-item p-3 border-b border-gray-100 ${estilo} ${esLeida}" data-notif-id="${data.idnotificacion || ''}">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <i class="${iconClass} text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                ${data.titulo}
                            </p>
                            ${!data.leida ? '<div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>' : ''}
                        </div>
                        <p class="text-xs text-gray-600 mt-1">${data.mensaje}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs font-medium px-2 py-1 rounded ${prioridadClass}">
                                ${data.prioridad || 'MEDIA'}
                            </span>
                            <span class="text-xs text-gray-400">${data.fecha_formato || 'Ahora'}</span>
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            ${!data.leida ? `<button class="btn-mark-read text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200" data-id="${data.idnotificacion || ''}">
                                <i class="fas fa-check mr-1"></i>Leída
                            </button>` : ''}
                            <button class="btn-delete-notif text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200" data-id="${data.idnotificacion || ''}">
                                <i class="fas fa-trash mr-1"></i>Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insertar al inicio
        notificationsList.insertAdjacentHTML('afterbegin', notificationHTML);
        
        // Agregar event listeners a los botones
        const item = notificationsList.firstElementChild;
        const btnMarkRead = item.querySelector('.btn-mark-read');
        const btnDelete = item.querySelector('.btn-delete-notif');
        
        if (btnMarkRead) {
            btnMarkRead.addEventListener('click', (e) => {
                e.stopPropagation();
                this.marcarComoLeida(data.idnotificacion, item);
            });
        }
        
        if (btnDelete) {
            btnDelete.addEventListener('click', (e) => {
                e.stopPropagation();
                this.eliminarNotificacion(data.idnotificacion, item);
            });
        }
        
        console.log(' Notificación agregada al dropdown');
    }

    /**
     * Obtiene clase de icono según tipo
     */
    obtenerIconoPorTipo(tipo) {
        const iconos = {
            'TEST_STOCK_BAJO': 'fas fa-flask text-blue-500',
            'STOCK_MINIMO': 'fas fa-box text-yellow-500',
            'STOCK_BAJO': 'fas fa-exclamation-triangle text-yellow-500',
            'SIN_STOCK': 'fas fa-times-circle text-red-500',
            'PRODUCTO_NUEVO': 'fas fa-box text-green-500',
            'PRODUCTO_ACTUALIZADO': 'fas fa-edit text-blue-500',
            'PRODUCTO_DESACTIVADO': 'fas fa-ban text-red-500',
            'PRODUCTO_ACTIVADO': 'fas fa-check-circle text-green-500',
            'COMPRA_POR_AUTORIZAR': 'fas fa-clock text-orange-500'
        };
        return iconos[tipo] || 'fas fa-info-circle text-blue-500';
    }

    /**
     * Obtiene clase CSS según prioridad
     */
    obtenerClasePrioridad(prioridad) {
        const clases = {
            'CRITICA': 'bg-red-100 text-red-800',
            'ALTA': 'bg-orange-100 text-orange-800',
            'MEDIA': 'bg-yellow-100 text-yellow-800',
            'BAJA': 'bg-blue-100 text-blue-800'
        };
        return clases[prioridad] || clases['MEDIA'];
    }

    mostrarNotificacionGenerica(mensaje) {
        this.incrementarContador();

        const data = mensaje.data || {};
        Swal.fire({
            icon: 'info',
            title: data.titulo || 'Notificación',
            text: data.mensaje || 'Nueva notificación',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true
        });

        this.agregarAListaVisual(data);
    }

    incrementarContador() {
        this.notificacionesNoLeidas++;
        this.actualizarBadges();
    }

    actualizarBadges() {
        const badges = document.querySelectorAll(
            '#mobile-notification-badge, #desktop-notification-badge'
        );

        badges.forEach(badge => {
            const count = this.notificacionesNoLeidas;
            badge.textContent = count > 99 ? '99+' : count;
            
            // Mostrar badge si hay notificaciones
            if (count > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
        
        console.log(`🔔 Badge actualizado: ${this.notificacionesNoLeidas} notificaciones`);
    }

    agregarAListaVisual(data) {
        const notificationsList = document.getElementById('notifications-list');
        if (!notificationsList) return;

        const iconoDefault = 'fas fa-bell text-blue-500';
        const notifHTML = `
            <div class="notification-item p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer unread">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <i class="${data.icono || iconoDefault} text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${data.titulo || 'Notificación'}</p>
                        <p class="text-xs text-gray-600 mt-1">${data.mensaje || ''}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs font-medium px-2 py-1 rounded ${this.getColorPrioridad(data.prioridad)}">
                                ${data.prioridad || 'MEDIA'}
                            </span>
                            <span class="text-xs text-gray-400">Ahora</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        notificationsList.insertAdjacentHTML('afterbegin', notifHTML);
    }

    getColorPrioridad(prioridad) {
        const colores = {
            'CRITICA': 'bg-red-100 text-red-800',
            'ALTA': 'bg-orange-100 text-orange-800',
            'MEDIA': 'bg-yellow-100 text-yellow-800',
            'BAJA': 'bg-blue-100 text-blue-800'
        };
        return colores[prioridad] || colores['MEDIA'];
    }

    /**
     * Carga notificaciones históricas desde el backend
     */
    cargarNotificacionesHistoricas() {
        const notificationsList = document.getElementById('notifications-list');
        if (!notificationsList) return;

        // Solo limpiar si realmente no hay notificaciones en memoria
        const existingNotifications = notificationsList.querySelectorAll('.notification-item');
        if (existingNotifications.length > 0) {
            console.log(`✅ Ya hay ${existingNotifications.length} notificaciones en el dropdown`);
            return; // No limpiar, mantener las notificaciones existentes
        }

        // Si está vacío, mostrar mensaje
        notificationsList.innerHTML = `
            <div class="p-8 text-center">
                <i class="fas fa-bell-slash text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 text-sm">No hay notificaciones guardadas</p>
                <p class="text-xs text-gray-400 mt-2">Las notificaciones en tiempo real aparecerán aquí</p>
            </div>
        `;

        // TODO: Implementar endpoint backend /api/notificaciones/obtener
    }

    limpiarContador() {
        this.notificacionesNoLeidas = 0;
        this.actualizarBadges();
    }

    mostrarErrorConexion() {
        toastr.error(
            'No se pudo conectar al servidor de notificaciones. Las notificaciones en tiempo real no están disponibles.',
            'Error de Conexión',
            {
                timeOut: 0,
                closeButton: true
            }
        );
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================

function inicializarSistemaNotificaciones() {
    // Verificar que tenemos variables de sesión
    const usuarioId = window.SESSION_USER_ID || 0;
    const rolId = window.SESSION_ROL_ID || 0;
    const rolNombre = window.SESSION_ROL_NOMBRE || 'Usuario';

    if (usuarioId > 0) {
        console.log('🚀 Iniciando WebSocket para usuario:', usuarioId);
        window.notificacionesWS = new SistemaNotificacionesWS(usuarioId, rolId, rolNombre);

        // Evento para marcar todas como leídas
        const markAllReadBtn = document.getElementById('mark-all-read-btn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                window.notificacionesWS.marcarTodasLeidas();

                // Limpiar visualmente
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
            });
        }

        // Evento para cerrar dropdown
        const closeNotificationsBtn = document.getElementById('close-notifications-btn');
        if (closeNotificationsBtn) {
            closeNotificationsBtn.addEventListener('click', () => {
                const dropdown = document.getElementById('notifications-dropdown');
                if (dropdown) dropdown.classList.add('hidden');
            });
        }

        // Evento para refrescar notificaciones
        const refreshNotificationsBtn = document.getElementById('refresh-notifications-btn');
        if (refreshNotificationsBtn) {
            refreshNotificationsBtn.addEventListener('click', () => {
                window.notificacionesWS.cargarNotificacionesHistoricas();
            });
        }

        // Toggle para desktop
        const desktopToggle = document.getElementById('desktop-notifications-toggle');
        if (desktopToggle) {
            desktopToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const dropdown = document.getElementById('notifications-dropdown');
                if (dropdown) {
                    dropdown.classList.toggle('hidden');

                    // Si se abre, cargar notificaciones
                    if (!dropdown.classList.contains('hidden')) {
                        window.notificacionesWS.cargarNotificacionesHistoricas();
                    }
                }
            });
        }

        // Toggle para mobile
        const mobileToggle = document.getElementById('mobile-notifications-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const dropdown = document.getElementById('notifications-dropdown');
                if (dropdown) {
                    dropdown.classList.toggle('hidden');

                    // Si se abre, cargar notificaciones
                    if (!dropdown.classList.contains('hidden')) {
                        window.notificacionesWS.cargarNotificacionesHistoricas();
                    }
                }
            });
        }

        // Cerrar al hacer clic fuera
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('notifications-dropdown');
            const desktopBtn = document.getElementById('desktop-notifications-toggle');
            const mobileBtn = document.getElementById('mobile-notifications-toggle');

            if (dropdown && !dropdown.classList.contains('hidden')) {
                if (!dropdown.contains(e.target) &&
                    e.target !== desktopBtn &&
                    e.target !== mobileBtn &&
                    !desktopBtn?.contains(e.target) &&
                    !mobileBtn?.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    } else {
        console.warn(' No hay sesión de usuario, WebSocket no iniciado');
    }
}

// ============================================
// MÉTODOS PARA NOTIFICACIONES INFORMATIVAS
// ============================================

/**
 * Procesar notificación informativa (solo localStorage)
 */
SistemaNotificacionesWS.prototype.procesarNotificacionInformativa = function(mensaje) {
    // Guardar en localStorage
    this.guardarEnLocalStorage(mensaje);
    
    // Mostrar toast
    this.mostrarToastInformativo(mensaje);
    
    // Agregar al dropdown
    this.agregarNotificacionAlDropdown(mensaje.data || mensaje);
    
    // Incrementar contador
    this.incrementarContador();
};

/**
 * Guardar notificación en localStorage
 */
SistemaNotificacionesWS.prototype.guardarEnLocalStorage = function(notificacion) {
    try {
        const key = 'notificaciones_informativas';
        let notificaciones = JSON.parse(localStorage.getItem(key) || '[]');
        
        // Agregar nueva notificación con ID único
        notificacion.id = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        notificacion.timestamp = new Date().toISOString();
        notificaciones.unshift(notificacion);
        
        // Limitar a 50 notificaciones
        if (notificaciones.length > 50) {
            notificaciones = notificaciones.slice(0, 50);
        }
        
        localStorage.setItem(key, JSON.stringify(notificaciones));
        console.log(`💾 Notificación guardada en localStorage (total: ${notificaciones.length})`);
    } catch (error) {
        console.error('❌ Error al guardar en localStorage:', error);
    }
};

/**
 * Cargar notificaciones persistentes desde la base de datos
 */
SistemaNotificacionesWS.prototype.cargarNotificacionesBD = async function() {
    try {
        console.log('📡 Cargando notificaciones persistentes desde BD...');
        
        const endpoint = `${this.baseUrl}/notificaciones/getNotificaciones`;
        
        console.log('Endpoint:', endpoint);
        
        const response = await fetch(endpoint, {
            method: 'GET',
            credentials: 'include',  // Enviar cookies de sesión
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'  // Indicar que es AJAX
            }
        });
        
        if (!response.ok) {
            console.error('❌ Respuesta HTTP error:', response.status, response.statusText);
            const text = await response.text();
            console.error('Respuesta:', text.substring(0, 500));
            return;
        }
        
        const contentType = response.headers.get('content-type');
        console.log('📋 Content-Type:', contentType);
        
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Content-Type no es JSON:', contentType);
            const text = await response.text();
            console.error('Respuesta recibida (primeros 500 chars):', text.substring(0, 500));
            return;
        }
        
        let result;
        const responseText = await response.text();
        console.log('📄 Respuesta raw (primeros 200 chars):', responseText.substring(0, 200));
        
        // Intentar parsear JSON
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Error al parsear JSON:', parseError.message);
            console.error('   Primeros 500 caracteres:', responseText.substring(0, 500));
            console.error('   Charcode del primer carácter:', responseText.charCodeAt(0));
            return;
        }
        
        console.log('Respuesta JSON:', result);
        
        if (result.status && result.data && Array.isArray(result.data)) {
            const notificaciones = result.data;
            console.log(`✅ Cargadas ${notificaciones.length} notificaciones desde BD`);
            
            // Agregar al dropdown
            let agregadas = 0;
            notificaciones.forEach(notif => {
                try {
                    this.agregarNotificacionAlDropdown({
                        idnotificacion: notif.idnotificacion,
                        titulo: notif.titulo,
                        mensaje: notif.mensaje,
                        fecha_formato: notif.fecha_formato,
                        prioridad: notif.prioridad,
                        tipo: notif.tipo,
                        leida: parseInt(notif.leida) === 1
                    });
                    agregadas++;
                } catch (e) {
                    console.error('Error agregando notificación:', e);
                }
            });
            console.log(`📌 Agregadas ${agregadas}/${notificaciones.length} notificaciones al dropdown`);
            
            // Actualizar contador con notificaciones no leídas
            const noLeidas = notificaciones.filter(n => parseInt(n.leida) === 0).length;
            this.notificacionesNoLeidas = noLeidas;
            this.actualizarBadges();
            console.log(`🔔 Badge actualizado: ${noLeidas} no leídas`);
            
        } else {
            console.log('⚠️ Respuesta sin datos esperados:');
            console.log('  - status:', result.status);
            console.log('  - data es array:', Array.isArray(result.data));
            console.log('  - mensaje:', result.message || 'Sin datos');
        }
    } catch (error) {
        console.error('❌ Error al cargar notificaciones BD:', error);
        console.error('Stack:', error.stack);
    }
};

/**
 * Cargar notificaciones desde localStorage
 */
SistemaNotificacionesWS.prototype.cargarNotificacionesLocalStorage = function() {
    try {
        const key = 'notificaciones_informativas';
        const notificaciones = JSON.parse(localStorage.getItem(key) || '[]');
        
        if (notificaciones.length > 0) {
            console.log(`📦 Cargadas ${notificaciones.length} notificaciones desde localStorage`);
            this.notificacionesInformativas = notificaciones;
            this.notificacionesNoLeidas = notificaciones.length;
        }
    } catch (error) {
        console.error('❌ Error al cargar localStorage:', error);
    }
};

/**
 * Mostrar toast para notificación informativa
 */
SistemaNotificacionesWS.prototype.mostrarToastInformativo = function(mensaje) {
    const titulo = mensaje.data?.titulo || mensaje.titulo || 'Notificación';
    const texto = mensaje.data?.mensaje || mensaje.mensaje || '';
    
    Swal.fire({
        icon: 'info',
        title: titulo,
        text: texto,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });
};

/**
 * Marca una notificación como leída
 */
SistemaNotificacionesWS.prototype.marcarComoLeida = function(idNotificacion, elementoUI = null) {
    const self = this;
    
    fetch(`${this.baseUrl}/notificaciones/marcarComoLeida`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify({
            idnotificacion: idNotificacion
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(result => {
        if (result.status) {
            console.log('✅ Notificación marcada como leída:', idNotificacion);
            
            // Decrementar contador de notificaciones no leídas
            if (this.notificacionesNoLeidas > 0) {
                this.notificacionesNoLeidas--;
            }
            
            if (elementoUI) {
                elementoUI.classList.remove('unread', 'bg-blue-50');
                elementoUI.classList.add('read', 'bg-gray-50');
                
                const btnMarkRead = elementoUI.querySelector('.btn-mark-read');
                if (btnMarkRead) {
                    btnMarkRead.remove();
                }
                
                const dot = elementoUI.querySelector('[class*="animate-pulse"]');
                if (dot) {
                    dot.remove();
                }
            }
            
            self.actualizarBadges();
        } else {
            console.error('❌ Error:', result.message);
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('Error al marcar notificación: ' + error.message);
    });
};

/**
 * Elimina una notificación (soft delete)
 */
SistemaNotificacionesWS.prototype.eliminarNotificacion = function(idNotificacion, elementoUI = null) {
    const self = this;
    
    fetch(`${this.baseUrl}/notificaciones/eliminarNotificacion`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify({
            idnotificacion: idNotificacion
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(result => {
        if (result.status) {
            console.log('✅ Notificación eliminada:', idNotificacion);
            
            if (elementoUI) {
                elementoUI.style.transition = 'opacity 0.3s ease-out';
                elementoUI.style.opacity = '0';
                
                setTimeout(() => {
                    elementoUI.remove();
                    
                    const notificationsList = document.getElementById('notifications-list');
                    if (notificationsList && notificationsList.children.length === 0) {
                        notificationsList.innerHTML = `
                            <div class="text-center p-6 text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-sm">No hay notificaciones</p>
                            </div>
                        `;
                    }
                }, 300);
            }
            
            self.actualizarBadges();
        } else {
            console.error('❌ Error:', result.message);
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
    });
};

/**
 * Marca todas las notificaciones como leídas
 */
SistemaNotificacionesWS.prototype.marcarTodasComoLeidas = function() {
    const self = this;
    
    fetch(`${this.baseUrl}/notificaciones/marcarTodasLeidas`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(result => {
        if (result.status) {
            console.log('✅ Todas las notificaciones marcadas como leídas');
            
            // Contar cuántas estaban sin leer y reset el contador
            const notificationsList = document.getElementById('notifications-list');
            if (notificationsList) {
                const items = notificationsList.querySelectorAll('.notification-item.unread');
                self.notificacionesNoLeidas = 0; // Reiniciar a 0 ya que todas se marcan como leídas
                
                items.forEach(item => {
                    item.classList.remove('unread', 'bg-blue-50');
                    item.classList.add('read', 'bg-gray-50');
                    
                    const btnMarkRead = item.querySelector('.btn-mark-read');
                    if (btnMarkRead) {
                        btnMarkRead.remove();
                    }
                    
                    const dot = item.querySelector('[class*="animate-pulse"]');
                    if (dot) {
                        dot.remove();
                    }
                });
            }
            
            self.actualizarBadges();
        } else {
            console.error('❌ Error:', result.message);
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('Error: ' + error.message);
    });
};

/**
 * Limpiar notificaciones informativas del localStorage
 */
SistemaNotificacionesWS.prototype.limpiarNotificacionesInformativas = function() {
    try {
        localStorage.removeItem('notificaciones_informativas');
        this.notificacionesInformativas = [];
        this.notificacionesNoLeidas = 0;
        this.actualizarBadges();
        console.log('🗑️ Notificaciones informativas limpiadas');
    } catch (error) {
        console.error('❌ Error al limpiar notificaciones:', error);
    }
};

// ============================================
// EJECUCIÓN
// ============================================

// Ejecutar inmediatamente si DOM ya está listo, o esperar si no
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSistemaNotificaciones);
} else {
    // DOM ya está listo, ejecutar inmediatamente
    inicializarSistemaNotificaciones();
}

// ============================================
// FUNCIONES GLOBALES (compatibilidad)
// ============================================

// Función para enviar notificación de prueba (debug)
function enviarNotificacionPrueba() {
    if (window.notificacionesWS && window.notificacionesWS.socket) {
        console.log(' Enviando notificación de prueba...');
        fetch('/opt/lampp/htdocs/project/tests/test_notificacion_helper.php')
            .then(() => console.log(' Notificación enviada'))
            .catch(err => console.error('❌ Error:', err));
    } else {
        console.error('❌ WebSocket no conectado');
    }
}
