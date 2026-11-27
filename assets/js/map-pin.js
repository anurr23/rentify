/* Pin kustom Leaflet untuk Rentify (dipakai checkout & detail pesanan admin). */
(() => {
  'use strict';

  const TENT_PATH = 'm12 4 8.5 15H3.5L12 4Z';
  const TENT_POLE = 'm12 4 2.5 15';
  const TENT_BASE = 'M8.5 19 12 12l3.5 7';

  function pinMarkup(options = {}) {
    const variant = options.variant === 'store' ? ' store' : ' main';
    const gradientStart = variant === 'store' ? '#2b5240' : '#1b3b2b';
    const gradientEnd = variant === 'store' ? '#032517' : '#03160e';
    const glyphColor = variant === 'store' ? '#ddf2df' : '#ecf6ec';
    const ringColor = variant === 'store' ? '#cbe8ce' : '#f5f7f4';
    const id = 'rentifyPinGrad' + (variant === 'store' ? 'Store' : 'Main');

    return `
      <svg viewBox="0 0 40 50" role="img" aria-label="Titik pengantaran Rentify">
        <defs>
          <linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="${gradientStart}"/>
            <stop offset="100%" stop-color="${gradientEnd}"/>
          </linearGradient>
        </defs>
        <path d="M20 2.5C11.4 2.5 4.5 9.4 4.5 18c0 11.4 15.5 29.2 15.5 29.2S35.5 29.4 35.5 18C35.5 9.4 28.6 2.5 20 2.5Z"
              fill="url(#${id})" stroke="${ringColor}" stroke-width="1.6" stroke-opacity="0.55"/>
        <circle cx="20" cy="18" r="10.6" fill="${ringColor}" fill-opacity="0.14"
                stroke="${ringColor}" stroke-width="1.1" stroke-opacity="0.7"/>
        <g transform="translate(8 6) scale(1)" fill="none" stroke="${glyphColor}" stroke-width="1.7"
           stroke-linecap="round" stroke-linejoin="round">
          <path d="${TENT_PATH}"/>
          <path d="${TENT_POLE}"/>
          <path d="${TENT_BASE}"/>
        </g>
      </svg>`;
  }

  /**
   * Buat divIcon pin Rentify.
   * @param {object} L objek Leaflet global
   * @param {object} options { variant: 'main'|'store', draggable: bool }
   */
  function rentifyPinIcon(L, options = {}) {
    const variant = options.variant === 'store' ? 'store' : 'main';
    const size = variant === 'store' ? [26, 33] : [34, 42];
    const anchor = [size[0] / 2, size[1]];
    const classes = ['rentify-pin', `rentify-pin--${variant}`];
    if (options.draggable) classes.push('rentify-pin--draggable');

    return L.divIcon({
      className: classes.join(' '),
      html: pinMarkup({ variant }),
      iconSize: size,
      iconAnchor: anchor,
      tooltipAnchor: [0, -size[1] * 0.55],
      popupAnchor: [0, -size[1] * 0.75],
    });
  }

  window.rentifyPinIcon = rentifyPinIcon;

  /* ---------- Mode tampilan peta (layer dasar) ---------- */
  const BASE_LAYERS = {
    peta: {
      label: 'Peta',
      hint: 'Peta jalan standar',
      url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      maxZoom: 19,
    },
    satelit: {
      label: 'Satelit',
      hint: 'Citra bumi + lapisan jalan/nama tempat',
      url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      /* Lapisan kedua: tile peta jalan OSM ditumpuk transparan di atas citra,
         supaya nama jalan & tempat tetap terbaca tanpa layanan label berbayar. */
      overlay: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
      overlayClassName: 'rentify-osm-overlay',
      overlayOpacity: 0.55,
      maxZoom: 19,
    },
  };

  /**
   * Pasang layer dasar + tombol ganti mode (Peta / Satelit).
   * @returns {{ setMode: (mode: string) => void, current: () => string }}
   */
  function rentifyAddBaseLayers(L, map, options = {}) {
    const modes = Object.keys(BASE_LAYERS);
    const layers = {};
    const overlays = {};
    modes.forEach((mode) => {
      layers[mode] = L.tileLayer(BASE_LAYERS[mode].url, { maxZoom: BASE_LAYERS[mode].maxZoom });
      if (BASE_LAYERS[mode].overlay) {
        overlays[mode] = L.tileLayer(BASE_LAYERS[mode].overlay, {
          maxZoom: BASE_LAYERS[mode].maxZoom,
          className: BASE_LAYERS[mode].overlayClassName || '',
          opacity: BASE_LAYERS[mode].overlayOpacity ?? 1,
        });
      }
    });

    const STORAGE_KEY = 'rentify:map-mode';
    const saved = (() => {
      try { return window.localStorage.getItem(STORAGE_KEY); } catch (_) { return null; }
    })();
    const preferred = options.initial || saved;
    let active = modes.includes(preferred) ? preferred : 'peta';
    layers[active].addTo(map);
    if (overlays[active]) overlays[active].addTo(map);

    let controlApi = null;

    function applyMode(mode) {
      if (!modes.includes(mode) || mode === active) return;
      map.removeLayer(layers[active]);
      if (overlays[active]) map.removeLayer(overlays[active]);
      layers[mode].addTo(map);
      if (overlays[mode]) overlays[mode].addTo(map);
      active = mode;
      try { window.localStorage.setItem(STORAGE_KEY, mode); } catch (_) { /* ignore */ }
      if (controlApi) controlApi.setActive(mode);
    }

    const ctl = L.control({ position: 'topright' });
    ctl.onAdd = () => {
      const wrap = L.DomUtil.create('div', 'rentify-layer-switch');
      wrap.setAttribute('role', 'group');
      wrap.setAttribute('aria-label', 'Mode tampilan peta');
      const buttons = {};

      modes.forEach((mode) => {
        const btn = L.DomUtil.create('button', 'rentify-layer-switch__btn', wrap);
        btn.type = 'button';
        btn.dataset.mode = mode;
        btn.title = BASE_LAYERS[mode].hint;
        btn.textContent = BASE_LAYERS[mode].label;
        buttons[mode] = btn;
        L.DomEvent.on(btn, 'click', (event) => {
          L.DomEvent.stop(event);
          applyMode(mode);
        });
      });

      L.DomEvent.disableClickPropagation(wrap);
      L.DomEvent.disableScrollPropagation(wrap);

      controlApi = {
        setActive(mode) {
          Object.entries(buttons).forEach(([key, btn]) => {
            btn.classList.toggle('is-active', key === mode);
            btn.setAttribute('aria-pressed', key === mode ? 'true' : 'false');
          });
        },
      };
      controlApi.setActive(active);
      return wrap;
    };
    ctl.addTo(map);

    return {
      setMode: applyMode,
      current: () => active,
    };
  }

  window.rentifyAddBaseLayers = rentifyAddBaseLayers;
  window.rentifyBaseLayers = BASE_LAYERS;
})();
