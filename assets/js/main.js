
document.querySelectorAll('.nav-toggle').forEach(button=>button.addEventListener('click',()=>{const nav=document.getElementById('main-nav');const open=nav.classList.toggle('open');button.setAttribute('aria-expanded',String(open));}));
document.querySelectorAll('.nav-links a').forEach(a=>a.addEventListener('click',()=>{document.getElementById('main-nav')?.classList.remove('open');document.querySelector('.nav-toggle')?.setAttribute('aria-expanded','false');}));
document.querySelectorAll('[data-wa]').forEach(link=>{const text=`Bonjour Faty Style, je souhaite un devis pour : ${link.dataset.wa}.`;link.href=`https://wa.me/33768655643?text=${encodeURIComponent(text)}`;});
