<?php
/**
 * Admin — Yazı Yönetimi
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$pageTitle = 'Yazı Yönetimi';

// Silme işlemi
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $delId = (int)$_GET['delete'];
        $post = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
        $post->execute([$delId]);
        $post = $post->fetch();
        if ($post && $post['image'] && file_exists('../uploads/' . $post['image'])) {
            unlink('../uploads/' . $post['image']);
        }
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$delId]);
        setFlash('success', 'Yazı silindi!');
        header('Location: posts.php');
        exit;
    }
}

// Tüm yazılar
$posts = $pdo->query("
    SELECT p.*, u.username, c.name as category_name, c.color as category_color,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();

$currentAdminPage = 'posts';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazı Yönetimi — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?= showFlash() ?>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">⚙️ Admin Panel</div>
            <nav class="admin-nav">
                <a href="index.php"><span class="nav-icon">📊</span> Dashboard</a>
                <a href="posts.php" class="active"><span class="nav-icon">📝</span> Yazılar</a>
                <a href="categories.php"><span class="nav-icon">📂</span> Kategoriler</a>
                <a href="users.php"><span class="nav-icon">👥</span> Kullanıcılar</a>
                <a href="comments.php"><span class="nav-icon">💬</span> Yorumlar</a>
                <hr style="border-color: var(--border-glass); margin: 16px 0;">
                <a href="../index.php"><span class="nav-icon">🏠</span> Siteye Dön</a>
                <a href="../logout.php"><span class="nav-icon">🚪</span> Çıkış Yap</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>📝 Yazı Yönetimi</h1>
                <a href="../new_post.php" class="btn btn-primary">✏️ Yeni Yazı</a>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Başlık</th>
                        <th>Yazar</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>👁️</th>
                        <th>💬</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><a href="../post.php?slug=<?= e($p['slug']) ?>"><?= e(truncate($p['title'], 40)) ?></a></td>
                            <td><?= e($p['username']) ?></td>
                            <td><span class="badge" style="background: <?= e($p['category_color']) ?>20; color: <?= e($p['category_color']) ?>;"><?= e($p['category_name']) ?></span></td>
                            <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Yayında' : 'Taslak' ?></span></td>
                            <td><?= $p['views'] ?></td>
                            <td><?= $p['comment_count'] ?></td>
                            <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="../edit_post.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                                    <a href="posts.php?delete=<?= $p['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                       class="btn btn-danger btn-sm" onclick="return confirmDelete()">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-muted);">Henüz yazı yok</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
