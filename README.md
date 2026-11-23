<div align="center">

<img src="assets/img/logo-mark.svg" width="78" height="78" alt="Rentify Outdoor Karawang">

# Rentify Outdoor Karawang

**Gear Up, Go Further.**

Platform rental perlengkapan outdoor untuk pendaki dan camper di Karawang.
Dipandu **AI Gear Planner**, dipesan tanpa reload, dibayar via transfer atau QRIS.

<p>
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white">
  <img alt="Tailwind CSS" src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white">
  <img alt="Three.js" src="https://img.shields.io/badge/Three.js-3D-000000?style=flat-square&logo=threedotjs&logoColor=white">
  <img alt="JavaScript" src="https://img.shields.io/badge/Vanilla_JS-AJAX-F7DF1E?style=flat-square&logo=javascript&logoColor=black">
  <img alt="AI" src="https://img.shields.io/badge/AI-9router-53CF6C?style=flat-square&logo=openai&logoColor=white">
</p>

</div>

![Landing page Rentify dengan animasi gunung 3D](docs/screenshots/landing.jpg)

> **Rentify** adalah aplikasi rental outdoor lengkap: landing page 3D, katalog gear dengan stok real-time, AI Planner, keranjang dan checkout AJAX, pembayaran manual, pelacakan pesanan, sampai panel admin dengan laporan dan grafik. Dibangun **tanpa framework** — PHP native, PDO, dan JavaScript modular.

---

## Kenapa Rentify?

| | |
|---|---|
| 🤖 **AI yang benar-benar berguna** | AI menyusun daftar gear dari aktivitas, destinasi, durasi, dan jumlah peserta — bukan chatbot. Harga dan stok tetap divalidasi server. |
| 🏕️ **Katalog yang terasa hidup** | Filter dinamis, pencarian instan, stok per tanggal, varian, galeri, ulasan, dan estimasi biaya tanpa reload. |
| 🧾 **Alur sewa yang jelas** | Keranjang → checkout → pembayaran → verifikasi → pengambilan → pengembalian → review. Semua status terlihat. |
| 📊 **Admin yang siap kerja** | Dashboard metrik, CRUD gear, verifikasi pembayaran, pengembalian, laporan, export CSV, dan monitoring AI. |

---

## Sorotan Fitur

### 🤖 AI Gear Planner

Isi rencana perjalanan, AI memilih gear yang paling relevan dari katalog Rentify.
Setiap item bisa di-include/exclude, diubah jumlahnya, lalu dimasukkan ke keranjang sekaligus.

![AI Gear Planner dengan hasil rekomendasi](docs/screenshots/ai-planner.jpg)

- Pilihan aktivitas: **Tektok, Camping, Hiking, Ekspedisi**.
- Destinasi Karawang & sekitarnya, tanggal mulai, durasi inklusif, dan jumlah peserta.
- Output berisi **prioritas wajib/opsional**, alasan pemilihan, tips, dan estimasi biaya.
- **Grounded**: AI hanya boleh memilih ID dari kandidat yang dikirim server.
- **Fallback** berbasis aturan kategori jika 9router sedang offline.
- API key AI hanya ada di server (`config/ai.local.php`), tidak pernah dikirim ke browser.

### 🏕️ Beranda & katalog personal

![Beranda customer Rentify](docs/screenshots/beranda.jpg)

- Sapaan personal, member ID, pesanan mendatang, dan pintasan AI.
- Kategori dan **Top trends** langsung di beranda.
- Katalog dengan filter kategori dinamis, urutan, grid/list, dan pagination AJAX.
- Wishlist dan notifikasi tersedia di seluruh halaman.

### 🔍 Detail gear yang meyakinkan

![Detail gear Tenda Dome Alpenaz 4P](docs/screenshots/detail-gear.jpg)

- Galeri foto, brand, rating, jumlah unit, dan label **Pilihan Rentify**.
- Varian/ukuran, spesifikasi, fitur, dan ulasan pelanggan.
- Cek ketersediaan **real-time** per rentang tanggal.
- Estimasi biaya, deposit, dan durasi otomatis.
- Tombol **Masukkan keranjang** dan **Sewa langsung**.

### 🧾 Checkout tanpa drama

![Halaman checkout Rentify](docs/screenshots/checkout.jpg)

