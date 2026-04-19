<?php
/**
 * Admin — Yorum Yönetimi
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$pageTitle = 'Yorum Yönetimi';

// Silme
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $delId = (int)$_GET['delete'];
        $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$delId]);
        setFlash('success', 'Yorum silindi!');
        header('Location: comments.php');
        exit;
    }
}

// Tüm yorumlar
$comments = $pdo->query("
    SELECT cm.*, u.username, u.email as user_email, p.title as post_title, p.slug as post_slug
    FROM comments cm
    JOIN users u ON cm.user_id = u.id
    JOIN posts p ON cm.post_id = p.id
    ORDER BY cm.created_at DESC
")->fetchAll();

$currentAdminPage = 'comments';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorum Yönetimi — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?= showFlash() ?>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">⚙️ Admin Panel</div>
            <nav class="admin-nav">
                <a href="index.php"><span class="nav-icon">📊</span> Dashboard</a>
                <a href="posts.php"><span class="nav-icon">📝</span> Yazılar</a>
                <a href="categories.php"><span class="nav-icon">📂</span> Kategoriler</a>
                <a href="users.php"><span class="nav-icon">👥</span> Kullanıcılar</a>
                <a href="comments.php" class="active"><span class="nav-icon">💬</span> Yorumlar</a>
                <hr style="border-color: var(--border-glass); margin: 16px 0;">
                <a href="../index.php"><span class="nav-icon">🏠</span> Siteye Dön</a>
                <a href="../logout.php"><span class="nav-icon">🚪</span> Çıkış Yap</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>💬 Yorum Yönetimi</h1>
                <span style="color: var(--text-muted);"><?= count($comments) ?> yorum</span>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kullanıcı</th>
                        <th>Yorum</th>
                        <th>Yazı</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $cm): ?>
                        <tr>
                            <td><?= $cm['id'] ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <img src="<?= getAvatar($cm['user_email'], 28) ?>" alt="" style="width:28px; height:28px; border-radius:50%;">
                                    <strong><?= e($cm['username']) ?></strong>
                                </div>
                            </td>
                            <td style="max-width: 300px;">
                                <p style="line-height: 1.5;"><?= e(truncate($cm['content'], 80)) ?></p>
                            </td>
                            <td>
                                <a href="../post.php?slug=<?= e($cm['post_slug']) ?>" style="font-size: 0.85rem;">
                                    <?= e(truncate($cm['post_title'], 30)) ?>
                                </a>
                            </td>
                            <td style="white-space: nowrap;"><?= timeAgo($cm['created_at']) ?></td>
                            <td>
                                <a href="comments.php?delete=<?= $cm['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                   class="btn btn-danger btn-sm" onclick="return confirmDelete('Bu yorumu silmek istediğinize emin misiniz?')">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($comments)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">Henüz yorum yok</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
