
(() => {
  const button = document.querySelector('.nav-toggle');
  const nav = document.getElementById('main-nav');
  if (!button || !nav) return;

  const closeNav = () => {
    nav.classList.remove('open');
    button.classList.remove('is-open');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', 'Ouvrir le menu');
  };

  button.addEventListener('click', event => {
    event.stopPropagation();
    const open = nav.classList.toggle('open');
    button.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', String(open));
    button.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
  });

  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', closeNav));
  document.addEventListener('click', event => {
    if (!nav.classList.contains('open')) return;
    if (nav.contains(event.target) || button.contains(event.target)) return;
    closeNav();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeNav();
  });
})();
document.querySelectorAll('[data-wa]').forEach(link=>{const text=`Bonjour Faty Style, je souhaite un devis pour : ${link.dataset.wa}.`;link.href=`https://wa.me/33768655643?text=${encodeURIComponent(text)}`;});
