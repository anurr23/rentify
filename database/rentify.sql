-- Rentify Outdoor Karawang
-- Schema database utama. Kompatibel MariaDB 10.4+ / MySQL 8+.
SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS stores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(24) NOT NULL,
  name VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  address TEXT NOT NULL,
  district VARCHAR(100) DEFAULT NULL,
  city VARCHAR(100) NOT NULL,
  province VARCHAR(100) NOT NULL,
  postal_code VARCHAR(10) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  whatsapp VARCHAR(30) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  maps_url VARCHAR(500) DEFAULT NULL,
  opening_hours VARCHAR(160) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stores_code (code),
  UNIQUE KEY uq_stores_slug (slug),
  KEY idx_stores_active (is_active, sort_order),
  KEY idx_stores_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_code VARCHAR(24) DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('superadmin','admin','customer') NOT NULL DEFAULT 'customer',
  store_id BIGINT UNSIGNED DEFAULT NULL,
  preferred_store_id BIGINT UNSIGNED DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  last_latitude DECIMAL(10,7) DEFAULT NULL,
  last_longitude DECIMAL(10,7) DEFAULT NULL,
  last_location_at DATETIME DEFAULT NULL,
  identity_number VARCHAR(50) DEFAULT NULL,
  identity_photo VARCHAR(255) DEFAULT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone),
  UNIQUE KEY uq_users_member_code (member_code),
  KEY idx_users_role (role),
  KEY idx_users_store (store_id),
  KEY idx_users_preferred_store (preferred_store_id),
  CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL,
  CONSTRAINT fk_users_preferred_store FOREIGN KEY (preferred_store_id) REFERENCES stores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_remember_user (user_id),
  KEY idx_remember_hash (token_hash),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reset_user (user_id),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'info',
  title VARCHAR(160) NOT NULL,
  message TEXT DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user (user_id, is_read, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  description TEXT DEFAULT NULL,
  icon VARCHAR(50) DEFAULT NULL,
  hero_image VARCHAR(255) DEFAULT NULL,
  accent_color VARCHAR(20) DEFAULT '#CBE8CE',
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS category_filters (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(100) NOT NULL,
  filter_key VARCHAR(60) NOT NULL,
  type ENUM('chips','size','range') NOT NULL DEFAULT 'chips',
  options JSON DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_category_filter (category_id, filter_key),
  CONSTRAINT fk_filter_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NOT NULL,
  owner_store_id BIGINT UNSIGNED DEFAULT NULL,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  brand VARCHAR(100) DEFAULT NULL,
  short_description VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  price_per_day DECIMAL(12,2) NOT NULL DEFAULT 0,
  deposit DECIMAL(12,2) NOT NULL DEFAULT 0,
  replacement_value DECIMAL(12,2) DEFAULT NULL,
  stock_total INT NOT NULL DEFAULT 0,
  min_duration INT NOT NULL DEFAULT 1,
  max_duration INT NOT NULL DEFAULT 30,
  capacity_person INT DEFAULT NULL,
  weight_gram INT DEFAULT NULL,
  specs JSON DEFAULT NULL,
  features JSON DEFAULT NULL,
  primary_image VARCHAR(255) DEFAULT NULL,
  rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
  rating_count INT NOT NULL DEFAULT 0,
  rented_count INT NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  search_keywords VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_items_slug (slug),
  KEY idx_items_category (category_id, is_active),
  KEY idx_items_owner_store (owner_store_id),
  KEY idx_items_featured (is_featured, is_active),
  KEY idx_items_price (price_per_day),
  KEY idx_items_rating (rating_avg),
  CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT,
  CONSTRAINT fk_items_owner_store FOREIGN KEY (owner_store_id) REFERENCES stores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_images (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id BIGINT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(180) DEFAULT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_item_images_item (item_id),
  CONSTRAINT fk_item_images_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_variants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(80) DEFAULT NULL,
  label VARCHAR(120) NOT NULL,
  attributes JSON DEFAULT NULL,
  stock_total INT NOT NULL DEFAULT 0,
  price_per_day DECIMAL(12,2) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_variant_sku (sku),
  KEY idx_variants_item (item_id, is_active),
  CONSTRAINT fk_variants_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  price_per_day DECIMAL(12,2) NOT NULL DEFAULT 0,
  deposit DECIMAL(12,2) NOT NULL DEFAULT 0,
  replacement_value DECIMAL(12,2) DEFAULT NULL,
  stock_total INT NOT NULL DEFAULT 0,
  min_duration INT NOT NULL DEFAULT 1,
  max_duration INT NOT NULL DEFAULT 30,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED DEFAULT NULL,
  updated_by BIGINT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_store_item (store_id, item_id),
  KEY idx_store_items_item (item_id, is_active),
  KEY idx_store_items_store (store_id, is_active),
  CONSTRAINT fk_store_items_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT fk_store_items_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
  CONSTRAINT fk_store_items_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_store_items_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_item_variants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_item_id BIGINT UNSIGNED NOT NULL,
  variant_id BIGINT UNSIGNED NOT NULL,
  stock_total INT NOT NULL DEFAULT 0,
  price_per_day DECIMAL(12,2) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_store_item_variant (store_item_id, variant_id),
  KEY idx_store_variant_variant (variant_id),
  CONSTRAINT fk_siv_store_item FOREIGN KEY (store_item_id) REFERENCES store_items (id) ON DELETE CASCADE,
  CONSTRAINT fk_siv_variant FOREIGN KEY (variant_id) REFERENCES item_variants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_filter_values (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id BIGINT UNSIGNED NOT NULL,
  filter_id BIGINT UNSIGNED NOT NULL,
  value VARCHAR(120) NOT NULL,
  numeric_value DECIMAL(12,2) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_item_filter (item_id, filter_id, value),
  KEY idx_item_filter_search (filter_id, value),
  CONSTRAINT fk_ifv_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
  CONSTRAINT fk_ifv_filter FOREIGN KEY (filter_id) REFERENCES category_filters (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activities (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  icon VARCHAR(50) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  default_category_ids JSON DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_activities_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destinations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  region VARCHAR(120) DEFAULT NULL,
  elevation_m INT DEFAULT NULL,
  terrain VARCHAR(180) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_destinations_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_gear_rules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  activity_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED DEFAULT NULL,
  item_id BIGINT UNSIGNED DEFAULT NULL,
  priority ENUM('wajib','opsional') NOT NULL DEFAULT 'wajib',
  qty_per_person DECIMAL(4,2) NOT NULL DEFAULT 1,
  min_days INT NOT NULL DEFAULT 1,
  note VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_rules_activity (activity_id, is_active),
  CONSTRAINT fk_rules_activity FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE,
  CONSTRAINT fk_rules_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_rules_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pickup_points (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id BIGINT UNSIGNED DEFAULT NULL,
  name VARCHAR(140) NOT NULL,
  address TEXT NOT NULL,
  hours VARCHAR(120) DEFAULT NULL,
  maps_url VARCHAR(255) DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pickup_store (store_id, is_active),
  CONSTRAINT fk_pickup_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(24) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  store_id BIGINT UNSIGNED DEFAULT NULL,
  pickup_point_id BIGINT UNSIGNED DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  duration_days INT NOT NULL DEFAULT 1,
  pickup_method ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
  delivery_address TEXT DEFAULT NULL,
  delivery_lat DECIMAL(10,7) DEFAULT NULL,
  delivery_lng DECIMAL(10,7) DEFAULT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  deposit_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  late_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('pending','confirmed','preparing','ready','ongoing','returned','completed','cancelled') NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid','waiting','partial','paid','refunded') NOT NULL DEFAULT 'unpaid',
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes TEXT DEFAULT NULL,
  admin_notes TEXT DEFAULT NULL,
  cancel_reason VARCHAR(255) DEFAULT NULL,
  pickup_code VARCHAR(12) DEFAULT NULL,
  hold_expires_at DATETIME DEFAULT NULL,
  returned_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bookings_code (code),
  KEY idx_bookings_user (user_id, created_at),
  KEY idx_bookings_status (status, created_at),
  KEY idx_bookings_dates (start_date, end_date),
  KEY idx_bookings_store (store_id, created_at),
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL,
  CONSTRAINT fk_bookings_pickup FOREIGN KEY (pickup_point_id) REFERENCES pickup_points (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  store_item_id BIGINT UNSIGNED DEFAULT NULL,
  variant_id BIGINT UNSIGNED DEFAULT NULL,
  item_name VARCHAR(180) NOT NULL,
  variant_label VARCHAR(120) DEFAULT NULL,
  qty INT NOT NULL DEFAULT 1,
  price_per_day DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration_days INT NOT NULL DEFAULT 1,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  deposit DECIMAL(12,2) NOT NULL DEFAULT 0,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_booking_items_booking (booking_id),
  KEY idx_booking_items_store_item (store_item_id),
  KEY idx_booking_items_availability (item_id, variant_id, start_date, end_date),
  CONSTRAINT fk_bi_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_bi_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE RESTRICT,
  CONSTRAINT fk_bi_store_item FOREIGN KEY (store_item_id) REFERENCES store_items (id) ON DELETE SET NULL,
  CONSTRAINT fk_bi_variant FOREIGN KEY (variant_id) REFERENCES item_variants (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_status_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  changed_by BIGINT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_status_logs_booking (booking_id, created_at),
  CONSTRAINT fk_logs_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_logs_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  method ENUM('transfer','qris','cash') NOT NULL DEFAULT 'transfer',
  payment_type ENUM('dp','settlement','full') NOT NULL DEFAULT 'full',
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  proof_path VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  paid_at DATETIME DEFAULT NULL,
  verified_by BIGINT UNSIGNED DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_booking (booking_id),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_user FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  comment TEXT DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_booking_item (booking_id, item_id),
  KEY idx_reviews_item (item_id, is_published),
  CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlists (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wishlist (user_id, item_id),
  KEY idx_wishlist_user (user_id),
  CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED DEFAULT NULL,
  activity_id BIGINT UNSIGNED DEFAULT NULL,
  category_id BIGINT UNSIGNED DEFAULT NULL,
  destination_id BIGINT UNSIGNED DEFAULT NULL,
  destination_text VARCHAR(180) DEFAULT NULL,
  duration_days INT NOT NULL DEFAULT 1,
  participants INT NOT NULL DEFAULT 1,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  model VARCHAR(100) DEFAULT NULL,
  prompt_hash VARCHAR(64) DEFAULT NULL,
  status ENUM('success','fallback','failed') NOT NULL DEFAULT 'success',
  summary TEXT DEFAULT NULL,
  tips JSON DEFAULT NULL,
  raw_response MEDIUMTEXT DEFAULT NULL,
  latency_ms INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ai_user (user_id, created_at),
  KEY idx_ai_hash (prompt_hash),
  CONSTRAINT fk_ai_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_ai_activity FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE SET NULL,
  CONSTRAINT fk_ai_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_ai_destination FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_session_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ai_session_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  variant_id BIGINT UNSIGNED DEFAULT NULL,
  qty INT NOT NULL DEFAULT 1,
  priority ENUM('wajib','opsional') NOT NULL DEFAULT 'wajib',
  reason VARCHAR(500) DEFAULT NULL,
  is_selected TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_ai_items_session (ai_session_id),
  CONSTRAINT fk_aii_session FOREIGN KEY (ai_session_id) REFERENCES ai_sessions (id) ON DELETE CASCADE,
  CONSTRAINT fk_aii_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
  CONSTRAINT fk_aii_variant FOREIGN KEY (variant_id) REFERENCES item_variants (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_cache (
  prompt_hash VARCHAR(64) NOT NULL,
  response MEDIUMTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (prompt_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  setting_group VARCHAR(40) NOT NULL DEFAULT 'umum',
  setting_type ENUM('text','textarea','number','image','boolean') NOT NULL DEFAULT 'text',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED DEFAULT NULL,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(60) DEFAULT NULL,
  entity_id BIGINT UNSIGNED DEFAULT NULL,
  meta JSON DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id, created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
