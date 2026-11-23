<?php
declare(strict_types=1);

const REMEMBER_COOKIE = 'rentify_remember';
const REMEMBER_DAYS = 30;

function auth_user(bool $force = false): ?array
{
    static $cached = null;
    static $loaded = false;
    if ($loaded && !$force && $cached !== null) {
        return $cached;
    }
    $loaded = true;
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        $cached = null;
        return null;
    }
    try {
        $stmt = Database::conn()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || (int) $user['is_active'] !== 1) {
            logout_user(false);
            $cached = null;
            return null;
        }
        $cached = $user;
        return $user;
    } catch (Throwable) {
        $cached = null;
        return null;
    }
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function is_admin(): bool
{
    $user = auth_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    if (is_ajax()) {
        json_error('Silakan masuk terlebih dahulu.', 401, [], ['login_required' => true]);
    }
    flash('warning', 'Silakan masuk untuk melanjutkan.');
    $redirect = $_SERVER['REQUEST_URI'] ?? 'beranda.php';
    redirect('login.php?redirect=' . rawurlencode($redirect));
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        if (is_ajax()) {
            json_error('Akses khusus admin.', 403);
        }
        redirect('unauthorized.php');
    }
    $user = auth_user();
    if ($user && (int) $user['is_active'] !== 1) {
        logout_user();
        redirect('login.php');
    }
}

function api_require_login(): array
{
    $user = auth_user();
    if (!$user) {
        json_error('Silakan masuk terlebih dahulu.', 401, [], ['login_required' => true]);
    }
    return $user;
}

function api_require_admin(): array
{
    $user = api_require_login();
    if ($user['role'] !== 'admin') {
        json_error('Akses khusus admin.', 403);
    }
    return $user;
}

function login_user(array $user, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    $_SESSION['member_code'] = $user['member_code'] ?? null;
    $_SESSION['login_at'] = time();

    try {
        $stmt = Database::conn()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
    } catch (Throwable) {
        // Tidak menghalangi login.
    }

    if ($remember) {
        set_remember_cookie((int) $user['id']);
    }
}

function set_remember_cookie(int $userId): void
{
    try {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + REMEMBER_DAYS * 86400);
        $stmt = Database::conn()->prepare('INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $hash, $expiresAt]);
        setcookie(REMEMBER_COOKIE, $token, [
            'expires' => time() + REMEMBER_DAYS * 86400,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } catch (Throwable) {
        // Remember device opsional.
    }
}

function attempt_remember_login(): void
{
    if (is_logged_in() || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }
    $token = (string) $_COOKIE[REMEMBER_COOKIE];
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        clear_remember_cookie();
        return;
    }
    try {
        $hash = hash('sha256', $token);
        $stmt = Database::conn()->prepare(
            'SELECT u.* FROM remember_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.is_active = 1 LIMIT 1'
        );
        $stmt->execute([$hash]);
        $user = $stmt->fetch();
        if ($user) {
            Database::conn()->prepare('DELETE FROM remember_tokens WHERE token_hash = ?')->execute([$hash]);
            login_user($user, true);
            return;
        }
    } catch (Throwable) {
        // Abaikan token yang tidak valid.
    }
    clear_remember_cookie();
}

function clear_remember_cookie(): void
{
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        setcookie(REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[REMEMBER_COOKIE]);
    }
}

function logout_user(bool $redirect = true): void
{
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        try {
            $hash = hash('sha256', (string) $_COOKIE[REMEMBER_COOKIE]);
            Database::conn()->prepare('DELETE FROM remember_tokens WHERE token_hash = ?')->execute([$hash]);
        } catch (Throwable) {
            // Abaikan.
        }
    }
    clear_remember_cookie();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    if ($redirect) {
        redirect('index.php');
    }
}

function attempt_login(string $login, string $password): array
{
    $login = trim($login);
    if ($login === '' || $password === '') {
        return ['success' => false, 'message' => 'Email/member ID dan kata sandi wajib diisi.'];
    }

    try {
        $stmt = Database::conn()->prepare(
            'SELECT * FROM users
             WHERE (LOWER(email) = LOWER(?) OR phone = ? OR member_code = ?)
             LIMIT 1'
        );
        $stmt->execute([$login, $login, strtoupper($login)]);
        $user = $stmt->fetch();
    } catch (Throwable) {
        return ['success' => false, 'message' => 'Terjadi kesalahan pada server.'];
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Email/member ID atau kata sandi salah.'];
    }
    if ((int) $user['is_active'] !== 1) {
        return ['success' => false, 'message' => 'Akun Anda sedang dinonaktifkan. Hubungi admin Rentify.'];
    }

    $user['last_login_at'] = date('Y-m-d H:i:s');
    return ['success' => true, 'user' => $user];
}

function login_redirect_for(array $user): string
{
    return $user['role'] === 'admin' ? 'admin/dashboard.php' : 'beranda.php';
}

attempt_remember_login();
