<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once ROOT_PATH . '/includes/icons.php';

$admin = api_require_admin();

function admin_require_admin(): void
{
    if (!is_admin()) {
        json_error('Akses khusus admin.', 403);
    }
}

function admin_require_super(): void
{
    if (!is_superadmin()) {
        json_error('Aksi ini hanya untuk superadmin.', 403);
    }
}

function admin_store_form_html(?array $store): string
{
    $isEdit = $store !== null;
    $stores = Store::all();
    ob_start();
    ?>
    <form data-admin-form="store" enctype="multipart/form-data" class="space-y-5" data-store-form>
        <input type="hidden" name="action" value="store_save">
        <input type="hidden" name="id" value="<?= (int) ($store['id'] ?? 0) ?>">
        <input type="hidden" name="image_existing" value="<?= e($store['image'] ?? '') ?>">

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label" for="store-name">Nama toko <span class="text-danger">*</span></label>
                <input id="store-name" class="input" name="name" required maxlength="140" value="<?= e($store['name'] ?? '') ?>" placeholder="Rentify Karawang Kota">
            </div>
            <div>
                <label class="field-label" for="store-code">Kode cabang <span class="text-danger">*</span></label>
                <input id="store-code" class="input font-mono text-xs uppercase <?= is_superadmin() ? '' : 'opacity-60' ?>" name="code" required maxlength="24" value="<?= e($store['code'] ?? '') ?>" placeholder="RNT-KRW-01" <?= is_superadmin() ? '' : 'readonly' ?>>
            </div>
            <div>
                <label class="field-label" for="store-slug">Slug <span class="font-semibold text-muted">(opsional)</span></label>
                <input id="store-slug" class="input font-mono text-xs <?= is_superadmin() ? '' : 'opacity-60' ?>" name="slug" maxlength="160" value="<?= e($store['slug'] ?? '') ?>" placeholder="rentify-karawang-kota" <?= is_superadmin() ? '' : 'readonly' ?>>
            </div>
            <div>
                <label class="field-label" for="store-city">Kota <span class="text-danger">*</span></label>
                <input id="store-city" class="input" name="city" required maxlength="100" value="<?= e($store['city'] ?? '') ?>" placeholder="Karawang">
            </div>
            <div>
                <label class="field-label" for="store-province">Provinsi <span class="text-danger">*</span></label>
                <input id="store-province" class="input" name="province" required maxlength="100" value="<?= e($store['province'] ?? 'Jawa Barat') ?>">
            </div>
            <div>
                <label class="field-label" for="store-district">Kecamatan</label>
                <input id="store-district" class="input" name="district" maxlength="100" value="<?= e($store['district'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="store-postal">Kode pos</label>
                <input id="store-postal" class="input" name="postal_code" maxlength="10" value="<?= e($store['postal_code'] ?? '') ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="store-address">Alamat lengkap <span class="text-danger">*</span></label>
                <textarea id="store-address" class="input min-h-[4.5rem]" name="address" rows="2" required placeholder="Jl. ..."><?= e($store['address'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="field-label" for="store-phone">Telepon</label>
                <input id="store-phone" class="input" name="phone" maxlength="30" value="<?= e($store['phone'] ?? '') ?>" placeholder="0812...">
            </div>
            <div>
                <label class="field-label" for="store-whatsapp">WhatsApp</label>
                <input id="store-whatsapp" class="input" name="whatsapp" maxlength="30" value="<?= e($store['whatsapp'] ?? '') ?>" placeholder="62812...">
            </div>
            <div>
                <label class="field-label" for="store-email">Email</label>
                <input id="store-email" class="input" type="email" name="email" maxlength="190" value="<?= e($store['email'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="store-hours">Jam operasional</label>
                <input id="store-hours" class="input" name="opening_hours" maxlength="160" value="<?= e($store['opening_hours'] ?? '') ?>" placeholder="Setiap hari, 08.00-21.00 WIB">
            </div>
            <div>
                <label class="field-label" for="store-fee">Biaya antar (Rp)</label>
                <input id="store-fee" class="input" type="number" min="0" step="1000" name="delivery_fee" value="<?= (int) round((float) ($store['delivery_fee'] ?? 0)) ?>">
            </div>
            <div>
                <label class="field-label" for="store-sort">Urutan tampil</label>
                <input id="store-sort" class="input" type="number" min="0" name="sort_order" value="<?= e($store['sort_order'] ?? 0) ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="store-description">Deskripsi singkat</label>
                <textarea id="store-description" class="input min-h-[4rem]" name="description" rows="2" placeholder="Keunggulan cabang ini..."><?= e($store['description'] ?? '') ?></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="store-image">Foto toko</label>
                <div class="flex items-center gap-3">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-line bg-cream-100" data-image-preview>
                        <?php if (!empty($store['image'])): ?>
                            <img src="<?= e(upload_url($store['image'])) ?>" alt="" class="h-full w-full object-cover">
                        <?php else: ?>
                            <span class="flex h-full w-full items-center justify-center text-muted"><?= icon('image', 'h-6 w-6') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <input id="store-image" class="input text-xs" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-preview-input="[data-image-preview]">
                        <p class="helper mt-1.5">JPG/PNG/WebP, maks 5 MB. Kosongkan untuk mempertahankan foto lama.</p>
                    </div>
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-line p-4" data-store-map-section>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="field-label !mb-0">Titik lokasi toko</p>
                    <p class="helper mt-1">Klik peta atau geser marker untuk memilih lokasi. Basemap topografi dengan label nama tempat.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input id="store-map-search" class="input !w-auto min-w-[12rem] !py-2 text-xs" type="search" placeholder="Cari alamat..." data-store-map-search>
                    <button type="button" class="btn btn-outline btn-sm" data-store-map-geocode><?= icon('search', 'h-3.5 w-3.5') ?> Cari</button>
                    <button type="button" class="btn btn-outline btn-sm" data-store-map-locate><?= icon('compass', 'h-3.5 w-3.5') ?> Lokasi saya</button>
                </div>
            </div>
            <div id="store-map-picker" class="mt-3 h-72 w-full rounded-2xl border border-line" data-store-map-picker></div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="store-lat">Latitude <span class="text-danger">*</span></label>
                    <input id="store-lat" class="input font-mono text-xs" name="latitude" required inputmode="decimal" value="<?= e($store['latitude'] ?? '') ?>" placeholder="-6.3010000" data-store-lat>
                </div>
                <div>
                    <label class="field-label" for="store-lng">Longitude <span class="text-danger">*</span></label>
                    <input id="store-lng" class="input font-mono text-xs" name="longitude" required inputmode="decimal" value="<?= e($store['longitude'] ?? '') ?>" placeholder="107.3050000" data-store-lng>
                </div>
            </div>
            <p class="helper mt-2" data-store-map-status><?= $isEdit ? 'Titik saat ini: ' . e($store['latitude'] ?? '-') . ', ' . e($store['longitude'] ?? '-') : 'Belum ada titik lokasi.' ?></p>
            <p class="mt-1 text-[0.6rem] text-muted/80">Peta: Esri World Topographic · © OpenStreetMap</p>
        </section>

        <div class="grid gap-3 sm:grid-cols-3">
            <label class="flex items-center gap-2 rounded-xl bg-cream-50 px-3 py-3 text-sm font-bold text-forest-800"><input type="checkbox" name="is_active" value="1" class="h-4 w-4" <?= !$isEdit || !empty($store['is_active']) ? 'checked' : '' ?> <?= is_superadmin() ? '' : 'disabled' ?>> Toko aktif</label>
            <label class="flex items-center gap-2 rounded-xl bg-cream-50 px-3 py-3 text-sm font-bold text-forest-800"><input type="checkbox" name="is_default" value="1" class="h-4 w-4" <?= !empty($store['is_default']) ? 'checked' : '' ?> <?= is_superadmin() ? '' : 'disabled' ?>> Cabang default</label>
            <div class="rounded-xl bg-cream-50 px-3 py-2 text-[0.62rem] leading-relaxed text-muted">
                <?= count($stores) ?> cabang terdaftar. Default dipakai jika customer belum membagikan lokasi.
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-line pt-4">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan toko</button>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}

function admin_user_form_html(?array $user, array $stores): string
{
    $isEdit = $user !== null;
    $role = $user['role'] ?? 'customer';
    $selectedStore = (int) ($user['store_id'] ?? 0);
    ob_start();
    ?>
    <form data-admin-form="user" class="space-y-4">
        <input type="hidden" name="action" value="user_save">
        <input type="hidden" name="id" value="<?= (int) ($user['id'] ?? 0) ?>">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label" for="admin-user-name">Nama lengkap</label>
                <input id="admin-user-name" class="input" name="name" required minlength="3" maxlength="120" value="<?= e($user['name'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="admin-user-email">Email</label>
                <input id="admin-user-email" class="input" type="email" name="email" required value="<?= e($user['email'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="admin-user-phone">WhatsApp</label>
                <input id="admin-user-phone" class="input" name="phone" required inputmode="tel" value="<?= e($user['phone'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="admin-user-role">Role</label>
                <select id="admin-user-role" class="select input" name="role" data-user-role>
                    <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin Cabang</option>
                    <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                </select>
            </div>
            <div data-user-store-wrap class="<?= $role === 'admin' ? '' : 'hidden' ?>">
                <label class="field-label" for="admin-user-store">Toko cabang</label>
                <select id="admin-user-store" class="select input" name="store_id" data-user-store>
                    <option value="0">Pilih cabang...</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= (int) $store['id'] ?>" <?= (int) $store['id'] === $selectedStore ? 'selected' : '' ?>><?= e($store['name']) ?> — <?= e($store['city']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="helper mt-1">Admin cabang hanya melihat pesanan cabang ini.</p>
            </div>
            <div>
                <label class="field-label" for="admin-user-password"><?= $isEdit ? 'Kata sandi baru (opsional)' : 'Kata sandi awal' ?></label>
                <input id="admin-user-password" class="input" type="password" name="password" minlength="8" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                <p class="helper mt-1"><?= $isEdit ? 'Kosongkan jika tidak ingin mengubah.' : 'Minimal 8 karakter.' ?></p>
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_active" value="1" class="h-4 w-4" <?= !$isEdit || !empty($user['is_active']) ? 'checked' : '' ?>> Akun aktif</label>
            </div>
        </div>
        <div class="rounded-xl bg-cream-50 px-3 py-2.5 text-[0.65rem] leading-relaxed text-muted">
            Superadmin mengelola seluruh cabang, katalog, pengguna, dan pengaturan. Admin cabang mengelola pesanan dan pengembalian cabangnya.
        </div>
        <div class="flex justify-end gap-2 border-t border-line pt-4">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan pengguna</button>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}

/**
 * Form item lengkap (dipakai modal admin via AJAX).
 */
function admin_item_form_html(
    ?array $item,
    ?array $storeItem,
    array $stores,
    int $storeId,
    bool $canEditProduct,
    array $storeVariants = []
): string {
    $isEdit = $item !== null;
    $categories = Category::all();
    $selectedCategory = (int) ($item['category_id'] ?? ($categories[0]['id'] ?? 0));
    $filtersByCategory = [];
    foreach ($categories as $category) {
        $filtersByCategory[(int) $category['id']] = Category::filters((int) $category['id']);
    }
    $activeFilters = $isEdit ? Item::filterValues((int) $item['id']) : [];
    $specs = '';
    if ($isEdit && !empty($item['specs']) && is_array($item['specs'])) {
        $lines = [];
        foreach ($item['specs'] as $key => $value) {
            $lines[] = $key . ': ' . $value;
        }
        $specs = implode("\n", $lines);
    }
    $features = $isEdit && is_array($item['features'] ?? null) ? implode(', ', $item['features']) : '';
    $variants = $storeVariants ?: ($isEdit ? ($item['variants'] ?? []) : []);
    $inventory = $storeItem ?: [];
    $activeStore = null;
    foreach ($stores as $store) {
        if ((int) $store['id'] === $storeId) {
            $activeStore = $store;
            break;
        }
    }
    $price = $inventory || $item
        ? (int) round((float) ($inventory['price_per_day'] ?? $item['price_per_day'] ?? 0))
        : '';
    $deposit = $inventory || $item
        ? (int) round((float) ($inventory['deposit'] ?? $item['deposit'] ?? 0))
        : 0;
    $replacementValue = $inventory['replacement_value'] ?? $item['replacement_value'] ?? null;
    $replacement = $replacementValue !== null && $replacementValue !== ''
        ? (int) round((float) $replacementValue)
        : '';
    $stock = $inventory['stock_total'] ?? $item['stock_total'] ?? 1;
    $minDuration = $inventory['min_duration'] ?? $item['min_duration'] ?? 1;
    $maxDuration = $inventory['max_duration'] ?? $item['max_duration'] ?? 30;
    $isActive = $inventory['is_active'] ?? ($item['is_active'] ?? 1);
    $isFeatured = $inventory['is_featured'] ?? 0;
    $readonly = $canEditProduct ? '' : 'readonly';
    $disabled = $canEditProduct ? '' : 'disabled';

    ob_start();
    ?>
    <form data-admin-form="item" enctype="multipart/form-data" class="space-y-5" data-item-form>
        <input type="hidden" name="action" value="item_save">
        <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
        <?php if (!(is_superadmin() && !$isEdit)): ?>
            <input type="hidden" name="store_id" value="<?= $storeId ?>">
        <?php endif; ?>
        <input type="hidden" name="store_item_id" value="<?= (int) ($inventory['id'] ?? 0) ?>">
        <input type="hidden" name="primary_image_existing" value="<?= e($item['primary_image'] ?? '') ?>">

        <?php if (is_superadmin() && !$isEdit): ?>
            <div>
                <label class="field-label" for="item-store">Toko cabang <span class="text-danger">*</span></label>
                <select id="item-store" class="select input" name="store_id" required data-searchable>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= (int) $store['id'] ?>" <?= (int) $store['id'] === $storeId ? 'selected' : '' ?>><?= e($store['name']) ?> — <?= e($store['city']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="helper mt-1">Produk baru akan dibuat di master global dan otomatis masuk inventaris cabang ini.</p>
            </div>
        <?php else: ?>
            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-line bg-cream-50 px-3.5 py-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon('map-pin', 'h-4 w-4') ?></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-muted">Inventaris cabang</p>
                    <p class="text-xs font-extrabold text-forest-900"><?= e($activeStore['name'] ?? 'Cabang') ?> · <?= e($activeStore['city'] ?? '') ?></p>
                </div>
                <span class="badge badge-mint"><?= $canEditProduct ? 'Produk milik cabang / superadmin' : 'Produk global' ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$canEditProduct): ?>
            <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-[0.65rem] leading-relaxed text-amber-800">
                <?= icon('info', 'h-3.5 w-3.5') ?> Produk ini milik master global. Kamu hanya bisa mengubah harga, stok, varian, dan status untuk cabang ini.
            </div>
        <?php endif; ?>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label" for="item-name">Nama gear <span class="text-danger">*</span></label>
                <input id="item-name" class="input <?= $canEditProduct ? '' : 'opacity-60' ?>" name="name" required maxlength="180" value="<?= e($item['name'] ?? '') ?>" placeholder="Contoh: Tenda Dome Alpenaz 4P" <?= $readonly ?>>
            </div>
            <div>
                <label class="field-label" for="item-category">Kategori</label>
                <select id="item-category" class="select input <?= $canEditProduct ? '' : 'opacity-60' ?>" name="category_id" required data-item-category data-searchable <?= $disabled ?>>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === $selectedCategory ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label" for="item-brand">Brand</label>
                <input id="item-brand" class="input <?= $canEditProduct ? '' : 'opacity-60' ?>" name="brand" maxlength="100" value="<?= e($item['brand'] ?? '') ?>" placeholder="Eiger, Consina, Naturehike..." <?= $readonly ?>>
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="item-short">Deskripsi singkat</label>
                <input id="item-short" class="input <?= $canEditProduct ? '' : 'opacity-60' ?>" name="short_description" maxlength="255" value="<?= e($item['short_description'] ?? '') ?>" placeholder="Satu kalimat yang menjual gear ini." <?= $readonly ?>>
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="item-desc">Deskripsi lengkap</label>
                <textarea id="item-desc" class="input min-h-[7rem] <?= $canEditProduct ? '' : 'opacity-60' ?>" name="description" rows="4" placeholder="Ceritakan keunggulan, bahan, dan kondisi gear." <?= $readonly ?>><?= e($item['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="field-label" for="item-price">Harga sewa di cabang ini <span class="text-danger">*</span></label>
                <input id="item-price" class="input" type="number" min="0" step="500" name="price_per_day" required value="<?= e($price) ?>">
            </div>
            <div>
                <label class="field-label" for="item-deposit">Deposit cabang</label>
                <input id="item-deposit" class="input" type="number" min="0" step="500" name="deposit" value="<?= e($deposit) ?>">
            </div>
            <div>
                <label class="field-label" for="item-replacement">Nilai penggantian</label>
                <input id="item-replacement" class="input" type="number" min="0" step="1000" name="replacement_value" value="<?= e($replacement) ?>">
            </div>
            <div>
                <label class="field-label" for="item-stock">Stok di cabang ini</label>
                <input id="item-stock" class="input" type="number" min="0" name="stock_total" value="<?= e($stock) ?>">
            </div>
            <div>
                <label class="field-label" for="item-min">Min. durasi (hari)</label>
                <input id="item-min" class="input" type="number" min="1" name="min_duration" value="<?= e($minDuration) ?>">
            </div>
            <div>
                <label class="field-label" for="item-max">Maks. durasi (hari)</label>
                <input id="item-max" class="input" type="number" min="1" name="max_duration" value="<?= e($maxDuration) ?>">
            </div>
            <div>
                <label class="field-label" for="item-capacity">Kapasitas (orang)</label>
                <input id="item-capacity" class="input <?= $canEditProduct ? '' : 'opacity-60' ?>" type="number" min="0" name="capacity_person" value="<?= e($item['capacity_person'] ?? '') ?>" <?= $readonly ?>>
            </div>
            <div>
                <label class="field-label" for="item-weight">Berat (gram)</label>
                <input id="item-weight" class="input <?= $canEditProduct ? '' : 'opacity-60' ?>" type="number" min="0" name="weight_gram" value="<?= e($item['weight_gram'] ?? '') ?>" <?= $readonly ?>>
            </div>
            <?php if ($canEditProduct): ?>
                <div class="sm:col-span-2">
                    <label class="field-label" for="item-image">Foto utama</label>
                    <div class="flex items-center gap-3">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-line bg-cream-100" data-image-preview>
                            <?php if (!empty($item['primary_image'])): ?>
                                <img src="<?= e(upload_url($item['primary_image'])) ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <span class="flex h-full w-full items-center justify-center text-muted"><?= icon('image', 'h-6 w-6') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="item-image" class="input text-xs" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-preview-input="[data-image-preview]">
                            <p class="helper mt-1.5">JPG/PNG/WebP, maks 5 MB. Kosongkan untuk mempertahankan foto lama.</p>
                        </div>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="item-features">Fitur (pisahkan dengan koma)</label>
                    <input id="item-features" class="input" name="features" value="<?= e($features) ?>" placeholder="Waterproof, 2 pintu, frame aluminium">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="item-specs">Spesifikasi (satu per baris, format <code>Kunci: Nilai</code>)</label>
                    <textarea id="item-specs" class="input min-h-[6rem] font-mono text-xs" name="specs" rows="4" placeholder="Kapasitas: 4 orang&#10;Berat: 3.4 kg"><?= e($specs) ?></textarea>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-2xl bg-cream-100 px-4 py-3">
            <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded border-line" <?= !empty($isFeatured) ? 'checked' : '' ?>> Unggulan di cabang ini</label>
            <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-line" <?= !empty($isActive) ? 'checked' : '' ?>> Aktif di cabang ini</label>
        </div>

        <?php if ($canEditProduct): ?>
            <section>
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-extrabold text-forest-900">Filter kategori</h3>
                        <p class="helper mt-0.5">Filter mengikuti kategori yang dipilih.</p>
                    </div>
                </div>
                <div class="mt-3 space-y-3" data-filter-groups>
                    <?php foreach ($categories as $category): ?>
                        <div data-filter-group="<?= (int) $category['id'] ?>" class="<?= (int) $category['id'] === $selectedCategory ? '' : 'hidden' ?>">
                            <?php foreach (($filtersByCategory[(int) $category['id']] ?? []) as $filter): ?>
                                <div class="mb-3">
                                    <p class="mb-2 text-[0.62rem] font-extrabold uppercase tracking-[0.12em] text-forest-500"><?= e($filter['label']) ?></p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach (($filter['options'] ?? []) as $option): ?>
                                            <?php $checked = in_array($option, $activeFilters[$filter['filter_key']] ?? [], true); ?>
                                            <label class="chip cursor-pointer !py-1.5 <?= $checked ? 'chip-mint font-bold' : '' ?>">
                                                <input type="checkbox" name="filters[<?= (int) $filter['id'] ?>][]" value="<?= e($option) ?>" class="h-3.5 w-3.5" <?= $checked ? 'checked' : '' ?>>
                                                <?= e($option) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($filtersByCategory[(int) $category['id']])): ?>
                                <p class="helper">Kategori ini belum punya filter.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section>
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-extrabold text-forest-900">Varian / ukuran di cabang ini</h3>
                    <p class="helper mt-0.5">Stok dan harga varian hanya berlaku untuk cabang ini.</p>
                </div>
                <?php if ($canEditProduct): ?>
                    <button type="button" class="btn btn-outline btn-sm" data-add-variant><?= icon('plus', 'h-3.5 w-3.5') ?> Varian</button>
                <?php endif; ?>
            </div>
            <div class="mt-3 space-y-2" data-variant-list>
                <?php if (!$variants && $canEditProduct): ?>
                    <div class="grid gap-2 rounded-2xl border border-dashed border-line p-3 sm:grid-cols-[.7fr_1.4fr_.8fr_.8fr_.8fr_auto]" data-variant-row>
                        <input type="hidden" name="variant_id[]" value="0">
                        <input class="input !py-2 text-xs" name="variant_label[]" placeholder="Label (mis. 4P)">
                        <input class="input !py-2 text-xs" name="variant_sku[]" placeholder="SKU">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_stock[]" placeholder="Stok" value="0">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_price[]" placeholder="Harga/hari">
                        <button type="button" class="icon-btn h-9 w-9 self-center text-danger" data-remove-variant aria-label="Hapus varian"><?= icon('trash', 'h-4 w-4') ?></button>
                    </div>
                <?php endif; ?>
                <?php foreach ($variants as $variant): ?>
                    <div class="grid gap-2 rounded-2xl border border-line p-3 sm:grid-cols-[.7fr_1.4fr_.8fr_.8fr_.8fr_auto]" data-variant-row>
                        <input type="hidden" name="variant_id[]" value="<?= (int) ($variant['id'] ?? 0) ?>">
                        <?php if ($canEditProduct): ?>
                            <input class="input !py-2 text-xs" name="variant_label[]" value="<?= e($variant['label'] ?? '') ?>" placeholder="Label">
                            <input class="input !py-2 text-xs" name="variant_sku[]" value="<?= e($variant['sku'] ?? '') ?>" placeholder="SKU">
                        <?php else: ?>
                            <div class="flex items-center px-1 text-xs font-extrabold text-forest-900"><?= e($variant['label'] ?? '') ?></div>
                            <input type="hidden" name="variant_label[]" value="<?= e($variant['label'] ?? '') ?>">
                            <input type="hidden" name="variant_sku[]" value="<?= e($variant['sku'] ?? '') ?>">
                        <?php endif; ?>
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_stock[]" value="<?= (int) ($variant['stock_total'] ?? 0) ?>" placeholder="Stok">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_price[]" value="<?= e($variant['price_per_day'] ?? '') ?>" placeholder="Harga/hari">
                        <?php if ($canEditProduct): ?>
                            <button type="button" class="icon-btn h-9 w-9 self-center text-danger" data-remove-variant aria-label="Hapus varian"><?= icon('trash', 'h-4 w-4') ?></button>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary"><?= icon('check', 'h-4 w-4') ?> Simpan barang cabang</button>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}

function admin_category_form_html(?array $category): string
{
    $isEdit = $category !== null;
    $iconOptions = ['tent', 'backpack', 'boot', 'jacket', 'sleep', 'lamp', 'compass', 'stove', 'hammock', 'droplet', 'zap', 'package'];
    ob_start();
    ?>
    <form data-admin-form="category" class="space-y-4">
        <input type="hidden" name="action" value="category_save">
        <input type="hidden" name="id" value="<?= (int) ($category['id'] ?? 0) ?>">
        <div>
            <label class="field-label" for="category-name">Nama kategori <span class="text-danger">*</span></label>
            <input id="category-name" class="input" name="name" required value="<?= e($category['name'] ?? '') ?>" placeholder="Tenda & Shelter">
        </div>
        <div>
            <label class="field-label" for="category-desc">Deskripsi</label>
            <textarea id="category-desc" class="input min-h-[5rem]" name="description" rows="3"><?= e($category['description'] ?? '') ?></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="field-label" for="category-icon">Ikon</label>
                <select id="category-icon" class="select input" name="icon">
                    <?php foreach ($iconOptions as $iconName): ?>
                        <option value="<?= e($iconName) ?>" <?= ($category['icon'] ?? '') === $iconName ? 'selected' : '' ?>><?= e($iconName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label" for="category-accent">Warna aksen</label>
                <input id="category-accent" class="input h-[3rem] !p-1" type="color" name="accent_color" value="<?= e($category['accent_color'] ?? '#CBE8CE') ?>">
            </div>
            <div>
                <label class="field-label" for="category-sort">Urutan</label>
                <input id="category-sort" class="input" type="number" name="sort_order" value="<?= (int) ($category['sort_order'] ?? 0) ?>">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_active" value="1" class="h-4 w-4" <?= !$isEdit || !empty($category['is_active']) ? 'checked' : '' ?>> Aktif</label>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line pt-4">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan kategori</button>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}

function admin_pickup_form_html(?array $point): string
{
    $isEdit = $point !== null;
    ob_start();
    ?>
    <form data-admin-form="pickup" class="space-y-4">
        <input type="hidden" name="action" value="pickup_save">
        <input type="hidden" name="id" value="<?= (int) ($point['id'] ?? 0) ?>">
        <div>
            <label class="field-label" for="pickup-name">Nama titik <span class="text-danger">*</span></label>
            <input id="pickup-name" class="input" name="name" required value="<?= e($point['name'] ?? '') ?>" placeholder="Basecamp Karawang Kota">
        </div>
        <div>
            <label class="field-label" for="pickup-address">Alamat</label>
            <textarea id="pickup-address" class="input min-h-[5rem]" name="address" rows="2" required><?= e($point['address'] ?? '') ?></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="field-label" for="pickup-hours">Jam operasional</label>
                <input id="pickup-hours" class="input" name="hours" value="<?= e($point['hours'] ?? '') ?>" placeholder="08.00–21.00 WIB">
            </div>
            <div>
                <label class="field-label" for="pickup-maps">URL Maps</label>
                <input id="pickup-maps" class="input" name="maps_url" value="<?= e($point['maps_url'] ?? '') ?>" placeholder="https://maps.google.com/...">
            </div>
            <div>
                <label class="field-label" for="pickup-lat">Latitude</label>
                <input id="pickup-lat" class="input" name="latitude" value="<?= e($point['latitude'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="pickup-lng">Longitude</label>
                <input id="pickup-lng" class="input" name="longitude" value="<?= e($point['longitude'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="pickup-sort">Urutan</label>
                <input id="pickup-sort" class="input" type="number" name="sort_order" value="<?= (int) ($point['sort_order'] ?? 0) ?>">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_active" value="1" class="h-4 w-4" <?= !$isEdit || !empty($point['is_active']) ? 'checked' : '' ?>> Aktif</label>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line pt-4">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan titik</button>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $action = (string) input('action', '');
    if ($action === 'store_form') {
        admin_require_admin();
        $scopeStoreId = admin_store_id();
        $id = $scopeStoreId !== null ? $scopeStoreId : input_int('id');
        $store = $id ? Store::find($id) : null;
        if ($id && !$store) {
            json_error('Toko tidak ditemukan.', 404);
        }
        if ($scopeStoreId !== null && !$store) {
            json_error('Anda belum terhubung ke toko cabang.', 404);
        }
        json_success([
            'title' => $store ? 'Edit ' . $store['name'] : 'Tambah toko cabang',
            'html' => admin_store_form_html($store),
            'id' => $id,
        ]);
    }
    if ($action === 'user_form') {
        admin_require_super();
        $id = input_int('id');
        $editUser = $id ? User::find($id) : null;
        if ($id && !$editUser) {
            json_error('Pengguna tidak ditemukan.', 404);
        }
        json_success([
            'title' => $editUser ? 'Edit ' . $editUser['name'] : 'Tambah pengguna',
            'html' => admin_user_form_html($editUser, Store::all()),
            'id' => $id,
        ]);
    }
    if ($action === 'item_form') {
        admin_require_admin();
        $id = input_int('id');
        $item = $id ? Item::find($id) : null;
        if ($id && !$item) {
            json_error('Item tidak ditemukan.', 404);
        }
        $scopeStoreId = admin_store_id();
        $storeId = $scopeStoreId !== null ? $scopeStoreId : input_int('store_id');
        if ($storeId <= 0) {
            $defaultStore = Store::defaultStore();
            $storeId = $defaultStore ? (int) $defaultStore['id'] : 0;
        }
        $store = $storeId > 0 ? Store::find($storeId) : null;
        if (!$store || (int) $store['is_active'] !== 1) {
            json_error('Toko cabang tidak ditemukan.', 404);
        }
        $storeItem = $id ? StoreItem::findForStore($storeId, $id) : null;
        if ($id && !$storeItem) {
            json_error('Barang ini belum terdaftar di cabang Anda.', 404);
        }
        $canEditProduct = is_superadmin() || !$item || (int) ($item['owner_store_id'] ?? 0) === $storeId;
        $storeVariants = $storeItem ? StoreItem::variants((int) $storeItem['id']) : [];
        $stores = is_superadmin() ? Store::all(true) : [$store];
        $html = admin_item_form_html($item, $storeItem, $stores, $storeId, $canEditProduct, $storeVariants);
        json_success([
            'title' => $item ? 'Edit ' . $item['name'] . ' · ' . $store['name'] : 'Tambah gear di ' . $store['name'],
            'html' => $html,
            'id' => $id,
            'store_id' => $storeId,
        ]);
    }
    if ($action === 'category_form') {
        admin_require_super();
        $id = input_int('id');
        $category = $id ? Category::find($id) : null;
        if ($id && !$category) {
            json_error('Kategori tidak ditemukan.', 404);
        }
        json_success([
            'title' => $category ? 'Edit kategori' : 'Tambah kategori',
            'html' => admin_category_form_html($category),
        ]);
    }
    if ($action === 'pickup_form') {
        admin_require_super();
        $id = input_int('id');
        $point = null;
        if ($id) {
            foreach (Setting::allPickupPoints() as $candidate) {
                if ((int) $candidate['id'] === $id) {
                    $point = $candidate;
                    break;
                }
            }
        }
        json_success([
            'title' => $point ? 'Edit titik ambil' : 'Tambah titik ambil',
            'html' => admin_pickup_form_html($point),
        ]);
    }
    if ($action === 'user_detail') {
        admin_require_super();
        $id = input_int('id');
        $customer = User::find($id);
        if (!$customer) {
            json_error('Pengguna tidak ditemukan.', 404);
        }
        $bookings = Booking::search(['q' => $customer['email']], 1, 20)['items'];
        json_success([
            'user' => $customer,
            'bookings' => $bookings,
            'stats' => User::stats($id),
        ]);
    }
    json_error('Aksi admin tidak dikenali.', 400);
}

$action = (string) input('action', '');

if ($action === 'store_save') {
    admin_require_admin();
    $scopeStoreId = admin_store_id();
    $id = $scopeStoreId !== null ? $scopeStoreId : input_int('id');
    $existing = $id ? Store::find($id) : null;
    if ($id && !$existing) {
        json_error('Toko tidak ditemukan.', 404);
    }
    if ($scopeStoreId !== null && !$existing) {
        json_error('Akun Anda belum terhubung ke toko cabang.', 404);
    }
    $name = trim((string) input('name', ''));
    $code = $existing ? (string) $existing['code'] : strtoupper(trim((string) input('code', '')));
    if ($scopeStoreId !== null) {
        $code = (string) $existing['code'];
    }
    $city = trim((string) input('city', ''));
    $province = trim((string) input('province', ''));
    $address = trim((string) input('address', ''));
    $latitude = trim((string) input('latitude', ''));
    $longitude = trim((string) input('longitude', ''));
    $email = strtolower(trim((string) input('email', '')));

    if (mb_strlen($name) < 3) {
        json_error('Nama toko minimal 3 karakter.', 422, ['name' => 'Nama terlalu pendek.']);
    }
    if (!preg_match('/^[A-Z0-9-]{3,24}$/', $code)) {
        json_error('Kode cabang hanya boleh huruf, angka, dan tanda hubung (3-24 karakter).', 422, ['code' => 'Kode tidak valid.']);
    }
    if ($city === '' || $province === '' || $address === '') {
        json_error('Kota, provinsi, dan alamat wajib diisi.', 422);
    }
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        json_error('Pilih titik lokasi toko di peta.', 422, ['latitude' => 'Koordinat wajib diisi.']);
    }
    $lat = (float) $latitude;
    $lng = (float) $longitude;
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        json_error('Koordinat lokasi tidak valid.', 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Format email toko tidak valid.', 422, ['email' => 'Email tidak valid.']);
    }
    $isActive = $scopeStoreId !== null ? (int) $existing['is_active'] : input_bool('is_active');
    $isDefault = $scopeStoreId !== null ? (int) $existing['is_default'] : input_bool('is_default');
    $sortOrder = $scopeStoreId !== null ? (int) $existing['sort_order'] : input_int('sort_order');
    if (!$isActive) {
        $check = Database::conn()->prepare('SELECT COUNT(*) FROM stores WHERE is_active = 1 AND id <> ?');
        $check->execute([$id ?: 0]);
        $activeOthers = (int) $check->fetchColumn();
        if ($activeOthers === 0) {
            json_error('Minimal satu toko cabang harus aktif.', 422);
        }
        if ($isDefault) {
            json_error('Cabang default harus dalam status aktif.', 422);
        }
    }

    $stmt = Database::conn()->prepare('SELECT id FROM stores WHERE code = ? AND id <> ? LIMIT 1');
    $stmt->execute([$code, $id ?: 0]);
    if ($stmt->fetchColumn()) {
        json_error('Kode cabang sudah dipakai.', 422, ['code' => 'Kode sudah dipakai.']);
    }

    if ($scopeStoreId !== null && $existing) {
        $slug = (string) $existing['slug'];
    } else {
        $slug = slugify(trim((string) input('slug', '')) ?: $name);
        if ($slug === '') {
            $slug = strtolower($code);
        }
        $slugBase = $slug;
        $suffix = 2;
        while (true) {
            $stmt = Database::conn()->prepare('SELECT id FROM stores WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->execute([$slug, $id ?: 0]);
            if (!$stmt->fetchColumn()) {
                break;
            }
            $slug = $slugBase . '-' . $suffix++;
        }
    }

    $image = trim((string) input('image_existing', ''));
    if (!empty($_FILES['image']['name'])) {
        try {
            $image = upload_image($_FILES['image'], 'stores', 5242880);
        } catch (Throwable $exception) {
            json_error($exception->getMessage(), 422, ['image' => $exception->getMessage()]);
        }
    }

    $result = Store::save($id ?: null, [
        'code' => $code,
        'name' => $name,
        'slug' => $slug,
        'address' => $address,
        'district' => trim((string) input('district', '')),
        'city' => $city,
        'province' => $province,
        'postal_code' => trim((string) input('postal_code', '')),
        'phone' => trim((string) input('phone', '')),
        'whatsapp' => trim((string) input('whatsapp', '')),
        'email' => $email,
        'latitude' => $lat,
        'longitude' => $lng,
        'maps_url' => trim((string) input('maps_url', '')),
        'opening_hours' => trim((string) input('opening_hours', '')),
        'description' => trim((string) input('description', '')),
        'image' => $image,
        'delivery_fee' => (float) input('delivery_fee', 0),
        'is_active' => $isActive,
        'is_default' => $isDefault,
        'sort_order' => $sortOrder,
    ]);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit($id ? 'store.update' : 'store.create', 'store', (int) $result['id'], ['name' => $name, 'code' => $code]);
    json_success([
        'message' => $result['message'] . ' Titik pickup dan lokasi ikut diperbarui.',
        'id' => (int) $result['id'],
        'reload' => true,
    ]);
}

if ($action === 'store_delete') {
    admin_require_super();
    $id = input_int('id');
    $result = Store::delete($id);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('store.delete', 'store', $id);
    json_success(['message' => $result['message'], 'reload' => true]);
}

if ($action === 'store_toggle') {
    admin_require_super();
    $id = input_int('id');
    $store = Store::find($id);
    if (!$store) {
        json_error('Toko tidak ditemukan.', 404);
    }
    $new = (int) $store['is_active'] === 1 ? 0 : 1;
    if ($new === 0) {
        $active = (int) Database::conn()->query('SELECT COUNT(*) FROM stores WHERE is_active = 1')->fetchColumn();
        if ($active <= 1) {
            json_error('Minimal satu toko harus aktif.');
        }
    }
    Database::conn()->prepare('UPDATE stores SET is_active = ? WHERE id = ?')->execute([$new, $id]);
    if ($new === 1) {
        Database::conn()->prepare('UPDATE pickup_points SET is_active = 1 WHERE store_id = ?')->execute([$id]);
    } else {
        Database::conn()->prepare('UPDATE pickup_points SET is_active = 0 WHERE store_id = ?')->execute([$id]);
        if ((int) $store['is_default'] === 1) {
            $next = (int) Database::conn()->query('SELECT id FROM stores WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn();
            if ($next) {
                Database::conn()->prepare('UPDATE stores SET is_default = 1 WHERE id = ?')->execute([$next]);
            }
        }
    }
    log_audit('store.toggle', 'store', $id, ['is_active' => $new]);
    json_success(['message' => $new ? 'Toko diaktifkan.' : 'Toko dinonaktifkan.', 'is_active' => $new]);
}

if ($action === 'user_save') {
    admin_require_super();
    $id = input_int('id');
    $existing = $id ? User::find($id) : null;
    if ($id && !$existing) {
        json_error('Pengguna tidak ditemukan.', 404);
    }
    $name = trim((string) input('name', ''));
    $email = strtolower(trim((string) input('email', '')));
    $phone = trim((string) input('phone', ''));
    $role = (string) input('role', 'customer');
    $storeId = input_int('store_id');
    $password = (string) input('password', '');
    $isActive = input_bool('is_active', true);

    if (mb_strlen($name) < 3) {
        json_error('Nama minimal 3 karakter.', 422, ['name' => 'Nama terlalu pendek.']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Email tidak valid.', 422, ['email' => 'Email tidak valid.']);
    }
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
        json_error('Nomor WhatsApp tidak valid.', 422, ['phone' => 'Nomor tidak valid.']);
    }
    if (!in_array($role, ['superadmin', 'admin', 'customer'], true)) {
        json_error('Role tidak valid.', 422);
    }
    if ($role === 'admin' && $storeId <= 0) {
        json_error('Pilih toko untuk admin cabang.', 422, ['store_id' => 'Toko wajib dipilih.']);
    }
    if ($role === 'admin') {
        $stmt = Database::conn()->prepare(
            "SELECT u.id, u.name FROM users u
             WHERE u.store_id = ? AND u.role = 'admin' AND u.is_active = 1 AND u.id <> ?
             LIMIT 1"
        );
        $stmt->execute([$storeId, $id ?: 0]);
        $occupied = $stmt->fetch();
        if ($occupied) {
            json_error('Toko ini sudah dipegang ' . $occupied['name'] . '. Pindahkan admin lama terlebih dahulu.', 422, ['store_id' => 'Toko sudah memiliki admin aktif.']);
        }
    }
    if ($password !== '' && strlen($password) < 8) {
        json_error('Kata sandi minimal 8 karakter.', 422, ['password' => 'Terlalu pendek.']);
    }
    if ($id && $id === (int) $admin['id']) {
        if ($role !== 'superadmin' || !$isActive) {
            json_error('Anda tidak dapat menurunkan atau menonaktifkan akun sendiri.');
        }
    }
    if ($existing && $existing['role'] === 'superadmin' && ($role !== 'superadmin' || !$isActive)) {
        $activeSupers = (int) Database::conn()->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND is_active = 1")->fetchColumn();
        if ($activeSupers <= 1) {
            json_error('Minimal satu superadmin aktif harus tersedia.');
        }
    }

    $result = User::saveAdmin($id ?: null, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'role' => $role,
        'store_id' => $role === 'admin' ? $storeId : null,
        'password' => $password,
        'is_active' => $isActive ? 1 : 0,
    ]);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit($id ? 'user.update' : 'user.create', 'user', (int) $result['id'], ['role' => $role, 'store_id' => $role === 'admin' ? $storeId : null]);
    json_success(['message' => $result['message'], 'id' => (int) $result['id'], 'reload' => true]);
}

if ($action === 'item_save') {
    admin_require_admin();
    $id = input_int('id');
    $existingItem = $id ? Item::find($id) : null;
    if ($id && !$existingItem) {
        json_error('Item tidak ditemukan.', 404);
    }
    $scopeStoreId = admin_store_id();
    $storeId = $scopeStoreId !== null ? $scopeStoreId : input_int('store_id');
    $store = $storeId > 0 ? Store::find($storeId) : null;
    if (!$store || (int) $store['is_active'] !== 1) {
        json_error('Toko cabang tidak ditemukan.', 422);
    }
    if ($existingItem && $scopeStoreId !== null && !StoreItem::findForStore($storeId, $id)) {
        json_error('Barang ini bukan milik cabang Anda.', 403);
    }

    $canEditProduct = is_superadmin()
        || !$existingItem
        || (int) ($existingItem['owner_store_id'] ?? 0) === $storeId;

    $name = $canEditProduct ? trim((string) input('name', '')) : (string) ($existingItem['name'] ?? '');
    $categoryId = $canEditProduct ? input_int('category_id') : (int) ($existingItem['category_id'] ?? 0);
    $price = (float) input('price_per_day', 0);
    if (mb_strlen($name) < 3) {
        json_error('Nama gear minimal 3 karakter.', 422, ['name' => 'Nama terlalu pendek.']);
    }
    if (!$categoryId || !Category::find($categoryId)) {
        json_error('Kategori tidak valid.', 422, ['category_id' => 'Pilih kategori.']);
    }
    if ($price <= 0) {
        json_error('Harga sewa di cabang ini harus lebih dari 0.', 422, ['price_per_day' => 'Harga tidak valid.']);
    }

    $primaryImage = $canEditProduct
        ? trim((string) input('primary_image_existing', ''))
        : (string) ($existingItem['primary_image'] ?? '');
    if ($canEditProduct && !empty($_FILES['image']['name'])) {
        try {
            $primaryImage = upload_image($_FILES['image'], 'items', 5242880);
        } catch (Throwable $exception) {
            json_error($exception->getMessage(), 422, ['image' => $exception->getMessage()]);
        }
    }

    $filters = [];
    if ($canEditProduct) {
        $rawFilters = input('filters', []);
        if (is_array($rawFilters)) {
            foreach ($rawFilters as $filterId => $values) {
                if (is_array($values) && $values) {
                    $filters[(int) $filterId] = array_values(array_filter(array_map('strval', $values)));
                }
            }
        }
    }

    $postedVariants = [];
    $labels = input('variant_label', []);
    if (is_array($labels)) {
        $ids = input('variant_id', []);
        $skus = input('variant_sku', []);
        $stocks = input('variant_stock', []);
        $prices = input('variant_price', []);
        foreach ($labels as $index => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $postedVariants[] = [
                'id' => (int) ($ids[$index] ?? 0),
                'label' => $label,
                'sku' => trim((string) ($skus[$index] ?? '')) ?: null,
                'stock_total' => max(0, (int) ($stocks[$index] ?? 0)),
                'price_per_day' => trim((string) ($prices[$index] ?? '')) ?: null,
            ];
        }
    }

    $itemId = $id;
    if ($canEditProduct) {
        $ownerStoreId = $existingItem
            ? ($existingItem['owner_store_id'] !== null ? (int) $existingItem['owner_store_id'] : null)
            : ($scopeStoreId !== null ? $storeId : null);
        $itemVariants = array_map(static fn (array $variant): array => [
            'id' => $variant['id'] ?? 0,
            'label' => $variant['label'],
            'sku' => $variant['sku'],
            'stock_total' => $variant['stock_total'],
            'price_per_day' => $variant['price_per_day'],
        ], $postedVariants);
        $result = Item::save($id ?: null, [
            'category_id' => $categoryId,
            'owner_store_id' => $ownerStoreId,
            'name' => $name,
            'slug' => trim((string) input('slug', '')) ?: (string) ($existingItem['slug'] ?? ''),
            'brand' => trim((string) input('brand', '')),
            'short_description' => trim((string) input('short_description', '')),
            'description' => trim((string) input('description', '')),
            'price_per_day' => $price,
            'deposit' => (float) input('deposit', 0),
            'replacement_value' => input('replacement_value', ''),
            'stock_total' => input_int('stock_total'),
            'min_duration' => input_int('min_duration', 1),
            'max_duration' => input_int('max_duration', 30),
            'capacity_person' => input('capacity_person', ''),
            'weight_gram' => input('weight_gram', ''),
            'specs' => (string) input('specs', ''),
            'features' => (string) input('features', ''),
            'primary_image' => $primaryImage,
            'is_featured' => 0,
            'is_active' => 1,
        ], $filters, $itemVariants);
        if (!$result['success']) {
            json_error($result['message']);
        }
        $itemId = (int) $result['id'];
    }

    $storeVariants = [];
    if ($itemId > 0) {
        $masterVariants = Item::variants($itemId);
        $masterById = [];
        $masterByLabel = [];
        foreach ($masterVariants as $masterVariant) {
            $masterById[(int) $masterVariant['id']] = true;
            $masterByLabel[mb_strtolower((string) $masterVariant['label'])] = (int) $masterVariant['id'];
        }
        foreach ($postedVariants as $variant) {
            $variantId = (int) ($variant['id'] ?? 0);
            if ($variantId <= 0 || !isset($masterById[$variantId])) {
                $variantId = $masterByLabel[mb_strtolower((string) $variant['label'])] ?? 0;
            }
            if ($variantId > 0) {
                $storeVariants[$variantId] = [
                    'stock_total' => $variant['stock_total'],
                    'price_per_day' => $variant['price_per_day'],
                    'is_active' => 1,
                ];
            }
        }
    }

    $inventoryResult = StoreItem::save($storeId, $itemId, [
        'price_per_day' => $price,
        'deposit' => (float) input('deposit', 0),
        'replacement_value' => input('replacement_value', ''),
        'stock_total' => input_int('stock_total'),
        'min_duration' => input_int('min_duration', 1),
        'max_duration' => input_int('max_duration', 30),
        'is_active' => input_bool('is_active'),
        'is_featured' => input_bool('is_featured'),
        'variants' => $storeVariants,
    ], (int) $admin['id']);
    if (!$inventoryResult['success']) {
        json_error($inventoryResult['message']);
    }

    log_audit($id ? 'item.update' : 'item.create', 'item', $itemId, [
        'name' => $name,
        'store_id' => $storeId,
        'store_item_id' => (int) $inventoryResult['id'],
    ]);
    json_success([
        'message' => $inventoryResult['message'],
        'id' => $itemId,
        'store_item_id' => (int) $inventoryResult['id'],
        'reload' => true,
    ]);
}

if ($action === 'item_delete') {
    admin_require_admin();
    $id = input_int('id');
    $item = Item::find($id);
    if (!$item) {
        json_error('Item tidak ditemukan.', 404);
    }
    $scopeStoreId = admin_store_id();
    $storeId = $scopeStoreId !== null ? $scopeStoreId : input_int('store_id');
    if ($storeId > 0) {
        $storeItem = StoreItem::findForStore($storeId, $id);
        if (!$storeItem) {
            json_error('Inventaris cabang tidak ditemukan.', 404);
        }
        $result = StoreItem::delete((int) $storeItem['id']);
        if (!$result['success']) {
            json_error($result['message']);
        }
        $isBranchOwned = $item['owner_store_id'] !== null && (int) $item['owner_store_id'] === $storeId;
        if ($isBranchOwned) {
            $remaining = (int) Database::conn()->query(
                'SELECT COUNT(*) FROM store_items WHERE item_id = ' . $id
            )->fetchColumn();
            if ($remaining === 0) {
                Item::delete($id);
            }
        }
        log_audit('store_item.delete', 'store_item', (int) $storeItem['id'], ['item_id' => $id, 'store_id' => $storeId]);
        json_success(['message' => $result['message'], 'reload' => true]);
    }

    admin_require_super();
    $result = Item::delete($id);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('item.delete', 'item', $id, ['name' => $item['name']]);
    json_success(['message' => $result['message'], 'reload' => true]);
}

if ($action === 'category_save') {
    admin_require_super();
    $id = input_int('id');
    $existingCategory = $id ? Category::find($id) : null;
    $name = trim((string) input('name', ''));
    if (mb_strlen($name) < 2) {
        json_error('Nama kategori minimal 2 karakter.', 422, ['name' => 'Nama terlalu pendek.']);
    }
    $categoryId = Category::save($id ?: null, [
        'name' => $name,
        'slug' => trim((string) input('slug', '')) ?: (string) ($existingCategory['slug'] ?? ''),
        'description' => trim((string) input('description', '')),
        'icon' => trim((string) input('icon', 'package')),
        'accent_color' => trim((string) input('accent_color', '#CBE8CE')),
        'sort_order' => input_int('sort_order'),
        'is_active' => input_bool('is_active'),
    ]);
    log_audit($id ? 'category.update' : 'category.create', 'category', $categoryId, ['name' => $name]);
    json_success(['message' => $id ? 'Kategori diperbarui.' : 'Kategori ditambahkan.', 'id' => $categoryId, 'reload' => true]);
}

if ($action === 'category_delete') {
    admin_require_super();
    $id = input_int('id');
    $result = Category::delete($id);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('category.delete', 'category', $id);
    json_success(['message' => $result['message'], 'reload' => true]);
}

if ($action === 'booking_status') {
    $bookingId = input_int('booking_id');
    $status = (string) input('status', '');
    $note = trim((string) input('note', ''));
    $targetBooking = Booking::find($bookingId);
    if (!$targetBooking) {
        json_error('Pesanan tidak ditemukan.', 404);
    }
    $scope = admin_store_id();
    if ($scope !== null && (int) ($targetBooking['store_id'] ?? 0) !== $scope) {
        json_error('Pesanan ini bukan milik cabang Anda.', 403);
    }
    $result = Booking::updateStatus($bookingId, $status, $note ?: null, (int) $admin['id']);
    if (!$result['success']) {
        json_error($result['message']);
    }
    json_success([
        'message' => $result['message'],
        'status' => $status,
        'status_label' => status_label($status),
        'status_class' => status_class($status),
        'reload' => true,
    ]);
}

if ($action === 'payment_verify') {
    $paymentId = input_int('payment_id');
    $approve = input_bool('approve');
    $note = trim((string) input('note', ''));
    $targetPayment = Payment::find($paymentId);
    if (!$targetPayment) {
        json_error('Pembayaran tidak ditemukan.', 404);
    }
    $scope = admin_store_id();
    if ($scope !== null && (int) ($targetPayment['store_id'] ?? 0) !== $scope) {
        json_error('Pembayaran ini bukan milik cabang Anda.', 403);
    }
    $result = Payment::verify($paymentId, (int) $admin['id'], $approve, $note);
    if (!$result['success']) {
        json_error($result['message']);
    }
    json_success(['message' => $result['message'], 'reload' => true]);
}

if ($action === 'user_toggle') {
    admin_require_super();
    $id = input_int('id');
    if ($id === (int) $admin['id']) {
        json_error('Anda tidak dapat menonaktifkan akun sendiri.', 422);
    }
    $result = User::toggleActive($id);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('user.toggle', 'user', $id, ['is_active' => $result['is_active']]);
    json_success(['message' => $result['message'], 'is_active' => (int) $result['is_active']]);
}

if ($action === 'user_create') {
    admin_require_super();
    $name = trim((string) input('name', ''));
    $email = strtolower(trim((string) input('email', '')));
    $phone = trim((string) input('phone', ''));
    $role = (string) input('role', 'customer');
    if (!in_array($role, ['superadmin', 'admin', 'customer'], true)) {
        $role = 'customer';
    }
    $storeId = input_int('store_id');
    if ($role === 'admin' && ($storeId <= 0 || !Store::find($storeId))) {
        json_error('Pilih toko untuk admin cabang.');
    }
    $password = (string) input('password', '');
    if (mb_strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        json_error('Lengkapi nama, email valid, dan kata sandi minimal 8 karakter.');
    }
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
        json_error('Nomor WhatsApp tidak valid.');
    }
    $stmt = Database::conn()->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) OR phone = ? LIMIT 1');
    $stmt->execute([$email, $phone]);
    if ($stmt->fetchColumn()) {
        json_error('Email atau nomor WhatsApp sudah terdaftar.');
    }
    $id = User::create(['name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $password, 'role' => $role, 'store_id' => $role === 'admin' ? $storeId : null]);
    log_audit('user.create', 'user', $id, ['role' => $role, 'store_id' => $role === 'admin' ? $storeId : null]);
    json_success(['message' => 'Pengguna berhasil ditambahkan.', 'id' => $id, 'reload' => true]);
}

if ($action === 'user_reset_password') {
    admin_require_super();
    $id = input_int('id');
    $newPassword = (string) input('password', '');
    if (strlen($newPassword) < 8) {
        json_error('Kata sandi minimal 8 karakter.');
    }
    $target = User::find($id);
    if (!$target) {
        json_error('Pengguna tidak ditemukan.', 404);
    }
    Database::conn()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    log_audit('user.reset_password', 'user', $id);
    json_success(['message' => 'Kata sandi ' . $target['name'] . ' berhasil direset.']);
}

if ($action === 'settings_save') {
    admin_require_super();
    $raw = input('settings', []);
    if (!is_array($raw)) {
        json_error('Data pengaturan tidak valid.');
    }
    $allowed = [];
    foreach (settings_all() as $row) {
        $allowed[$row['setting_key']] = true;
    }
    $values = [];
    foreach ($raw as $key => $value) {
        if (!isset($allowed[$key]) || is_array($value)) {
            continue;
        }
        $values[$key] = trim((string) $value);
    }
    if (!empty($_FILES['qris_image']['name'])) {
        try {
            $path = upload_image($_FILES['qris_image'], 'settings', 3145728);
            if (!empty($values['qris_image'])) {
                delete_upload($values['qris_image']);
            }
            $values['qris_image'] = $path;
        } catch (Throwable $exception) {
            json_error($exception->getMessage());
        }
    }
    if (!array_key_exists('app_name', $values) || $values['app_name'] === '') {
        $values['app_name'] = APP_NAME;
    }
    Setting::updateMany($values);
    log_audit('settings.update', 'settings', null, ['keys' => array_keys($values)]);
    json_success(['message' => 'Pengaturan berhasil disimpan.', 'reload' => true]);
}

if ($action === 'pickup_save') {
    admin_require_super();
    $id = input_int('id');
    $name = trim((string) input('name', ''));
    $address = trim((string) input('address', ''));
    if ($name === '' || $address === '') {
        json_error('Nama dan alamat titik ambil wajib diisi.');
    }
    Setting::savePickupPoint($id ?: null, [
        'store_id' => input_int('store_id') ?: null,
        'name' => $name,
        'address' => $address,
        'hours' => trim((string) input('hours', '')),
        'maps_url' => trim((string) input('maps_url', '')),
        'latitude' => trim((string) input('latitude', '')),
        'longitude' => trim((string) input('longitude', '')),
        'is_active' => input_bool('is_active'),
        'sort_order' => input_int('sort_order'),
    ]);
    log_audit($id ? 'pickup.update' : 'pickup.create', 'pickup_point', $id ?: null, ['name' => $name]);
    json_success(['message' => $id ? 'Titik ambil diperbarui.' : 'Titik ambil ditambahkan.', 'reload' => true]);
}

if ($action === 'pickup_delete') {
    admin_require_super();
    $id = input_int('id');
    Database::conn()->prepare('DELETE FROM pickup_points WHERE id = ?')->execute([$id]);
    log_audit('pickup.delete', 'pickup_point', $id);
    json_success(['message' => 'Titik ambil dihapus.', 'reload' => true]);
}

if ($action === 'review_toggle') {
    admin_require_super();
    $id = input_int('id');
    $stmt = Database::conn()->prepare('SELECT * FROM reviews WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $review = $stmt->fetch();
    if (!$review) {
        json_error('Review tidak ditemukan.', 404);
    }
    $publish = (int) $review['is_published'] === 1 ? 0 : 1;
    Database::conn()->prepare('UPDATE reviews SET is_published = ? WHERE id = ?')->execute([$publish, $id]);
    Review::refreshItemRating((int) $review['item_id']);
    log_audit('review.toggle', 'review', $id, ['published' => $publish]);
    json_success(['message' => $publish ? 'Review ditampilkan.' : 'Review disembunyikan.', 'published' => $publish]);
}

json_error('Aksi admin tidak dikenali.', 400);
