/* Katalog AJAX: filter, pencarian, urutan, tampilan, dan pagination. */
(() => {
  'use strict';

  const root = document.querySelector('[data-catalog-root]');
  if (!root) return;

  const api = window.RENTIFY;
  const results = root.querySelector('[data-catalog-results]');
  const searchForms = document.querySelectorAll('[data-catalog-search-form]');
  const filterCard = document.querySelector('[data-filter-card]');
  const category = root.dataset.category || '';
  const initial = new URLSearchParams(window.location.search);

  const state = {
    q: initial.get('q') || '',
    sort: initial.get('sort') || 'popular',
    page: Number(initial.get('page') || 1) || 1,
    view: window.localStorage.getItem('rentify-catalog-view') || 'grid',
    filters: {},
  };

  initial.forEach((value, key) => {
    const match = key.match(/^f\[([a-z0-9_]+)\]$/i);
    if (match && value) state.filters[match[1]] = value;
  });

  let controller = null;
  let scrollAfterLoad = false;

  function buildQuery(page = state.page) {
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    if (state.q) params.set('q', state.q);
    if (state.sort !== 'popular') params.set('sort', state.sort);
    if (state.view === 'list') params.set('view', 'list');
    if (page > 1) params.set('page', String(page));
    Object.entries(state.filters).forEach(([key, value]) => {
      if (value) params.set(`f[${key}]`, value);
    });
    return params;
  }

  function syncUrl() {
    const params = buildQuery();
    const next = `${window.location.pathname}${params.toString() ? `?${params}` : ''}${window.location.hash}`;
    window.history.replaceState({}, '', next);
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
    const pageTitle = document.querySelector('[data-catalog-total]');
    if (pageTitle) pageTitle.classList.remove('animate-pulse');
  }

  function emptyState() {
    if (!results) return;
    results.innerHTML = '<div class="rounded-[1.35rem] border border-dashed border-line bg-white px-6 py-14 text-center"><p class="text-sm font-bold text-forest-900">Gagal memuat katalog.</p><p class="mt-1 text-xs text-muted">Periksa koneksi lalu coba lagi.</p></div>';
  }

  async function load({ page = 1, scroll = false } = {}) {
    if (!results) return;
    state.page = page;
    scrollAfterLoad = scroll;
    if (controller) controller.abort();
    controller = new AbortController();
    results.style.opacity = '0.45';
    results.style.pointerEvents = 'none';

    try {
      const params = buildQuery(page);
      const response = await fetch(`${api.apiUrl('items.php')}?${params}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: controller.signal,
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error(payload.message || 'Gagal memuat katalog.');
      results.innerHTML = payload.html;
      state.view = payload.view || state.view;
      state.sort = payload.sort || state.sort;
      setSortValue();
      syncUrl();
      if (scrollAfterLoad) {
        const anchor = document.getElementById('katalog-lengkap') || root;
        anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        api.toast(error.message || 'Katalog gagal dimuat.', 'error');
        emptyState();
      }
    } finally {
      results.style.opacity = '';
      results.style.pointerEvents = '';
    }
  }

  searchForms.forEach((form) => {
    const input = form.querySelector('[data-catalog-search]');
    if (!input) return;
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      state.q = input.value.trim();
      load({ page: 1, scroll: true });
    });
    let timer = null;
    input.addEventListener('input', () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => {
        const next = input.value.trim();
        if (next === state.q) return;
        state.q = next;
        load({ page: 1 });
      }, 460);
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
      document.querySelectorAll('[data-catalog-search]').forEach((input) => { input.value = ''; });
      setActiveChips();
      setSortValue();
      load({ page: 1, scroll: true });
      return;
    }

    const viewButton = event.target.closest('[data-catalog-view]');
    if (viewButton && results.contains(viewButton)) {
      event.preventDefault();
      state.view = viewButton.dataset.catalogView === 'list' ? 'list' : 'grid';
      window.localStorage.setItem('rentify-catalog-view', state.view);
      load({ page: state.page });
      return;
    }

    const pageButton = event.target.closest('[data-page]');
    if (pageButton && !pageButton.disabled) {
      event.preventDefault();
      load({ page: Number(pageButton.dataset.page) || 1, scroll: true });
    }
  });

  document.addEventListener('change', (event) => {
    if (event.target.matches('[data-catalog-sort]')) {
      state.sort = event.target.value;
      load({ page: 1 });
    }
  });

  setActiveChips();
  setSortValue();
})();
