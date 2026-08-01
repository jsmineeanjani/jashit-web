// ============================================
// JASHIT — Global JavaScript
// ============================================

// Auto-hide flash message setelah 4 detik
document.addEventListener('DOMContentLoaded', function () {
    const flashes = document.querySelectorAll('.alert-flash');
    flashes.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 4000);
    });
});