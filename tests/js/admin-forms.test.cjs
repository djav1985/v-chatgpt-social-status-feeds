const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const { test } = require('node:test');

const scriptPath = path.resolve('root/public/assets/js/admin-forms.js');
const adminFormsScript = readFileSync(scriptPath, 'utf8');

function createDom() {
  const dom = new JSDOM(`<!doctype html><html><body>
    <form action="/accounts" id="account-form">
      <input id="account" name="account" value="">
      <input id="link" name="link" value="https://domain.com">
      <select id="hashtags" name="hashtags"><option value="0" selected>No</option><option value="1">Yes</option></select>
      <select id="platform" name="platform"><option value="facebook" selected>Facebook</option><option value="twitter">Twitter</option></select>
      <textarea id="add-prompt" name="prompt">Create a compelling status update...</textarea>
      <select id="days" name="days[]" multiple><option value="everyday">Everyday</option><option value="monday">Monday</option></select>
      <select id="cron" name="cron[]" multiple><option value="null" selected>Off</option><option value="18">6 pm</option></select>
      <button id="cancel-account-update" type="button" hidden>Cancel update</button>
      <button type="submit">Add or update account</button>
    </form>
    <form action="/users" id="user-form" class="form-grid">
      <input id="username" name="username" value="">
      <input id="email" name="email" value="">
      <select id="total-accounts" name="total-accounts"><option value="3">3</option></select>
      <select id="max-api-calls" name="max-api-calls"><option value="90">90</option></select>
      <select id="used-api-calls" name="used-api-calls"><option value="0">0</option></select>
      <input id="expires" name="expires" value="">
      <select id="admin" name="admin"><option value="0">No</option><option value="1">Yes</option></select>
      <button type="submit">Save</button>
    </form>
    <button class="account-update" type="button" data-account-name="Cafe" data-link="https://cafe.example" data-hashtags="1" data-cron="18" data-days="monday" data-platform="twitter" data-prompt="Post about coffee"></button>
    <button class="user-update" type="button" data-username="ada" data-email="ada@example.test" data-total-accounts="3" data-max-api-calls="90" data-used-api-calls="4" data-expires="2027-01-01" data-admin="1"></button>
  </body></html>`, { runScripts: 'dangerously', url: 'https://example.test/' });

  dom.window.HTMLElement.prototype.scrollIntoView = () => {};
  dom.window.eval(adminFormsScript);
  return dom;
}

test('account update mode can be canceled without submitting and restores add defaults', () => {
  const dom = createDom();
  const { document } = dom.window;
  const accountForm = document.querySelector('#account-form');
  let submitCount = 0;
  accountForm.addEventListener('submit', event => {
    event.preventDefault();
    submitCount++;
  });

  document.querySelector('.account-update').click();
  assert.equal(document.querySelector('#account').value, 'Cafe');
  assert.equal(document.querySelector('#account').readOnly, true);
  assert.equal(document.querySelector('#cancel-account-update').hidden, false);

  document.querySelector('#cancel-account-update').click();

  assert.equal(document.querySelector('#account').value, '');
  assert.equal(document.querySelector('#link').value, 'https://domain.com');
  assert.equal(document.querySelector('#account').readOnly, false);
  assert.equal(document.querySelector('#cancel-account-update').hidden, true);
  assert.equal(submitCount, 0);
  dom.window.close();
});

test('user update includes the selected readonly username on form submission', () => {
  const dom = createDom();
  const { document, FormData } = dom.window;
  const userForm = document.querySelector('#user-form');
  document.querySelector('.user-update').click();

  assert.equal(document.querySelector('#username').readOnly, true);
  userForm.dispatchEvent(new dom.window.Event('submit', { bubbles: true, cancelable: true }));

  assert.equal(document.querySelector('#username').readOnly, false);
  assert.equal(new FormData(userForm).get('username'), 'ada');
  dom.window.close();
});
