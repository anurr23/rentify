-- Rentify demo seed data
SET NAMES utf8mb4;
SET time_zone = '+07:00';

INSERT IGNORE INTO stores (id, code, name, slug, address, district, city, province, postal_code, phone, whatsapp, email, latitude, longitude, maps_url, opening_hours, description, image, delivery_fee, is_active, is_default, sort_order) VALUES
(1, 'RNT-KRW-01', 'Rentify Karawang Kota', 'rentify-karawang-kota', 'Jl. Galuh Mas Raya No. 8, Karawang Barat, Jawa Barat', 'Karawang Barat', 'Karawang', 'Jawa Barat', '41361', '081200000011', '6281200000011', 'karawang@rentify.id', -6.3010000, 107.3050000, 'https://maps.google.com/?q=-6.301,107.305', 'Setiap hari, 08.00-21.00 WIB', 'Basecamp utama Rentify di jantung Karawang. Stok gear terlengkap, area cek gear, dan parkir luas.', 'uploads/stores/karawang-kota.jpg', 15000, 1, 1, 1),
(2, 'RNT-KRW-02', 'Rentify Karawang Selatan', 'rentify-karawang-selatan', 'Jl. Raya Sanggabuana No. 21, Karawang Selatan, Jawa Barat', 'Karawang Selatan', 'Karawang', 'Jawa Barat', '41371', '081200000012', '6281200000012', 'sanggabuana@rentify.id', -6.3920000, 107.3010000, 'https://maps.google.com/?q=-6.392,107.301', 'Setiap hari, 07.00-20.00 WIB', 'Pos terdepan menuju Gunung Sanggabuana. Cocok untuk pendaki yang ingin berangkat pagi buta.', 'uploads/stores/karawang-selatan.jpg', 15000, 1, 0, 2),
(3, 'RNT-KRW-03', 'Rentify Cikampek', 'rentify-cikampek', 'Jl. Raya Cikampek No. 45, Cikampek, Karawang, Jawa Barat', 'Cikampek', 'Karawang', 'Jawa Barat', '41373', '081200000013', '6281200000013', 'cikampek@rentify.id', -6.4020000, 107.4560000, 'https://maps.google.com/?q=-6.402,107.456', 'Setiap hari, 08.00-20.00 WIB', 'Cabang timur Karawang, dekat gerbang tol Cikampek. Praktis untuk trip ke Purwakarta dan Bandung.', 'uploads/stores/cikampek.jpg', 18000, 1, 0, 3),
(4, 'RNT-BDG-01', 'Rentify Bandung', 'rentify-bandung', 'Jl. Ir. H. Juanda No. 112, Dago, Bandung, Jawa Barat', 'Coblong', 'Bandung', 'Jawa Barat', '40135', '081200000014', '6281200000014', 'bandung@rentify.id', -6.9175000, 107.6191000, 'https://maps.google.com/?q=-6.9175,107.6191', 'Setiap hari, 08.00-21.00 WIB', 'Basecamp Bandung untuk akses cepat ke Gunung Puntang, Papandayan, dan Ranca Upas.', 'uploads/stores/bandung.jpg', 25000, 1, 0, 4),
(5, 'RNT-JKT-01', 'Rentify Jakarta', 'rentify-jakarta', 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan', 'Kebayoran Baru', 'Jakarta', 'DKI Jakarta', '12190', '081200000015', '6281200000015', 'jakarta@rentify.id', -6.2088000, 106.8456000, 'https://maps.google.com/?q=-6.2088,106.8456', 'Setiap hari, 08.00-21.00 WIB', 'Cabang Jakarta dengan layanan antar-jemput area Jabodetabek untuk trip akhir pekan.', 'uploads/stores/jakarta.jpg', 30000, 1, 0, 5),
(6, 'RNT-BGR-01', 'Rentify Bogor', 'rentify-bogor', 'Jl. Pajajaran No. 88, Bogor Tengah, Jawa Barat', 'Bogor Tengah', 'Bogor', 'Jawa Barat', '16143', '081200000016', '6281200000016', 'bogor@rentify.id', -6.5950000, 106.8166000, 'https://maps.google.com/?q=-6.595,106.8166', 'Setiap hari, 07.00-21.00 WIB', 'Gerbang menuju Gunung Salak, Gede, dan Pangrango. Gear dingin selalu siap.', 'uploads/stores/bogor.jpg', 25000, 1, 0, 6),
(7, 'RNT-SBY-01', 'Rentify Surabaya', 'rentify-surabaya', 'Jl. Basuki Rahmat No. 101, Surabaya, Jawa Timur', 'Genteng', 'Surabaya', 'Jawa Timur', '60271', '081200000017', '6281200000017', 'surabaya@rentify.id', -7.2575000, 112.7521000, 'https://maps.google.com/?q=-7.2575,112.7521', 'Setiap hari, 08.00-21.00 WIB', 'Cabang Jawa Timur untuk pendakian Semeru, Bromo, dan trip outdoor lainnya.', 'uploads/stores/surabaya.jpg', 35000, 1, 0, 7);

INSERT IGNORE INTO users (id, member_code, name, email, phone, password_hash, role, store_id, address, is_verified, is_active, created_at) VALUES
(1, 'RNT-ADMIN', 'Owner Rentify', 'admin@rentify.id', '081200000001', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'superadmin', NULL, 'Basecamp Rentify, Karawang', 1, 1, NOW()),
(2, 'RNT-2026-0001', 'Galih Pratama', 'galih@petualang.id', '081234567890', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'customer', NULL, 'Karawang Barat, Jawa Barat', 1, 1, NOW()),
(3, 'RNT-2026-0002', 'William Saputra', 'william@petualang.id', '081234567891', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'customer', NULL, 'Karawang Selatan, Jawa Barat', 0, 1, NOW()),
(4, 'RNT-STAFF-01', 'Asep Nugraha', 'asep@rentify.id', '081200000021', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 1, 'Karawang Barat, Jawa Barat', 1, 1, NOW()),
(5, 'RNT-STAFF-02', 'Dewi Lestari', 'dewi@rentify.id', '081200000022', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 2, 'Karawang Selatan, Jawa Barat', 1, 1, NOW()),
(6, 'RNT-STAFF-03', 'Rizky Pratama', 'rizky@rentify.id', '081200000023', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 3, 'Cikampek, Karawang', 1, 1, NOW()),
(7, 'RNT-STAFF-04', 'Dimas Anggara', 'dimas@rentify.id', '081200000024', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 4, 'Bandung, Jawa Barat', 1, 1, NOW()),
(8, 'RNT-STAFF-05', 'Nadia Putri', 'nadia@rentify.id', '081200000025', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 5, 'Jakarta Selatan', 1, 1, NOW()),
(9, 'RNT-STAFF-06', 'Bayu Firmansyah', 'bayu@rentify.id', '081200000026', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 6, 'Bogor Tengah, Jawa Barat', 1, 1, NOW()),
(10, 'RNT-STAFF-07', 'Sinta Maharani', 'sinta@rentify.id', '081200000027', '$2y$12$Cw9xMCCa3dJTeEWxXRY5N.m3Z19JFEeFUTpLmlnoZEMjt3KUn0AzO', 'admin', 7, 'Surabaya, Jawa Timur', 1, 1, NOW());

INSERT IGNORE INTO categories (id, name, slug, description, icon, hero_image, accent_color, sort_order, is_active) VALUES
(1, 'Tenda & Shelter', 'tenda-shelter', 'Dome, family, dan glamping tent untuk tidur nyaman di alam terbuka.', 'tent', 'uploads/categories/tent-shelter.jpg', '#CBE8CE', 1, 1),
(2, 'Carrier & Tas', 'carrier-tas', 'Carrier 45–75L, daypack, dan dry bag untuk membawa seluruh kebutuhan ekspedisi.', 'backpack', 'uploads/categories/carrier-tas.jpg', '#DDF2DF', 2, 1),
(3, 'Sepatu & Sandal', 'sepatu-sandal', 'Hiking boots, trail running, dan sandal outdoor dengan grip teruji.', 'boot', 'uploads/categories/sepatu-sandal.jpg', '#ECF6EC', 3, 1),
(4, 'Pakaian & Jaket Gunung', 'pakaian-jaket', 'Hardshell, down jacket, dan raincoat untuk cuaca pegunungan.', 'jacket', 'uploads/categories/apparel.jpg', '#CBE8CE', 4, 1),
(5, 'Sleeping System', 'sleeping-system', 'Sleeping bag, matras, dan bantal ultralight untuk istirahat maksimal.', 'sleep', 'uploads/categories/sleeping-system.jpg', '#DDF2DF', 5, 1),
(6, 'Lampu & Penerangan', 'lampu-penerangan', 'Headlamp, lantern, dan senter dengan daya tahan baterai panjang.', 'lamp', 'uploads/categories/lampu-penerangan.jpg', '#ECF6EC', 6, 1),
(7, 'Trekking & Navigasi', 'trekking-navigasi', 'Trekking pole, GPS handheld, dan kompas untuk rute yang aman.', 'compass', 'uploads/categories/trekking-navigasi.jpg', '#CBE8CE', 7, 1),
(8, 'Kompor & Cooking Set', 'kompor-cooking', 'Kompor ultralight, cookset, dan gas canister praktis.', 'stove', 'uploads/categories/kompor-cooking.jpg', '#DDF2DF', 8, 1),
(9, 'Hammock & Tarp', 'hammock-tarp', 'Hammock double, tarp, dan aksesoris camping minimalis.', 'hammock', 'uploads/categories/hammock-sanitasi.jpg', '#ECF6EC', 9, 1),
(10, 'Sanitasi & Kebersihan', 'sanitasi-kebersihan', 'Portable shower, toilet kit, dan botol minum untuk tetap bersih.', 'droplet', 'uploads/categories/sanitasi-kebersihan.jpg', '#CBE8CE', 10, 1),
(11, 'Power & Gadget', 'power-gadget', 'Powerbank, solar charger, dan kabel untuk gadget tetap hidup.', 'bolt', 'uploads/categories/power-gadget.jpg', '#DDF2DF', 11, 1),
(12, 'Paket Camping', 'paket-camping', 'Paket hemat siap sewa untuk tektok, camping 2P, dan keluarga 4P.', 'package', 'uploads/categories/paket-camping.jpg', '#ECF6EC', 12, 1);

INSERT IGNORE INTO category_filters (id, category_id, label, filter_key, type, options, sort_order) VALUES
(1, 1, 'Tipe Tenda', 'tipe', 'chips', '["Semua Tenda","Dome","Family","Glamping"]', 1),
(2, 1, 'Kapasitas', 'kapasitas', 'chips', '["2P","4P","6P"]', 2),
(3, 2, 'Tipe Carrier', 'tipe', 'chips', '["Daypack","Carrier 45-60L","Carrier 65L+"]', 1),
(4, 2, 'Kapasitas', 'kapasitas', 'chips', '["25L","60L","65L"]', 2),
(5, 3, 'Tipe Sepatu', 'tipe', 'chips', '["Hiking Boots","Trail Running","Sandal"]', 1),
(6, 3, 'Ukuran Sepatu (EU)', 'ukuran', 'size', '["38-39","40-41","42-43","44-45"]', 2),
(7, 4, 'Tipe Pakaian', 'tipe', 'chips', '["Hardshell","Down","Raincoat"]', 1),
(8, 4, 'Ukuran Pakaian', 'ukuran', 'size', '["S","M","L","XL","XXL"]', 2),
(9, 5, 'Tipe Sleep System', 'tipe', 'chips', '["Sleeping Bag","Matras","Aksesoris"]', 1),
(10, 5, 'Comfort Suhu', 'suhu', 'chips', '["Comfort 0-5°C","Comfort 5-10°C","Umum"]', 2),
(11, 6, 'Tipe Lampu', 'tipe', 'chips', '["Headlamp","Lantern","Senter"]', 1),
(12, 6, 'Lumen', 'lumen', 'chips', '["500-800 lm","1000-1500 lm","2000+ lm"]', 2),
(13, 7, 'Tipe Navigasi', 'tipe', 'chips', '["Trekking Pole","GPS","Kompas"]', 1),
(14, 8, 'Tipe Alat Masak', 'tipe', 'chips', '["Kompor","Cookset","Gas"]', 1),
(15, 8, 'Kapasitas Masak', 'kapasitas', 'chips', '["1-2P","3-4P"]', 2),
(16, 9, 'Tipe Hammock', 'tipe', 'chips', '["Hammock","Tarp","Aksesoris"]', 1),
(17, 10, 'Tipe Sanitasi', 'tipe', 'chips', '["Shower","Toilet Kit","Botol"]', 1),
(18, 11, 'Tipe Power', 'tipe', 'chips', '["Powerbank","Solar Charger","Kabel"]', 1),
(19, 12, 'Tipe Paket', 'tipe', 'chips', '["Paket Tektok","Paket 2P","Paket 4P"]', 1);

INSERT IGNORE INTO items (id, category_id, name, slug, brand, short_description, description, price_per_day, deposit, replacement_value, stock_total, min_duration, max_duration, capacity_person, weight_gram, specs, features, primary_image, rating_avg, rating_count, rented_count, is_featured, is_active, search_keywords) VALUES
(1, 1, 'Tenda Dome Alpenaz 4P', 'tenda-dome-alpenaz-4p', 'Alpenaz', 'Tenda dome double layer untuk 4 orang, kokoh menghadapi hujan dan angin.', 'Tenda dome Alpenaz 4P memiliki konstruksi double layer, frame aluminium, dan flysheet waterproof 3000mm. Nyaman untuk camping keluarga maupun ekspedisi pendek di Gunung Sanggabuana.', 65000, 50000, 850000, 8, 1, 14, 4, 3800, '{"Kapasitas":"4 orang","Berat":"3.8 kg","Dimensi":"210 x 210 x 135 cm","Material":"Polyester 190T","Waterproof":"3000 mm"}', '["4P","Double Layer","Waterproof 3000mm","Frame Aluminium"]', 'uploads/items/tent-dome.jpg', 5.00, 0, 64, 1, 1, 'tenda dome camping 4p alpenaz waterproof'),
(2, 1, 'Tenda Family Estate 6P', 'tenda-family-estate-6p', 'Naturehike', 'Tenda keluarga dengan ruang luas dan porch untuk bersantai.', 'Tenda family 6P dengan ruang utama luas dan area porch. Cocok untuk camping keluarga di Loji atau Puncak Sempur dengan kenyamanan ekstra.', 95000, 75000, 1450000, 5, 1, 14, 6, 6200, '{"Kapasitas":"6 orang","Berat":"6.2 kg","Dimensi":"300 x 260 x 180 cm","Material":"Polyester 210T","Waterproof":"4000 mm"}', '["6P","Ruang Luas","Porch","Waterproof 4000mm"]', 'uploads/items/tent-family.jpg', 4.80, 0, 32, 1, 1, 'tenda family 6p camping keluarga naturehike'),
(3, 1, 'Tenda Glamping Bell 4P', 'tenda-glamping-bell-4p', 'Rentify Select', 'Bell tent canvas estetik untuk glamping dengan pengalaman berbeda.', 'Bell tent 4P dengan material canvas tebal, cocok untuk glamping romantis atau camping komunitas. Termasuk groundsheet dan lampu hias.', 120000, 100000, 2200000, 3, 2, 14, 4, 12500, '{"Kapasitas":"4 orang","Berat":"12.5 kg","Dimensi":"300 x 300 x 200 cm","Material":"Cotton Canvas"}', '["4P","Glamping","Canvas","Lampu Hias"]', 'uploads/items/tent-glamping.jpg', 4.90, 0, 21, 0, 1, 'tenda glamping bell canvas estetik'),
(4, 2, 'Carrier Eiger 60L', 'carrier-eiger-60l', 'Eiger', 'Carrier 60L dengan back system nyaman untuk ekspedisi 3–5 hari.', 'Carrier Eiger 60L memiliki kompartemen besar, rain cover, dan back system yang nyaman. Pilihan favorit pendaki Gunung Sanggabuana.', 45000, 50000, 1100000, 10, 1, 21, NULL, 1800, '{"Kapasitas":"60 liter","Berat":"1.8 kg","Material":"Nylon 600D","Rain Cover":"Termasuk"}', '["60L","Rain Cover","Back System","Eiger Official"]', 'uploads/items/backpack-60l.jpg', 4.90, 0, 112, 1, 1, 'carrier eiger 60l backpack gunung'),
(5, 2, 'Daypack Consina 25L', 'daypack-consina-25l', 'Consina', 'Daypack ringkas 25L untuk tektok dan day hike.', 'Daypack Consina 25L dengan kompartemen hidrasi, mesh back panel, dan rain cover. Ringan untuk perjalanan sehari.', 25000, 25000, 450000, 15, 1, 7, NULL, 650, '{"Kapasitas":"25 liter","Berat":"0.65 kg","Material":"Nylon","Rain Cover":"Termasuk"}', '["25L","Rain Cover","Hydration Ready"]', 'uploads/items/backpack-daypack.jpg', 4.70, 0, 78, 0, 1, 'daypack consina 25l tektok'),
(6, 2, 'Carrier Deuter Aircontact 65L', 'carrier-deuter-aircontact-65l', 'Deuter', 'Carrier premium 65L dengan ventilasi punggung maksimal.', 'Carrier Deuter Aircontact 65L untuk ekspedisi panjang. Suspensi aktif, adjustable torso, dan rain cover membuat beban berat terasa lebih ringan.', 75000, 100000, 2350000, 4, 1, 21, NULL, 2400, '{"Kapasitas":"65 liter","Berat":"2.4 kg","Material":"Duratex","Rain Cover":"Termasuk"}', '["65L","Ventilasi","Adjustable Torso","Rain Cover"]', 'uploads/items/backpack-60l.jpg', 4.90, 0, 46, 0, 1, 'carrier deuter aircontact 65l ekspedisi'),
(7, 3, 'Salomon Quest 4D GTX', 'salomon-quest-4d-gtx', 'Salomon Official', 'Hiking boots mid-cut Gore-Tex dengan grip Vibram.', 'Salomon Quest 4D GTX memberikan dukungan ankle, perlindungan Gore-Tex, dan grip Vibram untuk medan Gunung Sanggabuana yang licin.', 35000, 50000, 2900000, 6, 1, 14, NULL, 1200, '{"Ukuran":"40-45 EU","Berat":"1.2 kg/pasang","Material":"Gore-Tex","Sole":"Vibram"}', '["Gore-Tex","Vibram Grip","Mid-Cut","Waterproof"]', 'uploads/items/boots-hiking.jpg', 5.00, 0, 142, 1, 1, 'sepatu hiking boots salomon quest gore tex'),
(8, 3, 'Trail Running Arei', 'trail-running-arei', 'Arei', 'Sepatu trail running ringan untuk tektok cepat.', 'Sepatu trail running Arei dengan outsole agresif dan upper breathable. Nyaman untuk jalur tanah, bebatuan, dan tanjakan pendek.', 25000, 30000, 750000, 8, 1, 7, NULL, 780, '{"Ukuran":"38-45 EU","Berat":"0.78 kg/pasang","Material":"Mesh","Sole":"Rubber Trail"}', '["Trail Grip","Breathable","Ringan"]', 'uploads/items/shoes-trail.jpg', 4.70, 0, 88, 0, 1, 'sepatu trail running arei tektok'),
(9, 3, 'Boots Rei Waterproof', 'boots-rei-waterproof', 'Rei', 'Boots kulit waterproof untuk trekking harian.', 'Boots Rei dengan upper kulit sintetis, membran waterproof, dan sol karet anti-slip. Pilihan ekonomis untuk pendaki pemula.', 30000, 30000, 850000, 6, 1, 14, NULL, 1050, '{"Ukuran":"40-45 EU","Berat":"1.05 kg/pasang","Material":"Synthetic Leather","Waterproof":"Ya"}', '["Waterproof","Anti-Slip","Mid-Cut"]', 'uploads/items/boots-hiking.jpg', 4.60, 0, 54, 0, 1, 'boots rei waterproof hiking'),
(10, 4, 'Summit Series Gore-Tex Pro Hardshell', 'summit-series-gore-tex-hardshell', 'Summit Series', 'Jaket hardshell 3-layer untuk cuaca ekstrem.', 'Hardshell 3-layer Gore-Tex Pro dengan storm hood, pit zips, dan seam-sealed. Melindungi dari hujan dan angin di ketinggian.', 40000, 50000, 1850000, 10, 1, 14, NULL, 520, '{"Ukuran":"S-XXL","Berat":"0.52 kg","Material":"Gore-Tex Pro 3-Layer","Waterproof":"20.000 mm"}', '["Gore-Tex Pro","Waterproof 20.000mm","Storm Hood","Windproof"]', 'uploads/items/jacket-hardshell.jpg', 4.90, 0, 74, 1, 1, 'jaket hardshell gore tex waterproof gunung'),
(11, 4, 'Down Jacket Ultralight 800FP', 'down-jacket-ultralight-800fp', 'Summit Series', 'Down jacket 800FP ringan dan hangat untuk suhu dingin.', 'Down jacket ultralight dengan fill power 800 dan stuff sack kecil. Menghangatkan tanpa menambah beban carrier.', 30000, 30000, 950000, 8, 1, 14, NULL, 320, '{"Ukuran":"S-XXL","Berat":"0.32 kg","Material":"Nylon Ripstop","Fill Power":"800FP"}', '["800FP","Ultralight","Stuff Sack","Windproof"]', 'uploads/items/jacket-down.jpg', 4.80, 0, 68, 0, 1, 'down jacket ultralight 800fp hangat'),
(12, 4, 'Raincoat Poncho Emergency', 'raincoat-poncho-emergency', 'Rentify Select', 'Poncho darurat ringan, selalu siap di tas.', 'Poncho waterproof ringan yang bisa dilipat kecil. Cocok untuk tektok dan antisipasi hujan di jalur.', 15000, 10000, 150000, 20, 1, 7, NULL, 180, '{"Ukuran":"All Size","Berat":"0.18 kg","Material":"EVA","Waterproof":"Ya"}', '["Ringan","Waterproof","Compact"]', 'uploads/items/jacket-hardshell.jpg', 4.50, 0, 122, 0, 1, 'raincoat poncho jas hujan outdoor'),
(13, 5, 'Sleeping Bag Ultralight -2°C', 'sleeping-bag-ultralight-2c', 'Naturehike', 'Sleeping bag mummy comfort 2°C, ringan dan hangat.', 'Sleeping bag mummy dengan filling hollow fiber, comfort 2°C, dan stuff sack. Pas untuk camping di Sanggabuana dan Puncak Sempur.', 32000, 30000, 780000, 10, 1, 14, NULL, 680, '{"Comfort":"2°C","Berat":"0.68 kg","Ukuran":"210 x 75 cm","Filling":"Hollow Fiber"}', '["Comfort 2°C","680 gram","Mummy","Stuff Sack"]', 'uploads/items/sleeping-bag.jpg', 4.90, 0, 164, 1, 1, 'sleeping bag ultralight 2c camping'),
(14, 5, 'Sleeping Pad Inflatable', 'sleeping-pad-inflatable', 'Naturehike', 'Matras inflatable tebal 5cm dengan pompa terintegrasi.', 'Sleeping pad inflatable 5cm dengan R-value 4.4 dan pompa bawaan. Tidur lebih nyaman di medan berbatu.', 18000, 20000, 420000, 12, 1, 14, NULL, 780, '{"Tebal":"5 cm","R-Value":"4.4","Berat":"0.78 kg","Pompa":"Terintegrasi"}', '["R-Value 4.4","Tebal 5cm","Pompa Terintegrasi"]', 'uploads/items/sleeping-mat.jpg', 4.70, 0, 96, 0, 1, 'matras sleeping pad inflatable camping'),
(15, 5, 'Matras Foil Aluminium', 'matras-foil-aluminium', 'Rentify Select', 'Matras foil ringan sebagai alas tambahan.', 'Matras foil aluminium yang ringan dan mudah dilipat. Melindungi dari dingin dan permukaan tanah.', 10000, 5000, 85000, 20, 1, 14, NULL, 250, '{"Dimensi":"180 x 60 cm","Berat":"0.25 kg","Material":"Foil Aluminium"}', '["Ringan","Compact","Alas Tambahan"]', 'uploads/items/sleeping-mat.jpg', 4.40, 0, 108, 0, 1, 'matras foil camping alas tenda'),
(16, 6, 'Nitecore NU33 Headlamp 1200 Lumens', 'nitecore-nu33-headlamp', 'Nitecore Official', 'Headlamp rechargeable 1200 lumens dengan red light.', 'Nitecore NU33 memiliki output 1200 lumens, baterai rechargeable, mode red light, dan tahan air IPX6. Termasuk powerbank darurat.', 22000, 25000, 750000, 15, 1, 14, NULL, 146, '{"Output":"1200 lumens","Baterai":"Rechargeable 2000mAh","Waterproof":"IPX6","Berat":"146 gram"}', '["Rechargeable Type-C","1200 Lumens","IPX6","Free Powerbank"]', 'uploads/items/headlamp.jpg', 5.00, 0, 210, 1, 1, 'headlamp nitecore nu33 1200 lumen'),
(17, 6, 'Blackdog LED Lantern 3000K', 'blackdog-led-lantern-3000k', 'Blackdog', 'Lantern hangat 3000K dengan baterai tahan 48 jam.', 'Lampu tenda Blackdog dengan cahaya hangat 3000K, dimmer, dan baterai 48 jam. Menciptakan suasana camping yang nyaman.', 15000, 15000, 350000, 10, 1, 14, NULL, 320, '{"Warna":"3000K","Baterai":"48 jam","Output":"400 lumens","Berat":"320 gram"}', '["3000K Glow","Baterai 48 Jam","Dimmer"]', 'uploads/items/lantern.jpg', 4.90, 0, 88, 0, 1, 'lantern lampu tenda camping 3000k'),
(18, 6, 'Senter Taktikal 2000 Lumens', 'senter-taktikal-2000-lumens', 'Rentify Select', 'Senter fokus 2000 lumens untuk navigasi malam.', 'Senter taktikal dengan beam fokus, mode strobe, dan body aluminium. Cocok untuk trekking malam dan kebutuhan darurat.', 12000, 10000, 280000, 12, 1, 14, NULL, 220, '{"Output":"2000 lumens","Baterai":"18650","Waterproof":"IPX5","Berat":"220 gram"}', '["2000 Lumens","Fokus Beam","Strobe"]', 'uploads/items/headlamp.jpg', 4.60, 0, 58, 0, 1, 'senter taktikal 2000 lumen camping'),
(19, 7, 'Trekking Pole Carbon Black Diamond', 'trekking-pole-carbon-black-diamond', 'Black Diamond Official', 'Sepasang trekking pole karbon ultralight dengan double flicklock.', 'Trekking pole karbon ringan dengan sistem lipat dan double FlickLock. Mengurangi beban lutut saat turun gunung.', 25000, 30000, 1450000, 8, 1, 14, NULL, 480, '{"Material":"Carbon","Berat":"480 gram/pasang","Panjang":"110-130 cm","Lock":"Double FlickLock"}', '["Carbon 100%","Double FlickLock","Free Mud Basket"]', 'uploads/items/trekking-poles.jpg', 4.90, 0, 132, 1, 1, 'trekking pole karbon black diamond'),
(20, 7, 'GPS Garmin eTrex 32x', 'gps-garmin-etrex-32x', 'Garmin', 'GPS handheld dengan peta topo dan kompas 3-axis.', 'Garmin eTrex 32x memiliki layar warna, peta topo, kompas 3-axis, dan barometer. Andal untuk ekspedisi tanpa sinyal.', 65000, 100000, 4200000, 4, 1, 14, NULL, 174, '{"Layar":"2.2 inci","Baterai":"25 jam","Waterproof":"IPX7","Berat":"174 gram"}', '["Peta Topo","Kompas 3-Axis","IPX7","Baterai 25 Jam"]', 'uploads/items/gps-device.jpg', 4.80, 0, 24, 0, 1, 'gps garmin etrex navigasi gunung'),
(21, 7, 'Kompas Bidik & Peta Topografi', 'kompas-bidik-peta-topografi', 'Rentify Select', 'Kompas bidik dan peta topo untuk navigasi dasar.', 'Paket kompas bidik dan peta topografi area Karawang–Jawa Barat. Wajib untuk pendaki yang ingin belajar navigasi darat.', 8000, 5000, 120000, 15, 1, 14, NULL, 90, '{"Jenis":"Kompas Bidik","Berat":"90 gram","Peta":"Topografi Karawang"}', '["Kompas Bidik","Peta Topo","Navigasi Dasar"]', 'uploads/items/gps-device.jpg', 4.40, 0, 42, 0, 1, 'kompas bidik peta topografi navigasi'),
(22, 8, 'Fire Maple Polaris & FMC-206 Series', 'fire-maple-polaris-fmc-206', 'Fire Maple Official', 'Paket kompor gas ultralight dengan pressure regulator anti-angin.', 'Fire Maple Polaris dengan regulator anti-angin dan cookset FMC-206. Beban ringan 380 gram, cocok untuk 3–4 orang.', 20000, 20000, 650000, 18, 1, 14, NULL, 380, '{"Beban":"380 gram","Output":"2600W","Bahan Bakar":"Gas Canister","Kapasitas":"3-4 orang"}', '["Beban 380g Ringan","Windproof Valve","Free Korek & Tas"]', 'uploads/items/stove-cookset.jpg', 4.90, 0, 176, 1, 1, 'kompor camping fire maple polaris cookset'),
(23, 8, 'Cookset Aluminium 4P', 'cookset-aluminium-4p', 'Rentify Select', 'Cookset aluminium 4 porsi dengan nesting design.', 'Cookset aluminium anodized berisi 2 panci, 4 mangkuk, dan sendok. Ringan dan mudah dibersihkan.', 18000, 15000, 320000, 10, 1, 14, NULL, 720, '{"Kapasitas":"4 porsi","Material":"Aluminium Anodized","Berat":"0.72 kg"}', '["4 Porsi","Nesting","Anti Lengket"]', 'uploads/items/cookware-set.jpg', 4.60, 0, 66, 0, 1, 'cookset aluminium camping 4 porsi'),
(24, 8, 'Gas Canister 230g', 'gas-canister-230g', 'Hi-Cook', 'Gas canister 230g untuk kompor camping.', 'Gas canister 230g dengan campuran butana-propana. Praktis untuk 2–3 hari pemakaian.', 12000, 5000, 45000, 25, 1, 14, NULL, 230, '{"Berat":"230 gram","Campuran":"Butana Propana","Pemakaian":"2-3 hari"}', '["230g","Butana Propana","Praktis"]', 'uploads/items/stove-cookset.jpg', 4.50, 0, 140, 0, 1, 'gas canister 230g kompor camping'),
(25, 9, 'Eno DoubleNest Hammock & Bug Net', 'eno-doublenest-hammock-bug-net', 'Eno', 'Hammock double dengan bug net dan tree-friendly strap.', 'Hammock double Eno dengan bug net, tali tree-friendly, dan kapasitas 180 kg. Nyaman untuk camping minimalis.', 18000, 20000, 680000, 12, 1, 14, NULL, 700, '{"Kapasitas":"180 kg","Ukuran":"2 orang","Material":"Parachute Nylon","Berat":"0.7 kg"}', '["Ultralight Ripstop","180kg Load","Tree-Friendly Straps"]', 'uploads/items/hammock.jpg', 4.90, 0, 98, 1, 1, 'hammock eno double nest camping'),
(26, 9, 'Tarp Shelter 3x3 Waterproof', 'tarp-shelter-3x3', 'Rentify Select', 'Tarp 3x3m untuk shelter darurat dan area memasak.', 'Tarp waterproof 3x3m dengan lubang eyelet dan guy line. Bisa dipasang sebagai flysheet tambahan atau area komunal.', 15000, 15000, 350000, 8, 1, 14, NULL, 620, '{"Ukuran":"3 x 3 meter","Material":"Polyester Ripstop","Waterproof":"3000 mm","Berat":"0.62 kg"}', '["3x3m","Waterproof","Guy Line"]', 'uploads/items/tarp.jpg', 4.70, 0, 44, 0, 1, 'tarp shelter 3x3 waterproof camping'),
(27, 10, 'Portable Shower 20L', 'portable-shower-20l', 'Rentify Select', 'Shower portable 20L dengan pompa USB.', 'Portable shower 20L dengan pompa rechargeable dan nozzle adjustable. Praktis untuk camping keluarga.', 20000, 20000, 420000, 5, 1, 14, NULL, 950, '{"Kapasitas":"20 liter","Pompa":"USB Rechargeable","Berat":"0.95 kg"}', '["20L","USB Pump","Adjustable Nozzle"]', 'uploads/items/sanitizer-kit.jpg', 4.60, 0, 18, 0, 1, 'portable shower camping 20l'),
(28, 10, 'Toilet Kit Camping', 'toilet-kit-camping', 'Rentify Select', 'Paket sanitasi pribadi: sabun, tisu, hand sanitizer, dan handuk.', 'Toilet kit berisi kebutuhan kebersihan pribadi selama camping. Praktis dan higienis.', 8000, 5000, 95000, 15, 1, 14, NULL, 350, '{"Isi":"Sabun, tisu, sanitizer, handuk","Berat":"0.35 kg"}', '["Hygiene Kit","Travel Size","Higienis"]', 'uploads/items/sanitizer-kit.jpg', 4.50, 0, 36, 0, 1, 'toilet kit sanitasi camping'),
(29, 10, 'Water Bottle 1L Insulated', 'water-bottle-1l-insulated', 'Rentify Select', 'Botol minum insulated 1L, tahan dingin 24 jam.', 'Botol stainless steel 1L dengan double wall vacuum. Menjaga suhu minuman tetap dingin hingga 24 jam.', 7000, 5000, 180000, 25, 1, 14, NULL, 420, '{"Kapasitas":"1 liter","Material":"Stainless Steel","Suhu":"24 jam dingin"}', '["1L","Insulated","BPA Free"]', 'uploads/items/water-bottle.jpg', 4.80, 0, 154, 0, 1, 'botol minum water bottle 1l insulated'),
(30, 11, 'Powerbank 20000mAh PD', 'powerbank-20000mah-pd', 'Rentify Select', 'Powerbank 20000mAh fast charging untuk gadget outdoor.', 'Powerbank 20000mAh dengan USB-C PD 22.5W, bisa mengisi headlamp, HP, dan kamera beberapa kali.', 15000, 15000, 320000, 10, 1, 14, NULL, 390, '{"Kapasitas":"20000 mAh","Output":"22.5W PD","Berat":"0.39 kg"}', '["20000mAh","Fast Charging","USB-C PD"]', 'uploads/items/powerbank.jpg', 4.70, 0, 74, 0, 1, 'powerbank 20000mah camping outdoor'),
(31, 11, 'Solar Charger Panel 21W', 'solar-charger-panel-21w', 'Rentify Select', 'Solar panel lipat 21W untuk pengisian darurat.', 'Solar charger 21W dengan output USB dan carabiner. Cocok untuk ekspedisi tanpa akses listrik.', 20000, 20000, 480000, 5, 1, 14, NULL, 620, '{"Output":"21W","Port":"USB-A & USB-C","Berat":"0.62 kg"}', '["21W","Foldable","USB-C"]', 'uploads/items/powerbank.jpg', 4.50, 0, 22, 0, 1, 'solar charger panel 21w outdoor'),
(32, 12, 'Paket Camping 2P Lengkap', 'paket-camping-2p-lengkap', 'Rentify Select', 'Paket camping 2 orang: tenda, sleeping bag, matras, headlamp, kompor.', 'Paket camping lengkap untuk 2 orang selama 2 hari. Hemat dan siap langsung dipakai.', 150000, 100000, 2500000, 5, 1, 7, 2, 6800, '{"Isi":"Tenda 2P, 2 sleeping bag, 2 matras, headlamp, kompor, cookset","Durasi":"2 hari","Kapasitas":"2 orang"}', '["Hemat","Lengkap","2P","Siap Pakai"]', 'uploads/items/camping-package.jpg', 4.90, 0, 28, 1, 1, 'paket camping 2p lengkap hemat'),
(33, 12, 'Paket Tektok 1P', 'paket-tektok-1p', 'Rentify Select', 'Paket tektok: daypack, raincoat, headlamp, botol minum.', 'Paket praktis untuk pendakian sehari. Semua kebutuhan dasar tektok sudah termasuk.', 85000, 50000, 950000, 8, 1, 3, 1, 1500, '{"Isi":"Daypack 25L, raincoat, headlamp, botol minum","Durasi":"1 hari","Kapasitas":"1 orang"}', '["Tektok","1P","Ringan","Hemat"]', 'uploads/items/camping-package.jpg', 4.80, 0, 32, 0, 1, 'paket tektok daypack raincoat headlamp'),
(34, 12, 'Paket Keluarga 4P', 'paket-keluarga-4p', 'Rentify Select', 'Paket camping keluarga 4 orang dengan tenda family dan cookset.', 'Paket camping keluarga berisi tenda family 4P, 4 sleeping bag, matras, lampu, dan cookset. Liburan outdoor tanpa repot.', 250000, 150000, 4200000, 3, 1, 7, 4, 12500, '{"Isi":"Tenda family 4P, 4 sleeping bag, 4 matras, lampu, cookset","Durasi":"2 hari","Kapasitas":"4 orang"}', '["Keluarga","4P","Lengkap","Hemat"]', 'uploads/items/camping-package.jpg', 4.90, 0, 16, 0, 1, 'paket camping keluarga 4p tenda family');

INSERT IGNORE INTO item_images (item_id, path, alt, is_primary, sort_order) VALUES
(1, 'uploads/items/tent-dome.jpg', 'Tenda Dome Alpenaz 4P', 1, 1),
(2, 'uploads/items/tent-family.jpg', 'Tenda Family Estate 6P', 1, 1),
(3, 'uploads/items/tent-glamping.jpg', 'Tenda Glamping Bell 4P', 1, 1),
(4, 'uploads/items/backpack-60l.jpg', 'Carrier Eiger 60L', 1, 1),
(5, 'uploads/items/backpack-daypack.jpg', 'Daypack Consina 25L', 1, 1),
(6, 'uploads/items/backpack-60l.jpg', 'Carrier Deuter Aircontact 65L', 1, 1),
(7, 'uploads/items/boots-hiking.jpg', 'Salomon Quest 4D GTX', 1, 1),
(8, 'uploads/items/shoes-trail.jpg', 'Trail Running Arei', 1, 1),
(9, 'uploads/items/boots-hiking.jpg', 'Boots Rei Waterproof', 1, 1),
(10, 'uploads/items/jacket-hardshell.jpg', 'Hardshell Summit Series', 1, 1),
(11, 'uploads/items/jacket-down.jpg', 'Down Jacket Ultralight', 1, 1),
(12, 'uploads/items/jacket-hardshell.jpg', 'Raincoat Poncho', 1, 1),
(13, 'uploads/items/sleeping-bag.jpg', 'Sleeping Bag Ultralight', 1, 1),
(14, 'uploads/items/sleeping-mat.jpg', 'Sleeping Pad Inflatable', 1, 1),
(15, 'uploads/items/sleeping-mat.jpg', 'Matras Foil', 1, 1),
(16, 'uploads/items/headlamp.jpg', 'Nitecore NU33', 1, 1),
(17, 'uploads/items/lantern.jpg', 'Blackdog LED Lantern', 1, 1),
(18, 'uploads/items/headlamp.jpg', 'Senter Taktikal', 1, 1),
(19, 'uploads/items/trekking-poles.jpg', 'Trekking Pole Carbon', 1, 1),
(20, 'uploads/items/gps-device.jpg', 'Garmin eTrex 32x', 1, 1),
(21, 'uploads/items/gps-device.jpg', 'Kompas Bidik', 1, 1),
(22, 'uploads/items/stove-cookset.jpg', 'Fire Maple Polaris', 1, 1),
(23, 'uploads/items/cookware-set.jpg', 'Cookset Aluminium 4P', 1, 1),
(24, 'uploads/items/stove-cookset.jpg', 'Gas Canister 230g', 1, 1),
(25, 'uploads/items/hammock.jpg', 'Eno DoubleNest Hammock', 1, 1),
(26, 'uploads/items/tarp.jpg', 'Tarp Shelter 3x3', 1, 1),
(27, 'uploads/items/sanitizer-kit.jpg', 'Portable Shower 20L', 1, 1),
(28, 'uploads/items/sanitizer-kit.jpg', 'Toilet Kit Camping', 1, 1),
(29, 'uploads/items/water-bottle.jpg', 'Water Bottle 1L', 1, 1),
(30, 'uploads/items/powerbank.jpg', 'Powerbank 20000mAh', 1, 1),
(31, 'uploads/items/powerbank.jpg', 'Solar Charger 21W', 1, 1),
(32, 'uploads/items/camping-package.jpg', 'Paket Camping 2P', 1, 1),
(33, 'uploads/items/camping-package.jpg', 'Paket Tektok 1P', 1, 1),
(34, 'uploads/items/camping-package.jpg', 'Paket Keluarga 4P', 1, 1);

INSERT IGNORE INTO item_variants (id, item_id, sku, label, attributes, stock_total, price_per_day, is_active, sort_order) VALUES
(1, 7, 'SLM-Q4D-4041', '40-41 EU', '{"ukuran":"40-41"}', 2, NULL, 1, 1),
(2, 7, 'SLM-Q4D-4243', '42-43 EU', '{"ukuran":"42-43"}', 2, NULL, 1, 2),
(3, 7, 'SLM-Q4D-4445', '44-45 EU', '{"ukuran":"44-45"}', 2, NULL, 1, 3),
(4, 8, 'AREI-TR-3839', '38-39 EU', '{"ukuran":"38-39"}', 2, NULL, 1, 1),
(5, 8, 'AREI-TR-4041', '40-41 EU', '{"ukuran":"40-41"}', 2, NULL, 1, 2),
(6, 8, 'AREI-TR-4243', '42-43 EU', '{"ukuran":"42-43"}', 2, NULL, 1, 3),
(7, 8, 'AREI-TR-4445', '44-45 EU', '{"ukuran":"44-45"}', 2, NULL, 1, 4),
(8, 9, 'REI-BT-4041', '40-41 EU', '{"ukuran":"40-41"}', 2, NULL, 1, 1),
(9, 9, 'REI-BT-4243', '42-43 EU', '{"ukuran":"42-43"}', 2, NULL, 1, 2),
(10, 9, 'REI-BT-4445', '44-45 EU', '{"ukuran":"44-45"}', 2, NULL, 1, 3),
(11, 10, 'SUM-HS-S', 'S', '{"ukuran":"S"}', 2, NULL, 1, 1),
(12, 10, 'SUM-HS-M', 'M', '{"ukuran":"M"}', 3, NULL, 1, 2),
(13, 10, 'SUM-HS-L', 'L', '{"ukuran":"L"}', 3, NULL, 1, 3),
(14, 10, 'SUM-HS-XL', 'XL', '{"ukuran":"XL"}', 2, NULL, 1, 4),
(15, 11, 'SUM-DJ-S', 'S', '{"ukuran":"S"}', 2, NULL, 1, 1),
(16, 11, 'SUM-DJ-M', 'M', '{"ukuran":"M"}', 2, NULL, 1, 2),
(17, 11, 'SUM-DJ-L', 'L', '{"ukuran":"L"}', 2, NULL, 1, 3),
(18, 11, 'SUM-DJ-XL', 'XL', '{"ukuran":"XL"}', 2, NULL, 1, 4);

INSERT IGNORE INTO item_filter_values (item_id, filter_id, value, numeric_value) VALUES
(1,1,'Dome',NULL),(1,2,'4P',4),
(2,1,'Family',NULL),(2,2,'6P',6),
(3,1,'Glamping',NULL),(3,2,'4P',4),
(4,3,'Carrier 45-60L',NULL),(4,4,'60L',60),
(5,3,'Daypack',NULL),(5,4,'25L',25),
(6,3,'Carrier 65L+',NULL),(6,4,'65L',65),
(7,5,'Hiking Boots',NULL),
(8,5,'Trail Running',NULL),
(9,5,'Hiking Boots',NULL),
(10,7,'Hardshell',NULL),
(11,7,'Down',NULL),
(12,7,'Raincoat',NULL),
(13,9,'Sleeping Bag',NULL),(13,10,'Comfort 0-5°C',NULL),
(14,9,'Matras',NULL),(14,10,'Comfort 5-10°C',NULL),
(15,9,'Matras',NULL),(15,10,'Umum',NULL),
(16,11,'Headlamp',NULL),(16,12,'1000-1500 lm',1200),
(17,11,'Lantern',NULL),(17,12,'500-800 lm',400),
(18,11,'Senter',NULL),(18,12,'2000+ lm',2000),
(19,13,'Trekking Pole',NULL),
(20,13,'GPS',NULL),
(21,13,'Kompas',NULL),
(22,14,'Kompor',NULL),(22,15,'3-4P',4),
(23,14,'Cookset',NULL),(23,15,'3-4P',4),
(24,14,'Gas',NULL),(24,15,'1-2P',2),
(25,16,'Hammock',NULL),
(26,16,'Tarp',NULL),
(27,17,'Shower',NULL),
(28,17,'Toilet Kit',NULL),
(29,17,'Botol',NULL),
(30,18,'Powerbank',NULL),
(31,18,'Solar Charger',NULL),
(32,19,'Paket 2P',NULL),
(33,19,'Paket Tektok',NULL),
(34,19,'Paket 4P',NULL);

INSERT IGNORE INTO activities (id, name, slug, icon, description, default_category_ids, sort_order, is_active) VALUES
(1, 'Tektok', 'tektok', 'footprints', 'Pendakian singkat sehari, fokus pada perlengkapan ringan dan esensial.', '[2,3,4,6,7,10,11]', 1, 1),
(2, 'Camping', 'camping', 'tent', 'Menginap di alam terbuka, butuh shelter, tidur, masak, dan penerangan.', '[1,2,5,6,8,9,10,11]', 2, 1),
(3, 'Hiking / Mendaki', 'hiking', 'mountain', 'Pendakian 2–4 hari dengan carrier, shelter, dan perlindungan cuaca.', '[1,2,3,4,5,6,7,8,10]', 3, 1),
(4, 'Ekspedisi', 'ekspedisi', 'flag', 'Perjalanan panjang 5+ hari yang membutuhkan perlengkapan lengkap dan cadangan.', '[1,2,3,4,5,6,7,8,9,10,11]', 4, 1);

INSERT IGNORE INTO destinations (id, name, slug, region, elevation_m, terrain, notes, is_active) VALUES
(1, 'Gunung Sanggabuana', 'gunung-sanggabuana', 'Karawang, Jawa Barat', 1250, 'Hutan tropis, jalur tanah, bebatuan', 'Jalur favorit warga Karawang. Siapkan sepatu grip, headlamp, dan jas hujan.', 1),
(2, 'Puncak Sempur', 'puncak-sempur', 'Karawang, Jawa Barat', 900, 'Perbukitan, savana, jalur terbuka', 'Cocok untuk camping dan tektok. Angin malam cukup dingin.', 1),
(3, 'Loji', 'loji', 'Karawang, Jawa Barat', 700, 'Hutan pinus, jalur landai', 'Lokasi camping populer, akses relatif mudah. Bawa matras dan lampu tenda.', 1),
(4, 'Curug Cigentis', 'curug-cigentis', 'Karawang, Jawa Barat', 500, 'Hutan, sungai, jalur licin', 'Tektok air terjun. Gunakan sepatu anti-slip dan bawa raincoat.', 1),
(5, 'Gunung Puntang', 'gunung-puntang', 'Bandung, Jawa Barat', 2223, 'Hutan hujan, jalur panjang', 'Suhu malam bisa di bawah 10°C. Sleeping bag dan hardshell wajib.', 1),
(6, 'Gunung Papandayan', 'gunung-papandayan', 'Garut, Jawa Barat', 2665, 'Vulkanik, kawah, savana', 'Cuaca cepat berubah. Bawa layer hangat, headlamp, dan masker.', 1),
(7, 'Ranca Upas', 'ranca-upas', 'Bandung, Jawa Barat', 1700, 'Hutan pinus, area camping', 'Camping keluarga, banyak spot tenda. Bawa alas tidur ekstra.', 1),
(8, 'Situ Cipule', 'situ-cipule', 'Karawang, Jawa Barat', 50, 'Danau, area terbuka', 'Camping santai dan memancing. Cocok untuk paket keluarga.', 1);

INSERT IGNORE INTO activity_gear_rules (activity_id, category_id, priority, qty_per_person, min_days, note) VALUES
(1,2,'wajib',1,1,'Daypack untuk membawa air, jas hujan, dan logistik harian.'),
(1,3,'wajib',1,1,'Sepatu dengan grip untuk jalur tanah dan bebatuan.'),
(1,4,'wajib',1,1,'Jas hujan atau hardshell untuk antisipasi cuaca.'),
(1,6,'wajib',1,1,'Headlamp untuk jaga-jaga jika turun malam.'),
(1,7,'opsional',1,1,'Trekking pole membantu mengurangi beban lutut.'),
(1,10,'wajib',1,1,'Botol minum minimal 1 liter.'),
(1,11,'opsional',1,1,'Powerbank untuk gadget dan headlamp.'),
(2,1,'wajib',0.5,1,'Tenda sesuai jumlah orang.'),
(2,2,'wajib',1,1,'Carrier untuk membawa logistik camping.'),
(2,5,'wajib',1,1,'Sleeping bag dan matras untuk tidur nyaman.'),
(2,6,'wajib',1,1,'Headlamp dan lampu tenda.'),
(2,8,'wajib',1,1,'Kompor dan cookset untuk memasak.'),
(2,9,'opsional',1,1,'Hammock/tarp untuk area bersantai.'),
(2,10,'wajib',1,1,'Toilet kit dan botol minum.'),
(2,11,'opsional',1,1,'Powerbank untuk perangkat elektronik.'),
(3,1,'wajib',0.5,2,'Tenda untuk menginap di jalur.'),
(3,2,'wajib',1,2,'Carrier 45–65L.'),
(3,3,'wajib',1,2,'Hiking boots untuk medan panjang.'),
(3,4,'wajib',1,2,'Hardshell dan layer hangat.'),
(3,5,'wajib',1,2,'Sleeping bag comfort di bawah 10°C.'),
(3,6,'wajib',1,2,'Headlamp untuk navigasi malam.'),
(3,7,'wajib',0.5,2,'Trekking pole dan navigasi.'),
(3,8,'wajib',1,2,'Kompor dan cookset.'),
(3,10,'wajib',1,2,'Botol minum dan sanitasi.'),
(4,1,'wajib',1,1,'Tenda ekspedisi dengan flysheet kuat.'),
(4,2,'wajib',1,1,'Carrier 60L+ untuk logistik panjang.'),
(4,3,'wajib',1,1,'Sepatu hiking boots.'),
(4,4,'wajib',1,1,'Hardshell, down jacket, dan layer.'),
(4,5,'wajib',1,1,'Sleeping bag dan matras.'),
(4,6,'wajib',1,1,'Headlamp + senter cadangan.'),
(4,7,'wajib',1,1,'GPS/kompas dan trekking pole.'),
(4,8,'wajib',1,1,'Kompor, cookset, gas cadangan.'),
(4,9,'opsional',1,1,'Hammock/tarp untuk shelter tambahan.'),
(4,10,'wajib',1,1,'Sanitasi dan botol minum.'),
(4,11,'wajib',1,1,'Powerbank dan solar charger.');

INSERT IGNORE INTO pickup_points (id, store_id, name, address, hours, maps_url, latitude, longitude, is_active, sort_order) VALUES
(1, 1, 'Rentify Karawang Kota', 'Jl. Galuh Mas Raya No. 8, Karawang Barat, Jawa Barat', 'Setiap hari, 08.00-21.00 WIB', 'https://maps.google.com/?q=-6.301,107.305', -6.3010000, 107.3050000, 1, 1),
(2, 2, 'Rentify Karawang Selatan', 'Jl. Raya Sanggabuana No. 21, Karawang Selatan, Jawa Barat', 'Setiap hari, 07.00-20.00 WIB', 'https://maps.google.com/?q=-6.392,107.301', -6.3920000, 107.3010000, 1, 2),
(3, 3, 'Rentify Cikampek', 'Jl. Raya Cikampek No. 45, Cikampek, Karawang, Jawa Barat', 'Setiap hari, 08.00-20.00 WIB', 'https://maps.google.com/?q=-6.402,107.456', -6.4020000, 107.4560000, 1, 3),
(4, 4, 'Rentify Bandung', 'Jl. Ir. H. Juanda No. 112, Dago, Bandung, Jawa Barat', 'Setiap hari, 08.00-21.00 WIB', 'https://maps.google.com/?q=-6.9175,107.6191', -6.9175000, 107.6191000, 1, 4),
(5, 5, 'Rentify Jakarta', 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan', 'Setiap hari, 08.00-21.00 WIB', 'https://maps.google.com/?q=-6.2088,106.8456', -6.2088000, 106.8456000, 1, 5),
(6, 6, 'Rentify Bogor', 'Jl. Pajajaran No. 88, Bogor Tengah, Jawa Barat', 'Setiap hari, 07.00-21.00 WIB', 'https://maps.google.com/?q=-6.595,106.8166', -6.5950000, 106.8166000, 1, 6),
(7, 7, 'Rentify Surabaya', 'Jl. Basuki Rahmat No. 101, Surabaya, Jawa Timur', 'Setiap hari, 08.00-21.00 WIB', 'https://maps.google.com/?q=-7.2575,112.7521', -7.2575000, 112.7521000, 1, 7);

INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, setting_type) VALUES
('app_name', 'Rentify Outdoor Karawang', 'umum', 'text'),
('app_tagline', 'Gear Up, Go Further', 'umum', 'text'),
('whatsapp', '6281234567890', 'kontak', 'text'),
('email', 'hello@rentify.id', 'kontak', 'text'),
('address', 'Basecamp Karawang Kota, Jawa Barat', 'kontak', 'textarea'),
('payment_bank', 'BCA', 'pembayaran', 'text'),
('payment_account', '1234567890', 'pembayaran', 'text'),
('payment_account_name', 'Rentify Outdoor Karawang', 'pembayaran', 'text'),
('qris_image', '', 'pembayaran', 'image'),
('delivery_fee', '15000', 'pembayaran', 'number'),
('hold_hours', '24', 'sistem', 'number'),
('ai_enabled', '1', 'sistem', 'boolean');

INSERT IGNORE INTO bookings (id, code, user_id, store_id, pickup_point_id, start_date, end_date, duration_days, pickup_method, delivery_address, subtotal, deposit_total, delivery_fee, discount, late_fee, total, status, payment_status, notes, admin_notes, pickup_code, hold_expires_at, returned_at, completed_at, created_at) VALUES
(1, 'RNT-20261101-0001', 2, 1, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 19 DAY), 2, 'pickup', NULL, 108000, 0, 0, 0, 0, 108000, 'completed', 'paid', 'Camping di Loji, terima kasih.', 'Peralatan kembali lengkap dan bersih.', 'A1B2C3', NULL, DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 22 DAY)),
(2, 'RNT-20261105-0002', 3, 2, 2, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 2, 'pickup', NULL, 170000, 0, 0, 0, 0, 170000, 'completed', 'paid', 'Camping keluarga kecil.', 'Sudah dicek, aman.', 'D4E5F6', NULL, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 17 DAY)),
(3, 'RNT-20261121-0003', 2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 3, 'pickup', NULL, 201000, 0, 0, 0, 0, 201000, 'ongoing', 'paid', 'Trekking Sanggabuana 2 malam.', 'Sudah diambil customer.', 'G7H8I9', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'RNT-20261125-0004', 3, 1, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 2, 'pickup', NULL, 190000, 75000, 0, 0, 0, 265000, 'pending', 'unpaid', 'Mohon tenda dipastikan bersih.', NULL, NULL, DATE_ADD(NOW(), INTERVAL 20 HOUR), NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(5, 'RNT-20261110-0005', 2, 1, 1, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 2, 'pickup', NULL, 80000, 0, 0, 0, 0, 80000, 'completed', 'paid', 'Sewa hammock dan lampu tenda.', 'Lengkap.', 'J1K2L3', NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY));

