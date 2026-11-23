/* Admin panel: modal, CRUD AJAX, status, verifikasi, bagan. */
(() => {
  'use strict';

  const api = window.RENTIFY;
  if (!api) return;

  const sidebar = document.querySelector('[data-admin-sidebar]');
  const overlay = document.querySelector('[data-admin-overlay]');

  function closeSidebar() {
    sidebar?.classList.add('hidden');
    sidebar?.classList.remove('flex');
    overlay?.classList.add('hidden');
  }

  function openSidebar() {
    sidebar?.classList.remove('hidden');
    sidebar?.classList.add('flex');
    overlay?.classList.remove('hidden');
  }

  document.querySelector('[data-admin-toggle]')?.addEventListener('click', openSidebar);
  overlay?.addEventListener('click', closeSidebar);

  /* ---------- Modal helpers ---------- */
  function setError(form, message, errors = {}) {
    let box = form.querySelector('[data-admin-error]');
    if (!box) {
      box = document.createElement('div');
      box.dataset.adminError = '';
      box.className = 'rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700';
      form.prepend(box);
    }
    box.textContent = message;
    Object.entries(errors || {}).forEach(([field, text]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('!border-danger', '!ring-2', '!ring-red-100');
      input.setAttribute('aria-invalid', 'true');
      const hint = document.createElement('p');
      hint.dataset.adminFieldError = '';
      hint.className = 'mt-1 text-[0.68rem] font-bold text-danger';
      hint.textContent = text;
      input.insertAdjacentElement('afterend', hint);
    });
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function clearError(form) {
    form.querySelectorAll('[data-admin-error], [data-admin-field-error]').forEach((el) => el.remove());
    form.querySelectorAll('.input').forEach((input) => {
      input.classList.remove('!border-danger', '!ring-2', '!ring-red-100');
      input.removeAttribute('aria-invalid');
    });
  }

  function enhanceForm(form) {
    const categorySelect = form.querySelector('[data-item-category]');
    const groups = form.querySelectorAll('[data-filter-group]');
    function syncGroups() {
      if (!categorySelect || !groups.length) return;
      groups.forEach((group) => {
        const hidden = group.dataset.filterGroup !== categorySelect.value;
        group.classList.toggle('hidden', hidden);
        group.querySelectorAll('input, select, textarea').forEach((input) => {
          input.disabled = hidden;
        });
      });
    }
    categorySelect?.addEventListener('change', syncGroups);
    syncGroups();

    const roleSelect = form.querySelector('[data-user-role]');
    const storeWrap = form.querySelector('[data-user-store-wrap]');
    const storeSelect = form.querySelector('[data-user-store]');
    function syncRole() {
      if (!roleSelect || !storeWrap) return;
      const isAdmin = roleSelect.value === 'admin';
      storeWrap.classList.toggle('hidden', !isAdmin);
      if (storeSelect) storeSelect.required = isAdmin;
    }
    roleSelect?.addEventListener('change', syncRole);
    syncRole();

    const variantList = form.querySelector('[data-variant-list]');
    form.querySelector('[data-add-variant]')?.addEventListener('click', () => {
      if (!variantList) return;
      const row = document.createElement('div');
      row.className = 'grid gap-2 rounded-2xl border border-line p-3 sm:grid-cols-[.7fr_1.4fr_.8fr_.8fr_.8fr_auto]';
      row.dataset.variantRow = '';
      row.innerHTML = `<input type="hidden" name="variant_id[]" value="0">
        <input class="input !py-2 text-xs" name="variant_label[]" placeholder="Label (mis. 4P)">
        <input class="input !py-2 text-xs" name="variant_sku[]" placeholder="SKU">
        <input class="input !py-2 text-xs" type="number" min="0" name="variant_stock[]" placeholder="Stok" value="0">
        <input class="input !py-2 text-xs" type="number" min="0" name="variant_price[]" placeholder="Harga/hari">
        <button type="button" class="icon-btn h-9 w-9 self-center text-danger" data-remove-variant aria-label="Hapus varian">${trashIcon()}</button>`;
      variantList.appendChild(row);
      row.querySelector('input')?.focus();
    });

    form.querySelectorAll('[data-preview-input]').forEach((input) => {
      input.addEventListener('change', () => {
        const target = document.querySelector(input.dataset.previewInput);
        const file = input.files?.[0];
        if (!target || !file) return;
        if (!file.type.startsWith('image/')) {
          api.toast('File harus berupa gambar.', 'error');
          input.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = (event) => {
          target.innerHTML = `<img src="${event.target.result}" alt="Pratinjau" class="h-full w-full object-cover">`;
        };
        reader.readAsDataURL(file);
      });
    });
  }

  function trashIcon() {
    return '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>';
  }

  async function openAdminModal(url, fallbackTitle = 'Form') {
    try {
      const payload = await api.request(url);
      const modal = api.openModal(payload.html || '', {
        title: payload.title || fallbackTitle,
        maxWidth: '52rem',
      });
      if (!modal) return;
      const form = modal.querySelector('form');
      if (form) enhanceForm(form);
      document.dispatchEvent(new CustomEvent('admin:modal-open', { detail: { modal, form } }));
    } catch (error) {
      api.toast(error.message || 'Gagal membuka form.', 'error');
    }
  }

  document.addEventListener('click', (event) => {
    const open = event.target.closest('[data-admin-modal]');
    if (open) {
      event.preventDefault();
      openAdminModal(open.dataset.url, open.dataset.adminModalTitle || open.textContent.trim());
      return;
    }

    if (event.target.closest('[data-close-modal]')) {
      event.preventDefault();
      api.closeModal();
      return;
    }

    const removeVariant = event.target.closest('[data-remove-variant]');
    if (removeVariant) {
      event.preventDefault();
      const row = removeVariant.closest('[data-variant-row]');
      const list = row?.parentElement;
      row?.remove();
      if (list && !list.querySelector('[data-variant-row]')) {
        /* biarkan kosong, form tetap valid tanpa varian */
      }
    }
  });

  /* ---------- Generic admin forms ---------- */
  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-admin-form]');
    if (!form) return;
    event.preventDefault();
    if (form.dataset.busy === '1') return;
    clearError(form);
    form.dataset.busy = '1';
    const submit = form.querySelector('button[type="submit"]');
    api.setLoading(submit, true, 'Menyimpan...');
    try {
      const payload = await api.request('admin.php', { method: 'POST', body: new FormData(form) });
      api.toast(payload.message || 'Tersimpan.', 'success');
      const modal = form.closest('[data-modal]');
      if (payload.reload) {
        const target = payload.redirect || window.location.href;
        window.setTimeout(() => { window.location.href = target; }, 650);
      } else {
        if (modal) api.closeModal(modal);
        api.setLoading(submit, false);
        form.dataset.busy = '0';
      }
    } catch (error) {
      api.toast(error.message || 'Gagal menyimpan.', 'error');
      setError(form, error.message || 'Gagal menyimpan.', error.payload?.errors || {});
      api.setLoading(submit, false);
      form.dataset.busy = '0';
    }
  });

  /* ---------- Delete ---------- */
  document.querySelectorAll('[data-admin-delete]').forEach((button) => {
    button.addEventListener('click', async () => {
      const type = button.dataset.adminDelete;
      const id = button.dataset.id;
      const name = button.dataset.name || 'item ini';
      const confirmed = await api.confirmDialog(`Hapus ${name}? Tindakan ini tidak bisa dibatalkan jika belum pernah dipakai.`, {
        title: 'Hapus data',
        confirmText: 'Ya, hapus',
        danger: true,
      });
      if (!confirmed) return;
      api.setLoading(button, true, '');
      try {
        const body = { action: `${type}_delete`, id };
        if (button.dataset.storeId) body.store_id = button.dataset.storeId;
        const payload = await api.request('admin.php', { method: 'POST', body });
        api.toast(payload.message || 'Dihapus.', 'success');
        window.setTimeout(() => { window.location.reload(); }, 600);
      } catch (error) {
        api.toast(error.message || 'Gagal menghapus.', 'error');
        api.setLoading(button, false);
      }
    });
  });

  /* ---------- Quick status ---------- */
  document.querySelectorAll('[data-admin-status]').forEach((button) => {
    button.addEventListener('click', async () => {
      const confirmed = await api.confirmDialog(button.dataset.confirm || 'Ubah status pesanan ini?', {
        title: 'Ubah status',
        confirmText: 'Ya, ubah',
      });
      if (!confirmed) return;
      api.setLoading(button, true, '');
      try {
        const payload = await api.request('admin.php', {
          method: 'POST',
          body: { action: 'booking_status', booking_id: button.dataset.bookingId, status: button.dataset.status },
        });
        api.toast(payload.message || 'Status diperbarui.', 'success');
        window.setTimeout(() => { window.location.reload(); }, 600);
      } catch (error) {
        api.toast(error.message || 'Gagal memperbarui status.', 'error');
        api.setLoading(button, false);
      }
    });
  });

  /* ---------- Batalkan pesanan (admin) ---------- */
  document.querySelectorAll('[data-booking-cancel-admin]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = api.openModal(
        `<form class="space-y-4" data-admin-form="booking-cancel">
          <input type="hidden" name="action" value="booking_status">
          <input type="hidden" name="booking_id" value="${api.escapeHtml(button.dataset.bookingId)}">
          <input type="hidden" name="status" value="cancelled">
          <p class="text-sm leading-relaxed text-forest-700">Batalkan pesanan <strong>${api.escapeHtml(button.dataset.code || '')}</strong>? Stok akan dilepas dan customer menerima notifikasi.</p>
          <div><label class="field-label" for="cancel-reason">Alasan pembatalan</label><textarea id="cancel-reason" class="input min-h-[5rem]" name="note" rows="2" required placeholder="mis. customer tidak menyelesaikan pembayaran."></textarea></div>
          <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" data-close-modal>Kembali</button><button type="submit" class="btn bg-danger text-white hover:bg-red-700">Batalkan pesanan</button></div>
        </form>`,
        { title: 'Batalkan pesanan', maxWidth: '28rem' }
      );
      if (modal) enhanceForm(modal.querySelector('form'));
    });
  });

  /* ---------- Verifikasi pembayaran ---------- */
  document.querySelectorAll('[data-payment-verify]').forEach((button) => {
    button.addEventListener('click', async () => {
      const approve = button.dataset.approve === '1';
      if (approve) {
        const confirmed = await api.confirmDialog('Verifikasi pembayaran ini? Pesanan akan otomatis dikonfirmasi dan customer mendapat notifikasi.', {
          title: 'Verifikasi pembayaran',
          confirmText: 'Ya, verifikasi',
        });
        if (!confirmed) return;
        api.setLoading(button, true, 'Memverifikasi...');
        try {
          const payload = await api.request('admin.php', {
            method: 'POST',
            body: { action: 'payment_verify', payment_id: button.dataset.paymentId, approve: 1 },
          });
          api.toast(payload.message || 'Pembayaran diverifikasi.', 'success');
          window.setTimeout(() => { window.location.reload(); }, 650);
        } catch (error) {
          api.toast(error.message || 'Gagal memverifikasi.', 'error');
          api.setLoading(button, false);
        }
        return;
      }

      const modal = api.openModal(
        `<form class="space-y-4" data-admin-form="payment-reject">
          <input type="hidden" name="action" value="payment_verify">
          <input type="hidden" name="payment_id" value="${api.escapeHtml(button.dataset.paymentId)}">
          <input type="hidden" name="approve" value="0">
          <p class="text-sm leading-relaxed text-forest-700">Tolak bukti pembayaran ini? Customer dapat mengunggah bukti baru.</p>
          <div><label class="field-label" for="reject-note">Alasan penolakan</label><textarea id="reject-note" class="input min-h-[5rem]" name="note" rows="2" required placeholder="mis. nominal tidak sesuai atau bukti tidak terbaca."></textarea></div>
          <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" data-close-modal>Kembali</button><button type="submit" class="btn bg-danger text-white hover:bg-red-700">Tolak pembayaran</button></div>
        </form>`,
        { title: 'Tolak pembayaran', maxWidth: '28rem' }
      );
      if (modal) enhanceForm(modal.querySelector('form'));
    });
  });

  /* ---------- Toko cabang ---------- */
  document.querySelectorAll('[data-store-toggle]').forEach((button) => {
    button.addEventListener('click', async () => {
      const active = Number(button.dataset.active) === 1;
      const confirmed = await api.confirmDialog(
        active ? 'Nonaktifkan toko ini? Toko tidak muncul di checkout dan halaman cabang.' : 'Aktifkan kembali toko ini?',
        { title: active ? 'Nonaktifkan toko' : 'Aktifkan toko', confirmText: active ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: active }
      );
      if (!confirmed) return;
      api.setLoading(button, true, '');
      try {
        const payload = await api.request('admin.php', { method: 'POST', body: { action: 'store_toggle', id: button.dataset.id } });
        api.toast(payload.message || 'Status toko diperbarui.', 'success');
        window.setTimeout(() => { window.location.reload(); }, 600);
      } catch (error) {
        api.toast(error.message || 'Gagal memperbarui toko.', 'error');
        api.setLoading(button, false);
      }
    });
  });

  /* ---------- Pengguna ---------- */
  document.querySelectorAll('[data-user-toggle]').forEach((button) => {
    button.addEventListener('click', async () => {
      const active = Number(button.dataset.active) === 1;
      const confirmed = await api.confirmDialog(active ? 'Nonaktifkan akun ini? Pengguna tidak bisa masuk sampai diaktifkan kembali.' : 'Aktifkan kembali akun ini?', {
        title: active ? 'Nonaktifkan akun' : 'Aktifkan akun',
        confirmText: active ? 'Ya, nonaktifkan' : 'Ya, aktifkan',
        danger: active,
      });
      if (!confirmed) return;
      api.setLoading(button, true, '');
      try {
        const payload = await api.request('admin.php', { method: 'POST', body: { action: 'user_toggle', id: button.dataset.id } });
        api.toast(payload.message || 'Status akun diperbarui.', 'success');
        window.setTimeout(() => { window.location.reload(); }, 600);
      } catch (error) {
        api.toast(error.message || 'Gagal memperbarui akun.', 'error');
        api.setLoading(button, false);
      }
    });
  });

  document.querySelectorAll('[data-user-reset]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = api.openModal(
        `<form class="space-y-4" data-admin-form="user-reset">
          <input type="hidden" name="action" value="user_reset_password">
          <input type="hidden" name="id" value="${api.escapeHtml(button.dataset.id)}">
          <p class="text-sm leading-relaxed text-forest-700">Setel ulang kata sandi untuk <strong>${api.escapeHtml(button.dataset.name || 'pengguna')}</strong>. Sampaikan kata sandi baru secara aman.</p>
          <div><label class="field-label" for="reset-password">Kata sandi baru</label><input id="reset-password" class="input" type="password" name="password" minlength="8" required autocomplete="new-password"></div>
          <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" data-close-modal>Batal</button><button type="submit" class="btn btn-primary">Setel ulang</button></div>
        </form>`,
        { title: 'Reset kata sandi', maxWidth: '26rem' }
      );
      if (modal) {
        enhanceForm(modal.querySelector('form'));
        modal.querySelector('input[type="password"]')?.focus();
      }
    });
  });

  /* ---------- Auto-open form ---------- */
  window.setTimeout(() => {
    const auto = document.querySelector('[data-auto-open="1"]');
    if (auto) auto.click();
  }, 250);

  /* ---------- Chart.js ---------- */
  function initCharts() {
    const configs = window.RENTIFY_CHARTS;
    if (!configs || !window.Chart) return;
    window.Chart.defaults.font.family = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";
    window.Chart.defaults.color = '#5e7066';
    configs.forEach((config) => {
      const canvas = document.getElementById(config.id);
      if (!canvas) return;
      if (config.id.toLowerCase().includes('revenue')) {
        config.options = config.options || {};
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = {
          callbacks: {
            label: (context) => `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(Number(context.parsed.y || 0)))}`,
          },
        };
      }
      try {
        new window.Chart(canvas, config);
      } catch (error) {
        console.warn('Chart init failed', config.id, error);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
  } else {
    initCharts();
  }
})();
