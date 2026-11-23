<?php
declare(strict_types=1);

/**
 * Migrasi multi-cabang Rentify.
 * Aman dijalankan berulang pada database lama maupun baru.
 *
 * CLI: php database/migrate.php
 * Atau dipanggil dari install.php: rentify_migrate($pdo)
 */

if (!function_exists('rentify_migrate_has_column')) {
    function rentify_migrate_has_column(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('rentify_migrate_has_index')) {
    function rentify_migrate_has_index(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('rentify_migrate_has_fk')) {
    function rentify_migrate_has_fk(PDO $pdo, string $table, string $constraint): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY"'
        );
        $stmt->execute([$table, $constraint]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('rentify_migrate_stores')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function rentify_migrate_stores(): array
    {
        return [
            [
                'code' => 'RNT-KRW-01', 'name' => 'Rentify Karawang Kota', 'slug' => 'rentify-karawang-kota',
                'address' => 'Jl. Galuh Mas Raya No. 8, Karawang Barat, Jawa Barat', 'district' => 'Karawang Barat',
                'city' => 'Karawang', 'province' => 'Jawa Barat', 'postal_code' => '41361',
                'phone' => '081200000011', 'whatsapp' => '6281200000011', 'email' => 'karawang@rentify.id',
                'latitude' => -6.301, 'longitude' => 107.305, 'maps_url' => 'https://maps.google.com/?q=-6.301,107.305',
                'opening_hours' => 'Setiap hari, 08.00-21.00 WIB',
                'description' => 'Basecamp utama Rentify di jantung Karawang. Stok gear terlengkap, area cek gear, dan parkir luas.',
                'image' => 'uploads/stores/karawang-kota.jpg', 'delivery_fee' => 15000, 'is_default' => 1, 'sort_order' => 1,
            ],
            [
                'code' => 'RNT-KRW-02', 'name' => 'Rentify Karawang Selatan', 'slug' => 'rentify-karawang-selatan',
                'address' => 'Jl. Raya Sanggabuana No. 21, Karawang Selatan, Jawa Barat', 'district' => 'Karawang Selatan',
                'city' => 'Karawang', 'province' => 'Jawa Barat', 'postal_code' => '41371',
                'phone' => '081200000012', 'whatsapp' => '6281200000012', 'email' => 'sanggabuana@rentify.id',
                'latitude' => -6.392, 'longitude' => 107.301, 'maps_url' => 'https://maps.google.com/?q=-6.392,107.301',
                'opening_hours' => 'Setiap hari, 07.00-20.00 WIB',
                'description' => 'Pos terdepan menuju Gunung Sanggabuana. Cocok untuk pendaki yang ingin berangkat pagi buta.',
                'image' => 'uploads/stores/karawang-selatan.jpg', 'delivery_fee' => 15000, 'is_default' => 0, 'sort_order' => 2,
            ],
            [
                'code' => 'RNT-KRW-03', 'name' => 'Rentify Cikampek', 'slug' => 'rentify-cikampek',
                'address' => 'Jl. Raya Cikampek No. 45, Cikampek, Karawang, Jawa Barat', 'district' => 'Cikampek',
                'city' => 'Karawang', 'province' => 'Jawa Barat', 'postal_code' => '41373',
                'phone' => '081200000013', 'whatsapp' => '6281200000013', 'email' => 'cikampek@rentify.id',
                'latitude' => -6.402, 'longitude' => 107.456, 'maps_url' => 'https://maps.google.com/?q=-6.402,107.456',
                'opening_hours' => 'Setiap hari, 08.00-20.00 WIB',
                'description' => 'Cabang timur Karawang, dekat gerbang tol Cikampek. Praktis untuk trip ke Purwakarta dan Bandung.',
                'image' => 'uploads/stores/cikampek.jpg', 'delivery_fee' => 18000, 'is_default' => 0, 'sort_order' => 3,
            ],
            [
                'code' => 'RNT-BDG-01', 'name' => 'Rentify Bandung', 'slug' => 'rentify-bandung',
                'address' => 'Jl. Ir. H. Juanda No. 112, Dago, Bandung, Jawa Barat', 'district' => 'Coblong',
                'city' => 'Bandung', 'province' => 'Jawa Barat', 'postal_code' => '40135',
                'phone' => '081200000014', 'whatsapp' => '6281200000014', 'email' => 'bandung@rentify.id',
                'latitude' => -6.9175, 'longitude' => 107.6191, 'maps_url' => 'https://maps.google.com/?q=-6.9175,107.6191',
                'opening_hours' => 'Setiap hari, 08.00-21.00 WIB',
                'description' => 'Basecamp Bandung untuk akses cepat ke Gunung Puntang, Papandayan, dan Ranca Upas.',
                'image' => 'uploads/stores/bandung.jpg', 'delivery_fee' => 25000, 'is_default' => 0, 'sort_order' => 4,
            ],
            [
                'code' => 'RNT-JKT-01', 'name' => 'Rentify Jakarta', 'slug' => 'rentify-jakarta',
                'address' => 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan', 'district' => 'Kebayoran Baru',
                'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'postal_code' => '12190',
                'phone' => '081200000015', 'whatsapp' => '6281200000015', 'email' => 'jakarta@rentify.id',
                'latitude' => -6.2088, 'longitude' => 106.8456, 'maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
                'opening_hours' => 'Setiap hari, 08.00-21.00 WIB',
                'description' => 'Cabang Jakarta dengan layanan antar-jemput area Jabodetabek untuk trip akhir pekan.',
                'image' => 'uploads/stores/jakarta.jpg', 'delivery_fee' => 30000, 'is_default' => 0, 'sort_order' => 5,
            ],
            [
                'code' => 'RNT-BGR-01', 'name' => 'Rentify Bogor', 'slug' => 'rentify-bogor',
                'address' => 'Jl. Pajajaran No. 88, Bogor Tengah, Jawa Barat', 'district' => 'Bogor Tengah',
                'city' => 'Bogor', 'province' => 'Jawa Barat', 'postal_code' => '16143',
                'phone' => '081200000016', 'whatsapp' => '6281200000016', 'email' => 'bogor@rentify.id',
                'latitude' => -6.595, 'longitude' => 106.8166, 'maps_url' => 'https://maps.google.com/?q=-6.595,106.8166',
                'opening_hours' => 'Setiap hari, 07.00-21.00 WIB',
                'description' => 'Gerbang menuju Gunung Salak, Gede, dan Pangrango. Gear dingin selalu siap.',
                'image' => 'uploads/stores/bogor.jpg', 'delivery_fee' => 25000, 'is_default' => 0, 'sort_order' => 6,
            ],
            [
                'code' => 'RNT-SBY-01', 'name' => 'Rentify Surabaya', 'slug' => 'rentify-surabaya',
                'address' => 'Jl. Basuki Rahmat No. 101, Surabaya, Jawa Timur', 'district' => 'Genteng',
                'city' => 'Surabaya', 'province' => 'Jawa Timur', 'postal_code' => '60271',
                'phone' => '081200000017', 'whatsapp' => '6281200000017', 'email' => 'surabaya@rentify.id',
                'latitude' => -7.2575, 'longitude' => 112.7521, 'maps_url' => 'https://maps.google.com/?q=-7.2575,112.7521',
                'opening_hours' => 'Setiap hari, 08.00-21.00 WIB',
                'description' => 'Cabang Jawa Timur untuk pendakian Semeru, Bromo, dan trip outdoor lainnya.',
                'image' => 'uploads/stores/surabaya.jpg', 'delivery_fee' => 35000, 'is_default' => 0, 'sort_order' => 7,
            ],
        ];
    }
}

if (!function_exists('rentify_migrate')) {
    /**
     * @return array{success:bool, messages:array<int,string>}
     */
    function rentify_migrate(PDO $pdo): array
    {
        $messages = [];
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS stores (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                code VARCHAR(24) NOT NULL,
                name VARCHAR(140) NOT NULL,
                slug VARCHAR(160) NOT NULL,
                address TEXT NOT NULL,
                district VARCHAR(100) DEFAULT NULL,
                city VARCHAR(100) NOT NULL,
                province VARCHAR(100) NOT NULL,
                postal_code VARCHAR(10) DEFAULT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                whatsapp VARCHAR(30) DEFAULT NULL,
                email VARCHAR(190) DEFAULT NULL,
                latitude DECIMAL(10,7) DEFAULT NULL,
                longitude DECIMAL(10,7) DEFAULT NULL,
                maps_url VARCHAR(500) DEFAULT NULL,
                opening_hours VARCHAR(160) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                image VARCHAR(255) DEFAULT NULL,
                delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_stores_code (code),
                UNIQUE KEY uq_stores_slug (slug),
                KEY idx_stores_active (is_active, sort_order),
                KEY idx_stores_city (city)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $messages[] = 'Tabel stores siap.';

        $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if ($roleColumn && !str_contains((string) $roleColumn['Type'], 'superadmin')) {
            $pdo->exec("ALTER TABLE users MODIFY role ENUM('superadmin','admin','customer') NOT NULL DEFAULT 'customer'");
            $messages[] = 'Role superadmin ditambahkan.';
        }

        $userColumns = [
            'store_id' => 'BIGINT UNSIGNED DEFAULT NULL AFTER role',
            'preferred_store_id' => 'BIGINT UNSIGNED DEFAULT NULL AFTER store_id',
            'last_latitude' => 'DECIMAL(10,7) DEFAULT NULL AFTER address',
            'last_longitude' => 'DECIMAL(10,7) DEFAULT NULL AFTER last_latitude',
            'last_location_at' => 'DATETIME DEFAULT NULL AFTER last_longitude',
        ];
        foreach ($userColumns as $column => $definition) {
            if (!rentify_migrate_has_column($pdo, 'users', $column)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                $messages[] = "Kolom users.$column ditambahkan.";
            }
        }

        if (!rentify_migrate_has_column($pdo, 'pickup_points', 'store_id')) {
            $pdo->exec('ALTER TABLE pickup_points ADD COLUMN store_id BIGINT UNSIGNED DEFAULT NULL AFTER id');
            $messages[] = 'Kolom pickup_points.store_id ditambahkan.';
        }
        if (!rentify_migrate_has_column($pdo, 'bookings', 'store_id')) {
            $pdo->exec('ALTER TABLE bookings ADD COLUMN store_id BIGINT UNSIGNED DEFAULT NULL AFTER user_id');
            $messages[] = 'Kolom bookings.store_id ditambahkan.';
        }

        $insertStore = $pdo->prepare(
            'INSERT INTO stores (code, name, slug, address, district, city, province, postal_code, phone, whatsapp, email,
                latitude, longitude, maps_url, opening_hours, description, image, delivery_fee, is_active, is_default, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())'
        );
        $checkStore = $pdo->prepare('SELECT id FROM stores WHERE code = ? LIMIT 1');
        foreach (rentify_migrate_stores() as $store) {
            $checkStore->execute([$store['code']]);
            if ($checkStore->fetchColumn()) {
                continue;
            }
            $insertStore->execute([
                $store['code'], $store['name'], $store['slug'], $store['address'], $store['district'], $store['city'],
                $store['province'], $store['postal_code'], $store['phone'], $store['whatsapp'], $store['email'],
                $store['latitude'], $store['longitude'], $store['maps_url'], $store['opening_hours'],
                $store['description'], $store['image'], $store['delivery_fee'], $store['is_default'], $store['sort_order'],
            ]);
            $messages[] = 'Toko ' . $store['name'] . ' ditambahkan.';
        }

        $defaultId = (int) $pdo->query('SELECT id FROM stores ORDER BY is_default DESC, sort_order ASC, id ASC LIMIT 1')->fetchColumn();
        if ($defaultId > 0) {
            $hasDefault = (int) $pdo->query('SELECT COUNT(*) FROM stores WHERE is_default = 1')->fetchColumn();
            if ($hasDefault === 0) {
                $pdo->prepare('UPDATE stores SET is_default = 1 WHERE id = ?')->execute([$defaultId]);
            }
        }

        $storeIds = [];
        foreach ($pdo->query('SELECT id, code FROM stores')->fetchAll() as $row) {
            $storeIds[$row['code']] = (int) $row['id'];
        }
        $karawangId = $storeIds['RNT-KRW-01'] ?? $defaultId;

        if ($defaultId > 0) {
            $pdo->prepare("UPDATE users SET role = 'superadmin', store_id = NULL WHERE LOWER(email) = 'admin@rentify.id'")
                ->execute();
            $pdo->prepare("UPDATE users SET store_id = ? WHERE role = 'admin' AND store_id IS NULL")
                ->execute([$karawangId]);
            $pdo->prepare("UPDATE users SET preferred_store_id = ? WHERE role = 'customer' AND preferred_store_id IS NULL")
                ->execute([$defaultId]);
        }

        $staff = [
            ['Asep Nugraha', 'asep@rentify.id', '081200000021', 'RNT-STAFF-01', 'RNT-KRW-01'],
            ['Dewi Lestari', 'dewi@rentify.id', '081200000022', 'RNT-STAFF-02', 'RNT-KRW-02'],
            ['Rizky Pratama', 'rizky@rentify.id', '081200000023', 'RNT-STAFF-03', 'RNT-KRW-03'],
            ['Dimas Anggara', 'dimas@rentify.id', '081200000024', 'RNT-STAFF-04', 'RNT-BDG-01'],
            ['Nadia Putri', 'nadia@rentify.id', '081200000025', 'RNT-STAFF-05', 'RNT-JKT-01'],
            ['Bayu Firmansyah', 'bayu@rentify.id', '081200000026', 'RNT-STAFF-06', 'RNT-BGR-01'],
            ['Sinta Maharani', 'sinta@rentify.id', '081200000027', 'RNT-STAFF-07', 'RNT-SBY-01'],
        ];
        $findUser = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $insertUser = $pdo->prepare(
            "INSERT INTO users (member_code, name, email, phone, password_hash, role, store_id, is_verified, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, 'admin', ?, 1, 1, NOW())"
        );
        $assignUser = $pdo->prepare("UPDATE users SET role = 'admin', store_id = ? WHERE id = ?");
        foreach ($staff as [$name, $email, $phone, $memberCode, $storeCode]) {
            $findUser->execute([$email]);
            $userId = (int) $findUser->fetchColumn();
            if (!$userId) {
                $insertUser->execute([
                    $memberCode, $name, strtolower($email), $phone,
                    password_hash('password', PASSWORD_DEFAULT), $storeIds[$storeCode] ?? $karawangId,
                ]);
                $messages[] = 'Admin cabang ' . $name . ' ditambahkan.';
                continue;
            }
            if (isset($storeIds[$storeCode])) {
                $assignUser->execute([$storeIds[$storeCode], $userId]);
            }
        }

        if (!rentify_migrate_has_fk($pdo, 'pickup_points', 'fk_pickup_store')) {
            $pdo->exec('ALTER TABLE pickup_points ADD CONSTRAINT fk_pickup_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
            $messages[] = 'Relasi pickup_points.store_id ditambahkan.';
        }
        if (!rentify_migrate_has_fk($pdo, 'bookings', 'fk_bookings_store')) {
            $pdo->exec('ALTER TABLE bookings ADD CONSTRAINT fk_bookings_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL');
            $messages[] = 'Relasi bookings.store_id ditambahkan.';
        }
        if (!rentify_migrate_has_fk($pdo, 'users', 'fk_users_store')) {
            $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL');
            $messages[] = 'Relasi users.store_id ditambahkan.';
        }
        if (!rentify_migrate_has_fk($pdo, 'users', 'fk_users_preferred_store')) {
            $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_preferred_store FOREIGN KEY (preferred_store_id) REFERENCES stores (id) ON DELETE SET NULL');
            $messages[] = 'Relasi users.preferred_store_id ditambahkan.';
        }
        foreach ([
            ['users', 'idx_users_store', 'store_id'],
            ['users', 'idx_users_preferred_store', 'preferred_store_id'],
            ['pickup_points', 'idx_pickup_store', 'store_id, is_active'],
            ['bookings', 'idx_bookings_store', 'store_id, created_at'],
        ] as [$table, $index, $columns]) {
            if (!rentify_migrate_has_index($pdo, $table, $index)) {
                $pdo->exec("ALTER TABLE $table ADD KEY $index ($columns)");
            }
        }

        $point1 = (int) $pdo->query("SELECT id FROM stores WHERE code = 'RNT-KRW-01' LIMIT 1")->fetchColumn();
        $point2 = (int) $pdo->query("SELECT id FROM stores WHERE code = 'RNT-KRW-02' LIMIT 1")->fetchColumn();
        if ($point1 > 0) {
            $pdo->prepare("UPDATE pickup_points SET store_id = ? WHERE store_id IS NULL AND name LIKE '%Karawang Kota%'")
                ->execute([$point1]);
            $pdo->prepare("UPDATE pickup_points SET is_active = 0, store_id = ? WHERE store_id IS NULL AND name LIKE '%Antar%'")
                ->execute([$point1]);
        }
        if ($point2 > 0) {
            $pdo->prepare("UPDATE pickup_points SET store_id = ? WHERE store_id IS NULL AND (name LIKE '%Sanggabuana%' OR name LIKE '%Karawang Selatan%')")
                ->execute([$point2]);
        }

        foreach ($pdo->query('SELECT * FROM stores ORDER BY id ASC')->fetchAll() as $store) {
            $pointStmt = $pdo->prepare('SELECT COUNT(*) FROM pickup_points WHERE store_id = ?');
            $pointStmt->execute([(int) $store['id']]);
            if ((int) $pointStmt->fetchColumn() === 0) {
                $pdo->prepare(
                    'INSERT INTO pickup_points (store_id, name, address, hours, maps_url, latitude, longitude, is_active, sort_order, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                )->execute([
                    (int) $store['id'], $store['name'], $store['address'], $store['opening_hours'], $store['maps_url'],
                    $store['latitude'], $store['longitude'], $store['is_active'], $store['sort_order'],
                ]);
            } else {
                $pdo->prepare(
                    'UPDATE pickup_points SET name = ?, address = ?, hours = ?, maps_url = ?, latitude = ?, longitude = ?, is_active = ?
                     WHERE store_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1'
                )->execute([
                    $store['name'], $store['address'], $store['opening_hours'], $store['maps_url'],
                    $store['latitude'], $store['longitude'], $store['is_active'], (int) $store['id'],
                ]);
            }
        }

        if ($defaultId > 0) {
            $pdo->prepare(
                'UPDATE bookings b JOIN pickup_points pp ON pp.id = b.pickup_point_id
                 SET b.store_id = pp.store_id WHERE b.store_id IS NULL AND pp.store_id IS NOT NULL'
            )->execute();
            $pdo->prepare('UPDATE bookings SET store_id = ? WHERE store_id IS NULL')->execute([$defaultId]);
        }

        return ['success' => true, 'messages' => $messages];
    }
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    require_once dirname(__DIR__) . '/config/config.php';
    try {
        $result = rentify_migrate(Database::conn());
        echo "Migrasi Rentify selesai.\n";
        foreach ($result['messages'] as $message) {
            echo ' - ' . $message . "\n";
        }
        echo 'Total toko: ' . Store::countActive() . " aktif.\n";
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Migrasi gagal: ' . $exception->getMessage() . "\n");
        exit(1);
    }
}
