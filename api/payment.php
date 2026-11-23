<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$method = api_method(['GET', 'POST']);
$user = api_require_login();

if ($method === 'GET') {
    json_success([
        'bank' => setting('payment_bank', 'BCA'),
        'account' => setting('payment_account', '1234567890'),
        'account_name' => setting('payment_account_name', 'Rentify Outdoor Karawang'),
        'qris_image' => setting('qris_image', ''),
        'delivery_fee' => (float) setting('delivery_fee', '15000'),
        'hold_hours' => (int) setting('hold_hours', '24'),
    ]);
}

$bookingId = input_int('booking_id');
$method = (string) input('method', 'transfer');
if (!in_array($method, ['transfer', 'qris', 'cash'], true)) {
    json_error('Metode pembayaran tidak valid.');
}
$booking = Booking::find($bookingId);
if (!$booking) {
    json_error('Pesanan tidak ditemukan.', 404);
}
if ((int) $booking['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin') {
    json_error('Anda tidak berhak membayar pesanan ini.', 403);
}
if ($booking['status'] === 'cancelled') {
    json_error('Pesanan sudah dibatalkan.');
}
if ($booking['payment_status'] === 'paid') {
    json_error('Pesanan ini sudah lunas.');
}
if ($booking['payment_status'] === 'waiting') {
    json_error('Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi admin.');
}

$proofPath = null;
if (!empty($_FILES['proof']['name'])) {
    try {
        $proofPath = upload_image($_FILES['proof'], 'proofs', 5242880);
    } catch (Throwable $exception) {
        json_error($exception->getMessage());
    }
}
if ($method !== 'cash' && !$proofPath) {
    json_error('Unggah bukti pembayaran terlebih dahulu.');
}
if ($method === 'cash' && $user['role'] !== 'admin') {
    json_error('Pembayaran tunai hanya bisa dicatat oleh admin.');
}

$result = Booking::markPaid(
    $bookingId,
    (float) $booking['total'],
    $method,
    $proofPath,
    trim((string) input('note', '')) ?: null
);
if (!$result['success']) {
    if ($proofPath) {
        delete_upload($proofPath);
    }
    json_error($result['message']);
}

log_audit('payment.upload', 'booking', $bookingId, ['method' => $method, 'amount' => (float) $booking['total']]);
json_success([
    'message' => $result['message'],
    'payment_status' => 'waiting',
    'payment_status_label' => payment_status_label('waiting'),
    'redirect' => url('pesanan_detail.php?id=' . $bookingId),
]);
