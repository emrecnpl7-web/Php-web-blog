<?php
/**
 * Kayıt Sayfası
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Kayıt Ol';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik doğrulaması başarısız!';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        // Doğrulamalar
        if (empty($username) || empty($email) || empty($password)) {
            $errors[] = 'Tüm alanları doldurun.';
        }
        if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
            $errors[] = 'Kullanıcı adı 3-30 karakter olmalı.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta girin.';
        }
        if (mb_strlen($password) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalı.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Şifreler eşleşmiyor!';
        }

        // Benzersizlik kontrolü
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Bu kullanıcı adı veya e-posta zaten kayıtlı.';
            }
        }

        // Kayıt
        if (empty($errors)) {
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPass]);

            // Otomatik giriş
            $userId = $pdo->lastInsertId();
            $_SESSION['user_id']     = $userId;
            $_SESSION['user_name']   = $username;
            $_SESSION['user_email']  = $email;
            $_SESSION['user_role']   = 'user';
            $_SESSION['user_avatar'] = 'default.png';

            setFlash('success', 'Hesabınız oluşturuldu! Hoş geldiniz, ' . $username . '!');
            header('Location: index.php');
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">🚀</div>
                <h1>Kayıt Ol</h1>
                <p>Ücretsiz hesap oluşturun ve yazmaya başlayın</p>
            </div>

            <?php if ($errors): ?>
                <div class="flash-message flash-error" style="position:static; margin-bottom:20px; max-width:100%;">
                    <span class="flash-icon">❌</span>
                    <span class="flash-text">
                        <?php foreach ($errors as $err): ?>
                            <?= e($err) ?><br>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label class="form-label" for="username">👤 Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="kullanici_adi" value="<?= e($username ?? '') ?>" 
                           minlength="3" maxlength="30" required autofocus>
                    <div class="form-hint">3-30 karakter, harf ve rakam kullanın</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">📧 E-posta</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="ornek@email.com" value="<?= e($email ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">🔒 Şifre</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="En az 6 karakter" minlength="6" required>
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
                    ✨ Hesap Oluştur
                </button>
            </form>

            <div class="auth-divider">veya</div>

            <div class="auth-footer">
                Zaten hesabınız var mı? <a href="login.php" style="font-weight:600;">Giriş Yapın</a>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
