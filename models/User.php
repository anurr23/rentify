<?php
declare(strict_types=1);

class User extends Model
{
    public static function find(int $id): ?array
    {
        $user = self::fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $user = self::fetchOne('SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1', [$email]);
        return $user ?: null;
    }

    public static function findByLogin(string $login): ?array
    {
        $user = self::fetchOne(
            'SELECT * FROM users WHERE LOWER(email) = LOWER(?) OR phone = ? OR member_code = ? LIMIT 1',
            [$login, $login, strtoupper($login)]
        );
        return $user ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role, store_id, address, is_verified, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, NOW())'
        );
        $stmt->execute([
            $data['name'],
            strtolower($data['email']),
            $data['phone'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'customer',
            $data['store_id'] ?? null,
            $data['address'] ?? null,
        ]);
        $id = (int) self::pdo()->lastInsertId();
        self::pdo()->prepare('UPDATE users SET member_code = ? WHERE id = ?')
            ->execute([generate_member_code($id), $id]);
        return $id;
    }

    public static function saveAdmin(?int $id, array $data): array
    {
        $db = self::pdo();
        $role = in_array($data['role'] ?? '', ['superadmin', 'admin', 'customer'], true) ? $data['role'] : 'customer';
        $storeId = $role === 'admin' ? (int) ($data['store_id'] ?? 0) : null;
        if ($role === 'admin' && $storeId <= 0) {
            return ['success' => false, 'message' => 'Admin cabang wajib memiliki toko.'];
        }
        if ($storeId !== null && !Store::find($storeId)) {
            return ['success' => false, 'message' => 'Toko cabang tidak ditemukan.'];
        }

        $email = strtolower(trim((string) $data['email']));
        $phone = trim((string) $data['phone']);
        $name = trim((string) $data['name']);
        $password = (string) ($data['password'] ?? '');

        $stmt = $db->prepare('SELECT id FROM users WHERE (LOWER(email) = LOWER(?) OR phone = ?) AND id <> ? LIMIT 1');
        $stmt->execute([$email, $phone, $id ?? 0]);
        if ($stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Email atau nomor WhatsApp sudah dipakai pengguna lain.'];
        }

        try {
            if ($id) {
                $sets = ['name = ?', 'email = ?', 'phone = ?', 'role = ?', 'store_id = ?', 'is_active = ?'];
                $params = [$name, $email, $phone, $role, $storeId, (int) ($data['is_active'] ?? 1)];
                if ($password !== '') {
                    $sets[] = 'password_hash = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $params[] = $id;
                $db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
                if ($role !== 'customer') {
                    $db->prepare('UPDATE users SET is_verified = 1 WHERE id = ?')->execute([$id]);
                }
                return ['success' => true, 'id' => $id, 'message' => 'Pengguna diperbarui.'];
            }
            if ($password === '') {
                return ['success' => false, 'message' => 'Kata sandi awal wajib diisi.'];
            }
            $newId = self::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'role' => $role,
                'store_id' => $storeId,
            ]);
            if ($role !== 'customer') {
                $db->prepare('UPDATE users SET is_verified = 1 WHERE id = ?')->execute([$newId]);
            }
            if ((int) ($data['is_active'] ?? 1) !== 1) {
                $db->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$newId]);
            }
            return ['success' => true, 'id' => $newId, 'message' => 'Pengguna ditambahkan.'];
        } catch (Throwable $exception) {
            error_log('User save failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan pengguna.'];
        }
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE users SET name = ?, phone = ?, address = ?, identity_number = ?,
                identity_photo = COALESCE(?, identity_photo), avatar = COALESCE(?, avatar)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['address'] ?? null,
            $data['identity_number'] ?? null,
            $data['identity_photo'] ?? null,
            $data['avatar'] ?? null,
            $id,
        ]);
    }

    public static function changePassword(int $id, string $current, string $new): array
    {
        $user = self::find($id);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Kata sandi saat ini salah.'];
        }
        if (strlen($new) < 8) {
            return ['success' => false, 'message' => 'Kata sandi minimal 8 karakter.'];
        }
        self::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
        return ['success' => true, 'message' => 'Kata sandi berhasil diperbarui.'];
    }

    public static function stats(int $id, ?int $storeId = null): array
    {
        $storeWhere = $storeId !== null ? ' AND store_id = ' . (int) $storeId : '';
        $total = (int) self::fetchValue('SELECT COUNT(*) FROM bookings WHERE user_id = ?' . $storeWhere, [$id]);
        $active = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('confirmed','preparing','ready','ongoing')" . $storeWhere,
            [$id]
        );
        $spent = (float) self::fetchValue(
            "SELECT COALESCE(SUM(total), 0) FROM bookings WHERE user_id = ? AND payment_status = 'paid'" . $storeWhere,
            [$id]
        );
        $wishlist = (int) self::fetchValue('SELECT COUNT(*) FROM wishlists WHERE user_id = ?', [$id]);
        return [
            'bookings' => $total,
            'active' => $active,
            'spent' => $spent,
            'wishlist' => $wishlist,
        ];
    }

    public static function search(string $query = '', string $role = '', int $page = 1, int $perPage = 12): array
    {
        $where = ['1=1'];
        $params = [];
        if ($query !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.member_code LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($role !== '') {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT u.*, s.name AS store_name, s.city AS store_city
             FROM users u LEFT JOIN stores s ON s.id = u.store_id
             WHERE $whereSql ORDER BY u.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );
        return ['items' => $rows, 'pagination' => $pagination];
    }

    public static function toggleActive(int $id): array
    {
        $user = self::find($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
        }
        if ($user['role'] === 'superadmin') {
            return ['success' => false, 'message' => 'Akun superadmin tidak dapat dinonaktifkan.'];
        }
        $new = (int) $user['is_active'] === 1 ? 0 : 1;
        self::pdo()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$new, $id]);
        return ['success' => true, 'message' => $new ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.', 'is_active' => $new];
    }

    public static function customersWithStats(string $query = '', int $page = 1, int $perPage = 12, ?int $storeId = null): array
    {
        $where = ["u.role = 'customer'"];
        $params = [];
        if ($query !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.member_code LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($storeId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM bookings bx WHERE bx.user_id = u.id AND bx.store_id = ?)';
            $params[] = $storeId;
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
        $pagination = paginate($total, $perPage, $page);

        $storeFilter = $storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '';
        $selectParams = $params;
        $rows = self::fetchAll(
            "SELECT u.*,
                (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id$storeFilter) AS total_bookings,
                (SELECT COALESCE(SUM(b.total), 0) FROM bookings b WHERE b.user_id = u.id AND b.payment_status = 'paid'$storeFilter) AS total_spent,
                (SELECT MAX(b.created_at) FROM bookings b WHERE b.user_id = u.id$storeFilter) AS last_booking
             FROM users u
             WHERE $whereSql
             ORDER BY u.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $selectParams
        );
        return ['items' => $rows, 'pagination' => $pagination];
    }
}
