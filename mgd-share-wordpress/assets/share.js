(() => {
  const labels = { whatsapp: 'WhatsApp', linkedin: 'LinkedIn', x: 'X' };
  const encode = encodeURIComponent;
  const targetFor = (action, title, url) => ({
    email: `mailto:?subject=${encode(title)}&body=${encode(`${title}\n${url}`)}`,
    whatsapp: `https://wa.me/?text=${encode(`${title} ${url}`)}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encode(url)}`,
    x: `https://x.com/intent/post?text=${encode(title)}&url=${encode(url)}`,
  })[action];

  document.querySelectorAll('[data-mgd-share]').forEach((root) => {
    const url = root.dataset.url;
    const title = root.dataset.title;
    const trigger = root.querySelector('[data-mgd-share-trigger]');
    const menu = root.querySelector('[data-mgd-share-menu]');
    const dialog = root.querySelector('[data-mgd-share-dialog]');
    const dialogCopy = root.querySelector('[data-mgd-share-dialog-copy]');
    const confirm = root.querySelector('[data-mgd-share-confirm]');
    const status = root.querySelector('[data-mgd-share-status]');
    let pendingTarget = '';
    if (!url || !title || !trigger || !menu || !status) return;

    const announce = (message) => { status.textContent = message; };
    const closeMenu = () => { menu.hidden = true; trigger.setAttribute('aria-expanded', 'false'); };
    const openMenu = () => { menu.hidden = false; trigger.setAttribute('aria-expanded', 'true'); };
    const copy = async (message) => {
      try { await navigator.clipboard.writeText(url); announce(message); }
      catch {
        const temporary = document.createElement('textarea');
        temporary.value = url; temporary.readOnly = true; temporary.style.position = 'fixed'; temporary.style.opacity = '0';
        document.body.append(temporary); temporary.select(); document.execCommand('copy'); temporary.remove();
        announce(`${message} Falls das nicht geklappt hat, kopiere die Adresse aus der Browserleiste.`);
      }
    };

    trigger.addEventListener('click', () => menu.hidden ? openMenu() : closeMenu());
    document.addEventListener('click', (event) => { if (!root.contains(event.target)) closeMenu(); });
    root.addEventListener('keydown', (event) => { if (event.key === 'Escape') { closeMenu(); trigger.focus(); } });
    root.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-mgd-share-action]');
      if (!button) return;
      const action = button.dataset.mgdShareAction;
      if (action === 'copy') { await copy('Link wurde in die Zwischenablage kopiert.'); closeMenu(); return; }
      if (action === 'native' && navigator.share) { try { await navigator.share({ title, url }); } catch {} closeMenu(); return; }
      if (action === 'native') { await copy('System-Teilen ist nicht verfügbar. Der Link wurde kopiert.'); closeMenu(); return; }
      const target = targetFor(action, title, url);
      if (!target) return;
      if (button.hasAttribute('data-mgd-share-external') && dialog?.showModal) {
        pendingTarget = target; dialogCopy.textContent = `${labels[action]} erhält den Seitentitel und die Seitenadresse.`; dialog.showModal(); return;
      }
      window.open(target, '_blank', 'noopener,noreferrer'); closeMenu();
    });
    confirm?.addEventListener('click', () => { if (pendingTarget) window.open(pendingTarget, '_blank', 'noopener,noreferrer'); closeMenu(); });
  });
})();
