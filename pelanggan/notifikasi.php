<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = (int) $_SESSION['user_id'];

// 1. Otomatis tandai semua notifikasi menjadi "Sudah Dibaca" saat halaman ini dibuka
mysqli_query($koneksi, "UPDATE notifikasi SET is_read = 1 WHERE user_id = $user_id");

// 2. Ambil semua riwayat notifikasi dari database
$query = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Semua Notifikasi — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/notifikasi_bell.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="dashboard-wrapper">
    
  <?php require_once '../includes/layouts/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_pelanggan.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title fs-5 fw-bold mb-0">Semua Notifikasi</h1>
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            
            <div class="notif-page-card shadow-sm p-0">
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        while ($row = mysqli_fetch_assoc($query)): 
                            $judul_lower = strtolower($row['judul']);
                            $target_url  = $row['url'] ?? '';

                            // Logika Pintar: Tentukan link tujuan berdasarkan judul notifikasi
                            if (empty($target_url)) {
                                if (strpos($judul_lower, 'promo') !== false || strpos($judul_lower, 'diskon') !== false) {
                                    $target_url = BASE_URL . '/pelanggan/promo.php'; 
                                } elseif (strpos($judul_lower, 'pembayaran') !== false || strpos($judul_lower, 'pesanan') !== false) {
                                    $target_url = BASE_URL . '/pelanggan/tracking.php'; 
                                } else {
                                    $target_url = '#'; 
                                }
                            }

                            // Tentukan Ikon berdasarkan isi
                            if (strpos($judul_lower, 'promo') !== false || strpos($judul_lower, 'diskon') !== false) {
                                $icon_bg = '#fef08a'; $icon_color = '#ca8a04'; $icon_bi = 'bi-tags-fill';
                            } elseif (strpos($judul_lower, 'pembayaran') !== false) {
                                $icon_bg = '#dbeafe'; $icon_color = '#2563eb'; $icon_bi = 'bi-wallet2';
                            } else {
                                $icon_bg = '#f1f5f9'; $icon_color = '#64748b'; $icon_bi = 'bi-bell-fill';
                            }
                        ?>
                            <a href="<?= $target_url ?>" class="list-group-item list-group-item-action d-flex gap-3 py-3 notif-page-item">
                                <div class="notif-page-icon" style="background-color: <?= $icon_bg ?>; color: <?= $icon_color ?>;">
                                    <i class="bi <?= $icon_bi ?>"></i>
                                </div>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold notif-page-title"><?= htmlspecialchars($row['judul']) ?></h6>
                                        <small class="text-muted notif-page-time">
                                            <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 text-secondary notif-page-desc">
                                        <?= htmlspecialchars($row['pesan']) ?>
                                    </p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="notif-page-empty">
                        <i class="bi bi-bell-slash text-muted"></i>
                        <p class="mt-3 text-muted fw-bold mb-1">Belum ada riwayat notifikasi.</p>
                        <p class="text-muted" style="font-size: 13px;">Semua pemberitahuan pesanan dan promo akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>