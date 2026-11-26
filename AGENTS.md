# AGENTS.md

Panduan untuk AI coding agent (Codex, Claude Code, Cursor, dll.) yang bekerja di repositori **Rentify Outdoor Karawang**.

---

## 1. Gambaran Proyek

Rentify adalah platform rental perlengkapan outdoor di Karawang.

- **Backend**: PHP 8.5 native, tanpa framework, PDO/MySQL.
- **Frontend**: Tailwind CSS v4 (build lokal), JavaScript modular vanilla, AJAX/Fetch.
- **Landing page**: Three.js (vendor lokal).
- **Admin charts**: Chart.js (vendor lokal).
- **AI Gear Planner**: server-side proxy ke 9router (OpenAI-compatible), model `ag/gemini-3.8-flash-medium`.
- **Form bertema**: Flatpickr untuk tanggal, Tom Select untuk select pencarian (vendor lokal, tema di `assets/css/inputs.css`).
- **Tiga role**: `superadmin`, `admin` (admin cabang), dan `customer`.
- **Multi-cabang**: tabel `stores`, `users.store_id`, `bookings.store_id`; peta Leaflet + OpenStreetMap.
- **Inventaris per cabang**: `store_items` + `store_item_variants` menyimpan harga, deposit, stok, durasi, dan status unggulan tiap cabang; `items.owner_store_id` menandai produk milik cabang.
- **Lokasi customer**: GPS disimpan untuk rekomendasi cabang terdekat (Haversine).

Struktur mengikuti pola sederhana **page → model → PDO**:

```text
Request → *.php (page/controller) → models/*.php → PDO → MySQL
API     → api/*.php → api/_bootstrap.php → models → JSON
```

Jangan menambahkan framework, ORM, atau bundler baru. Semua halaman adalah PHP native.

### Hierarki akses

| Role | Akses |
|---|---|
| `superadmin` | Semua cabang, master toko, katalog, pengguna, pengaturan, AI, semua pesanan |
| `admin` | Hanya cabang `store_id` miliknya: pesanan, pelanggan, pengembalian, laporan |
| `customer` | Katalog, AI, keranjang, pesanan sendiri, lokasi & toko utama |

`admin_store_id()` mengembalikan `null` untuk superadmin (semua cabang) dan integer untuk admin cabang. Semua query admin wajib memakai scope ini.

---

## 2. Perintah Penting

### Build aset

```bash
npm install
npm run build          # vendor (Three, Chart, Leaflet, Flatpickr, Tom Select) + Tailwind minified
npm run build:css      # hanya Tailwind
npm run watch:css      # watch Tailwind
```

`npm run build` wajib dijalankan setelah mengubah class Tailwind, `assets/css/app.css`, atau `tailwind.config` bila ada.

### Menjalankan aplikasi

```bash
php -S localhost:8000 router.php
```

atau PowerShell:

```powershell
.\run.ps1
```

Docker yang dipakai di mesin demo:

```bash
docker start mysql-server php8.5-apache
```

- Aplikasi: `http://localhost/rentify/`
- Health check: `http://localhost/rentify/install.php`
- MySQL dari host: `127.0.0.1:3306`, user `root`, password `root123`
- MySQL dari container PHP: host `mysql-server`

### Database

```bash
mysql -u root -p -e "CREATE DATABASE rentify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p rentify < database/rentify.sql
mysql -u root -p rentify < database/seed.sql
```

Atau buka `install.php` sekali. Installer membuat schema, seed, lalu menjalankan migrasi multi-cabang.

Untuk database lama yang sudah berisi data:

```bash
php database/migrate.php
```

Migrasi idempoten: menambah tabel `stores`, `store_items`, `store_item_variants`, role `superadmin`, kolom `store_id`/lokasi/`owner_store_id`/`store_item_id`, 7 cabang, admin cabang, inventaris tiap cabang, dan memetakan booking lama.

### Lint PHP (wajib sebelum selesai)

PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | Where-Object { $_.FullName -notmatch 'node_modules|_legacy_pos' } | ForEach-Object { php -l $_.FullName }
```

Semua file harus lolos tanpa syntax error.

### Smoke test manual

Buka dan pastikan tidak ada `Warning`, `Deprecated`, atau `Fatal error`:

```text
/
/beranda.php
/katalog.php?store=rentify-karawang-kota
/detail.php?slug=tenda-dome-alpenaz-4p&store=rentify-karawang-kota
/toko.php?store=rentify-karawang-kota
/rekomendasi.php
/keranjang.php
/checkout.php
/pesanan.php
/profil.php
/wishlist.php
/login.php
/admin/dashboard.php
/admin/barang.php
/admin/pesanan.php
/admin/laporan.php
/admin/pengaturan.php
/admin/toko_saya.php
/admin/perbandingan.php
```

Uji juga endpoint yang berubah, termasuk request non-GET dengan CSRF.

---

## 3. Peta Folder

| Path | Isi |
|---|---|
| `admin/` | 15 halaman panel admin, termasuk `toko.php` (superadmin), `toko_saya.php` (admin cabang), dan `perbandingan.php` (superadmin) |
| `api/` | Endpoint JSON + `_bootstrap.php` + `location.php` |
| `assets/css/app.css` | Sumber Tailwind + komponen kustom |
| `assets/css/tailwind.css` | Hasil build, **di-commit** |
| `assets/js/` | `app.js`, `catalog.js`, `detail.js`, `ai.js`, `cart.js`, `booking.js`, `profile.js`, `auth.js`, `admin.js`, `inputs.js`, `admin-tables.js`, `landing-3d.js`, `location.js`, `map-stores.js`, `admin-stores.js` |
| `assets/vendor/` | Three.js, Chart.js, Leaflet (JS/CSS/marker), Flatpickr, Tom Select, DataTables hasil salinan |
| `config/` | Bootstrap, database, AI, env |
| `database/` | `rentify.sql`, `seed.sql`, `migrate.php` |
| `includes/` | Layout, nav, helper, renderer, auth |
| `models/` | Logika bisnis & query PDO, termasuk `Store.php` dan `StoreItem.php` |
| `storage/logs/` | Log aplikasi (runtime) |
| `uploads/` | Foto gear, kategori, toko, bukti bayar, avatar |
| `docs/screenshots/` | Screenshot untuk README |
| `screenshoot/` | Referensi desain awal (tidak di-commit) |

---

## 4. Konvensi Kode

### PHP

- `declare(strict_types=1);` di file baru.
- Gunakan `e()` untuk escape HTML: `<?= e($value) ?>`.
- Gunakan `url()`, `asset()`, `redirect()`, `input()`, `input_int()`, `flash()` dari `includes/helpers.php`.
- Query **wajib** prepared statement. Jangan interpolasi input user ke SQL.
- Gunakan model yang sudah ada (`Item`, `Booking`, `Payment`, `User`, `Store`, dll.) sebelum membuat query baru di halaman.
- Transaksi database untuk operasi multi-tabel (booking, pembayaran, stok, simpan toko + pickup point).
- Admin cabang **wajib** memakai `admin_store_id()` pada query pesanan/pelanggan/pengembalian/laporan.
- Superadmin-only memakai `require_superadmin()` (halaman) / `admin_require_super()` (API); admin umum memakai `admin_require_admin()`.
- Inventaris cabang **wajib** lewat `StoreItem` (`find`, `forItem`, `variants`, `availableQty`, `save`, `syncVariants`) agar stok, varian, dan harga konsisten.
- `store_items` adalah sumber utama harga/stok/katalog; `items.price_per_day`/`stock_total` hanya nilai dasar. Jangan mengubah produk global saat admin cabang hanya boleh mengubah inventarisnya.
- Jangan menampilkan error mentah ke user. Log ke `storage/logs/app.log` dan tampilkan pesan ramah.
- Format uang memakai helper yang ada; jangan hardcode `Rp`.
- Tanggal disimpan `Y-m-d`; durasi sewa **inklusif** (`end - start + 1`).

### API

- Semua endpoint baru harus melalui `api/_bootstrap.php`.
- `_bootstrap.php` sudah menangani JSON, sesi, dan `verify_csrf()` untuk non-GET. Jangan memanggil `verify_csrf()` ulang.
- Selalu balas `{ "success": bool, "message": string, ... }`.
- Gunakan `input()` / `input_int()`; jangan baca `$_POST` mentah.
- Jangan keluarkan output sebelum `json_encode`; buffer sudah diatur bootstrap.

### JavaScript

- Gunakan `window.RENTIFY` (`api.request`, `api.toast`, `api.formatRupiah`, `api.baseUrl`, `api.loading`).
- Jangan menaruh URL absolut; gunakan `api.baseUrl('...')` atau `api.apiUrl('...')`.
- Event delegation untuk elemen dinamis.
- Tidak ada framework, tidak ada build JS. File dimuat lewat `footer.php` (`defer`) atau `type="module"` untuk landing.
- `location.js` menangani GPS, `data-use-location`, `data-location-target`, dan event `rentify:location`.
- `map-stores.js` hanya untuk `toko.php`; `admin-stores.js` hanya untuk modal toko.
- Muat Leaflet CSS lewat `$pageStyles = ['vendor/leaflet/leaflet.css']` dan JS lewat `$pageVendorScripts = ['leaflet/leaflet.js']`.
- `inputs.js` menginisialisasi Flatpickr pada `input[type=date]` dan Tom Select pada `select[data-searchable]`; panggil `window.RENTIFY_INPUTS.refresh(root)` setelah menyuntik HTML dinamis (modal sudah otomatis lewat event `admin:modal-open`).
- Tabel admin memakai DataTables server-side: `<table data-datatable="dataset" data-order='[[0,"desc"]]'>` + `assets/js/admin-tables.js` + dataset di `api/admin_table.php`. Jangan render ribuan baris di PHP untuk halaman list.
- `catalog.js` melakukan pencarian live (debounce 280 ms), filter/sortir, dan “muat lebih banyak” tanpa reload. **Jangan** menambahkan `scrollIntoView` otomatis setelah update katalog; posisi scroll user harus dipertahankan.

### Tailwind & desain

- Palet resmi (jangan diganti):
  `#032517`, `#0C1D14`, `#1B3B2B`, `#486255`, `#CBE8CE`, `#B2D8C8`, `#F8FAF6`, `#F5F7F4`, `#F3F4F0`, `#0E2117`, `#E5A440`, `#53CF6C`, `#D9534F`.
