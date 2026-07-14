(function () {
  const page = (location.pathname.split("/").pop() || "index.html").replace(/#.*$/, "");
  const cacheBust = "ts=" + Date.now();

  const text = (selector, value, root = document) => {
    const node = root.querySelector(selector);
    if (node && value != null) node.textContent = value;
  };

  const attr = (selector, name, value, root = document) => {
    const node = root.querySelector(selector);
    if (node && value) node.setAttribute(name, value);
  };

  const esc = (value) => String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");

  const wa = (site, message) => `https://wa.me/${site.whatsapp || "33768655643"}?text=${encodeURIComponent(message || "Bonjour Faty Style, je souhaite vous contacter pour un projet couture.")}`;

  function setMeta(name, value) {
    if (!value) return;
    let meta = document.querySelector(`meta[name="${name}"]`);
    if (!meta) {
      meta = document.createElement("meta");
      meta.setAttribute("name", name);
      document.head.appendChild(meta);
    }
    meta.setAttribute("content", value);
  }

  function setOg(property, value) {
    if (!value) return;
    let meta = document.querySelector(`meta[property="${property}"]`);
    if (!meta) {
      meta = document.createElement("meta");
      meta.setAttribute("property", property);
      document.head.appendChild(meta);
    }
    meta.setAttribute("content", value);
  }

  function setLinkRel(rel, href) {
    if (!href) return;
    let link = document.querySelector(`link[rel="${rel}"]`);
    if (!link) {
      link = document.createElement("link");
      link.setAttribute("rel", rel);
      document.head.appendChild(link);
    }
    link.setAttribute("href", href);
  }

  function updateSeo(content) {
    const seoByPage = (content.pages && content.pages[page] && content.pages[page].seo) || {};
    const titleValue = seoByPage.title || content.seo?.title || content.site?.metaTitle;
    const description = seoByPage.description || content.seo?.description || content.site?.metaDescription;
    const existingCanonical = document.querySelector('link[rel="canonical"]')?.href;
    const siteUrl = content.seo?.siteUrl || existingCanonical || `${window.location.origin}/`;
    const canonical = new URL(page || "index.html", siteUrl.endsWith("/") ? siteUrl : `${siteUrl}/`).href;
    const socialImage = new URL(content.seo?.image || "assets/images/hero/1.jpg", siteUrl).href;
    if (titleValue) document.title = titleValue;
    setMeta("description", description);
    if (Array.isArray(content.seo?.keywords)) setMeta("keywords", content.seo.keywords.join(", "));
    setLinkRel("canonical", canonical);
    setOg("og:title", titleValue);
    setOg("og:description", description);
    setOg("og:url", canonical);
    setOg("og:image", socialImage);
    setMeta("twitter:card", "summary_large_image");
    setMeta("twitter:title", titleValue);
    setMeta("twitter:description", description);
    setMeta("twitter:image", socialImage);
  }

  function updateCommon(content) {
    const site = content.site || {};
    document.querySelectorAll('.top-strip a[href^="tel:"], .footer-links a[href^="tel:"], .contact-line[href^="tel:"]').forEach((link) => {
      link.href = "tel:+" + String(site.whatsapp || "33768655643");
      link.textContent = site.phone || link.textContent;
    });
    document.querySelectorAll('.top-strip a[href^="mailto:"], .footer-links a[href^="mailto:"], .contact-line[href^="mailto:"]').forEach((link) => {
      link.href = "mailto:" + (site.email || "fatystyle@hotmail.fr");
      link.textContent = site.email || link.textContent;
    });
    document.querySelectorAll("[data-wa-message]").forEach((link) => {
      link.href = wa(site, link.dataset.waMessage);
    });
    document.querySelectorAll(".top-strip span").forEach((node) => {
      node.textContent = site.hours || node.textContent;
    });
    document.querySelectorAll(".footer-social a, .social-row a").forEach((link) => {
      const label = link.textContent.trim().toLowerCase();
      if (label.includes("facebook") && site.facebook) link.href = site.facebook;
      if (label.includes("instagram") && site.instagram) link.href = site.instagram;
    });
    document.querySelectorAll(".footer-grid > div:first-child p").forEach((node) => {
      node.textContent = content.footer?.text || node.textContent;
    });
    document.querySelectorAll(".footer-bottom").forEach((node) => {
      node.textContent = content.footer?.copyright || node.textContent;
    });

    const navigation = Array.isArray(content.navigation) ? content.navigation : [];
    if (navigation.length) {
      const current = page || "index.html";
      document.querySelectorAll(".main-nav ul").forEach((list) => {
        list.innerHTML = navigation.map((item) => {
          const url = item.url || "#";
          const active = url === current ? ' class="active"' : "";
          return `<li><a${active} href="${esc(url)}">${esc(item.label)}</a></li>`;
        }).join("");
      });
      document.querySelectorAll(".footer-grid .footer-links").forEach((links) => {
        const heading = links.closest("div")?.querySelector("h3")?.textContent.toLowerCase() || "";
        if (!heading.includes("navigation")) return;
        links.innerHTML = navigation.map((item) => `<a href="${esc(item.url || "#")}">${esc(item.label)}</a>`).join("");
      });
    }
  }

  function renderHome(content) {
    const home = content.home || {};
    const hero = home.hero || {};
    text(".hero--home .eyebrow", hero.eyebrow);
    text(".hero--home h1 span", hero.title);
    const h1 = document.querySelector(".hero--home h1");
    if (h1 && hero.subtitle) {
      const span = h1.querySelector("span");
      h1.innerHTML = "";
      if (span) h1.appendChild(span);
      h1.append(document.createTextNode(hero.subtitle));
    }
    text(".hero--home p", hero.text);
    if (hero.image) document.querySelector(".hero--home")?.style.setProperty("background-image", `url('${hero.image}')`);

    text(".split-copy h2", home.about?.title);
    const aboutParagraphs = document.querySelectorAll(".split-copy > p");
    const aboutTexts = Array.isArray(home.about?.paragraphs) && home.about.paragraphs.length
      ? home.about.paragraphs
      : [home.about?.text].filter(Boolean);
    aboutParagraphs.forEach((paragraph, index) => {
      if (aboutTexts[index]) paragraph.textContent = aboutTexts[index];
    });
    attr(".split-media img", "src", home.about?.image);

    text(".artisan-copy h2", home.certification?.title);
    text(".artisan-copy .lead", home.certification?.subtitle);
    const artisanParagraphs = document.querySelectorAll(".artisan-copy p:not(.lead)");
    if (artisanParagraphs[0] && home.certification?.text) artisanParagraphs[0].textContent = home.certification.text;
    attr(".artisan-badge img", "src", home.certification?.image);

    text(".creation-widget-head strong", home.creationPreview?.title);
    const widgetLead = document.querySelector(".creation-widget-footer p");
    if (widgetLead && home.creationPreview?.text) widgetLead.textContent = home.creationPreview.text;
    const grid = document.querySelector(".creation-widget-grid");
    if (grid && Array.isArray(home.creationPreview?.items)) {
      grid.innerHTML = home.creationPreview.items.map((item) => `
        <a class="creation-tile" href="${esc(item.url || "savoir-faire.html#univers")}">
          <img src="${esc(item.image)}" alt="${esc(item.title)}" loading="lazy" decoding="async">
          <span>${esc(item.category)}</span>
          <strong>${esc(item.title)}</strong>
        </a>`).join("");
    }
    const googleLink = document.querySelector('.reviews-section a[href*="maps.app"], .reviews-section a[href*="google"]');
    if (googleLink && (home.googleReviews?.url || content.site?.googleReviews)) googleLink.href = home.googleReviews?.url || content.site.googleReviews;
  }

  function renderUnivers(content) {
    const grid = document.querySelector("[data-creation-grid]");
    if (!grid || !Array.isArray(content.creationCategories)) return;
    grid.innerHTML = content.creationCategories.map((category) => {
      return (category.photos || []).map((photo, index) => {
        const src = category.folder + photo;
        return `<button class="creation-photo" type="button" data-photo-category="${esc(category.slug)}" aria-label="Voir ${esc(category.title)} ${index + 1}">
          <img data-gallery="${esc(category.slug)}" data-category="${esc(category.title)}" data-title="${esc(category.title)} ${index + 1}" data-src="${esc(src)}" src="${esc(src)}" alt="${esc(category.title)} ${index + 1}" loading="lazy" decoding="async">
        </button>`;
      }).join("");
    }).join("");

    bindUniverseFilters(grid, content.creationCategories);
    bindUniverseShowcase(grid);
  }

  function bindUniverseFilters(grid, categories) {
    const section = grid.closest("section");
    if (!section || !Array.isArray(categories) || !categories.length) return;
    section.querySelector("[data-universe-filters]")?.remove();

    const filters = document.createElement("div");
    filters.className = "universe-filters";
    filters.setAttribute("data-universe-filters", "");
    filters.setAttribute("aria-label", "Filtrer les créations Faty Style");

    const buttons = [
      { label: "Tout", slug: "all" },
      ...categories.map((category) => ({ label: category.shortTitle || category.title, slug: category.slug }))
    ];

    filters.innerHTML = buttons.map((button, index) => `
      <button class="filter-btn${index === 0 ? " is-active" : ""}" type="button" data-filter="${esc(button.slug)}" aria-pressed="${index === 0 ? "true" : "false"}">${esc(button.label)}</button>
    `).join("");

    grid.before(filters);
    filters.addEventListener("click", (event) => {
      const button = event.target.closest("[data-filter]");
      if (!button) return;
      const filter = button.dataset.filter;

      filters.querySelectorAll("[data-filter]").forEach((item) => {
        const isActive = item === button;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-pressed", String(isActive));
      });

      grid.classList.add("is-filtering");
      grid.querySelectorAll("[data-photo-category]").forEach((photo) => {
        const visible = filter === "all" || photo.dataset.photoCategory === filter;
        photo.toggleAttribute("hidden", !visible);
      });
      window.setTimeout(() => grid.classList.remove("is-filtering"), 180);
    });
  }

  function bindUniverseShowcase(grid) {
    const panel = document.querySelector("[data-creation-gallery]");
    const filters = panel?.querySelector("[data-universe-filters]");
    document.querySelectorAll("[data-universe-slugs]").forEach((entry) => {
      if (entry.dataset.universeBound === "true") return;
      entry.dataset.universeBound = "true";
      entry.addEventListener("click", () => {
        const slugs = entry.dataset.universeSlugs.split(",").filter(Boolean);
        grid.classList.add("is-filtering");
        grid.querySelectorAll("[data-photo-category]").forEach((photo) => {
          photo.toggleAttribute("hidden", !slugs.includes(photo.dataset.photoCategory));
        });
        filters?.querySelectorAll("[data-filter]").forEach((button) => {
          button.classList.remove("is-active");
          button.setAttribute("aria-pressed", "false");
        });
        window.setTimeout(() => grid.classList.remove("is-filtering"), 180);
        panel?.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }

  function renderContact(content) {
    const site = content.site || {};
    const contact = content.contact || {};
    text(".contact-form > p:not(.form-alert)", contact.intro);
    const access = document.querySelector('input[name="access_key"]');
    if (access && contact.web3formsAccessKey) access.value = contact.web3formsAccessKey;
    const owner = document.querySelector('input[name="Notification propriétaire"]');
    if (owner && contact.ownerNotification) owner.value = contact.ownerNotification;
    const confirmation = document.querySelector('input[name="Confirmation"]');
    if (confirmation && contact.confirmationMessage) confirmation.value = contact.confirmationMessage;
    const select = document.getElementById("requestType");
    if (select && Array.isArray(contact.requestTypes)) {
      select.innerHTML = contact.requestTypes.map((item) => `<option>${esc(item.value || item)}</option>`).join("");
    }
    document.querySelectorAll(".contact-line").forEach((line) => {
      const small = line.querySelector("small")?.textContent.toLowerCase() || "";
      const strong = line.querySelector("strong");
      if (small.includes("téléphone")) {
        line.href = "tel:+" + (site.whatsapp || "33768655643");
        if (strong) strong.textContent = site.phone || strong.textContent;
      }
      if (small.includes("email")) {
        line.href = "mailto:" + (site.email || "fatystyle@hotmail.fr");
        if (strong) strong.textContent = site.email || strong.textContent;
      }
      if (small.includes("adresse") && strong) strong.textContent = site.address || strong.textContent;
    });
  }

  function applyContent(content) {
    updateSeo(content);
    updateCommon(content);
    if (page === "index.html" || page === "") renderHome(content);
    if (page === "savoir-faire.html") renderUnivers(content);
    if (page === "contact.html") renderContact(content);
    window.dispatchEvent(new CustomEvent("fatystyle:content-ready", { detail: content }));
  }

  const previewContent = (() => {
    if (!new URLSearchParams(window.location.search).has("preview")) return null;
    try {
      return JSON.parse(localStorage.getItem("fatystyle-admin-content") || "null");
    } catch (error) {
      return null;
    }
  })();

  if (previewContent) {
    applyContent(previewContent);
    return;
  }

  fetch("data/content.json?" + cacheBust)
    .then((response) => response.ok ? response.json() : Promise.reject(new Error("content.json introuvable")))
    .then(applyContent)
    .catch(() => {
      window.dispatchEvent(new CustomEvent("fatystyle:content-ready"));
    });
})();
