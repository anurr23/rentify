/* Keranjang AJAX: tanggal, jumlah, hapus, kosongkan, dan add-on. */
(() => {
  'use strict';

  const root = document.querySelector('[data-cart-root]');
  if (!root) return;
  const api = window.RENTIFY;
  if (!api) return;

  const lines = root.querySelector('[data-cart-lines]');
  const summary = root.querySelector('[data-cart-summary]');
  const countEl = root.querySelector('[data-cart-total-count]');
  const durationEl = root.querySelector('[data-cart-duration]');
  const checkout = root.querySelector('[data-cart-checkout]');
  const hint = root.querySelector('[data-cart-checkout-hint]');
  const dateForm = root.querySelector('[data-cart-dates-form]');
  const startInput = dateForm?.querySelector('[name="start_date"]');
  const endInput = dateForm?.querySelector('[name="end_date"]');
  const clearButton = root.querySelector('[data-cart-clear]');
  let busy = false;

  function applyState(payload) {
    if (lines && typeof payload.html === 'string') lines.innerHTML = payload.html;
    if (summary && typeof payload.summary === 'string') summary.innerHTML = payload.summary;
    if (countEl && typeof payload.count === 'number') countEl.textContent = String(payload.count);
    if (durationEl) {
      durationEl.textContent = payload.duration ? `${payload.duration} hari` : 'Belum diatur';
    }
    if (payload.dates) {
      if (startInput && payload.dates.start) startInput.value = payload.dates.start;
      if (endInput && payload.dates.end) endInput.value = payload.dates.end;
      if (startInput && endInput) endInput.min = startInput.value;
    }
    api.refreshCartBadge(Number(payload.count ?? payload.cart_count ?? 0));
    const hasItems = Number(payload.count ?? 0) > 0;
    const hasDates = Boolean(payload.dates?.start && payload.dates?.end);
    checkout?.classList.toggle('pointer-events-none', !hasItems || !hasDates);
    checkout?.classList.toggle('opacity-50', !hasItems || !hasDates);
    hint?.classList.toggle('hidden', hasItems && hasDates);
    if (!hasItems) {
      clearButton?.classList.add('hidden');
    } else {
      clearButton?.classList.remove('hidden');
    }
  }

  async function post(body, button = null) {
    if (busy) return null;
    busy = true;
    if (button) api.setLoading(button, true, 'Menyimpan...');
    try {
      const payload = await api.request('cart.php', { method: 'POST', body });
      applyState(payload);
      if (payload.message) api.toast(payload.message, 'success', 2600);
      return payload;
    } catch (error) {
      api.toast(error.message || 'Gagal memperbarui keranjang.', 'error');
      return null;
    } finally {
      busy = false;
      if (button) api.setLoading(button, false);
    }
  }

  dateForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!startInput?.value || !endInput?.value) {
      api.toast('Lengkapi tanggal mulai dan kembali.', 'warning');
      return;
    }
    if (endInput.value < startInput.value) {
      api.toast('Tanggal kembali tidak boleh sebelum tanggal mulai.', 'warning');
      endInput.value = startInput.value;
      return;
    }
    post({ action: 'dates', start_date: startInput.value, end_date: endInput.value }, dateForm.querySelector('button[type="submit"]'));
  });

  startInput?.addEventListener('change', () => {
    if (!endInput) return;
    endInput.min = startInput.value;
    if (!endInput.value || endInput.value < startInput.value) {
      const start = new Date(`${startInput.value}T00:00:00`);
      start.setDate(start.getDate() + 1);
      endInput.value = start.toISOString().slice(0, 10);
    }
  });

  root.addEventListener('click', async (event) => {
    const remove = event.target.closest('[data-cart-remove]');
    if (remove) {
      event.preventDefault();
      const confirmed = await api.confirmDialog('Hapus gear ini dari keranjang?', {
        title: 'Hapus item',
        confirmText: 'Ya, hapus',
        danger: true,
      });
      if (confirmed) post({ action: 'remove', key: remove.dataset.cartRemove });
      return;
    }

    const dec = event.target.closest('[data-cart-dec]');
    const inc = event.target.closest('[data-cart-inc]');
    if (dec || inc) {
      const stepper = (dec || inc).closest('[data-cart-stepper]');
      if (!stepper || busy) return;
      event.preventDefault();
      const current = Number(stepper.dataset.qty || stepper.querySelector('[data-cart-qty]')?.textContent || 1);
      const next = (dec ? current - 1 : current + 1);
      if (next < 1) return;
      if (!dec && next > Number(stepper.dataset.max || 99)) {
        api.toast('Jumlah sudah mencapai stok tersedia.', 'warning');
        return;
      }
      post({ action: 'update', key: stepper.dataset.key, qty: next });
      return;
    }

    const add = event.target.closest('[data-cart-add]');
    if (add) {
      event.preventDefault();
      if (busy) return;
      const start = startInput?.value || '';
      const end = endInput?.value || '';
      if (!start || !end) {
        api.toast('Atur tanggal sewa terlebih dahulu.', 'warning');
        dateForm?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      busy = true;
      api.setLoading(add, true, '');
      try {
        const payload = await api.request('cart.php', {
          method: 'POST',
          body: {
            action: 'add',
            item_id: Number(add.dataset.cartAdd),
            quantity: 1,
            start_date: start,
            end_date: end,
          },
        });
        applyState(payload);
        api.toast(payload.message || 'Ditambahkan ke keranjang.', 'success', 2600);
      } catch (error) {
        api.toast(error.message || 'Gagal menambahkan item.', 'error');
      } finally {
        busy = false;
        api.setLoading(add, false);
      }
    }
  });

  clearButton?.addEventListener('click', async () => {
    const confirmed = await api.confirmDialog('Kosongkan semua item dari keranjang?', {
      title: 'Kosongkan keranjang',
      confirmText: 'Ya, kosongkan',
      danger: true,
    });
    if (!confirmed) return;
    await post({ action: 'clear' });
  });

  checkout?.addEventListener('click', (event) => {
    if (checkout.classList.contains('pointer-events-none')) {
      event.preventDefault();
      const hasItems = Number(countEl?.textContent || 0) > 0;
      api.toast(hasItems ? 'Atur tanggal sewa terlebih dahulu.' : 'Keranjang masih kosong.', 'warning');
      if (hasItems) dateForm?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
})();