- Ambil di basecamp atau antar ke lokasi (area Karawang).
- Ringkasan subtotal, deposit, biaya antar, dan total transparan.
- Tanggal sewa fleksibel dengan perhitungan **2D1N = 2 hari**.
- Stok ditahan sementara agar tidak bentrok dengan penyewa lain.
- Pembayaran transfer bank atau QRIS, lalu unggah bukti.

### 📦 Pesanan yang transparan

![Daftar pesanan customer](docs/screenshots/pesanan.jpg)

- Timeline status dari `pending` sampai `completed`.
- Status pembayaran, batas waktu bayar, dan riwayat verifikasi.
- Kode pengambilan, pembatalan, dan tombol chat admin.
- Review dan rating setelah gear dikembalikan.

### 📊 Panel admin serba bisa

![Dashboard admin Rentify](docs/screenshots/admin-dashboard.jpg)

- Metrik pendapatan, pesanan, pelanggan, dan utilisasi stok.
- Grafik tren 8 bulan dan distribusi status pesanan.
- Daftar pesanan terbaru dan pembayaran yang menunggu verifikasi.

<div align="center">
<table>
  <tr>
    <td width="50%">
      <img src="docs/screenshots/admin-pesanan.jpg" alt="Manajemen pesanan admin">
      <p align="center"><sub><b>Manajemen pesanan</b> — filter, detail, verifikasi pembayaran, ubah status.</sub></p>
    </td>
    <td width="50%">
      <img src="docs/screenshots/admin-laporan.jpg" alt="Laporan bisnis admin">
      <p align="center"><sub><b>Laporan bisnis</b> — filter tanggal, grafik, gear terlaris, export CSV.</sub></p>
    </td>
  </tr>
</table>
</div>

Admin juga mengelola **gear & varian, kategori, pelanggan, pengembalian, pengguna, pengaturan toko, dan monitoring AI Planner** — semuanya dengan audit log.

---

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.5 native, PDO, arsitektur model/controller sederhana |
| Database | MySQL 8 / MariaDB 10.4+, 25 tabel, prepared statements |
| Frontend | Tailwind CSS v4 (build lokal), JavaScript modular vanilla, Fetch API |
| 3D | Three.js (vendor lokal) untuk landing page |
| Grafik | Chart.js (vendor lokal) untuk dashboard & laporan |
| AI | 9router OpenAI-compatible API, model `ag/gemini-3.8-flash-medium` |
| Font | Plus Jakarta Sans (self-hosted) |
| Upload | Validasi MIME + ukuran, gambar bukti bayar & avatar |

---

## Arsitektur

```mermaid
flowchart LR
    A["Browser<br/>Tailwind + Vanilla JS"] -->|HTML & AJAX| B["PHP Native 8.5"]
    B --> C["Models / PDO"]
    C --> D[("MySQL 8<br/>rentify")]
    B --> E["api/*.php<br/>JSON"]
    E --> C
    E -->|server-side proxy| F["9router<br/>Gemini"]
    E -->|fallback rules| C
```

- `api/_bootstrap.php` menangani JSON, sesi, dan CSRF untuk semua endpoint non-GET.
- `models/` menyimpan logika bisnis: katalog, keranjang, booking, pembayaran, AI.
- `includes/` menyediakan layout, navigasi, helper, dan renderer komponen.
- `admin/` adalah panel terpisah dengan guard role dan sidebar konsisten.

---

## Mulai Cepat

### 1. Clone & build aset

```bash
git clone https://github.com/anurr23/rentify.git
cd rentify
npm install
npm run build
```

`npm run build` mengompilasi Tailwind dan menyalin Three.js/Chart.js ke `assets/vendor/`.

### 2. Siapkan database

```bash
mysql -u root -p -e "CREATE DATABASE rentify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p rentify < database/rentify.sql
mysql -u root -p rentify < database/seed.sql
```

Atau buka `install.php` sekali untuk membuat database dan mengimpor data demo otomatis.

### 3. Konfigurasi

`config/database.local.php` (tidak di-commit):

```php
<?php
return [
    'host' => '127.0.0.1',
    'docker_host' => 'mysql-server',
    'port' => '3306',
    'name' => 'rentify',
    'username' => 'root',
    'password' => '',
];
```