INSERT IGNORE INTO booking_items (id, booking_id, item_id, variant_id, item_name, variant_label, qty, price_per_day, duration_days, subtotal, deposit, start_date, end_date) VALUES
(1, 1, 13, NULL, 'Sleeping Bag Ultralight -2°C', NULL, 1, 32000, 2, 64000, 0, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 19 DAY)),
(2, 1, 16, NULL, 'Nitecore NU33 Headlamp 1200 Lumens', NULL, 1, 22000, 2, 44000, 0, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 19 DAY)),
(3, 2, 1, NULL, 'Tenda Dome Alpenaz 4P', NULL, 1, 65000, 2, 130000, 0, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(4, 2, 22, NULL, 'Fire Maple Polaris & FMC-206 Series', NULL, 1, 20000, 2, 40000, 0, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(5, 3, 4, NULL, 'Carrier Eiger 60L', NULL, 1, 45000, 3, 135000, 0, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
(6, 3, 16, NULL, 'Nitecore NU33 Headlamp 1200 Lumens', NULL, 1, 22000, 3, 66000, 0, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
(7, 4, 2, NULL, 'Tenda Family Estate 6P', NULL, 1, 95000, 2, 190000, 0, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY)),
(8, 5, 25, NULL, 'Eno DoubleNest Hammock & Bug Net', NULL, 1, 18000, 2, 36000, 0, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(9, 5, 17, NULL, 'Blackdog LED Lantern 3000K', NULL, 1, 15000, 2, 30000, 0, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(10, 5, 29, NULL, 'Water Bottle 1L Insulated', NULL, 1, 7000, 2, 14000, 0, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY));

INSERT IGNORE INTO booking_status_logs (id, booking_id, status, note, changed_by, created_at) VALUES
(1, 1, 'pending', 'Booking dibuat.', 2, DATE_SUB(NOW(), INTERVAL 22 DAY)),
(2, 1, 'confirmed', 'Pembayaran diverifikasi admin.', 1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
(3, 1, 'completed', 'Peralatan dikembalikan dengan baik.', 1, DATE_SUB(NOW(), INTERVAL 19 DAY)),
(4, 2, 'completed', 'Selesai dan sudah direview.', 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(5, 3, 'ongoing', 'Customer mengambil perlengkapan.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 4, 'pending', 'Menunggu pembayaran.', 3, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(7, 5, 'completed', 'Selesai.', 1, DATE_SUB(NOW(), INTERVAL 8 DAY));

INSERT IGNORE INTO payments (id, booking_id, method, amount, proof_path, status, paid_at, verified_by, note, created_at) VALUES
(1, 1, 'transfer', 108000, NULL, 'verified', DATE_SUB(NOW(), INTERVAL 21 DAY), 1, 'Transfer BCA.', DATE_SUB(NOW(), INTERVAL 21 DAY)),
(2, 2, 'qris', 170000, NULL, 'verified', DATE_SUB(NOW(), INTERVAL 16 DAY), 1, 'QRIS.', DATE_SUB(NOW(), INTERVAL 16 DAY)),
(3, 3, 'transfer', 201000, NULL, 'verified', DATE_SUB(NOW(), INTERVAL 3 DAY), 1, 'Transfer BCA.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 5, 'transfer', 80000, NULL, 'verified', DATE_SUB(NOW(), INTERVAL 10 DAY), 1, 'Transfer BCA.', DATE_SUB(NOW(), INTERVAL 10 DAY));

INSERT IGNORE INTO reviews (id, booking_id, user_id, item_id, rating, comment, is_published, created_at) VALUES
(1, 1, 2, 13, 5, 'Sleeping bag-nya hangat dan wangi. Tidur nyaman banget di Loji.', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(2, 1, 2, 16, 5, 'Headlamp terang, baterai awet. Recommended!', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(3, 2, 3, 1, 5, 'Tenda kokoh, tidak bocor walau hujan malam. Pelayanan cepat.', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
(4, 2, 3, 22, 4, 'Kompor ringan dan mudah dipakai. Cocok untuk pemula.', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
(5, 5, 2, 25, 5, 'Hammock nyaman, strap-nya aman untuk pohon. Mantap!', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 5, 2, 17, 5, 'Lampu tenda hangat, suasana camping jadi lebih syahdu.', 1, DATE_SUB(NOW(), INTERVAL 7 DAY));

INSERT IGNORE INTO wishlists (user_id, item_id) VALUES (2, 7), (2, 19), (3, 13);

INSERT IGNORE INTO notifications (id, user_id, type, title, message, link, is_read, created_at) VALUES
(1, 2, 'booking', 'Pesanan RNT-20261121-0003 sedang berjalan', 'Jangan lupa kembalikan perlengkapan sesuai tanggal sewa.', 'pesanan_detail.php?id=3', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 2, 'promo', 'AI Gear Planner siap membantu', 'Coba rekomendasi perlengkapan untuk petualangan berikutnya.', 'rekomendasi.php', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 1, 'admin', 'Pembayaran baru menunggu verifikasi', 'Ada pembayaran yang perlu dicek.', 'admin/pesanan.php', 0, DATE_SUB(NOW(), INTERVAL 4 HOUR));

INSERT IGNORE INTO ai_sessions (id, user_id, activity_id, category_id, destination_id, destination_text, duration_days, participants, notes, model, prompt_hash, status, summary, tips, raw_response, latency_ms, created_at) VALUES
(1, 2, 2, NULL, 1, 'Gunung Sanggabuana', 2, 2, 'Camping akhir pekan, suka hujan.', 'ag/gemini-3.8-flash-medium', 'seed-demo-001', 'success', 'Paket camping 2 hari untuk 2 orang di Sanggabuana dengan shelter, tidur, masak, dan penerangan.', '["Bawa jas hujan cadangan.","Simpan makanan di dalam carrier agar aman dari hewan.","Isi ulang air di basecamp sebelum naik."]', NULL, 4200, DATE_SUB(NOW(), INTERVAL 6 DAY));

INSERT IGNORE INTO ai_session_items (ai_session_id, item_id, variant_id, qty, priority, reason, is_selected, sort_order) VALUES
(1, 1, NULL, 1, 'wajib', 'Tenda 4P cukup untuk 2 orang dan nyaman saat hujan.', 1, 1),
(1, 13, NULL, 2, 'wajib', 'Satu sleeping bag per orang untuk suhu malam Sanggabuana.', 1, 2),
(1, 16, NULL, 1, 'wajib', 'Headlamp untuk navigasi malam dan lampu tenda.', 1, 3),
(1, 22, NULL, 1, 'wajib', 'Kompor ringan untuk memasak di camp.', 1, 4),
(1, 4, NULL, 1, 'opsional', 'Carrier untuk membawa logistik 2 hari.', 1, 5);

-- Seed reviews aggregate ke item.
UPDATE items i
LEFT JOIN (
  SELECT item_id, ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS total
  FROM reviews WHERE is_published = 1 GROUP BY item_id
) r ON r.item_id = i.id
SET i.rating_avg = COALESCE(r.avg_rating, i.rating_avg),
    i.rating_count = COALESCE(r.total, i.rating_count);


-- Inventaris per cabang: semua produk tersedia di 7 cabang dengan stok/harga bervariasi.
-- Sebagian kecil sengaja nonaktif agar katalog tiap cabang tidak identik.
INSERT IGNORE INTO store_items
(store_id, item_id, price_per_day, deposit, replacement_value, stock_total, min_duration, max_duration,
 is_active, is_featured, sort_order)
SELECT
  s.id,
  i.id,
  ROUND(i.price_per_day * (1 + ((s.id + i.id) % 4) * 0.02), 0),
  i.deposit,
  i.replacement_value,
  GREATEST(1, i.stock_total - ((s.id * 5 + i.id) % 4)),
  i.min_duration,
  i.max_duration,
  CASE WHEN (s.id * 11 + i.id) % 23 = 0 THEN 0 ELSE 1 END,
  CASE WHEN i.is_featured = 1 AND s.id IN (1, 4, 6) THEN 1 ELSE 0 END,
  i.id
FROM stores s
CROSS JOIN items i;

INSERT IGNORE INTO store_item_variants (store_item_id, variant_id, stock_total, price_per_day, is_active)
SELECT si.id, v.id, GREATEST(0, v.stock_total - ((si.store_id + v.id) % 2)),
       COALESCE(v.price_per_day, si.price_per_day), 1
FROM store_items si
JOIN item_variants v ON v.item_id = si.item_id;

UPDATE booking_items bi
JOIN bookings b ON b.id = bi.booking_id
JOIN store_items si ON si.store_id = b.store_id AND si.item_id = bi.item_id
SET bi.store_item_id = si.id
WHERE bi.store_item_id IS NULL;
