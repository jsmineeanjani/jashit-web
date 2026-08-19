<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $del = mysqli_query($koneksi, "DELETE FROM informasi_diskon WHERE id = $id_hapus");
    if ($del) {
        $_SESSION['flash_success'] = 'Informasi diskon berhasil dihapus.';
    }
    header("Location: informasi.php");
    exit;
}

$query = "SELECT * FROM informasi_diskon ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Informasi Diskon — Admin JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/informasi.css" rel="stylesheet">
</head>
<body class="page-informasi">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content dashboard-content-pad">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title page-title-sm">Informasi Diskon</h1>
                <a href="informasi_tambah.php" class="btn btn-sm btn-add-diskon">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Diskon
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show alert-sm" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="diskon-search-wrap">
                    <i class="bi bi-search diskon-search-icon"></i>
                    <input type="text" id="cariDiskon" class="form-control diskon-search-input" placeholder="Cari diskon...">
                </div>

                <div id="diskonNoResult" class="diskon-coupon-empty diskon-hidden">
                    <i class="bi bi-search"></i>
                    Tidak ada diskon yang cocok dengan pencarian
                </div>

                <div class="diskon-coupon-list">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="diskon-coupon-card">

                            <!-- Kotak Nilai Diskon -->
                            <div class="diskon-coupon-value">
                                <div class="diskon-coupon-value-number"><?= (int)$row['diskon_persen'] ?>%</div>
                                <div class="diskon-coupon-value-label">OFF</div>
                            </div>

                            <!-- Isi -->
                            <div class="diskon-coupon-body">
                                <div class="diskon-coupon-meta">
                                    <?php if($row['target_pelanggan'] == 'pengguna_baru'): ?>
                                        <span><i class="bi bi-stars me-1"></i>Pengguna Baru</span>
                                    <?php else: ?>
                                        <span>Semua Pelanggan</span>
                                    <?php endif; ?>

                                    <?php if (!empty($row['kode_promo'])): ?>
                                        <span class="diskon-coupon-meta-sep">&bull;</span>
                                        <span>Kode: <?= htmlspecialchars($row['kode_promo']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="diskon-coupon-title"><?= htmlspecialchars($row['judul']) ?></h3>

                                <div class="diskon-coupon-footer">
                                    <span>
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?php 
                                        if (empty($row['tgl_mulai']) && empty($row['tgl_selesai'])) {
                                            echo '<span class="diskon-periode-selamanya">Berlaku Selamanya</span>';
                                        } else {
                                            $mulai = !empty($row['tgl_mulai']) ? date('d M Y', strtotime($row['tgl_mulai'])) : '...';
                                            $selesai = !empty($row['tgl_selesai']) ? date('d M Y', strtotime($row['tgl_selesai'])) : 'Seterusnya';
                                            echo $mulai . ' &ndash; ' . $selesai;
                                        }
                                        ?>
                                    </span>
                                    <?php 
                                        if (!empty($row['tgl_selesai'])) {
                                            $sekarang = date('Y-m-d');
                                            if ($sekarang > $row['tgl_selesai']) {
                                                echo '<span class="text-danger diskon-expired-note"><i class="bi bi-exclamation-circle me-1"></i>Sudah Kedaluwarsa</span>';
                                            }
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- Status & Aksi -->
                            <div class="diskon-coupon-actions">
                                <?php if($row['status'] == 'aktif'): ?>
                                    <span class="badge bg-success diskon-status-badge">AKTIF</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary diskon-status-badge">NONAKTIF</span>
                                <?php endif; ?>

                                <div class="diskon-coupon-btns">
                                    <a href="informasi_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="informasi.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus" title="Hapus Permanen">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="diskon-coupon-empty">
                    <i class="bi bi-tag"></i>
                    Belum ada informasi diskon
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Eksternal Script -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/informasi.js"></script>

</body>
</html>