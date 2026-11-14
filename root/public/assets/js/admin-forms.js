(() => {
  document.querySelectorAll('.account-update').forEach(button => button.addEventListener('click', () => {
    for (const field of ['account','link','hashtags','platform']) document.querySelector(`#${field}`).value = button.dataset[field === 'account' ? 'accountName' : field] || '';
    document.querySelector('#add-prompt').value = button.dataset.prompt || '';
    document.querySelector('#account').readOnly = true;
    const days = document.querySelector('#days');
    const selectedDays = (button.dataset.days || '').split(',');
    [...days.options].forEach(option => option.selected = selectedDays.includes(option.value));
    const cron = document.querySelector('#cron');
    const selectedTimes = (button.dataset.cron || '').split(',');
    [...cron.options].forEach(option => option.selected = selectedTimes.includes(option.value));
    if (!selectedTimes.length || selectedTimes[0] === '') cron.querySelector('[value="null"]').selected = true;
    document.querySelector('#account').scrollIntoView({behavior: 'smooth', block: 'center'});
  }));
  const accountForm = document.querySelector('form[action="/accounts"]');
  accountForm?.addEventListener('submit', () => { document.querySelector('#account').readOnly = false; });
  document.querySelector('#cron')?.addEventListener('change', event => {
    const field = event.currentTarget;
    const off = field.querySelector('[value="null"]');
    if (event.target.value === 'null' && event.target.selected) {
      [...field.options].forEach(option => { option.selected = option.value === 'null'; });
    } else if (event.target.selected && off) {
      off.selected = false;
    }
  });
  document.querySelector('#days')?.addEventListener('change', event => {
    const field = event.currentTarget;
    if (event.target.value === 'everyday' && event.target.selected) {
      [...field.options].forEach(option => option.selected = option.value === 'everyday');
    } else if (event.target.selected) {
      const everyday = field.querySelector('[value="everyday"]');
      if (everyday) everyday.selected = false;
    }
  });
  document.querySelectorAll('.user-update').forEach(button => button.addEventListener('click', () => {
    for (const field of ['username','email','total-accounts','max-api-calls','expires','admin']) {
      const dataKey = field.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
      const input = document.getElementById(field);
      if (input) input.value = button.dataset[dataKey] || '';
    }
    const used = document.querySelector('#used-api-calls');
    if (used) { const value=button.dataset.usedApiCalls || '0'; used.replaceChildren(new Option(value,value), new Option('0','0')); }
    const password = document.querySelector('#password');
    if (password) {
      password.value = '';
      password.placeholder = '********';
    }
    document.querySelector('#username').readOnly = true;
    document.querySelector('#username').scrollIntoView({behavior:'smooth',block:'center'});
  }));
  const profile = document.querySelector('#update-profile-form');
  if (profile) for (const field of ['who','where','what','goal']) document.getElementById(field).value = profile.dataset[field] || '';
})();

