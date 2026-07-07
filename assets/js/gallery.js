(() => {
  const lightboxItems = Array.from(document.querySelectorAll('[data-lightbox]'));
  if (lightboxItems.length) {
    const box = document.createElement('div');
    box.className = 'lightbox';
    box.setAttribute('aria-hidden', 'true');
    box.innerHTML = `
      <button class="lightbox-close" type="button" aria-label="Fermer">×</button>
      <button class="lightbox-nav lightbox-prev" type="button" aria-label="Photo précédente">‹</button>
      <figure>
        <img alt="">
        <figcaption>
          <div>
            <div class="lightbox-title"></div>
            <div class="lightbox-meta"></div>
          </div>
          <a class="btn small primary lightbox-quote" target="_blank" rel="noopener noreferrer">Demander un devis</a>
        </figcaption>
      </figure>
      <button class="lightbox-nav lightbox-next" type="button" aria-label="Photo suivante">›</button>
    `;
    document.body.appendChild(box);

    const image = box.querySelector('img');
    const title = box.querySelector('.lightbox-title');
    const meta = box.querySelector('.lightbox-meta');
    const quote = box.querySelector('.lightbox-quote');
    const close = box.querySelector('.lightbox-close');
    const prev = box.querySelector('.lightbox-prev');
    const next = box.querySelector('.lightbox-next');
    const galleries = {};
    let currentGallery = '';
    let currentIndex = 0;

    const cleanCategory = card => {
      const heading = card?.querySelector('h3');
      return heading ? heading.textContent.trim() : 'Création Faty Style';
    };

    lightboxItems.forEach(item => {
      const card = item.closest('.universe-card');
      const gallery = item.dataset.gallery || card?.id || 'creations';
      item.dataset.gallery = gallery;
      item.dataset.category = item.dataset.category || cleanCategory(card);
      if (!galleries[gallery]) galleries[gallery] = [];
      galleries[gallery].push(item);
    });

    Object.entries(galleries).forEach(([gallery, items]) => {
      const card = items[0]?.closest('.universe-card');
      const info = card?.querySelector(':scope > div:first-child');
      if (!card || !info || !items.length) return;
      items[0].classList.add('category-cover');

      if (!info.querySelector('.category-count')) {
        const count = document.createElement('p');
        count.className = 'category-count';
        count.textContent = `${items.length} photo${items.length > 1 ? 's' : ''}`;
        const firstButton = info.querySelector('a[data-wa]');
        info.insertBefore(count, firstButton || null);
      }

      if (!info.querySelector('.gallery-action')) {
        const action = document.createElement('button');
        action.className = 'btn small outline gallery-action';
        action.type = 'button';
        action.dataset.openGallery = gallery;
        action.textContent = 'Voir les créations';
        const quoteButton = info.querySelector('a[data-wa]');
        info.insertBefore(action, quoteButton || null);
      }
    });

    const update = () => {
      const items = galleries[currentGallery] || [];
      const item = items[currentIndex];
      if (!item) return;
      const itemTitle = item.dataset.title || item.querySelector('img')?.alt || 'Création Faty Style';
      const category = item.dataset.category || 'Création Faty Style';
      image.src = item.dataset.lightbox;
      image.alt = itemTitle;
      title.textContent = itemTitle;
      meta.textContent = `${category} · ${currentIndex + 1} / ${items.length}`;
      quote.href = `https://wa.me/33768655643?text=${encodeURIComponent(`Bonjour Faty Style, je souhaite un devis pour cette création : ${itemTitle} / catégorie : ${category}. Pouvez-vous me donner plus d'informations ?`)}`;
      const hideNav = items.length < 2;
      prev.style.display = hideNav ? 'none' : '';
      next.style.display = hideNav ? 'none' : '';
    };

    const open = item => {
      currentGallery = item.dataset.gallery || 'creations';
      currentIndex = galleries[currentGallery].indexOf(item);
      if (currentIndex < 0) currentIndex = 0;
      update();
      box.classList.add('open');
      box.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      close.focus();
    };

    const closeBox = () => {
      box.classList.remove('open');
      box.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    const move = direction => {
      const items = galleries[currentGallery] || [];
      if (items.length < 2) return;
      currentIndex = (currentIndex + direction + items.length) % items.length;
      update();
    };

    lightboxItems.forEach(item => item.addEventListener('click', () => open(item)));
    document.querySelectorAll('[data-open-gallery]').forEach(button => {
      button.addEventListener('click', () => {
        const items = galleries[button.dataset.openGallery] || [];
        if (items[0]) open(items[0]);
      });
    });
    close.addEventListener('click', closeBox);
    prev.addEventListener('click', event => { event.stopPropagation(); move(-1); });
    next.addEventListener('click', event => { event.stopPropagation(); move(1); });
    box.addEventListener('click', event => { if (event.target === box) closeBox(); });
    let touchStartX = 0;
    box.addEventListener('touchstart', event => {
      touchStartX = event.changedTouches[0]?.clientX || 0;
    }, {passive: true});
    box.addEventListener('touchend', event => {
      const touchEndX = event.changedTouches[0]?.clientX || 0;
      const delta = touchEndX - touchStartX;
      if (Math.abs(delta) > 45) move(delta > 0 ? -1 : 1);
    }, {passive: true});
    document.addEventListener('keydown', event => {
      if (!box.classList.contains('open')) return;
      if (event.key === 'Escape') closeBox();
      if (event.key === 'ArrowLeft') move(-1);
      if (event.key === 'ArrowRight') move(1);
    });
  }

  document.querySelectorAll('[data-carousel]').forEach(carousel => {
    const track = carousel.querySelector('.carousel-track');
    if (!track) return;
    carousel.querySelector('.next')?.addEventListener('click', () => track.scrollBy({left: track.clientWidth * .8, behavior: 'smooth'}));
    carousel.querySelector('.prev')?.addEventListener('click', () => track.scrollBy({left: -track.clientWidth * .8, behavior: 'smooth'}));
    let timer = setInterval(() => track.scrollBy({left: track.clientWidth * .8, behavior: 'smooth'}), 5200);
    carousel.addEventListener('mouseenter', () => clearInterval(timer));
    carousel.addEventListener('mouseleave', () => {
      clearInterval(timer);
      timer = setInterval(() => track.scrollBy({left: track.clientWidth * .8, behavior: 'smooth'}), 5200);
    });
  });
})();
