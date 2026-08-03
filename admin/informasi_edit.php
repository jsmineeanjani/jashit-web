<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (!isset($_GET['id'])) {
    header('Location: informasi.php');
    exit;
}

$id = (int)$_GET['id'];
$error = '';

$stmt = mysqli_prepare($koneksi, "SELECT * FROM informasi_diskon WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$diskon = mysqli_fetch_assoc($result);

if (!$diskon) {
    header('Location: informasi.php');
    exit;
}

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

        $update = mysqli_prepare($koneksi, "UPDATE informasi_diskon SET judul=?, target_pelanggan=?, kode_promo=?, diskon_persen=?, deskripsi=?, tgl_mulai=?, tgl_selesai=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($update, 'sssissssi', $judul, $target_pelanggan, $kode_promo, $diskon_persen, $deskripsi, $tgl_mulai_val, $tgl_selesai_val, $status, $id);

        if (mysqli_stmt_execute($update)) {
            $_SESSION['flash_success'] = 'Data diskon berhasil diperbarui!';
            header('Location: informasi.php');
            exit();
        } else {
            $error = 'Gagal mengupdate data di database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Diskon — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Edit Data Diskon</h1>
                <a href="informasi.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <div class="section-card" style="max-width: 650px; background: #fff; border: 1px solid #e2e8f0; padding: 30px;">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="font-size: 14px;"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Judul Diskon/Promo <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($_POST['judul'] ?? $diskon['judul']) ?>" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Target Pelanggan <span class="text-danger">*</span></label>
                            <select name="target_pelanggan" class="form-select" required>
                                <option value="semua" <?= $diskon['target_pelanggan'] == 'semua' ? 'selected' : '' ?>>Semua Pelanggan (Pakai Kode)</option>
                                <option value="pengguna_baru" <?= $diskon['target_pelanggan'] == 'pengguna_baru' ? 'selected' : '' ?>>Khusus Pengguna Baru (Otomatis)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Kode Promo <span class="text-muted fw-normal">(Boleh kosong untuk pengguna baru)</span></label>
                            <input type="text" name="kode_promo" class="form-control" value="<?= htmlspecialchars($_POST['kode_promo'] ?? $diskon['kode_promo']) ?>" placeholder="Cth: LEBARAN10" style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Besaran Diskon (%) <span class="text-danger">*</span></label>
                            <input type="number" name="diskon_persen" class="form-control" value="<?= (int)($_POST['diskon_persen'] ?? $diskon['diskon_persen']) ?>" min="0" max="100" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Deskripsi & Syarat Ketentuan</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? $diskon['deskripsi']) ?></textarea>
                    </div>

                    <div class="row mb-1">
                        <div class="col-12">
                            <label style="font-size: 13px; font-weight: 600; color: #475569;">Periode Berlaku <span style="font-weight: 400; font-size: 11px;">(Kosongkan jika berlaku selamanya)</span></label>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($_POST['tgl_mulai'] ?? $diskon['tgl_mulai']) ?>">
                            <small class="text-muted" style="font-size: 11px;">Tanggal Mulai</small>
                        </div>
                        <div class="col-md-6">
                            <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($_POST['tgl_selesai'] ?? $diskon['tgl_selesai']) ?>">
                            <small class="text-muted" style="font-size: 11px;">Tanggal Selesai</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Status Penayangan <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif" <?= ($diskon['status'] == 'aktif') ? 'selected' : '' ?>>Aktif (Ditampilkan ke pelanggan)</option>
                            <option value="nonaktif" <?= ($diskon['status'] == 'nonaktif') ? 'selected' : '' ?>>Nonaktif (Sembunyikan)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn w-100" style="background-color: #ea580c; color: white; border: none; padding: 12px; font-weight: 600; letter-spacing: 0.5px;">
                        UPDATE DATA DISKON
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>