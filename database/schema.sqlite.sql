-- Thunder Mounts — SQLite schema (generated from database/schema.sql)
-- Used for local testing and the automated journey test.

-- ==========================================================================
-- Thunder Mounts — MySQL / MariaDB schema (utf8mb4, InnoDB)
-- Compatible with MySQL 5.7+/8.x and MariaDB 10.3+ on Hostinger.
-- All money columns are DECIMAL; application arithmetic is done in cents.
-- ==========================================================================

SET NAMES utf8mb4;


CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  group_name VARCHAR(40) NOT NULL DEFAULT 'general',
  updated_at TEXT NULL,
  PRIMARY KEY (`key`)
) ;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(80) NOT NULL DEFAULT '',
  last_name VARCHAR(80) NOT NULL DEFAULT '',
  phone VARCHAR(30) NOT NULL DEFAULT '',
  company VARCHAR(120) NOT NULL DEFAULT '',
  role TEXT NOT NULL DEFAULT 'customer',
  dealer_id INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'active',
  email_verified_at TEXT NULL,
  last_login_at TEXT NULL,
  reset_token_hash CHAR(64) NULL,
  reset_expires_at TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (email)
) ;

CREATE TABLE IF NOT EXISTS dealer_tiers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(80) NOT NULL,
  discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  free_shipping_min DECIMAL(10,2) NULL,
  min_order_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  notes VARCHAR(255) NOT NULL DEFAULT '',
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
) ;

CREATE TABLE IF NOT EXISTS dealers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  company VARCHAR(160) NOT NULL,
  contact_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  business_type VARCHAR(60) NOT NULL DEFAULT '',
  website VARCHAR(255) NOT NULL DEFAULT '',
  resale_number VARCHAR(80) NOT NULL DEFAULT '',
  document_path VARCHAR(255) NOT NULL DEFAULT '',
  address1 VARCHAR(190) NOT NULL DEFAULT '',
  address2 VARCHAR(190) NOT NULL DEFAULT '',
  city VARCHAR(100) NOT NULL DEFAULT '',
  state VARCHAR(60) NOT NULL DEFAULT '',
  postcode VARCHAR(20) NOT NULL DEFAULT '',
  country CHAR(2) NOT NULL DEFAULT 'US',
  tier_id INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  admin_notes TEXT NULL,
  applied_at TEXT NOT NULL,
  reviewed_at TEXT NULL,
  reviewed_by INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (email),
  CONSTRAINT fk_dealers_tier FOREIGN KEY (tier_id) REFERENCES dealer_tiers (id) ON DELETE SET NULL
) ;

CREATE TABLE IF NOT EXISTS addresses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  label VARCHAR(60) NOT NULL DEFAULT 'Address',
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  company VARCHAR(120) NOT NULL DEFAULT '',
  address1 VARCHAR(190) NOT NULL,
  address2 VARCHAR(190) NOT NULL DEFAULT '',
  city VARCHAR(100) NOT NULL,
  state VARCHAR(60) NOT NULL,
  postcode VARCHAR(20) NOT NULL,
  country CHAR(2) NOT NULL DEFAULT 'US',
  phone VARCHAR(30) NOT NULL DEFAULT '',
  is_default_billing INTEGER(1) NOT NULL DEFAULT 0,
  is_default_shipping INTEGER(1) NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  parent_id INTEGER NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description TEXT NULL,
  image VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(300) NOT NULL DEFAULT '',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (slug),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL
) ;

CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  sku VARCHAR(60) NOT NULL,
  model VARCHAR(60) NOT NULL DEFAULT '',
  brand VARCHAR(80) NOT NULL DEFAULT 'Thunder Mounts',
  short_description TEXT NULL,
  description TEXT NULL,
  specs TEXT NULL,
  box_contents TEXT NULL,
  compatibility TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  sale_price DECIMAL(10,2) NULL,
  dealer_price DECIMAL(10,2) NULL,
  cost DECIMAL(10,2) NULL,
  stock_qty INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  manage_stock INTEGER(1) NOT NULL DEFAULT 0,
  stock_status TEXT NOT NULL DEFAULT 'in_stock',
  backorder_allowed INTEGER(1) NOT NULL DEFAULT 0,
  weight DECIMAL(8,2) NULL,
  length DECIMAL(8,2) NULL,
  width DECIMAL(8,2) NULL,
  height DECIMAL(8,2) NULL,
  material VARCHAR(120) NOT NULL DEFAULT '',
  finish VARCHAR(120) NOT NULL DEFAULT '',
  is_featured INTEGER(1) NOT NULL DEFAULT 0,
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  is_taxable INTEGER(1) NOT NULL DEFAULT 1,
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(300) NOT NULL DEFAULT '',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (slug),
  UNIQUE (sku),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ;

CREATE TABLE IF NOT EXISTS product_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(190) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS carts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  token CHAR(64) NOT NULL,
  user_id INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (token)
) ;