`config/ai.local.php` (tidak di-commit):

```php
<?php
return [
    'api_key' => 'sk-...',
    'base_url' => 'http://localhost:20128/v1',
    'docker_base_url' => 'http://host.docker.internal:20128/v1',
    'model' => 'ag/gemini-3.8-flash-medium',
];
```

Bisa juga lewat environment variable: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `RENTIFY_AI_KEY`, `RENTIFY_AI_BASE_URL`, `RENTIFY_AI_MODEL`.

### 4. Jalankan

```bash
php -S localhost:8000 router.php
```

Buka **http://localhost:8000**.

Atau lewat Apache/Docker:

```bash
docker start mysql-server php8.5-apache
```

Buka **http://localhost/rentify/**.

---

## Akun Demo

| Role | Email | Password |
|---|---|---|
| Admin / Owner | `admin@rentify.id` | `password` |
| Customer | `galih@petualang.id` | `password` |
| Customer | `william@petualang.id` | `password` |

Seed berisi **12 kategori, 34 gear, 5 pesanan, pembayaran, review, aktivitas, destinasi, pickup point,** dan pengaturan awal.

---

## Struktur Database

<details>
<summary><b>25 tabel</b> — klik untuk melihat</summary>

| Kelompok | Tabel |
|---|---|
| Pengguna | `users`, `remember_tokens`, `password_resets`, `notifications` |
| Katalog | `categories`, `category_filters`, `items`, `item_images`, `item_variants`, `item_filter_values` |
| Perjalanan | `activities`, `destinations`, `activity_gear_rules`, `pickup_points` |
| Transaksi | `bookings`, `booking_items`, `booking_status_logs`, `payments` |
| Interaksi | `reviews`, `wishlists` |
| AI | `ai_sessions`, `ai_session_items`, `ai_cache` |
| Sistem | `settings`, `audit_logs` |

</details>

**Durasi sewa dihitung inklusif:** `end_date - start_date + 1`.
Mulai 24 Nov, kembali 25 Nov = **2 hari (2D1N)**.

**Stok tersedia** = stok total/varian − pesanan aktif yang tanggalnya overlap − hold yang belum kedaluwarsa.

---

## API

Semua respons berupa JSON. Request non-GET wajib menyertakan header `X-CSRF-Token` (otomatis oleh `assets/js/app.js`).

<details>
<summary><b>Customer endpoints</b></summary>

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/items.php` | Filter & pencarian katalog |
| GET | `/api/availability.php` | Cek stok per tanggal |
| GET/POST | `/api/cart.php` | State, add, add_many, update, remove, clear, dates |
| GET/POST | `/api/wishlist.php` | List / toggle wishlist |
| POST | `/api/auth.php` | Login, register, logout |
| GET/POST | `/api/ai_recommend.php` | Meta AI / buat rekomendasi |
| GET/POST | `/api/booking.php` | Detail, quote, create, cancel |
| GET/POST | `/api/payment.php` | Info pembayaran / unggah bukti |
| POST | `/api/review.php` | Kirim review |
| POST | `/api/profile.php` | Update profil / ganti sandi |
| GET/POST | `/api/notifications.php` | List / read / read all |

</details>

<details>
<summary><b>Admin endpoints</b></summary>

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/admin.php?action=item_form&id=` | Form gear |
| POST | `/api/admin.php?action=item_save` | Simpan gear & varian |
| POST | `/api/admin.php?action=item_delete` | Hapus gear |
| POST | `/api/admin.php?action=booking_status` | Ubah status pesanan |
| POST | `/api/admin.php?action=payment_verify` | Verifikasi / tolak pembayaran |
| POST | `/api/admin.php?action=category_save` | Simpan kategori |
| POST | `/api/admin.php?action=user_save` | Simpan pengguna |
| POST | `/api/admin.php?action=settings_save` | Simpan pengaturan |
| GET | `/admin/laporan.php?export=csv` | Export laporan CSV |

</details>

---

## Keamanan

