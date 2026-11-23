<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$q = trim((string) ($_GET['q'] ?? ''));
$role = (string) ($_GET['role'] ?? '');
if (!in_array($role, ['', 'admin', 'customer'], true)) {
    $role = '';
}
$page = page_number();
$result = User::search($q, $role, $page, 15);
$users = $result['items'];
$pagination = $result['pagination'];
$adminCount = (int) Database::conn()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();

$adminTitle = 'Pengguna';
$adminPage = 'pengguna';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Akses & akun</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= format_number((int) $pagination['total']) ?> pengguna</h2>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-open-modal="user-create-template" data-modal-title="Tambah pengguna"><?= icon('plus', 'h-3.5 w-3.5') ?> Tambah pengguna</button>
    </div>

    <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-[0.68rem] leading-relaxed text-amber-800">
        <?= icon('shield', 'h-3.5 w-3.5') ?> Ada <?= $adminCount ?> admin aktif. Akun admin tidak dapat dinonaktifkan dari panel; kelola akses lewat database jika diperlukan.
    </div>

    <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get">
        <div class="relative min-w-[14rem] flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, telepon, member ID...">
        </div>
        <select class="select input !w-auto !py-2.5 text-sm" name="role">
            <option value="">Semua role</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[48rem]">
                <thead><tr><th>Pengguna</th><th>Kontak</th><th>Role</th><th>Bergabung</th><th>Login terakhir</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$users): ?><tr><td colspan="7" class="py-12 text-center text-sm text-muted">Tidak ada pengguna yang cocok.</td></tr><?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?= e(upload_url($user['avatar'])) ?>" alt="" class="h-9 w-9 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full <?= $user['role'] === 'admin' ? 'bg-forest-900 text-mint-300' : 'bg-mint-300 text-forest-900' ?> text-[0.62rem] font-extrabold"><?= e(initials($user['name'])) ?></span>
                                <?php endif; ?>
                                <div><p class="font-extrabold text-forest-900"><?= e($user['name']) ?></p><p class="text-[0.62rem] text-muted"><?= e($user['member_code'] ?: '—') ?></p></div>
                            </div>
                        </td>
                        <td><p class="text-xs"><?= e($user['email']) ?></p><p class="text-[0.62rem] text-muted"><?= e($user['phone']) ?></p></td>
                        <td><span class="badge <?= $user['role'] === 'admin' ? 'badge-dark' : 'badge-neutral' ?>"><?= e(ucfirst($user['role'])) ?></span></td>
                        <td class="text-xs text-muted"><?= e(format_date_id($user['created_at'])) ?></td>
                        <td class="text-xs text-muted"><?= e($user['last_login_at'] ? time_ago($user['last_login_at']) : '—') ?></td>
                        <td><span class="badge <?= (int) $user['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= (int) $user['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button type="button" class="icon-btn h-8 w-8" data-user-reset data-id="<?= (int) $user['id'] ?>" data-name="<?= e($user['name']) ?>" aria-label="Reset kata sandi"><?= icon('key', 'h-3.5 w-3.5') ?></button>
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <button type="button" class="icon-btn h-8 w-8 <?= (int) $user['is_active'] ? 'text-danger' : '' ?>" data-user-toggle data-id="<?= (int) $user['id'] ?>" data-active="<?= (int) $user['is_active'] ?>" aria-label="Ubah status akun"><?= icon('shield', 'h-3.5 w-3.5') ?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int) $pagination['pages'] > 1): ?>
            <div class="flex items-center justify-between border-t border-line px-4 py-3 text-xs text-muted">
                <span>Halaman <?= (int) $pagination['page'] ?> dari <?= (int) $pagination['pages'] ?></span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= (int) $pagination['pages']; $i++): ?>
                        <a href="<?= e(url('admin/pengguna.php?' . http_build_query(array_filter(['q' => $q ?: null, 'role' => $role ?: null, 'page' => $i])))) ?>" class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 font-extrabold <?= $i === (int) $pagination['page'] ? 'bg-forest-900 text-white' : 'border border-line bg-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<template id="user-create-template">
    <form class="space-y-4" data-admin-form="user-create">
        <input type="hidden" name="action" value="user_create">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label" for="new-user-name">Nama lengkap</label>
                <input id="new-user-name" class="input" name="name" required minlength="3" maxlength="120">
            </div>
            <div>
                <label class="field-label" for="new-user-email">Email</label>
                <input id="new-user-email" class="input" type="email" name="email" required>
            </div>
            <div>
                <label class="field-label" for="new-user-phone">WhatsApp</label>
                <input id="new-user-phone" class="input" name="phone" required inputmode="tel">
            </div>
            <div>
                <label class="field-label" for="new-user-role">Role</label>
                <select id="new-user-role" class="select input" name="role">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="new-user-password">Kata sandi awal</label>
                <input id="new-user-password" class="input" type="password" name="password" minlength="8" required>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line pt-4">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan pengguna</button>
        </div>
    </form>
</template>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
