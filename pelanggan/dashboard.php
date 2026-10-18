<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Pastikan hanya user dengan role 'pelanggan' yang bisa masuk
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];

// 1. Hitung Statistik Pesanan Pelanggan
$q_total = mysqli_query($koneksi, "SELECT COUNT(id) AS total FROM pesanan WHERE user_id = $user_id");
$stat_total = mysqli_fetch_assoc($q_total)['total'];

$q_aktif = mysqli_query($koneksi, "SELECT COUNT(id) AS aktif FROM pesanan WHERE user_id = $user_id AND status NOT IN ('selesai', 'dibatalkan', 'diambil')");
$stat_aktif = mysqli_fetch_assoc($q_aktif)['aktif'];

$q_tagihan = mysqli_query($koneksi, "SELECT COUNT(id) AS tagihan FROM pesanan WHERE user_id = $user_id AND sisa_tagihan > 0 AND status != 'dibatalkan'");
$stat_tagihan = mysqli_fetch_assoc($q_tagihan)['tagihan'];

// 2. Ambil 5 Pesanan Terbaru Pelanggan + CEK TABEL TRANSAKSI
$query_pesanan = "SELECT p.*, 
                  (SELECT status_verifikasi FROM transaksi t WHERE t.pesanan_id = p.id ORDER BY t.id DESC LIMIT 1) as status_transaksi 
                  FROM pesanan p 
                  WHERE p.user_id = $user_id 
                  ORDER BY p.id DESC LIMIT 5";
$result_pesanan = mysqli_query($koneksi, $query_pesanan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pelanggan — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard_pelanggan.css" rel="stylesheet">
</head>
<body class="dashboard-pelanggan-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_pelanggan.php'; ?>
    
    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_pelanggan.php'; ?>

        <div class="dashboard-content dashboard-pelanggan-content">
            <div class="mb-4">
                <h1 class="dashboard-pelanggan-title">Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Pelanggan') ?>!</h1>
                <p class="text-muted dashboard-pelanggan-subtitle">Pantau terus progres jahitanmu di sini.</p>
            </div>

            <div class="row mb-5">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card shadow-sm stat-card-total">
                        <div class="text-muted stat-label">Total Pesanan</div>
                        <div class="stat-value stat-value-total"><?= $stat_total ?></div>
                        <i class="bi bi-bag-check-fill icon-bg icon-bg-total"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card shadow-sm stat-card-proses">
                        <div class="text-muted stat-label">Sedang Diproses</div>
                        <div class="stat-value stat-value-proses"><?= $stat_aktif ?></div>
                        <i class="bi bi-scissors icon-bg icon-bg-proses"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card shadow-sm stat-card-tagihan">
                        <div class="text-muted stat-label">Belum Lunas</div>
                        <div class="stat-value stat-value-tagihan"><?= $stat_tagihan ?></div>
                        <i class="bi bi-wallet2 icon-bg icon-bg-tagihan"></i>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-heading">Aktivitas Pesanan Terbaru</h5>
                <a href="tracking.php" class="fw-bold link-lihat-semua">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="card-table-wrap shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="table-head-row">
                                <th>KODE & TGL</th>
                                <th>PAKAIAN</th>
                                <th>TAGIHAN & PEMBAYARAN</th>
                                <th class="text-center">STATUS PRODUKSI</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result_pesanan && mysqli_num_rows($result_pesanan) > 0): 
                                while ($row = mysqli_fetch_assoc($result_pesanan)): 
                                    
                                    // Logika Warna Status Sesuai Admin
                                    $st = $row['status'];
                                    $badge_class = 'bg-primary'; // Default diproses
                                    
                                    if ($st === 'menunggu_konfirmasi') { $badge_class = 'bg-warning text-dark'; }
                                    elseif ($st === 'dibatalkan') { $badge_class = 'bg-danger'; }
                                    elseif (in_array($st, ['selesai', 'diambil'])) { $badge_class = 'bg-success'; }
                                    elseif (in_array($st, ['dikonfirmasi', 'siap_kirim'])) { $badge_class = 'bg-info text-dark'; }
                                    
                                    $status_label = str_replace('_', ' ', strtoupper($st));
                                    $transaksi_status = strtolower($row['status_transaksi'] ?? '');
                            ?>
                                <tr>
                                    <td class="align-middle" style="padding: 15px;">
                                        <div class="pesanan-kode"><?= $row['kode_pesanan'] ?></div>
                                        <div class="pesanan-tgl"><?= date('d M Y', strtotime($row['tanggal_pesan'])) ?></div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="pesanan-jenis"><?= htmlspecialchars($row['jenis_pakaian']) ?></div>
                                        <div class="pesanan-jumlah">Jumlah: <?= $row['jumlah'] ?> Pcs</div>
                                    </td>
                                    <td class="align-middle">
                                        <?php if($transaksi_status === 'ditolak' || $transaksi_status === 'tidak valid'): ?>
                                            <div class="tagihan-belum-lunas">Rp <?= number_format($row['sisa_tagihan'], 0, ',', '.') ?></div>
                                            <span class="badge bg-danger mt-1 badge-bukti-tidak-valid"><i class="bi bi-x-circle me-1"></i>BUKTI TIDAK VALID</span>
                                        <?php elseif($row['sisa_tagihan'] > 0): ?>
                                            <div class="tagihan-belum-lunas">Rp <?= number_format($row['sisa_tagihan'], 0, ',', '.') ?></div>
                                        <?php else: ?>
                                            <div class="tagihan-lunas">LUNAS</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-status <?= $badge_class ?>">
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="pesanan_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-dark fw-bold btn-detail-pesanan">
                                            DETAIL
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 empty-state-pesanan">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Belum ada pesanan terdaftar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>