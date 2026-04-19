<?php
/**
 * Admin — Kategori Yönetimi
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$pageTitle = 'Kategori Yönetimi';
$errors = [];
$editCat = null;

// Silme
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $delId = (int)$_GET['delete'];
        // Yazı var mı kontrol et
        $hasPost = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ?");
        $hasPost->execute([$delId]);
        if ($hasPost->fetchColumn() > 0) {
            setFlash('error', 'Bu kategoride yazı var! Önce yazıları silmeniz gerekir.');
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$delId]);
            setFlash('success', 'Kategori silindi!');
        }
        header('Location: categories.php');
        exit;
    }
}

// Düzenleme moduna geç
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}

// Ekle / Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $name  = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#6c5ce7');
    $icon  = trim($_POST['icon'] ?? '📁');
    $catId = (int)($_POST['cat_id'] ?? 0);

    if (empty($name)) {
        $errors[] = 'Kategori adı zorunludur.';
    }

    $slug = createSlug($name);

    if (empty($errors)) {
        if ($catId > 0) {
            // Güncelle
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, color = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $color, $icon, $catId]);
            setFlash('success', 'Kategori güncellendi!');
        } else {
            // Ekle
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, color, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $color, $icon]);
            setFlash('success', 'Kategori eklendi!');
        }
        header('Location: categories.php');
        exit;
    }
}

// Tüm kategoriler
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();

$currentAdminPage = 'categories';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi — <?= SITE_NAME ?></title>
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
                <a href="categories.php" class="active"><span class="nav-icon">📂</span> Kategoriler</a>
                <a href="users.php"><span class="nav-icon">👥</span> Kullanıcılar</a>
                <a href="comments.php"><span class="nav-icon">💬</span> Yorumlar</a>
                <hr style="border-color: var(--border-glass); margin: 16px 0;">
                <a href="../index.php"><span class="nav-icon">🏠</span> Siteye Dön</a>
                <a href="../logout.php"><span class="nav-icon">🚪</span> Çıkış Yap</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>📂 Kategori Yönetimi</h1>
            </div>

            <!-- Form -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-body" style="padding: 28px;">
                    <h3 style="margin-bottom: 20px; font-weight: 700;">
                        <?= $editCat ? '✏️ Kategori Düzenle' : '➕ Yeni Kategori' ?>
                    </h3>

                    <?php if ($errors): ?>
                        <div class="flash-message flash-error" style="position:static; margin-bottom:16px; max-width:100%;">
                            <span class="flash-icon">❌</span>
                            <span class="flash-text"><?= e(implode(', ', $errors)) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?? 0 ?>">

                        <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
                            <div class="form-group" style="flex: 2; margin-bottom: 0;">
                                <label class="form-label">Kategori Adı</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= e($editCat['name'] ?? '') ?>" placeholder="Kategori adı..." required>
                            </div>
                            <div class="form-group" style="flex: 0.5; margin-bottom: 0;">
                                <label class="form-label">Renk</label>
                                <input type="color" name="color" class="form-control" style="padding:8px; height:48px;" 
                                       value="<?= e($editCat['color'] ?? '#6c5ce7') ?>">
                            </div>
                            <div class="form-group" style="flex: 0.5; margin-bottom: 0;">
                                <label class="form-label">İkon</label>
                                <input type="text" name="icon" class="form-control" 
                                       value="<?= e($editCat['icon'] ?? '📁') ?>" placeholder="📁">
                            </div>
                            <button type="submit" class="btn btn-primary" style="height: 48px;">
                                <?= $editCat ? '💾 Güncelle' : '➕ Ekle' ?>
                            </button>
                            <?php if ($editCat): ?>
                                <a href="categories.php" class="btn btn-secondary" style="height: 48px;">İptal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste -->
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>İkon</th>
                        <th>Ad</th>
                        <th>Slug</th>
                        <th>Renk</th>
                        <th>Yazı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['id'] ?></td>
                            <td style="font-size: 1.5rem;"><?= $cat['icon'] ?></td>
                            <td><strong><?= e($cat['name']) ?></strong></td>
                            <td style="font-family: var(--font-mono); font-size: 0.8rem;"><?= e($cat['slug']) ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:20px; height:20px; border-radius:4px; background:<?= e($cat['color']) ?>;"></div>
                                    <span style="font-family:var(--font-mono); font-size:0.8rem;"><?= e($cat['color']) ?></span>
                                </div>
                            </td>
                            <td><?= $cat['post_count'] ?></td>
                            <td>
                                <div class="actions">
                                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                                    <a href="categories.php?delete=<?= $cat['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                       class="btn btn-danger btn-sm" onclick="return confirmDelete('Bu kategoriyi silmek istediğinize emin misiniz?')">🗑️</a>
                                </div>
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
