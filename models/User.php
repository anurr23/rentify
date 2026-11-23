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
            'INSERT INTO users (name, email, phone, password_hash, role, address, is_verified, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 1, NOW())'
        );
        $stmt->execute([
            $data['name'],
            strtolower($data['email']),
            $data['phone'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'customer',
            $data['address'] ?? null,
        ]);
        $id = (int) self::pdo()->lastInsertId();
        self::pdo()->prepare('UPDATE users SET member_code = ? WHERE id = ?')
            ->execute([generate_member_code($id), $id]);
        return $id;
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

    public static function stats(int $id): array
    {
        $total = (int) self::fetchValue('SELECT COUNT(*) FROM bookings WHERE user_id = ?', [$id]);
        $active = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('confirmed','preparing','ready','ongoing')",
            [$id]
        );
        $spent = (float) self::fetchValue(
            "SELECT COALESCE(SUM(total), 0) FROM bookings WHERE user_id = ? AND payment_status = 'paid'",
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
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR member_code LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($role !== '') {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE $whereSql", $params);
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT * FROM users WHERE $whereSql ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
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
        if ($user['role'] === 'admin') {
            return ['success' => false, 'message' => 'Akun admin tidak dapat dinonaktifkan.'];
        }
        $new = (int) $user['is_active'] === 1 ? 0 : 1;
        self::pdo()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$new, $id]);
        return ['success' => true, 'message' => $new ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.', 'is_active' => $new];
    }

    public static function customersWithStats(string $query = '', int $page = 1, int $perPage = 12): array
    {
        $where = ["u.role = 'customer'"];
        $params = [];
        if ($query !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.member_code LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT u.*,
                COUNT(b.id) AS total_bookings,
                COALESCE(SUM(CASE WHEN b.payment_status = 'paid' THEN b.total ELSE 0 END), 0) AS total_spent,
                MAX(b.created_at) AS last_booking
             FROM users u
             LEFT JOIN bookings b ON b.user_id = u.id
             WHERE $whereSql
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );
        return ['items' => $rows, 'pagination' => $pagination];
    }
}
