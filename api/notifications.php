<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$method = api_method(['GET', 'POST']);
$user = api_require_login();

if ($method === 'GET') {
    $stmt = Database::conn()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([(int) $user['id']]);
    $items = $stmt->fetchAll();
    $stmt = Database::conn()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([(int) $user['id']]);
    json_success([
        'items' => array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'link' => $row['link'],
            'is_read' => (int) $row['is_read'] === 1,
            'time_ago' => time_ago($row['created_at']),
        ], $items),
        'unread' => (int) $stmt->fetchColumn(),
    ]);
}

$action = (string) input('action', 'read_all');
if ($action === 'read_all') {
    $stmt = Database::conn()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([(int) $user['id']]);
    json_success(['message' => 'Semua notifikasi ditandai sudah dibaca.', 'unread' => 0]);
}

if ($action === 'read') {
    $id = input_int('id');
    $stmt = Database::conn()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, (int) $user['id']]);
    json_success(['message' => 'Notifikasi ditandai sudah dibaca.']);
}

json_error('Aksi notifikasi tidak dikenali.', 400);
