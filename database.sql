-- Huy CNC Nha Trang - Database Schema
-- Import: mysql -u root -p < database.sql
-- Admin password: tao bang PHP: echo password_hash('your_password', PASSWORD_BCRYPT);

CREATE DATABASE IF NOT EXISTS `huycnc_nhatrang_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `huycnc_nhatrang_db`;

-- --------------------------------------------------------
-- Admin accounts
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','editor','viewer') NOT NULL DEFAULT 'viewer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`),
  KEY `idx_admin_status_role` (`status`, `role`),
  KEY `idx_admin_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_unit` varchar(50) DEFAULT 'VNĐ/sản phẩm',
  `description` text DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `thickness` varchar(50) DEFAULT NULL,
  `technology` varchar(100) DEFAULT NULL,
  `precision_level` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT 'assets/images/placeholder.jpg',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_featured` (`is_featured`),
  KEY `idx_products_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`, `sort_order`),
  CONSTRAINT `fk_product_images_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Portfolio projects
CREATE TABLE IF NOT EXISTS `portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `client` varchar(150) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON array of additional images',
  `year` year DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_portfolio_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Vouchers
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `usage_limit` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vouchers_code` (`code`),
  KEY `idx_vouchers_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Quote requests
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_code` varchar(80) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `material` varchar(100) NOT NULL,
  `thickness` float NOT NULL DEFAULT 0,
  `dimensions` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `estimated_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cad_file_path` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('new','processing','replied','done','cancelled') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quotes_code` (`quote_code`),
  KEY `idx_quotes_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Contact form submissions
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `phone_email` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_contacts_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Blog / technical articles
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'general',
  `excerpt` varchar(500) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `author` varchar(120) DEFAULT 'Huy CNC Nha Trang',
  `views` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_posts_slug` (`slug`),
  KEY `idx_blog_posts_status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Materials catalog
CREATE TABLE IF NOT EXISTS `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price_per_unit` decimal(15,2) DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'kg',
  `min_thickness` float DEFAULT NULL,
  `max_thickness` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample admin account (password: Admin@2025! - change immediately!)
-- INSERT INTO `admin` (`fullname`,`email`,`password_hash`,`role`) VALUES
-- ('Admin NTrang','admin@huycnc-nhatrang.com','$2y$12$CHANGE_THIS_HASH','super_admin');

-- Sample materials
INSERT IGNORE INTO `materials` (`name`,`category`,`unit`,`min_thickness`,`max_thickness`) VALUES
('Nhôm (Aluminum)','kim_loai','mm',0.5,30),
('Inox 304','kim_loai','mm',0.5,20),
('Inox 316','kim_loai','mm',0.5,20),
('Thép carbon','kim_loai','mm',0.5,30),
('Đồng (Brass)','kim_loai','mm',0.5,20),
('Mica trong','nhua','mm',1,25),
('Mica màu','nhua','mm',1,20),
('Gỗ MDF','go','mm',3,30),
('Gỗ ply','go','mm',3,25),
('Nhựa POM','nhua','mm',2,50),
('Nhựa PP','nhua','mm',1,20),
('Foam/Xốp','khac','mm',5,100);

-- Sample admin account (Password: Admin@123456)
INSERT IGNORE INTO `admin` (`id`, `fullname`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'Admin Nha Trang', 'admin@huycnc-nhatrang.com', '$2y$10$vEcfRrvlKk0N12P6qWdYQehU29Wqf3Z70gI.FjK.hQoE6.Ekm5w8K', 'super_admin', 'active');

