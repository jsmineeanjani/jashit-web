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
    <style>
        .stat-card { background: #fff; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .stat-card .icon-bg { position: absolute; right: -10px; bottom: -15px; font-size: 90px; opacity: 0.05; }
        .card-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; }
        .badge-status { font-size: 10px; letter-spacing: 0.5px; padding: 6px 10px; text-transform: uppercase; font-weight: 700; border-radius: 4px; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 800; color: var(--navy-dark); margin: 0;">Halo, <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Pelanggan') ?>!</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Pantau terus progres jahitanmu di sini.</p>
            </div>

            <div class="row mb-5">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card shadow-sm" style="border-top: 4px solid var(--navy-dark);">
                        <div class="text-muted" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Total Pesanan</div>
                        <div style="font-size: 28px; font-weight: 800; color: var(--navy-dark); margin-top: 5px;"><?= $stat_total ?></div>
                        <i class="bi bi-bag-check-fill icon-bg" style="color: var(--navy-dark);"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card shadow-sm" style="border-top: 4px solid #3b82f6;">
                        <div class="text-muted" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Sedang Diproses</div>
                        <div style="font-size: 28px; font-weight: 800; color: #3b82f6; margin-top: 5px;"><?= $stat_aktif ?></div>
                        <i class="bi bi-scissors icon-bg" style="color: #3b82f6;"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card shadow-sm" style="border-top: 4px solid #ef4444;">
                        <div class="text-muted" style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Belum Lunas</div>
                        <div style="font-size: 28px; font-weight: 800; color: #ef4444; margin-top: 5px;"><?= $stat_tagihan ?></div>
                        <i class="bi bi-wallet2 icon-bg" style="color: #ef4444;"></i>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="font-weight: 800; color: var(--navy-dark); margin: 0; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;">Aktivitas Pesanan Terbaru</h5>
                <a href="tracking.php" class="text-decoration-none fw-bold" style="font-size: 13px; color: var(--navy-dark);">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="card-table-wrap shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="font-size: 11px; color: #64748b; padding: 15px; font-weight: 700;">KODE & TGL</th>
                                <th style="font-size: 11px; color: #64748b; padding: 15px; font-weight: 700;">PAKAIAN</th>
                                <th style="font-size: 11px; color: #64748b; padding: 15px; font-weight: 700;">TAGIHAN & PEMBAYARAN</th>
                                <th style="font-size: 11px; color: #64748b; padding: 15px; font-weight: 700;" class="text-center">STATUS PRODUKSI</th>
                                <th style="font-size: 11px; color: #64748b; padding: 15px; font-weight: 700;" class="text-center">AKSI</th>
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
                                        <div style="font-weight: 800; color: var(--navy-dark); font-family: monospace; font-size: 14px;"><?= $row['kode_pesanan'] ?></div>
                                        <div style="font-size: 11px; color: #94a3b8; font-weight: 600;"><?= date('d M Y', strtotime($row['tanggal_pesan'])) ?></div>
                                    </td>
                                    <td class="align-middle">
                                        <div style="font-size: 14px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($row['jenis_pakaian']) ?></div>
                                        <div style="font-size: 12px; color: #64748b;">Jumlah: <?= $row['jumlah'] ?> Pcs</div>
                                    </td>
                                    <td class="align-middle">
                                        <?php if($transaksi_status === 'ditolak' || $transaksi_status === 'tidak valid'): ?>
                                            <div style="font-weight: 800; color: #ef4444; font-size: 14px;">Rp <?= number_format($row['sisa_tagihan'], 0, ',', '.') ?></div>
                                            <span class="badge bg-danger mt-1" style="font-size: 10px; letter-spacing: 0.5px;"><i class="bi bi-x-circle me-1"></i>BUKTI TIDAK VALID</span>
                                        <?php elseif($row['sisa_tagihan'] > 0): ?>
                                            <div style="font-weight: 800; color: #ef4444; font-size: 14px;">Rp <?= number_format($row['sisa_tagihan'], 0, ',', '.') ?></div>
                                        <?php else: ?>
                                            <div style="font-weight: 800; color: #10b981; font-size: 14px;">LUNAS</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-status <?= $badge_class ?>">
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="pesanan_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-dark fw-bold" style="font-size: 11px; padding: 5px 12px;">
                                            DETAIL
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5" style="color: #94a3b8;">
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