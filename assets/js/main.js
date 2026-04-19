/**
 * Adıyamanlı Blog — Ana JavaScript Dosyası
 * Animasyonlar, Etkileşimler, Dinamik UI
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initScrollReveal();
    initFlashAutoClose();
    initPasswordToggle();
    initSmoothLinks();
    initCardAnimations();
});

/* ─── Navbar ─────────────────────────────────────────────────────── */
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    // Scroll efekti
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // Hamburger menü
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('open');
        });

        // Link tıklayınca kapat
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navLinks.classList.remove('open');
            });
        });
    }
}

/* ─── Scroll Reveal Animasyon ────────────────────────────────────── */
function initScrollReveal() {
    const reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('active');
                }, index * 100);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    reveals.forEach(el => observer.observe(el));
}

/* ─── Flash Mesaj Otomatik Kapanma ───────────────────────────────── */
function initFlashAutoClose() {
    const flash = document.getElementById('flashMessage');
    if (flash) {
        setTimeout(() => {
            flash.style.animation = 'slideInRight 0.4s ease reverse forwards';
            setTimeout(() => flash.remove(), 400);
        }, 4000);
    }
}

/* ─── Şifre Göster/Gizle ────────────────────────────────────────── */
function initPasswordToggle() {
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.textContent = isPassword ? '🙈' : '👁️';
        });
    });
}

/* ─── Smooth Scroll Linkleri ─────────────────────────────────────── */
function initSmoothLinks() {
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

/* ─── Kart Hover Parıltı Efekti ──────────────────────────────────── */
function initCardAnimations() {
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
}

/* ─── Karakter Sayacı (Textarea) ─────────────────────────────────── */
function initCharCounter(textareaId, counterId, max) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    if (!textarea || !counter) return;

    textarea.addEventListener('input', () => {
        const len = textarea.value.length;
        counter.textContent = `${len} / ${max}`;
        counter.style.color = len > max ? 'var(--danger)' : 'var(--text-muted)';
    });
}

/* ─── Onay Dialogu ───────────────────────────────────────────────── */
function confirmDelete(message = 'Bu öğeyi silmek istediğinize emin misiniz?') {
    return confirm(message);
}

/* ─── Görsel Önizleme ────────────────────────────────────────────── */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

/* ─── Admin Sidebar Toggle (Mobile) ──────────────────────────────── */
function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

/* ─── Sayfa Geçiş Efekti ────────────────────────────────────────── */
document.querySelectorAll('a:not([href^="#"]):not([target="_blank"])').forEach(link => {
    if (link.hostname === window.location.hostname && !link.hasAttribute('onclick')) {
        link.addEventListener('click', function(e) {
            if (e.ctrlKey || e.metaKey) return;
            e.preventDefault();
            const href = this.href;
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.25s ease';
            setTimeout(() => window.location.href = href, 250);
        });
    }
});

// Sayfa yüklendiğinde fade-in
window.addEventListener('pageshow', () => {
    document.body.style.opacity = '1';
    document.body.style.transition = 'opacity 0.3s ease';
});
