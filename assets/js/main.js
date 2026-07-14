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
})();
