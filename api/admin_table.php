<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once ROOT_PATH . '/includes/icons.php';

api_method(['GET']);
$admin = api_require_admin();

/**
 * Server-side DataTables untuk tabel admin. Setiap dataset divalidasi,
 * di-scope dengan admin_store_id(), dan kolom order di-whitelist.
 */
$dataset = (string) input('dataset', '');
$draw = max(0, input_int('draw', 1));
$start = max(0, input_int('start', 0));
$length = input_int('length', 10);
if ($length < 1) {
    $length = 10;
}
$length = min(100, $length);

$orderColumn = 0;
$orderDir = 'ASC';
$order = input('order', []);
if (is_array($order) && isset($order[0]) && is_array($order[0])) {
    $orderColumn = max(0, (int) ($order[0]['column'] ?? 0));
    $orderDir = strtolower((string) ($order[0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
}

$searchValue = trim((string) input('q', ''));
$scopeStoreId = admin_store_id();

function at_value(string $sql, array $params = []): mixed
{
    $stmt = Database::conn()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function at_rows(string $sql, array $params = []): array
{
    $stmt = Database::conn()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function at_badge(string $class, string $label): string
{
    return '<span class="badge ' . e($class) . '">' . e($label) . '</span>';
}

function at_limit(int $length, int $start): string
{
    return ' LIMIT ' . $length . ' OFFSET ' . $start;
}

$rows = [];
$recordsTotal = 0;
$recordsFiltered = 0;
$stats = [];

switch ($dataset) {
    case 'inventory':
        $storeId = $scopeStoreId ?? input_int('store_id');
        $categoryId = input_int('category');
        $status = (string) input('status', 'semua');

        $base = 'FROM store_items si
            JOIN items i ON i.id = si.item_id
            JOIN categories c ON c.id = i.category_id
            JOIN stores s ON s.id = si.store_id';
        $scopeWhere = '1=1';
        $scopeParams = [];
        if ($storeId > 0) {
            $scopeWhere = 'si.store_id = ?';
            $scopeParams[] = $storeId;
        }
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base WHERE $scopeWhere", $scopeParams);

        $where = [];
        $params = [];
        if ($storeId > 0) {
            $where[] = 'si.store_id = ?';
            $params[] = $storeId;
        }
        if ($searchValue !== '') {
            $where[] = '(i.name LIKE ? OR i.brand LIKE ? OR i.slug LIKE ? OR c.name LIKE ? OR s.name LIKE ?)';
            $like = '%' . $searchValue . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($categoryId > 0) {
            $where[] = 'i.category_id = ?';
            $params[] = $categoryId;
        }
        if ($status === 'aktif') {
            $where[] = 'si.is_active = 1';
        } elseif ($status === 'nonaktif') {
            $where[] = 'si.is_active = 0';
        } elseif ($status === 'habis') {
            $where[] = 'si.stock_total <= 0';
        }
        $whereSql = $where ? implode(' AND ', $where) : '1=1';
        $recordsFiltered = (int) at_value("SELECT COUNT(*) $base WHERE $whereSql", $params);

        $statRow = at_rows(
            "SELECT COUNT(*) AS total,
                COALESCE(SUM(si.is_active = 1), 0) AS active,
                COALESCE(SUM(si.stock_total <= 3), 0) AS low
             $base WHERE $whereSql",
            $params
        )[0] ?? ['total' => 0, 'active' => 0, 'low' => 0];
        $stats = [
            'total' => (int) $statRow['total'],
            'active' => (int) $statRow['active'],
            'low' => (int) $statRow['low'],
        ];

        $orderMap = [
            0 => 'i.name',
            1 => 'c.name',
            2 => 's.name',
            3 => 'si.price_per_day',
            4 => 'si.stock_total',
            5 => 'variant_count',
            6 => 'si.is_active',
        ];
        $orderSql = ($orderMap[$orderColumn] ?? 'si.is_active') . ' ' . $orderDir;

        $items = at_rows(
            "SELECT si.id AS store_item_id, si.store_id, si.price_per_day, si.stock_total, si.is_active,
                i.id AS item_id, i.name, i.brand, i.slug, i.primary_image, i.owner_store_id, i.created_at,
                c.name AS category_name, s.name AS store_name, s.city AS store_city,
                (SELECT COUNT(*) FROM item_variants v WHERE v.item_id = i.id AND v.is_active = 1) AS variant_count
             $base WHERE $whereSql
             ORDER BY $orderSql, i.created_at DESC, si.id DESC"
            . at_limit($length, $start),
            $params
        );

        foreach ($items as $row) {
            $stock = (int) $row['stock_total'];
            $isOwner = (int) ($row['owner_store_id'] ?? 0) === (int) $row['store_id'];
            $stockBadge = $stock <= 0 ? 'badge-danger' : ($stock <= 3 ? 'badge-warning' : 'badge-mint');
            $image = upload_url($row['primary_image']);
            $cells = [
                '<div class="flex items-center gap-2.5">'
                    . '<div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-cream-100"><img src="' . e($image) . '" alt="" loading="lazy" class="h-full w-full object-cover"></div>'
                    . '<div class="min-w-0"><p class="truncate font-extrabold text-forest-900">' . e($row['name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($row['brand'] ?: 'Rentify Select') . ($isOwner ? ' · Milik cabang' : '') . '</p></div>'
                    . '</div>',
                '<span class="text-xs text-muted">' . e($row['category_name']) . '</span>',
                '<p class="text-xs font-bold text-forest-900">' . e($row['store_name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($row['store_city']) . '</p>',
                '<span class="font-bold">' . e(format_rupiah($row['price_per_day'])) . '</span>',
                at_badge($stockBadge, $stock . ' unit'),
                '<span class="text-xs font-bold">' . (int) $row['variant_count'] . '</span>',
                at_badge((int) $row['is_active'] ? 'badge-success' : 'badge-neutral', (int) $row['is_active'] ? 'Aktif' : 'Nonaktif'),
                '<div class="flex justify-end gap-1">'
                    . '<button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="' . e(url('api/admin.php?action=item_form&id=' . (int) $row['item_id'] . '&store_id=' . (int) $row['store_id'])) . '" data-admin-modal-title="Edit ' . e($row['name']) . '" aria-label="Edit barang">' . icon('edit', 'h-3.5 w-3.5') . '</button>'
                    . '<button type="button" class="icon-btn h-8 w-8 text-danger" data-admin-delete="item" data-id="' . (int) $row['item_id'] . '" data-store-id="' . (int) $row['store_id'] . '" data-name="' . e($row['name']) . '" aria-label="Hapus dari cabang">' . icon('trash', 'h-3.5 w-3.5') . '</button>'
                    . '</div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'bookings':
        $status = (string) input('status', 'semua');
        $paymentStatus = (string) input('payment_status', 'semua');
        $from = trim((string) input('from', ''));
        $to = trim((string) input('to', ''));
        $storeId = $scopeStoreId ?? input_int('store_id');

        $base = 'FROM bookings b JOIN users u ON u.id = b.user_id LEFT JOIN stores s ON s.id = b.store_id';
        $scopeWhere = '1=1';
        $scopeParams = [];
        if ($storeId > 0) {
            $scopeWhere = 'b.store_id = ?';
            $scopeParams[] = $storeId;
        }
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base WHERE $scopeWhere", $scopeParams);

        $where = [];
        $params = [];
        if ($storeId > 0) {
            $where[] = 'b.store_id = ?';
            $params[] = $storeId;
        }
        if ($searchValue !== '') {
            $where[] = '(b.code LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $searchValue . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($status !== '' && $status !== 'semua') {
            $where[] = 'b.status = ?';
            $params[] = $status;
        }
        if ($paymentStatus !== '' && $paymentStatus !== 'semua') {
            $where[] = 'b.payment_status = ?';
            $params[] = $paymentStatus;
        }
        if ($from !== '') {
            $where[] = 'b.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'b.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        $whereSql = $where ? implode(' AND ', $where) : '1=1';
        $recordsFiltered = (int) at_value("SELECT COUNT(*) $base WHERE $whereSql", $params);

        $orderMap = [
            0 => 'b.code',
            1 => 'u.name',
            2 => 's.name',
            3 => 'b.start_date',
            4 => 'item_count',
            5 => 'b.total',
            6 => 'b.status',
            7 => 'b.payment_status',
        ];
        $orderSql = ($orderMap[$orderColumn] ?? 'b.created_at') . ' ' . $orderDir;

        $items = at_rows(
            "SELECT b.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                s.name AS store_name, s.city AS store_city,
                (SELECT COUNT(*) FROM booking_items bi WHERE bi.booking_id = b.id) AS item_count
             $base WHERE $whereSql
             ORDER BY $orderSql, b.id DESC"
            . at_limit($length, $start),
            $params
        );

        foreach ($items as $row) {
            $cells = [
                '<a href="' . e(url('admin/pesanan.php?id=' . (int) $row['id'])) . '" class="font-extrabold text-forest-900 hover:underline">' . e($row['code']) . '</a>'
                    . '<p class="text-[0.62rem] text-muted">' . e(time_ago($row['created_at'])) . '</p>',
                '<p class="font-bold">' . e($row['user_name']) . '</p><p class="text-[0.62rem] text-muted">' . e($row['user_phone']) . '</p>',
                '<p class="text-xs font-semibold">' . e($row['store_name'] ?: '-') . '</p><p class="text-[0.62rem] text-muted">' . e($row['store_city'] ?: '') . '</p>',
                '<p class="text-xs font-semibold">' . e(format_date_id($row['start_date'])) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">s/d ' . e(format_date_id($row['end_date'])) . ' · ' . (int) $row['duration_days'] . 'h</p>',
                '<span class="font-bold">' . (int) $row['item_count'] . '</span>',
                '<span class="font-extrabold">' . e(format_rupiah($row['total'])) . '</span>',
                at_badge(status_class((string) $row['status']), status_label((string) $row['status'], $row['pickup_method'] ?? null)),
                at_badge(payment_status_class((string) $row['payment_status']), payment_status_label((string) $row['payment_status'])),
                '<div class="flex justify-end"><a href="' . e(url('admin/pesanan.php?id=' . (int) $row['id'])) . '" class="btn btn-outline btn-sm">Detail</a></div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'customers':
        $storeId = $scopeStoreId ?? input_int('store_id');
        $base = 'FROM users u';
        $scopeWhere = "u.role = 'customer'";
        $scopeParams = [];
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base WHERE $scopeWhere", $scopeParams);

        $where = ["u.role = 'customer'"];
        $params = [];
        if ($searchValue !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.member_code LIKE ?)';
            $like = '%' . $searchValue . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($storeId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM bookings bx WHERE bx.user_id = u.id AND bx.store_id = ?)';
            $params[] = $storeId;
        }
        $whereSql = implode(' AND ', $where);
        $recordsFiltered = (int) at_value("SELECT COUNT(*) $base WHERE $whereSql", $params);

        $storeFilter = $storeId > 0 ? ' AND b.store_id = ' . $storeId : '';
        $orderMap = [
            0 => 'u.name',
            1 => 'u.email',
            2 => 'total_bookings',
            3 => 'total_spent',
            4 => 'last_booking',
            5 => 'u.is_active',
        ];
        $orderSql = ($orderMap[$orderColumn] ?? 'u.created_at') . ' ' . $orderDir;

        $items = at_rows(
            "SELECT u.*,
                (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id$storeFilter) AS total_bookings,
                (SELECT COALESCE(SUM(b.total), 0) FROM bookings b WHERE b.user_id = u.id AND b.payment_status = 'paid'$storeFilter) AS total_spent,
                (SELECT MAX(b.created_at) FROM bookings b WHERE b.user_id = u.id$storeFilter) AS last_booking
             $base WHERE $whereSql
             ORDER BY $orderSql, u.id DESC"
            . at_limit($length, $start),
            $params
        );

        foreach ($items as $row) {
            $avatar = !empty($row['avatar'])
                ? '<img src="' . e(upload_url($row['avatar'])) . '" alt="" class="h-9 w-9 rounded-full object-cover">'
                : '<span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-300 text-[0.62rem] font-extrabold text-forest-900">' . e(initials($row['name'])) . '</span>';
            $cells = [
                '<div class="flex items-center gap-2.5">' . $avatar
                    . '<div><p class="font-extrabold text-forest-900">' . e($row['name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($row['member_code'] ?: '-') . ' · ' . e(time_ago($row['created_at'])) . '</p></div></div>',
                '<p class="text-xs">' . e($row['email']) . '</p><p class="text-[0.62rem] text-muted">' . e($row['phone']) . '</p>',
                '<span class="font-bold">' . (int) $row['total_bookings'] . '</span>',
                '<span class="font-bold">' . e(format_rupiah($row['total_spent'])) . '</span>',
                '<span class="text-xs text-muted">' . e($row['last_booking'] ? time_ago($row['last_booking']) : '-') . '</span>',
                at_badge((int) $row['is_active'] ? 'badge-success' : 'badge-danger', (int) $row['is_active'] ? 'Aktif' : 'Nonaktif'),
                '<div class="flex justify-end gap-1">'
                    . '<a href="' . e(url('admin/pelanggan.php?id=' . (int) $row['id'])) . '" class="btn btn-outline btn-sm">Detail</a>'
                    . (is_superadmin()
                        ? '<button type="button" class="icon-btn h-8 w-8 ' . ((int) $row['is_active'] ? 'text-danger' : '') . '" data-user-toggle data-id="' . (int) $row['id'] . '" data-active="' . (int) $row['is_active'] . '" aria-label="Ubah status akun">' . icon('shield', 'h-3.5 w-3.5') . '</button>'
                        : '')
                    . '</div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'returns':
        $storeId = $scopeStoreId ?? input_int('store_id');
        $base = 'FROM bookings b JOIN users u ON u.id = b.user_id LEFT JOIN stores s ON s.id = b.store_id';
        $statusWhere = "b.status IN ('confirmed','preparing','ready','ongoing')";
        $scopeWhere = $statusWhere;
        $scopeParams = [];
        if ($storeId > 0) {
            $scopeWhere .= ' AND b.store_id = ?';
            $scopeParams[] = $storeId;
        }
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base WHERE $scopeWhere", $scopeParams);

        $where = $scopeWhere;
        $params = $scopeParams;
        if ($searchValue !== '') {
            $where .= ' AND (b.code LIKE ? OR u.name LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $searchValue . '%';
            array_push($params, $like, $like, $like);
        }
        $recordsFiltered = (int) at_value("SELECT COUNT(*) $base WHERE $where", $params);

        $statRow = at_rows(
            "SELECT COUNT(*) AS total,
                COALESCE(SUM(t.days_left < 0), 0) AS overdue,
                COALESCE(SUM(t.days_left = 0), 0) AS today_due,
                COALESCE(SUM(t.days_left > 0), 0) AS upcoming
             FROM (
                SELECT DATEDIFF(b.end_date, CURDATE()) AS days_left
                $base WHERE $where
             ) t",
            $params
        )[0] ?? ['total' => 0, 'overdue' => 0, 'today_due' => 0, 'upcoming' => 0];
        $stats = [
            'overdue' => (int) $statRow['overdue'],
            'today_due' => (int) $statRow['today_due'],
            'upcoming' => (int) $statRow['upcoming'],
            'total' => (int) $statRow['total'],
        ];

        $orderMap = [
            0 => 'b.code',
            1 => 'u.name',
            2 => 'b.start_date',
            3 => 'qty_total',
            4 => 'b.status',
            5 => 'days_left',
        ];
        $orderSql = ($orderMap[$orderColumn] ?? 'days_left') . ' ' . $orderDir;

        $items = at_rows(
            "SELECT b.*, u.name AS user_name, u.phone AS user_phone, s.name AS store_name,
                DATEDIFF(b.end_date, CURDATE()) AS days_left,
                (SELECT COALESCE(SUM(bi.qty), 0) FROM booking_items bi WHERE bi.booking_id = b.id) AS qty_total
             $base WHERE $where
             ORDER BY $orderSql, b.id ASC"
            . at_limit($length, $start),
            $params
        );

        foreach ($items as $row) {
            $daysLeft = (int) $row['days_left'];
            $late = $daysLeft < 0
                ? '<span class="badge badge-danger">Telat ' . abs($daysLeft) . ' hari</span>'
                : ($daysLeft === 0
                    ? '<span class="badge badge-warning">Hari ini</span>'
                    : '<span class="text-xs font-bold text-forest-700">' . $daysLeft . ' hari lagi</span>');
            $cells = [
                '<a href="' . e(url('admin/pesanan.php?id=' . (int) $row['id'])) . '" class="font-extrabold text-forest-900 hover:underline">' . e($row['code']) . '</a>'
                    . '<p class="text-[0.62rem] text-muted">' . ($row['pickup_method'] === 'delivery' ? 'Delivery' : 'Pickup') . '</p>',
                '<p class="font-bold">' . e($row['user_name']) . '</p>'
                    . '<a href="' . e(phone_wa_link($row['user_phone'], 'Halo ' . $row['user_name'] . ', pengingat pengembalian gear ' . $row['code'] . '.')) . '" target="_blank" rel="noopener" class="text-[0.62rem] font-bold text-forest-600 hover:underline">' . e($row['user_phone']) . '</a>',
                '<p class="text-xs font-semibold">' . e(format_date_id($row['start_date'])) . ' – ' . e(format_date_id($row['end_date'])) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . (int) $row['duration_days'] . ' hari</p>',
                '<span class="font-bold">' . (int) $row['qty_total'] . ' unit</span>',
                at_badge(status_class((string) $row['status']), status_label((string) $row['status'], $row['pickup_method'] ?? null)),
                $late,
                '<div class="flex justify-end gap-1.5">'
                    . '<button type="button" class="btn btn-outline btn-sm" data-admin-status data-booking-id="' . (int) $row['id'] . '" data-status="returned" data-confirm="Tandai gear sudah dikembalikan?">Dikembalikan</button>'
                    . '<button type="button" class="btn btn-primary btn-sm" data-admin-status data-booking-id="' . (int) $row['id'] . '" data-status="completed" data-confirm="Selesaikan pesanan ini? Pastikan gear sudah dicek lengkap.">Selesai</button>'
                    . '</div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => $daysLeft < 0 ? ['row_class' => 'bg-red-50/40'] : []];
        }
        break;

    case 'users':
        if (!is_superadmin()) {
            json_error('Akses khusus superadmin.', 403);
        }
        $role = (string) input('role', '');
        if (!in_array($role, ['', 'superadmin', 'admin', 'customer'], true)) {
            $role = '';
        }
        $base = 'FROM users u LEFT JOIN stores s ON s.id = u.store_id';
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base");

        $where = ['1=1'];
        $params = [];
        if ($searchValue !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.member_code LIKE ?)';
            $like = '%' . $searchValue . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($role !== '') {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }
        $whereSql = implode(' AND ', $where);
        $recordsFiltered = (int) at_value("SELECT COUNT(*) $base WHERE $whereSql", $params);

        $orderMap = [
            0 => 'u.name',
            1 => 'u.email',
            2 => 'u.role',
            3 => 's.name',
            4 => 'u.created_at',
            5 => 'u.is_active',
        ];
        $orderSql = ($orderMap[$orderColumn] ?? 'u.created_at') . ' ' . $orderDir;
        $currentId = (int) $admin['id'];

        $items = at_rows(
            "SELECT u.*, s.name AS store_name, s.city AS store_city
             $base WHERE $whereSql
             ORDER BY $orderSql, u.id DESC"
            . at_limit($length, $start),
            $params
        );

        foreach ($items as $row) {
            $isSelf = (int) $row['id'] === $currentId;
            $canToggle = !$isSelf && $row['role'] !== 'superadmin';
            $avatar = !empty($row['avatar'])
                ? '<img src="' . e(upload_url($row['avatar'])) . '" alt="" class="h-9 w-9 rounded-full object-cover">'
                : '<span class="flex h-9 w-9 items-center justify-center rounded-full ' . ($row['role'] === 'superadmin' ? 'bg-forest-900 text-mint-300' : ($row['role'] === 'admin' ? 'bg-mint-300 text-forest-900' : 'bg-cream-100 text-forest-700')) . ' text-[0.62rem] font-extrabold">' . e(initials($row['name'])) . '</span>';
            $storeCell = ($row['role'] === 'admin' && !empty($row['store_name']))
                ? '<p class="text-xs font-bold text-forest-900">' . e($row['store_name']) . '</p><p class="text-[0.62rem] text-muted">' . e($row['store_city'] ?: '') . '</p>'
                : '<span class="text-xs text-muted">' . ($row['role'] === 'superadmin' ? 'Semua cabang' : '-') . '</span>';
            $cells = [
                '<div class="flex items-center gap-2.5">' . $avatar
                    . '<div><p class="font-extrabold text-forest-900">' . e($row['name']) . ($isSelf ? ' <span class="text-[0.58rem] font-bold text-forest-500">(Anda)</span>' : '') . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($row['member_code'] ?: '-') . '</p></div></div>',
                '<p class="text-xs">' . e($row['email']) . '</p><p class="text-[0.62rem] text-muted">' . e($row['phone']) . '</p>',
                at_badge(role_badge_class((string) $row['role']), role_label((string) $row['role'])),
                $storeCell,
                '<span class="text-xs text-muted">' . e(format_date_id($row['created_at'])) . '</span>',
                at_badge((int) $row['is_active'] ? 'badge-success' : 'badge-danger', (int) $row['is_active'] ? 'Aktif' : 'Nonaktif'),
                '<div class="flex justify-end gap-1">'
                    . '<button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="' . e(url('api/admin.php?action=user_form&id=' . (int) $row['id'])) . '" data-admin-modal-title="Edit pengguna" aria-label="Edit pengguna">' . icon('edit', 'h-3.5 w-3.5') . '</button>'
                    . '<button type="button" class="icon-btn h-8 w-8" data-user-reset data-id="' . (int) $row['id'] . '" data-name="' . e($row['name']) . '" aria-label="Reset kata sandi">' . icon('key', 'h-3.5 w-3.5') . '</button>'
                    . ($canToggle
                        ? '<button type="button" class="icon-btn h-8 w-8 ' . ((int) $row['is_active'] ? 'text-danger' : '') . '" data-user-toggle data-id="' . (int) $row['id'] . '" data-active="' . (int) $row['is_active'] . '" aria-label="Ubah status akun">' . icon('shield', 'h-3.5 w-3.5') . '</button>'
                        : '')
                    . '</div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'stores':
        if (!is_superadmin()) {
            json_error('Akses khusus superadmin.', 403);
        }
        $allStores = Store::all();
        $recordsTotal = count($allStores);
        $recordsFiltered = $recordsTotal;
        foreach (array_slice($allStores, $start, $length) as $store) {
            $hasPoint = $store['latitude'] !== null && $store['longitude'] !== null;
            $cells = [
                '<div class="flex items-center gap-2.5">'
                    . '<div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-cream-100"><img src="' . e(upload_url($store['image'] ?? null)) . '" alt="" loading="lazy" class="h-full w-full object-cover"></div>'
                    . '<div class="min-w-0"><p class="truncate font-extrabold text-forest-900">' . e($store['name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($store['code']) . ((int) $store['is_default'] ? ' &middot; Default' : '') . '</p></div>'
                    . '</div>',
                '<p class="text-xs font-bold text-forest-900">' . e($store['city']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($store['province']) . '</p>',
                $hasPoint
                    ? '<p class="font-mono text-[0.62rem] text-forest-700">' . e($store['latitude']) . ', ' . e($store['longitude']) . '</p>'
                        . '<a href="' . e(store_maps_link($store)) . '" target="_blank" rel="noopener" class="text-[0.62rem] font-bold text-forest-600 hover:underline">Buka peta</a>'
                    : at_badge('badge-warning', 'Belum diset'),
                '<span class="font-bold">' . (int) $store['admin_count'] . '</span>',
                '<span class="font-bold">' . (int) $store['booking_count'] . '</span>',
                at_badge((int) $store['is_active'] ? 'badge-success' : 'badge-danger', (int) $store['is_active'] ? 'Aktif' : 'Nonaktif'),
                '<div class="flex justify-end gap-1">'
                    . '<a href="' . e(url('toko.php?store=' . rawurlencode($store['slug']))) . '" target="_blank" rel="noopener" class="icon-btn h-8 w-8" aria-label="Lihat halaman toko">' . icon('arrow-up-right', 'h-3.5 w-3.5') . '</a>'
                    . '<button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="' . e(url('api/admin.php?action=store_form&id=' . (int) $store['id'])) . '" data-admin-modal-title="Edit ' . e($store['name']) . '" aria-label="Edit toko">' . icon('edit', 'h-3.5 w-3.5') . '</button>'
                    . '<button type="button" class="icon-btn h-8 w-8 ' . ((int) $store['is_active'] ? 'text-danger' : '') . '" data-store-toggle data-id="' . (int) $store['id'] . '" data-active="' . (int) $store['is_active'] . '" aria-label="Ubah status toko">' . icon('shield', 'h-3.5 w-3.5') . '</button>'
                    . '<button type="button" class="icon-btn h-8 w-8 text-danger" data-admin-delete="store" data-id="' . (int) $store['id'] . '" data-name="' . e($store['name']) . '" aria-label="Hapus toko">' . icon('trash', 'h-3.5 w-3.5') . '</button>'
                    . '</div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'dashboard_recent':
        $storeId = $scopeStoreId ?? input_int('store_id');
        $base = 'FROM bookings b JOIN users u ON u.id = b.user_id LEFT JOIN stores s ON s.id = b.store_id';
        $where = '1=1';
        $params = [];
        if ($storeId > 0) {
            $where = 'b.store_id = ?';
            $params[] = $storeId;
        }
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base WHERE $where", $params);
        $recordsFiltered = $recordsTotal;
        $items = at_rows(
            "SELECT b.*, u.name AS user_name, s.name AS store_name
             $base WHERE $where ORDER BY b.created_at DESC"
            . at_limit($length, $start),
            $params
        );
        foreach ($items as $row) {
            $cells = [
                '<a href="' . e(url('admin/pesanan.php?id=' . (int) $row['id'])) . '" class="font-extrabold text-forest-900 hover:underline">' . e($row['code']) . '</a>'
                    . '<p class="text-[0.62rem] text-muted">' . e(time_ago($row['created_at'])) . '</p>',
                '<p class="font-semibold">' . e($row['user_name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e(format_date_id($row['start_date'])) . '</p>',
                '<span class="font-bold">' . e(format_rupiah($row['total'])) . '</span>',
                at_badge(status_class((string) $row['status']), status_label((string) $row['status'], $row['pickup_method'] ?? null)),
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'report_bookings':
        $storeId = $scopeStoreId ?? input_int('store_id');
        $from = trim((string) input('from', date('Y-m-01')));
        $to = trim((string) input('to', today()));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = today();
        }
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $base = 'FROM bookings b JOIN users u ON u.id = b.user_id LEFT JOIN stores s ON s.id = b.store_id';
        $where = 'DATE(b.created_at) BETWEEN ? AND ?';
        $params = [$from, $to];
        if ($storeId > 0) {
            $where .= ' AND b.store_id = ?';
            $params[] = $storeId;
        }
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base WHERE $where", $params);
        $recordsFiltered = $recordsTotal;
        $revenueParams = [$from, $to];
        $revenueWhere = "p.status = 'verified' AND DATE(p.paid_at) BETWEEN ? AND ?";
        if ($storeId > 0) {
            $revenueWhere .= ' AND b.store_id = ?';
            $revenueParams[] = $storeId;
        }
        $revenue = (float) at_value(
            "SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE $revenueWhere",
            $revenueParams
        );
        $stats = ['report_total' => format_rupiah($revenue)];

        $orderMap = [
            0 => 'b.code',
            1 => 'u.name',
            2 => 'b.start_date',
            3 => 'b.total',
            4 => 'b.status',
            5 => 'b.payment_status',
        ];
        $orderSql = ($orderMap[$orderColumn] ?? 'b.created_at') . ' ' . $orderDir;
        $items = at_rows(
            "SELECT b.code, b.created_at, u.name AS customer, u.phone, b.start_date, b.end_date,
                b.duration_days, b.total, b.status, b.payment_status, b.pickup_method, s.name AS store_name
             $base WHERE $where ORDER BY $orderSql, b.id DESC"
            . at_limit($length, $start),
            $params
        );
        foreach ($items as $row) {
            $cells = [
                '<a href="' . e(url('admin/pesanan.php?q=' . rawurlencode($row['code']))) . '" class="font-extrabold text-forest-900 hover:underline">' . e($row['code']) . '</a>'
                    . '<p class="text-[0.62rem] text-muted">' . e(time_ago($row['created_at'])) . '</p>',
                '<p class="font-bold">' . e($row['customer']) . '</p><p class="text-[0.62rem] text-muted">' . e($row['phone']) . '</p>',
                '<span class="text-xs">' . e(format_date_id($row['start_date'])) . ' &ndash; ' . e(format_date_id($row['end_date'])) . '</span>',
                '<span class="font-bold">' . e(format_rupiah($row['total'])) . '</span>',
                at_badge(status_class((string) $row['status']), status_label((string) $row['status'], $row['pickup_method'] ?? null)),
                at_badge(payment_status_class((string) $row['payment_status']), payment_status_label((string) $row['payment_status'])),
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'report_top_items':
        $storeId = $scopeStoreId ?? input_int('store_id');
        $all = Report::topItems(500, $storeId > 0 ? $storeId : null);
        $recordsTotal = count($all);
        $recordsFiltered = $recordsTotal;
        foreach (array_slice($all, $start, $length) as $item) {
            $cells = [
                '<div class="flex items-center gap-2.5">'
                    . '<div class="h-9 w-9 overflow-hidden rounded-lg bg-cream-100"><img src="' . e(upload_url($item['primary_image'])) . '" alt="" loading="lazy" class="h-full w-full object-cover"></div>'
                    . '<div><p class="max-w-[14rem] truncate font-bold text-forest-900">' . e($item['name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($item['category_name']) . '</p></div></div>',
                '<span class="font-bold">' . (int) $item['rented_qty'] . ' unit</span>',
                '<span class="font-bold">' . e(format_rupiah($item['revenue'])) . '</span>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'comparison_stores':
        if (!is_superadmin()) {
            json_error('Akses khusus superadmin.', 403);
        }
        $from = trim((string) input('from', date('Y-m-01')));
        $to = trim((string) input('to', today()));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = today();
        }
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $all = Report::storePerformance($from, $to);
        $recordsTotal = count($all);
        $recordsFiltered = $recordsTotal;
        foreach (array_slice($all, $start, $length) as $row) {
            $cells = [
                '<span class="inline-flex h-8 w-8 items-center justify-center rounded-full ' . ($row['rank'] <= 3 ? 'bg-forest-900 text-mint-300' : 'bg-cream-100 text-forest-700') . ' text-xs font-extrabold">#' . (int) $row['rank'] . '</span>',
                '<p class="font-extrabold text-forest-900">' . e($row['name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($row['code']) . ' &middot; ' . e($row['city']) . '</p>',
                '<span class="font-extrabold">' . e(format_rupiah($row['revenue'])) . '</span>',
                '<span class="font-bold">' . (int) $row['orders'] . '</span>',
                '<span>' . e(format_rupiah_short($row['aov'])) . '</span>',
                '<span class="badge ' . ($row['growth'] >= 0 ? 'badge-success' : 'badge-danger') . '">' . ($row['growth'] >= 0 ? '+' : '') . number_format((float) $row['growth'], 1, ',', '.') . '%</span>',
                '<div class="flex items-center gap-2">'
                    . '<div class="h-1.5 w-16 overflow-hidden rounded-full bg-cream-200"><div class="h-full rounded-full bg-forest-700" style="width:' . (int) $row['utilization'] . '%"></div></div>'
                    . '<span class="text-[0.65rem] font-bold text-forest-700">' . (int) $row['utilization'] . '%</span></div>',
                at_badge((int) $row['late_returns'] ? 'badge-danger' : 'badge-neutral', (string) (int) $row['late_returns']),
                at_badge((int) $row['pending_payments'] ? 'badge-warning' : 'badge-neutral', (string) (int) $row['pending_payments']),
                '<span class="text-xs font-bold">' . ($row['rating'] > 0 ? number_format((float) $row['rating'], 1, ',', '.') : '?') . '</span>',
                '<span class="text-xs"><span class="font-bold text-forest-900">' . (int) $row['items'] . '</span> / ' . (int) $row['stock'] . ' unit</span>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'customer_bookings':
        $userId = input_int('user_id');
        if ($userId <= 0) {
            json_error('Pelanggan tidak valid.', 422);
        }
        $storeId = $scopeStoreId ?? input_int('store_id');
        $where = 'b.user_id = ?';
        $params = [$userId];
        if ($storeId > 0) {
            $where .= ' AND b.store_id = ?';
            $params[] = $storeId;
        }
        $recordsTotal = (int) at_value("SELECT COUNT(*) FROM bookings b WHERE $where", $params);
        $recordsFiltered = $recordsTotal;
        $items = at_rows(
            "SELECT b.* FROM bookings b WHERE $where ORDER BY b.created_at DESC"
            . at_limit($length, $start),
            $params
        );
        foreach ($items as $row) {
            $cells = [
                '<a href="' . e(url('admin/pesanan.php?id=' . (int) $row['id'])) . '" class="font-extrabold text-forest-900 hover:underline">' . e($row['code']) . '</a>'
                    . '<p class="text-[0.62rem] text-muted">' . e(time_ago($row['created_at'])) . '</p>',
                '<span class="text-xs">' . e(format_date_id($row['start_date'])) . ' &ndash; ' . e(format_date_id($row['end_date'])) . '</span>',
                '<span class="font-bold">' . e(format_rupiah($row['total'])) . '</span>',
                at_badge(status_class((string) $row['status']), status_label((string) $row['status'], $row['pickup_method'] ?? null)),
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'booking_items':
        $bookingId = input_int('booking_id');
        if ($bookingId <= 0) {
            json_error('Pesanan tidak valid.', 422);
        }
        $booking = Booking::find($bookingId);
        if (!$booking) {
            json_error('Pesanan tidak ditemukan.', 404);
        }
        if ($scopeStoreId !== null && (int) $booking['store_id'] !== $scopeStoreId) {
            json_error('Pesanan ini bukan milik cabang Anda.', 403);
        }
        $all = Booking::items($bookingId);
        $recordsTotal = count($all);
        $recordsFiltered = $recordsTotal;
        foreach (array_slice($all, $start, $length) as $item) {
            $cells = [
                '<div class="flex items-center gap-2.5">'
                    . '<div class="h-9 w-9 overflow-hidden rounded-lg bg-cream-100"><img src="' . e(upload_url($item['primary_image'])) . '" alt="" loading="lazy" class="h-full w-full object-cover"></div>'
                    . '<div><p class="font-bold text-forest-900">' . e($item['item_name']) . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($item['category_name']) . ($item['variant_label'] ? ' ? ' . e($item['variant_label']) : '') . '</p></div></div>',
                '<span class="font-bold">' . (int) $item['qty'] . '</span>',
                '<span>' . e(format_rupiah($item['price_per_day'])) . '</span>',
                '<span>' . (int) $item['duration_days'] . ' hari</span>',
                '<span class="text-right font-extrabold">' . e(format_rupiah($item['subtotal'])) . '</span>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    case 'ai_sessions':
        if (!is_superadmin()) {
            json_error('Akses khusus superadmin.', 403);
        }
        $base = 'FROM ai_sessions s LEFT JOIN activities a ON a.id = s.activity_id LEFT JOIN users u ON u.id = s.user_id';
        $recordsTotal = (int) at_value("SELECT COUNT(*) $base");
        $recordsFiltered = $recordsTotal;
        $items = at_rows(
            "SELECT s.*, a.name AS activity_name, u.name AS user_name,
                (SELECT COUNT(*) FROM ai_session_items i WHERE i.ai_session_id = s.id) AS item_count
             $base ORDER BY s.created_at DESC"
            . at_limit($length, $start)
        );
        foreach ($items as $row) {
            $statusClass = $row['status'] === 'success' ? 'badge-success' : ($row['status'] === 'failed' ? 'badge-danger' : 'badge-warning');
            $cells = [
                '<p class="text-xs font-bold">' . e(time_ago($row['created_at'])) . '</p>'
                    . '<p class="text-[0.6rem] text-muted">' . e(format_datetime_id($row['created_at'])) . '</p>',
                '<span class="text-xs">' . e($row['user_name'] ?: 'Tamu') . '</span>',
                '<p class="text-xs font-bold text-forest-900">' . e($row['activity_name'] ?: '-') . '</p>'
                    . '<p class="text-[0.62rem] text-muted">' . e($row['destination_text'] ?: '-') . ' &middot; ' . (int) $row['duration_days'] . 'h</p>',
                '<span class="badge ' . $statusClass . '">' . e($row['status']) . '</span>'
                    . '<p class="mt-0.5 text-[0.6rem] text-muted">' . (int) $row['item_count'] . ' gear</p>',
                '<span class="text-[0.62rem] text-muted">' . e($row['model'] ?: '-') . '</span>',
                '<span class="text-xs">' . ($row['latency_ms'] ? number_format(((int) $row['latency_ms']) / 1000, 1, ',', '.') . 's' : '-') . '</span>',
                '<div class="flex justify-end"><a href="' . e(url('admin/rekomendasi.php?id=' . (int) $row['id'])) . '" class="btn btn-outline btn-sm">Detail</a></div>',
            ];
            $rows[] = ['cells' => $cells, 'meta' => []];
        }
        break;

    default:
        json_error('Dataset tabel tidak dikenal.', 404);
}

json_success([
    'dataset' => $dataset,
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $rows,
    'stats' => $stats,
]);
