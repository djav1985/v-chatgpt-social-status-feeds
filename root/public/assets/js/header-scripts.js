/** Display an accessible Tabler toast. */
function showToast(message, variant = "info") {
  const container = document.getElementById("toast-container");
  if (!container) return;

  const safeVariant = ["success", "error", "info"].includes(variant)
    ? variant
    : "info";
  const toast = document.createElement("div");
  toast.className = `toast toast-${safeVariant}`;
  toast.setAttribute("role", safeVariant === "error" ? "alert" : "status");
  toast.setAttribute("aria-live", safeVariant === "error" ? "assertive" : "polite");
  toast.setAttribute("aria-atomic", "true");

  const body = document.createElement("div");
  body.className = "toast-body d-flex align-items-start gap-2";
  const text = document.createElement("span");
  text.className = "flex-grow-1";
  text.textContent = String(message);
  const close = document.createElement("button");
  close.type = "button";
  close.className = "btn-close";
  close.setAttribute("aria-label", "Dismiss notification");
  body.append(text, close);
  toast.append(body);
  container.appendChild(toast);

  const dismiss = () => {
    toast.classList.remove("show");
    window.setTimeout(() => toast.remove(), 220);
  };
  close.addEventListener("click", dismiss, { once: true });
  window.requestAnimationFrame(() => toast.classList.add("show"));
  let remaining = 5000;
  let startedAt = Date.now();
  let timeoutId = window.setTimeout(dismiss, remaining);
  toast.addEventListener("mouseenter", () => {
    window.clearTimeout(timeoutId);
    remaining -= Date.now() - startedAt;
  });
  toast.addEventListener("mouseleave", () => {
    startedAt = Date.now();
    timeoutId = window.setTimeout(dismiss, Math.max(remaining, 500));
  });
}

window.showToast = showToast;

document.addEventListener("DOMContentLoaded", () => {
  const navWrap = document.querySelector(".admin-nav-wrap");
  const navToggle = document.querySelector(".mobile-menu-toggle");
  const nav = navWrap?.querySelector(".admin-nav");
  if (!navWrap || !navToggle || !nav) return;

  navToggle.hidden = false;
  navWrap.classList.add("mobile-nav-ready");
  const mobileViewport = window.matchMedia("(max-width: 767.98px)");

  const setNavOpen = (open) => {
    const activeSection = navToggle.dataset.menuSection || 'section';
    navToggle.setAttribute("aria-expanded", String(open));
    navToggle.setAttribute("aria-label", `${open ? "Close" : "Open"} ${activeSection} navigation menu`);
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
