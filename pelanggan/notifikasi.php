<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];

// 1. Ambil data notifikasi pelanggan ini (sebelum di-update agar ketahuan mana yang baru)
$query = "SELECT * FROM notifikasi WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($koneksi, $query);

$notifikasi_list = [];
while($row = mysqli_fetch_assoc($result)) {
    $notifikasi_list[] = $row;
}

// 2. Ubah semua notifikasi menjadi "Sudah Dibaca" (is_read = 1) saat halaman ini dibuka
mysqli_query($koneksi, "UPDATE notifikasi SET is_read = 1 WHERE user_id = $user_id AND is_read = 0");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .notif-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .notif-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        /* Style untuk notifikasi yang BELUM dibaca */
        .notif-baru {
            background: #f0fdf4 !important; /* Warna hijau sangat muda (Soft Green) */
            border-color: #bbf7d0 !important;
        }
        .notif-baru::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #10b981; /* Garis hijau di pinggir kiri */
        }
        .notif-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .icon-promo { background: #fef08a; color: #ca8a04; }
        .icon-bayar { background: #dbeafe; color: #2563eb; }
        .icon-umum { background: #f1f5f9; color: #64748b; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 800; color: var(--navy-dark); margin: 0;">Kotak Masuk Notifikasi</h1>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <?php if (count($notifikasi_list) > 0): ?>
                        <?php foreach ($notifikasi_list as $notif): 
                            
                            // Deteksi tipe notif dari judulnya untuk menentukan Ikon
                            $judul_lower = strtolower($notif['judul']);
                            if (strpos($judul_lower, 'promo') !== false || strpos($judul_lower, 'diskon') !== false) {
                                $icon_class = 'icon-promo';
                                $icon_bi = 'bi-tags-fill';
                            } elseif (strpos($judul_lower, 'pembayaran') !== false) {
                                $icon_class = 'icon-bayar';
                                $icon_bi = 'bi-wallet2';
                            } else {
                                $icon_class = 'icon-umum';
                                $icon_bi = 'bi-bell-fill';
                            }

                            // Cek apakah pesan ini baru dibaca saat ini
                            $is_baru = ($notif['is_read'] == 0) ? 'notif-baru' : '';
                        ?>
                            <div class="notif-card <?= $is_baru ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="notif-icon <?= $icon_class ?> flex-shrink-0">
                                        <i class="bi <?= $icon_bi ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 16px;">
                                                <?= htmlspecialchars($notif['judul']) ?>
                                                <?php if($notif['is_read'] == 0): ?>
                                                    <span class="badge bg-danger ms-2" style="font-size: 10px;">BARU</span>
                                                <?php endif; ?>
                                            </h6>
                                            <span class="text-muted" style="font-size: 12px;">
                                                <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($notif['created_at'])) ?>
                                            </span>
                                        </div>
                                        <p class="mb-0 text-secondary" style="font-size: 14px; line-height: 1.5;">
                                            <?= nl2br(htmlspecialchars($notif['pesan'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert text-center py-5" style="background-color: #fff; border: 1px dashed #cbd5e1; border-radius: 12px;">
                            <i class="bi bi-envelope-open fs-1 text-muted d-block mb-3"></i>
                            <h6 class="fw-bold text-dark">Belum ada notifikasi</h6>
                            <p class="text-muted small mb-0">Semua pemberitahuan tentang pesanan dan promo akan muncul di sini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>