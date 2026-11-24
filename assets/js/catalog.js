/* Katalog AJAX: pencarian live, filter, urutan, tampilan, dan muat lebih banyak. */
(() => {
  'use strict';

  const root = document.querySelector('[data-catalog-root]');
  if (!root) return;

  const api = window.RENTIFY;
  const results = root.querySelector('[data-catalog-results]');
  const filterCard = document.querySelector('[data-filter-card]');
  const category = root.dataset.category || '';
  const initial = new URLSearchParams(window.location.search);
  const hint = document.querySelector('[data-catalog-search-hint]');
  const searchInput = document.querySelector('[data-catalog-search]');
  const clearButtons = document.querySelectorAll('[data-catalog-search-clear]');
  const searchForms = document.querySelectorAll('[data-catalog-search-form]');

  const state = {
    q: initial.get('q') || '',
    sort: initial.get('sort') || 'nearest',
    page: Number(initial.get('page') || 1) || 1,
    view: initial.get('view') === 'list'
      ? 'list'
      : (initial.get('view') === 'grid' ? 'grid' : (window.localStorage.getItem('rentify-catalog-view') || 'grid')),
    store: initial.get('store') || root.dataset.store || '',
    filters: {},
  };

  initial.forEach((value, key) => {
    const match = key.match(/^f\[([a-z0-9_]+)\]$/i);
    if (match && value) state.filters[match[1]] = value;
  });

  let controller = null;
  let loadingMore = false;
  let searchTimer = null;

  function buildQuery(page = 1, includePage = true) {
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    if (state.q) params.set('q', state.q);
    if (state.sort !== 'popular') params.set('sort', state.sort);
    if (state.store) params.set('store', state.store);
    if (state.view === 'list') params.set('view', 'list');
    if (includePage && page > 1) params.set('page', String(page));
    Object.entries(state.filters).forEach(([key, value]) => {
      if (value) params.set(`f[${key}]`, value);
    });
    return params;
  }

  function syncUrl() {
    const params = buildQuery(1, false);
    const next = `${window.location.pathname}${params.toString() ? `?${params}` : ''}`;
    window.history.replaceState({}, '', next);
  }

  function updateClearButtons() {
    const hasValue = Boolean((searchInput?.value || state.q || '').trim());
    clearButtons.forEach((button) => {
      button.classList.toggle('hidden', !hasValue);
    });
  }

  function updateHint(total = null, loading = false) {
    if (total !== null && !loading) {
      document.querySelectorAll('[data-catalog-total-hero]').forEach((el) => {
        el.textContent = String(total);
      });
    }
    if (!hint) return;
    if (loading) {
      hint.textContent = state.q ? `Mencari “${state.q}”...` : 'Memuat katalog...';
      return;
    }
    if (state.q) {
      hint.textContent = total === null
        ? `Hasil pencarian untuk “${state.q}”`
        : `${total} hasil untuk “${state.q}”`;
      return;
    }
    hint.textContent = total === null
      ? 'Ketik untuk mencari secara langsung'
      : `Menampilkan ${total} gear`;
  }

  function setBusy(busy) {
    if (!results) return;
    results.style.opacity = busy ? '0.55' : '';
    results.style.pointerEvents = busy ? 'none' : '';
    results.setAttribute('aria-busy', busy ? 'true' : 'false');
    const total = results.querySelector('[data-catalog-total]');
    if (total) total.classList.toggle('animate-pulse', busy);
  }

  function setActiveChips() {
    document.querySelectorAll('[data-filter-key]').forEach((chip) => {
      const key = chip.dataset.filterKey;
      const value = chip.dataset.filterValue || '';
      const isActive = value === '' ? !state.filters[key] : state.filters[key] === value;
      chip.classList.toggle('chip-active', value === '' && isActive);
      chip.classList.toggle('chip-mint', value !== '' && isActive);
      chip.classList.toggle('font-extrabold', value !== '' && isActive);
      if (value === '') {
        const icon = chip.querySelector('svg');
        if (icon) icon.classList.toggle('hidden', !isActive);
      }
    });
  }

  function setSortValue() {
    const sort = document.querySelector('[data-catalog-sort]');
    if (sort) sort.value = state.sort;
  }

  function emptyState() {
    if (!results) return;
    results.innerHTML = '<div class="rounded-[1.35rem] border border-dashed border-line bg-white px-6 py-14 text-center"><p class="text-sm font-bold text-forest-900">Gagal memuat katalog.</p><p class="mt-1 text-xs text-muted">Periksa koneksi lalu coba lagi.</p></div>';
  }

  function updateTotal(payload) {
    const total = results?.querySelector('[data-catalog-total]');
    if (total && payload.total !== undefined) total.textContent = String(payload.total);
    const shown = results?.querySelector('[data-catalog-shown]');
    if (shown) {
      shown.textContent = String(results.querySelectorAll('[data-catalog-grid] [data-item-card]').length);
    }
  }

  async function load({ page = 1, append = false } = {}) {
    if (!results) return;
    if (loadingMore && append) return;
    if (controller) controller.abort();
    controller = new AbortController();
    if (append) {
      loadingMore = true;
      const button = results.querySelector('[data-load-more]');
      if (button) {
        button.disabled = true;
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = 'Memuat...';
      }
    } else {
      state.page = page;
      setBusy(true);
      updateHint(null, true);
    }

    try {
      const params = buildQuery(page);
      const response = await fetch(`${api.apiUrl('items.php')}?${params}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: controller.signal,
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'Gagal memuat katalog.');

      state.view = payload.view || state.view;
      state.sort = payload.sort || state.sort;
      setSortValue();

      if (append) {
        const template = document.createElement('div');
        template.innerHTML = payload.html;
        const freshGrid = template.querySelector('[data-catalog-grid]');
        const currentGrid = results.querySelector('[data-catalog-grid]');
        if (freshGrid && currentGrid) {
          Array.from(freshGrid.children).forEach((child) => {
            if (child.matches('[data-item-card]')) currentGrid.appendChild(child);
          });
        }
        const freshPagination = template.querySelector('[data-catalog-pagination]');
        const currentPagination = results.querySelector('[data-catalog-pagination]');
        if (freshPagination && currentPagination) currentPagination.replaceWith(freshPagination);
        else if (!freshPagination) currentPagination?.remove();
        state.page = Number(payload.page) || state.page;
        updateTotal(payload);
        syncUrl();
        updateHint(payload.total, false);
      } else {
        results.innerHTML = payload.html;
        state.page = page;
        setActiveChips();
        updateTotal(payload);
        syncUrl();
        updateHint(payload.total, false);
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        api.toast(error.message || 'Katalog gagal dimuat.', 'error');
        if (!append) emptyState();
      }
    } finally {
      if (append) {
        loadingMore = false;
        const button = results.querySelector('[data-load-more]');
        if (button && button.dataset.originalHtml) {
          button.disabled = false;
          button.innerHTML = button.dataset.originalHtml;
        }
      } else {
        setBusy(false);
      }
    }
  }

  function runSearch(value) {
    const next = value.trim();
    if (next === state.q) {
      updateHint(Number(document.querySelector('[data-catalog-total]')?.textContent || 0), false);
      return;
    }
    state.q = next;
    updateClearButtons();
    load({ page: 1 });
  }

  searchForms.forEach((form) => {
    const input = form.querySelector('[data-catalog-search]');
    if (!input) return;
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      window.clearTimeout(searchTimer);
      runSearch(input.value);
    });
    input.addEventListener('input', () => {
      updateClearButtons();
      updateHint(null, true);
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(() => runSearch(input.value), 280);
    });
  });

  clearButtons.forEach((button) => {
    button.addEventListener('click', () => {
      window.clearTimeout(searchTimer);
      if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
      }
      state.q = '';
      updateClearButtons();
      load({ page: 1 });
    });
  });

  document.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-filter-key]');
    if (chip && (!filterCard || filterCard.contains(chip))) {
      event.preventDefault();
      const key = chip.dataset.filterKey;
      const value = chip.dataset.filterValue || '';
      if (value === '') delete state.filters[key];
      else state.filters[key] = value;
      setActiveChips();
      load({ page: 1 });
      return;
    }

    const resetKey = event.target.closest('[data-filter-reset-key]');
    if (resetKey && (!filterCard || filterCard.contains(resetKey))) {
      event.preventDefault();
      delete state.filters[resetKey.dataset.filterResetKey];
      setActiveChips();
      load({ page: 1 });
      return;
    }

    const resetAll = event.target.closest('[data-reset-filters]');
    if (resetAll) {
      event.preventDefault();
      state.filters = {};
      state.q = '';
      state.sort = 'popular';
      state.page = 1;
      window.clearTimeout(searchTimer);
      document.querySelectorAll('[data-catalog-search]').forEach((input) => { input.value = ''; });
      updateClearButtons();
      setActiveChips();
      setSortValue();
      load({ page: 1 });
      return;
    }

    const viewButton = event.target.closest('[data-catalog-view]');
    if (viewButton && results.contains(viewButton)) {
      event.preventDefault();
      state.view = viewButton.dataset.catalogView === 'list' ? 'list' : 'grid';
      window.localStorage.setItem('rentify-catalog-view', state.view);
      load({ page: 1 });
      return;
    }

    const loadMore = event.target.closest('[data-load-more]');
    if (loadMore && results.contains(loadMore)) {
      event.preventDefault();
      load({ page: Number(loadMore.dataset.nextPage) || state.page + 1, append: true });
    }
  });

  document.addEventListener('change', (event) => {
    if (event.target.matches('[data-catalog-sort]')) {
      state.sort = event.target.value;
      load({ page: 1 });
    }
    if (event.target.matches('[data-catalog-store]')) {
      state.store = event.target.value;
      load({ page: 1 });
    }
  });

  const storeSelect = document.querySelector('[data-catalog-store]');
  if (storeSelect) storeSelect.value = state.store;

  setActiveChips();
  setSortValue();
  updateClearButtons();
  updateHint(Number(document.querySelector('[data-catalog-total]')?.textContent || 0), false);

  const initialGrid = results?.querySelector('[data-catalog-grid]');
  if (initialGrid && initialGrid.dataset.view !== state.view) {
    load({ page: 1 });
  }
})();
