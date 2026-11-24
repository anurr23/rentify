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
        $cartStoreId = (int) ($cart['store']['id'] ?? 0);
        $storeId = (int) ($data['store_id'] ?? 0) ?: $cartStoreId;
        if ($cartStoreId > 0 && $storeId !== $cartStoreId) {
            return ['success' => false, 'message' => 'Keranjang berisi gear dari cabang lain. Selesaikan atau kosongkan keranjang terlebih dahulu.'];
        }
        $pickupPointId = !empty($data['pickup_point_id']) ? (int) $data['pickup_point_id'] : null;
        $deliveryAddress = $pickupMethod === 'delivery' ? trim((string) ($data['delivery_address'] ?? '')) : null;

        if ($pickupMethod === 'delivery' && !$deliveryAddress) {
            return ['success' => false, 'message' => 'Alamat pengantaran wajib diisi.'];
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
            if ($storeId <= 0) {
                $defaultStore = Store::defaultStore();
                $storeId = $defaultStore ? (int) $defaultStore['id'] : 0;
            }
            $store = $storeId > 0 ? Store::find($storeId) : null;
            if (!$store || (int) $store['is_active'] !== 1) {
                throw new RuntimeException('Toko cabang tidak tersedia. Pilih cabang lain.');
            }

            $pickupPoint = null;
            if ($pickupMethod === 'pickup') {
                if ($pickupPointId) {
                    $stmt = $db->prepare('SELECT * FROM pickup_points WHERE id = ? AND is_active = 1');
                    $stmt->execute([$pickupPointId]);
                    $pickupPoint = $stmt->fetch();
                } else {
                    $pickupPoint = Store::pickupPoint($storeId);
                }
                if (!$pickupPoint || (int) $pickupPoint['store_id'] !== $storeId) {
                    throw new RuntimeException('Titik pengambilan tidak sesuai dengan toko cabang yang dipilih.');
                }
                $pickupPointId = (int) $pickupPoint['id'];
            }

            $recheck = [];
            foreach ($cart['items'] as $line) {
                $storeItemId = (int) ($line['store_item_id'] ?? 0);
                if ($storeItemId <= 0) {
                    throw new RuntimeException('Barang ' . $line['name'] . ' tidak terhubung ke cabang.');
                }
                $stockStmt = $db->prepare('SELECT stock_total, store_id, item_id FROM store_items WHERE id = ? FOR UPDATE');
                $stockStmt->execute([$storeItemId]);
                $offer = $stockStmt->fetch();
                if (!$offer || (int) $offer['store_id'] !== $storeId || (int) $offer['item_id'] !== (int) $line['item_id']) {
                    throw new RuntimeException('Barang ' . $line['name'] . ' tidak tersedia di cabang ini.');
                }
                if ($line['variant_id']) {
                    $variantStock = $db->prepare(
                        'SELECT stock_total FROM store_item_variants WHERE store_item_id = ? AND variant_id = ? FOR UPDATE'
                    );
                    $variantStock->execute([$storeItemId, $line['variant_id']]);
                    $stock = $variantStock->fetchColumn();
                } else {
                    $stock = $offer['stock_total'];
                }
                if ($stock === false) {
                    throw new RuntimeException('Stok ' . $line['name'] . ' tidak ditemukan.');
                }
                $booked = StoreItem::bookedQty($storeId, (int) $line['item_id'], $line['variant_id'], $start, $end);
                if ((int) $line['qty'] > ((int) $stock - $booked)) {
                    throw new RuntimeException('Stok ' . $line['name'] . ' tidak cukup di cabang ini untuk tanggal tersebut.');
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
            $storeDeliveryFee = $store['delivery_fee'] !== null ? (float) $store['delivery_fee'] : (float) setting('delivery_fee', '15000');
            $deliveryFee = $pickupMethod === 'delivery' ? $storeDeliveryFee : 0.0;
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
                "INSERT INTO bookings (code, user_id, store_id, pickup_point_id, start_date, end_date, duration_days,
                    pickup_method, delivery_address, subtotal, deposit_total, delivery_fee, discount, total,
                    status, payment_status, notes, hold_expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())"
            );
            $stmt->execute([
                $code, $userId, $storeId, $pickupPointId, $start, $end, $duration,
                $pickupMethod, $deliveryAddress, $subtotal, $deposit, $deliveryFee, $discount, $total,
                trim((string) ($data['notes'] ?? '')) ?: null, $holdHours,
            ]);
            $bookingId = (int) $db->lastInsertId();

            $insertItem = $db->prepare(
                'INSERT INTO booking_items (booking_id, item_id, store_item_id, variant_id, item_name, variant_label, qty,
                    price_per_day, duration_days, subtotal, deposit, start_date, end_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($recheck as $line) {
                $lineSubtotal = (float) $line['price_per_day'] * (int) $line['qty'] * $duration;
                $insertItem->execute([
                    $bookingId, $line['item_id'], $line['store_item_id'], $line['variant_id'], $line['name'], $line['variant_label'],
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
            $adminIds = self::fetchAll(
                "SELECT id FROM users WHERE is_active = 1 AND (role = 'superadmin' OR (role = 'admin' AND store_id = ?))",
                [$storeId]
            );
            foreach ($adminIds as $admin) {
                notify((int) $admin['id'], 'admin', 'Pesanan baru ' . $code, 'Pesanan baru di ' . $store['name'] . ' menunggu pembayaran.', 'admin/pesanan.php?id=' . $bookingId);
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
                pp.name AS pickup_name, pp.address AS pickup_address, pp.hours AS pickup_hours,
                s.name AS store_name, s.code AS store_code, s.city AS store_city, s.address AS store_address,
                s.phone AS store_phone, s.whatsapp AS store_whatsapp, s.opening_hours AS store_hours,
                s.latitude AS store_latitude, s.longitude AS store_longitude
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN pickup_points pp ON pp.id = b.pickup_point_id
             LEFT JOIN stores s ON s.id = b.store_id
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
             WHERE p.booking_id = ? ORDER BY p.created_at DESC, p.id DESC',
            [$bookingId]
        );
    }

    public static function forUser(int $userId, string $status = '', int $page = 1, int $perPage = 10, ?int $storeId = null): array
    {
        $where = ['b.user_id = ?'];
        $params = [$userId];
        if ($storeId !== null) {
            $where[] = 'b.store_id = ?';
            $params[] = $storeId;
        }
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
            "SELECT b.*, pp.name AS pickup_name, s.name AS store_name, s.city AS store_city,
                (SELECT COUNT(*) FROM booking_items bi WHERE bi.booking_id = b.id) AS item_count,
                (SELECT bi.primary_image FROM booking_items bi2 JOIN items bi ON bi.id = bi2.item_id
                    WHERE bi2.booking_id = b.id ORDER BY bi2.id ASC LIMIT 1) AS cover_image
             FROM bookings b LEFT JOIN pickup_points pp ON pp.id = b.pickup_point_id
             LEFT JOIN stores s ON s.id = b.store_id
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
        if (array_key_exists('store_id', $filters) && $filters['store_id'] !== null) {
            $where[] = 'b.store_id = ?';
            $params[] = (int) $filters['store_id'];
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings b JOIN users u ON u.id = b.user_id WHERE $whereSql",
            $params
        );
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT b.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                s.name AS store_name, s.city AS store_city,
                (SELECT COUNT(*) FROM booking_items bi WHERE bi.booking_id = b.id) AS item_count
             FROM bookings b JOIN users u ON u.id = b.user_id
             LEFT JOIN stores s ON s.id = b.store_id
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
        $paidStages = ['confirmed', 'preparing', 'ready', 'ongoing', 'returned', 'completed'];
        $syncPayment = in_array($newStatus, $paidStages, true) && $booking['payment_status'] === 'waiting';
        $cancelWaitingPayment = $newStatus === 'cancelled' && $booking['payment_status'] === 'waiting';
        if ($booking['status'] === $newStatus && !$syncPayment && !$cancelWaitingPayment) {
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

            if ($syncPayment) {
                $db->prepare(
                    "UPDATE payments SET status = 'verified', verified_by = COALESCE(?, verified_by), updated_at = NOW()
                     WHERE booking_id = ? AND status = 'pending'"
                )->execute([$adminId, $bookingId]);
            }
            if ($cancelWaitingPayment) {
                $db->prepare(
                    "UPDATE payments SET status = 'rejected', verified_by = COALESCE(?, verified_by),
                        note = 'Pesanan dibatalkan.', updated_at = NOW()
                     WHERE booking_id = ? AND status = 'pending'"
                )->execute([$adminId, $bookingId]);
            }
            if ($syncPayment || $cancelWaitingPayment) {
                self::syncPaymentTotals($bookingId);
            }

            $logNote = $note ?: status_label($newStatus, $booking['pickup_method'] ?? null);
            if ($syncPayment) {
                $logNote .= ' · Pembayaran diverifikasi.';
            }
            if ($cancelWaitingPayment) {
                $logNote .= ' · Pembayaran dibatalkan.';
            }
            $db->prepare('INSERT INTO booking_status_logs (booking_id, status, note, changed_by) VALUES (?, ?, ?, ?)')
                ->execute([$bookingId, $newStatus, $logNote, $adminId]);

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
            status_label($newStatus, $booking['pickup_method'] ?? null) . ($note ? ' — ' . $note : ''),
            'pesanan_detail.php?id=' . $bookingId
        );
        log_audit('booking.status', 'booking', $bookingId, ['status' => $newStatus, 'note' => $note]);
        return ['success' => true, 'message' => 'Status pesanan diperbarui menjadi ' . status_label($newStatus, $booking['pickup_method'] ?? null) . '.'];
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
        if (in_array($booking['payment_status'], ['paid', 'partial'], true)) {
            return ['success' => false, 'message' => 'Pesanan sudah dibayar. Hubungi admin untuk pembatalan dan refund.'];
        }
        return self::updateStatus($bookingId, 'cancelled', $reason ?: 'Dibatalkan oleh customer.', $userId);
    }

    /**
     * Ringkasan nominal pembayaran: total, sudah dibayar, sisa, dan nominal DP.
     */
    public static function paymentAmounts(array $booking): array
    {
        $total = round((float) $booking['total'], 2);
        $paid = round((float) ($booking['paid_amount'] ?? 0), 2);
        $remaining = max(0.0, round($total - $paid, 2));
        $percent = (float) setting('dp_percent', '30');
        $dpAmount = ($percent > 0 && $percent < 100) ? round($total * $percent / 100, 2) : 0.0;
        if ($paid > 0 || $dpAmount <= 0 || $dpAmount >= $remaining) {
            $dpAmount = 0.0;
        }
        return ['total' => $total, 'paid' => $paid, 'remaining' => $remaining, 'dp_percent' => $percent, 'dp_amount' => $dpAmount];
    }

    /**
     * Hitung ulang paid_amount dan payment_status dari pembayaran terverifikasi.
     */
    public static function syncPaymentTotals(int $bookingId): void
    {
        self::pdo()->prepare(
            "UPDATE bookings b SET
                paid_amount = (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = 'verified'),
                payment_status = CASE
                    WHEN (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = 'verified') <= 0 THEN 'unpaid'
                    WHEN (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = 'verified') + 0.01 >= b.total THEN 'paid'
                    ELSE 'partial'
                END
             WHERE b.id = ?"
        )->execute([$bookingId]);
    }

    public static function markPaid(int $bookingId, string $method, string $type = 'full', ?string $proofPath = null, ?string $note = null): array
    {
        $booking = self::find($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        if ($booking['status'] === 'cancelled') {
            return ['success' => false, 'message' => 'Pesanan sudah dibatalkan.'];
        }
        $amounts = self::paymentAmounts($booking);
        if ($amounts['remaining'] <= 0) {
            return ['success' => false, 'message' => 'Pesanan ini sudah lunas.'];
        }
        $pending = (int) self::fetchValue(
            "SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'pending'",
            [$bookingId]
        );
        if ($pending > 0) {
            return ['success' => false, 'message' => 'Masih ada pembayaran yang menunggu verifikasi admin.'];
        }
        if ($type === 'dp') {
            if ($amounts['dp_amount'] <= 0) {
                return ['success' => false, 'message' => 'Pembayaran DP tidak tersedia untuk pesanan ini.'];
            }
            $amount = $amounts['dp_amount'];
            $paymentType = 'dp';
        } else {
            $amount = $amounts['remaining'];
            $paymentType = $amounts['paid'] > 0 ? 'settlement' : 'full';
        }

        $db = self::pdo();
        $db->beginTransaction();
        try {
            $db->prepare(
                "INSERT INTO payments (booking_id, method, payment_type, amount, proof_path, status, paid_at, note, created_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?, NOW())"
            )->execute([$bookingId, $method, $paymentType, $amount, $proofPath, $note]);
            $db->prepare("UPDATE bookings SET payment_status = 'waiting' WHERE id = ?")->execute([$bookingId]);
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Payment create failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan pembayaran.'];
        }
        $adminIds = self::fetchAll(
            "SELECT id FROM users WHERE is_active = 1 AND (role = 'superadmin' OR (role = 'admin' AND store_id = ?))",
            [(int) ($booking['store_id'] ?? 0)]
        );
        foreach ($adminIds as $admin) {
            notify((int) $admin['id'], 'admin', 'Pembayaran baru ' . $booking['code'], 'Bukti pembayaran menunggu verifikasi.', 'admin/pesanan.php?id=' . $bookingId);
        }
        notify((int) $booking['user_id'], 'payment', 'Bukti pembayaran diterima', 'Pembayaran pesanan ' . $booking['code'] . ' sedang diverifikasi admin.', 'pesanan_detail.php?id=' . $bookingId);
        return [
            'success' => true,
            'message' => $paymentType === 'dp'
                ? 'Bukti pembayaran DP ' . format_rupiah($amount) . ' dikirim. Menunggu verifikasi admin.'
                : 'Bukti pembayaran dikirim. Menunggu verifikasi admin.',
            'amount' => $amount,
        ];
    }

    public static function statusCounts(?int $storeId = null): array
    {
        $where = $storeId !== null ? 'WHERE store_id = ?' : '';
        $rows = self::fetchAll(
            "SELECT status, COUNT(*) AS total FROM bookings $where GROUP BY status",
            $storeId !== null ? [$storeId] : []
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['total'];
        }
        return $out;
    }

    public static function activeRentals(int $limit = 5, ?int $storeId = null): array
    {
        $where = "b.status IN ('ready','ongoing','preparing','confirmed')";
        $params = [];
        if ($storeId !== null) {
            $where .= ' AND b.store_id = ?';
            $params[] = $storeId;
        }
        $params[] = $limit;
        return self::fetchAll(
            "SELECT b.*, u.name AS user_name, u.phone AS user_phone, s.name AS store_name
             FROM bookings b JOIN users u ON u.id = b.user_id
             LEFT JOIN stores s ON s.id = b.store_id
             WHERE $where
             ORDER BY b.start_date ASC LIMIT ?",
            $params
        );
    }

    public static function dueSoon(?int $storeId = null): array
    {
        $where = "b.status IN ('ongoing','ready') AND b.end_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)";
        $params = [];
        if ($storeId !== null) {
            $where .= ' AND b.store_id = ?';
            $params[] = $storeId;
        }
        return self::fetchAll(
            "SELECT b.*, u.name AS user_name, u.phone AS user_phone, s.name AS store_name,
                DATEDIFF(b.end_date, CURDATE()) AS days_left
             FROM bookings b JOIN users u ON u.id = b.user_id
             LEFT JOIN stores s ON s.id = b.store_id
             WHERE $where
             ORDER BY b.end_date ASC",
            $params
        );
    }

    public static function upcomingForUser(int $userId, int $limit = 3): array
    {
        return self::fetchAll(
            "SELECT b.*, s.name AS store_name, s.city AS store_city
             FROM bookings b LEFT JOIN stores s ON s.id = b.store_id
             WHERE b.user_id = ?
                AND b.status IN ('pending','confirmed','preparing','ready')
                ORDER BY b.start_date ASC LIMIT ?",
            [$userId, $limit]
        );
    }
}
