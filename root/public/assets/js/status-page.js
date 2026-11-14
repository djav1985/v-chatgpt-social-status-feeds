(() => {
  const accordion = document.querySelector('#status-accordion');
  if (!accordion) return;

  const panels = [...accordion.querySelectorAll('.status-panel')];
  const key = 'statusAdmin.openAccount';
  const syncButtons = () => panels.forEach(panel => {
    const button = accordion.querySelector(`[aria-controls="${CSS.escape(panel.id)}"]`);
    const open = panel.classList.contains('show');
    if (button) {
      button.classList.toggle('collapsed', !open);
      button.setAttribute('aria-expanded', String(open));
    }
  });
  const openName = localStorage.getItem(key);
  const remembered = panels.find(panel => panel.dataset.accountName === openName);
  if (openName === '__none__') {
    panels.forEach(panel => panel.classList.remove('show'));
  } else if (remembered) {
    panels.forEach(panel => panel.classList.toggle('show', panel === remembered));
  }
  syncButtons();
  panels.forEach(panel => {
    panel.addEventListener('shown.bs.collapse', () => {
      localStorage.setItem(key, panel.dataset.accountName || '');
      syncButtons();
    });
    panel.addEventListener('hidden.bs.collapse', () => {
      window.requestAnimationFrame(() => {
        const openPanel = panels.find(item => item.classList.contains('show'));
        localStorage.setItem(key, openPanel?.dataset.accountName || '__none__');
        syncButtons();
      });
    });
  });

  accordion.addEventListener('click', async event => {
    const copy = event.target.closest('.copy-button');
    const share = event.target.closest('.share-button');
    const button = copy || share;
    if (!button) return;
    const text = button.dataset.text || '';
    const url = button.dataset.url || '';
    try {
      if (share && navigator.share) {
        await navigator.share({ text, url });
      } else if (copy) {
        await navigator.clipboard.writeText(text);
        if (url) {
          const link = document.createElement('a');
          link.href = url;
          link.download = '';
          link.rel = 'noopener';
          link.click();
        }
        window.showToast?.('Status copied. Image download started.', 'success');
      } else {
        await navigator.clipboard.writeText(`${text} ${url}`.trim());
        window.showToast?.('Status link copied.', 'success');
      }
    } catch (error) {
      if (error?.name !== 'AbortError') window.showToast?.('Could not complete that action.', 'error');
    }
  });
})();
