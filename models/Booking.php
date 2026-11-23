<?php
declare(strict_types=1);

class Booking extends Model
{
    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'preparing', 'ready', 'ongoing'];
    public const FINAL_STATUSES = ['returned', 'completed', 'cancelled'];

    public static function createFromCart(int $userId, array $data): array
    {
        $validation = cart_validate();
        if (!$validation['valid']) {
            return ['success' => false, 'message' => implode(' ', $validation['errors']), 'errors' => $validation['errors']];
        }
        $cart = $validation['cart'];
        $dates = $cart['dates'];
        $start = !empty($data['start_date']) ? (string) $data['start_date'] : $dates['start'];
        $end = !empty($data['end_date']) ? (string) $data['end_date'] : $dates['end'];
        $pickupMethod = ($data['pickup_method'] ?? 'pickup') === 'delivery' ? 'delivery' : 'pickup';
        $pickupPointId = !empty($data['pickup_point_id']) ? (int) $data['pickup_point_id'] : null;
        $deliveryAddress = $pickupMethod === 'delivery' ? trim((string) ($data['delivery_address'] ?? '')) : null;

        if ($pickupMethod === 'delivery' && !$deliveryAddress) {
            return ['success' => false, 'message' => 'Alamat pengantaran wajib diisi.'];
        }
        if ($pickupMethod === 'pickup' && !$pickupPointId) {
            return ['success' => false, 'message' => 'Pilih titik pengambilan terlebih dahulu.'];
        }
        if (!$start || !$end || $end < $start) {
            return ['success' => false, 'message' => 'Tanggal sewa tidak valid.'];
        }
        if ($start < today()) {
            return ['success' => false, 'message' => 'Tanggal mulai tidak boleh di masa lalu.'];
        }

        $db = self::pdo();
        $db->beginTransaction();
        try {
            $pickupPoint = null;
            if ($pickupMethod === 'pickup') {
                $stmt = $db->prepare('SELECT * FROM pickup_points WHERE id = ? AND is_active = 1');
                $stmt->execute([$pickupPointId]);
                $pickupPoint = $stmt->fetch();
                if (!$pickupPoint) {
                    throw new RuntimeException('Titik pengambilan tidak tersedia.');
                }
            }

            $recheck = [];
            foreach ($cart['items'] as $line) {
                if ($line['variant_id']) {
                    $stockStmt = $db->prepare('SELECT stock_total FROM item_variants WHERE id = ? AND item_id = ? FOR UPDATE');
                    $stockStmt->execute([$line['variant_id'], $line['item_id']]);
                } else {
                    $stockStmt = $db->prepare('SELECT stock_total FROM items WHERE id = ? FOR UPDATE');
                    $stockStmt->execute([$line['item_id']]);
                }
                $stock = $stockStmt->fetchColumn();
                if ($stock === false) {
                    throw new RuntimeException('Item ' . $line['name'] . ' tidak ditemukan.');
                }
                $booked = item_booked_qty($db, $line['item_id'], $line['variant_id'], $start, $end);
                if ((int) $line['qty'] > ((int) $stock - $booked)) {
                    throw new RuntimeException('Stok ' . $line['name'] . ' tidak cukup untuk tanggal ini.');
                }
                $recheck[] = $line;
            }

            $subtotal = 0.0;
            $deposit = 0.0;
            $duration = duration_days($start, $end);
            foreach ($recheck as $line) {
                $subtotal += (float) $line['price_per_day'] * (int) $line['qty'] * $duration;
                $deposit += (float) $line['deposit'];
            }
            $deliveryFee = $pickupMethod === 'delivery' ? (float) setting('delivery_fee', '15000') : 0.0;
            $discount = max(0, (float) ($data['discount'] ?? 0));
            $total = max(0, $subtotal + $deposit + $deliveryFee - $discount);
            $holdHours = max(1, (int) setting('hold_hours', '24'));

            $code = generate_booking_code();
            for ($i = 0; $i < 5; $i++) {
                $exists = $db->prepare('SELECT 1 FROM bookings WHERE code = ?');
                $exists->execute([$code]);
                if (!$exists->fetchColumn()) {
                    break;
                }
                $code = generate_booking_code();
            }

            $stmt = $db->prepare(
                "INSERT INTO bookings (code, user_id, pickup_point_id, start_date, end_date, duration_days,
                    pickup_method, delivery_address, subtotal, deposit_total, delivery_fee, discount, total,
                    status, payment_status, notes, hold_expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())"
            );
            $stmt->execute([
                $code, $userId, $pickupPointId, $start, $end, $duration,
                $pickupMethod, $deliveryAddress, $subtotal, $deposit, $deliveryFee, $discount, $total,
                trim((string) ($data['notes'] ?? '')) ?: null, $holdHours,
            ]);
            $bookingId = (int) $db->lastInsertId();

            $insertItem = $db->prepare(
                'INSERT INTO booking_items (booking_id, item_id, variant_id, item_name, variant_label, qty,
                    price_per_day, duration_days, subtotal, deposit, start_date, end_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($recheck as $line) {
                $lineSubtotal = (float) $line['price_per_day'] * (int) $line['qty'] * $duration;
                $insertItem->execute([
                    $bookingId, $line['item_id'], $line['variant_id'], $line['name'], $line['variant_label'],
                    $line['qty'], $line['price_per_day'], $duration, $lineSubtotal, $line['deposit'], $start, $end,
                ]);
                $db->prepare('UPDATE items SET rented_count = rented_count + ? WHERE id = ?')
                    ->execute([(int) $line['qty'], $line['item_id']]);
            }

            $db->prepare('INSERT INTO booking_status_logs (booking_id, status, note, changed_by) VALUES (?, ?, ?, ?)')
                ->execute([$bookingId, 'pending', 'Booking dibuat, menunggu pembayaran.', $userId]);

            $db->commit();

            cart_clear();
            notify(
                $userId,
                'booking',
                'Pesanan ' . $code . ' berhasil dibuat',
                'Selesaikan pembayaran sebelum ' . format_datetime_id(date('Y-m-d H:i:s', time() + $holdHours * 3600)) . '.',
                'pesanan_detail.php?id=' . $bookingId
            );
            $adminIds = self::pdo()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll();
            foreach ($adminIds as $admin) {
                notify((int) $admin['id'], 'admin', 'Pesanan baru ' . $code, 'Pesanan baru menunggu pembayaran.', 'admin/pesanan.php?id=' . $bookingId);
            }
            log_audit('booking.create', 'booking', $bookingId, ['code' => $code, 'total' => $total]);

            return [
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'booking_id' => $bookingId,
                'code' => $code,
                'total' => $total,
                'redirect' => 'pesanan_detail.php?id=' . $bookingId,
            ];
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Booking create failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    public static function find(int $id): ?array
    {
        $booking = self::fetchOne(
            'SELECT b.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                pp.name AS pickup_name, pp.address AS pickup_address, pp.hours AS pickup_hours
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN pickup_points pp ON pp.id = b.pickup_point_id
             WHERE b.id = ? LIMIT 1',
            [$id]
        );
        return $booking ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $id = (int) self::fetchValue('SELECT id FROM bookings WHERE code = ? LIMIT 1', [$code]);
        return $id ? self::find($id) : null;
    }

    public static function items(int $bookingId): array
    {
        return self::fetchAll(
            'SELECT bi.*, i.slug, i.primary_image, i.brand, c.name AS category_name
             FROM booking_items bi
             JOIN items i ON i.id = bi.item_id
             JOIN categories c ON c.id = i.category_id
             WHERE bi.booking_id = ?
             ORDER BY bi.id ASC',
            [$bookingId]
        );
    }

    public static function logs(int $bookingId): array
    {
        return self::fetchAll(
            'SELECT l.*, u.name AS user_name FROM booking_status_logs l
             LEFT JOIN users u ON u.id = l.changed_by
             WHERE l.booking_id = ? ORDER BY l.created_at ASC, l.id ASC',
            [$bookingId]
        );
    }

    public static function payments(int $bookingId): array
    {
        return self::fetchAll(
            'SELECT p.*, u.name AS verifier_name FROM payments p
             LEFT JOIN users u ON u.id = p.verified_by
             WHERE p.booking_id = ? ORDER BY p.created_at DESC',
            [$bookingId]
        );
    }

    public static function forUser(int $userId, string $status = '', int $page = 1, int $perPage = 10): array
    {
        $where = ['b.user_id = ?'];
        $params = [$userId];
        if ($status !== '' && $status !== 'semua') {
            if ($status === 'aktif') {
                $where[] = "b.status IN ('confirmed','preparing','ready','ongoing')";
            } elseif ($status === 'selesai') {
                $where[] = "b.status IN ('returned','completed')";
            } elseif ($status === 'dibatalkan') {
                $where[] = "b.status = 'cancelled'";
            } else {
                $where[] = 'b.status = ?';
                $params[] = $status;
            }
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue("SELECT COUNT(*) FROM bookings b WHERE $whereSql", $params);
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT b.*, pp.name AS pickup_name,
                (SELECT COUNT(*) FROM booking_items bi WHERE bi.booking_id = b.id) AS item_count,
                (SELECT bi.primary_image FROM booking_items bi2 JOIN items bi ON bi.id = bi2.item_id
                    WHERE bi2.booking_id = b.id ORDER BY bi2.id ASC LIMIT 1) AS cover_image
             FROM bookings b LEFT JOIN pickup_points pp ON pp.id = b.pickup_point_id
             WHERE $whereSql
             ORDER BY b.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );
        return ['items' => $rows, 'pagination' => $pagination];
    }

    public static function search(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = ['1=1'];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(b.code LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && $status !== 'semua') {
            $where[] = 'b.status = ?';
            $params[] = $status;
        }
        $paymentStatus = (string) ($filters['payment_status'] ?? '');
        if ($paymentStatus !== '' && $paymentStatus !== 'semua') {
            $where[] = 'b.payment_status = ?';
            $params[] = $paymentStatus;
        }
        $from = (string) ($filters['from'] ?? '');
        if ($from !== '') {
            $where[] = 'b.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        $to = (string) ($filters['to'] ?? '');
        if ($to !== '') {
            $where[] = 'b.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings b JOIN users u ON u.id = b.user_id WHERE $whereSql",
            $params
        );
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT b.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                (SELECT COUNT(*) FROM booking_items bi WHERE bi.booking_id = b.id) AS item_count
             FROM bookings b JOIN users u ON u.id = b.user_id
             WHERE $whereSql ORDER BY b.created_at DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );
        return ['items' => $rows, 'pagination' => $pagination];
    }

    public static function updateStatus(int $bookingId, string $newStatus, ?string $note = null, ?int $adminId = null): array
    {
        $valid = ['pending', 'confirmed', 'preparing', 'ready', 'ongoing', 'returned', 'completed', 'cancelled'];
        if (!in_array($newStatus, $valid, true)) {
            return ['success' => false, 'message' => 'Status tidak valid.'];
        }
        $booking = self::find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        if ($booking['status'] === $newStatus) {
            return ['success' => true, 'message' => 'Status tidak berubah.'];
        }
        $db = self::pdo();
        $db->beginTransaction();
        try {
            $sets = ['status = ?'];
            $params = [$newStatus];
            if ($newStatus === 'completed') {
                $sets[] = 'completed_at = NOW()';
            }
            if ($newStatus === 'returned') {
                $sets[] = 'returned_at = NOW()';
            }
            if ($newStatus === 'cancelled') {
                $sets[] = 'cancel_reason = ?';
                $params[] = $note;
            }
            $params[] = $bookingId;
            $stmt = $db->prepare('UPDATE bookings SET ' . implode(', ', $sets) . ' WHERE id = ?');
            $stmt->execute($params);

            $db->prepare('INSERT INTO booking_status_logs (booking_id, status, note, changed_by) VALUES (?, ?, ?, ?)')
                ->execute([$bookingId, $newStatus, $note ?: status_label($newStatus), $adminId]);

            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Booking status failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui status.'];
        }

        notify(
            (int) $booking['user_id'],
            'booking',
            'Status pesanan ' . $booking['code'] . ' diperbarui',
            status_label($newStatus) . ($note ? ' — ' . $note : ''),
            'pesanan_detail.php?id=' . $bookingId
        );
        log_audit('booking.status', 'booking', $bookingId, ['status' => $newStatus, 'note' => $note]);
        return ['success' => true, 'message' => 'Status pesanan diperbarui menjadi ' . status_label($newStatus) . '.'];
    }

    public static function cancelByCustomer(int $bookingId, int $userId, string $reason = ''): array
    {
        $booking = self::find($bookingId);
        if (!$booking || (int) $booking['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
            return ['success' => false, 'message' => 'Pesanan dengan status ini tidak bisa dibatalkan. Hubungi admin.'];
        }
        if ($booking['payment_status'] === 'paid') {
            return ['success' => false, 'message' => 'Pesanan sudah dibayar. Hubungi admin untuk pembatalan dan refund.'];
        }
        return self::updateStatus($bookingId, 'cancelled', $reason ?: 'Dibatalkan oleh customer.', $userId);
    }

    public static function markPaid(int $bookingId, float $amount, string $method, ?string $proofPath = null, ?string $note = null): array
    {
        $booking = self::find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        $db = self::pdo();
        $db->beginTransaction();
        try {
            $db->prepare(
                "INSERT INTO payments (booking_id, method, amount, proof_path, status, paid_at, note, created_at)
                 VALUES (?, ?, ?, ?, 'pending', NOW(), ?, NOW())"
            )->execute([$bookingId, $method, $amount, $proofPath, $note]);
            $db->prepare("UPDATE bookings SET payment_status = 'waiting' WHERE id = ?")->execute([$bookingId]);
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Payment create failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan pembayaran.'];
        }
        $adminIds = self::pdo()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll();
        foreach ($adminIds as $admin) {
            notify((int) $admin['id'], 'admin', 'Pembayaran baru ' . $booking['code'], 'Bukti pembayaran menunggu verifikasi.', 'admin/pesanan.php?id=' . $bookingId);
        }
        notify((int) $booking['user_id'], 'payment', 'Bukti pembayaran diterima', 'Pembayaran pesanan ' . $booking['code'] . ' sedang diverifikasi admin.', 'pesanan_detail.php?id=' . $bookingId);
        return ['success' => true, 'message' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi admin.'];
    }

    public static function statusCounts(): array
    {
        $rows = self::fetchAll('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['total'];
        }
        return $out;
    }

    public static function activeRentals(int $limit = 5): array
    {
        return self::fetchAll(
            "SELECT b.*, u.name AS user_name, u.phone AS user_phone
             FROM bookings b JOIN users u ON u.id = b.user_id
             WHERE b.status IN ('ready','ongoing','preparing','confirmed')
             ORDER BY b.start_date ASC LIMIT ?",
            [$limit]
        );
    }

    public static function dueSoon(): array
    {
        return self::fetchAll(
            "SELECT b.*, u.name AS user_name, u.phone AS user_phone,
                DATEDIFF(b.end_date, CURDATE()) AS days_left
             FROM bookings b JOIN users u ON u.id = b.user_id
             WHERE b.status IN ('ongoing','ready') AND b.end_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
             ORDER BY b.end_date ASC"
        );
    }

    public static function upcomingForUser(int $userId, int $limit = 3): array
    {
        return self::fetchAll(
            "SELECT b.* FROM bookings b WHERE b.user_id = ?
                AND b.status IN ('pending','confirmed','preparing','ready')
                ORDER BY b.start_date ASC LIMIT ?",
            [$userId, $limit]
        );
    }
}
