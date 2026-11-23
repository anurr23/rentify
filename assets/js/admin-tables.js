/* DataTables server-side untuk tabel admin (async, ringan). */
(() => {
  'use strict';

  const api = window.RENTIFY;
  const DataTable = window.DataTable;
  if (!api || !DataTable) return;

  const instances = new Map();
  const numberFormatter = new Intl.NumberFormat('id-ID');
  const formatNumber = (value) => numberFormatter.format(Number(value || 0));

  const language = {
    processing: 'Memuat data...',
    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data',
    infoFiltered: '(difilter dari _MAX_ total)',
    zeroRecords: 'Tidak ada data yang cocok',
    emptyTable: 'Belum ada data',
    paginate: {
      first: 'Awal',
      previous: 'Sebelumnya',
      next: 'Berikutnya',
      last: 'Akhir',
    },
  };

  function applyStats(scope, payload) {
    if (payload.stats) {
      Object.entries(payload.stats).forEach(([key, value]) => {
        document.querySelectorAll(`[data-stat="${key}"]`).forEach((element) => {
          element.textContent = typeof value === 'number' ? formatNumber(value) : value;
        });
      });
    }
    const count = payload.recordsFiltered ?? payload.recordsTotal;
    const dataset = payload.dataset || '';
    const selector = dataset ? `[data-table-count="${dataset}"]` : '[data-table-count]:not([data-table-count=""])';
    scope.querySelectorAll(selector).forEach((element) => {
      element.textContent = formatNumber(count);
    });
  }

  function filterParams(form) {
    const params = {};
    new URLSearchParams(location.search).forEach((value, key) => {
      if (value !== '') params[key] = value;
    });
    if (!form) return params;
    new FormData(form).forEach((value, key) => {
      const normalized = String(value).trim();
      if (normalized === '' || normalized === '0' || normalized === 'semua') {
        delete params[key];
        return;
      }
      params[key] = normalized;
    });
    return params;
  }

  document.querySelectorAll('table[data-datatable]').forEach((table) => {
    const dataset = table.dataset.datatable;
    const scope = table.closest('[data-table-section]') || document;
    const form = scope.querySelector('[data-table-filters]');
    const headerCells = table.querySelectorAll('thead th');
    const orderingEnabled = table.dataset.ordering !== '0';
    const order = (() => {
      try {
        return JSON.parse(table.dataset.order || '[]');
      } catch (error) {
        return [];
      }
    })();

    const dataTable = new DataTable(table, {
      serverSide: true,
      processing: true,
      searching: false,
      ordering: orderingEnabled,
      order: orderingEnabled ? order : [],
      pageLength: Number(table.dataset.pageLength || 10),
      lengthChange: false,
      autoWidth: false,
      layout: {
        topStart: null,
        topEnd: null,
        bottomStart: 'info',
        bottomEnd: 'paging',
      },
      language,
      ajax: {
        url: api.apiUrl('admin_table.php'),
        type: 'GET',
        data: (params) => {
          params.dataset = dataset;
          Object.assign(params, filterParams(form));
          if (table.dataset.userId) params.user_id = table.dataset.userId;
          if (table.dataset.bookingId) params.booking_id = table.dataset.bookingId;
          return params;
        },
        dataSrc: (payload) => {
          applyStats(scope, payload);
          return payload.data || [];
        },
        error: () => api.toast('Gagal memuat data tabel.', 'error'),
      },
      columns: Array.from(headerCells, (_, index) => ({ data: `cells.${index}` })),
      createdRow: (row, data) => {
        const rowClass = data.meta?.row_class;
        if (rowClass) {
          rowClass.split(/\s+/).filter(Boolean).forEach((name) => row.classList.add(name));
        }
      },
    });

    instances.set(dataset, dataTable);

    /* DataTables memindahkan <table> ke layout row. Bungkus hanya baris tabel
       dengan scroll horizontal, lalu angkat container keluar wrapper lama agar
       info & paging tetap terlihat di layar kecil. */
    const container = table.closest('.dt-container');
    const tableRow = table.closest('.dt-layout-row');
    if (container && tableRow) {
      const scroller = document.createElement('div');
      scroller.className = 'overflow-x-auto';
      container.insertBefore(scroller, tableRow);
      scroller.appendChild(tableRow);
      const oldWrap = container.parentElement?.classList.contains('overflow-x-auto') ? container.parentElement : null;
      if (oldWrap && oldWrap !== scroller) {
        oldWrap.parentNode.insertBefore(container, oldWrap);
        oldWrap.remove();
      }
    }

    if (form) {
      const reload = (resetPaging = true) => dataTable.ajax.reload(null, resetPaging);
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        reload(true);
        const query = new URLSearchParams(filterParams(form)).toString();
        const next = query ? `${location.pathname}?${query}` : location.pathname;
        window.history.replaceState(null, '', next);
      });

      form.querySelectorAll('select, input[type="date"]').forEach((input) => {
        input.addEventListener('change', () => reload(true));
      });

      const textInput = form.querySelector('input[type="search"], input[name="q"]');
      if (textInput && typeof api.debounce === 'function') {
        const debounced = api.debounce(() => reload(true), 350);
        textInput.addEventListener('input', debounced);
        textInput.addEventListener('search', () => reload(true));
      }
    }
  });

  window.RENTIFY_TABLES = {
    reload: () => {
      document.dispatchEvent(new CustomEvent('admin:tables-refresh'));
    },
  };

  document.addEventListener('admin:tables-refresh', () => {
    instances.forEach((dataTable) => dataTable.ajax.reload(null, false));
  });
})();
