/* Login & register AJAX. */
(() => {
  'use strict';

  const api = window.RENTIFY;
  if (!api) return;

  const forms = document.querySelectorAll('[data-auth-form]');
  forms.forEach((form) => {
    const errorBox = document.querySelector('[data-auth-error]');
    const submit = form.querySelector('[data-auth-submit]');
    const kind = form.dataset.authForm;

    function clearErrors() {
      errorBox?.classList.add('hidden');
      if (errorBox) errorBox.textContent = '';
      form.querySelectorAll('[data-error-for]').forEach((el) => {
        el.classList.add('hidden');
        el.textContent = '';
      });
      form.querySelectorAll('.input').forEach((input) => {
        input.classList.remove('!border-danger', '!ring-2', '!ring-red-100');
        input.removeAttribute('aria-invalid');
      });
    }

    function showError(message, errors = {}) {
      if (errorBox) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
      }
      Object.entries(errors || {}).forEach(([field, text]) => {
        const hint = form.querySelector(`[data-error-for="${field}"]`);
        const input = form.querySelector(`[name="${field}"]`);
        if (hint) {
          hint.textContent = text;
          hint.classList.remove('hidden');
        }
        if (input) {
          input.classList.add('!border-danger', '!ring-2', '!ring-red-100');
          input.setAttribute('aria-invalid', 'true');
        }
      });
      errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      clearErrors();
      if (!form.reportValidity()) return;

      const data = new FormData(form);
      if (kind === 'register') {
        if (!form.querySelector('[data-register-agree]')?.checked) {
          showError('Centang persetujuan ketentuan sewa terlebih dahulu.');
          return;
        }
        if (data.get('password') !== data.get('password_confirmation')) {
          showError('Konfirmasi kata sandi tidak sama.', { password_confirmation: 'Kata sandi tidak sama.' });
          return;
        }
      }
      data.append('action', kind);

      api.setLoading(submit, true, kind === 'register' ? 'Membuat akun...' : 'Masuk...');
      try {
        const payload = await api.request('auth.php', { method: 'POST', body: data });
        api.toast(payload.message || 'Berhasil.', 'success', 2600);
        window.setTimeout(() => {
          window.location.href = payload.redirect || api.baseUrl(kind === 'register' ? 'beranda.php' : 'beranda.php');
        }, 550);
      } catch (error) {
        showError(error.message || 'Gagal memproses. Coba lagi.', error.payload?.errors || {});
        api.setLoading(submit, false);
      }
    });
  });

  document.querySelectorAll('[data-demo-login]').forEach((button) => {
    button.addEventListener('click', () => {
      const form = document.querySelector('[data-auth-form="login"]');
      if (!form) return;
      const login = form.querySelector('[name="login"]');
      const password = form.querySelector('[name="password"]');
      if (login) login.value = button.dataset.email || '';
      if (password) password.value = button.dataset.password || '';
      api.toast('Mengisi akun demo. Tekan Masuk untuk melanjutkan.', 'info', 2200);
      login?.focus();
    });
  });
})();
