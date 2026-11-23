<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

api_method(['POST']);

$action = (string) input('action', 'login');

/**
 * Hanya izinkan redirect internal.
 */
function auth_safe_redirect(string $target, string $fallback): string
{
    $target = trim($target);
    if ($target === '' || preg_match('#^(https?:)?//#i', $target) || str_contains($target, '\\')) {
        return $fallback;
    }
    if (str_starts_with($target, BASE_PATH)) {
        $target = ltrim(substr($target, strlen(BASE_PATH)), '/');
    }
    if (!preg_match('#^[a-z0-9_\-/]+\.php(\?[^\s]*)?$#i', $target)) {
        return $fallback;
    }
    return $target;
}

if ($action === 'login') {
    $login = (string) input('login', input('email', ''));
    $password = (string) input('password', '');
    $remember = input_bool('remember');
    $redirect = auth_safe_redirect((string) input('redirect', ''), '');
    $throttle = $_SESSION['_login_attempts'] ?? ['count' => 0, 'until' => 0, 'at' => 0];
    if ((int) ($throttle['count'] ?? 0) >= 6 && time() < (int) ($throttle['until'] ?? 0)) {
        json_error('Terlalu banyak percobaan masuk. Coba lagi dalam ' . max(1, (int) ceil(((int) $throttle['until'] - time()) / 60)) . ' menit.', 429);
    }

    $result = attempt_login($login, $password);
    if (!$result['success']) {
        $attempts = (int) ($throttle['count'] ?? 0) + 1;
        $_SESSION['_login_attempts'] = [
            'count' => $attempts,
            'at' => time(),
            'until' => $attempts >= 6 ? time() + 300 : (int) ($throttle['until'] ?? 0),
        ];
        json_error($result['message'], 422);
    }

    unset($_SESSION['_login_attempts']);
    $user = $result['user'];
    login_user($user, $remember);
    $target = $redirect !== '' ? $redirect : login_redirect_for($user);
    log_audit('auth.login', 'user', (int) $user['id']);

    json_success([
        'message' => 'Selamat datang kembali, ' . explode(' ', $user['name'])[0] . '!',
        'redirect' => url($target),
        'role' => $user['role'],
        'name' => $user['name'],
    ]);
}

if ($action === 'register') {
    $name = trim((string) input('name', ''));
    $email = strtolower(trim((string) input('email', '')));
    $phone = trim((string) input('phone', ''));
    $password = (string) input('password', '');
    $confirm = (string) input('password_confirmation', '');
    $address = trim((string) input('address', ''));
    $redirect = auth_safe_redirect((string) input('redirect', ''), 'beranda.php');

    $errors = [];
    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama minimal 3 karakter.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Alamat email tidak valid.';
    }
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
        $errors['phone'] = 'Nomor WhatsApp tidak valid (9–20 digit).';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Kata sandi minimal 8 karakter.';
    }
    if ($password !== $confirm) {
        $errors['password_confirmation'] = 'Konfirmasi kata sandi tidak sama.';
    }

    if (!$errors) {
        $stmt = Database::conn()->prepare('SELECT email, phone FROM users WHERE LOWER(email) = LOWER(?) OR phone = ? LIMIT 1');
        $stmt->execute([$email, $phone]);
        $existing = $stmt->fetch();
        if ($existing) {
            if (strcasecmp((string) $existing['email'], $email) === 0) {
                $errors['email'] = 'Email sudah terdaftar. Silakan masuk.';
            }
            if ((string) $existing['phone'] === $phone) {
                $errors['phone'] = 'Nomor WhatsApp sudah terdaftar.';
            }
        }
    }

    if ($errors) {
        json_error('Periksa kembali data pendaftaran Anda.', 422, $errors);
    }

    try {
        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => 'customer',
            'address' => $address ?: null,
        ]);
        $user = User::find($userId);
        if (!$user) {
            throw new RuntimeException('Akun gagal dibuat.');
        }
        login_user($user);
        notify($userId, 'promo', 'Selamat datang di Rentify!', 'Lengkapi profil dan coba AI Gear Planner untuk petualangan pertamamu.', 'rekomendasi.php');
        log_audit('auth.register', 'user', $userId);
        json_success([
            'message' => 'Akun berhasil dibuat. Selamat bergabung!',
            'redirect' => url($redirect),
            'member_code' => $user['member_code'],
        ], 'OK', 201);
    } catch (Throwable $exception) {
        error_log('Register failed: ' . $exception->getMessage());
        json_error('Pendaftaran gagal. Coba lagi beberapa saat.', 500);
    }
}

if ($action === 'logout') {
    logout_user(false);
    json_success(['message' => 'Anda telah keluar.', 'redirect' => url('index.php')]);
}

json_error('Aksi autentikasi tidak dikenali.', 400);
