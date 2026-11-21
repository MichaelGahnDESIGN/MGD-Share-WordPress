(() => {
  const open = (url) => window.open(url, '_blank', 'noopener,noreferrer');
  const encode = encodeURIComponent;
  const NS = 'http://www.w3.org/2000/svg';

  // Ziele erst beim Klick bauen - vorher geht nix raus
  const targets = (url, title, image) => ({
    email: `mailto:?subject=${encode(title)}&body=${encode(`${title}\n${url}`)}`,
    whatsapp: `https://wa.me/?text=${encode(`${title} ${url}`)}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encode(url)}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encode(url)}`,
    telegram: `https://t.me/share/url?url=${encode(url)}&text=${encode(title)}`,
    pinterest: `https://pinterest.com/pin/create/button/?url=${encode(url)}&description=${encode(title)}${image ? `&media=${encode(image)}` : ''}`,
    reddit: `https://www.reddit.com/submit?url=${encode(url)}&title=${encode(title)}`,
    xing: `https://www.xing.com/spi/shares/new?url=${encode(url)}`,
    bluesky: `https://bsky.app/intent/compose?text=${encode(`${title} ${url}`)}`,
    threads: `https://www.threads.net/intent/post?text=${encode(`${title} ${url}`)}`,
  });

  // Häkchen für Kopiert-Feedback
  const checkIcon = () => {
    const svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('class', 'mgd-share__icon');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2.5');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    const path = document.createElementNS(NS, 'path');
    path.setAttribute('d', 'M20 6 9 17l-5-5');
    svg.appendChild(path);
    return svg;
  };

  const flashDone = (button, text) => {
    if (button.classList.contains('is-done')) return;
    const icon = button.querySelector('.mgd-share__icon');
    const label = button.querySelector('.mgd-share__text');
    const oldText = label ? label.textContent : '';
    const check = checkIcon();
    if (icon) icon.replaceWith(check);
    if (label) label.textContent = text;
    button.classList.add('is-done');
    setTimeout(() => {
      if (icon) check.replaceWith(icon);
      if (label) label.textContent = oldText;
      button.classList.remove('is-done');
    }, 2000);
  };

  const copy = async (url, button, announce) => {
    try {
      await navigator.clipboard.writeText(url);
      flashDone(button, 'Kopiert!');
      announce('Link wurde in die Zwischenablage kopiert.');
    } catch {
      // Zwischenablage gesperrt > Link zum Abschreiben zeigen
      try { window.prompt('Kopiere diesen Link:', url); } catch { announce(url); }
    }
  };

  const bind = (root) => {
    const url = root.dataset.url;
    const title = root.dataset.title;
    const status = root.querySelector('[data-mgd-share-status]');
    if (!url || !title || !status) return;
    const announce = (message) => { status.textContent = message; };
    const preview = root.classList.contains('mgd-share--preview');
    root.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-mgd-share-action]');
      if (!button) return;
      const action = button.dataset.mgdShareAction;
      // Admin-Vorschau: nur zeigen, nix öffnen
      if (preview) { flashDone(button, action === 'copy' ? 'Kopiert!' : 'Vorschau'); return; }
      if (action === 'copy') { await copy(url, button, announce); return; }
      if (action === 'print') { window.print(); return; }
      if (action === 'native') {
        if (navigator.share) {
          try { await navigator.share({ title, url }); } catch { /* Abbrechen ist keine Fehlermeldung. */ }
        } else {
          await copy(url, button, announce);
          announce('Dein Browser unterstützt System-Teilen nicht. Der Link wurde kopiert.');
        }
        return;
      }
      const target = targets(url, title, root.dataset.image || '')[action];
      if (target) open(target);
    });
  };

  // Handy-Leiste unten: Platz am Seitenende freihalten
  const padForBar = () => {
    const bar = document.querySelector('.mgd-share--mobile-bar');
    if (!bar) return;
    // Hintergrund vom Theme übernehmen, sonst weiß
    const bg = getComputedStyle(document.body).backgroundColor;
    if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') bar.style.setProperty('--mgd-share-bar-bg', bg);
    const fixed = getComputedStyle(bar).position === 'fixed' && getComputedStyle(bar).bottom === '0px';
    document.body.style.paddingBottom = fixed ? `${bar.offsetHeight}px` : '';
  };

  document.querySelectorAll('[data-mgd-share]').forEach(bind);
  padForBar();
  window.addEventListener('resize', padForBar, { passive: true });
  window.MGDShare = { bind };
})();
