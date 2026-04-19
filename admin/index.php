<?php
/**
 * Admin Panel — Dashboard
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$pageTitle = 'Admin Panel';

// İstatistikler
$stats = [
    'posts'      => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'users'      => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'comments'   => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'views'      => $pdo->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn(),
    'published'  => $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn(),
    'drafts'     => $pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn(),
];

// Son 5 yazı
$recentPosts = $pdo->query("
    SELECT p.*, u.username, c.name as category_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

// Son 5 yorum
$recentComments = $pdo->query("
    SELECT cm.*, u.username, p.title as post_title, p.slug as post_slug
    FROM comments cm 
    JOIN users u ON cm.user_id = u.id 
    JOIN posts p ON cm.post_id = p.id 
    ORDER BY cm.created_at DESC LIMIT 5
")->fetchAll();

$currentAdminPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚙️</text></svg>">
</head>
<body>
    <?= showFlash() ?>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-logo">⚙️ Admin Panel</div>
            <nav class="admin-nav">
                <a href="index.php" class="<?= $currentAdminPage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="posts.php" class="<?= $currentAdminPage === 'posts' ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span> Yazılar
                </a>
                <a href="categories.php" class="<?= $currentAdminPage === 'categories' ? 'active' : '' ?>">
                    <span class="nav-icon">📂</span> Kategoriler
                </a>
                <a href="users.php" class="<?= $currentAdminPage === 'users' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span> Kullanıcılar
                </a>
                <a href="comments.php" class="<?= $currentAdminPage === 'comments' ? 'active' : '' ?>">
                    <span class="nav-icon">💬</span> Yorumlar
                </a>
                <hr style="border-color: var(--border-glass); margin: 16px 0;">
                <a href="../index.php">
                    <span class="nav-icon">🏠</span> Siteye Dön
                </a>
                <a href="../logout.php">
                    <span class="nav-icon">🚪</span> Çıkış Yap
                </a>
            </nav>
        </aside>

        <!-- İçerik -->
        <main class="admin-content">
            <button class="btn btn-secondary btn-sm" onclick="toggleAdminSidebar()" style="display:none; margin-bottom:16px;" id="sidebarToggle">☰ Menü</button>

            <div class="admin-header">
                <h1>📊 Dashboard</h1>
                <p style="color: var(--text-muted);">Hoş geldiniz, <?= e($_SESSION['user_name']) ?>!</p>
            </div>

            <!-- İstatistik Kartları -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?= $stats['posts'] ?></div>
                    <div class="stat-label">Toplam Yazı</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?= $stats['users'] ?></div>
                    <div class="stat-label">Kullanıcı</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-value"><?= $stats['comments'] ?></div>
                    <div class="stat-label">Yorum</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-value"><?= number_format($stats['views']) ?></div>
                    <div class="stat-label">Görüntülenme</div>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 24px;">
                <!-- Son Yazılar -->
                <div>
                    <h3 style="font-weight: 700; margin-bottom: 16px;">📝 Son Yazılar</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Başlık</th>
                                <th>Yazar</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $rp): ?>
                                <tr>
                                    <td><a href="../post.php?slug=<?= e($rp['slug']) ?>"><?= e(truncate($rp['title'], 40)) ?></a></td>
                                    <td><?= e($rp['username']) ?></td>
                                    <td><span class="badge badge-<?= $rp['status'] ?>"><?= $rp['status'] === 'published' ? 'Yayında' : 'Taslak' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentPosts)): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">Henüz yazı yok</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Son Yorumlar -->
                <div>
                    <h3 style="font-weight: 700; margin-bottom: 16px;">💬 Son Yorumlar</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Yorum</th>
                                <th>Yazı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComments as $rc): ?>
                                <tr>
                                    <td><?= e($rc['username']) ?></td>
                                    <td><?= e(truncate($rc['content'], 30)) ?></td>
                                    <td><a href="../post.php?slug=<?= e($rc['post_slug']) ?>"><?= e(truncate($rc['post_title'], 20)) ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentComments)): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">Henüz yorum yok</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Mobilde sidebar toggle butonunu göster
        if (window.innerWidth <= 768) {
            document.getElementById('sidebarToggle').style.display = 'inline-flex';
        }
    </script>
</body>
</html>
