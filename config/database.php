<?php
/**
 * Veritabanı Yapılandırması ve Bağlantısı
 * Blog Sistemi — Adıyamanlı Blog
 */

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'adiyamanli_blog');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site ayarları
define('SITE_NAME', 'Adıyamanlı Blog');
define('SITE_URL', 'http://localhost/adiyamanli-blog');
define('SITE_DESC', 'Modern, şık ve kullanıcı dostu blog platformu');

// PDO bağlantısı
try {
    // Önce veritabanını oluştur
    $dsn_initial = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo_init = new PDO($dsn_initial, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo_init = null;

    // Veritabanına bağlan
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    die('<div style="padding:40px;text-align:center;font-family:Inter,sans-serif;color:#ff6b6b;">
        <h2>⚠️ Veritabanı Bağlantı Hatası</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p style="color:#999;">Lütfen MySQL servisinin çalıştığından emin olun.</p>
    </div>');
}

// ─── Tabloları Oluştur ──────────────────────────────────────────────

$pdo->exec("
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT 'default.png',
    `bio` TEXT DEFAULT NULL,
    `role` ENUM('user','admin') DEFAULT 'user',
    `reset_token` VARCHAR(255) DEFAULT NULL,
    `reset_expires` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `color` VARCHAR(7) DEFAULT '#6c5ce7',
    `icon` VARCHAR(50) DEFAULT '📁',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('draft','published') DEFAULT 'published',
    `views` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─── Varsayılan Veriler ─────────────────────────────────────────────

// Admin kullanıcı
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')")
        ->execute(['admin', 'admin@blog.com', $adminPass]);
}

// Varsayılan kategoriler
$stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $categories = [
        ['Teknoloji', 'teknoloji', '#6c5ce7', '💻'],
        ['Yaşam',     'yasam',     '#00cec9', '🌿'],
        ['Seyahat',   'seyahat',   '#fdcb6e', '✈️'],
        ['Yemek',     'yemek',     '#e17055', '🍽️'],
        ['Spor',      'spor',      '#00b894', '⚽'],
        ['Kültür',    'kultur',    '#e84393', '🎭'],
        ['Bilim',     'bilim',     '#0984e3', '🔬'],
        ['Eğitim',    'egitim',    '#a29bfe', '📚'],
    ];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, color, icon) VALUES (?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
}

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
