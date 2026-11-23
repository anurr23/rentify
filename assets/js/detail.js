/* Detail gear: galeri, varian, jadwal, ketersediaan, dan add-to-cart AJAX. */
(() => {
  'use strict';

  const root = document.querySelector('[data-detail-root]');
  if (!root) return;
  const api = window.RENTIFY;
  if (!api) return;

  const startInput = root.querySelector('[data-detail-start]');
  const endInput = root.querySelector('[data-detail-end]');
  const qtyEl = root.querySelector('[data-detail-qty]');
  const stepper = root.querySelector('[data-detail-stepper]');
  const badge = root.querySelector('[data-availability-badge]');
  const errorEl = root.querySelector('[data-detail-error]');
  const daysEls = root.querySelectorAll('[data-detail-days]');
  const subtotalEls = root.querySelectorAll('[data-detail-subtotal], [data-detail-subtotal-mobile]');
  const priceEl = root.querySelector('[data-detail-price]');
  const addButtons = root.querySelectorAll('[data-detail-add]');
  const picker = root.querySelector('[data-variant-picker]');

  const selectedChip = picker?.querySelector('.chip-active');
  const state = {
    itemId: Number(root.dataset.itemId || 0),
    storeId: Number(root.dataset.storeId || 0),
    storeItemId: Number(root.dataset.storeItemId || 0),
    variantId: selectedChip ? Number(selectedChip.dataset.variantId || 0) : 0,
    price: Number(selectedChip?.dataset.price || root.dataset.basePrice || 0),
    qty: 1,
    available: null,
    checking: false,
    seq: 0,
  };

  const minDays = Math.max(1, Number(root.dataset.minDays || 1));
  const maxDays = Math.max(1, Number(root.dataset.maxDays || 30));

  function rupiah(value) {
    return api.formatRupiah(value);
  }

  function parseDate(value) {
    const [year, month, day] = String(value).split('-').map(Number);
    if (!year || !month || !day) return null;
    return new Date(Date.UTC(year, month - 1, day));
  }

  function dayCount() {
    const start = parseDate(startInput?.value || '');
    const end = parseDate(endInput?.value || '');
    if (!start || !end) return 0;
    return Math.round((end - start) / 86400000) + 1;
  }

  function setError(message = '') {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.classList.toggle('hidden', message === '');
  }

  function setBadge(text, tone = 'ok') {
    if (!badge) return;
    const tones = {
      ok: 'bg-mint-100 text-forest-700',
      warn: 'bg-amber-50 text-amber-700',
      bad: 'bg-red-50 text-red-700',
      check: 'bg-cream-100 text-muted',
    };
    badge.className = `inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[0.62rem] font-extrabold ${tones[tone] || tones.ok}`;
    badge.innerHTML = `<span class="h-1.5 w-1.5 rounded-full bg-current"></span>${api.escapeHtml(text)}`;
  }

  function updateSummary() {
    const days = dayCount();
    daysEls.forEach((el) => { el.textContent = String(days || 0); });
    const subtotal = state.price * state.qty * Math.max(1, days);
    subtotalEls.forEach((el) => { el.textContent = rupiah(subtotal); });
    if (priceEl) priceEl.innerHTML = `${rupiah(state.price)}<span class="text-xs font-semibold text-muted">/hari</span>`;
    if (qtyEl) qtyEl.textContent = String(state.qty);
    const dec = stepper?.querySelector('[data-detail-dec]');
    const inc = stepper?.querySelector('[data-detail-inc]');
    if (dec) dec.disabled = state.qty <= 1;
    if (inc) inc.disabled = state.available !== null && state.qty >= state.available;
    return subtotal;
  }

  async function refreshAvailability() {
    if (!startInput?.value || !endInput?.value) return;
    const days = dayCount();
    if (days < minDays) {
      setError(`Minimal sewa ${minDays} hari.`);
      setBadge('Durasi kurang', 'bad');
      return;
    }
    if (days > maxDays) {
      setError(`Maksimal sewa ${maxDays} hari.`);
      setBadge('Durasi terlalu lama', 'bad');
      return;
    }
    setError('');
    const seq = ++state.seq;
    state.checking = true;
    setBadge('Mengecek stok...', 'check');
    try {
      const params = new URLSearchParams({
        item_id: String(state.itemId),
        store_id: String(state.storeId),
        store_item_id: String(state.storeItemId),
        start_date: startInput.value,
        end_date: endInput.value,
        qty: String(state.qty),
      });
      if (state.variantId) params.set('variant_id', String(state.variantId));
      const payload = await api.request(`availability.php?${params}`);
      if (seq !== state.seq) return;
      state.available = Number(payload.available);
      state.price = Number(payload.price_per_day) || state.price;
      if (state.available <= 0) {
        setBadge('Stok habis', 'bad');
        setError('Stok habis untuk rentang tanggal ini. Coba tanggal lain.');
      } else {
        if (state.qty > state.available) {
          state.qty = Math.max(1, state.available);
          setError(`Jumlah disesuaikan dengan stok tersedia (${state.available} unit).`);
        } else {
          setError('');
        }
        setBadge(state.available <= 3 ? `Sisa ${state.available} unit` : `${state.available} unit tersedia`, state.available <= 3 ? 'warn' : 'ok');
      }
      updateSummary();
    } catch (error) {
      if (seq !== state.seq) return;
      state.available = null;
      setBadge('Stok tidak diketahui', 'bad');
      setError(error.message || 'Gagal mengecek ketersediaan.');
    } finally {
      if (seq === state.seq) state.checking = false;
    }
  }

  function syncEndMin() {
    if (!startInput || !endInput) return;
    const start = parseDate(startInput.value);
    if (!start) return;
    const minEnd = new Date(start.getTime() + (minDays - 1) * 86400000);
    const minValue = minEnd.toISOString().slice(0, 10);
    endInput.min = minValue;
    if (!endInput.value || endInput.value < minValue) {
      endInput.value = minValue;
    }
    const maxEnd = new Date(start.getTime() + (maxDays - 1) * 86400000);
    endInput.max = maxEnd.toISOString().slice(0, 10);
  }

  picker?.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-variant-id]');
    if (!chip) return;
    event.preventDefault();
    picker.querySelectorAll('[data-variant-id]').forEach((el) => el.classList.remove('chip-active', 'chip-mint', 'font-bold'));
    chip.classList.add('chip-active');
    state.variantId = Number(chip.dataset.variantId || 0);
    state.price = Number(chip.dataset.price || 0);
    state.qty = 1;
    updateSummary();
    refreshAvailability();
  });

  root.querySelector('[data-detail-dec]')?.addEventListener('click', () => {
    state.qty = Math.max(1, state.qty - 1);
    updateSummary();
    refreshAvailability();
  });

  root.querySelector('[data-detail-inc]')?.addEventListener('click', () => {
    const max = state.available === null ? 99 : state.available;
    if (state.qty >= max) {
      api.toast('Jumlah sudah mencapai stok tersedia.', 'warning');
      return;
    }
    state.qty += 1;
    updateSummary();
    refreshAvailability();
  });

  startInput?.addEventListener('change', () => {
    syncEndMin();
    updateSummary();
    refreshAvailability();
  });

  endInput?.addEventListener('change', () => {
    updateSummary();
    refreshAvailability();
  });

  root.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const image = root.querySelector('[data-gallery-image]');
      if (image) image.src = thumb.dataset.src;
      root.querySelectorAll('[data-gallery-thumb]').forEach((el) => {
        el.classList.remove('border-forest-900');
        el.classList.add('border-transparent', 'opacity-70');
      });
      thumb.classList.add('border-forest-900');
      thumb.classList.remove('border-transparent', 'opacity-70');
    });
  });

  addButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      const mode = button.dataset.detailAdd || 'cart';
      if (state.checking) {
        api.toast('Tunggu pengecekan stok selesai.', 'info', 1800);
        return;
      }
      if (state.available !== null && state.available < state.qty) {
        api.toast('Stok tidak mencukupi untuk jumlah ini.', 'error');
        return;
      }
      if (!startInput?.value || !endInput?.value) {
        api.toast('Pilih tanggal sewa terlebih dahulu.', 'warning');
        return;
      }
      if (dayCount() < minDays) {
        api.toast(`Minimal sewa ${minDays} hari.`, 'warning');
        return;
      }

      addButtons.forEach((el) => api.setLoading(el, true, 'Menambahkan...'));
      const body = {
        action: 'add',
        item_id: state.itemId,
        store_id: state.storeId,
        store_item_id: state.storeItemId,
        variant_id: state.variantId,
        quantity: state.qty,
        start_date: startInput.value,
        end_date: endInput.value,
      };
      try {
        let payload;
        try {
          payload = await api.request('cart.php', { method: 'POST', body });
        } catch (error) {
          const conflict = error.payload?.data?.conflict;
          if (!conflict) throw error;
          const current = error.payload.data.current_store?.name || 'cabang lain';
          const next = error.payload.data.new_store?.name || 'cabang ini';
          const confirmed = await api.confirmDialog(
            `Keranjang berisi gear dari ${current}. Ganti ke ${next} dan kosongkan keranjang sebelumnya?`,
            { title: 'Ganti cabang', confirmText: 'Ya, ganti cabang', danger: true }
          );
          if (!confirmed) throw error;
          await api.request('cart.php', { method: 'POST', body: { action: 'clear' } });
          payload = await api.request('cart.php', { method: 'POST', body });
        }
        api.refreshCartBadge(Number(payload.cart_count || 0));
        api.toast(payload.message || 'Ditambahkan ke keranjang.', 'success');
        if (mode === 'checkout') {
          window.setTimeout(() => { window.location.href = api.baseUrl('keranjang.php'); }, 500);
        }
      } catch (error) {
        api.toast(error.message || 'Gagal menambahkan ke keranjang.', 'error');
      } finally {
        addButtons.forEach((el) => api.setLoading(el, false));
      }
    });
  });

  syncEndMin();
  updateSummary();
  refreshAvailability();
})();
