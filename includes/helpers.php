<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return BASE_URL . ltrim($path, '/');
}

function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $full = ROOT_PATH . '/assets/' . $relative;
    $version = is_file($full) ? (string) filemtime($full) : APP_VERSION;
    return ASSET_URL . $relative . '?v=' . $version;
}

function upload_url(?string $path, string $fallback = 'assets/img/placeholder.svg'): string
{
    if (!$path) {
        return asset($fallback);
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $relative = ltrim($path, '/');
    if (is_file(ROOT_PATH . '/' . $relative)) {
        return BASE_URL . $relative;
    }
    return asset($fallback);
}

function redirect(string $path, int $status = 302): never
{
    $target = preg_match('#^https?://#i', $path) ? $path : url($path);
    if (!headers_sent()) {
        header('Location: ' . $target, true, $status);
    }
    exit;
}

function redirect_back(string $fallback = 'index.php'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && str_starts_with($referer, BASE_URL)) {
        redirect($referer);
    }
    redirect($fallback);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $data, int $status = 200): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_success(array $data = [], string $message = 'OK', int $status = 200): never
{
    json_response(array_merge(['success' => true, 'message' => $message], $data), $status);
}

function json_error(string $message, int $status = 422, array $errors = [], mixed $data = null): never
{
    $payload = ['success' => false, 'message' => $message, 'errors' => $errors];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    json_response($payload, $status);
}

function request_data(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = is_array($_GET) ? $_GET : [];
    if (is_array($_POST) && $_POST) {
        $cache = array_merge($cache, $_POST);
    }
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains(strtolower($contentType), 'application/json')) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '[]', true);
        if (is_array($decoded)) {
            $cache = array_merge($cache, $decoded);
        }
    }
    return $cache;
}

function input(string $key, mixed $default = null, ?array $source = null): mixed
{
    $source = $source ?? request_data();
    if (!array_key_exists($key, $source)) {
        return $default;
    }
    $value = $source[$key];
    return is_string($value) ? trim($value) : $value;
}

function input_int(string $key, int $default = 0): int
{
    return max(0, (int) input($key, $default));
}

function input_bool(string $key, bool $default = false): bool
{
    $value = input($key, $default);
    return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(bool $throw = true): bool
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? input('_csrf', '');
    $valid = is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
    if (!$valid && $throw) {
        if (is_ajax() || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            json_error('Sesi keamanan berakhir. Muat ulang halaman lalu coba lagi.', 419);
        }
        flash('error', 'Sesi keamanan berakhir. Silakan coba lagi.');
        redirect_back();
    }
    return $valid;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

function format_rupiah(float|int|string|null $amount): string
{
    return 'Rp ' . number_format((float) ($amount ?? 0), 0, ',', '.');
}

function format_rupiah_short(float|int|string|null $amount): string
{
    $value = (float) ($amount ?? 0);
    if ($value >= 1000000) {
        return 'Rp ' . rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',') . 'jt';
    }
    if ($value >= 1000) {
        return 'Rp ' . rtrim(rtrim(number_format($value / 1000, 0, ',', '.'), '0'), ',') . 'rb';
    }
    return 'Rp ' . number_format($value, 0, ',', '.');
}

function format_number(float|int|string|null $value): string
{
    return number_format((float) ($value ?? 0), 0, ',', '.');
}

function format_date_id(?string $date, string $format = 'd M Y'): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    $monthsLong = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    try {
        $dt = new DateTime($date);
    } catch (Throwable) {
        return '-';
    }
    $format = str_replace(['F', 'M'], ["\x01", "\x02"], $format);
    $result = $dt->format($format);
    return str_replace(
        ["\x01", "\x02"],
        [$monthsLong[(int) $dt->format('n')], $months[(int) $dt->format('n')]],
        $result
    );
}

function format_datetime_id(?string $dateTime): string
{
    if (!$dateTime) {
        return '-';
    }
    try {
        $dt = new DateTime($dateTime);
        return format_date_id($dt->format('Y-m-d'), 'd M Y') . ' • ' . $dt->format('H:i');
    } catch (Throwable) {
        return '-';
    }
}

