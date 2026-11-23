<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

@set_time_limit(90);

$method = api_method(['GET', 'POST']);

if ($method === 'GET') {
    $activities = AiRecommender::activities();
    $destinations = AiRecommender::destinations();
    $pickupPoints = Setting::pickupPoints();
    $aiConfig = require ROOT_PATH . '/config/ai.php';
    json_success([
        'activities' => array_map(static fn (array $activity): array => [
            'id' => (int) $activity['id'],
            'name' => $activity['name'],
            'slug' => $activity['slug'],
            'icon' => $activity['icon'],
            'description' => $activity['description'],
        ], $activities),
        'destinations' => array_map(static fn (array $destination): array => [
            'id' => (int) $destination['id'],
            'name' => $destination['name'],
            'slug' => $destination['slug'],
            'region' => $destination['region'],
            'elevation_m' => $destination['elevation_m'] !== null ? (int) $destination['elevation_m'] : null,
            'terrain' => $destination['terrain'],
            'notes' => $destination['notes'],
        ], $destinations),
        'pickup_points' => array_map(static fn (array $point): array => [
            'id' => (int) $point['id'],
            'name' => $point['name'],
            'address' => $point['address'],
            'hours' => $point['hours'],
        ], $pickupPoints),
        'ai_enabled' => setting('ai_enabled', '1') === '1',
        'rate_limit' => (int) ($aiConfig['rate_limit_per_hour'] ?? 20),
    ]);
}

if (setting('ai_enabled', '1') !== '1') {
    json_error('AI Planner sedang dinonaktifkan oleh admin. Silakan coba lagi nanti.', 503);
}

$activityId = input_int('activity_id');
$destinationId = input_int('destination_id');
$destinationText = trim((string) input('destination', ''));
$days = max(1, min(14, input_int('days', 2)));
$participants = max(1, min(12, input_int('participants', 2)));
$startDate = trim((string) input('start_date', ''));
$endDate = trim((string) input('end_date', ''));
$notes = trim((string) input('notes', ''));
$storeId = input_int('store_id');

if ($startDate !== '' && $startDate < today()) {
    json_error('Tanggal mulai tidak boleh di masa lalu.');
}
if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
    json_error('Tanggal kembali harus setelah tanggal mulai.');
}
if ($startDate !== '' && $endDate === '') {
    $endDate = date('Y-m-d', strtotime($startDate . ' +' . ($days - 1) . ' days'));
}
if ($startDate === '' && $endDate === '') {
    $startDate = tomorrow();
    $endDate = date('Y-m-d', strtotime($startDate . ' +' . ($days - 1) . ' days'));
}
$days = duration_days($startDate, $endDate);

if ($notes !== '') {
    $_SERVER['HTTP_X_RENTIFY_NOTES'] = $notes;
}

try {
    $recommender = new AiRecommender();
    $result = $recommender->recommend([
        'activity_id' => $activityId,
        'destination_id' => $destinationId,
        'destination' => $destinationText,
        'days' => $days,
        'participants' => $participants,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'notes' => $notes,
        'store_id' => $storeId,
    ], auth_user());

    if (!$result['success']) {
        json_error($result['message']);
    }

    $result['redirect_cart'] = url('keranjang.php');
    $result['wa_help'] = phone_wa_link(
        setting('whatsapp', '6281234567890'),
        'Halo Rentify, saya sudah mendapat rekomendasi AI dan ingin bertanya.'
    );
    json_success($result);
} catch (Throwable $exception) {
    error_log('AI API failed: ' . $exception->getMessage());
    json_error('AI Planner sedang sibuk. Coba lagi sebentar lagi.', 503);
}
