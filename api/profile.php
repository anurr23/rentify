<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

api_method(['POST']);
$user = api_require_login();
$action = (string) input('action', 'update');

if ($action === 'update') {
    $name = trim((string) input('name', ''));
    $phone = trim((string) input('phone', ''));
    $address = trim((string) input('address', ''));
    $identityNumber = trim((string) input('identity_number', ''));
    $errors = [];

    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama minimal 3 karakter.';
    }
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
        $errors['phone'] = 'Nomor WhatsApp tidak valid.';
    }
    if ($identityNumber !== '' && !preg_match('/^[0-9]{8,20}$/', $identityNumber)) {
        $errors['identity_number'] = 'Nomor identitas harus 8–20 digit angka.';
    }
    if (!$errors) {
        $stmt = Database::conn()->prepare('SELECT id FROM users WHERE phone = ? AND id <> ? LIMIT 1');
        $stmt->execute([$phone, (int) $user['id']]);
        if ($stmt->fetchColumn()) {
            $errors['phone'] = 'Nomor WhatsApp sudah dipakai akun lain.';
        }
    }
    if ($errors) {
        json_error('Periksa kembali data profil Anda.', 422, $errors);
    }

    $avatarPath = null;
    $identityPath = null;
    try {
        if (!empty($_FILES['avatar']['name'])) {
            $avatarPath = upload_image($_FILES['avatar'], 'avatars', 3145728);
        }
        if (!empty($_FILES['identity_photo']['name'])) {
            $identityPath = upload_image($_FILES['identity_photo'], 'identities', 5242880);
        }
    } catch (Throwable $exception) {
        if ($avatarPath) {
            delete_upload($avatarPath);
        }
        json_error($exception->getMessage());
    }

    User::updateProfile((int) $user['id'], [
        'name' => $name,
        'phone' => $phone,
        'address' => $address ?: null,
        'identity_number' => $identityNumber ?: null,
        'identity_photo' => $identityPath,
        'avatar' => $avatarPath,
    ]);

    if ($avatarPath && !empty($user['avatar'])) {
        delete_upload($user['avatar']);
    }
    if ($identityPath && !empty($user['identity_photo'])) {
        delete_upload($user['identity_photo']);
    }

    $fresh = User::find((int) $user['id']);
    unset($_SESSION['user_name'], $_SESSION['user_avatar']);
    $_SESSION['user_name'] = $fresh['name'] ?? $name;

    log_audit('profile.update', 'user', (int) $user['id']);
    json_success([
        'message' => 'Profil berhasil diperbarui.',
        'name' => $fresh['name'] ?? $name,
        'initials' => initials($fresh['name'] ?? $name),
        'avatar_url' => $fresh && !empty($fresh['avatar']) ? upload_url($fresh['avatar']) : null,
    ]);
}

if ($action === 'password') {
    $current = (string) input('current_password', '');
    $new = (string) input('new_password', '');
    $confirm = (string) input('new_password_confirmation', '');
    if ($new !== $confirm) {
        json_error('Konfirmasi kata sandi baru tidak sama.', 422, ['new_password_confirmation' => 'Konfirmasi tidak sama.']);
    }
    $result = User::changePassword((int) $user['id'], $current, $new);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('profile.password', 'user', (int) $user['id']);
    json_success(['message' => $result['message']]);
}

json_error('Aksi profil tidak dikenali.', 400);
