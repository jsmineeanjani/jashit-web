/* =============================================================
   JASHIT — Dashboard Admin JavaScript
   File: assets/js/dashboard_admin.js
   ============================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-dismiss alert feedback setelah 8 detik ───────────
    const alertFeedback = document.getElementById('alertFeedbackBaru');
    if (alertFeedback) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertFeedback);
            bsAlert.close();
        }, 8000);
    }

    // ── Highlight baris tabel jika pembayaran butuh verifikasi ─
    document.querySelectorAll('.row-perlu-verifikasi').forEach(function (row) {
        row.style.backgroundColor = '#fffbeb';
    });

    // ── Tooltip Bootstrap (jika dibutuhkan) ───────────────────
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

});