<?php
/**
 * Kategori Sayfası — Kategoriye göre yazılar
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$category = null;

// Belirli kategori mi, yoksa tüm kategoriler mi?
if (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $category = $stmt->fetch();

    if (!$category) {
        setFlash('error', 'Kategori bulunamadı!');
        header('Location: category.php');
        exit;
    }

    $pageTitle = $category['name'] . ' Kategorisi';

    // Bu kategorideki yazılar
    $pagination = paginate($pdo, 'posts', "category_id = {$category['id']} AND status = 'published'", 9);

    $posts = $pdo->prepare("
        SELECT p.*, u.username, u.email as user_email, c.name as category_name, c.color as category_color,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}
    ");
    $posts->execute([$category['id']]);
    $posts = $posts->fetchAll();
} else {
    $pageTitle = 'Kategoriler';
}

// Tüm kategoriler
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id AND p.status='published' 
    GROUP BY c.id 
    ORDER BY post_count DESC
")->fetchAll();

require_once 'includes/header.php';
?>

    <section class="section" style="padding-top: 100px;">
        <div class="container">
            <?php if ($category): ?>
                <!-- Belirli Kategori -->
                <div class="section-header animate-fadeInUp">
                    <div style="font-size: 3rem; margin-bottom: 12px;"><?= $category['icon'] ?></div>
                    <h1 class="section-title" style="font-size: 2.5rem;"><?= e($category['name']) ?></h1>
                    <p class="section-subtitle"><?= $pagination['total'] ?> yazı bulundu</p>
                </div>

                <!-- Diğer Kategoriler -->
                <div class="category-bar reveal">
                    <a href="index.php" class="category-chip">🏠 Ana Sayfa</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="category.php?slug=<?= e($cat['slug']) ?>" 
                           class="category-chip <?= $cat['slug'] === $slug ? 'active' : '' ?>"
                           style="<?= $cat['slug'] === $slug ? 'background:' . e($cat['color']) . '; color:white;' : '' ?>"
                           onmouseover="this.style.background='<?= e($cat['color']) ?>'; this.style.color='white';"
                           onmouseout="<?= $cat['slug'] === $slug ? '' : "this.style.background=''; this.style.color='';" ?>">
                            <?= $cat['icon'] ?> <?= e($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Yazılar -->
                <?php if (empty($posts)): ?>
                    <div class="empty-state reveal">
                        <div class="empty-icon">📭</div>
                        <h3>Bu kategoride henüz yazı yok</h3>
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
                                        <img src="uploads/<?= e($p['image']) ?>" alt="" class="card-image">
                                    <?php else: ?>
                                        <div class="card-image" style="background: linear-gradient(135deg, <?= e($p['category_color']) ?>40, var(--bg-secondary)); display:flex; align-items:center; justify-content:center; font-size:3rem;">📄</div>
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

                    <!-- Sayfalama -->
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <div class="pagination">
                            <?php if ($pagination['page'] > 1): ?>
                                <a href="?slug=<?= e($slug) ?>&page=<?= $pagination['page'] - 1 ?>">←</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                                <?php if ($i === $pagination['page']): ?>
                                    <span class="active"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?slug=<?= e($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                                <a href="?slug=<?= e($slug) ?>&page=<?= $pagination['page'] + 1 ?>">→</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <!-- Tüm Kategoriler -->
                <div class="section-header animate-fadeInUp">
                    <h1 class="section-title" style="font-size: 2.5rem;">📂 Kategoriler</h1>
                    <p class="section-subtitle">İlgi alanınıza göre yazıları keşfedin</p>
                </div>

                <div class="grid grid-3">
                    <?php foreach ($categories as $i => $cat): ?>
                        <a href="category.php?slug=<?= e($cat['slug']) ?>" class="card reveal" style="animation-delay: <?= $i * 0.1 ?>s; text-decoration: none;">
                            <div class="card-body" style="text-align: center; padding: 40px 24px;">
                                <div style="font-size: 3rem; margin-bottom: 16px;"><?= $cat['icon'] ?></div>
                                <h3 style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                                    <?= e($cat['name']) ?>
                                </h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem;">
                                    <?= $cat['post_count'] ?> yazı
                                </p>
                                <div style="width: 40px; height: 4px; background: <?= e($cat['color']) ?>; border-radius: 2px; margin: 16px auto 0;"></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
