/* Input bertema: Flatpickr untuk tanggal, Tom Select untuk select pencarian. */
(() => {
  'use strict';

  const fp = window.flatpickr;
  const TomSelect = window.TomSelect;

  function initDates(root) {
    if (!fp) return;
    const inputs = root.querySelectorAll('input[type="date"], input[data-datepicker]');
    inputs.forEach((input) => {
      if (input.dataset.fpReady === '1') return;
      input.dataset.fpReady = '1';
      const config = {
        dateFormat: 'Y-m-d',
        altInput: false,
        allowInput: true,
        locale: fp.l10ns && fp.l10ns.id ? fp.l10ns.id : 'default',
        onChange: () => input.dispatchEvent(new Event('change', { bubbles: true })),
      };
      const min = input.getAttribute('min') || input.dataset.minDate || '';
      const max = input.getAttribute('max') || input.dataset.maxDate || '';
      if (min) config.minDate = min;
      if (max) config.maxDate = max;
      fp(input, config);
    });
  }

  function initSelects(root) {
    if (!TomSelect) return;
    root.querySelectorAll('select[data-searchable]').forEach((select) => {
      if (select.tomselect) return;
      const mode = select.dataset.searchable || '';
      const inline = select.classList.contains('!w-auto');
      const instance = new TomSelect(select, {
        create: false,
        maxItems: 1,
        allowEmptyOption: Boolean(select.querySelector('option[value=""]')),
        placeholder: select.dataset.placeholder || '',
        dropdownParent: 'body',
      });
      instance.wrapper.classList.add('ts-themed');
      if (inline) instance.wrapper.classList.add('ts-inline');
      if (mode === 'pill') instance.wrapper.classList.add('ts-pill');
      select.dispatchEvent(new CustomEvent('tomselect:ready', { bubbles: true, detail: { instance } }));
    });
  }

  function refresh(root) {
    const scope = root && root.querySelectorAll ? root : document;
    initDates(scope);
    initSelects(scope);
  }

  window.RENTIFY_INPUTS = { refresh };

  document.addEventListener('admin:modal-open', (event) => {
    refresh(event.detail?.modal || document);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => refresh(document));
  } else {
    refresh(document);
  }
})();
