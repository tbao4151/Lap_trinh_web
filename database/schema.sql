-- CÁ'S HOA | Đồ án Lập trình Web - TDTU | Yêu cầu 2
-- MySQL 8.0+, mã UTF-8; mở bằng phpMyAdmin hoặc mysql CLI.
CREATE DATABASE IF NOT EXISTS cas_hoa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cas_hoa;
CREATE TABLE IF NOT EXISTS roles (
  role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(role_id),
  INDEX idx_users_phone(phone)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS customers (
  customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(150),
  address VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  INDEX idx_customers_phone(phone)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) UNIQUE,
  status VARCHAR(30) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS tones (
  tone_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  hex_code VARCHAR(10),
  status VARCHAR(30) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS occasions (
  occasion_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS products (
  product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED,
  tone_id INT UNSIGNED,
  occasion_id INT UNSIGNED,
  sku VARCHAR(50) NOT NULL UNIQUE,
  slug VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(180) NOT NULL,
  product_type VARCHAR(30) NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  description TEXT,
  composition TEXT,
  image VARCHAR(255),
  featured TINYINT(1) NOT NULL DEFAULT 0,
  today TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  FOREIGN KEY (category_id) REFERENCES categories(category_id),
  FOREIGN KEY (tone_id) REFERENCES tones(tone_id),
  FOREIGN KEY (occasion_id) REFERENCES occasions(occasion_id),
  INDEX idx_products_catalog(status,product_type,price),
  CONSTRAINT chk_product_price CHECK(price >= 0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS wrapping_colors (
  color_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  hex_code VARCHAR(10),
  status VARCHAR(30) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS product_wrapping (
  product_id INT UNSIGNED NOT NULL,
  color_id INT UNSIGNED NOT NULL,
  PRIMARY KEY(product_id,color_id),
  FOREIGN KEY(product_id) REFERENCES products(product_id),
  FOREIGN KEY(color_id) REFERENCES wrapping_colors(color_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS orders (
  order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  recipient_name VARCHAR(120) NOT NULL,
  recipient_phone VARCHAR(20) NOT NULL,
  delivery_method VARCHAR(30) NOT NULL,
  delivery_address VARCHAR(255),
  receive_date DATE NOT NULL,
  receive_time VARCHAR(50),
  payment_method VARCHAR(30) NOT NULL DEFAULT 'cod',
  payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid',
  order_status VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation',
  card_message TEXT,
  order_note TEXT,
  subtotal DECIMAL(12,2) NOT NULL,
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
  INDEX idx_orders_recipient_phone(recipient_phone),
  INDEX idx_orders_created(created_at),
  CONSTRAINT chk_order_money CHECK(subtotal >= 0 AND total_amount >= 0 AND shipping_fee >= 0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_items (
  order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id),
  FOREIGN KEY (product_id) REFERENCES products(product_id),
  CONSTRAINT chk_order_item_qty CHECK(quantity > 0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS deliveries (
  delivery_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  delivery_status VARCHAR(40) NOT NULL DEFAULT 'not_delivered',
  scheduled_at DATETIME,
  fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  note VARCHAR(255),
  FOREIGN KEY (order_id) REFERENCES orders(order_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS refunds (
  refund_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  reason VARCHAR(255),
  status VARCHAR(30) NOT NULL DEFAULT 'requested',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inventory_items (
  item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_type VARCHAR(30) NOT NULL,
  name VARCHAR(150) NOT NULL,
  unit VARCHAR(30) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
  min_quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
  unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inventory_transactions (
  transaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  transaction_type VARCHAR(20) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS news (
  news_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  thumbnail VARCHAR(255),
  summary VARCHAR(500),
  content LONGTEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  published_at DATETIME,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS contacts (
  contact_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  subject VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB;