- Font: Plus Jakarta Sans (self-hosted).
- Mobile-first. Uji minimal lebar 390 px dan 1440 px.
- Gunakan komponen yang ada: `btn btn-primary`, `card`, `input`, `select`, `section-kicker`, `field-label`, `helper`, `chip`, `stepper`.
- Setelah mengubah class, jalankan `npm run build:css` (atau `npm run build`).

---

## 5. Aturan Keamanan

- Jangan pernah commit `.env`, log, atau upload user. Konfigurasi database/AI dibaca dari `.env` + environment variable oleh `config/env.php`; jangan mengembalikan ke file kredensial lokal.
- Jangan menaruh API key di JavaScript, HTML, README, atau screenshot.
- Jangan melemahkan CSRF, guard role, atau validasi upload.
- Master data global (buat/hapus toko, kategori, pengguna, pengaturan, AI, perbandingan toko) hanya **superadmin**.
- `barang` dapat diakses admin cabang, tetapi hanya untuk inventaris cabangnya; produk milik cabang lain harus ditolak dan produk global tidak boleh diubah selain nilai inventarisnya.
- Admin cabang tidak boleh mengubah pesanan/pembayaran di luar `store_id`-nya.
- Jangan menampilkan data pelanggan cabang lain ke admin cabang.
- Header `Permissions-Policy` harus tetap `geolocation=(self)` agar GPS berfungsi; jangan dikembalikan ke `()`.
- Folder `config/`, `models/`, `includes/`, `database/`, `storage/`, `_legacy_pos/`, `node_modules/`, `scripts/` diblokir `.htaccess` dan `router.php`. Jangan menghapus aturan tersebut.
- Upload hanya JPG/PNG/WebP, maksimal 5 MB (avatar 3 MB), nama file di-randomize.
- Jangan menambah dependency tanpa alasan kuat. Prioritaskan stdlib PHP dan aset lokal.

---

## 6. Alur Kerja yang Disarankan

1. Baca file terkait dan cek pola yang sudah ada.
2. Buat perubahan sekecil mungkin; jangan refactor di luar scope.
3. Jika mengubah schema, update **ketiganya**: `database/rentify.sql`, `database/seed.sql`, dan `database/migrate.php`.
4. Jika mengubah UI, jalankan `npm run build:css`; jika vendor berubah, `npm run build`.
5. Lint PHP.
6. Uji halaman/endpoint yang berubah, termasuk happy path dan error path.
7. Uji **superadmin** dan **admin cabang** untuk fitur yang menyentuh data cabang.
8. Perbarui `README.md` bila perilaku, setup, atau API berubah.
9. Jangan membersihkan atau menghapus data demo tanpa diminta.

---

## 7. Jebakan Umum