function duration_days(?string $start, ?string $end): int
{
    if (!$start || !$end) {
        return 1;
    }
    try {
        $s = new DateTimeImmutable($start);
        $e = new DateTimeImmutable($end);
        if ($e < $s) {
            return 0;
        }
        return (int) $s->diff($e)->days + 1;
    } catch (Throwable) {
        return 1;
    }
}

function today(): string
{
    return date('Y-m-d');
}

function tomorrow(): string
{
    return date('Y-m-d', strtotime('+1 day'));
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $stmt = Database::conn()->query('SELECT setting_key, setting_value FROM settings');
            foreach ($stmt->fetchAll() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable) {
            $cache = [];
        }
    }
    return (string) ($cache[$key] ?? $default);
}

function settings_all(): array
{
    try {
        $stmt = Database::conn()->query('SELECT setting_key, setting_value, setting_group, setting_type FROM settings ORDER BY setting_group, setting_key');
        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function notify(int $userId, string $type, string $title, string $message = '', ?string $link = null): void
{
    try {
        $stmt = Database::conn()->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$userId, $type, $title, $message, $link]);
    } catch (Throwable $exception) {
        error_log('Notification failed: ' . $exception->getMessage());
    }
}

function log_audit(string $action, ?string $entity = null, ?int $entityId = null, array $meta = []): void
{
    try {
        $stmt = Database::conn()->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, meta, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entity,
            $entityId,
            json_encode($meta, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable) {
        // Audit log tidak boleh menggagalkan operasi utama.
    }
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item-' . time();
}

function generate_booking_code(): string
{
    return 'RNT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

function generate_member_code(int $id): string
{
    return 'RNT-' . date('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function status_label(string $status, ?string $pickupMethod = null): string
{
    $labels = [
        'pending' => 'Pesanan Dibuat',
        'confirmed' => 'Pembayaran Lunas',
        'preparing' => 'Pesanan Disiapkan',
        'ready' => $pickupMethod === 'delivery'
            ? 'Pesanan Diantar'
            : ($pickupMethod === 'pickup' ? 'Siap Diambil' : 'Siap Diambil / Diantar'),
        'ongoing' => 'Diterima',
        'returned' => 'Dikembalikan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function status_class(string $status): string
{
    return [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'preparing' => 'badge-info',
        'ready' => 'badge-mint',
        'ongoing' => 'badge-success',
        'returned' => 'badge-neutral',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
    ][$status] ?? 'badge-neutral';
}

function payment_status_label(string $status): string
{
    return [
        'unpaid' => 'Belum Bayar',
        'waiting' => 'Menunggu Verifikasi',
        'partial' => 'DP Dibayar',
        'paid' => 'Lunas',
        'refunded' => 'Dikembalikan',
    ][$status] ?? ucfirst($status);
}

function payment_status_class(string $status): string
{
    return [
        'unpaid' => 'badge-warning',
        'waiting' => 'badge-info',
        'partial' => 'badge-mint',
        'paid' => 'badge-success',
        'refunded' => 'badge-neutral',
    ][$status] ?? 'badge-neutral';
}

function payment_type_label(string $type): string
{
    return ['dp' => 'DP', 'settlement' => 'Pelunasan', 'full' => 'Lunas'][$type] ?? ucfirst($type);
}

function payment_method_label(string $method): string
{
    return ['transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'cash' => 'Tunai'][$method] ?? ucfirst($method);
}

function priority_label(string $priority): string
{
    return $priority === 'wajib' ? 'Wajib' : 'Opsional';
}

function truncate_text(?string $text, int $length = 120): string
{
    $text = trim(strip_tags((string) $text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts));
    if (!$parts) {
        return 'R';
    }
    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function time_ago(?string $dateTime): string
{
    if (!$dateTime) {
        return '-';
    }
    try {
        $then = new DateTimeImmutable($dateTime);
        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $then->getTimestamp();
    } catch (Throwable) {
        return '-';
    }
    if ($diff < 60) {
        return 'Baru saja';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' menit lalu';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' hari lalu';
    }
    return format_date_id($then->format('Y-m-d'));
}


function upload_image(array $file, string $folder, int $maxBytes = 5242880): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Tidak ada file yang dipilih.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal. Kode error: ' . $file['error']);
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Ukuran file maksimal ' . round($maxBytes / 1048576, 1) . ' MB.');
    }
    $tmp = $file['tmp_name'] ?? '';
    if (!$tmp || !is_uploaded_file($tmp)) {
        throw new RuntimeException('File upload tidak valid.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WebP.');
    }
    $folder = trim($folder, '/');
    $dir = UPLOAD_PATH . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder upload tidak bisa dibuat.');
    }
    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    $destination = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Gagal menyimpan file upload.');
    }
    return 'uploads/' . $folder . '/' . $filename;
}

function delete_upload(?string $path): void
{
    if (!$path || preg_match('#^https?://#i', $path)) {
        return;
    }
    $full = ROOT_PATH . '/' . ltrim($path, '/');
    if (is_file($full)) {
        @unlink($full);
    }
}

function phone_wa_link(string $phone, string $message = ''): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits . ($message ? '?text=' . rawurlencode($message) : '');
}

function page_number(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = min(max(1, $page), $pages);
    return [
        'total' => $total,
        'per_page' => $perPage,
        'page' => $page,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
        'has_prev' => $page > 1,
        'has_next' => $page < $pages,
    ];
}

function query_url(array $params): string
{
    $query = array_filter($params, static fn ($value) => $value !== null && $value !== '');
    return '?' . http_build_query($query);
}

function nav_active(string $page): string
{
    $current = basename($_SERVER['PHP_SELF'] ?? '');
    return $current === $page ? 'text-mint-300' : '';
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    foreach (['HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_SCHEME', 'HTTP_X_URL_SCHEME'] as $header) {
        $value = strtolower(trim(explode(',', (string) ($_SERVER[$header] ?? ''))[0] ?? ''));
        if ($value === 'https' || $value === 'on') {
            return true;
        }
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    if (trim((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? '')) === '443') {
        return true;
    }
    return str_contains(strtolower((string) ($_SERVER['HTTP_CF_VISITOR'] ?? '')), '"scheme":"https"');
}

function safe_json_decode(?string $json, mixed $default = []): mixed
{
    if (!$json) {
        return $default;
    }
    $decoded = json_decode($json, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
}

function rating_stars(float $rating): string
{
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $out = str_repeat('★', $full);
    if ($half) {
        $out .= '½';
    }
    return $out ?: '★';
}

function item_availability_text(int $available): string
{
    if ($available <= 0) {
        return 'Stok habis';
    }
    if ($available <= 3) {
        return $available . ' unit tersisa';
    }
    return $available . ' unit tersedia';
}

function role_label(string $role): string
{
    return match ($role) {
        'superadmin' => 'Superadmin',
        'admin' => 'Admin Cabang',
        default => 'Customer',
    };
}

function role_badge_class(string $role): string
{
    return match ($role) {
        'superadmin' => 'badge-dark',
        'admin' => 'badge-mint',
        default => 'badge-neutral',
    };
}

function store_city_line(?array $store): string
{
    if (!$store) {
        return 'Karawang, Jawa Barat';
    }
    return trim(($store['city'] ?? '') . ', ' . ($store['province'] ?? ''), ', ');
}

function store_wa_link(?array $store, string $message = ''): string
{
    $phone = trim((string) ($store['whatsapp'] ?? ''))
        ?: trim((string) ($store['phone'] ?? ''))
        ?: setting('whatsapp', '6281234567890');
    return phone_wa_link($phone, $message);
}

function store_maps_link(?array $store): string
{
    if (!$store) {
        return 'https://maps.google.com/?q=Karawang';
    }
    if (!empty($store['maps_url'])) {
        return (string) $store['maps_url'];
    }
    if ($store['latitude'] !== null && $store['longitude'] !== null) {
        return 'https://maps.google.com/?q=' . $store['latitude'] . ',' . $store['longitude'];
    }
    return 'https://maps.google.com/?q=' . rawurlencode(($store['address'] ?? '') . ' ' . ($store['city'] ?? ''));
}

function build_meta(string $title, string $description = ''): array
{
    return [
        'title' => $title,
        'description' => $description ?: 'Sewa perlengkapan camping, hiking, dan pendakian berkualitas di Karawang. Gear bersih, siap pakai, dan bisa dipesan online.',
    ];
}
