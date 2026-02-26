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



});

// Exportar funciones para compatibilidad
window.markNotificationAsRead = window.marcarNotificacionComoLeida;