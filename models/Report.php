<?php
declare(strict_types=1);

class Report extends Model
{
    public static function summary(?int $storeId = null): array
    {
        $bookingStore = $storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '';
        $paymentFrom = 'FROM payments p JOIN bookings b ON b.id = p.booking_id';
        $revenueMonth = (float) self::fetchValue(
            "SELECT COALESCE(SUM(p.amount), 0) $paymentFrom
             WHERE p.status = 'verified' AND MONTH(p.paid_at) = MONTH(CURDATE()) AND YEAR(p.paid_at) = YEAR(CURDATE())$bookingStore"
        );
        $revenuePrev = (float) self::fetchValue(
            "SELECT COALESCE(SUM(p.amount), 0) $paymentFrom
             WHERE p.status = 'verified'
               AND DATE_FORMAT(p.paid_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')$bookingStore"
        );
        $bookingsMonth = (int) self::fetchValue(
            'SELECT COUNT(*) FROM bookings b WHERE MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())'
            . ($storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '')
        );
        $bookingsPrev = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings b WHERE DATE_FORMAT(b.created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')"
            . ($storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '')
        );
        if ($storeId !== null) {
            $customers = (int) self::fetchValue(
                'SELECT COUNT(DISTINCT b.user_id) FROM bookings b WHERE b.store_id = ?',
                [$storeId]
            );
            $newCustomers = (int) self::fetchValue(
                "SELECT COUNT(DISTINCT b.user_id) FROM bookings b
                 WHERE b.store_id = ? AND MONTH(b.created_at) = MONTH(CURDATE()) AND YEAR(b.created_at) = YEAR(CURDATE())",
                [$storeId]
            );
        } else {
            $customers = (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'customer'");
            $newCustomers = (int) self::fetchValue(
                "SELECT COUNT(*) FROM users WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
            );
        }
        $activeRentals = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings b WHERE b.status IN ('ready','ongoing')"
            . ($storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '')
        );
        $pendingPayments = (int) self::fetchValue(
            "SELECT COUNT(*) FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE p.status = 'pending'$bookingStore"
        );
        $totalStock = (int) self::fetchValue('SELECT COALESCE(SUM(stock_total), 0) FROM items WHERE is_active = 1');

        $today = today();
        $rentedNow = (int) self::fetchValue(
            "SELECT COALESCE(SUM(bi.qty), 0) FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id
             WHERE b.status IN ('ongoing','ready') AND bi.start_date <= ? AND bi.end_date >= ?"
            . ($storeId !== null ? ' AND b.store_id = ' . (int) $storeId : ''),
            [$today, $today]
        );
        $utilization = $totalStock > 0 ? min(100, round($rentedNow / $totalStock * 100)) : 0;

        $ratingFrom = 'FROM reviews r JOIN bookings b ON b.id = r.booking_id';
        $avgRating = (float) self::fetchValue(
            "SELECT COALESCE(AVG(r.rating), 0) $ratingFrom WHERE r.is_published = 1$bookingStore"
        );

        return [
            'revenue_month' => $revenueMonth,
            'revenue_prev' => $revenuePrev,
            'revenue_growth' => $revenuePrev > 0 ? round(($revenueMonth - $revenuePrev) / $revenuePrev * 100, 1) : ($revenueMonth > 0 ? 100 : 0),
            'bookings_month' => $bookingsMonth,
            'bookings_prev' => $bookingsPrev,
            'bookings_growth' => $bookingsPrev > 0 ? round(($bookingsMonth - $bookingsPrev) / $bookingsPrev * 100, 1) : ($bookingsMonth > 0 ? 100 : 0),
            'customers' => $customers,
            'new_customers' => $newCustomers,
            'active_rentals' => $activeRentals,
            'pending_payments' => $pendingPayments,
            'utilization' => $utilization,
            'stock_total' => $totalStock,
            'rented_now' => $rentedNow,
            'avg_rating' => $avgRating,
        ];
    }

    public static function monthlyRevenue(int $months = 8, ?int $storeId = null): array
    {
        $storeWhere = $storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '';
        $rows = self::fetchAll(
            "SELECT DATE_FORMAT(p.paid_at, '%Y-%m') AS period, COALESCE(SUM(p.amount), 0) AS total
             FROM payments p JOIN bookings b ON b.id = p.booking_id
             WHERE p.status = 'verified' AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)$storeWhere
             GROUP BY period ORDER BY period ASC",
            [$months]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row['period']] = (float) $row['total'];
        }
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $period = date('Y-m', strtotime('-' . $i . ' month'));
            $out[] = [
                'period' => $period,
                'label' => format_date_id($period . '-01', 'M'),
                'total' => $map[$period] ?? 0,
            ];
        }
        return $out;
    }

    public static function bookingsByStatus(?int $storeId = null): array
    {
        $where = $storeId !== null ? 'WHERE store_id = ' . (int) $storeId : '';
        return self::fetchAll("SELECT status, COUNT(*) AS total FROM bookings $where GROUP BY status ORDER BY total DESC");
    }

    public static function topItems(int $limit = 6, ?int $storeId = null): array
    {
        $storeJoin = $storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '';
        return self::fetchAll(
            "SELECT i.id, i.name, i.primary_image, i.rented_count, i.price_per_day, i.rating_avg,
                c.name AS category_name,
                COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN bi.qty ELSE 0 END), 0) AS rented_qty,
                COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN bi.subtotal ELSE 0 END), 0) AS revenue
             FROM items i
             JOIN categories c ON c.id = i.category_id
             LEFT JOIN booking_items bi ON bi.item_id = i.id
             LEFT JOIN bookings b ON b.id = bi.booking_id AND b.status NOT IN ('cancelled')$storeJoin
             GROUP BY i.id, i.name, i.primary_image, i.rented_count, i.price_per_day, i.rating_avg, c.name
             ORDER BY rented_qty DESC, i.rented_count DESC LIMIT ?",
            [$limit]
        );
    }

    public static function topCategories(?int $storeId = null): array
    {
        $storeJoin = $storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '';
        return self::fetchAll(
            "SELECT c.name, c.slug, COUNT(CASE WHEN b.id IS NOT NULL THEN bi.id END) AS total_rentals
             FROM categories c
             LEFT JOIN items i ON i.category_id = c.id
             LEFT JOIN booking_items bi ON bi.item_id = i.id
             LEFT JOIN bookings b ON b.id = bi.booking_id AND b.status <> 'cancelled'$storeJoin
             GROUP BY c.id, c.name, c.slug ORDER BY total_rentals DESC LIMIT 6"
        );
    }

    public static function recentBookings(int $limit = 6, ?int $storeId = null): array
    {
        $where = $storeId !== null ? 'WHERE b.store_id = ' . (int) $storeId : '';
        return self::fetchAll(
            "SELECT b.*, u.name AS user_name, s.name AS store_name FROM bookings b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN stores s ON s.id = b.store_id
             $where ORDER BY b.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function storePerformance(string $from, string $to): array
    {
        $stores = self::fetchAll('SELECT id, name, code, city, province FROM stores ORDER BY sort_order ASC, id ASC');
        $fromDate = new DateTimeImmutable($from);
        $toDate = new DateTimeImmutable($to);
        $days = max(1, (int) $fromDate->diff($toDate)->days + 1);
        $prevTo = $fromDate->modify('-1 day')->format('Y-m-d');
        $prevFrom = $fromDate->modify('-' . $days . ' days')->format('Y-m-d');
        $today = today();

        $rows = [];
        foreach ($stores as $store) {
            $storeId = (int) $store['id'];
            $revenue = (float) self::fetchValue(
                "SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                 JOIN bookings b ON b.id = p.booking_id
                 WHERE p.status = 'verified' AND b.store_id = ? AND DATE(p.paid_at) BETWEEN ? AND ?",
                [$storeId, $from, $to]
            );
            $prevRevenue = (float) self::fetchValue(
                "SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                 JOIN bookings b ON b.id = p.booking_id
                 WHERE p.status = 'verified' AND b.store_id = ? AND DATE(p.paid_at) BETWEEN ? AND ?",
                [$storeId, $prevFrom, $prevTo]
            );
            $orders = (int) self::fetchValue(
                'SELECT COUNT(*) FROM bookings WHERE store_id = ? AND DATE(created_at) BETWEEN ? AND ?',
                [$storeId, $from, $to]
            );
            $prevOrders = (int) self::fetchValue(
                'SELECT COUNT(*) FROM bookings WHERE store_id = ? AND DATE(created_at) BETWEEN ? AND ?',
                [$storeId, $prevFrom, $prevTo]
            );
            $completed = (int) self::fetchValue(
                "SELECT COUNT(*) FROM bookings WHERE store_id = ? AND status IN ('returned','completed') AND DATE(created_at) BETWEEN ? AND ?",
                [$storeId, $from, $to]
            );
            $customers = (int) self::fetchValue(
                'SELECT COUNT(DISTINCT user_id) FROM bookings WHERE store_id = ? AND DATE(created_at) BETWEEN ? AND ?',
                [$storeId, $from, $to]
            );
            $activeRentals = (int) self::fetchValue(
                "SELECT COUNT(*) FROM bookings WHERE store_id = ? AND status IN ('ready','ongoing','preparing','confirmed')",
                [$storeId]
            );
            $lateReturns = (int) self::fetchValue(
                "SELECT COUNT(*) FROM bookings WHERE store_id = ? AND status IN ('confirmed','preparing','ready','ongoing') AND end_date < ?",
                [$storeId, $today]
            );
            $pendingPayments = (int) self::fetchValue(
                "SELECT COUNT(*) FROM payments p JOIN bookings b ON b.id = p.booking_id
                 WHERE b.store_id = ? AND p.status = 'pending'",
                [$storeId]
            );
            $rating = (float) self::fetchValue(
                'SELECT COALESCE(AVG(r.rating), 0) FROM reviews r JOIN bookings b ON b.id = r.booking_id
                 WHERE b.store_id = ? AND r.is_published = 1',
                [$storeId]
            );
            $inventory = self::fetchOne(
                'SELECT COUNT(*) AS items, COALESCE(SUM(stock_total), 0) AS stock
                 FROM store_items WHERE store_id = ? AND is_active = 1',
                [$storeId]
            );
            $stock = (int) ($inventory['stock'] ?? 0);
            $rentedNow = (int) self::fetchValue(
                "SELECT COALESCE(SUM(bi.qty), 0) FROM booking_items bi
                 JOIN bookings b ON b.id = bi.booking_id
                 WHERE b.store_id = ? AND b.status IN ('ongoing','ready')
                   AND bi.start_date <= ? AND bi.end_date >= ?",
                [$storeId, $today, $today]
            );
            $rows[] = [
                'id' => $storeId,
                'name' => $store['name'],
                'code' => $store['code'],
                'city' => $store['city'],
                'province' => $store['province'],
                'revenue' => $revenue,
                'prev_revenue' => $prevRevenue,
                'growth' => $prevRevenue > 0 ? round(($revenue - $prevRevenue) / $prevRevenue * 100, 1) : ($revenue > 0 ? 100 : 0),
                'orders' => $orders,
                'prev_orders' => $prevOrders,
                'order_growth' => $prevOrders > 0 ? round(($orders - $prevOrders) / $prevOrders * 100, 1) : ($orders > 0 ? 100 : 0),
                'completed' => $completed,
                'customers' => $customers,
                'active_rentals' => $activeRentals,
                'late_returns' => $lateReturns,
                'pending_payments' => $pendingPayments,
                'rating' => $rating,
                'items' => (int) ($inventory['items'] ?? 0),
                'stock' => $stock,
                'rented_now' => $rentedNow,
                'utilization' => $stock > 0 ? min(100, round($rentedNow / $stock * 100)) : 0,
                'aov' => $orders > 0 ? $revenue / $orders : 0,
            ];
        }
        usort($rows, static fn (array $a, array $b): int => $b['revenue'] <=> $a['revenue']);
        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);
        return $rows;
    }

    public static function monthlyRevenueByStore(int $months = 8): array
    {
        $rows = self::fetchAll(
            "SELECT DATE_FORMAT(p.paid_at, '%Y-%m') AS period, b.store_id, s.name AS store_name,
                COALESCE(SUM(p.amount), 0) AS total
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN stores s ON s.id = b.store_id
             WHERE p.status = 'verified' AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY period, b.store_id, s.name
             ORDER BY period ASC, s.sort_order ASC",
            [$months]
        );
        $periods = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $periods[] = date('Y-m', strtotime('-' . $i . ' month'));
        }
        $stores = self::fetchAll('SELECT id, name FROM stores ORDER BY sort_order ASC, id ASC');
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['store_id']][$row['period']] = (float) $row['total'];
        }
        $datasets = [];
        foreach ($stores as $store) {
            $data = [];
            foreach ($periods as $period) {
                $data[] = $map[(int) $store['id']][$period] ?? 0;
            }
            $datasets[] = [
                'store_id' => (int) $store['id'],
                'label' => $store['name'],
                'data' => $data,
            ];
        }
        return [
            'labels' => array_map(static fn (string $period): string => format_date_id($period . '-01', 'M'), $periods),
            'datasets' => $datasets,
        ];
    }

    public static function exportRows(string $from, string $to, ?int $storeId = null): array
    {
        $storeWhere = $storeId !== null ? ' AND b.store_id = ' . (int) $storeId : '';
        return self::fetchAll(
            "SELECT b.code, b.created_at, u.name AS customer, u.phone,
                b.start_date, b.end_date, b.duration_days, b.subtotal, b.deposit_total, b.delivery_fee,
                b.discount, b.total, b.status, b.payment_status, b.pickup_method, s.name AS store_name
             FROM bookings b JOIN users u ON u.id = b.user_id
             LEFT JOIN stores s ON s.id = b.store_id
             WHERE DATE(b.created_at) BETWEEN ? AND ?$storeWhere
             ORDER BY b.created_at DESC",
            [$from, $to]
        );
    }
}
