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
    const meta = document.querySelector(`meta[property="${property}"]`);
    if (meta) meta.setAttribute("content", value);
  }

  function updateSeo(content) {
    const seoByPage = (content.pages && content.pages[page] && content.pages[page].seo) || {};
    const titleValue = seoByPage.title || content.seo?.title || content.site?.metaTitle;
    const description = seoByPage.description || content.seo?.description || content.site?.metaDescription;
    if (titleValue) document.title = titleValue;
    setMeta("description", description);
    if (Array.isArray(content.seo?.keywords)) setMeta("keywords", content.seo.keywords.join(", "));
    setOg("og:title", titleValue);
    setOg("og:description", description);
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
    text(".split-copy p", home.about?.text);
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
          <img src="${esc(item.image)}" alt="${esc(item.title)}" loading="lazy">
          <span>${esc(item.category)}</span>
          <strong>${esc(item.title)}</strong>
        </a>`).join("");
    }
    const googleLink = document.querySelector('.reviews-section a[href*="maps.app"], .reviews-section a[href*="google"]');
    if (googleLink && (home.googleReviews?.url || content.site?.googleReviews)) googleLink.href = home.googleReviews?.url || content.site.googleReviews;
  }

  function renderServices(content) {
    const list = document.querySelector(".service-list");
    if (!list || !Array.isArray(content.services)) return;
    list.innerHTML = content.services.map((service) => `
      <article class="card">
        <img src="${esc(service.image)}" alt="${esc(service.title)}" loading="lazy">
        <div class="card-body">
          <h3>${esc(service.title)}</h3>
          <p>${esc(service.description)}</p>
          <a class="text-link" href="contact.html?type=${encodeURIComponent(service.title)}">Demander un devis</a>
        </div>
      </article>`).join("");
  }

  function categoryType(category) {
    const titleValue = category.quoteType || category.title || "";
    const map = {
      "Robes de mariage": "Robe de mariage",
      "Robes de soirée": "Robe de soirée",
      "Retouches / transformations": "Retouche",
      "L'Atelier des Petits": "Enfant",
      "Initiation couture": "Initiation couture"
    };
    return map[titleValue] || titleValue;
  }

  function renderUnivers(content) {
    const list = document.querySelector(".universe-list");
    if (!list || !Array.isArray(content.creationCategories)) return;
    list.innerHTML = content.creationCategories.map((category) => {
      const photos = category.photos || [];
      const preview = photos.slice(0, 4).map((photo, index) => {
        const src = category.folder + photo;
        return `<button type="button"><img data-gallery="${esc(category.slug)}" data-category="${esc(category.title)}" data-title="${esc(category.title)} ${index + 1}" data-src="${esc(src)}" src="${esc(src)}" alt="${esc(category.title)} ${index + 1}" loading="lazy"></button>`;
      }).join("");
      return `<article class="universe-card">
        <div class="universe-cover"><img src="${esc(category.folder + category.cover)}" alt="${esc(category.title)}" loading="lazy"></div>
        <div class="universe-content">
          <h3>${esc(category.title)}</h3>
          <p>${esc(category.description)}</p>
          <div class="mini-gallery">${preview}</div>
          <div class="actions" style="justify-content:flex-start">
            <button class="btn" type="button" data-open-gallery="${esc(category.slug)}">Voir les créations</button>
            <a class="btn btn-light" href="contact.html?type=${encodeURIComponent(categoryType(category))}">Demander un devis</a>
          </div>
        </div>
      </article>`;
    }).join("");

    const hidden = document.querySelector(".sr-only");
    if (hidden) {
      hidden.innerHTML = content.creationCategories.map((category) => {
        return (category.photos || []).slice(4).map((photo, index) => {
          const src = category.folder + photo;
          return `<span data-gallery="${esc(category.slug)}" data-category="${esc(category.title)}" data-title="${esc(category.title)} ${index + 5}" data-src="${esc(src)}"></span>`;
        }).join("");
      }).join("");
    }
  }

  function renderSavoir(content) {
    const grid = document.querySelector(".method-grid");
    if (!grid || !Array.isArray(content.savoirFaire)) return;
    grid.innerHTML = content.savoirFaire.map((step, index) => `
      <article class="method-card">
        <strong>${String(index + 1).padStart(2, "0")}</strong>
        <h3>${esc(step.title)}</h3>
        <p>${esc(step.text)}</p>
      </article>`).join("");
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
    if (page === "presentation.html") renderServices(content);
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
