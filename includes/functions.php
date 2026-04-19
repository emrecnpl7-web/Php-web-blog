<?php
/**
 * Yardımcı Fonksiyonlar
 */

// Kullanıcı giriş yapmış mı?
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Admin mi?
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Giriş zorunlu sayfalar
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Admin zorunlu sayfalar
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit;
    }
}

// CSRF doğrulama
function verifyCsrf(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// Güvenli metin çıktısı
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Slug oluştur
function createSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $tr = ['ş'=>'s','ç'=>'c','ğ'=>'g','ü'=>'u','ö'=>'o','ı'=>'i',
           'Ş'=>'s','Ç'=>'c','Ğ'=>'g','Ü'=>'u','Ö'=>'o','İ'=>'i'];
    $text = strtr($text, $tr);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Zaman farkı (Türkçe)
function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y >= 1) return $diff->y . ' yıl önce';
    if ($diff->m >= 1) return $diff->m . ' ay önce';
    if ($diff->d >= 7) return floor($diff->d / 7) . ' hafta önce';
    if ($diff->d >= 1) return $diff->d . ' gün önce';
    if ($diff->h >= 1) return $diff->h . ' saat önce';
    if ($diff->i >= 1) return $diff->i . ' dakika önce';
    return 'Az önce';
}

// Metin kısalt
function truncate(string $text, int $length = 150): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

// Flash mesaj ayarla
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Flash mesaj göster
function showFlash(): string {
    if (!isset($_SESSION['flash'])) return '';
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $icon = match($flash['type']) {
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        'info'    => 'ℹ️',
        default   => '📌'
    };
    return '<div class="flash-message flash-' . e($flash['type']) . '" id="flashMessage">
        <span class="flash-icon">' . $icon . '</span>
        <span class="flash-text">' . e($flash['message']) . '</span>
        <button class="flash-close" onclick="this.parentElement.remove()">×</button>
    </div>';
}

// Sayfa numaralama
function paginate(PDO $pdo, string $table, string $where = '1=1', int $perPage = 6): array {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE $where");
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
    $offset = ($page - 1) * $perPage;
    return compact('total', 'totalPages', 'page', 'offset', 'perPage');
}

// Görsel yükleme
function uploadImage(array $file, string $dir = 'uploads/'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return null;

    if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB limit

    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $filename);
    return $filename;
}

// Gravatar URL
function getAvatar(string $email, int $size = 80): string {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
}
