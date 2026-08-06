<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul            = trim($_POST['judul'] ?? '');
    $target_pelanggan = $_POST['target_pelanggan'] ?? 'semua';
    $kode_promo       = trim(strtoupper($_POST['kode_promo'] ?? ''));
    $diskon_persen    = (int)($_POST['diskon_persen'] ?? 0);
    $deskripsi        = trim($_POST['deskripsi'] ?? '');
    $tgl_mulai        = $_POST['tgl_mulai'] ?? '';
    $tgl_selesai      = $_POST['tgl_selesai'] ?? '';
    $status           = $_POST['status'] ?? 'aktif';

    if (empty($judul)) {
        $error = 'Judul diskon wajib diisi.';
    } elseif ($target_pelanggan == 'semua' && empty($kode_promo)) {
        $error = 'Kode Promo wajib diisi untuk pelanggan umum.';
    } elseif (!empty($tgl_mulai) && !empty($tgl_selesai) && $tgl_mulai > $tgl_selesai) {
        $error = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
    } else {
        $tgl_mulai_val   = empty($tgl_mulai) ? NULL : $tgl_mulai;
        $tgl_selesai_val = empty($tgl_selesai) ? NULL : $tgl_selesai;

        // Pastikan kode promo dikosongkan jika targetnya adalah pengguna baru
        if ($target_pelanggan == 'pengguna_baru') {
            $kode_promo = ''; 
        }

        $stmt = mysqli_prepare($koneksi, "INSERT INTO informasi_diskon (judul, target_pelanggan, kode_promo, diskon_persen, deskripsi, tgl_mulai, tgl_selesai, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssissss', $judul, $target_pelanggan, $kode_promo, $diskon_persen, $deskripsi, $tgl_mulai_val, $tgl_selesai_val, $status);

        if (mysqli_stmt_execute($stmt)) {
            
            // --- LOGIKA NOTIFIKASI DIEKSEKUSI JIKA SIMPAN PROMO BERHASIL ---
            $judul_notif = "Ada Promo Baru Jashit!";
            $pesan_notif = "Jangan lewatkan diskon terbaru: " . $judul . ". Yuk buruan cek dan pesan sekarang!";
            
            // Kirim notifikasi ke SEMUA pelanggan (Looping)
            $q_users = mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'pelanggan'");
            while($u = mysqli_fetch_assoc($q_users)) {
                $uid = $u['id'];
                mysqli_query($koneksi, "INSERT INTO notifikasi (user_id, judul, pesan) VALUES ($uid, '$judul_notif', '$pesan_notif')");
            }

            $_SESSION['flash_success'] = 'Informasi Diskon & Promo berhasil ditambahkan!';
            header('Location: informasi.php');
            exit();
        } else {
            $error = 'Gagal menyimpan data ke database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Diskon — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/informasi.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Tambah Diskon Baru</h1>
                <a href="informasi.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <div class="form-card-wrap shadow-sm">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="font-size: 14px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label form-label-custom">Judul Diskon/Promo <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" required autofocus>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label form-label-custom">Target Pelanggan <span class="text-danger">*</span></label>
                            <select name="target_pelanggan" class="form-select" required>
                                <option value="semua">Semua Pelanggan (Pakai Kode)</option>
                                <option value="pengguna_baru">Khusus Pengguna Baru (Otomatis)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-custom">Kode Promo <span class="text-muted fw-normal">(Boleh kosong untuk pengguna baru)</span></label>
                            <input type="text" name="kode_promo" class="form-control" placeholder="Cth: LEBARAN10" style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-custom">Besaran Diskon (%) <span class="text-danger">*</span></label>
                            <input type="number" name="diskon_persen" class="form-control" value="0" min="0" max="100" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi & Syarat Ketentuan</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan detail potongan harga, syarat, dan ketentuan berlaku..."></textarea>
                    </div>

                    <div class="row mb-1">
                        <div class="col-12">
                            <label class="form-label-custom" style="color: #475569;">
                                Periode Berlaku <span class="text-xs fw-normal">(Kosongkan jika berlaku selamanya)</span>
                            </label>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <input type="date" name="tgl_mulai" class="form-control">
                            <small class="text-muted text-xs">Tanggal Mulai</small>
                        </div>
                        <div class="col-md-6">
                            <input type="date" name="tgl_selesai" class="form-control">
                            <small class="text-muted text-xs">Tanggal Selesai</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label form-label-custom">Status Penayangan <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif">Aktif (Ditampilkan ke pelanggan)</option>
                            <option value="nonaktif">Nonaktif (Sembunyikan)</option>
                        </select>
                    </div>

                    <!-- Tombol diatur menggunakan style dari informasi.css tapi backgroundnya dioverride agar selaras dengan desain aslimu -->
                    <button type="submit" class="btn btn-submit-custom w-100" style="background-color: var(--navy-dark);">
                        SIMPAN DATA DISKON
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Eksternal Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script (Memanggil file JS yang sama dengan halaman edit) -->
<script src="<?= BASE_URL ?>/assets/js/informasi_form.js"></script>

</body>
</html>