/* Geolocation & rekomendasi cabang terdekat. */
(() => {
  'use strict';

  const api = window.RENTIFY;
  if (!api) return;

  function formatDistance(distance, label) {
    if (label) return label;
    if (distance === null || distance === undefined) return '';
    return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(Number(distance))} km`;
  }

  function renderLocation(payload) {
    const nearest = payload?.nearest;
    if (!nearest) return;

    document.querySelectorAll('[data-location-status]').forEach((el) => {
      el.textContent = `Lokasi aktif · terdekat: ${nearest.name} (${formatDistance(nearest.distance_km, nearest.distance_label)})`;
    });
    document.querySelectorAll('[data-nearest-store-name]').forEach((el) => { el.textContent = nearest.name; });
    document.querySelectorAll('[data-nearest-store-city]').forEach((el) => { el.textContent = `${nearest.city}, ${nearest.province}`; });
    document.querySelectorAll('[data-nearest-store-address]').forEach((el) => { el.textContent = nearest.address; });
    document.querySelectorAll('[data-nearest-store-hours]').forEach((el) => { el.textContent = nearest.opening_hours || ''; });
    document.querySelectorAll('[data-nearest-store-distance]').forEach((el) => {
      el.textContent = nearest.distance_km === null || nearest.distance_km === undefined
        ? 'Cabang utama'
        : `${formatDistance(nearest.distance_km, nearest.distance_label)} dari lokasimu`;
    });
    document.querySelectorAll('[data-nearest-store-link]').forEach((el) => {
      if (nearest.slug) el.href = api.baseUrl(`toko.php?store=${encodeURIComponent(nearest.slug)}`);
    });

    document.querySelectorAll('[data-use-location]').forEach((button) => {
      const target = button.dataset.locationTarget;
      if (!target) return;
      const select = document.querySelector(target);
      if (!select) return;
      select.value = String(nearest.id);
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });

    window.dispatchEvent(new CustomEvent('rentify:location', { detail: payload }));
  }

  function position() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Browser tidak mendukung akses lokasi.'));
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (result) => resolve(result.coords),
        (error) => {
          const messages = {
            1: 'Akses lokasi ditolak. Pilih cabang secara manual.',
            2: 'Lokasi tidak tersedia. Coba lagi.',
            3: 'Permintaan lokasi terlalu lama. Coba lagi.',
          };
          reject(new Error(messages[error.code] || 'Gagal membaca lokasi.'));
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 300000 }
      );
    });
  }

  async function locate(button, silent = false) {
    const previous = button ? button.innerHTML : null;
    if (button) {
      button.disabled = true;
      button.dataset.originalHtml = previous;
      button.innerHTML = '<span class="inline-flex items-center gap-2">Membaca lokasi...</span>';
    }
    try {
      const coords = await position();
      const payload = await api.request('location.php', {
        method: 'POST',
        body: { action: 'save', lat: coords.latitude, lng: coords.longitude },
      });
      renderLocation(payload);
      if (!silent) api.toast(payload.message || 'Lokasi tersimpan.', 'success', 4200);
    } catch (error) {
      document.querySelectorAll('[data-location-status]').forEach((el) => {
        el.textContent = error.message || 'Gagal membaca lokasi.';
      });
      if (!silent) api.toast(error.message || 'Gagal membaca lokasi.', 'error', 4200);
    } finally {
      if (button) {
        button.disabled = false;
        if (previous !== null) button.innerHTML = previous;
      }
    }
  }

  document.querySelectorAll('[data-use-location]').forEach((button) => {
    button.addEventListener('click', () => locate(button));
  });

  document.querySelectorAll('[data-set-store]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!api.userId) {
        api.toast('Masuk dulu untuk menyimpan toko utama.', 'warning');
        window.setTimeout(() => {
          window.location.href = api.baseUrl(`login.php?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`);
        }, 900);
        return;
      }
      api.setLoading(button, true, 'Menyimpan...');
      try {
        const payload = await api.request('location.php', {
          method: 'POST',
          body: { action: 'preferred', store_id: button.dataset.setStore },
        });
        api.toast(payload.message || 'Toko utama disimpan.', 'success');
        document.querySelectorAll('[data-set-store]').forEach((el) => {
          el.classList.toggle('is-active', el === button);
          el.dataset.saved = el === button ? '1' : '0';
        });
      } catch (error) {
        api.toast(error.message || 'Gagal menyimpan toko utama.', 'error');
      } finally {
        api.setLoading(button, false);
      }
    });
  });

  const auto = document.querySelector('[data-location-auto]');
  if (auto) {
    const key = 'rentify_geo_prompted';
    let prompted = false;
    try {
      prompted = sessionStorage.getItem(key) === '1';
      sessionStorage.setItem(key, '1');
    } catch (_) {
      /* private mode */
    }
    if (!prompted) {
      window.setTimeout(() => locate(null, true), 1400);
    }
  }
})();
