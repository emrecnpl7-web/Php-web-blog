    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">✍️ <?= SITE_NAME ?></div>
                    <p class="footer-desc">
                        Modern, şık ve kullanıcı dostu blog platformu. 
                        Düşüncelerinizi paylaşın, keşfedin ve ilham verin.
                    </p>
                </div>
                <div>
                    <h4>Sayfalar</h4>
                    <ul>
                        <li><a href="index.php">Ana Sayfa</a></li>
                        <li><a href="category.php">Kategoriler</a></li>
                        <li><a href="register.php">Kayıt Ol</a></li>
                        <li><a href="login.php">Giriş Yap</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Kategoriler</h4>
                    <ul>
                        <?php
                        $footerCats = $pdo->query("SELECT name, slug FROM categories LIMIT 5")->fetchAll();
                        foreach ($footerCats as $fc):
                        ?>
                            <li><a href="category.php?slug=<?= e($fc['slug']) ?>"><?= e($fc['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h4>İletişim</h4>
                    <ul>
                        <li><a href="#">📧 info@blog.com</a></li>
                        <li><a href="#">📍 Adıyaman, Türkiye</a></li>
                        <li><a href="#">📱 +90 555 000 00 00</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Tüm hakları saklıdır.</p>
                <div class="footer-social">
                    <a href="#" title="Twitter">𝕏</a>
                    <a href="#" title="GitHub">⌨</a>
                    <a href="#" title="Instagram">📷</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
