/**
 * Cliente WebSocket para notificaciones en tiempo real
 * Conecta con el servidor Ratchet y maneja notificaciones push
 */

class NotificationsWebSocket {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 2000;
        this.userId = null;
        this.pingInterval = null;

        this.init();
    }

    init() {
        // Obtener ID del usuario de la sesión
        this.userId = document.body.dataset.userId || this.getUserIdFromSession();

        if (!this.userId) {
            console.error('❌ No se pudo obtener el ID del usuario');
            return;
        }

        console.log('🔧 Inicializando WebSocket para usuario:', this.userId);
        this.connect();

        // Ping cada 30 segundos para mantener conexión viva
        this.pingInterval = setInterval(() => this.ping(), 30000);
    }

    connect() {
        const wsUrl = `ws://localhost:8080?userId=${this.userId}`;

        console.log(`🔌 Conectando a ${wsUrl}...`);

        try {
            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => this.onOpen();
            this.ws.onmessage = (event) => this.onMessage(event);
            this.ws.onclose = () => this.onClose();
            this.ws.onerror = (error) => this.onError(error);
        } catch (error) {
            console.error('❌ Error al crear WebSocket:', error);
            this.scheduleReconnect();
        }
    }

    onOpen() {
        console.log('✅ Conectado al servidor de notificaciones');
        this.reconnectAttempts = 0;

        // Mostrar indicador de conexión
        this.updateConnectionStatus(true);
    }

    onMessage(event) {
        try {
            const message = JSON.parse(event.data);

            console.log('📨 Mensaje recibido:', message);

            switch (message.action) {
                case 'notificaciones_iniciales':
                    this.cargarNotificacionesIniciales(message.data);
                    break;

                case 'nueva_notificacion':
                    this.mostrarNuevaNotificacion(message.data);
                    break;

                case 'notificacion_marcada':
                    this.actualizarNotificacionLeida(message.notificacionId);
                    break;

                case 'pong':
                    // Respuesta al ping - conexión activa
                    break;

                default:
                    console.warn('⚠️ Acción desconocida:', message.action);
            }

        } catch (error) {
            console.error('❌ Error al procesar mensaje:', error);
        }
    }

    onClose() {
        console.log('🔌 Desconectado del servidor');
        this.updateConnectionStatus(false);

        this.scheduleReconnect();
    }

    scheduleReconnect() {
        // Intentar reconectar
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = this.reconnectDelay * this.reconnectAttempts;

            console.log(`🔄 Reconectando en ${delay / 1000}s... (intento ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

            setTimeout(() => this.connect(), delay);
        } else {
            console.error('❌ No se pudo reconectar. Por favor, recarga la página.');
            this.showReconnectError();
        }
    }

    onError(error) {
        console.error('❌ Error de WebSocket:', error);
    }

    // Métodos de interfaz

    cargarNotificacionesIniciales(notificaciones) {
        const mobileBadge = document.getElementById('mobile-notification-badge');
        const desktopBadge = document.getElementById('desktop-notification-badge');
        const list = document.getElementById('notification-list');

        if (!list) {
            console.warn('⚠️ Elemento notification-list no encontrado en el DOM');
            return;
        }

        // Limpiar lista
        list.innerHTML = '';

        // Actualizar contador
        const noLeidas = notificaciones.filter(n => !n.leida).length;
        [mobileBadge, desktopBadge].forEach(badge => {
            if (badge) {
                badge.textContent = noLeidas > 99 ? '99+' : noLeidas;
                badge.classList.toggle('hidden', noLeidas === 0);
            }
        });

        // Mostrar notificaciones
        if (notificaciones.length === 0) {
            list.innerHTML = '<div class="notification-item empty">No tienes notificaciones</div>';
        } else {
            notificaciones.forEach(notif => {
                this.agregarNotificacionALista(notif);
            });
        }

        console.log(`📬 Cargadas ${notificaciones.length} notificaciones (${noLeidas} no leídas)`);
    }

    mostrarNuevaNotificacion(notificacion) {
        console.log('🔔 Nueva notificación recibida:', notificacion);

        // Agregar a la lista
        this.agregarNotificacionALista(notificacion, true);

        // Actualizar contador en ambos badges
        const mobileBadge = document.getElementById('mobile-notification-badge');
        const desktopBadge = document.getElementById('desktop-notification-badge');

        [mobileBadge, desktopBadge].forEach(badge => {
            if (badge) {
                const count = parseInt(badge.textContent || 0) + 1;
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            }
        });

        // Notificación del navegador
        if (Notification.permission === 'granted') {
            new Notification(notificacion.titulo, {
                body: notificacion.mensaje,
                icon: '/app/assets/img/logo.png',
                tag: `notif-${notificacion.idnotificacion}`
            });
        }

        // Sonido (opcional)
        this.playNotificationSound();

        // Animación visual
        this.flashNotificationIcon();
    }

    agregarNotificacionALista(notificacion, prepend = false) {
        const list = document.getElementById('notification-list');
        if (!list) return;

        // Remover mensaje de "sin notificaciones"
        const emptyMsg = list.querySelector('.notification-item.empty');
        if (emptyMsg) {
            emptyMsg.remove();
        }

        const item = document.createElement('div');
        item.className = `notification-item ${!notificacion.leida ? 'unread' : ''}`;
        item.dataset.id = notificacion.idnotificacion;

        item.innerHTML = `
            <div class="notification-icon ${this.getIconClass(notificacion.prioridad)}">
                <i class="${this.getIconByType(notificacion.tipo)}"></i>
            </div>
            <div class="notification-content">
                <strong>${this.escapeHtml(notificacion.titulo)}</strong>
                <p>${this.escapeHtml(notificacion.mensaje)}</p>
                <small>${notificacion.fecha_formato || new Date().toLocaleString()}</small>
            </div>
        `;

        item.onclick = () => this.marcarComoLeida(notificacion.idnotificacion);

        if (prepend) {
            list.prepend(item);
        } else {
            list.appendChild(item);
        }
    }

    marcarComoLeida(notificacionId) {
        console.log('📖 Marcando notificación como leída:', notificacionId);
        this.send({
            action: 'marcar_leida',
            notificacionId: notificacionId
        });
    }

    actualizarNotificacionLeida(notificacionId) {
        const item = document.querySelector(`[data-id="${notificacionId}"]`);
        if (item) {
            item.classList.remove('unread');
        }

        // Actualizar contador en ambos badges
        const mobileBadge = document.getElementById('mobile-notification-badge');
        const desktopBadge = document.getElementById('desktop-notification-badge');

        [mobileBadge, desktopBadge].forEach(badge => {
            if (badge) {
                const currentCount = parseInt(badge.textContent || 0);
                const count = Math.max(0, currentCount - 1);
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.toggle('hidden', count === 0);
            }
        });
    }

    // Métodos auxiliares

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.error('❌ WebSocket no está conectado');
        }
    }

    ping() {
        this.send({ action: 'ping' });
    }

    getUserIdFromSession() {
        // Intentar obtener del localStorage o de una meta tag
        return localStorage.getItem('userId') ||
            document.querySelector('meta[name="user-id"]')?.content;
    }

    getIconByType(tipo) {
        const icons = {
            'LOGIN_USUARIO': 'fas fa-sign-in-alt',
            'LOGOUT_USUARIO': 'fas fa-sign-out-alt',
            'STOCK_BAJO': 'fas fa-exclamation-triangle',
            'SIN_STOCK': 'fas fa-times-circle',
            'COMPRA_POR_AUTORIZAR': 'fas fa-clipboard-check',
            'COMPRA_AUTORIZADA_PAGO': 'fas fa-dollar-sign',
            'COMPRA_COMPLETADA': 'fas fa-check-circle',
            'VENTA_NUEVA': 'fas fa-shopping-cart',
            'VENTA_CANCELADA': 'fas fa-ban'
        };
        return icons[tipo] || 'fas fa-bell';
    }

    getIconClass(prioridad) {
        const classes = {
            'CRITICA': 'danger',
            'ALTA': 'warning',
            'MEDIA': 'info',
            'BAJA': 'secondary'
        };
        return classes[prioridad] || 'info';
    }

    updateConnectionStatus(connected) {
        const indicator = document.getElementById('ws-status');
        if (indicator) {
            indicator.className = connected ? 'ws-status connected' : 'ws-status disconnected';
            indicator.title = connected ? 'Conectado en tiempo real' : 'Desconectado';
        }
    }

    showReconnectError() {
        // Mostrar mensaje al usuario usando Swal si está disponible
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Conexión perdida',
                text: 'No se pudo reconectar al servidor de notificaciones. Por favor, recarga la página.',
                confirmButtonText: 'Recargar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        } else {
            alert('Conexión perdida. Por favor, recarga la página.');
        }
    }

    playNotificationSound() {
        // Intentar reproducir sonido (opcional)
        try {
            const audio = new Audio('/app/assets/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(e => {
                // Silenciosamente fallar si no hay permiso o archivo
                console.log('ℹ️ No se pudo reproducir sonido de notificación');
            });
        } catch (e) {
            // Ignorar errores de audio
        }
    }

    flashNotificationIcon() {
        const icon = document.querySelector('.notification-icon-header');
        if (icon) {
            icon.classList.add('flash');
            setTimeout(() => icon.classList.remove('flash'), 1000);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
        }
        if (this.ws) {
            this.ws.close();
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Pedir permisos de notificaciones del navegador
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            console.log('🔔 Permisos de notificación:', permission);
        });
    }

    // Iniciar WebSocket solo si hay un usuario logueado
    const userId = document.body.dataset.userId;
    if (userId) {
        window.notificationsWS = new NotificationsWebSocket();
        console.log('✅ Cliente WebSocket inicializado');
    } else {
        console.log('ℹ️ No hay usuario logueado, WebSocket no inicializado');
    }
});

// Limpiar al salir de la página
window.addEventListener('beforeunload', () => {
    if (window.notificationsWS) {
        window.notificationsWS.destroy();
    }
});
