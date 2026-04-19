<?php
/**
 * Şifre Sıfırlama Sayfası
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Şifre Sıfırla';
$token = $_GET['token'] ?? '';
$errors = [];
$valid = false;

// Token doğrulama
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    $valid = (bool) $user;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik doğrulaması başarısız!';
    } else {
        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (mb_strlen($password) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalı.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Şifreler eşleşmiyor!';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt->execute([$hashedPass, $user['id']]);

                setFlash('success', 'Şifreniz başarıyla güncellendi! Giriş yapabilirsiniz.');
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Geçersiz veya süresi dolmuş bağlantı.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">🔄</div>
                <h1>Yeni Şifre Belirle</h1>
                <p>Yeni şifrenizi girin</p>
            </div>

            <?php if ($errors): ?>
                <div class="flash-message flash-error" style="position:static; margin-bottom:20px; max-width:100%;">
                    <span class="flash-icon">❌</span>
                    <span class="flash-text">
                        <?php foreach ($errors as $err): echo e($err) . '<br>'; endforeach; ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($valid): ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="form-group">
                        <label class="form-label" for="password">🔒 Yeni Şifre</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="En az 6 karakter" minlength="6" required autofocus>
                            <button type="button" class="password-toggle">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">🔒 Şifre Tekrar</label>
                        <div class="password-wrapper">
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                                   placeholder="Şifrenizi tekrar girin" required>
                            <button type="button" class="password-toggle">👁️</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content: center;">
                        ✅ Şifremi Güncelle
                    </button>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">⏰</div>
                    <h3>Geçersiz Bağlantı</h3>
                    <p>Bu sıfırlama bağlantısı geçersiz veya süresi dolmuş.</p>
                    <a href="forgot_password.php" class="btn btn-primary mt-3">Yeni Bağlantı İste</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
