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

  
  if (document.getElementById("notifications-dropdown")) {
    initNotificationSystem();
  }
});

function initNotificationSystem() {
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
  const refreshNotificationsBtn = document.getElementById(
    "refresh-notifications-btn"
  );

  if (mobileNotificationsToggle)
    mobileNotificationsToggle.addEventListener("click", toggleNotifications);
  if (desktopNotificationsToggle)
    desktopNotificationsToggle.addEventListener("click", toggleNotifications);
  if (closeNotificationsBtn)
    closeNotificationsBtn.addEventListener("click", closeNotifications);
  if (markAllReadBtn)
    markAllReadBtn.addEventListener("click", markAllNotificationsAsRead);
  if (refreshNotificationsBtn)
    refreshNotificationsBtn.addEventListener("click", () => {
      loadNotifications();
      actualizarContadorNotificaciones();
    });

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

  loadNotifications();
  actualizarContadorNotificaciones();
  setInterval(actualizarContadorNotificaciones, 30000);
}

function toggleNotifications() {
  const dropdown = document.getElementById("notifications-dropdown");
  if (dropdown) {
    dropdown.classList.toggle("hidden");
    if (!dropdown.classList.contains("hidden")) {
      loadNotifications();
    }
  }
}

function closeNotifications() {
  const dropdown = document.getElementById("notifications-dropdown");
  if (dropdown) {
    dropdown.classList.add("hidden");
  }
}

function loadNotifications() {
  const notificationsList = document.getElementById("notifications-list");
  if (!notificationsList) return;
  
  console.log("🔔 loadNotifications - Iniciando carga de notificaciones");
  notificationsList.innerHTML = `<div class="text-center py-4 text-blue-500"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p>Cargando...</p></div>`;
  
  console.log("🔔 loadNotifications - Intentando fetch a getNotificaciones");
  fetch("./Notificaciones/getNotificaciones")
    .then((response) => {
      console.log("🔔 loadNotifications - Respuesta recibida:", response);
      console.log("🔔 loadNotifications - Status:", response.status);
      return response.json();
    })
    .then((result) => {
      console.log("🔔 loadNotifications - JSON parseado:", result);
      if (result.status && result.data) {
        console.log("🔔 loadNotifications - Mostrando notificaciones");
        displayNotifications(result.data);
        actualizarContadorNotificaciones(); // Actualizar contador también
      } else {
        console.log("🔔 loadNotifications - No hay notificaciones o error");
        notificationsList.innerHTML = `<div class="text-center py-4 text-gray-500"><i class="fas fa-bell-slash fa-2x mb-2"></i><p>No hay notificaciones</p></div>`;
      }
      
      // Mostrar información de debug si existe
      if (result.debug_info) {
        console.log("🔔 DEBUG INFO:", result.debug_info);
      }
    })
    .catch((error) => {
      console.error("🔔 ERROR al cargar notificaciones:", error);
      console.error("🔔 ERROR detalles:", error.message);
      notificationsList.innerHTML = `<div class="text-center py-4 text-red-500"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Error al cargar</p></div>`;
    });
}

function regenerarNotificacionesStock() {
  return fetch("./Compras/regenerarNotificaciones", {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  })
  .then(response => response.json())
  .then(result => {
    if (result.status) {
      console.log("Notificaciones regeneradas exitosamente");
      // Recargar notificaciones después de regenerar
      setTimeout(() => {
        cargarNotificaciones();
      }, 1000);
    } else {
      console.error("Error al regenerar notificaciones:", result.message);
    }
    return result;
  })
  .catch(error => {
    console.error("Error en regeneración:", error);
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
      // Si la compra cambió a PAGADA, esperar un momento y recargar notificaciones
      if (nuevoEstado === 'PAGADA' && estadoAnterior !== 'PAGADA') {
        setTimeout(() => {
          cargarNotificaciones();
          actualizarContadorNotificaciones();
        }, 2000); // Esperar 2 segundos para que se procese todo
      }
    }
    return result;
  });
}

// Agregar auto-actualización de notificaciones cada 30 segundos
let intervaloNotificaciones;

