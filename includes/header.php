<?php
/**
 * Header — Üst Menü ve Navbar
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= SITE_DESC ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✍️</text></svg>">
</head>
<body>
    <!-- Arka Plan Efektleri -->
    <div class="bg-grid"></div>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>

    <!-- Flash Mesaj -->
    <?= showFlash() ?>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <a href="index.php" class="nav-brand">
            <div class="brand-icon">✍️</div>
            <span><?= SITE_NAME ?></span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 Ana Sayfa</a></li>
            <li><a href="category.php" class="<?= $currentPage === 'category.php' ? 'active' : '' ?>">📂 Kategoriler</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="new_post.php" class="<?= $currentPage === 'new_post.php' ? 'active' : '' ?>">✏️ Yazı Ekle</a></li>
                <li><a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">👤 Profilim</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/index.php" class="<?= strpos($currentPage, 'admin') !== false ? 'active' : '' ?>">⚙️ Admin</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-auth">
            <?php if (isLoggedIn()): ?>
                <a href="profile.php">
                    <img src="<?= getAvatar($_SESSION['user_email'] ?? '') ?>" alt="Avatar" class="nav-avatar">
                </a>
                <a href="logout.php" class="btn btn-secondary btn-sm">Çıkış</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-secondary btn-sm">Giriş</a>
                <a href="register.php" class="btn btn-primary btn-sm">Kayıt Ol</a>
            <?php endif; ?>
        </div>

        <div class="hamburger" id="hamburger" onclick="this.classList.toggle('active'); document.getElementById('navLinks').classList.toggle('open');">
            <span></span><span></span><span></span>
        </div>
    </nav>
