<?php
/**
 * Yazı Detay Sayfası + Yorumlar
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Yazıyı getir
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email as user_email, u.bio as user_bio,
    c.name as category_name, c.slug as category_slug, c.color as category_color, c.icon as category_icon
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    setFlash('error', 'Yazı bulunamadı!');
    header('Location: index.php');
    exit;
}

// Görüntülenme sayısını artır
$pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);

// Yorumları getir
$comments = $pdo->prepare("
    SELECT cm.*, u.username, u.email as user_email
    FROM comments cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.post_id = ?
    ORDER BY cm.created_at DESC
");
$comments->execute([$post['id']]);
$comments = $comments->fetchAll();

// Yorum ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (verifyCsrf()) {
        $content = trim($_POST['comment'] ?? '');
        if (!empty($content) && mb_strlen($content) <= 1000) {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post['id'], $_SESSION['user_id'], $content]);
            setFlash('success', 'Yorumunuz eklendi!');
            header('Location: post.php?slug=' . $slug . '#comments');
            exit;
        } else {
            setFlash('error', 'Yorum boş olamaz ve 1000 karakteri geçemez.');
        }
    }
}

// İlgili yazılar
$relatedPosts = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.color as category_color, u.username, u.email as user_email
    FROM posts p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.user_id = u.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'published'
    ORDER BY p.created_at DESC LIMIT 3
");
$relatedPosts->execute([$post['category_id'], $post['id']]);
$relatedPosts = $relatedPosts->fetchAll();

$pageTitle = $post['title'];
require_once 'includes/header.php';
?>

    <div class="container">
        <div class="post-detail">
            <!-- Başlık -->
            <div class="post-detail-header animate-fadeInUp">
                <a href="category.php?slug=<?= e($post['category_slug']) ?>" class="post-category" 
                   style="background: <?= e($post['category_color']) ?>;">
                    <?= $post['category_icon'] ?> <?= e($post['category_name']) ?>
                </a>
                <h1><?= e($post['title']) ?></h1>
                <div class="post-detail-meta">
                    <div class="meta-author">
                        <img src="<?= getAvatar($post['user_email']) ?>" alt="">
                        <div>
                            <strong><?= e($post['username']) ?></strong>
                        </div>
                    </div>
                    <span class="meta-sep">•</span>
                    <span>📅 <?= date('d M Y', strtotime($post['created_at'])) ?></span>
                    <span class="meta-sep">•</span>
                    <span>👁️ <?= $post['views'] + 1 ?> görüntülenme</span>
                    <span class="meta-sep">•</span>
                    <span>💬 <?= count($comments) ?> yorum</span>
                </div>
            </div>

            <!-- Kapak Görseli -->
            <?php if ($post['image']): ?>
                <img src="uploads/<?= e($post['image']) ?>" alt="<?= e($post['title']) ?>" class="post-detail-image animate-fadeIn">
            <?php endif; ?>

            <!-- İçerik -->
            <div class="post-detail-content animate-fadeInUp">
                <?= nl2br(e($post['content'])) ?>
            </div>

            <!-- Düzenle / Sil -->
            <?php if (isLoggedIn() && ($_SESSION['user_id'] == $post['user_id'] || isAdmin())): ?>
                <div style="display: flex; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-glass);">
                    <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-secondary">✏️ Düzenle</a>
                </div>
            <?php endif; ?>

            <!-- Yorumlar -->
            <section class="comments-section" id="comments">
                <h3>💬 Yorumlar (<?= count($comments) ?>)</h3>

                <!-- Yorum Formu -->
                <?php if (isLoggedIn()): ?>
                    <form method="POST" action="" class="comment-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <textarea name="comment" id="commentText" class="form-control" 
                                      placeholder="Düşüncelerinizi yazın..." maxlength="1000" required></textarea>
                            <div class="form-hint" id="charCounter">0 / 1000</div>
                        </div>
                        <button type="submit" class="btn btn-primary">📤 Yorum Gönder</button>
                    </form>
                    <script>
                        initCharCounter('commentText', 'charCounter', 1000);
                    </script>
                <?php else: ?>
                    <div class="card" style="padding: 28px; text-align: center; margin-bottom: 24px;">
                        <p style="color: var(--text-secondary); margin-bottom: 16px;">Yorum yapabilmek için giriş yapmalısınız.</p>
                        <a href="login.php?redirect=<?= urlencode('post.php?slug=' . $slug) ?>" class="btn btn-primary">🔐 Giriş Yap</a>
                    </div>
                <?php endif; ?>

                <!-- Yorum Listesi -->
                <?php if (empty($comments)): ?>
                    <div class="empty-state" style="padding: 40px;">
                        <div class="empty-icon">💭</div>
                        <h3>Henüz yorum yok</h3>
                        <p>İlk yorumu siz yapın!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $cm): ?>
                        <div class="comment">
                            <img src="<?= getAvatar($cm['user_email']) ?>" alt="" class="comment-avatar">
                            <div class="comment-body">
                                <div class="comment-header">
                                    <span class="comment-author"><?= e($cm['username']) ?></span>
                                    <span class="comment-date"><?= timeAgo($cm['created_at']) ?></span>
                                </div>
                                <p class="comment-text"><?= nl2br(e($cm['content'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- İlgili Yazılar -->
            <?php if (!empty($relatedPosts)): ?>
                <section class="section" style="padding-bottom: 0;">
                    <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 24px;">📚 İlgili Yazılar</h3>
                    <div class="post-grid">
                        <?php foreach ($relatedPosts as $rp): ?>
                            <article class="card reveal">
                                <div class="card-image-wrapper">
                                    <?php if ($rp['image']): ?>
                                        <img src="uploads/<?= e($rp['image']) ?>" alt="" class="card-image">
                                    <?php else: ?>
                                        <div class="card-image" style="background: linear-gradient(135deg, <?= e($rp['category_color']) ?>40, var(--bg-secondary)); display:flex; align-items:center; justify-content:center; font-size:3rem;">📄</div>
                                    <?php endif; ?>
                                    <span class="card-category" style="background: <?= e($rp['category_color']) ?>;">
                                        <?= e($rp['category_name']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h3 class="card-title">
                                        <a href="post.php?slug=<?= e($rp['slug']) ?>"><?= e($rp['title']) ?></a>
                                    </h3>
                                    <p class="card-text"><?= truncate($rp['content']) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