- Password di-hash dengan `password_hash()` / `password_verify()`.
- Semua query memakai **prepared statements** PDO.
- CSRF token untuk seluruh request non-GET.
- Validasi role di halaman dan endpoint admin.
- Upload divalidasi MIME, ekstensi, dan ukuran; nama file di-randomize.
- Folder sensitif (`config/`, `models/`, `includes/`, `database/`, `storage/`, `_legacy_pos/`) diblokir `.htaccess` dan router.
- Audit log untuk aksi admin dan customer.
- Cookie `HttpOnly`, `SameSite=Lax`, dan `Secure` otomatis saat HTTPS/tunnel.
- API key AI tidak pernah masuk ke HTML/JavaScript.

---

## Struktur Folder

<details>
<summary><b>Lihat struktur proyek</b></summary>

```text
rentify/
├── admin/                 Panel admin (10 halaman)
├── api/                   JSON API + _bootstrap.php
├── assets/
│   ├── css/               Tailwind source & hasil build
│   ├── fonts/             Plus Jakarta Sans
│   ├── img/               Logo, hero, placeholder
│   ├── js/                app, catalog, detail, ai, cart, booking, admin
│   └── vendor/            three.module.js, chart.umd.js
├── config/                Bootstrap, database, AI
├── database/              Schema + seed
├── docs/screenshots/      Screenshot README
├── includes/              Layout, nav, helper, auth, renderer
├── models/                Model PDO native
├── storage/logs/          Log aplikasi
├── uploads/               Foto gear, kategori, bukti bayar, avatar
├── index.php              Landing Three.js
├── beranda.php            Beranda customer
├── katalog.php            Katalog
├── detail.php             Detail gear
├── rekomendasi.php        AI Planner
├── keranjang.php          Keranjang
├── checkout.php           Checkout
├── pesanan.php            Daftar pesanan
├── pesanan_detail.php     Detail & pembayaran
├── profil.php             Profil customer
├── wishlist.php           Wishlist
├── install.php            Installer & health check
├── router.php             Router PHP built-in server
└── run.ps1                Script server Windows
```

</details>

---

## Pengujian

Yang sudah diuji pada mesin demo:

- Import schema + seed dari database kosong.
- Installer end-to-end via database sementara.
- Smoke test seluruh halaman publik, customer, admin, dan API.
- E2E: login → katalog → keranjang → checkout → booking → upload bukti → verifikasi admin → ubah status → review.
- AI sukses via 9router dan fallback saat AI offline.
- CRUD admin: gear, kategori, pengguna, pengaturan, pengembalian.
- Registrasi, profil, ganti kata sandi, wishlist, notifikasi.
- Export CSV dan tampilan responsif 390 px / 1440 px.

Health check cepat:

```text
http://localhost:8000/install.php
```

---

## Troubleshooting

<details>
<summary><b>CSS tidak muncul / tampilan polos</b></summary>

- Jalankan `npm run build:css`.
- Hard refresh (**Ctrl + Shift + R**).
- Jika lewat tunnel HTTPS, pastikan aplikasi membaca `X-Forwarded-Proto: https`. Versi terbaru sudah mendukung Cloudflare Tunnel, ngrok, dan localtunnel.
- Cek `tailwind.css?v=...` di DevTools → Network harus **200 text/css**.

</details>

<details>
<summary><b>AI Planner bilang "AI sedang tidak bisa dihubungi"</b></summary>

- Pastikan 9router berjalan di `http://localhost:20128`.
- Di Docker, gunakan `http://host.docker.internal:20128/v1`.
- Cek `storage/logs/app.log`.
- Jika AI mati, aplikasi otomatis memakai rekomendasi berbasis aturan.

</details>

<details>
<summary><b>Database belum siap</b></summary>

- Pastikan container `mysql-server` atau MySQL lokal berjalan.
- Cek kredensial di `config/database.local.php` atau environment variable.
- Buka `install.php` sekali untuk membuat database dan mengimpor seed.

</details>

<details>
<summary><b>Upload gagal</b></summary>

- Pastikan folder `uploads/` writable.
- Format JPG, PNG, atau WebP; maksimal 5 MB (avatar 3 MB).
- Cek `file_uploads`, `upload_max_filesize`, dan `post_max_size`.

</details>

---

<div align="center">

**Rentify Outdoor Karawang** — dibuat dengan PHP native, sedikit AJAX, dan banyak kopi di basecamp.

<sub>Basecamp Karawang, Jawa Barat · 2026</sub>

</div>
