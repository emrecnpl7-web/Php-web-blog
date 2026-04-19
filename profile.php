<?php
/**
 * Profil Sayfası
 */
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$pageTitle = 'Profilim';
$errors = [];

// Kullanıcı bilgileri
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';
    $curPass  = $_POST['current_password'] ?? '';

    if (empty($username) || empty($email)) {
        $errors[] = 'Kullanıcı adı ve e-posta zorunludur.';
    }

    // Benzersizlik
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Bu kullanıcı adı veya e-posta başkası tarafından kullanılıyor.';
        }
    }

    // Şifre değişikliği
    if (!empty($newPass)) {
        if (empty($curPass) || !password_verify($curPass, $user['password'])) {
            $errors[] = 'Mevcut şifre hatalı!';
        }
        if (mb_strlen($newPass) < 6) {
            $errors[] = 'Yeni şifre en az 6 karakter olmalı.';
        }
    }

    if (empty($errors)) {
        $updateFields = "username = ?, email = ?, bio = ?";
        $params = [$username, $email, $bio];

        if (!empty($newPass)) {
            $updateFields .= ", password = ?";
            $params[] = password_hash($newPass, PASSWORD_DEFAULT);
        }

        $params[] = $_SESSION['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET $updateFields WHERE id = ?");
        $stmt->execute($params);

        // Session güncelle
        $_SESSION['user_name']  = $username;
        $_SESSION['user_email'] = $email;

        setFlash('success', 'Profiliniz güncellendi!');
        header('Location: profile.php');
        exit;
    }
}

// Kullanıcının yazıları
$myPosts = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.color as category_color,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$myPosts->execute([$_SESSION['user_id']]);
$myPosts = $myPosts->fetchAll();

// İstatistikler
$totalViews = $pdo->prepare("SELECT COALESCE(SUM(views), 0) FROM posts WHERE user_id = ?");
$totalViews->execute([$_SESSION['user_id']]);
$totalViews = $totalViews->fetchColumn();

$totalComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$totalComments->execute([$_SESSION['user_id']]);
$totalComments = $totalComments->fetchColumn();

require_once 'includes/header.php';
?>

    <section class="section" style="padding-top: 100px;">
        <div class="container container-md">

            <!-- Profil Başlık -->
            <div class="profile-header animate-fadeInUp">
                <img src="<?= getAvatar($user['email'], 120) ?>" alt="" class="profile-avatar">
                <h1 class="profile-name"><?= e($user['username']) ?></h1>
                <p style="color: var(--text-muted); margin-top: 8px;">
                    <?= e($user['email']) ?> • Kayıt: <?= date('d M Y', strtotime($user['created_at'])) ?>
                </p>
                <?php if ($user['bio']): ?>
                    <p style="color: var(--text-secondary); margin-top: 12px; max-width: 500px; margin-left: auto; margin-right: auto;"><?= e($user['bio']) ?></p>
                <?php endif; ?>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="stat-val"><?= count($myPosts) ?></div>
                        <div class="stat-lbl">Yazı</div>
                    </div>
                    <div class="profile-stat">
                        <div class="stat-val"><?= $totalViews ?></div>
                        <div class="stat-lbl">Görüntülenme</div>
                    </div>
                    <div class="profile-stat">
                        <div class="stat-val"><?= $totalComments ?></div>
                        <div class="stat-lbl">Yorum</div>
                    </div>
                </div>
            </div>

            <!-- Profil Düzenleme -->
            <div class="card reveal" style="margin-bottom: 40px;">
                <div class="card-body" style="padding: 32px;">
                    <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 24px;">⚙️ Profil Ayarları</h3>

                    <?php if ($errors): ?>
                        <div class="flash-message flash-error" style="position:static; margin-bottom:20px; max-width:100%;">
                            <span class="flash-icon">❌</span>
                            <span class="flash-text">
                                <?php foreach ($errors as $err): echo e($err) . '<br>'; endforeach; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label" for="username">👤 Kullanıcı Adı</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?= e($user['username']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">📧 E-posta</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= e($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="bio">📝 Hakkımda</label>
                            <textarea id="bio" name="bio" class="form-control" rows="3" 
                                      placeholder="Kendinizden bahsedin..."><?= e($user['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label" for="current_password">🔒 Mevcut Şifre</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" 
                                       placeholder="Şifre değiştirmek için">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="new_password">🔑 Yeni Şifre</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" 
                                       placeholder="En az 6 karakter">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">💾 Değişiklikleri Kaydet</button>
                    </form>
                </div>
            </div>

            <!-- Yazılarım -->
            <div class="reveal">
                <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 24px;">📖 Yazılarım</h3>

                <?php if (empty($myPosts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">✏️</div>
                        <h3>Henüz yazınız yok</h3>
                        <p>İlk yazınızı ekleyin!</p>
                        <a href="new_post.php" class="btn btn-primary mt-3">✏️ Yazı Ekle</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($myPosts as $mp): ?>
                        <div class="card" style="margin-bottom: 16px;">
                            <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                                <div style="flex: 1;">
                                    <span class="badge" style="background: <?= e($mp['category_color']) ?>20; color: <?= e($mp['category_color']) ?>;"><?= e($mp['category_name']) ?></span>
                                    <span class="badge badge-<?= $mp['status'] ?>" style="margin-left: 6px;"><?= $mp['status'] === 'published' ? 'Yayında' : 'Taslak' ?></span>
                                    <h4 style="margin-top: 8px;">
                                        <a href="post.php?slug=<?= e($mp['slug']) ?>"><?= e($mp['title']) ?></a>
                                    </h4>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        👁️ <?= $mp['views'] ?> • 💬 <?= $mp['comment_count'] ?> • <?= timeAgo($mp['created_at']) ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit_post.php?id=<?= $mp['id'] ?>" class="btn btn-secondary btn-sm">✏️ Düzenle</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