-- Sample products seeds
INSERT IGNORE INTO `products` (`id`, `name`, `slug`, `category`, `price`, `material`, `thickness`, `technology`, `precision_level`, `image`, `is_featured`, `status`) VALUES
(1, 'Biển Chữ Nổi Inox 304 Cao Cấp', 'catalogue-sp01', 'bienqc', 0.00, 'Inox 304 vàng gương', '1.2mm', 'Laser Fiber & Uốn Chân CNC', '±0.01mm', 'uploads/products/catalogue-sp01-card.webp', 1, 'active'),
(2, 'Gia Công CNC Nhôm 6061 Chính Xác', 'catalogue-sp02', 'cnc', 0.00, 'Nhôm A6061-T6', '15.0mm', 'Phay CNC 5 Trục', '±0.005mm', 'uploads/products/catalogue-sp02-card.webp', 1, 'active'),
(3, 'Cắt Laser Fiber Inox Tấm Theo Yêu Cầu', 'catalogue-sp03', 'laser', 0.00, 'Inox tấm 304/316', '2.0mm', 'Laser Fiber Raycus 3000W', '±0.02mm', 'uploads/products/catalogue-sp03-card.webp', 1, 'active'),
(4, 'Kệ Mica Trưng Bày & Menu Để Bàn', 'catalogue-sp04', 'mica', 0.00, 'Mica Đài Loan Acrylic', '3.0mm', 'Laser CO2 & Uốn Nhiệt', '±0.05mm', 'uploads/products/catalogue-sp04-card.webp', 1, 'active'),
(5, 'Khắc Laser Logo Công Ty & Quà Tặng', 'catalogue-sp05', 'quatang', 0.00, 'Kim loại / Pha lê', '1.0mm', 'Khắc Laser Fiber MOPA', '±0.01mm', 'uploads/products/catalogue-sp05-card.webp', 1, 'active'),
(6, 'Biển Hộp Đèn LED Mica Ngoài Trời', 'catalogue-sp06', 'bienqc', 0.00, 'Mica & Nhôm định hình', '3.0mm', 'Hút nổi & LED Chống Nước', '±0.1mm', 'uploads/products/catalogue-sp06-card.webp', 1, 'active');

-- Sample blog seeds
INSERT IGNORE INTO `blog_posts` (`id`, `title`, `slug`, `category`, `excerpt`, `content`, `thumbnail`, `author`, `status`, `published_at`) VALUES
(1, 'So Sánh Inox 304 và Inox 316 Trong Môi Trường Gió Biển Nha Trang', 'so-sanh-inox-304-va-316-moi-truong-bien', 'Vật Liệu', 'Khí hậu biển Nha Trang với độ ẩm cao và nồng độ muối lớn là thử thách khắc nghiệt đối với kim loại. Tìm hiểu tại sao Inox 316 là giải pháp tối ưu cho biển hiệu và phụ kiện ven biển.', '<p>Nội dung chi tiết về so sánh vật liệu chống ăn mòn trong môi trường biển.</p>', 'uploads/products/catalogue-sp01-display.webp', 'Kỹ Sư Trưởng Huy CNC', 'published', NOW()),
(2, 'Kỹ Thuật Cắt Laser Fiber: Chọn Khí Nitơ (N2) Hay Oxy (O2)?', 'ky-thuat-cat-laser-khi-tro-n2-vs-o2', 'Laser', 'Phân tích sự khác biệt về chất lượng vết cắt, tốc độ và chi phí gia công khi sử dụng khí nitơ áp cao so với oxy trên inox và thép carbon tấm.', '<p>Hướng dẫn chọn khí cắt tối ưu cho từng loại vật liệu kim loại tấm.</p>', 'uploads/products/catalogue-sp03-card.webp', 'Huy CNC', 'published', NOW()),
(3, 'Quy Trình Gia Công Chi Tiết Nhôm CNC 5 Trục Từ Bản Vẽ CAD', 'quy-trinh-phay-cnc-nhom-6061', 'CNC', 'Các bước tối ưu đường dao CAM, kiểm soát độ giãn nở nhiệt của nhôm và bí quyết giữ dung sai khắt khe trong gia công cơ khí chính xác.', '<p>Quy trình lập trình CAM và gá đặt phôi nhôm 6061 chuẩn xác.</p>', 'uploads/products/catalogue-sp02-card.webp', 'Trần Minh Kỹ', 'published', NOW());