CREATE TABLE IF NOT EXISTS cart_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cart_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  UNIQUE (cart_id, product_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_number VARCHAR(30) NOT NULL,
  user_id INTEGER NULL,
  dealer_id INTEGER NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'pending',
  payment_status TEXT NOT NULL DEFAULT 'unpaid',
  payment_method VARCHAR(40) NOT NULL DEFAULT 'paypal',
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  shipping_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_code VARCHAR(40) NOT NULL DEFAULT '',
  shipping_method_name VARCHAR(120) NOT NULL DEFAULT '',
  shipping_address TEXT NULL,
  billing_address TEXT NULL,
  customer_note TEXT NULL,
  admin_note TEXT NULL,
  paypal_order_id VARCHAR(64) NULL,
  paypal_capture_id VARCHAR(64) NULL,
  paypal_payer_id VARCHAR(64) NULL,
  tracking_number VARCHAR(80) NOT NULL DEFAULT '',
  tracking_carrier VARCHAR(80) NOT NULL DEFAULT '',
  refunded_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid_at TEXT NULL,
  shipped_at TEXT NULL,
  completed_at TEXT NULL,
  cancelled_at TEXT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (order_number)
) ;

CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  product_id INTEGER NULL,
  name VARCHAR(190) NOT NULL,
  sku VARCHAR(60) NOT NULL DEFAULT '',
  qty INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_dealer_price INTEGER(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ;

CREATE TABLE IF NOT EXISTS order_notes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  admin_id INTEGER NULL,
  note TEXT NOT NULL,
  is_customer_visible INTEGER(1) NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  CONSTRAINT fk_order_notes_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS order_status_history (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  from_status VARCHAR(30) NOT NULL DEFAULT '',
  to_status VARCHAR(30) NOT NULL,
  note VARCHAR(255) NOT NULL DEFAULT '',
  admin_id INTEGER NULL,
  created_at TEXT NOT NULL,
  CONSTRAINT fk_order_history_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS discounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code VARCHAR(40) NOT NULL,
  description VARCHAR(190) NOT NULL DEFAULT '',
  type TEXT NOT NULL DEFAULT 'percent',
  value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  starts_at TEXT NULL,
  ends_at TEXT NULL,
  usage_limit INT NULL,
  used_count INT NOT NULL DEFAULT 0,
  per_user_limit INT NULL,
  dealer_only INTEGER(1) NOT NULL DEFAULT 0,
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (code)
) ;

CREATE TABLE IF NOT EXISTS discount_redemptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  discount_id INTEGER NOT NULL,
  order_id INTEGER NOT NULL,
  user_id INTEGER NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TEXT NOT NULL,
  CONSTRAINT fk_redemptions_discount FOREIGN KEY (discount_id) REFERENCES discounts (id) ON DELETE CASCADE,
  CONSTRAINT fk_redemptions_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS shipping_zones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(120) NOT NULL,
  countries VARCHAR(255) NOT NULL DEFAULT 'US',
  states TEXT NULL,
  is_default INTEGER(1) NOT NULL DEFAULT 0,
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
) ;

CREATE TABLE IF NOT EXISTS shipping_methods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  zone_id INTEGER NOT NULL,
  name VARCHAR(120) NOT NULL,
  type TEXT NOT NULL DEFAULT 'flat',
  rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  per_unit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_weight DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  max_weight DECIMAL(8,2) NULL,
  free_over DECIMAL(10,2) NULL,
  est_days_min INT NULL,
  est_days_max INT NULL,
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  CONSTRAINT fk_shipping_methods_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS tax_rates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(120) NOT NULL,
  country CHAR(2) NOT NULL DEFAULT 'US',
  state VARCHAR(60) NOT NULL DEFAULT '',
  rate DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
) ;

CREATE TABLE IF NOT EXISTS reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  user_id INTEGER NULL,
  name VARCHAR(120) NOT NULL,
  rating INTEGER NOT NULL DEFAULT 5,
  title VARCHAR(190) NOT NULL DEFAULT '',
  body TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  verified_purchase INTEGER(1) NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS pages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(190) NOT NULL,
  content TEXT NULL,
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(300) NOT NULL DEFAULT '',
  is_active INTEGER(1) NOT NULL DEFAULT 1,
  show_in_footer INTEGER(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (slug)
) ;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  subject VARCHAR(190) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'new',
  created_at TEXT NOT NULL
) ;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(190) NOT NULL,
  status TEXT NOT NULL DEFAULT 'subscribed',
  created_at TEXT NOT NULL,
  UNIQUE (email)
) ;

CREATE TABLE IF NOT EXISTS product_waitlist (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  email VARCHAR(190) NOT NULL,
  created_at TEXT NOT NULL,
  notified_at TEXT NULL,
  UNIQUE (product_id, email),
  CONSTRAINT fk_waitlist_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  delta INT NOT NULL,
  stock_after INT NOT NULL DEFAULT 0,
  reason VARCHAR(60) NOT NULL,
  order_id INTEGER NULL,
  admin_id INTEGER NULL,
  note VARCHAR(255) NOT NULL DEFAULT '',
  created_at TEXT NOT NULL
) ;

CREATE TABLE IF NOT EXISTS email_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NULL,
  status TEXT NOT NULL DEFAULT 'queued',
  error TEXT NULL,
  created_at TEXT NOT NULL,
  sent_at TEXT NULL
) ;

CREATE TABLE IF NOT EXISTS paypal_webhook_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id VARCHAR(64) NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  resource_type VARCHAR(60) NOT NULL DEFAULT '',
  payload TEXT NULL,
  status TEXT NOT NULL DEFAULT 'received',
  error TEXT NULL,
  created_at TEXT NOT NULL,
  processed_at TEXT NULL,
  UNIQUE (event_id)
) ;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  success INTEGER(1) NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
) ;

CREATE TABLE IF NOT EXISTS admin_activity (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) NOT NULL DEFAULT '',
  entity_id VARCHAR(60) NOT NULL DEFAULT '',
  meta VARCHAR(255) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at TEXT NOT NULL
) ;

