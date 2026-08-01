<?php
// Tentukan halaman aktif
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand" style="text-align: center; padding: 25px 20px 10px;">
        <!-- Trik filter: invert(1) digunakan untuk membalikkan warna hitam menjadi putih agar kontras dengan background gelap -->
        <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" style="max-width: 120px; height: auto; margin-bottom: 8px; filter: invert(1) brightness(1.5);">
        <span class="sidebar-role" style="display: block;">Admin Panel</span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/dashboard.php"
           class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i> Dashboard
        </a>
        
        <a href="<?= BASE_URL ?>/admin/pengguna.php"
           class="<?= in_array($current, ['pengguna.php','pengguna_tambah.php','pengguna_edit.php']) ? 'active' : '' ?>">
            <i class="bi bi-person"></i> Manajemen User
        </a>
        
        <a href="<?= BASE_URL ?>/admin/feedback.php"
           class="<?= $current === 'feedback.php' ? 'active' : '' ?>">
            <i class="bi bi-chat-left-text"></i> Feedback
        </a>
        
        <a href="<?= BASE_URL ?>/admin/informasi.php"
           class="<?= in_array($current, ['informasi.php', 'informasi_tambah.php', 'informasi_edit.php']) ? 'active' : '' ?>">
            <i class="bi bi-ticket-perforated"></i> Kelola Informasi Diskon
        </a>
        
        <a href="<?= BASE_URL ?>/admin/layanan.php"
           class="<?= in_array($current, ['layanan.php','layanan_tambah.php','layanan_edit.php']) ? 'active' : '' ?>">
            <i class="bi bi-scissors"></i> Layanan Jashit
        </a>
        
        <a href="<?= BASE_URL ?>/admin/pesanan.php"
           class="<?= in_array($current, ['pesanan.php','pesanan_tambah.php','pesanan_edit.php', 'pesanan_detail.php']) ? 'active' : '' ?>">
            <i class="bi bi-clipboard-data"></i> Pesanan
        </a>
        
        <a href="<?= BASE_URL ?>/admin/transaksi.php"
           class="<?= $current === 'transaksi.php' ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i> Transaksi
        </a>
    </nav>
</aside>