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
})();
