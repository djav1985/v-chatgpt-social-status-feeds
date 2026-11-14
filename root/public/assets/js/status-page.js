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

  const copyText = async text => {
    if (typeof navigator.clipboard?.writeText === 'function') {
      try {
        await navigator.clipboard.writeText(text);
        return true;
      } catch {
        // Continue to the legacy fallback for browsers that reject clipboard access.
      }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    let copied;
    try {
      copied = document.execCommand?.('copy') === true;
    } catch {
      copied = false;
    }
    textarea.remove();
    return copied;
  };

  const downloadImage = url => {
    if (!url) return false;
    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    link.rel = 'noopener';
    link.hidden = true;
    document.body.appendChild(link);
    link.click();
    link.remove();
    return true;
  };

  const createFileSharePayload = async (text, url) => {
    if (
      !url
      || typeof navigator.canShare !== 'function'
      || typeof window.fetch !== 'function'
      || typeof window.File !== 'function'
    ) return null;

    try {
      const response = await window.fetch(url);
      if (!response.ok) return null;
      const blob = await response.blob();
      const path = new URL(url, document.baseURI).pathname;
      const fileName = decodeURIComponent(path.split('/').pop() || 'status-image')
        .replace(/[\\/:*?"<>|]/g, '_');
      const file = new window.File([blob], fileName, { type: blob.type || 'application/octet-stream' });
      const payload = { text, files: [file] };
      return navigator.canShare(payload) ? payload : null;
    } catch {
      return null;
    }
  };

  accordion.addEventListener('click', async event => {
    const copy = event.target.closest('.copy-button');
    const share = event.target.closest('.share-button');
    const button = copy || share;
    if (!button) return;
    const text = button.dataset.text || '';
    const url = button.dataset.url || '';
    try {
      if (share && typeof navigator.share === 'function') {
        const payload = await createFileSharePayload(text, url) || { text, url };
        await navigator.share(payload);
      } else if (copy) {
        const copied = await copyText(text);
        const downloaded = downloadImage(url);
        if (copied) {
          window.showToast?.(downloaded ? 'Status copied. Image download started.' : 'Status copied.', 'success');
        } else {
          window.showToast?.(downloaded ? 'Image download started, but status could not be copied.' : 'Could not copy status.', 'error');
        }
      } else {
        const copied = await copyText(`${text} ${url}`.trim());
        window.showToast?.(copied ? 'Status link copied.' : 'Could not copy status link.', copied ? 'success' : 'error');
      }
    } catch (error) {
      if (error?.name !== 'AbortError') window.showToast?.('Could not complete that action.', 'error');
    }
  });
})();
