(function () {
  const body = document.body;
  const navToggle = document.querySelector("[data-nav-toggle]");
  const nav = document.querySelector("[data-nav]");

  function closeNav() {
    if (!nav || !navToggle) return;
    nav.classList.remove("is-open");
    navToggle.classList.remove("is-open");
    navToggle.setAttribute("aria-expanded", "false");
    body.classList.remove("is-locked");
  }

  if (navToggle && nav) {
    navToggle.addEventListener("click", () => {
      const isOpen = nav.classList.toggle("is-open");
      navToggle.classList.toggle("is-open", isOpen);
      navToggle.setAttribute("aria-expanded", String(isOpen));
      body.classList.toggle("is-locked", isOpen);
    });

    nav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", closeNav);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") closeNav();
    });
  }

  document.querySelectorAll("[data-wa-message]").forEach((link) => {
    const message = link.getAttribute("data-wa-message");
    if (message) {
      link.href = "https://wa.me/33768655643?text=" + encodeURIComponent(message);
    }
  });

  function initReveal() {
    const items = document.querySelectorAll(
      ".hero-content, .section-head, .section-title, .split, .card, .pill-card, .creation-widget, .artisan-card, .universe-card, .method-card, .method-section .step, .review-card, .contact-card, .contact-form, .timeline-item, .cta-band, .pro-lounge-hero__copy, .pro-lounge-hero__visual, .pro-step-card, .pro-expertise-card, .pro-final-cta__box"
    );

    items.forEach((item) => {
      if (!item.classList.contains("reveal")) {
        item.classList.add("reveal");
      }
    });

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches || !("IntersectionObserver" in window)) {
      items.forEach((item) => item.classList.add("is-visible"));
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12 }
    );

    items.forEach((item) => {
      if (!item.classList.contains("is-visible")) observer.observe(item);
    });
  }

  initReveal();
  window.addEventListener("fatystyle:content-ready", initReveal);

  function applyRequestedContactType() {
    const select = document.getElementById("requestType");
    const requestedType = new URLSearchParams(window.location.search).get("type");
    if (!select || !requestedType) return;
    const normalized = requestedType.trim().toLowerCase();
    Array.from(select.options).some((option) => {
      const matches = option.value.trim().toLowerCase() === normalized || option.text.trim().toLowerCase() === normalized;
      if (matches) select.value = option.value;
      return matches;
    });
  }

  function initContactForm() {
    const form = document.querySelector("[data-contact-form]");
    if (!form) return;

    const params = new URLSearchParams(window.location.search);
    const alert = document.querySelector("[data-form-alert]");
    const redirect = form.querySelector("[data-form-redirect]");
    const replyTo = form.querySelector("[data-form-replyto]");
    const email = form.querySelector('#email');

    if (params.has("erreur") && alert) alert.hidden = false;
    if (redirect) redirect.value = new URL("message-envoye.html", window.location.href).href;
    applyRequestedContactType();

    if (form.dataset.formBound === "true") return;
    form.dataset.formBound = "true";
    form.addEventListener("submit", (event) => {
      form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]), textarea').forEach((field) => {
        field.value = field.value.trim();
      });
      if (replyTo && email) replyTo.value = email.value;
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });
  }

  initContactForm();
  window.addEventListener("fatystyle:content-ready", () => {
    initContactForm();
    applyRequestedContactType();
  });
})();
