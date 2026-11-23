/* Pemilih lokasi toko berbasis Leaflet. */
(() => {
  'use strict';

  const api = window.RENTIFY;
  if (!api || typeof window.L === 'undefined') return;

  let map = null;
  let marker = null;

  function setStatus(form, message) {
    const el = form.querySelector('[data-store-map-status]');
    if (el) el.textContent = message;
  }

  function setPoint(form, lat, lng, pan = true) {
    const latInput = form.querySelector('[data-store-lat]');
    const lngInput = form.querySelector('[data-store-lng]');
    if (latInput) latInput.value = Number(lat).toFixed(7);
    if (lngInput) lngInput.value = Number(lng).toFixed(7);
    if (!map) return;
    if (!marker) {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', () => {
        const pos = marker.getLatLng();
        setPoint(form, pos.lat, pos.lng, false);
      });
    } else {
      marker.setLatLng([lat, lng]);
    }
    if (pan) map.setView([lat, lng], Math.max(map.getZoom(), 13));
    setStatus(form, `Titik dipilih: ${Number(lat).toFixed(7)}, ${Number(lng).toFixed(7)}`);
  }

  function initMap(form) {
    const container = form.querySelector('[data-store-map-picker]');
    if (!container) return;
    if (map) {
      map.remove();
      map = null;
      marker = null;
    }

    const latInput = form.querySelector('[data-store-lat]');
    const lngInput = form.querySelector('[data-store-lng]');
    const hasPoint = latInput?.value && lngInput?.value && !Number.isNaN(Number(latInput.value)) && !Number.isNaN(Number(lngInput.value));
    const lat = hasPoint ? Number(latInput.value) : -6.301;
    const lng = hasPoint ? Number(lngInput.value) : 107.305;

    map = L.map(container, { scrollWheelZoom: false }).setView([lat, lng], hasPoint ? 13 : 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(map);

    if (hasPoint) {
      setPoint(form, lat, lng, false);
    }

    map.on('click', (event) => {
      setPoint(form, event.latlng.lat, event.latlng.lng, false);
    });

    setTimeout(() => map.invalidateSize(), 120);

    form.querySelector('[data-store-map-locate]')?.addEventListener('click', () => {
      if (!navigator.geolocation) {
        api.toast('Browser tidak mendukung akses lokasi.', 'error');
        return;
      }
      setStatus(form, 'Membaca lokasi...');
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setPoint(form, position.coords.latitude, position.coords.longitude);
          api.toast('Lokasi Anda dipakai sebagai titik toko.', 'success');
        },
        (error) => {
          setStatus(form, 'Gagal membaca lokasi. Pilih titik di peta.');
          api.toast(error.code === 1 ? 'Akses lokasi ditolak.' : 'Lokasi tidak tersedia.', 'warning');
        },
        { enableHighAccuracy: true, timeout: 12000 }
      );
    });

    async function geocode() {
      const input = form.querySelector('[data-store-map-search]');
      const query = input?.value.trim();
      if (!query) {
        api.toast('Tulis alamat atau nama tempat dulu.', 'warning');
        input?.focus();
        return;
      }
      setStatus(form, 'Mencari alamat...');
      try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Pencarian alamat gagal.');
        const results = await response.json();
        if (!results.length) {
          setStatus(form, 'Alamat tidak ditemukan. Coba kata kunci lain.');
          api.toast('Alamat tidak ditemukan.', 'warning');
          return;
        }
        const result = results[0];
        setPoint(form, Number(result.lat), Number(result.lon));
        map.setView([Number(result.lat), Number(result.lon)], 16);
        setStatus(form, `Ditemukan: ${result.display_name}`);
      } catch (error) {
        setStatus(form, 'Pencarian alamat gagal. Pilih titik manual di peta.');
        api.toast(error.message || 'Pencarian alamat gagal.', 'error');
      }
    }

    form.querySelector('[data-store-map-geocode]')?.addEventListener('click', geocode);
    form.querySelector('[data-store-map-search]')?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        geocode();
      }
    });
  }

  document.addEventListener('admin:modal-open', (event) => {
    const form = event.detail?.form?.matches?.('[data-store-form]')
      ? event.detail.form
      : document.querySelector('[data-store-form]');
    if (form) initMap(form);
  });

  // Jika form dirender langsung (bukan modal), inisialisasi juga.
  const inlineForm = document.querySelector('[data-store-form]');
  if (inlineForm && inlineForm.querySelector('[data-store-map-picker]')) {
    initMap(inlineForm);
  }
})();
