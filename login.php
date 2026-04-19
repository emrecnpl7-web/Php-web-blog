<?php
/**
 * Giriş Sayfası
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Giriş Yap';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik doğrulaması başarısız!';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Tüm alanları doldurun.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['username'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['user_role']     = $user['role'];
                $_SESSION['user_avatar']   = $user['avatar'];

                setFlash('success', 'Hoş geldiniz, ' . $user['username'] . '!');

                $redirect = $_GET['redirect'] ?? 'index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'E-posta veya şifre hatalı!';
            }
        }
    }
}

require_once 'includes/header.php';
?>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">🔐</div>
                <h1>Giriş Yap</h1>
                <p>Hesabınıza giriş yapın ve yazmaya başlayın</p>
            </div>

            <?php if ($errors): ?>
                <div class="flash-message flash-error" style="position:static; margin-bottom:20px; max-width:100%;">
                    <span class="flash-icon">❌</span>
                    <span class="flash-text"><?= e(implode('<br>', $errors)) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label class="form-label" for="email">📧 E-posta</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="ornek@email.com" value="<?= e($email ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">🔒 Şifre</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="••••••••" required>
                        <button type="button" class="password-toggle">👁️</button>
                    </div>
                </div>

                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="forgot_password.php" style="font-size: 0.85rem; color: var(--accent-light);">Şifremi Unuttum</a>
                </div>

                <button type="submit" class="btn btn-primary w-full btn-lg" style="justify-content: center;">
                    🚀 Giriş Yap
                </button>
            </form>

            <div class="auth-divider">veya</div>

            <div class="auth-footer">
                Hesabınız yok mu? <a href="register.php" style="font-weight:600;">Kayıt Olun</a>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
