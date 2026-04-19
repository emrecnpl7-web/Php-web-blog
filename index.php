<?php
/**
 * Ana Sayfa — Son Yazılar ve Hero
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Ana Sayfa';

// İstatistikler
$totalPosts    = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

// Kategoriler
$categories = $pdo->query("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON c.id = p.category_id AND p.status='published' GROUP BY c.id ORDER BY post_count DESC")->fetchAll();

// Öne çıkan yazı (son eklenen)
$featured = $pdo->query("
    SELECT p.*, u.username, u.email as user_email, c.name as category_name, c.slug as category_slug, c.color as category_color,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC LIMIT 1
")->fetch();

// Son yazılar (öne çıkan hariç)
$featuredId = $featured ? $featured['id'] : 0;
$posts = $pdo->prepare("
    SELECT p.*, u.username, u.email as user_email, c.name as category_name, c.slug as category_slug, c.color as category_color,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'published' AND p.id != ?
    ORDER BY p.created_at DESC LIMIT 6
");
$posts->execute([$featuredId]);
$posts = $posts->fetchAll();

require_once 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                Hoş Geldiniz
            </div>
            <h1>
                Düşüncelerinizi<br>
                <span class="gradient-text">Dünyayla Paylaşın</span>
            </h1>
            <p>
                Yazın, keşfedin ve ilham verin. Modern blog platformumuzda 
                fikirlerinizi paylaşın ve topluluğumuzun bir parçası olun.
            </p>
            <div class="hero-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="new_post.php" class="btn btn-primary btn-lg">✏️ Yazı Yaz</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">🚀 Hemen Başla</a>
                <?php endif; ?>
                <a href="#posts" class="btn btn-secondary btn-lg">📖 Yazıları Keşfet</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="stat-number"><?= $totalPosts ?></div>
                    <div class="stat-label">Yazı</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-number"><?= $totalUsers ?></div>
                    <div class="stat-label">Yazar</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-number"><?= $totalComments ?></div>
                    <div class="stat-label">Yorum</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kategoriler -->
    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="category-bar reveal">
                <a href="index.php" class="category-chip active" style="background: var(--accent); color: white;">Tümü</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="category.php?slug=<?= e($cat['slug']) ?>" class="category-chip" 
                       style="--chip-color: <?= e($cat['color']) ?>;"
                       onmouseover="this.style.background='<?= e($cat['color']) ?>'; this.style.color='white';"
                       onmouseout="this.style.background=''; this.style.color='';">
                        <span class="chip-icon"><?= $cat['icon'] ?></span>
                        <?= e($cat['name']) ?> 
                        <span style="opacity:0.6">(<?= $cat['post_count'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Öne Çıkan Yazı -->
    <?php if ($featured): ?>
    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="featured-post reveal">
                <?php if ($featured['image']): ?>
                    <img src="uploads/<?= e($featured['image']) ?>" alt="<?= e($featured['title']) ?>" class="featured-image">
                <?php else: ?>
                    <div class="featured-image" style="background: linear-gradient(135deg, <?= e($featured['category_color']) ?>, var(--accent)); display:flex; align-items:center; justify-content:center; font-size:5rem; color:rgba(255,255,255,0.3);">📝</div>
                <?php endif; ?>
                <div class="featured-body">
                    <a href="category.php?slug=<?= e($featured['category_slug']) ?>" class="category-chip" style="background: <?= e($featured['category_color']) ?>; color: white; align-self: flex-start; margin-bottom: 16px;">
                        <?= e($featured['category_name']) ?>
                    </a>
                    <h2><a href="post.php?slug=<?= e($featured['slug']) ?>" style="color: var(--text-primary);"><?= e($featured['title']) ?></a></h2>
                    <p><?= truncate($featured['content'], 200) ?></p>
                    <div class="card-meta" style="border-top: none; padding-top: 0;">
                        <div class="card-author">
                            <img src="<?= getAvatar($featured['user_email']) ?>" alt="">
                            <span><?= e($featured['username']) ?></span>
                        </div>
                        <div class="card-stats">
                            <span>👁️ <?= $featured['views'] ?></span>
                            <span>💬 <?= $featured['comment_count'] ?></span>
                            <span>📅 <?= timeAgo($featured['created_at']) ?></span>
                        </div>
                    </div>
                    <a href="post.php?slug=<?= e($featured['slug']) ?>" class="btn btn-primary mt-3">Devamını Oku →</a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Son Yazılar -->
    <section class="section" id="posts" style="padding-top: 0;">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-title">📖 Son Yazılar</h2>
                <p class="section-subtitle">Topluluğumuzdan en güncel içerikler</p>
            </div>

            <?php if (empty($posts) && !$featured): ?>
                <div class="empty-state reveal">
                    <div class="empty-icon">📝</div>
                    <h3>Henüz yazı yok</h3>
                    <p>İlk yazıyı siz ekleyin!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="new_post.php" class="btn btn-primary mt-3">✏️ Yazı Ekle</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="post-grid">
                    <?php foreach ($posts as $i => $p): ?>
                        <article class="card reveal" style="animation-delay: <?= $i * 0.1 ?>s;">
                            <div class="card-image-wrapper">
                                <?php if ($p['image']): ?>
                                    <img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>" class="card-image">
                                <?php else: ?>
                                    <div class="card-image" style="background: linear-gradient(135deg, <?= e($p['category_color']) ?>40, var(--bg-secondary)); display:flex; align-items:center; justify-content:center; font-size:3rem; color:rgba(255,255,255,0.2);">📄</div>
                                <?php endif; ?>
                                <span class="card-category" style="background: <?= e($p['category_color']) ?>;">
                                    <?= e($p['category_name']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title">
                                    <a href="post.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
                                </h3>
                                <p class="card-text"><?= truncate($p['content']) ?></p>
                                <div class="card-meta">
                                    <div class="card-author">
                                        <img src="<?= getAvatar($p['user_email']) ?>" alt="">
                                        <span><?= e($p['username']) ?></span>
                                    </div>
                                    <div class="card-stats">
                                        <span>👁️ <?= $p['views'] ?></span>
                                        <span>💬 <?= $p['comment_count'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
