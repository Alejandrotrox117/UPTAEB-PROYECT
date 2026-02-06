// ============================================
// FUNCIONES DE UI DEL HEADER - SOLO WEBSOCKET
// ============================================
// Este archivo maneja SOLO la interfaz de usuario.
// Las notificaciones son manejadas automáticamente por notifications-websocket.js
// ============================================

document.addEventListener("DOMContentLoaded", () => {

  const mobileMenuToggle = document.getElementById("mobile-menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const sidebarOverlay = document.getElementById("sidebar-overlay");
  const sidebarClose = document.getElementById("sidebar-close");

  function openSidebar() {
    if (sidebar && sidebarOverlay) {
      sidebar.classList.remove("-translate-x-full");
      sidebarOverlay.classList.remove("hidden");
      document.body.classList.add("overflow-hidden", "lg:overflow-auto");
    }
  }

  function closeSidebar() {
    if (sidebar && sidebarOverlay) {
      sidebar.classList.add("-translate-x-full");
      sidebarOverlay.classList.add("hidden");
      document.body.classList.remove("overflow-hidden", "lg:overflow-auto");
    }
  }

  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", openSidebar);
  }

  if (sidebarClose) {
    sidebarClose.addEventListener("click", closeSidebar);
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", closeSidebar);
  }

  const sidebarLinks = sidebar ? sidebar.querySelectorAll("a.nav-link") : [];
  sidebarLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (window.innerWidth < 1024) {
        closeSidebar();
      }
    });
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 1024) {
      closeSidebar();
    }
  });


  const currentPath = window.location.pathname;
  const navLinksQuery = "#sidebar nav a.nav-link";
  const allNavLinks = document.querySelectorAll(navLinksQuery);

  allNavLinks.forEach((link) => {
    const linkElement = link;
    const listItem = linkElement.closest("li.menu-item, li.menu-item-group");
    const icon = linkElement.querySelector("i.nav-icon");
    const textSpan = linkElement.querySelector("span.nav-text");

    if (listItem) listItem.classList.remove("bg-green-600");
    linkElement.classList.remove("text-white");
    if (icon) icon.classList.remove("text-white");
    if (textSpan) textSpan.classList.remove("text-white");

    if (linkElement.getAttribute("href") === currentPath) {
      if (listItem) {
        listItem.classList.add("bg-green-600", "rounded-md");
        linkElement.classList.add("text-white");
        if (icon) icon.classList.add("text-white");
        if (textSpan) textSpan.classList.add("text-white");

        linkElement.classList.remove(
          "hover:bg-green-100",
          "hover:text-green-700"
        );
        if (icon)
          icon.classList.remove("text-gray-500", "group-hover:text-green-600");
        if (textSpan) textSpan.classList.remove("text-gray-700");

        let parentDetails = linkElement.closest("details");
        if (parentDetails) {
          parentDetails.setAttribute("open", "");
          const summary = parentDetails.querySelector("summary.nav-link-summary");
          if (summary) {
            summary.classList.add("bg-green-600", "text-white");
            const summaryIcon = summary.querySelector("i.nav-icon");
            const summaryText = summary.querySelector("span.nav-text");
            if (summaryIcon) summaryIcon.classList.add("text-white");
            if (summaryText) summaryText.classList.add("text-white");
            summary.classList.remove(
              "hover:bg-green-100",
              "hover:text-green-700"
            );
          }
        }
      }
    }
  });


  // Sistema de notificaciones ahora manejado por WebSocket
  // Mantener solo UI handlers básicos
  if (document.getElementById("notifications-dropdown")) {
    initNotificationUI();
  }
});

// ============================================
// SISTEMA DE NOTIFICACIONES VÍA WEBSOCKET
// Las notificaciones ahora se manejan automáticamente
// ============================================

