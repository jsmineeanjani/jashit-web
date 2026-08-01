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
    <style>
        .promo-card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column; }
        .promo-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: #cbd5e1; }
        .promo-img { width: 100%; height: 200px; object-fit: cover; background-color: #f1f5f9; }
        .promo-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .promo-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-top: 12px; margin-bottom: 10px; line-height: 1.4; }
        .promo-desc { font-size: 13.5px; color: #475569; margin-bottom: 20px; line-height: 1.6; }
        .promo-date { margin-top: auto; font-size: 12px; color: #64748b; padding-top: 15px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .btn-gunakan { background-color: var(--peach-soft); color: var(--navy-dark); font-weight: 600; font-size: 12px; padding: 6px 14px; border-radius: 6px; transition: 0.2s; text-decoration: none;}
        .btn-gunakan:hover { background-color: #f2c6aa; color: var(--navy-dark); }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 700; color: var(--navy-dark); margin: 0;">Promo & Diskon Spesial</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Nikmati berbagai penawaran menarik khusus untuk Anda</p>
            </div>

            <div class="row g-4">
                <?php 
                $ada_promo = false;
                if ($result && mysqli_num_rows($result) > 0) {
                    $sekarang = date('Y-m-d');
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        // FILTER: Lewati promo jika tanggal selesai sudah lewat
                        if (!empty($row['tgl_selesai']) && $row['tgl_selesai'] != '0000-00-00' && $sekarang > $row['tgl_selesai']) {
                            continue; 
                        }
                        $ada_promo = true;
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="promo-card shadow-sm">
                            <?php if(!empty($row['gambar'])): ?>
                                <img src="<?= BASE_URL ?>/assets/img/promo/<?= $row['gambar'] ?>" class="promo-img" alt="<?= htmlspecialchars($row['judul']) ?>">
                            <?php else: ?>
                                <div class="promo-img d-flex align-items-center justify-content-center" style="color: #94a3b8; background: linear-gradient(45deg, #f8fafc, #e2e8f0);">
                                    <i class="bi bi-ticket-perforated" style="font-size: 50px; color: #cbd5e1;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="promo-body">
                                <div>
                                    <?php if($row['target_pelanggan'] == 'pengguna_baru'): ?>
                                        <span class="badge bg-info text-dark shadow-sm" style="font-size: 11px; letter-spacing: 0.5px;"><i class="bi bi-stars me-1"></i> KHUSUS PENGGUNA BARU</span>
                                    <?php else: ?>
                                        <span class="badge bg-success shadow-sm" style="font-size: 11px; letter-spacing: 0.5px;"><i class="bi bi-tag-fill me-1"></i> SEMUA PELANGGAN</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="promo-title">
                                    <?= htmlspecialchars($row['judul']) ?>
                                </h5>
                                
                                <div class="promo-desc">
                                    <?= nl2br(htmlspecialchars($row['deskripsi'] ?? 'Dapatkan penawaran spesial ini untuk pesanan jahitan Anda.')) ?>
                                </div>
                                
                                <div class="promo-date">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-calendar-check fs-5 text-primary"></i>
                                        <span>
                                        <?php 
                                        if (empty($row['tgl_mulai']) && empty($row['tgl_selesai'])) {
                                            echo '<strong>Berlaku Selamanya</strong>';
                                        } else {
                                            $mulai = !empty($row['tgl_mulai']) ? date('d M Y', strtotime($row['tgl_mulai'])) : 'Sekarang';
                                            $selesai = (!empty($row['tgl_selesai']) && $row['tgl_selesai'] != '0000-00-00') ? date('d M Y', strtotime($row['tgl_selesai'])) : 'Seterusnya';
                                            echo "<strong style='color: #1e293b;'>$mulai - $selesai</strong>";
                                        }
                                        ?>
                                        </span>
                                    </div>
                                    <a href="katalog.php" class="btn btn-sm btn-gunakan">Klaim Promo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    } 
                }
                
                // Tampilan jika tabel kosong atau semua promo sudah kedaluwarsa
                if (!$ada_promo): 
                ?>
                    <div class="col-12">
                        <div class="alert text-center py-5" style="background-color: #fff; border: 1px dashed #cbd5e1; border-radius: 12px;">
                            <i class="bi bi-ticket-detailed fs-1 text-muted d-block mb-3"></i>
                            <h6 class="fw-bold text-dark">Belum Ada Promo Aktif</h6>
                            <p class="text-muted small mb-0">Nantikan diskon dan penawaran menarik dari Jashit selanjutnya!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>