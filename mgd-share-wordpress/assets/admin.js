/* Live-Vorschau Einstellungen - gleiche Klassen-Logik wie MGD_Share_Options::classes() */
(() => {
  const form = document.getElementById('mgd-sa-form');
  const stage = document.getElementById('mgd-sa-stage');
  const list = document.getElementById('mgd-sa-networks');
  if (!form || !stage || !list) return;
  const preview = stage.querySelector('[data-mgd-share]');
  if (!preview) return;
  const KEYS = ['shape', 'variant', 'color_mode', 'size', 'display', 'align', 'hover', 'container'];

  const radio = (name) => (form.querySelector(`input[name="${name}"]:checked`) || {}).value || '';

  // gleiche Formel wie PHP on_color()
  const onColor = (hex) => {
    const h = hex.replace('#', '');
    if (h.length !== 6) return '#ffffff';
    const lin = [0, 2, 4].map((i) => {
      const c = parseInt(h.slice(i, i + 2), 16) / 255;
      return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * lin[0] + 0.7152 * lin[1] + 0.0722 * lin[2] > 0.4 ? '#111827' : '#ffffff';
  };

  const refresh = () => {
    const classes = ['mgd-share', 'mgd-share--preview'];
    KEYS.forEach((key) => classes.push(`mgd-share--${key.replace('_', '-')}-${radio(key)}`));
    preview.className = classes.join(' ');
    const accent = form.querySelector('[name="accent"]').value;
    preview.style.setProperty('--mgd-share-accent', accent);
    preview.style.setProperty('--mgd-share-on', onColor(accent));

    // Text vor den Buttons
    const label = preview.querySelector('.mgd-share__label');
    const text = form.querySelector('[name="label"]').value.trim();
    label.textContent = text;
    label.hidden = !form.querySelector('[name="show_label"]').checked || text === '';

    // Buttons: Reihenfolge + an/aus aus der Liste
    const actions = preview.querySelector('.mgd-share__actions');
    list.querySelectorAll('input[name="networks[]"]').forEach((box) => {
      const btn = actions.querySelector(`[data-mgd-share-action="${box.value}"]`);
      if (!btn) return;
      btn.hidden = !box.checked;
      actions.appendChild(btn);
    });
  };

  form.addEventListener('input', refresh);
  form.addEventListener('change', refresh);

  // Schnellstart-Vorlagen
  form.querySelectorAll('[data-preset]').forEach((button) => {
    button.addEventListener('click', () => {
      const values = JSON.parse(button.dataset.preset);
      Object.entries(values).forEach(([key, value]) => {
        const input = form.querySelector(`input[name="${key}"][value="${value}"]`);
        if (input) input.checked = true;
      });
      refresh();
    });
  });

  // Farbfelder
  form.querySelectorAll('[data-color]').forEach((swatch) => {
    swatch.addEventListener('click', () => {
      form.querySelector('[name="accent"]').value = swatch.dataset.color;
      refresh();
    });
  });

  // Sortieren per Ziehen (jQuery UI aus WP) + Pfeile für Tastatur
  if (window.jQuery && window.jQuery.fn.sortable) {
    window.jQuery(list).sortable({ handle: '.mgd-sa-network__grip', axis: 'y', update: refresh });
  }
  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-move]');
    if (!button) return;
    const item = button.closest('li');
    if (button.dataset.move === '-1' && item.previousElementSibling) item.parentNode.insertBefore(item, item.previousElementSibling);
    if (button.dataset.move === '1' && item.nextElementSibling) item.parentNode.insertBefore(item.nextElementSibling, item);
    button.focus();
    refresh();
  });

  // Hintergrund der Vorschau
  document.querySelectorAll('[data-bg]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-bg]').forEach((b) => b.classList.toggle('is-active', b === button));
      stage.classList.toggle('is-dark', button.dataset.bg === 'dark');
    });
  });

  refresh();
})();
