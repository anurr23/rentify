/* AI Gear Planner: form, loading, render hasil, include/exclude, add to cart. */
(() => {
  'use strict';

  const root = document.querySelector('[data-ai-root]');
  if (!root) return;
  const api = window.RENTIFY;
  if (!api) return;

  const form = root.querySelector('[data-ai-form]');
  const idle = root.querySelector('[data-ai-idle]');
  const loading = root.querySelector('[data-ai-loading]');
  const loadingText = root.querySelector('[data-ai-loading-text]');
  const errorBox = root.querySelector('[data-ai-error]');
  const errorText = root.querySelector('[data-ai-error-text]');
  const results = root.querySelector('[data-ai-results]');
  const itemsEl = root.querySelector('[data-ai-items]');
  const emptyEl = root.querySelector('[data-ai-empty]');
  const destinationSelect = root.querySelector('[data-ai-destination]');
  const destinationText = root.querySelector('[data-ai-destination-text]');
  const tipsEl = root.querySelector('[data-ai-tips]');
  const tipsWrap = root.querySelector('[data-ai-tips-wrap]');
  const sourceEl = root.querySelector('[data-ai-source]');
  const metaEl = root.querySelector('[data-ai-meta]');
  const titleEl = root.querySelector('[data-ai-title]');
  const summaryEl = root.querySelector('[data-ai-summary]');
  const noticeEl = root.querySelector('[data-ai-notice]');
  const subtotalEl = root.querySelector('[data-ai-subtotal]');
  const depositEl = root.querySelector('[data-ai-deposit]');
  const totalEl = root.querySelector('[data-ai-total]');
  const selectedCountEl = root.querySelector('[data-ai-selected-count]');
  const addButtons = root.querySelectorAll('[data-ai-add]');
  const filterButtons = root.querySelectorAll('[data-ai-filter]');

  const state = {
    result: null,
    selected: new Map(),
    filter: 'all',
    busy: false,
  };

  let loadingTimer = null;
  const loadingMessages = [
    'Membaca destinasi & durasi perjalanan',
    'Mencocokkan kebutuhan dengan katalog Rentify',
    'Memprioritaskan gear wajib & opsional',
    'Mengecek ketersediaan stok',
  ];

  function formatDate(value) {
    if (!value) return '-';
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
  }

  function addDays(value, days) {
    const date = new Date(`${value}T00:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);
    return date.toISOString().slice(0, 10);
  }

  function setStepper(container, value) {
    const min = Number(container.dataset.min || 1);
    const max = Number(container.dataset.max || 99);
    const input = container.querySelector('[data-step-input]');
    const display = container.querySelector('[data-step-value]');
    const next = Math.min(max, Math.max(min, Number(value) || min));
    if (input) input.value = String(next);
    if (display) display.textContent = String(next);
    const dec = container.querySelector('[data-step-dec]');
    const inc = container.querySelector('[data-step-inc]');
    if (dec) dec.disabled = next <= min;
    if (inc) inc.disabled = next >= max;
    return next;
  }

  root.querySelectorAll('[data-ai-days], [data-ai-participants]').forEach((container) => {
    setStepper(container, container.querySelector('[data-step-input]')?.value || 1);
    container.querySelector('[data-step-dec]')?.addEventListener('click', () => {
      setStepper(container, Number(container.querySelector('[data-step-input]')?.value || 1) - 1);
    });
    container.querySelector('[data-step-inc]')?.addEventListener('click', () => {
      setStepper(container, Number(container.querySelector('[data-step-input]')?.value || 1) + 1);
    });
  });

  destinationSelect?.addEventListener('change', () => {
    const manual = destinationSelect.value === '0';
    destinationText?.classList.toggle('hidden', !manual);
    if (manual) destinationText?.focus();
  });
  if (destinationSelect?.value === '0') destinationText?.classList.remove('hidden');

  function showPane(pane) {
    [idle, loading, errorBox, results].forEach((el) => {
      if (!el) return;
      el.classList.toggle('hidden', el !== pane);
    });
  }

  function startLoading() {
    showPane(loading);
    let index = 0;
    if (loadingText) loadingText.textContent = loadingMessages[0];
    window.clearInterval(loadingTimer);
    loadingTimer = window.setInterval(() => {
      index = (index + 1) % loadingMessages.length;
      if (loadingText) loadingText.textContent = loadingMessages[index];
    }, 1700);
  }

  function stopLoading() {
    window.clearInterval(loadingTimer);
    loadingTimer = null;
  }

  function sourceLabel(source) {
    if (source === 'fallback') return { label: 'Aturan Rentify', icon: 'shield' };
    if (source === 'cache') return { label: 'Cache AI', icon: 'bolt' };
    return { label: 'AI 9router', icon: 'sparkles' };
  }

  function render() {
    const result = state.result;
    if (!result) return;
    showPane(results);

    const source = sourceLabel(result.source);
    if (sourceEl) {
      sourceEl.innerHTML = `<svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2M12 19v2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M3 12h2M19 12h2M5.6 18.4 7 17M17 7l1.4-1.4"/></svg>${api.escapeHtml(source.label)}`;
    }
    if (metaEl) {
      metaEl.textContent = `${result.activity?.name || 'Camping'} · ${result.destination_name || 'Karawang'} · ${result.duration_days} hari · ${result.participants} orang`;
    }
    if (titleEl) {
      titleEl.textContent = `Gear ${result.activity?.name || 'outdoor'} untuk ${result.destination_name || 'Karawang'}`;
    }
    if (summaryEl) summaryEl.textContent = result.summary || '';
    if (noticeEl) {
      noticeEl.textContent = result.notice || '';
      noticeEl.classList.toggle('hidden', !result.notice);
    }
    if (tipsEl) {
      tipsEl.innerHTML = (result.tips || []).map((tip) => `<li class="flex items-start gap-2"><span class="mt-0.5 shrink-0 text-forest-500">${iconCheck()}</span><span>${api.escapeHtml(tip)}</span></li>`).join('');
      tipsWrap?.classList.toggle('hidden', !(result.tips || []).length);
    }

    state.selected.clear();
    (result.items || []).forEach((item) => {
      state.selected.set(Number(item.item_id), { ...item, qty: Number(item.qty) || 1, included: true });
    });

    renderItems();
    updateTotals();
  }

  function iconCheck() {
    return '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"/></svg>';
  }

  function iconPlus() {
    return '<svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>';
  }

  function iconMinus() {
    return '<svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg>';
  }

  function renderItems() {
    if (!itemsEl || !state.result) return;
    const items = [...state.selected.values()];
    itemsEl.innerHTML = items.map((item) => {
      const included = item.included;
      const priorityClass = item.priority === 'wajib' ? 'badge-mint' : 'badge-neutral';
      const features = (item.features || []).slice(0, 3).map((feature) => `<span class="feature-tag">${api.escapeHtml(feature)}</span>`).join('');
      return `<article class="rounded-2xl border ${included ? 'border-line bg-white' : 'border-dashed border-line bg-cream-50 opacity-65'} p-3 transition" data-ai-item data-item-id="${Number(item.item_id)}" data-priority="${api.escapeHtml(item.priority)}">
        <div class="flex gap-3">
          <button type="button" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border ${included ? 'border-forest-900 bg-forest-900 text-white' : 'border-line bg-white text-transparent'} transition" data-ai-toggle aria-label="${included ? 'Keluarkan dari pilihan' : 'Masukkan ke pilihan'}" aria-pressed="${included ? 'true' : 'false'}">${iconCheck()}</button>
          <a href="${api.baseUrl(item.detail_url || `detail.php?slug=${item.slug}`)}" class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-cream-100">
            <img src="${api.escapeHtml(item.image ? api.baseUrl(item.image) : api.baseUrl('assets/img/placeholder.svg'))}" alt="${api.escapeHtml(item.name)}" class="h-full w-full object-cover" loading="lazy">
          </a>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-1.5">
              <div class="min-w-0">
                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-forest-500">${api.escapeHtml(item.category || '')}</p>
                <a href="${api.baseUrl(item.detail_url || `detail.php?slug=${item.slug}`)}" class="mt-0.5 block text-sm font-extrabold leading-snug text-forest-900 hover:text-forest-600">${api.escapeHtml(item.name)}</a>
              </div>
              <span class="badge ${priorityClass}">${item.priority === 'wajib' ? 'Wajib' : 'Opsional'}</span>
            </div>
            <p class="mt-1 line-clamp-2 text-[0.68rem] leading-relaxed text-muted">${api.escapeHtml(item.reason || '')}</p>
          </div>
        </div>
        <div class="mt-2.5 flex flex-wrap items-end justify-between gap-2 border-t border-line/70 pt-2.5 pl-8">
          <div class="flex items-center gap-2">
            <button type="button" class="text-[0.62rem] font-bold text-forest-600 underline underline-offset-2" data-ai-toggle>${included ? 'Keluarkan' : 'Masukkan'}</button>
            ${features ? `<span class="hidden gap-1 sm:flex">${features}</span>` : ''}
          </div>
          <div class="flex items-center gap-3">
            <div class="stepper !h-8" data-ai-qty data-item-id="${Number(item.item_id)}">
              <button type="button" data-ai-dec aria-label="Kurangi jumlah" ${!included || item.qty <= 1 ? 'disabled' : ''}>${iconMinus()}</button>
              <span data-ai-qty-value>${item.qty}</span>
              <button type="button" data-ai-inc aria-label="Tambah jumlah" ${!included || item.qty >= Math.min(6, Number(item.stock || 6)) ? 'disabled' : ''}>${iconPlus()}</button>
            </div>
            <div class="text-right">
              <p class="text-sm font-extrabold text-forest-900">${api.formatRupiah((Number(item.price_per_day) || 0) * item.qty * (Number(state.result.duration_days) || 1))}</p>
              <p class="text-[0.58rem] text-muted">${item.qty} × ${state.result.duration_days} hari</p>
            </div>
          </div>
        </div>
      </article>`;
    }).join('');
    applyFilter();
  }

  function applyFilter() {
    if (!itemsEl) return;
    let visible = 0;
    itemsEl.querySelectorAll('[data-ai-item]').forEach((el) => {
      const show = state.filter === 'all' || el.dataset.priority === state.filter;
      el.classList.toggle('hidden', !show);
      if (show) visible += 1;
    });
    emptyEl?.classList.toggle('hidden', visible > 0);
  }

  function updateTotals() {
    let subtotal = 0;
    let deposit = 0;
    let count = 0;
    state.selected.forEach((item) => {
      if (!item.included) return;
      count += 1;
      subtotal += (Number(item.price_per_day) || 0) * item.qty * (Number(state.result?.duration_days) || 1);
      deposit += Number(item.deposit) || 0;
    });
    if (subtotalEl) subtotalEl.textContent = api.formatRupiah(subtotal);
    if (depositEl) depositEl.textContent = api.formatRupiah(deposit);
    if (totalEl) totalEl.textContent = api.formatRupiah(subtotal + deposit);
    if (selectedCountEl) selectedCountEl.textContent = `${count} dipilih`;
    addButtons.forEach((button) => { button.disabled = state.busy || count === 0; });
  }

  itemsEl?.addEventListener('click', (event) => {
    const card = event.target.closest('[data-ai-item]');
    if (!card) return;
    const id = Number(card.dataset.itemId);
    const item = state.selected.get(id);
    if (!item) return;

    if (event.target.closest('[data-ai-toggle]')) {
      item.included = !item.included;
      card.classList.toggle('border-line', item.included);
      card.classList.toggle('bg-white', item.included);
      card.classList.toggle('border-dashed', !item.included);
      card.classList.toggle('bg-cream-50', !item.included);
      card.classList.toggle('opacity-65', !item.included);
      renderItems();
      updateTotals();
      return;
    }

    if (event.target.closest('[data-ai-dec]')) {
      if (!item.included) return;
      item.qty = Math.max(1, item.qty - 1);
      renderItems();
      updateTotals();
      return;
    }

    if (event.target.closest('[data-ai-inc]')) {
      if (!item.included) return;
      const max = Math.max(1, Math.min(6, Number(item.stock) || 6));
      if (item.qty >= max) {
        api.toast('Jumlah sudah mencapai stok tersedia.', 'warning');
        return;
      }
      item.qty += 1;
      renderItems();
      updateTotals();
    }
  });

  filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      state.filter = button.dataset.aiFilter || 'all';
      filterButtons.forEach((el) => {
        const active = el === button;
        el.classList.toggle('bg-forest-900', active);
        el.classList.toggle('text-white', active);
        el.classList.toggle('text-muted', !active);
      });
      applyFilter();
    });
  });

  async function submit() {
    if (state.busy) return;
    const formData = new FormData(form);
    const activityId = Number(formData.get('activity_id') || 0);
    const destinationId = Number(formData.get('destination_id') || 0);
    const manualDestination = String(formData.get('destination') || '').trim();
    const startDate = String(formData.get('start_date') || '');
    const days = Number(formData.get('days') || 2);
    const participants = Number(formData.get('participants') || 2);
    const notes = String(formData.get('notes') || '').trim();

    if (!activityId) {
      api.toast('Pilih aktivitas terlebih dahulu.', 'warning');
      return;
    }
    if (destinationId === 0 && manualDestination === '') {
      api.toast('Tulis destinasi perjalananmu.', 'warning');
      destinationText?.focus();
      return;
    }
    if (!startDate) {
      api.toast('Pilih tanggal mulai.', 'warning');
      return;
    }

    state.busy = true;
    startLoading();
    results?.classList.add('hidden');
    try {
      const payload = await api.request('ai_recommend.php', {
        method: 'POST',
        body: {
          activity_id: activityId,
          destination_id: destinationId,
          destination: manualDestination,
          start_date: startDate,
          days,
          participants,
          notes,
        },
      });
      state.result = payload;
      render();
      results?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
      if (errorText) errorText.textContent = error.message || 'Gagal membuat rekomendasi.';
      showPane(errorBox);
      errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } finally {
      state.busy = false;
      stopLoading();
      updateTotals();
    }
  }

  form?.addEventListener('submit', (event) => {
    event.preventDefault();
    submit();
  });

  root.querySelector('[data-ai-retry]')?.addEventListener('click', submit);

  addButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      if (state.busy || !state.result) return;
      const selected = [...state.selected.values()].filter((item) => item.included);
      if (!selected.length) {
        api.toast('Pilih minimal satu gear.', 'warning');
        return;
      }
      const start = state.result.start_date || String(new FormData(form).get('start_date') || tomorrow());
      const days = Number(state.result.duration_days) || 1;
      const end = state.result.end_date || addDays(start, days - 1);
      const mode = button.dataset.aiAdd || 'cart';

      state.busy = true;
      addButtons.forEach((el) => api.setLoading(el, true, 'Menambahkan...'));
      try {
        const payload = await api.request('cart.php', {
          method: 'POST',
          body: {
            action: 'add_many',
            items: selected.map((item) => ({
              item_id: item.item_id,
              variant_id: item.variant_id,
              qty: item.qty,
            })),
            start_date: start,
            end_date: end,
          },
        });
        api.refreshCartBadge(Number(payload.cart_count || 0));
        const extra = payload.add_errors?.length ? ` ${payload.add_errors.length} item dilewati karena stok.` : '';
        api.toast((payload.message || 'Item ditambahkan ke keranjang.') + extra, 'success', 4200);
        if (mode === 'checkout') {
          window.setTimeout(() => { window.location.href = api.baseUrl('keranjang.php'); }, 650);
        }
      } catch (error) {
        api.toast(error.message || 'Gagal menambahkan ke keranjang.', 'error');
      } finally {
        state.busy = false;
        addButtons.forEach((el) => api.setLoading(el, false));
        updateTotals();
      }
    });
  });

  function tomorrow() {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    return date.toISOString().slice(0, 10);
  }
})();
