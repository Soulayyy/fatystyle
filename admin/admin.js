(function () {
  const LOGIN = { user: "admin", pass: "pwd123" };
  const API_TOKEN = "faty-style-admin-2026";
  const state = {
    content: null,
    active: "overview",
    dirty: false,
  };

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const login = $("[data-login]");
  const dashboard = $("[data-dashboard]");
  const panel = $("[data-panel]");
  const title = $("[data-panel-title]");
  const status = $("[data-status]");

  const clone = (value) => JSON.parse(JSON.stringify(value));
  const get = (path) => path.split(".").reduce((acc, key) => acc && acc[key], state.content);
  const set = (path, value) => {
    const keys = path.split(".");
    const last = keys.pop();
    const target = keys.reduce((acc, key) => {
      if (!acc[key] || typeof acc[key] !== "object") acc[key] = {};
      return acc[key];
    }, state.content);
    target[last] = value;
    markDirty();
  };

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;");
  }

  function markDirty() {
    state.dirty = true;
    setStatus("Modifications non sauvegardées.", "");
  }

  function setStatus(message, type = "") {
    status.textContent = message || "";
    status.className = "status" + (type ? " " + type : "");
  }

  function field(label, path, options = {}) {
    const value = get(path) ?? "";
    const noBind = options.noBind ? ' data-no-bind="true"' : "";
    const input = options.type === "textarea"
      ? `<textarea data-field="${path}"${noBind} ${options.required ? "required" : ""}>${escapeHtml(value)}</textarea>`
      : `<input data-field="${path}"${noBind} type="${options.type || "text"}" value="${escapeHtml(value)}" ${options.required ? "required" : ""}>`;
    return `<label class="${options.full ? "full" : ""}">${label}${input}</label>`;
  }

  function bindFields(root = panel) {
    $$("[data-field]", root).forEach((input) => {
      if (input.dataset.noBind === "true") return;
      input.addEventListener("input", () => set(input.dataset.field, input.value));
    });
  }

  function wireArray(container, array, renderItem, onAdd) {
    container.innerHTML = array.map(renderItem).join("");
    $$("[data-array-field]", container).forEach((input) => {
      input.addEventListener("input", () => {
        const item = array[Number(input.dataset.index)];
        const key = input.dataset.key;
        if (typeof item !== "object" || item === null) {
          array[Number(input.dataset.index)] = input.value;
          markDirty();
          return;
        }
        if (input.dataset.list === "true") {
          item[key] = input.value.split("\n").map((line) => line.trim()).filter(Boolean);
        } else {
          item[key] = input.value;
        }
        markDirty();
      });
    });
    $$("[data-remove]", container).forEach((button) => {
      button.addEventListener("click", () => {
        array.splice(Number(button.dataset.remove), 1);
        markDirty();
        render();
      });
    });
    $$("[data-up]", container).forEach((button) => {
      button.addEventListener("click", () => {
        const index = Number(button.dataset.up);
        if (index <= 0) return;
        [array[index - 1], array[index]] = [array[index], array[index - 1]];
        markDirty();
        render();
      });
    });
    $$("[data-down]", container).forEach((button) => {
      button.addEventListener("click", () => {
        const index = Number(button.dataset.down);
        if (index >= array.length - 1) return;
        [array[index + 1], array[index]] = [array[index], array[index + 1]];
        markDirty();
        render();
      });
    });
    const add = $("[data-add]", panel);
    if (add) {
      add.addEventListener("click", () => {
        array.push(onAdd());
        markDirty();
        render();
      });
    }
  }

  function cardActions(index) {
    return `<div class="row-actions">
      <button class="small-btn" type="button" data-up="${index}">Monter</button>
      <button class="small-btn" type="button" data-down="${index}">Descendre</button>
      <button class="small-btn danger" type="button" data-remove="${index}">Supprimer</button>
    </div>`;
  }

  function renderOverview() {
    title.textContent = "Tableau de bord";
    panel.innerHTML = `
      <div class="grid">
        <article class="card"><h3>Contenus administrables</h3><p class="help">Général, SEO, accueil, prestations, catégories de créations, galeries, savoir-faire, contact et réseaux sociaux.</p></article>
        <article class="card"><h3>Sauvegarde</h3><p class="help">Le bouton Enregistrer tente une sauvegarde réelle dans <code>data/content.json</code> via PHP. Si le serveur ne le permet pas, utilisez Export / Import.</p></article>
        <article class="card"><h3>Images</h3><p class="help">Les images existantes restent dans leurs dossiers métiers. Les nouveaux imports sont stockés dans <code>assets/images/admin/</code>.</p></article>
        <article class="card"><h3>Non administrable volontairement</h3><p class="help">La structure fine du design, les grilles CSS et le comportement responsive restent dans le code pour préserver un rendu premium stable.</p></article>
      </div>`;
  }

  function renderGeneral() {
    title.textContent = "Général & SEO";
    panel.innerHTML = `
      <div class="grid">
        ${field("Nom du site", "site.name")}
        ${field("Baseline", "site.baseline")}
        ${field("Téléphone", "site.phone")}
        ${field("WhatsApp international", "site.whatsapp")}
        ${field("Email", "site.email")}
        ${field("Horaires", "site.hours")}
        ${field("Adresse", "site.address", { full: true })}
        ${field("Facebook", "site.facebook", { full: true })}
        ${field("Instagram", "site.instagram", { full: true })}
        ${field("Lien avis Google", "site.googleReviews", { full: true })}
        ${field("Title SEO global", "seo.title", { full: true })}
        ${field("Description SEO", "seo.description", { type: "textarea", full: true })}
        ${field("Mots-clés SEO", "seo.keywords", { type: "textarea", full: true, noBind: true })}
        ${field("Texte footer", "footer.text", { type: "textarea", full: true })}
        ${field("Copyright footer", "footer.copyright", { full: true })}
      </div>
      <p class="help">Pour les mots-clés, utilisez une ligne ou une virgule par expression.</p>
      <h2>SEO par page</h2>
      <div data-page-seo-list></div>
      <h2>Navigation</h2>
      <p class="help">Gardez une navigation courte. Le design public est prévu pour ces liens principaux.</p>
      <div data-navigation-list></div>
      <button class="btn" type="button" data-nav-add>Ajouter un lien</button>`;
    const keywords = $('[data-field="seo.keywords"]');
    if (Array.isArray(get("seo.keywords")) && keywords) keywords.value = get("seo.keywords").join("\n");
    keywords?.addEventListener("input", () => {
      set("seo.keywords", keywords.value.split(/\n|,/).map((item) => item.trim()).filter(Boolean));
    });
    bindFields();
    bindPageSeo();
    bindNavigation();
  }

  function bindPageSeo() {
    const pages = state.content.pages || (state.content.pages = {});
    const list = $("[data-page-seo-list]");
    if (!list) return;
    list.innerHTML = Object.entries(pages).map(([page, data]) => `
      <article class="card">
        <div class="card-head"><h3>${escapeHtml(page)}</h3></div>
        <label>Title SEO<input data-page-seo="${escapeHtml(page)}" data-page-key="title" value="${escapeHtml(data?.seo?.title || "")}"></label>
        <label>Description SEO<textarea data-page-seo="${escapeHtml(page)}" data-page-key="description">${escapeHtml(data?.seo?.description || "")}</textarea></label>
      </article>`).join("");
    $$("[data-page-seo]", list).forEach((input) => {
      input.addEventListener("input", () => {
        const page = input.dataset.pageSeo;
        const key = input.dataset.pageKey;
        if (!pages[page]) pages[page] = { seo: {} };
        if (!pages[page].seo) pages[page].seo = {};
        pages[page].seo[key] = input.value;
        markDirty();
      });
    });
  }

  function bindNavigation() {
    const nav = state.content.navigation || (state.content.navigation = []);
    const list = $("[data-navigation-list]");
    if (!list) return;
    const draw = () => {
      list.innerHTML = nav.map((item, index) => `
        <article class="card">
          <div class="grid">
            <label>Libellé<input data-nav-field data-index="${index}" data-key="label" value="${escapeHtml(item.label)}"></label>
            <label>URL<input data-nav-field data-index="${index}" data-key="url" value="${escapeHtml(item.url)}"></label>
          </div>
          ${cardActions(index)}
        </article>`).join("");
      $$("[data-nav-field]", list).forEach((input) => {
        input.addEventListener("input", () => {
          nav[Number(input.dataset.index)][input.dataset.key] = input.value;
          markDirty();
        });
      });
      $$("[data-remove]", list).forEach((button) => {
        button.addEventListener("click", () => {
          nav.splice(Number(button.dataset.remove), 1);
          markDirty();
          draw();
        });
      });
      $$("[data-up]", list).forEach((button) => {
        button.addEventListener("click", () => {
          const index = Number(button.dataset.up);
          if (index <= 0) return;
          [nav[index - 1], nav[index]] = [nav[index], nav[index - 1]];
          markDirty();
          draw();
        });
      });
      $$("[data-down]", list).forEach((button) => {
        button.addEventListener("click", () => {
          const index = Number(button.dataset.down);
          if (index >= nav.length - 1) return;
          [nav[index + 1], nav[index]] = [nav[index], nav[index + 1]];
          markDirty();
          draw();
        });
      });
    };
    draw();
    $("[data-nav-add]")?.addEventListener("click", () => {
      nav.push({ label: "Nouveau lien", url: "index.html" });
      markDirty();
      draw();
    });
  }

  function renderHome() {
    title.textContent = "Accueil";
    panel.innerHTML = `
      <div class="grid">
        ${field("Hero - accroche", "home.hero.eyebrow")}
        ${field("Hero - titre script", "home.hero.title")}
        ${field("Hero - titre principal", "home.hero.subtitle", { full: true })}
        ${field("Hero - texte", "home.hero.text", { type: "textarea", full: true })}
        ${field("Hero - image", "home.hero.image", { full: true })}
        ${field("À propos - titre", "home.about.title", { full: true })}
        ${field("À propos - texte", "home.about.text", { type: "textarea", full: true })}
        ${field("À propos - image", "home.about.image", { full: true })}
        ${field("Label - titre", "home.certification.title")}
        ${field("Label - sous-titre", "home.certification.subtitle")}
        ${field("Label - texte", "home.certification.text", { type: "textarea", full: true })}
        ${field("Label - image", "home.certification.image", { full: true })}
        ${field("Widget créations - titre", "home.creationPreview.title", { full: true })}
        ${field("Widget créations - texte", "home.creationPreview.text", { type: "textarea", full: true })}
        ${field("Avis Google - titre", "home.googleReviews.title")}
        ${field("Avis Google - lien", "home.googleReviews.url")}
        ${field("Avis Google - note", "home.googleReviews.note", { type: "textarea", full: true })}
      </div>
      <h2>Items du widget créations</h2>
      <div data-home-items></div>
      <button class="btn" type="button" data-add>Ajouter un item</button>`;
    bindFields();
    wireArray($("[data-home-items]"), get("home.creationPreview.items") || (state.content.home.creationPreview.items = []), (item, index) => `
      <article class="card">
        <div class="card-head"><h3>${escapeHtml(item.title || "Item")}</h3></div>
        <label>Titre<input data-array-field data-index="${index}" data-key="title" value="${escapeHtml(item.title)}"></label>
        <label>Catégorie<input data-array-field data-index="${index}" data-key="category" value="${escapeHtml(item.category)}"></label>
        <label>Image<input data-array-field data-index="${index}" data-key="image" value="${escapeHtml(item.image)}"></label>
        <label>Lien<input data-array-field data-index="${index}" data-key="url" value="${escapeHtml(item.url)}"></label>
        ${cardActions(index)}
      </article>`, () => ({ title: "Nouvelle création", category: "Univers", image: "assets/images/creations/robes-mariage/cover.jpg", url: "presentation.html#univers" }));
  }

  function renderServices() {
    title.textContent = "Prestations";
    panel.innerHTML = `<div data-list></div><button class="btn" type="button" data-add>Ajouter une prestation</button>`;
    wireArray($("[data-list]"), state.content.services || (state.content.services = []), (item, index) => `
      <article class="card">
        <div class="card-head"><h3>${escapeHtml(item.title || "Prestation")}</h3></div>
        <div class="grid">
          <label>Titre<input data-array-field data-index="${index}" data-key="title" value="${escapeHtml(item.title)}"></label>
          <label>Slug<input data-array-field data-index="${index}" data-key="slug" value="${escapeHtml(item.slug)}"></label>
          <label class="full">Image<input data-array-field data-index="${index}" data-key="image" value="${escapeHtml(item.image)}"></label>
          <label class="full">Description<textarea data-array-field data-index="${index}" data-key="description">${escapeHtml(item.description)}</textarea></label>
        </div>
        ${cardActions(index)}
      </article>`, () => ({ title: "Nouvelle prestation", slug: "nouvelle-prestation", image: "assets/images/prestations/sur-mesure/1.jpg", description: "" }));
  }

  function renderCategories() {
    title.textContent = "Créations";
    panel.innerHTML = `<p class="help">Chaque catégorie correspond à un univers de création. Les photos sont listées une par ligne, relatives au dossier indiqué.</p><div data-list></div><button class="btn" type="button" data-add>Ajouter une catégorie</button>`;
    wireArray($("[data-list]"), state.content.creationCategories || (state.content.creationCategories = []), (item, index) => `
      <article class="card">
        <div class="card-head"><h3>${escapeHtml(item.title || "Catégorie")}</h3></div>
        <div class="grid">
          <label>Titre<input data-array-field data-index="${index}" data-key="title" value="${escapeHtml(item.title)}"></label>
          <label>Slug<input data-array-field data-index="${index}" data-key="slug" value="${escapeHtml(item.slug)}"></label>
          <label class="full">Dossier images<input data-array-field data-index="${index}" data-key="folder" value="${escapeHtml(item.folder)}"></label>
          <label>Cover<input data-array-field data-index="${index}" data-key="cover" value="${escapeHtml(item.cover)}"></label>
          <label>Texte bouton devis<input data-array-field data-index="${index}" data-key="quoteType" value="${escapeHtml(item.quoteType || item.title || "")}"></label>
          <label class="full">Description<textarea data-array-field data-index="${index}" data-key="description">${escapeHtml(item.description)}</textarea></label>
          <label class="full">Photos<textarea data-array-field data-list="true" data-index="${index}" data-key="photos">${escapeHtml((item.photos || []).join("\n"))}</textarea></label>
        </div>
        ${cardActions(index)}
      </article>`, () => ({ title: "Nouvelle catégorie", slug: "nouvelle-categorie", folder: "assets/images/creations/nouvelle-categorie/", cover: "cover.jpg", description: "", photos: [] }));
  }

  function renderSavoir() {
    title.textContent = "Savoir-faire";
    panel.innerHTML = `<div data-list></div><button class="btn" type="button" data-add>Ajouter une étape</button>`;
    wireArray($("[data-list]"), state.content.savoirFaire || (state.content.savoirFaire = []), (item, index) => `
      <article class="card">
        <div class="card-head"><h3>${String(index + 1).padStart(2, "0")} - ${escapeHtml(item.title || "Étape")}</h3></div>
        <label>Titre<input data-array-field data-index="${index}" data-key="title" value="${escapeHtml(item.title)}"></label>
        <label>Texte<textarea data-array-field data-index="${index}" data-key="text">${escapeHtml(item.text)}</textarea></label>
        ${cardActions(index)}
      </article>`, () => ({ title: "Nouvelle étape", text: "" }));
  }

  function renderContact() {
    title.textContent = "Contact";
    panel.innerHTML = `
      <div class="grid">
        ${field("Texte intro", "contact.intro", { type: "textarea", full: true })}
        ${field("Access key Web3Forms", "contact.web3formsAccessKey")}
        ${field("Email destinataire affiché", "site.email")}
        ${field("Message confirmation visiteur", "contact.confirmationMessage", { type: "textarea", full: true })}
        ${field("Message propriétaire", "contact.ownerNotification", { type: "textarea", full: true })}
      </div>
      <h2>Types de demande</h2>
      <div data-list></div>
      <button class="btn" type="button" data-add>Ajouter un type</button>`;
    if (!state.content.contact.web3formsAccessKey) state.content.contact.web3formsAccessKey = "24828741-d117-4016-811f-d9d24eca2ecb";
    if (!state.content.contact.confirmationMessage) state.content.contact.confirmationMessage = "Bonjour, nous avons bien reçu votre message envoyé à Faty Style. L'atelier revient vers vous très bientôt. Pour une demande urgente, vous pouvez aussi contacter directement Faty Style au 07 68 65 56 43. Merci pour votre confiance.";
    if (!state.content.contact.ownerNotification) state.content.contact.ownerNotification = "Un visiteur vient d'envoyer une demande depuis le site Faty Style. Merci de le recontacter rapidement.";
    bindFields();
    wireArray($("[data-list]"), state.content.contact.requestTypes || (state.content.contact.requestTypes = []), (item, index) => `
      <article class="card">
        <label>Type de demande<input data-array-field data-index="${index}" data-key="value" value="${escapeHtml(item.value || item)}"></label>
        ${cardActions(index)}
      </article>`, () => "Nouvelle demande");
  }

  function renderMedia() {
    title.textContent = "Images";
    const allImages = collectImages();
    panel.innerHTML = `
      <div class="card">
        <h3>Importer une image</h3>
        <p class="help">Formats acceptés : jpg, png, webp, avif. Taille max : 5 Mo. Dimensions conseillées : hero 1920x1080, galerie 900x1200 ou 1200x1200.</p>
        <form data-upload-form>
          <label>Image<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif" required></label>
          <button class="btn" type="submit">Importer</button>
        </form>
        <p data-upload-result></p>
      </div>
      <h2>Images référencées dans le contenu</h2>
      <div class="media-preview">${allImages.map((src) => `<div><img src="../${escapeHtml(src)}" alt=""><small>${escapeHtml(src)}</small></div>`).join("")}</div>`;
    const form = $("[data-upload-form]");
    form?.addEventListener("submit", uploadImage);
  }

  function renderJson() {
    title.textContent = "Export / Import";
    panel.innerHTML = `
      <p class="help">Utilisez cette zone si la sauvegarde PHP n'est pas disponible chez un hébergeur. Exportez le JSON, remplacez <code>data/content.json</code>, puis renvoyez le site.</p>
      <textarea class="json-box" data-json>${escapeHtml(JSON.stringify(state.content, null, 2))}</textarea>
      <div class="row-actions">
        <button class="btn" type="button" data-apply-json>Appliquer le JSON</button>
        <button class="btn btn-light" type="button" data-export-json>Exporter content.json</button>
        <label class="btn btn-light">Importer content.json<input type="file" data-import-json accept="application/json,.json" hidden></label>
        <button class="btn btn-light" type="button" data-reset-server>Réinitialiser depuis le serveur</button>
      </div>`;
    $("[data-apply-json]")?.addEventListener("click", () => {
      try {
        state.content = JSON.parse($("[data-json]").value);
        markDirty();
        setStatus("JSON appliqué localement. Pensez à enregistrer.", "ok");
      } catch (error) {
        setStatus("JSON invalide : " + error.message, "error");
      }
    });
    $("[data-export-json]")?.addEventListener("click", exportJson);
    $("[data-import-json]")?.addEventListener("change", importJson);
    $("[data-reset-server]")?.addEventListener("click", loadServerContent);
  }

  function collectImages() {
    const images = new Set();
    const visit = (value) => {
      if (Array.isArray(value)) return value.forEach(visit);
      if (!value || typeof value !== "object") return;
      Object.entries(value).forEach(([key, item]) => {
        if (typeof item === "string" && (key === "image" || key === "icon" || key === "cover")) {
          if (item.startsWith("assets/")) images.add(item);
        }
        visit(item);
      });
      if (value.folder && value.cover) images.add(value.folder + value.cover);
      if (value.folder && Array.isArray(value.photos)) value.photos.forEach((photo) => images.add(value.folder + photo));
    };
    visit(state.content);
    return Array.from(images);
  }

  async function uploadImage(event) {
    event.preventDefault();
    const result = $("[data-upload-result]");
    const form = event.currentTarget;
    const file = form.image.files[0];
    if (!file) return;
    const data = new FormData();
    data.append("image", file);
    try {
      const response = await fetch("upload-image.php", {
        method: "POST",
        headers: { "X-Faty-Admin": API_TOKEN },
        body: data,
      });
      const json = await response.json();
      result.textContent = json.ok ? `Image importée : ${json.path}` : json.message;
      result.style.color = json.ok ? "var(--ok)" : "var(--danger)";
    } catch (error) {
      result.textContent = "Upload indisponible sur ce serveur. Ajoutez l'image manuellement puis renseignez son chemin.";
      result.style.color = "var(--danger)";
    }
  }

  function render() {
    const map = {
      overview: renderOverview,
      general: renderGeneral,
      home: renderHome,
      services: renderServices,
      categories: renderCategories,
      savoir: renderSavoir,
      contact: renderContact,
      media: renderMedia,
      json: renderJson,
    };
    (map[state.active] || renderOverview)();
  }

  async function loadServerContent() {
    try {
      const response = await fetch("../data/content.json?ts=" + Date.now());
      state.content = await response.json();
      localStorage.setItem("fatystyle-admin-content", JSON.stringify(state.content));
      state.dirty = false;
      setStatus("Contenu serveur chargé.", "ok");
      render();
    } catch (error) {
      const cached = localStorage.getItem("fatystyle-admin-content");
      if (cached) {
        state.content = JSON.parse(cached);
        setStatus("Serveur indisponible, contenu local chargé.", "error");
        render();
      } else {
        setStatus("Impossible de charger content.json.", "error");
      }
    }
  }

  async function saveContent() {
    if (!state.content) return;
    localStorage.setItem("fatystyle-admin-content", JSON.stringify(state.content));
    try {
      const response = await fetch("save-content.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Faty-Admin": API_TOKEN,
        },
        body: JSON.stringify(state.content),
      });
      const json = await response.json();
      if (!response.ok || !json.ok) throw new Error(json.message || "Sauvegarde refusée");
      state.dirty = false;
      setStatus("Sauvegarde réelle effectuée dans data/content.json.", "ok");
    } catch (error) {
      setStatus("Sauvegarde PHP indisponible. Contenu gardé en local, utilisez Export / Import. Détail : " + error.message, "error");
    }
  }

  function exportJson() {
    const blob = new Blob([JSON.stringify(state.content, null, 2)], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "content.json";
    link.click();
    URL.revokeObjectURL(url);
  }

  function importJson(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      try {
        state.content = JSON.parse(reader.result);
        markDirty();
        render();
        setStatus("JSON importé. Pensez à enregistrer.", "ok");
      } catch (error) {
        setStatus("Import impossible : JSON invalide.", "error");
      }
    };
    reader.readAsText(file);
  }

  function boot() {
    const authenticated = sessionStorage.getItem("fatystyle-admin-auth") === "yes";
    if (authenticated) showDashboard();

    $("[data-login-form]")?.addEventListener("submit", (event) => {
      event.preventDefault();
      const data = new FormData(event.currentTarget);
      if (data.get("username") === LOGIN.user && data.get("password") === LOGIN.pass) {
        sessionStorage.setItem("fatystyle-admin-auth", "yes");
        showDashboard();
      } else {
        $("[data-login-message]").textContent = "Identifiants incorrects.";
      }
    });

    $$("[data-tab]").forEach((button) => {
      button.addEventListener("click", () => {
        state.active = button.dataset.tab;
        $$("[data-tab]").forEach((item) => item.classList.toggle("is-active", item === button));
        render();
      });
    });

    $("[data-save]")?.addEventListener("click", saveContent);
    $("[data-logout]")?.addEventListener("click", () => {
      sessionStorage.removeItem("fatystyle-admin-auth");
      window.location.reload();
    });

    window.addEventListener("beforeunload", (event) => {
      if (!state.dirty) return;
      event.preventDefault();
      event.returnValue = "";
    });
  }

  function showDashboard() {
    login.hidden = true;
    dashboard.hidden = false;
    loadServerContent();
  }

  boot();
})();
