<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

api_method(['POST']);
$user = api_require_login();

$bookingId = input_int('booking_id');
$itemId = input_int('item_id');
$rating = max(1, min(5, input_int('rating', 5)));
$comment = trim((string) input('comment', ''));

if ($bookingId <= 0 || $itemId <= 0) {
    json_error('Data review tidak lengkap.');
}
if (mb_strlen($comment) > 1000) {
    json_error('Komentar maksimal 1000 karakter.');
}

$result = Review::create($bookingId, (int) $user['id'], $itemId, $rating, $comment);
if (!$result['success']) {
    json_error($result['message']);
}

log_audit('review.create', 'item', $itemId, ['booking_id' => $bookingId, 'rating' => $rating]);
json_success([
    'message' => $result['message'],
    'redirect' => url('pesanan_detail.php?id=' . $bookingId),
]);
