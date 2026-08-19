<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

// Ambil data diskon yang statusnya aktif dari Admin
$query = "SELECT * FROM informasi_diskon WHERE status = 'aktif' ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Info Promo & Diskon — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/promo.css" rel="stylesheet">
</head>
<body class="promo-page-bg">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>

        <div class="dashboard-content dashboard-content-pad">
            
            <div class="mb-4">
                <h1 class="page-title promo-page-title">Promo & Diskon Spesial</h1>
                <p class="text-muted promo-page-subtitle">Nikmati berbagai penawaran menarik khusus untuk Anda</p>
            </div>

            <?php 
            $ada_promo = false;
            $daftar_promo = [];
            if ($result && mysqli_num_rows($result) > 0) {
                $sekarang = date('Y-m-d');

                while ($row = mysqli_fetch_assoc($result)) {
                    // FILTER: Lewati promo jika tanggal selesai sudah lewat
                    if (!empty($row['tgl_selesai']) && $row['tgl_selesai'] != '0000-00-00' && $sekarang > $row['tgl_selesai']) {
                        continue; 
                    }
                    $ada_promo = true;
                    $daftar_promo[] = $row;
                }
            }
            ?>

            <?php if ($ada_promo): ?>
                <div class="promo-strip-list">
                    <?php foreach ($daftar_promo as $row): ?>
                        <div class="promo-strip shadow-sm">
                            <div class="promo-strip-left">
                                <div class="promo-strip-headline">
                                    <?php if($row['target_pelanggan'] == 'pengguna_baru'): ?>
                                        <span class="promo-strip-badge"><i class="bi bi-stars"></i>Pengguna Baru</span>
                                    <?php else: ?>
                                        <span class="promo-strip-badge">Promo</span>
                                    <?php endif; ?>

                                    <?php if (!empty($row['diskon_persen']) && (int)$row['diskon_persen'] > 0): ?>
                                        <?= (int)$row['diskon_persen'] ?>% OFF &mdash;
                                    <?php endif; ?>
                                    <?= htmlspecialchars($row['judul']) ?>
                                </div>
                                <div class="promo-strip-sub">
                                    <i class="bi bi-calendar-check"></i>
                                    <?php 
                                    if (empty($row['tgl_mulai']) && empty($row['tgl_selesai'])) {
                                        echo 'Berlaku Selamanya';
                                    } else {
                                        $mulai = !empty($row['tgl_mulai']) ? date('d M Y', strtotime($row['tgl_mulai'])) : 'Sekarang';
                                        $selesai = (!empty($row['tgl_selesai']) && $row['tgl_selesai'] != '0000-00-00') ? date('d M Y', strtotime($row['tgl_selesai'])) : 'Seterusnya';
                                        echo htmlspecialchars("$mulai - $selesai");
                                    }
                                    ?>
                                    <i class="bi bi-info-circle promo-strip-info-icon"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="<?= htmlspecialchars($row['deskripsi'] ?? 'Dapatkan penawaran spesial ini untuk pesanan jahitan Anda.') ?>"></i>
                                </div>
                            </div>
                            <a href="katalog.php" class="promo-strip-btn">Klaim Promo</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert text-center py-5 promo-empty-state">
                    <i class="bi bi-ticket-detailed fs-1 text-muted d-block mb-3"></i>
                    <h6 class="fw-bold text-dark">Belum Ada Promo Aktif</h6>
                    <p class="text-muted small mb-0">Nantikan diskon dan penawaran menarik dari Jashit selanjutnya!</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/promo.js"></script>
</body>
</html>