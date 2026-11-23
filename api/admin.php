<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once ROOT_PATH . '/includes/icons.php';

$admin = api_require_admin();

/**
 * Form item lengkap (dipakai modal admin via AJAX).
 */
function admin_item_form_html(?array $item, array $categories): string
{
    $isEdit = $item !== null;
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
    $variants = $isEdit ? ($item['variants'] ?? []) : [];

    ob_start();
    ?>
    <form data-admin-form="item" enctype="multipart/form-data" class="space-y-5" data-item-form>
        <input type="hidden" name="action" value="item_save">
        <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
        <input type="hidden" name="primary_image_existing" value="<?= e($item['primary_image'] ?? '') ?>">

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="field-label" for="item-name">Nama gear <span class="text-danger">*</span></label>
                <input id="item-name" class="input" name="name" required maxlength="180" value="<?= e($item['name'] ?? '') ?>" placeholder="Contoh: Tenda Dome Alpenaz 4P">
            </div>
            <div>
                <label class="field-label" for="item-category">Kategori</label>
                <select id="item-category" class="select input" name="category_id" required data-item-category>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === $selectedCategory ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label" for="item-brand">Brand</label>
                <input id="item-brand" class="input" name="brand" maxlength="100" value="<?= e($item['brand'] ?? '') ?>" placeholder="Eiger, Consina, Naturehike...">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="item-short">Deskripsi singkat</label>
                <input id="item-short" class="input" name="short_description" maxlength="255" value="<?= e($item['short_description'] ?? '') ?>" placeholder="Satu kalimat yang menjual gear ini.">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="item-desc">Deskripsi lengkap</label>
                <textarea id="item-desc" class="input min-h-[7rem]" name="description" rows="4" placeholder="Ceritakan keunggulan, bahan, dan kondisi gear."><?= e($item['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="field-label" for="item-price">Harga sewa / hari <span class="text-danger">*</span></label>
                <input id="item-price" class="input" type="number" min="0" step="500" name="price_per_day" required value="<?= e($item['price_per_day'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="item-deposit">Deposit</label>
                <input id="item-deposit" class="input" type="number" min="0" step="500" name="deposit" value="<?= e($item['deposit'] ?? 0) ?>">
            </div>
            <div>
                <label class="field-label" for="item-replacement">Nilai penggantian</label>
                <input id="item-replacement" class="input" type="number" min="0" step="1000" name="replacement_value" value="<?= e($item['replacement_value'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="item-stock">Total stok</label>
                <input id="item-stock" class="input" type="number" min="0" name="stock_total" value="<?= e($item['stock_total'] ?? 1) ?>">
            </div>
            <div>
                <label class="field-label" for="item-min">Min. durasi (hari)</label>
                <input id="item-min" class="input" type="number" min="1" name="min_duration" value="<?= e($item['min_duration'] ?? 1) ?>">
            </div>
            <div>
                <label class="field-label" for="item-max">Maks. durasi (hari)</label>
                <input id="item-max" class="input" type="number" min="1" name="max_duration" value="<?= e($item['max_duration'] ?? 30) ?>">
            </div>
            <div>
                <label class="field-label" for="item-capacity">Kapasitas (orang)</label>
                <input id="item-capacity" class="input" type="number" min="0" name="capacity_person" value="<?= e($item['capacity_person'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="item-weight">Berat (gram)</label>
                <input id="item-weight" class="input" type="number" min="0" name="weight_gram" value="<?= e($item['weight_gram'] ?? '') ?>">
            </div>
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
                <textarea id="item-specs" class="input min-h-[6rem] font-mono text-xs" name="specs" rows="4" placeholder="Kapasitas: 4 orang&#10;Berat: 3.4 kg&#10;Bahan: Polyester 210T"><?= e($specs) ?></textarea>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-2xl bg-cream-100 px-4 py-3">
            <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded border-line" <?= !empty($item['is_featured']) ? 'checked' : '' ?>> Pilihan Rentify</label>
            <label class="flex items-center gap-2 text-sm font-bold text-forest-800"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-line" <?= !$isEdit || !empty($item['is_active']) ? 'checked' : '' ?>> Aktif di katalog</label>
        </div>

        <section>
            <div class="flex items-center justify-between">
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
                            <p class="helper">Kategori ini belum punya filter. Atur di halaman Kategori.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-forest-900">Varian / ukuran</h3>
                    <p class="helper mt-0.5">Opsional. Tambahkan jika stok dibedakan per ukuran.</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" data-add-variant><?= icon('plus', 'h-3.5 w-3.5') ?> Varian</button>
            </div>
            <div class="mt-3 space-y-2" data-variant-list>
                <?php if (!$variants): ?>
                    <div class="grid gap-2 rounded-2xl border border-dashed border-line p-3 sm:grid-cols-[1.4fr_.8fr_.8fr_.8fr_auto]" data-variant-row>
                        <input class="input !py-2 text-xs" name="variant_label[]" placeholder="Label (mis. 4P)">
                        <input class="input !py-2 text-xs" name="variant_sku[]" placeholder="SKU">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_stock[]" placeholder="Stok" value="0">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_price[]" placeholder="Harga/hari">
                        <button type="button" class="icon-btn h-9 w-9 self-center text-danger" data-remove-variant aria-label="Hapus varian"><?= icon('trash', 'h-4 w-4') ?></button>
                    </div>
                <?php endif; ?>
                <?php foreach ($variants as $variant): ?>
                    <div class="grid gap-2 rounded-2xl border border-line p-3 sm:grid-cols-[1.4fr_.8fr_.8fr_.8fr_auto]" data-variant-row>
                        <input class="input !py-2 text-xs" name="variant_label[]" value="<?= e($variant['label']) ?>" placeholder="Label">
                        <input class="input !py-2 text-xs" name="variant_sku[]" value="<?= e($variant['sku'] ?? '') ?>" placeholder="SKU">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_stock[]" value="<?= (int) $variant['stock_total'] ?>" placeholder="Stok">
                        <input class="input !py-2 text-xs" type="number" min="0" name="variant_price[]" value="<?= e($variant['price_per_day'] ?? '') ?>" placeholder="Harga/hari">
                        <button type="button" class="icon-btn h-9 w-9 self-center text-danger" data-remove-variant aria-label="Hapus varian"><?= icon('trash', 'h-4 w-4') ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary"><?= icon('check', 'h-4 w-4') ?> Simpan gear</button>
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
    if ($action === 'item_form') {
        $id = input_int('id');
        $item = $id ? Item::find($id) : null;
        if ($id && !$item) {
            json_error('Item tidak ditemukan.', 404);
        }
        $html = admin_item_form_html($item, Category::all());
        json_success([
            'title' => $item ? 'Edit ' . $item['name'] : 'Tambah gear baru',
            'html' => $html,
            'id' => $id,
        ]);
    }
    if ($action === 'category_form') {
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

if ($action === 'item_save') {
    $id = input_int('id');
    $existingItem = $id ? Item::find($id) : null;
    $name = trim((string) input('name', ''));
    $categoryId = input_int('category_id');
    $price = (float) input('price_per_day', 0);
    if (mb_strlen($name) < 3) {
        json_error('Nama gear minimal 3 karakter.', 422, ['name' => 'Nama terlalu pendek.']);
    }
    if (!$categoryId || !Category::find($categoryId)) {
        json_error('Kategori tidak valid.', 422, ['category_id' => 'Pilih kategori.']);
    }
    if ($price <= 0) {
        json_error('Harga sewa harus lebih dari 0.', 422, ['price_per_day' => 'Harga tidak valid.']);
    }

    $primaryImage = trim((string) input('primary_image_existing', ''));
    if (!empty($_FILES['image']['name'])) {
        try {
            $uploaded = upload_image($_FILES['image'], 'items', 5242880);
            $primaryImage = $uploaded;
        } catch (Throwable $exception) {
            json_error($exception->getMessage(), 422, ['image' => $exception->getMessage()]);
        }
    }

    $filters = [];
    $rawFilters = input('filters', []);
    if (is_array($rawFilters)) {
        foreach ($rawFilters as $filterId => $values) {
            if (is_array($values) && $values) {
                $filters[(int) $filterId] = array_values(array_filter(array_map('strval', $values)));
            }
        }
    }

    $variants = [];
    $labels = input('variant_label', []);
    if (is_array($labels)) {
        $skus = input('variant_sku', []);
        $stocks = input('variant_stock', []);
        $prices = input('variant_price', []);
        foreach ($labels as $index => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $variants[] = [
                'label' => $label,
                'sku' => trim((string) ($skus[$index] ?? '')) ?: null,
                'stock_total' => max(0, (int) ($stocks[$index] ?? 0)),
                'price_per_day' => trim((string) ($prices[$index] ?? '')) ?: null,
            ];
        }
    }

    $result = Item::save($id ?: null, [
        'category_id' => $categoryId,
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
        'is_featured' => input_bool('is_featured'),
        'is_active' => input_bool('is_active'),
    ], $filters, $variants);

    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit($id ? 'item.update' : 'item.create', 'item', (int) $result['id'], ['name' => $name]);
    json_success([
        'message' => $result['message'],
        'id' => (int) $result['id'],
        'reload' => true,
        'redirect' => url('admin/barang.php'),
    ]);
}

if ($action === 'item_delete') {
    $id = input_int('id');
    $item = Item::find($id);
    if (!$item) {
        json_error('Item tidak ditemukan.', 404);
    }
    $result = Item::delete($id);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('item.delete', 'item', $id, ['name' => $item['name']]);
    json_success(['message' => $result['message'], 'reload' => true]);
}

if ($action === 'category_save') {
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
    $result = Payment::verify($paymentId, (int) $admin['id'], $approve, $note);
    if (!$result['success']) {
        json_error($result['message']);
    }
    json_success(['message' => $result['message'], 'reload' => true]);
}

if ($action === 'user_toggle') {
    $id = input_int('id');
    $result = User::toggleActive($id);
    if (!$result['success']) {
        json_error($result['message']);
    }
    log_audit('user.toggle', 'user', $id, ['is_active' => $result['is_active']]);
    json_success(['message' => $result['message'], 'is_active' => (int) $result['is_active']]);
}

if ($action === 'user_create') {
    $name = trim((string) input('name', ''));
    $email = strtolower(trim((string) input('email', '')));
    $phone = trim((string) input('phone', ''));
    $role = (string) input('role', 'customer') === 'admin' ? 'admin' : 'customer';
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
    $id = User::create(['name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $password, 'role' => $role]);
    log_audit('user.create', 'user', $id, ['role' => $role]);
    json_success(['message' => 'Pengguna berhasil ditambahkan.', 'id' => $id, 'reload' => true]);
}

if ($action === 'user_reset_password') {
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
    $id = input_int('id');
    $name = trim((string) input('name', ''));
    $address = trim((string) input('address', ''));
    if ($name === '' || $address === '') {
        json_error('Nama dan alamat titik ambil wajib diisi.');
    }
    Setting::savePickupPoint($id ?: null, [
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
    $id = input_int('id');
    Database::conn()->prepare('DELETE FROM pickup_points WHERE id = ?')->execute([$id]);
    log_audit('pickup.delete', 'pickup_point', $id);
    json_success(['message' => 'Titik ambil dihapus.', 'reload' => true]);
}

if ($action === 'review_toggle') {
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
