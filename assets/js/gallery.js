(function () {
function initGallery() {
  const items = Array.from(document.querySelectorAll("[data-gallery]"));
  if (!items.length) return;

  const galleries = items.reduce((acc, item) => {
    const name = item.dataset.gallery;
    if (!acc[name]) acc[name] = [];
    acc[name].push({
      src: item.dataset.src || item.currentSrc || item.src,
      title: item.dataset.title || item.getAttribute("alt") || "Création Faty Style",
      category: item.dataset.category || name,
    });
    item.dataset.index = String(acc[name].length - 1);
    return acc;
  }, {});

  const lightbox = document.querySelector("[data-lightbox]");
  if (!lightbox) return;

  const image = lightbox.querySelector("[data-lightbox-image]");
  const title = lightbox.querySelector("[data-lightbox-title]");
  const category = lightbox.querySelector("[data-lightbox-category]");
  const counter = lightbox.querySelector("[data-lightbox-counter]");
  const quote = lightbox.querySelector("[data-lightbox-quote]");
  const closeButton = lightbox.querySelector("[data-lightbox-close]");
  const prevButton = lightbox.querySelector("[data-lightbox-prev]");
  const nextButton = lightbox.querySelector("[data-lightbox-next]");

  let currentGallery = "";
  let currentIndex = 0;
  let touchStartX = 0;

  function currentItems() {
    return galleries[currentGallery] || [];
  }

  function render() {
    const list = currentItems();
    const item = list[currentIndex];
    if (!item) return;
    image.src = item.src;
    image.alt = item.title;
    title.textContent = item.title;
    category.textContent = item.category;
    counter.textContent = `${currentIndex + 1} / ${list.length}`;
    const typeByCategory = {
      "Robes de mariage": "Robe de mariage",
      "Robes de soirée": "Robe de soirée",
      "Ensembles & vestes": "Création sur mesure",
      "Nœuds papillon": "Accessoire",
      "Accessoires": "Accessoire",
      "Tabliers": "Accessoire",
      "Sacs porte-plat": "Accessoire",
      "Écharpes": "Accessoire",
      "L'Atelier des Petits": "Enfant",
      "Retouches / transformations": "Retouche",
      "Initiation couture": "Initiation couture"
    };
    quote.href = "contact.html?type=" + encodeURIComponent(typeByCategory[item.category] || item.category);
  }

  function open(gallery, index) {
    currentGallery = gallery;
    currentIndex = Number(index) || 0;
    render();
    lightbox.classList.add("is-open");
    lightbox.setAttribute("aria-hidden", "false");
    document.body.classList.add("is-locked");
  }

  function close() {
    lightbox.classList.remove("is-open");
    lightbox.setAttribute("aria-hidden", "true");
    document.body.classList.remove("is-locked");
  }

  function move(direction) {
    const list = currentItems();
    if (!list.length) return;
    currentIndex = (currentIndex + direction + list.length) % list.length;
    render();
  }

  items.forEach((item) => {
    if (item.dataset.lightboxBound === "true") return;
    item.dataset.lightboxBound = "true";
    item.addEventListener("click", () => open(item.dataset.gallery, item.dataset.index));
  });

  document.querySelectorAll("[data-open-gallery]").forEach((button) => {
    if (button.dataset.lightboxBound === "true") return;
    button.dataset.lightboxBound = "true";
    button.addEventListener("click", () => open(button.dataset.openGallery, 0));
  });

  closeButton?.addEventListener("click", close);
  prevButton?.addEventListener("click", () => move(-1));
  nextButton?.addEventListener("click", () => move(1));
  lightbox.addEventListener("click", (event) => {
    if (event.target === lightbox) close();
  });

  lightbox.addEventListener("touchstart", (event) => {
    touchStartX = event.changedTouches[0].clientX;
  }, { passive: true });

  lightbox.addEventListener("touchend", (event) => {
    const diff = event.changedTouches[0].clientX - touchStartX;
    if (Math.abs(diff) > 50) move(diff > 0 ? -1 : 1);
  }, { passive: true });

  document.addEventListener("keydown", (event) => {
    if (!lightbox.classList.contains("is-open")) return;
    if (event.key === "Escape") close();
    if (event.key === "ArrowLeft") move(-1);
    if (event.key === "ArrowRight") move(1);
  });
}

initGallery();
window.addEventListener("fatystyle:content-ready", initGallery);
})();
