<?php
declare(strict_types=1);

class Report extends Model
{
    public static function summary(): array
    {
        $revenueMonth = (float) self::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE status = 'verified' AND MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())"
        );
        $revenuePrev = (float) self::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE status = 'verified'
               AND DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')"
        );
        $bookingsMonth = (int) self::fetchValue(
            'SELECT COUNT(*) FROM bookings WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())'
        );
        $bookingsPrev = (int) self::fetchValue(
            "SELECT COUNT(*) FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')"
        );
        $customers = (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        $newCustomers = (int) self::fetchValue(
            "SELECT COUNT(*) FROM users WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
        );
        $activeRentals = (int) self::fetchValue("SELECT COUNT(*) FROM bookings WHERE status IN ('ready','ongoing')");
        $pendingPayments = (int) self::fetchValue("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
        $totalStock = (int) self::fetchValue('SELECT COALESCE(SUM(stock_total), 0) FROM items WHERE is_active = 1');

        $today = today();
        $rentedNow = (int) self::fetchValue(
            "SELECT COALESCE(SUM(bi.qty), 0) FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id
             WHERE b.status IN ('ongoing','ready') AND bi.start_date <= ? AND bi.end_date >= ?",
            [$today, $today]
        );
        $utilization = $totalStock > 0 ? min(100, round($rentedNow / $totalStock * 100)) : 0;

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
            'avg_rating' => (float) self::fetchValue('SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE is_published = 1'),
        ];
    }

    public static function monthlyRevenue(int $months = 8): array
    {
        $rows = self::fetchAll(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS period, COALESCE(SUM(amount), 0) AS total
             FROM payments WHERE status = 'verified' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
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

    public static function bookingsByStatus(): array
    {
        $rows = self::fetchAll('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status ORDER BY total DESC');
        return $rows;
    }

    public static function topItems(int $limit = 6): array
    {
        return self::fetchAll(
            "SELECT i.id, i.name, i.primary_image, i.rented_count, i.price_per_day, i.rating_avg,
                c.name AS category_name,
                COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN bi.qty ELSE 0 END), 0) AS rented_qty,
                COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN bi.subtotal ELSE 0 END), 0) AS revenue
             FROM items i
             JOIN categories c ON c.id = i.category_id
             LEFT JOIN booking_items bi ON bi.item_id = i.id
             LEFT JOIN bookings b ON b.id = bi.booking_id AND b.status NOT IN ('cancelled')
             GROUP BY i.id, i.name, i.primary_image, i.rented_count, i.price_per_day, i.rating_avg, c.name
             ORDER BY rented_qty DESC, i.rented_count DESC LIMIT ?",
            [$limit]
        );
    }

    public static function topCategories(): array
    {
        return self::fetchAll(
            "SELECT c.name, c.slug, COUNT(CASE WHEN b.id IS NOT NULL THEN bi.id END) AS total_rentals
             FROM categories c
             LEFT JOIN items i ON i.category_id = c.id
             LEFT JOIN booking_items bi ON bi.item_id = i.id
             LEFT JOIN bookings b ON b.id = bi.booking_id AND b.status <> 'cancelled'
             GROUP BY c.id, c.name, c.slug ORDER BY total_rentals DESC LIMIT 6"
        );
    }

    public static function recentBookings(int $limit = 6): array
    {
        return self::fetchAll(
            "SELECT b.*, u.name AS user_name FROM bookings b JOIN users u ON u.id = b.user_id
             ORDER BY b.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function exportRows(string $from, string $to): array
    {
        return self::fetchAll(
            "SELECT b.code, b.created_at, u.name AS customer, u.phone,
                b.start_date, b.end_date, b.duration_days, b.subtotal, b.deposit_total, b.delivery_fee,
                b.discount, b.total, b.status, b.payment_status
             FROM bookings b JOIN users u ON u.id = b.user_id
             WHERE DATE(b.created_at) BETWEEN ? AND ?
             ORDER BY b.created_at DESC",
            [$from, $to]
        );
    }
}
