<?php
/**
 * Yazı Düzenleme
 */
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$postId = (int)($_GET['id'] ?? 0);
if ($postId <= 0) {
    header('Location: index.php');
    exit;
}

// Yazıyı getir
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    setFlash('error', 'Yazı bulunamadı!');
    header('Location: index.php');
    exit;
}

// Yetki kontrolü
if ($post['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    setFlash('error', 'Bu yazıyı düzenleme yetkiniz yok!');
    header('Location: index.php');
    exit;
}

$pageTitle = 'Yazı Düzenle';
$errors = [];
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Silme
if (isset($_GET['delete']) && verifyCsrf()) {
    // Görseli sil
    if ($post['image'] && file_exists('uploads/' . $post['image'])) {
        unlink('uploads/' . $post['image']);
    }
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);
    setFlash('success', 'Yazı silindi!');
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik doğrulaması başarısız!';
    } else {
        $title      = trim($_POST['title'] ?? '');
        $content    = trim($_POST['content'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $status     = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

        if (empty($title)) $errors[] = 'Başlık zorunludur.';
        if (empty($content)) $errors[] = 'İçerik zorunludur.';
        if ($categoryId <= 0) $errors[] = 'Kategori seçin.';

        // Görsel
        $image = $post['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $newImage = uploadImage($_FILES['image']);
            if ($newImage) {
                // Eski görseli sil
                if ($image && file_exists('uploads/' . $image)) {
                    unlink('uploads/' . $image);
                }
                $image = $newImage;
            } else {
                $errors[] = 'Görsel yüklenemedi.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ?, image = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $content, $categoryId, $image, $status, $postId]);

            setFlash('success', 'Yazınız güncellendi!');
            header('Location: post.php?slug=' . $post['slug']);
            exit;
        }
    }
} else {
    $title      = $post['title'];
    $content    = $post['content'];
    $categoryId = $post['category_id'];
    $status     = $post['status'];
}

require_once 'includes/header.php';
?>

    <section class="section" style="padding-top: 100px;">
        <div class="container container-md">
            <div class="section-header animate-fadeInUp">
                <h1 class="section-title">✏️ Yazı Düzenle</h1>
                <p class="section-subtitle">"<?= e($post['title']) ?>" yazısını düzenliyorsunuz</p>
            </div>

            <div class="card animate-fadeInUp">
                <div class="card-body" style="padding: 40px;">

                    <?php if ($errors): ?>
                        <div class="flash-message flash-error" style="position:static; margin-bottom:20px; max-width:100%;">
                            <span class="flash-icon">❌</span>
                            <span class="flash-text">
                                <?php foreach ($errors as $err): echo e($err) . '<br>'; endforeach; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="form-group">
                            <label class="form-label" for="title">📌 Başlık</label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?= e($title) ?>" maxlength="255" required>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label" for="category_id">📂 Kategori</label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                            <?= $cat['icon'] ?> <?= e($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="status">📊 Durum</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>✅ Yayında</option>
                                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>📝 Taslak</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="image">🖼️ Kapak Görseli</label>
                            <?php if ($post['image']): ?>
                                <div style="margin-bottom: 12px;">
                                    <img src="uploads/<?= e($post['image']) ?>" style="max-height:150px; border-radius: var(--radius-md);">
                                    <p class="form-hint">Yeni görsel yüklerseniz mevcut görsel değiştirilir.</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="image" name="image" class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this, 'imagePreview')">
                            <img id="imagePreview" style="display:none; margin-top:12px; max-height:200px; border-radius: var(--radius-md);">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="content">📝 İçerik</label>
                            <textarea id="content" name="content" class="form-control" rows="12" required><?= e($content) ?></textarea>
                        </div>

                        <div style="display: flex; gap: 12px; justify-content: space-between; flex-wrap: wrap;">
                            <a href="edit_post.php?id=<?= $postId ?>&delete=1&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                               class="btn btn-danger" onclick="return confirmDelete('Bu yazıyı silmek istediğinize emin misiniz?')">
                                🗑️ Yazıyı Sil
                            </a>
                            <div style="display: flex; gap: 12px;">
                                <a href="post.php?slug=<?= e($post['slug']) ?>" class="btn btn-secondary">İptal</a>
                                <button type="submit" class="btn btn-primary btn-lg">💾 Güncelle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
