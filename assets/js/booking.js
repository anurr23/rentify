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
    const pickupSelect = checkoutRoot.querySelector('[name="pickup_point_id"]');
    const deliveryAddress = checkoutRoot.querySelector('[name="delivery_address"]');
    const deliveryEl = checkoutRoot.querySelector('[data-checkout-delivery]');
    const totalEl = checkoutRoot.querySelector('[data-checkout-total]');
    const submit = checkoutRoot.querySelector('[data-checkout-submit]');
    const agree = checkoutRoot.querySelector('[data-checkout-agree]');
    const baseFee = Number(checkoutRoot.dataset.deliveryFee || 0);
    let busy = false;

    function currentMethod() {
      return checkoutRoot.querySelector('[data-pickup-method]:checked')?.value || 'pickup';
    }

    function syncMethod() {
      const delivery = currentMethod() === 'delivery';
      pickupFields?.classList.toggle('hidden', delivery);
      deliveryFields?.classList.toggle('hidden', !delivery);
      if (pickupSelect) pickupSelect.required = !delivery;
      if (deliveryAddress) deliveryAddress.required = delivery;
      updateQuote();
    }

    async function updateQuote() {
      if (deliveryEl) deliveryEl.textContent = currentMethod() === 'delivery' ? api.formatRupiah(baseFee) : api.formatRupiah(0);
      try {
        const payload = await api.request('booking.php', {
          method: 'POST',
          body: { action: 'quote', pickup_method: currentMethod() },
        });
        if (deliveryEl) deliveryEl.textContent = api.formatRupiah(payload.delivery_fee || 0);
        if (totalEl) totalEl.textContent = api.formatRupiah(payload.total || 0);
      } catch (_) {
        /* total server tetap divalidasi saat membuat pesanan */
      }
    }

    methods.forEach((radio) => radio.addEventListener('change', syncMethod));
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
