/* Peta cabang Rentify dengan Leaflet + rekomendasi lokasi. */
(() => {
  'use strict';

  const api = window.RENTIFY;
  const stores = Array.isArray(window.RENTIFY_STORES) ? window.RENTIFY_STORES : [];
  const mapEl = document.getElementById('store-map');
  if (!mapEl || typeof window.L === 'undefined') return;

  const map = L.map(mapEl, {
    scrollWheelZoom: false,
    zoomControl: true,
    attributionControl: false,
  }).setView([-6.301, 107.305], 7);

  // Basemap topografi: elevasi, kontur, dan label nama tempat tetap terlihat.
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
  }).addTo(map);

  const bounds = [];
  const markers = new Map();

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
  }

  stores.forEach((store) => {
    if (store.latitude === null || store.longitude === null) return;
    const marker = L.marker([store.latitude, store.longitude]).addTo(map);
    marker.bindPopup(`
      <div style="min-width:13rem;font-family:system-ui,sans-serif">
        <p style="margin:0;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#486255">${escapeHtml(store.code)}</p>
        <p style="margin:2px 0 0;font-size:14px;font-weight:800;color:#032517">${escapeHtml(store.name)}</p>
        <p style="margin:2px 0 6px;font-size:11px;color:#486255">${escapeHtml(store.city)}, ${escapeHtml(store.province)}</p>
        <p style="margin:0 0 6px;font-size:11px;line-height:1.5;color:#486255">${escapeHtml(store.address)}</p>
        <p style="margin:0 0 8px;font-size:10px;color:#486255">${escapeHtml(store.opening_hours || '')}</p>
        <a href="${escapeHtml(store.url)}" style="display:inline-block;border-radius:999px;background:#032517;color:#fff;padding:6px 12px;font-size:10px;font-weight:700;text-decoration:none">Lihat cabang</a>
      </div>
    `);
    markers.set(Number(store.id), marker);
    bounds.push([store.latitude, store.longitude]);
  });

  if (bounds.length === 1) {
    map.setView(bounds[0], 12);
  } else if (bounds.length > 1) {
    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 11 });
  }

  const params = new URLSearchParams(window.location.search);
  const selectedSlug = params.get('store');
  if (selectedSlug) {
    const selected = stores.find((store) => store.slug === selectedSlug);
    const marker = selected ? markers.get(Number(selected.id)) : null;
    if (selected && marker) {
      map.setView([selected.latitude, selected.longitude], 13);
      marker.openPopup();
    }
  }

  let userMarker = null;
  window.addEventListener('rentify:location', (event) => {
    const payload = event.detail || {};
    const nearest = payload.nearest;
    const userLat = payload.latitude;
    const userLng = payload.longitude;
    if (userLat !== null && userLat !== undefined && userLng !== null && userLng !== undefined && userMarker === null) {
      userMarker = L.circleMarker([userLat, userLng], {
        radius: 7,
        color: '#0C1D14',
        weight: 2,
        fillColor: '#53CF6C',
        fillOpacity: 0.9,
      }).addTo(map).bindPopup('Lokasi kamu');
    }

    (payload.stores || []).forEach((store) => {
      const el = document.querySelector(`[data-store-distance="${Number(store.id)}"]`);
      if (el) {
        el.textContent = store.distance_label
          ? `${store.distance_label} dari lokasimu`
          : 'Lokasi tidak tersedia';
        el.classList.add('text-forest-700', 'font-bold');
      }
    });
    document.querySelectorAll('[data-nearest-store-distance]').forEach((el) => {
      if (nearest?.distance_label) el.textContent = `${nearest.distance_label} dari lokasimu`;
    });

    if (userLat !== null && userLat !== undefined && userLng !== null && userLng !== undefined) {
      if (nearest && nearest.latitude !== null && nearest.longitude !== null) {
        map.fitBounds(
          [[userLat, userLng], [nearest.latitude, nearest.longitude]],
          { padding: [48, 48], maxZoom: 13 }
        );
      } else {
        map.setView([userLat, userLng], 12);
      }
    }
  });

  if (api) {
    // Ekspos untuk debugging ringan tanpa mengubah state aplikasi.
    api.storeMap = { map, markers };
  }
})();
