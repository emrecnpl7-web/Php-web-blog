<?php
/**
 * Admin — Kullanıcı Yönetimi
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$pageTitle = 'Kullanıcı Yönetimi';

// Rol değiştir
if (isset($_GET['toggle_role']) && isset($_GET['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $userId = (int)$_GET['toggle_role'];
        if ($userId != $_SESSION['user_id']) { // Kendini değiştiremesin
            $user = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $user->execute([$userId]);
            $user = $user->fetch();
            if ($user) {
                $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
                $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $userId]);
                setFlash('success', 'Kullanıcı rolü güncellendi!');
            }
        }
        header('Location: users.php');
        exit;
    }
}

// Silme
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $delId = (int)$_GET['delete'];
        if ($delId != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$delId]);
            setFlash('success', 'Kullanıcı silindi!');
        } else {
            setFlash('error', 'Kendi hesabınızı silemezsiniz!');
        }
        header('Location: users.php');
        exit;
    }
}

// Tüm kullanıcılar
$users = $pdo->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
    (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();

$currentAdminPage = 'users';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi — <?= SITE_NAME ?></title>
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
                <a href="users.php" class="active"><span class="nav-icon">👥</span> Kullanıcılar</a>
                <a href="comments.php"><span class="nav-icon">💬</span> Yorumlar</a>
                <hr style="border-color: var(--border-glass); margin: 16px 0;">
                <a href="../index.php"><span class="nav-icon">🏠</span> Siteye Dön</a>
                <a href="../logout.php"><span class="nav-icon">🚪</span> Çıkış Yap</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>👥 Kullanıcı Yönetimi</h1>
                <span style="color: var(--text-muted);"><?= count($users) ?> kullanıcı</span>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Avatar</th>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Yazı</th>
                        <th>Yorum</th>
                        <th>Kayıt</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><img src="<?= getAvatar($u['email'], 36) ?>" alt="" style="width:36px; height:36px; border-radius:50%;"></td>
                            <td><strong><?= e($u['username']) ?></strong></td>
                            <td style="font-size: 0.85rem;"><?= e($u['email']) ?></td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? '👑 Admin' : '👤 Kullanıcı' ?></span></td>
                            <td><?= $u['post_count'] ?></td>
                            <td><?= $u['comment_count'] ?></td>
                            <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <div class="actions">
                                        <a href="users.php?toggle_role=<?= $u['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                           class="btn btn-secondary btn-sm" title="Rol Değiştir">🔄</a>
                                        <a href="users.php?delete=<?= $u['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                           class="btn btn-danger btn-sm" onclick="return confirmDelete('Bu kullanıcıyı silmek istediğinize emin misiniz? Tüm yazıları ve yorumları da silinecek!')">🗑️</a>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">— Siz —</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