- **CSS tidak berubah**: lupa `npm run build:css`, atau cache browser. Asset memakai `?v=filemtime`.
- **URL salah di subfolder**: `BASE_URL` dideteksi otomatis. Jangan hardcode `/rentify/`.
- **Tunnel HTTPS**: `config/config.php` membaca `X-Forwarded-*` dan `CF-Visitor`. Jangan memaksa `http://`.
- **AI error dari container**: gunakan `host.docker.internal:20128`, bukan `localhost:20128`.
- **Opcache container**: `opcache.validate_timestamps=Off`; restart `php8.5-apache` setelah mengubah config PHP bila perubahan tidak terlihat.
- **Keranjang kosong setelah reload**: keranjang disimpan di sesi PHP; pastikan cookie sesi tidak hilang.
- **Stok bentrok**: selalu cek ulang stok di server saat create booking, jangan hanya mengandalkan UI.
- **Durasi salah**: 2D1N = 2 hari, bukan 1. Gunakan `Booking::durationDays()`/helper yang ada.
- **Admin cabang melihat semua data**: lupa menerapkan `admin_store_id()` pada query.
- **Store_id di booking kosong**: checkout harus mengirim `store_id`, dan `Booking::createFromCart()` memvalidasi cabang aktif + pickup point milik cabang.
- **Peta Leaflet tidak muncul**: cek `$pageStyles`/`$pageVendorScripts`, modal diinisialisasi lewat event `admin:modal-open`, dan panggil `map.invalidateSize()` setelah modal tampil.
- **Leaflet di halaman detail admin tidak jalan**: `admin/pesanan.php` memakai `require admin_footer; exit;` lebih awal, jadi script init **harus diletakkan sebelum baris itu**. Leaflet baru dimuat di footer, jadi init wajib retry (`if (!window.L) setTimeout(boot, 150)`), bukan langsung dipanggil.
- **Peta tinggi 2px / kosong**: `assets/css/tailwind.css` sudah ter-compile, kelas baru seperti `h-56` tidak otomatis ada. Pakai kelas yang sudah tersedia (`h-48`) atau jalankan `npm run build:css`.
- **Reverse geocoding Nominatim**: debounce minimal 1 detik (rate-limit 1 req/detik) dan jangan menimpa alamat yang sudah diketik user — simpan nilai hasil autofill lalu bandingkan sebelum mengganti.
- **Watermark peta**: pemilik proyek minta peta bersih, jadi kedua peta memakai `attributionControl: false` dan `tileLayer` tanpa opsi `attribution`. Jangan menambahkan atribusi Leaflet/OSM kembali.
- **Titik antar tidak tersimpan**: kolom `bookings.delivery_lat`/`delivery_lng` nullable; validasi (pasangan lengkap, numerik, dalam rentang, bukan 0,0) ada di `Booking::createFromCart()`, bukan hanya di JS. Hidden input checkout memakai `data-delivery-lat`/`data-delivery-lng`.
- **GPS tidak bekerja**: header `Permissions-Policy` harus `geolocation=(self)`, dan permintaan lokasi harus lewat HTTPS atau localhost.
- **Pickup point dobel**: gunakan `Store::syncPickupPoint()`; jangan membuat pickup point manual untuk cabang yang sama.
- **Stok cabang salah sumber**: katalog/keranjang/booking harus memakai `store_items`, bukan `items.stock_total`.
- **Admin cabang mengubah produk global**: cek `items.owner_store_id`; hanya pemilik atau superadmin yang boleh mengubah data produk.
- **Keranjang lintas cabang**: satu keranjang = satu cabang; konflik harus mengembalikan 409 + data `current_store`/`new_store`.
- **Tom Select/Flatpickr tidak muncul di modal**: form dinamis harus memicu `admin:modal-open` atau memanggil `RENTIFY_INPUTS.refresh`.
- **Tabel admin kosong**: pastikan `data-datatable` cocok dengan dataset di `api/admin_table.php`, jumlah `<th>` sama dengan jumlah `cells`, dan aset DataTables dimuat lewat `$pageStyles`/`$pageVendorScripts`/`$pageScripts`.
- **Aksi tabel tidak jalan setelah paging**: handler admin harus memakai delegasi (`data-admin-delete`, `data-admin-status`, `data-user-toggle`, `data-admin-modal`) dan memanggil `RENTIFY_TABLES.reload()` alih-alih reload halaman.

---

## 8. Definition of Done

- [ ] `php -l` bersih untuk semua file yang diubah.
- [ ] `npm run build` (atau `build:css`) berhasil.
- [ ] Halaman/endpoint terkait diuji dan tidak ada warning.
- [ ] CSRF, role guard, dan validasi tetap aktif.
- [ ] Superadmin dan admin cabang diuji; data lintas cabang tidak bocor.
- [ ] Migrasi database lama diuji bila schema berubah.
- [ ] Leaflet/GPS diuji (map render, marker, geolocation fallback).
- [ ] Tidak ada secret atau file runtime yang ikut ter-commit.
- [ ] README/AGENTS.md diperbarui bila perlu.

---

## 9. Akun Demo

| Role | Cabang | Email | Password |
|---|---|---|---|
| Superadmin | Semua | `admin@rentify.id` | `password` |
| Admin Cabang | Karawang Kota | `asep@rentify.id` | `password` |
| Admin Cabang | Karawang Selatan | `dewi@rentify.id` | `password` |
| Admin Cabang | Cikampek | `rizky@rentify.id` | `password` |
| Admin Cabang | Bandung | `dimas@rentify.id` | `password` |
| Admin Cabang | Jakarta | `nadia@rentify.id` | `password` |
| Admin Cabang | Bogor | `bayu@rentify.id` | `password` |
| Admin Cabang | Surabaya | `sinta@rentify.id` | `password` |
| Customer | — | `galih@petualang.id` | `password` |
| Customer | — | `william@petualang.id` | `password` |