function initNotificationUI() {
  const mobileNotificationsToggle = document.getElementById(
    "mobile-notifications-toggle"
  );
  const desktopNotificationsToggle = document.getElementById(
    "desktop-notifications-toggle"
  );
  const notificationsDropdown = document.getElementById(
    "notifications-dropdown"
  );
  const closeNotificationsBtn = document.getElementById(
    "close-notifications-btn"
  );
  const markAllReadBtn = document.getElementById("mark-all-read-btn");

  // Toggle para abrir/cerrar dropdown
  if (mobileNotificationsToggle)
    mobileNotificationsToggle.addEventListener("click", toggleNotifications);
  if (desktopNotificationsToggle)
    desktopNotificationsToggle.addEventListener("click", toggleNotifications);
  if (closeNotificationsBtn)
    closeNotificationsBtn.addEventListener("click", closeNotifications);
  if (markAllReadBtn)
    markAllReadBtn.addEventListener("click", markAllNotificationsAsRead);

  // Cerrar dropdown al hacer click fuera
  document.addEventListener("click", (event) => {
    if (
      notificationsDropdown &&
      !notificationsDropdown.contains(event.target) &&
      !mobileNotificationsToggle?.contains(event.target) &&
      !desktopNotificationsToggle?.contains(event.target)
    ) {
      closeNotifications();
    }
  });
}

function toggleNotifications() {
  const dropdown = document.getElementById("notifications-dropdown");
  if (dropdown) {
    dropdown.classList.toggle("hidden");
  }
}

function closeNotifications() {
  const dropdown = document.getElementById("notifications-dropdown");
  if (dropdown) {
    dropdown.classList.add("hidden");
  }
}

// Función para marcar todas como leídas (usa el backend)
function markAllNotificationsAsRead() {
  fetch(`${base_url}/notificaciones/marcarTodasLeidas`, { method: "POST" })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        // Actualizar UI
        document
          .querySelectorAll(".notification-item.unread")
          .forEach((item) => {
            item.classList.remove("unread");
          });

        // Actualizar badges
        const mobileBadge = document.getElementById('mobile-notification-badge');
        const desktopBadge = document.getElementById('desktop-notification-badge');
        [mobileBadge, desktopBadge].forEach(badge => {
          if (badge) {
            badge.textContent = '0';
            badge.classList.add('hidden');
          }
        });

        console.log('✅ Todas las notificaciones marcadas como leídas');
      }
    })
    .catch((error) => {
      console.error('❌ Error al marcar todas como leídas:', error);
    });
}

// ============================================
// FUNCIONES DE COMPATIBILIDAD PARA CÓDIGO LEGACY
// Estas funciones se mantienen para no romper código existente
// pero ahora delegan en el sistema WebSocket
// ============================================

function regenerarNotificacionesStock() {
  console.log('⚠️ regenerarNotificacionesStock: Las notificaciones se actualizarán automáticamente vía WebSocket');
  return fetch(`${base_url}/compras/regenerarNotificaciones`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  })
    .then(response => response.json())
    .then(result => {
      if (result.status) {
        console.log("✅ Notificaciones regeneradas - se actualizarán automáticamente");
      }
      return result;
    })
    .catch(error => {
      console.error("❌ Error en regeneración:", error);
      return { status: false, message: error.message };
    });
}

// Función para manejar cambios de estado de compra
function cambiarEstadoCompra(idcompra, nuevoEstado, estadoAnterior) {
  const formData = new FormData();
  formData.append('idcompra', idcompra);
  formData.append('estado', nuevoEstado);
  formData.append('estado_anterior', estadoAnterior);

  return fetch("./Compras/cambiarEstado", {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(result => {
      if (result.status) {
        console.log('✅ Estado cambiado - notificaciones actualizadas vía WebSocket');
      }
      return result;
    });
}

// Funciones obsoletas - Ahora todo se maneja via WebSocket
// Se mantienen como stubs para evitar errores en código legacy

function loadNotifications() {
  console.warn('⚠️ loadNotifications está obsoleta - las notificaciones se cargan automáticamente vía WebSocket');
}

function displayNotifications(notifications) {
  console.warn('⚠️ displayNotifications está obsoleta - el WebSocket maneja la visualización');
}

function actualizarContadorNotificaciones() {
  console.warn('⚠️ actualizarContadorNotificaciones está obsoleta - el WebSocket actualiza el contador automáticamente');
}

function markNotificationAsRead(notificationId) {
  // Redirigir a la implementación WebSocket
  if (window.notificationsWS) {
    window.notificationsWS.marcarComoLeida(notificationId);
  } else {
    console.warn('⚠️ WebSocket no disponible, usando método legacy');
    fetch(`${base_url}/notificaciones/marcarLeida`, {
      method: "POST",
      body: JSON.stringify({ idnotificacion: notificationId }),
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.status) {
          const el = document.querySelector(`[data-notification-id="${notificationId}"]`);
          if (el) {
            el.classList.remove("unread");
          }
        }
      });
  }
}

// Exportar para compatibilidad
window.markNotificationAsRead = markNotificationAsRead;