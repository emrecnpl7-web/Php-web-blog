<?php
/**
 * Yeni Yazı Ekleme
 */
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$pageTitle = 'Yeni Yazı Ekle';
$errors = [];
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

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

        // Slug oluştur
        $slug = createSlug($title);
        $baseSlug = $slug;
        $counter = 1;
        while (true) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetchColumn() == 0) break;
            $slug = $baseSlug . '-' . $counter++;
        }

        // Görsel yükleme
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uploadImage($_FILES['image']);
            if (!$image) {
                $errors[] = 'Görsel yüklenemedi. (JPG, PNG, GIF, WebP — max 5MB)';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, title, slug, content, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $categoryId, $title, $slug, $content, $image, $status]);

            setFlash('success', 'Yazınız başarıyla yayınlandı!');
            header('Location: post.php?slug=' . $slug);
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

    <section class="section" style="padding-top: 100px;">
        <div class="container container-md">
            <div class="section-header animate-fadeInUp">
                <h1 class="section-title">✏️ Yeni Yazı Ekle</h1>
                <p class="section-subtitle">Düşüncelerinizi dünyayla paylaşın</p>
            </div>

            <div class="card animate-fadeInUp" style="animation-delay: 0.2s;">
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
                                   placeholder="Yazınızın başlığı..." value="<?= e($title ?? '') ?>" 
                                   maxlength="255" required autofocus>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label" for="category_id">📂 Kategori</label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">Kategori seçin...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($categoryId ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                            <?= $cat['icon'] ?> <?= e($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="status">📊 Durum</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="published">✅ Yayınla</option>
                                    <option value="draft">📝 Taslak</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="image">🖼️ Kapak Görseli</label>
                            <input type="file" id="image" name="image" class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this, 'imagePreview')">
                            <div class="form-hint">JPG, PNG, GIF veya WebP — Maksimum 5MB</div>
                            <img id="imagePreview" style="display:none; margin-top:12px; max-height:200px; border-radius: var(--radius-md);">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="content">📝 İçerik</label>
                            <textarea id="content" name="content" class="form-control" rows="12" 
                                      placeholder="Yazınızın içeriğini buraya girin..." required><?= e($content ?? '') ?></textarea>
                        </div>

                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <a href="index.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary btn-lg">🚀 Yayınla</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
