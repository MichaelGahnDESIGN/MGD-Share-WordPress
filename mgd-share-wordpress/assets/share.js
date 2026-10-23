(() => {
  const open = (url) => window.open(url, '_blank', 'noopener,noreferrer');
  const encode = encodeURIComponent;
  document.querySelectorAll('[data-mgd-share]').forEach((root) => {
    const url = root.dataset.url;
    const title = root.dataset.title;
    const status = root.querySelector('[data-mgd-share-status]');
    if (!url || !title || !status) return;
    const announce = (message) => { status.textContent = message; };
    root.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-mgd-share-action]');
      if (!button) return;
      const action = button.dataset.mgdShareAction;
      if (action === 'copy') {
        try { await navigator.clipboard.writeText(url); announce('Link wurde in die Zwischenablage kopiert.'); }
        catch { window.prompt('Kopiere diesen Link:', url); }
        return;
      }
      if (action === 'native' && navigator.share) {
        try { await navigator.share({ title, url }); } catch { /* Abbrechen ist keine Fehlermeldung. */ }
        return;
      }
      const targets = {
        email: `mailto:?subject=${encode(title)}&body=${encode(`${title}\n${url}`)}`,
        whatsapp: `https://wa.me/?text=${encode(`${title} ${url}`)}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encode(url)}`,
        x: `https://x.com/intent/post?text=${encode(title)}&url=${encode(url)}`,
      };
      if (targets[action]) open(targets[action]);
      else if (action === 'native') { try { await navigator.clipboard.writeText(url); announce('Dein Browser unterstützt System-Teilen nicht. Der Link wurde kopiert.'); } catch { window.prompt('Kopiere diesen Link:', url); } }
    });
  });
})();
