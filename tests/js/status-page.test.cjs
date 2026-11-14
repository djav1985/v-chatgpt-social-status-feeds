const assert = require('node:assert/strict');
const { Blob } = require('node:buffer');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { cwd } = require('node:process');
const { test } = require('node:test');
const { setTimeout: delay } = require('node:timers/promises');
const { JSDOM } = require('jsdom');

const statusPageScript = readFileSync(
  resolve(cwd(), 'root/public/assets/js/status-page.js'),
  'utf8',
);

function setup({ openAccount = null, clipboard, share, canShare, fetch } = {}) {
  const dom = new JSDOM(`
    <div id="status-accordion">
      <button class="accordion-button collapsed" aria-controls="panel-alpha" aria-expanded="false"></button>
      <div id="panel-alpha" class="status-panel collapse" data-account-name="Alpha"></div>
      <button class="accordion-button collapsed" aria-controls="panel-beta" aria-expanded="false"></button>
      <div id="panel-beta" class="status-panel collapse" data-account-name="Beta"></div>
      <button class="copy-button" data-text="A status" data-url="https://example.test/status.png"><span id="copy-target">copy</span></button>
      <button class="share-button" data-text="A status" data-url="https://example.test/status.png"><span id="share-target">share</span></button>
    </div>`, {
    runScripts: 'outside-only',
    url: 'http://localhost3/',
    pretendToBeVisual: true,
  });
  const { window } = dom;
  Object.defineProperty(window, 'CSS', { value: { escape: value => value }, configurable: true });
  if (openAccount !== null) window.localStorage.setItem('statusAdmin.openAccount', openAccount);
  if (clipboard !== undefined) Object.defineProperty(window.navigator, 'clipboard', { value: clipboard, configurable: true });
  if (share !== undefined) Object.defineProperty(window.navigator, 'share', { value: share, configurable: true });
  if (canShare !== undefined) Object.defineProperty(window.navigator, 'canShare', { value: canShare, configurable: true });
  if (fetch !== undefined) Object.defineProperty(window, 'fetch', { value: fetch, configurable: true });

  const copied = [];
  window.document.execCommand = command => {
    if (command === 'copy') copied.push(window.document.querySelector('textarea')?.value ?? '');
    return command === 'copy';
  };
  const downloads = [];
  window.HTMLAnchorElement.prototype.click = function () {
    downloads.push({ href: this.href, download: this.download });
  };
  const toasts = [];
  window.showToast = (message, variant) => toasts.push({ message, variant });

  window.eval(statusPageScript);
  return { dom, window, copied, downloads, toasts };
}

async function clickAndFlush(window, selector) {
  window.document.querySelector(selector).click();
  await delay(0);
  await delay(0);
}

test('copies with Clipboard API and downloads the image', async () => {
  const writes = [];
  const harness = setup({ clipboard: { writeText: async text => writes.push(text) } });

  await clickAndFlush(harness.window, '#copy-target');

  assert.deepEqual(writes, ['A status']);
  assert.equal(harness.downloads.length, 1);
  assert.equal(harness.downloads[0].href, 'https://example.test/status.png');
  assert.equal(harness.toasts.at(-1).variant, 'success');
  harness.dom.window.close();
});

test('uses the textarea fallback and still downloads the image without Clipboard API', async () => {
  const harness = setup();

  await clickAndFlush(harness.window, '#copy-target');

  assert.deepEqual(harness.copied, ['A status']);
  assert.equal(harness.downloads.length, 1);
  assert.equal(harness.toasts.at(-1).variant, 'success');
  harness.dom.window.close();
});

test('starts the image download when both clipboard methods fail', async () => {
  const harness = setup({ clipboard: { writeText: async () => { throw new Error('denied'); } } });
  harness.window.document.execCommand = () => false;

  await clickAndFlush(harness.window, '#copy-target');

  assert.equal(harness.downloads.length, 1);
  assert.equal(harness.toasts.at(-1).variant, 'error');
  harness.dom.window.close();
});

test('shares the generated image file when native file sharing is supported', async () => {
  const shared = [];
  const harness = setup({
    share: async payload => shared.push(payload),
    canShare: payload => Array.isArray(payload.files),
    fetch: async () => ({
      ok: true,
      blob: async () => new Blob(['image bytes'], { type: 'image/png' }),
    }),
  });

  await clickAndFlush(harness.window, '#share-target');

  assert.equal(shared.length, 1);
  assert.equal(shared[0].text, 'A status');
  assert.equal(shared[0].files.length, 1);
  assert.equal(shared[0].files[0].type, 'image/png');
  assert.equal(shared[0].files[0].name, 'status.png');
  harness.dom.window.close();
});

test('falls back to sharing the image URL when native file sharing is unsupported', async () => {
  const shared = [];
  const harness = setup({
    share: async payload => shared.push(payload),
    canShare: () => false,
    fetch: async () => { throw new Error('fetch should not be needed'); },
  });

  await clickAndFlush(harness.window, '#share-target');

  assert.equal(shared.length, 1);
  assert.equal(shared[0].text, 'A status');
  assert.equal(shared[0].url, 'https://example.test/status.png');
  harness.dom.window.close();
});

test('falls back to the image URL if fetching a native share file fails', async () => {
  const shared = [];
  const harness = setup({
    share: async payload => shared.push(payload),
    canShare: () => true,
    fetch: async () => { throw new Error('network error'); },
  });

  await clickAndFlush(harness.window, '#share-target');

  assert.equal(shared.length, 1);
  assert.equal(shared[0].text, 'A status');
  assert.equal(shared[0].url, 'https://example.test/status.png');
  harness.dom.window.close();
});

test('reports native share failure without throwing an uncaught rejection', async () => {
  const harness = setup({ share: async () => { throw new Error('share failed'); } });

  await clickAndFlush(harness.window, '#share-target');

  assert.equal(harness.toasts.at(-1).variant, 'error');
  harness.dom.window.close();
});

test('restores the remembered account accordion panel', () => {
  const harness = setup({ openAccount: 'Beta' });
  const { document } = harness.window;

  assert.equal(document.querySelector('#panel-alpha').classList.contains('show'), false);
  assert.equal(document.querySelector('#panel-beta').classList.contains('show'), true);
  assert.equal(document.querySelector('[aria-controls="panel-beta"]').getAttribute('aria-expanded'), 'true');
  harness.dom.window.close();
});
