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

        console.log('🔌 Inicializando Sistema de Notificaciones WebSocket');
        this.conectar();
    }

    conectar() {
        console.log(`🔌 Conectando a WebSocket (intento ${this.reconexionIntento + 1})...`);

        try {
            this.socket = new WebSocket('ws://localhost:8080');

            this.socket.onopen = () => {
                console.log('✅ WebSocket conectado');
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

            switch (mensaje.tipo) {
                case 'conexion':
                    console.log('✅', mensaje.mensaje);
                    break;

                case 'autenticacion':
                    console.log('✅ Autenticación:', mensaje.mensaje);
                    break;

                // Notificaciones de productos
                case 'TEST_STOCK_BAJO':
                case 'STOCK_BAJO':
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

        // Agregar al dropdown si está abierto
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown && !dropdown.classList.contains('hidden')) {
            this.agregarNotificacionAlDropdown(data);
        }
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

        // Determinar tipo de icono
        const iconClass = this.obtenerIconoPorTipo(data.tipo);
        const prioridadClass = this.obtenerClasePrioridad(data.prioridad || 'MEDIA');

        const now = new Date();
        const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;

        const notificationHTML = `
            <div class="notification-item p-3 border-b border-gray-100 bg-blue-50 unread">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <i class="${iconClass} text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                ${data.titulo}
                            </p>
                            <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">${data.mensaje}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs font-medium px-2 py-1 rounded ${prioridadClass}">
                                ${data.prioridad || 'MEDIA'}
                            </span>
                            <span class="text-xs text-gray-400">Ahora (${timeString})</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insertar al inicio
        notificationsList.insertAdjacentHTML('afterbegin', notificationHTML);
        console.log('✅ Notificación agregada al dropdown');
    }

    /**
     * Obtiene clase de icono según tipo
     */
    obtenerIconoPorTipo(tipo) {
        const iconos = {
            'PRODUCTO_NUEVO': 'fas fa-box text-green-500',
            'PRODUCTO_ACTUALIZADO': 'fas fa-edit text-blue-500',
            'PRODUCTO_DESACTIVADO': 'fas fa-ban text-red-500',
            'PRODUCTO_ACTIVADO': 'fas fa-check-circle text-green-500',
            'STOCK_BAJO': 'fas fa-exclamation-triangle text-yellow-500',
            'SIN_STOCK': 'fas fa-times-circle text-red-500',
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
            badge.classList.toggle('hidden', count === 0);
        });
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
    // Verificar que estamos en una página con sesión
    if (typeof base_url === 'undefined') {
        console.warn('⚠️ base_url no definida, no se puede conectar WebSocket');
        return;
    }

    // Obtener datos de sesión desde PHP
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
                window.notificacionesWS.limpiarContador();

                // Limpiar visualmente
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
            });
        }
    } else {
        console.warn('⚠️ No hay sesión de usuario, WebSocket no iniciado');
    }
}

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
        console.log('📤 Enviando notificación de prueba...');
        fetch('/opt/lampp/htdocs/project/tests/test_notificacion_helper.php')
            .then(() => console.log('✅ Notificación enviada'))
            .catch(err => console.error('❌ Error:', err));
    } else {
        console.error('❌ WebSocket no conectado');
    }
}