function iniciarAutoActualizacion() {
  // Limpiar intervalo existente si hay uno
  if (intervaloNotificaciones) {
    clearInterval(intervaloNotificaciones);
  }
  
  // Configurar nueva auto-actualización cada 30 segundos
  intervaloNotificaciones = setInterval(() => {
    actualizarContadorNotificaciones();
    
    // Solo recargar lista si está abierta
    const dropdown = document.getElementById('notifications-dropdown');
    if (dropdown && !dropdown.classList.contains('hidden')) {
      cargarNotificaciones();
    }
  }, 30000);
}

// Llamar la función cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
  iniciarAutoActualizacion();
  
  // Agregar evento al botón de refrescar notificaciones
  const refreshBtn = document.getElementById('refresh-notifications-btn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function() {
      cargarNotificaciones();
    });
  }
});

// Detener auto-actualización cuando se cierra la página
window.addEventListener('beforeunload', function() {
  if (intervaloNotificaciones) {
    clearInterval(intervaloNotificaciones);
  }
});

function displayNotifications(notifications) {
  const notificationsList = document.getElementById("notifications-list");
  if (!notificationsList) return;
  if (notifications.length === 0) {
    notificationsList.innerHTML = `<div class="text-center py-4 text-gray-500"><i class="fas fa-bell-slash fa-2x mb-2"></i><p>No hay notificaciones</p></div>`;
    return;
  }
  let notificationsHTML = "";
  notifications.forEach((notification) => {
    const isUnread = notification.leida == 0;
    let iconClass = "fas fa-info-circle text-blue-500";
    if (notification.tipo === "STOCK_BAJO")
      iconClass = "fas fa-exclamation-triangle text-yellow-500";
    if (notification.tipo === "SIN_STOCK")
      iconClass = "fas fa-times-circle text-red-500";
    notificationsHTML += `<div class="notification-item p-3 border-b border-gray-100 cursor-pointer ${
      isUnread ? "unread" : ""
    }" data-notification-id="${
      notification.idnotificacion
    }" onclick="markNotificationAsRead(${notification.idnotificacion})"><div class="flex items-start space-x-3"><div class="flex-shrink-0"><i class="${iconClass} text-lg"></i></div><div class="flex-1 min-w-0"><div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-900 truncate">${
      notification.titulo
    }</p>${
      isUnread ? '<div class="w-2 h-2 bg-red-500 rounded-full"></div>' : ""
    }</div><p class="text-xs text-gray-600 mt-1">${
      notification.mensaje
    }</p><div class="flex items-center justify-between mt-2"><span class="text-xs font-medium">${
      notification.prioridad || "MEDIA"
    }</span><span class="text-xs text-gray-400">${
      notification.fecha_formato
    }</span></div></div></div></div>`;
  });
  notificationsList.innerHTML = notificationsHTML;
}

function actualizarContadorNotificaciones() {
  fetch("./Notificaciones/getContadorNotificaciones")
    .then((response) => response.json())
    .then((result) => {
      const badges = document.querySelectorAll(
        "#mobile-notification-badge, #desktop-notification-badge"
      );
      const count = result.count || 0;
      badges.forEach((badge) => {
        if (badge) {
          badge.textContent = count > 99 ? "99+" : count;
          badge.classList.toggle("hidden", count === 0);
        }
      });
    });
}

function markNotificationAsRead(notificationId) {
  fetch("./Notificaciones/marcarLeida", {
    method: "POST",
    body: JSON.stringify({ idnotificacion: notificationId }),
    headers: { "Content-Type": "application/json" },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        const el = document.querySelector(
          `[data-notification-id="${notificationId}"]`
        );
        if (el) {
          el.classList.remove("unread");
          const dot = el.querySelector(".w-2.h-2.bg-red-500");
          if (dot) dot.remove();
        }
        actualizarContadorNotificaciones();
      }
    });
}

function markAllNotificationsAsRead() {
  fetch("./Notificaciones/marcarTodasLeidas", { method: "POST" })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        document
          .querySelectorAll(".notification-item.unread")
          .forEach((item) => {
            item.classList.remove("unread");
            const dot = item.querySelector(".w-2.h-2.bg-red-500");
            if (dot) dot.remove();
          });
        actualizarContadorNotificaciones();
      }
    });
}

window.markNotificationAsRead = markNotificationAsRead;