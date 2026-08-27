<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand text-center pt-4 pb-3">
        <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" style="max-width: 120px; height: auto; margin-bottom: 8px; filter: invert(1) brightness(1.5);">
        <div style="font-size: 11px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; margin-top: 5px;">
            Pelanggan Panel
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/pelanggan/dashboard.php"
           class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i> Beranda
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/katalog.php"
           class="<?= $current === 'katalog.php' ? 'active' : '' ?>">
            <i class="bi bi-shop"></i> Layanan
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/promo.php"
        class="<?= $current === 'promo.php' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Informasi Diskon
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/tracking.php"
           class="<?= $current === 'tracking.php' ? 'active' : '' ?>">
            <i class="bi bi-geo-alt"></i> Pesanan Saya
        </a>
        
        <a href="<?= BASE_URL ?>/pelanggan/riwayat.php"
           class="<?= $current === 'riwayat.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Riwayat
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/konsultasi.php"
        class="<?= $current === 'konsultasi.php' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots"></i> Konsultasi
        </a>
        
        <a href="<?= BASE_URL ?>/pelanggan/feedback.php"
           class="<?= $current === 'feedback.php' ? 'active' : '' ?>">
            <i class="bi bi-star"></i> Feedback
        </a>
        
        <a href="<?= BASE_URL ?>/pelanggan/profil.php"
           class="<?= $current === 'profil.php' ? 'active' : '' ?>">
            <i class="bi bi-person"></i> Profil Saya
        </a>
    </nav>
    
</aside>