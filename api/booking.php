<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$method = api_method(['GET', 'POST']);
$user = api_require_login();

if ($method === 'GET') {
    $bookingId = input_int('id');
    $booking = Booking::find($bookingId);
    $canAccess = $booking && ((int) $booking['user_id'] === (int) $user['id']
        || $user['role'] === 'superadmin'
        || ($user['role'] === 'admin' && (int) $booking['store_id'] === (int) ($user['store_id'] ?? 0)));
    if (!$canAccess) {
        json_error('Pesanan tidak ditemukan.', 404);
    }
    json_success([
        'booking' => $booking,
        'items' => Booking::items($bookingId),
        'payments' => Booking::payments($bookingId),
        'logs' => Booking::logs($bookingId),
    ]);
}

$action = (string) input('action', 'create');

if ($action === 'quote') {
    $validation = cart_validate();
    if (!$validation['valid']) {
        json_error(implode(' ', $validation['errors']), 422, $validation['errors']);
    }
    $cart = $validation['cart'];
    $pickupMethod = (string) input('pickup_method', 'pickup') === 'delivery' ? 'delivery' : 'pickup';
    $storeId = input_int('store_id');
    $store = $storeId > 0 ? Store::find($storeId) : Store::defaultStore();
    if (!$store || (int) $store['is_active'] !== 1) {
        json_error('Toko cabang tidak tersedia.', 422);
    }
    $deliveryFee = $pickupMethod === 'delivery'
        ? (float) ($store['delivery_fee'] ?? setting('delivery_fee', '15000'))
        : 0.0;
    $subtotal = (float) $cart['subtotal'];
    $deposit = (float) $cart['deposit'];
    json_success([
        'subtotal' => $subtotal,
        'deposit' => $deposit,
        'delivery_fee' => $deliveryFee,
        'total' => $subtotal + $deposit + $deliveryFee,
        'duration' => (int) $cart['duration'],
        'pickup_method' => $pickupMethod,
    ]);
}

if ($action === 'create') {
    $start = (string) input('start_date', '');
    $end = (string) input('end_date', '');
    if ($start !== '' && $end !== '') {
        $setDates = cart_set_dates($start, $end);
        if (!$setDates['success']) {
            json_error($setDates['message']);
        }
    }
    $data = [
        'start_date' => $start,
        'end_date' => $end,
        'pickup_method' => (string) input('pickup_method', 'pickup'),
        'store_id' => input_int('store_id'),
        'pickup_point_id' => input_int('pickup_point_id'),
        'delivery_address' => (string) input('delivery_address', ''),
        'notes' => (string) input('notes', ''),
    ];
    $result = Booking::createFromCart((int) $user['id'], $data);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('booking.create', 'booking', (int) $result['booking_id'], ['code' => $result['code']]);
    json_success([
        'message' => $result['message'] . ' Kode pesanan: ' . $result['code'],
        'booking_id' => (int) $result['booking_id'],
        'code' => $result['code'],
        'redirect' => url('pesanan_detail.php?id=' . (int) $result['booking_id']),
        'cart_count' => 0,
    ]);
}

if ($action === 'cancel') {
    $bookingId = input_int('booking_id');
    $reason = trim((string) input('reason', ''));
    $booking = Booking::find($bookingId);
    if (!$booking) {
        json_error('Pesanan tidak ditemukan.', 404);
    }
    $canCancel = (int) $booking['user_id'] === (int) $user['id']
        || $user['role'] === 'superadmin'
        || ($user['role'] === 'admin' && (int) $booking['store_id'] === (int) ($user['store_id'] ?? 0));
    if (!$canCancel) {
        json_error('Anda tidak berhak membatalkan pesanan ini.', 403);
    }
    $result = Booking::cancelByCustomer($bookingId, (int) $user['id'], $reason);
    if (!$result['success']) {
        json_error($result['message']);
    }
    json_success([
        'message' => $result['message'],
        'status' => 'cancelled',
        'status_label' => status_label('cancelled'),
        'redirect' => url('pesanan_detail.php?id=' . $bookingId),
    ]);
}

json_error('Aksi pesanan tidak dikenali.', 400);
