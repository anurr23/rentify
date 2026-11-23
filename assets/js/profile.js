/* Profil: update data & kata sandi via AJAX. */
(() => {
  'use strict';

  const root = document.querySelector('[data-profile-root]');
  if (!root) return;
  const api = window.RENTIFY;
  if (!api) return;

  const profileForm = root.querySelector('[data-profile-form]');
  const passwordForm = root.querySelector('[data-password-form]');

  function clearErrors(form) {
    form.querySelectorAll('.input').forEach((input) => {
      input.classList.remove('!border-danger', '!ring-2', '!ring-red-100');
      input.removeAttribute('aria-invalid');
    });
    form.querySelectorAll('[data-field-error]').forEach((el) => el.remove());
  }

  function markErrors(form, errors) {
    Object.entries(errors || {}).forEach(([field, message]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('!border-danger', '!ring-2', '!ring-red-100');
      input.setAttribute('aria-invalid', 'true');
      const help = document.createElement('p');
      help.dataset.fieldError = '';
      help.className = 'mt-1 text-[0.68rem] font-bold text-danger';
      help.textContent = message;
      input.insertAdjacentElement('afterend', help);
    });
  }

  profileForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!profileForm.reportValidity()) return;
    clearErrors(profileForm);
    const button = profileForm.querySelector('button[type="submit"]');
    const data = new FormData(profileForm);
    data.append('action', 'update');
    api.setLoading(button, true, 'Menyimpan...');
    try {
      const payload = await api.request('profile.php', { method: 'POST', body: data });
      api.toast(payload.message || 'Profil diperbarui.', 'success');
      window.setTimeout(() => { window.location.reload(); }, 750);
    } catch (error) {
      api.toast(error.message || 'Gagal menyimpan profil.', 'error');
      if (error.payload?.errors) markErrors(profileForm, error.payload.errors);
      api.setLoading(button, false);
    }
  });

  passwordForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!passwordForm.reportValidity()) return;
    clearErrors(passwordForm);
    const newPassword = passwordForm.querySelector('[name="new_password"]');
    const confirmPassword = passwordForm.querySelector('[name="new_password_confirmation"]');
    if (newPassword.value !== confirmPassword.value) {
      api.toast('Konfirmasi kata sandi baru tidak sama.', 'warning');
      confirmPassword.focus();
      return;
    }
    const button = passwordForm.querySelector('button[type="submit"]');
    const data = new FormData(passwordForm);
    data.append('action', 'password');
    api.setLoading(button, true, 'Memperbarui...');
    try {
      const payload = await api.request('profile.php', { method: 'POST', body: data });
      api.toast(payload.message || 'Kata sandi diperbarui.', 'success');
      passwordForm.reset();
    } catch (error) {
      api.toast(error.message || 'Gagal memperbarui kata sandi.', 'error');
      if (error.payload?.errors) markErrors(passwordForm, error.payload.errors);
    } finally {
      api.setLoading(button, false);
    }
  });
})();
