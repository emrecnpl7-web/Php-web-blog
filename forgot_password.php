<?php
/**
 * Şifremi Unuttum Sayfası
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Şifremi Unuttum';
$sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik doğrulaması başarısız!';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta girin.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Token oluştur
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);

                // Gerçek e-posta göndermek yerine token'ı gösteriyoruz (geliştirme amaçlı)
                $resetLink = "reset_password.php?token=" . $token;
                $sent = true;
                $_SESSION['reset_link'] = $resetLink;
            } else {
                // Güvenlik: kullanıcı olsun olmasın aynı mesajı göster
                $sent = true;
            }
        }
    }
}

require_once 'includes/header.php';
?>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">🔑</div>
                <h1>Şifremi Unuttum</h1>
                <p>E-posta adresinizi girin, şifre sıfırlama bağlantısı göndereceğiz</p>
            </div>

            <?php if ($errors): ?>
                <div class="flash-message flash-error" style="position:static; margin-bottom:20px; max-width:100%;">
                    <span class="flash-icon">❌</span>
                    <span class="flash-text"><?= e(implode('<br>', $errors)) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($sent): ?>
                <div class="flash-message flash-success" style="position:static; margin-bottom:20px; max-width:100%;">
                    <span class="flash-icon">✅</span>
                    <span class="flash-text">Şifre sıfırlama bağlantısı gönderildi!</span>
                </div>

                <?php if (isset($_SESSION['reset_link'])): ?>
                    <div style="background: var(--bg-secondary); padding: 20px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid var(--border-glass);">
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 10px;">
                            ⚠️ Geliştirme Modu — Sıfırlama bağlantısı:
                        </p>
                        <a href="<?= e($_SESSION['reset_link']) ?>" style="word-break: break-all; font-family: var(--font-mono); font-size: 0.85rem;">
                            <?= e($_SESSION['reset_link']) ?>
                        </a>
                    </div>
                    <?php unset($_SESSION['reset_link']); ?>
                <?php endif; ?>

                <a href="login.php" class="btn btn-secondary w-full" style="justify-content: center;">← Giriş Sayfasına Dön</a>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="form-group">
                        <label class="form-label" for="email">📧 E-posta Adresiniz</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="ornek@email.com" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content: center;">
                        📨 Sıfırlama Bağlantısı Gönder
                    </button>
                </form>

                <div class="auth-footer" style="margin-top: 24px;">
                    <a href="login.php">← Giriş sayfasına dön</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
