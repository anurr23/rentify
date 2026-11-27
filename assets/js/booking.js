/* Checkout, pembayaran, pembatalan, dan review pesanan. */
(() => {
  'use strict';

  const api = window.RENTIFY;
  if (!api) return;

  /* ---------- Checkout ---------- */
  const checkoutRoot = document.querySelector('[data-checkout-root]');
  if (checkoutRoot) {
    const form = checkoutRoot.querySelector('[data-checkout-form]');
    const methods = checkoutRoot.querySelectorAll('[data-pickup-method]');
    const pickupFields = checkoutRoot.querySelector('[data-pickup-fields]');
    const deliveryFields = checkoutRoot.querySelector('[data-delivery-fields]');
    const pickupSelect = checkoutRoot.querySelector('[data-store-pickup]');
    const deliveryAddress = checkoutRoot.querySelector('[name="delivery_address"]');
    const deliveryEl = checkoutRoot.querySelector('[data-checkout-delivery]');
    const totalEl = checkoutRoot.querySelector('[data-checkout-total]');
    const submit = checkoutRoot.querySelector('[data-checkout-submit]');
    const agree = checkoutRoot.querySelector('[data-checkout-agree]');
    const storeSelect = checkoutRoot.querySelector('[data-store-select]');
    const storeNameEls = checkoutRoot.querySelectorAll('[data-store-name]');
    const storeAddressEls = checkoutRoot.querySelectorAll('[data-store-address]');
    const storeHoursEls = checkoutRoot.querySelectorAll('[data-store-hours], [data-store-pickup-hours]');
    const storePickupNameEls = checkoutRoot.querySelectorAll('[data-store-pickup-name]');
    const storePickupAddressEls = checkoutRoot.querySelectorAll('[data-store-pickup-address]');
    const storeCityEls = checkoutRoot.querySelectorAll('[data-store-city-label]');
    const storeMapsEl = checkoutRoot.querySelector('[data-store-maps]');
    const feeLabels = checkoutRoot.querySelectorAll('[data-delivery-fee-label]');
    const mapEl = checkoutRoot.querySelector('[data-delivery-map]');
    const latInput = checkoutRoot.querySelector('[data-delivery-lat]');
    const lngInput = checkoutRoot.querySelector('[data-delivery-lng]');
    const coordEl = checkoutRoot.querySelector('[data-delivery-coord]');
    const geoStatusEl = checkoutRoot.querySelector('[data-delivery-geocode-status]');
    const useLocationBtn = checkoutRoot.querySelector('[data-use-my-location]');
    let baseFee = Number(checkoutRoot.dataset.deliveryFee || 0);
    let busy = false;
    let map = null;
    let marker = null;
    let geocodeTimer = null;
    let autoAddress = '';

    function currentMethod() {
      return checkoutRoot.querySelector('[data-pickup-method]:checked')?.value || 'pickup';
    }

    function currentStoreOption() {
      return storeSelect?.selectedOptions?.[0] || null;
    }

    function selectedStoreId() {
      return Number(storeSelect?.value || 0);
    }

    function syncStore() {
      const option = currentStoreOption();
      if (!option) return;
      const name = option.dataset.name || option.textContent.trim();
      const address = option.dataset.address || '';
      const hours = option.dataset.hours || '';
      const city = option.dataset.city || '';
      const fee = Number(option.dataset.fee || 0);
      baseFee = fee;
      if (pickupSelect) pickupSelect.value = option.dataset.pickupId || '0';
      storeNameEls.forEach((el) => { el.textContent = name; });
      storePickupNameEls.forEach((el) => { el.textContent = name; });
      storeAddressEls.forEach((el) => { el.textContent = address; });
      storePickupAddressEls.forEach((el) => { el.textContent = address; });
      storeHoursEls.forEach((el) => { el.textContent = hours; });
      storeCityEls.forEach((el) => { el.textContent = city; });
      feeLabels.forEach((el) => { el.textContent = api.formatRupiah(fee); });
      if (storeMapsEl && option.dataset.maps) storeMapsEl.href = option.dataset.maps;
      updateQuote();
    }

    function syncMethod() {
      const delivery = currentMethod() === 'delivery';
      pickupFields?.classList.toggle('hidden', delivery);
      deliveryFields?.classList.toggle('hidden', !delivery);
      if (pickupSelect) pickupSelect.required = false;
      if (deliveryAddress) deliveryAddress.required = delivery;
      if (delivery) initDeliveryMap();
      updateQuote();
    }

    /* ---------- Peta titik pengantaran (Leaflet + reverse geocoding Nominatim) ---------- */
    function num(value) {
      const n = Number(value);
      return Number.isFinite(n) ? n : null;
    }

    function defaultCenter() {
      const lat = num(checkoutRoot.dataset.storeLat);
      const lng = num(checkoutRoot.dataset.storeLng);
      if (lat !== null && lng !== null && lat !== 0 && lng !== 0) return [lat, lng];
      const ulat = num(checkoutRoot.dataset.userLat);
      const ulng = num(checkoutRoot.dataset.userLng);
      if (ulat !== null && ulng !== null && ulat !== 0 && ulng !== 0) return [ulat, ulng];
      return [-6.3011, 107.3055]; /* Karawang */
    }

    function updatePointLabel(lat, lng) {
      const text = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
      if (coordEl) coordEl.textContent = `Titik dipilih: ${text}`;
      if (latInput) latInput.value = lat.toFixed(7);
      if (lngInput) lngInput.value = lng.toFixed(7);
    }

    function setPoint(lat, lng, options = {}) {
      if (!map) return;
      const zoom = options.zoom || Math.max(map.getZoom(), 16);
      const pinIcon = typeof window.rentifyPinIcon === 'function'
        ? window.rentifyPinIcon(window.L, { draggable: true })
        : new window.L.Icon.Default();
      if (!marker) {
        marker = window.L.marker([lat, lng], { draggable: true, icon: pinIcon }).addTo(map);
        marker.on('dragend', () => {
          const pos = marker.getLatLng();
          updatePointLabel(pos.lat, pos.lng);
          scheduleGeocode(pos.lat, pos.lng);
        });
      } else {
        marker.setLatLng([lat, lng]);
        marker.setIcon(pinIcon);
      }
      map.setView([lat, lng], zoom);
      updatePointLabel(lat, lng);
      if (options.geocode !== false) scheduleGeocode(lat, lng);
    }

    function scheduleGeocode(lat, lng) {
      window.clearTimeout(geocodeTimer);
      if (geoStatusEl) geoStatusEl.textContent = 'Mencari alamat dari titik ini...';
      /* Nominatim: maksimal 1 permintaan/detik */
      geocodeTimer = window.setTimeout(() => reverseGeocode(lat, lng), 1200);
    }

    async function reverseGeocode(lat, lng) {
      try {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1'
          + `&accept-language=id&lat=${lat}&lon=${lng}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const a = data.address || {};
        const parts = [
          a.road || a.pedestrian || a.footway,
          a.house_number,
          a.neighbourhood || a.suburb || a.village || a.hamlet,
          a.city_district || a.district || a.town || a.city || a.county,
          a.state,
        ].filter(Boolean);
        const line = parts.join(', ') || data.display_name || '';
        if (!line) {
          if (geoStatusEl) geoStatusEl.textContent = 'Alamat tidak ditemukan, isi manual di kolom bawah.';
          return;
        }
        if (geoStatusEl) geoStatusEl.textContent = 'Alamat terisi otomatis dari titik peta — boleh dilengkapi patokan.';
        if (deliveryAddress) {
          const current = deliveryAddress.value.trim();
          /* jangan menimpa alamat yang diketik manual user */
          if (!current || current === autoAddress) {
            deliveryAddress.value = line;
            autoAddress = line;
            deliveryAddress.classList.remove('input-error');
          }
        }
      } catch (error) {
        if (geoStatusEl) geoStatusEl.textContent = 'Gagal mengambil alamat otomatis. Isi manual di kolom bawah.';
      }
    }

    function initDeliveryMap() {
      if (map || !mapEl || !window.L) return;
      const center = defaultCenter();
      map = window.L.map(mapEl, { scrollWheelZoom: true, zoomControl: true, attributionControl: false })
        .setView(center, 14);
      window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
      }).addTo(map);
      map.on('click', (event) => setPoint(event.latlng.lat, event.latlng.lng));
      window.setTimeout(() => map && map.invalidateSize(), 180);

      /* penanda cabang asal gear, biar user tahu jarak ambil/antar */
      const slat = num(checkoutRoot.dataset.storeLat);
      const slng = num(checkoutRoot.dataset.storeLng);
      if (slat !== null && slng !== null && slat !== 0 && slng !== 0) {
        const storePin = typeof window.rentifyPinIcon === 'function'
          ? window.rentifyPinIcon(window.L, { variant: 'store' })
          : new window.L.Icon.Default();
        window.L.marker([slat, slng], { icon: storePin, interactive: true })
          .addTo(map)
          .bindTooltip('Cabang penyiapan gear', {
            direction: 'top',
            offset: [0, -8],
            className: 'leaflet-pin-tooltip',
          });
      }

      const ulat = num(checkoutRoot.dataset.userLat);
      const ulng = num(checkoutRoot.dataset.userLng);
      if (ulat !== null && ulng !== null && ulat !== 0 && ulng !== 0) {
        setPoint(ulat, ulng, { geocode: false });
      }
    }

    useLocationBtn?.addEventListener('click', () => {
      if (!navigator.geolocation) {
        api.toast('Perangkat ini tidak mendukung penentuan lokasi.', 'warning');
        return;
      }
      api.setLoading(useLocationBtn, true, 'Mencari...');
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          api.setLoading(useLocationBtn, false);
          initDeliveryMap();
          setPoint(pos.coords.latitude, pos.coords.longitude);
        },
        () => {
          api.setLoading(useLocationBtn, false);
          api.toast('Lokasi tidak bisa diambil. Coba geser pin di peta.', 'warning');
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });

    async function updateQuote() {
      if (deliveryEl) deliveryEl.textContent = currentMethod() === 'delivery' ? api.formatRupiah(baseFee) : api.formatRupiah(0);
      try {
        const payload = await api.request('booking.php', {
          method: 'POST',
          body: { action: 'quote', pickup_method: currentMethod(), store_id: selectedStoreId() },
        });
        if (deliveryEl) deliveryEl.textContent = api.formatRupiah(payload.delivery_fee || 0);
        if (totalEl) totalEl.textContent = api.formatRupiah(payload.total || 0);
      } catch (_) {
        /* total server tetap divalidasi saat membuat pesanan */
      }
    }

    let storeTimer = null;
    storeSelect?.addEventListener('change', () => {
      window.clearTimeout(storeTimer);
      storeTimer = window.setTimeout(syncStore, 80);
    });
    methods.forEach((radio) => radio.addEventListener('change', syncMethod));
    syncStore();
    syncMethod();

    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (busy) return;
      if (!agree?.checked) {
        api.toast('Centang persetujuan ketentuan sewa terlebih dahulu.', 'warning');
        agree?.focus();
        return;
      }
      if (currentMethod() === 'delivery' && !deliveryAddress?.value.trim()) {
        api.toast('Alamat pengantaran wajib diisi.', 'warning');
        deliveryAddress?.focus();
        return;
      }
      if (currentMethod() === 'delivery' && (!latInput?.value || !lngInput?.value)) {
        api.toast('Pilih titik lokasi pengantaran di peta (klik atau geser pin).', 'warning');
        initDeliveryMap();
        mapEl?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      if (currentMethod() === 'pickup' && !pickupSelect?.value) {
        api.toast('Pilih titik pengambilan.', 'warning');
        pickupSelect?.focus();
        return;
      }

      busy = true;
      api.setLoading(submit, true, 'Membuat pesanan...');
      try {
        const payload = await api.request('booking.php', { method: 'POST', body: new FormData(form) });
        api.refreshCartBadge(0);
        api.toast(payload.message || 'Pesanan berhasil dibuat.', 'success', 5000);
        window.setTimeout(() => { window.location.href = payload.redirect || api.baseUrl('pesanan.php'); }, 700);
      } catch (error) {
        api.toast(error.message || 'Gagal membuat pesanan.', 'error');
        api.setLoading(submit, false);
        busy = false;
      }
    });
  }

  /* ---------- Pembayaran ---------- */
  const paymentForm = document.querySelector('[data-payment-form]');
  if (paymentForm) {
    const methodRadios = paymentForm.querySelectorAll('[data-payment-method]');
    const transferBox = paymentForm.querySelector('[data-payment-transfer]');
    const qrisBox = paymentForm.querySelector('[data-payment-qris]');
    const submit = paymentForm.querySelector('[data-payment-submit]');
    const proof = paymentForm.querySelector('[name="proof"]');
    let busy = false;

    function syncMethod() {
      const method = paymentForm.querySelector('[data-payment-method]:checked')?.value || 'transfer';
      transferBox?.classList.toggle('hidden', method !== 'transfer');
      qrisBox?.classList.toggle('hidden', method !== 'qris');
      if (proof) proof.required = method !== 'cash';
    }
    methodRadios.forEach((radio) => radio.addEventListener('change', syncMethod));
    syncMethod();

    paymentForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (busy) return;
      if (proof?.required && !proof.files?.length) {
        api.toast('Unggah bukti pembayaran terlebih dahulu.', 'warning');
        proof.focus();
        return;
      }
      busy = true;
      api.setLoading(submit, true, 'Mengirim bukti...');
      try {
        const payload = await api.request('payment.php', { method: 'POST', body: new FormData(paymentForm) });
        api.toast(payload.message || 'Bukti pembayaran terkirim.', 'success', 4800);
        window.setTimeout(() => { window.location.href = payload.redirect || window.location.href; }, 800);
      } catch (error) {
        api.toast(error.message || 'Gagal mengirim pembayaran.', 'error');
        api.setLoading(submit, false);
        busy = false;
      }
    });
  }

  /* ---------- Pembatalan ---------- */
  document.querySelectorAll('[data-booking-cancel]').forEach((button) => {
    button.addEventListener('click', async () => {
      const bookingId = button.dataset.bookingCancel;
      if (!bookingId) return;
      const confirmed = await api.confirmDialog(
        'Pesanan akan dibatalkan. Stok gear dilepas untuk pelanggan lain. Lanjutkan?',
        { title: 'Batalkan pesanan', confirmText: 'Ya, batalkan', danger: true }
      );
      if (!confirmed) return;
      api.setLoading(button, true, 'Membatalkan...');
      try {
        const payload = await api.request('booking.php', {
          method: 'POST',
          body: { action: 'cancel', booking_id: bookingId, reason: 'Dibatalkan oleh customer.' },
        });
        api.toast(payload.message || 'Pesanan dibatalkan.', 'success');
        window.setTimeout(() => { window.location.reload(); }, 650);
      } catch (error) {
        api.toast(error.message || 'Gagal membatalkan pesanan.', 'error');
        api.setLoading(button, false);
      }
    });
  });

  /* ---------- Review ---------- */
  document.querySelectorAll('[data-review-form]').forEach((form) => {
    const stars = form.querySelectorAll('[data-review-star]');
    const ratingInput = form.querySelector('input[name="rating"]');
    const submit = form.querySelector('[data-review-submit]');
    let busy = false;

    function paint(value) {
      stars.forEach((star) => {
        const on = Number(star.dataset.reviewStar) <= value;
        star.classList.toggle('text-star', on);
        star.classList.toggle('text-line', !on);
        const svg = star.querySelector('svg');
        if (svg) svg.setAttribute('fill', on ? 'currentColor' : 'none');
      });
    }

    stars.forEach((star) => {
      star.addEventListener('click', () => {
        const value = Number(star.dataset.reviewStar) || 5;
        if (ratingInput) ratingInput.value = String(value);
        paint(value);
      });
    });
    paint(Number(ratingInput?.value || 5));

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (busy) return;
      busy = true;
      api.setLoading(submit, true, 'Mengirim...');
      try {
        const payload = await api.request('review.php', { method: 'POST', body: new FormData(form) });
        api.toast(payload.message || 'Review terkirim.', 'success');
        window.setTimeout(() => { window.location.reload(); }, 650);
      } catch (error) {
        api.toast(error.message || 'Gagal mengirim review.', 'error');
        api.setLoading(submit, false);
        busy = false;
      }
    });
  });
})();
