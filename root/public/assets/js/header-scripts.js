/**
 * Accessible Tabler-compatible notification toasts.
 */
(() => {
  const stack = () => {
    let container = document.getElementById("notification-stack");
    if (!container) {
      container = document.createElement("div");
      container.id = "notification-stack";
      container.className = "app-toast-stack";
      container.setAttribute("aria-live", "polite");
      container.setAttribute("aria-relevant", "additions text");
      document.body.appendChild(container);
    }
    return container;
  };

  const dismiss = (toast) => {
    toast.classList.add("is-dismissing");
    window.setTimeout(() => toast.remove(), 180);
  };

  const addDismissControl = (toast) => {
    const close = document.createElement("button");
    close.className = "btn-close";
    close.type = "button";
    close.setAttribute("data-toast-dismiss", "");
    close.setAttribute("aria-label", "Dismiss notification");
    toast.appendChild(close);
  };

  const attachLifetime = (toast) => {
    let remaining = 5000;
    let startedAt = Date.now();
    let timeoutId = window.setTimeout(() => dismiss(toast), remaining);
    toast.addEventListener("mouseenter", () => {
      window.clearTimeout(timeoutId);
      remaining -= Date.now() - startedAt;
    });
    toast.addEventListener("mouseleave", () => {
      startedAt = Date.now();
      timeoutId = window.setTimeout(() => dismiss(toast), Math.max(remaining, 500));
    });
  };

  document.addEventListener("click", (event) => {
    const close = event.target.closest("[data-toast-dismiss]");
    if (close) {
      const toast = close.closest(".app-toast");
      if (toast) dismiss(toast);
    }
  });

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("#notification-stack .app-toast").forEach(attachLifetime);

    const navWrap = document.querySelector(".admin-nav-wrap");
    const navToggle = document.querySelector(".mobile-menu-toggle");
    const nav = navWrap?.querySelector(".admin-nav");
    if (!navWrap || !navToggle || !nav) return;

    navToggle.hidden = false;
    navWrap.classList.add("mobile-nav-ready");
    const mobileViewport = window.matchMedia("(max-width: 767.98px)");

    const setNavOpen = (open) => {
      navToggle.setAttribute("aria-expanded", String(open));
      navToggle.setAttribute("aria-label", open ? "Close navigation menu" : "Open navigation menu");
      navWrap.classList.toggle("is-open", open);
      nav.inert = mobileViewport.matches && !open;
    };

    setNavOpen(false);

    navToggle.addEventListener("click", () => {
      setNavOpen(navToggle.getAttribute("aria-expanded") !== "true");
    });

    document.addEventListener("click", (event) => {
      if (!navWrap.contains(event.target) && !navToggle.contains(event.target)) setNavOpen(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && navToggle.getAttribute("aria-expanded") === "true") {
        setNavOpen(false);
        navToggle.focus();
      }
    });

    mobileViewport.addEventListener("change", (event) => {
      setNavOpen(false);
      nav.inert = event.matches;
    });
  });

  window.showToast = (message, variant = "info") => {
    const variantClass = {
      info: "info",
      success: "success",
      error: "danger",
      warning: "warning",
    }[variant] || "info";
    const toast = document.createElement("div");
    toast.className = `alert alert-${variantClass} app-toast is-entering`;
    toast.setAttribute("role", variant === "error" ? "alert" : "status");
    const text = document.createElement("span");
    text.textContent = String(message);
    toast.appendChild(text);
    addDismissControl(toast);
    stack().appendChild(toast);
    window.requestAnimationFrame(() => toast.classList.remove("is-entering"));
    attachLifetime(toast);
  };
})();
