/* Rentify core UI helpers */
(() => {
  'use strict';

  const config = window.RENTIFY || {};
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

  const state = {
    loadingCount: 0,
  };

  function baseUrl(path = '') {
    if (/^https?:\/\//i.test(path)) return path;
    return `${config.base || '/'}${String(path).replace(/^\/+/, '')}`;
  }

  function apiUrl(path = '') {
    if (/^https?:\/\//i.test(path)) return path;
    return `${config.api || `${config.base || '/'}api/`}${String(path).replace(/^\/+/, '')}`;
  }

  function loading(show = null) {
    if (show === true) state.loadingCount += 1;
    if (show === false) state.loadingCount = Math.max(0, state.loadingCount - 1);
    const bar = document.getElementById('global-loader');
    if (!bar) return;
    bar.classList.toggle('hidden', state.loadingCount <= 0);
  }

  function toast(message, type = 'info', duration = 4200) {
    const stack = document.getElementById('toast-stack');
    if (!stack) {
      console.warn(message);
      return;
    }
    const icons = {
      success: '<path d="m5 12 4.5 4.5L19 7"/>',
      error: '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6"/><path d="m15 9-6 6"/>',
      info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.5h.01"/>',
      warning: '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5"/><path d="M12 17.5h.01"/>',
    };
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `<svg viewBox="0 0 24 24" class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icons[type] || icons.info}</svg><span class="flex-1"></span><button type="button" class="shrink-0 text-muted hover:text-ink" aria-label="Tutup"><svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12"/><path d="m18 6-12 12"/></svg></button>`;
    el.querySelector('span').textContent = message;
    const remove = () => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(6px)';
      el.style.transition = 'all 180ms ease';
      window.setTimeout(() => el.remove(), 200);
    };
    el.querySelector('button').addEventListener('click', remove);
    stack.appendChild(el);
    if (duration > 0) window.setTimeout(remove, duration);
  }

  class ApiError extends Error {
    constructor(message, status = 0, payload = null) {
      super(message);
      this.name = 'ApiError';
      this.status = status;
      this.payload = payload;
    }
  }

  async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) };
    let body = options.body;
    if (body && !(body instanceof FormData) && typeof body !== 'string') {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }
    if (method !== 'GET' && config.csrf) headers['X-CSRF-Token'] = config.csrf;
    loading(true);
    try {
      const response = await fetch(apiUrl(path), { ...options, method, headers, body, credentials: 'same-origin' });
      const contentType = response.headers.get('Content-Type') || '';
      let payload = null;
      if (contentType.includes('application/json')) {
        payload = await response.json();
      } else {
        const text = await response.text();
        payload = { success: response.ok, message: text };
      }
      if (!response.ok || payload?.success === false) {
        const error = new ApiError(payload?.message || 'Terjadi kesalahan. Coba lagi.', response.status, payload);
        if (payload?.data?.login_required) {
          toast('Silakan masuk terlebih dahulu.', 'warning');
          window.setTimeout(() => {
            window.location.href = baseUrl(`login.php?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`);
          }, 700);
        }
        throw error;
      }
      return payload;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      throw new ApiError('Koneksi bermasalah. Periksa internet Anda lalu coba lagi.', 0, null);
    } finally {
      loading(false);
    }
  }

  function setLoading(button, isLoading, label = 'Memproses...') {
    if (!button) return;
    if (isLoading) {
      if (!button.dataset.originalHtml) button.dataset.originalHtml = button.innerHTML;
      button.disabled = true;
      button.classList.add('opacity-70');
      button.innerHTML = `<svg viewBox="0 0 24 24" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/></svg><span>${label}</span>`;
    } else {
      button.disabled = false;
      button.classList.remove('opacity-70');
      if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
      }
    }
  }

  function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(Number(value) || 0))}`;
  }

  function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => fn(...args), delay);
    };
  }

  function query(params) {
    const search = new URLSearchParams();
    Object.entries(params || {}).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      if (Array.isArray(value)) {
        value.filter((v) => v !== '' && v !== null && v !== undefined).forEach((v) => search.append(key, v));
      } else {
        search.append(key, value);
      }
    });
    return search.toString();
  }

  function refreshCartBadge(count) {
    if (typeof count !== 'number') return;
    config.cartCount = count;
    $$('[data-cart-count]').forEach((badge) => {
      badge.textContent = String(count);
      badge.classList.toggle('hidden', count <= 0);
    });
  }

  function refreshWishlistBadge(count) {
    if (typeof count !== 'number') return;
    $$('[data-wishlist-count]').forEach((badge) => {
      badge.textContent = String(count);
      badge.classList.toggle('hidden', count <= 0);
    });
  }

  function setWishlistButton(button, active) {
    button.classList.toggle('is-wished', active);
    button.classList.toggle('!bg-white', active);
    button.classList.toggle('!text-danger', active);
    button.setAttribute('aria-pressed', active ? 'true' : 'false');
    button.setAttribute('aria-label', active ? 'Hapus dari wishlist' : 'Simpan ke wishlist');
    const svg = button.querySelector('svg');
    if (svg) svg.setAttribute('fill', active ? 'currentColor' : 'none');
  }

  async function toggleWishlist(button) {
    if (!config.userId) {
      toast('Masuk dulu untuk menyimpan wishlist.', 'warning');
      window.setTimeout(() => {
        window.location.href = baseUrl(`login.php?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`);
      }, 650);
      return;
    }
    const itemId = button.dataset.itemId;
    if (!itemId) return;
    button.disabled = true;
    try {
      const payload = await request('wishlist.php', { method: 'POST', body: { item_id: itemId } });
      setWishlistButton(button, Boolean(payload.active));
      $$(`[data-wishlist-button][data-item-id="${CSS.escape(itemId)}"]`).forEach((el) => setWishlistButton(el, Boolean(payload.active)));
      refreshWishlistBadge(payload.wishlist_count);
      toast(payload.message, 'success', 2600);
      if (!payload.active && document.body.dataset.page === 'wishlist') {
        const card = button.closest('[data-wishlist-row]');
        if (card) {
          card.style.opacity = '0';
          card.style.transition = 'opacity 180ms ease';
          window.setTimeout(() => card.remove(), 190);
        }
      }
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      button.disabled = false;
    }
  }

  function openModal(content, options = {}) {
    const root = document.getElementById('modal-root');
    if (!root) return null;
    const wrapper = document.createElement('div');
    wrapper.className = 'modal is-open';
    wrapper.dataset.modal = '';
    const maxWidth = options.maxWidth || '34rem';
    wrapper.innerHTML = `<div class="modal-backdrop" data-modal-close></div><div class="modal-card" style="max-width:${maxWidth}" role="dialog" aria-modal="true" aria-label="${escapeHtml(options.title || 'Dialog')}"><div class="modal-head"><div><h2 class="text-base font-extrabold text-forest-900">${escapeHtml(options.title || '')}</h2>${options.subtitle ? `<p class="mt-0.5 text-xs text-muted">${escapeHtml(options.subtitle)}</p>` : ''}</div><button type="button" class="icon-btn h-9 w-9" data-modal-close aria-label="Tutup">${iconClose()}</button></div><div class="modal-body">${content}</div></div>`;
    root.appendChild(wrapper);
    document.body.style.overflow = 'hidden';
    const close = () => closeModal(wrapper);
    wrapper.querySelectorAll('[data-modal-close]').forEach((el) => el.addEventListener('click', close));
    wrapper.addEventListener('click', (event) => {
      if (event.target === wrapper.querySelector('.modal-backdrop')) close();
    });
    const keyHandler = (event) => {
      if (event.key === 'Escape') {
        close();
        document.removeEventListener('keydown', keyHandler);
      }
    };
    document.addEventListener('keydown', keyHandler);
    window.setTimeout(() => {
      const focusable = wrapper.querySelector('input, select, textarea, button:not([data-modal-close])');
      focusable?.focus();
    }, 80);
    return wrapper;
  }

  function closeModal(modal = null) {
    const target = modal || $('.modal.is-open');
    if (!target) return;
    target.classList.remove('is-open');
    document.body.style.overflow = '';
    window.setTimeout(() => target.remove(), 160);
  }

  function confirmDialog(message, options = {}) {
    return new Promise((resolve) => {
      const modal = openModal(
        `<p class="text-sm leading-relaxed text-forest-700">${escapeHtml(message)}</p>
         <div class="mt-5 flex gap-2">
           <button type="button" class="btn btn-outline flex-1" data-cancel>${escapeHtml(options.cancelText || 'Batal')}</button>
           <button type="button" class="btn flex-1 ${options.danger ? 'bg-danger text-white hover:bg-red-700' : 'btn-primary'}" data-confirm>${escapeHtml(options.confirmText || 'Ya, lanjutkan')}</button>
         </div>`,
        { title: options.title || 'Konfirmasi', maxWidth: '26rem' }
      );
      let settled = false;
      const finish = (value) => {
        if (settled) return;
        settled = true;
        closeModal(modal);
        resolve(value);
      };
      modal.querySelector('[data-cancel]').addEventListener('click', () => finish(false));
      modal.querySelector('[data-confirm]').addEventListener('click', () => finish(true));
      modal.querySelectorAll('[data-modal-close]').forEach((el) => el.addEventListener('click', () => finish(false)));
    });
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
  }

  function iconClose() {
    return '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12"/><path d="m18 6-12 12"/></svg>';
  }

  function setupModals() {
    $$('[data-open-modal]').forEach((button) => {
      button.addEventListener('click', () => {
        const template = document.getElementById(button.dataset.openModal);
        if (template) openModal(template.innerHTML, { title: button.dataset.modalTitle || '' });
      });
    });
  }

  async function markNotificationsRead() {
    if (!config.userId) return;
    try {
      await request('notifications.php', { method: 'POST', body: { action: 'read_all' } });
      $$('[data-notif-count]').forEach((badge) => badge.classList.add('hidden'));
      $$('[data-notif-dot]').forEach((dot) => dot.classList.add('hidden'));
    } catch (_) {
      /* silent */
    }
  }

  function initFlashes() {
    $$('#server-flashes [data-flash-type]').forEach((el, index) => {
      window.setTimeout(() => toast(el.dataset.flashMessage || '', el.dataset.flashType || 'info', 5200), index * 180);
    });
  }

  function initLazyReveal() {
    const elements = $$('.reveal');
    if (!elements.length) return;
    if (!('IntersectionObserver' in window)) {
      elements.forEach((el) => el.classList.add('is-visible'));
      return;
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    elements.forEach((el) => observer.observe(el));
  }

  function initScrollTop() {
    const button = document.getElementById('scroll-top');
    if (!button) return;
    const toggle = () => button.classList.toggle('is-visible', window.scrollY > 520);
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
    button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  function initAjaxLinks() {
    document.addEventListener('click', async (event) => {
      const wishlistButton = event.target.closest('[data-wishlist-button]');
      if (wishlistButton) {
        event.preventDefault();
        event.stopPropagation();
        await toggleWishlist(wishlistButton);
        return;
      }
      const confirmLink = event.target.closest('[data-confirm-link]');
      if (confirmLink) {
        event.preventDefault();
        const confirmed = await confirmDialog(confirmLink.dataset.confirmLink, {
          danger: confirmLink.dataset.confirmDanger === '1',
          confirmText: confirmLink.dataset.confirmText || 'Ya, lanjutkan',
          title: confirmLink.dataset.confirmTitle || 'Konfirmasi',
        });
        if (confirmed) {
          if (confirmLink.dataset.confirmMethod === 'post') {
            submitPost(confirmLink.href, {});
          } else {
            window.location.href = confirmLink.href;
          }
        }
        return;
      }
      const copyButton = event.target.closest('[data-copy]');
      if (copyButton) {
        event.preventDefault();
        try {
          await navigator.clipboard.writeText(copyButton.dataset.copy);
          toast('Disalin ke clipboard.', 'success', 1800);
        } catch (_) {
          toast('Gagal menyalin. Salin manual ya.', 'error');
        }
      }
      const readAll = event.target.closest('[data-notif-read-all]');
      if (readAll) {
        event.preventDefault();
        await markNotificationsRead();
      }
    });

    $$('details[open]').forEach((details) => details.removeAttribute('open'));
    document.addEventListener('click', (event) => {
      $$('details.relative[open]').forEach((details) => {
        if (!details.contains(event.target)) details.removeAttribute('open');
      });
    });
  }

  async function submitPost(path, data) {
    try {
      loading(true);
      const response = await fetch(path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'text/html' },
        body: new URLSearchParams({ ...data, _csrf: config.csrf || '' }),
        credentials: 'same-origin',
      });
      const html = await response.text();
      document.open();
      document.write(html);
      document.close();
    } catch (_) {
      toast('Gagal memproses. Coba lagi.', 'error');
    } finally {
      loading(false);
    }
  }

  function initFilePreviews() {
    $$('[data-preview-input]').forEach((input) => {
      input.addEventListener('change', () => {
        const target = document.querySelector(input.dataset.previewInput);
        const file = input.files?.[0];
        if (!target || !file) return;
        if (!file.type.startsWith('image/')) {
          toast('File harus berupa gambar.', 'error');
          input.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = (event) => {
          target.innerHTML = `<img src="${event.target.result}" alt="Pratinjau" class="h-full w-full object-cover">`;
          target.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
      });
    });

    $$('.upload-drop').forEach((drop) => {
      const input = drop.querySelector('input[type="file"]');
      if (!input) return;
      ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (event) => {
        event.preventDefault();
        drop.classList.add('is-dragover');
      }));
      ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, (event) => {
        event.preventDefault();
        drop.classList.remove('is-dragover');
      }));
      drop.addEventListener('drop', (event) => {
        if (event.dataTransfer.files?.length) {
          input.files = event.dataTransfer.files;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    });
  }

  function initPasswordToggles() {
    $$('[data-toggle-password]').forEach((button) => {
      button.addEventListener('click', () => {
        const target = document.querySelector(button.dataset.togglePassword);
        if (!target) return;
        target.type = target.type === 'password' ? 'text' : 'password';
        button.classList.toggle('text-forest-900');
      });
    });
  }

  function initDateDefaults() {
    const today = config.today || new Date().toISOString().slice(0, 10);
    $$('input[type="date"][data-min-today]').forEach((input) => {
      if (!input.min) input.min = today;
    });
  }

  function init() {
    initFlashes();
    initLazyReveal();
    initScrollTop();
    initAjaxLinks();
    initFilePreviews();
    initPasswordToggles();
    initDateDefaults();
    setupModals();
    document.body.dataset.page = document.body.dataset.page || '';
  }

  window.RENTIFY = Object.assign(config, {
    $, $$, request, toast, loading, setLoading, formatRupiah, debounce, query, openModal, closeModal,
    confirmDialog, escapeHtml, refreshCartBadge, refreshWishlistBadge, toggleWishlist, ApiError, baseUrl, apiUrl,
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
