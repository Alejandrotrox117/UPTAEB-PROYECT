document.addEventListener("DOMContentLoaded", () => {
  // --- LÓGICA DEL SIDEBAR Y MENÚ MÓVIL ---
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

  // --- LÓGICA PARA MARCAR EL ENLACE ACTIVO EN EL SIDEBAR ---
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

  // --- LÓGICA DEL SISTEMA DE NOTIFICACIONES ---
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
  notificationsList.innerHTML = `<div class="text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p>Cargando...</p></div>`;
  fetch("./Notificaciones/getNotificaciones")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        displayNotifications(result.data);
      } else {
        notificationsList.innerHTML = `<div class="text-center py-4 text-gray-500"><i class="fas fa-bell-slash fa-2x mb-2"></i><p>No hay notificaciones</p></div>`;
      }
    })
    .catch((error) => {
      console.error("Error al cargar notificaciones:", error);
      notificationsList.innerHTML = `<div class="text-center py-4 text-red-500"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Error al cargar</p></div>`;
    });
}

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