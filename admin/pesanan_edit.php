<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (!isset($_GET['id'])) {
    header('Location: pesanan.php');
    exit;
}

$id = (int)$_GET['id'];
$error = '';

$query = "SELECT p.*, u.nama AS nama_pelanggan, u.id AS id_pelanggan, u.no_hp 
          FROM pesanan p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE p.id = $id LIMIT 1";
$result = mysqli_query($koneksi, $query);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    header('Location: pesanan.php');
    exit;
}

// -----------------------------------------------------------------------------
// LOGIKA PENDETEKSI: JENIS PEMBELIAN
$teks_ukuran = strtoupper($pesanan['ukuran']);
$is_massal   = (strpos($teks_ukuran, 'RINCIAN MASSAL') !== false || strpos($teks_ukuran, '[PRODUKSI MASSAL]') !== false);
$is_bahan    = (strpos($teks_ukuran, 'PEMBELIAN BAHAN') !== false);
$is_aksesoris = (strpos($teks_ukuran, 'PEMBELIAN AKSESORIS') !== false);
// -----------------------------------------------------------------------------

// AMBIL DATA LAYANAN UNTUK DROPDOWN JENIS PAKAIAN
$layanan_res = mysqli_query($koneksi, "SELECT nama_layanan FROM layanan WHERE status = 'Aktif' ORDER BY nama_layanan ASC");

// SINKRONISASI: Ambil dari tanggal_deadline
$tgl_deadline_tampil = $pesanan['tanggal_deadline'];
if ((empty($tgl_deadline_tampil) || $tgl_deadline_tampil === '0000-00-00') && !empty($pesanan['tanggal_selesai']) && $pesanan['status'] !== 'selesai') {
    $tgl_deadline_tampil = $pesanan['tanggal_selesai'];
}

if (empty($tgl_deadline_tampil) || $tgl_deadline_tampil === '0000-00-00') {
    $tgl_deadline_tampil = ''; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_pakaian    = trim($_POST['jenis_pakaian'] ?? '');
    if ($jenis_pakaian === 'custom_manual_input') {
        $jenis_pakaian = trim($_POST['jenis_pakaian_manual'] ?? '');
    }

    $jumlah           = (int)($_POST['jumlah'] ?? 1);
    $deskripsi        = trim($_POST['deskripsi'] ?? '');
    
    // Kembalikan format newline (enter) ke delimiter pipeline saat disimpan ke DB
    $ukuran_input     = trim($_POST['ukuran'] ?? '');
    $ukuran           = str_replace(["\r\n", "\r", "\n"], ' | ', $ukuran_input);
    
    if ($is_massal) {
        $warna = '-';
    } else {
        $warna = trim($_POST['warna'] ?? '');
    }

    $bahan            = trim($_POST['bahan'] ?? '');
    $total_harga      = (int)($_POST['total_harga'] ?? 0);
    $dp_dibayar       = (int)($_POST['dp_dibayar'] ?? 0);
    
    if ($dp_dibayar > $total_harga) {
        $dp_dibayar = $total_harga;
    }
    
    $sisa_tagihan     = $total_harga - $dp_dibayar;
    $tanggal_deadline = !empty($_POST['tanggal_deadline']) ? $_POST['tanggal_deadline'] : NULL;
    $status           = $_POST['status'] ?? 'menunggu_konfirmasi';

    $tanggal_selesai = NULL; 
    if ($status === 'selesai') {
        $tanggal_selesai = date('Y-m-d'); 
    }

    if ($dp_dibayar <= 0) {
        $status_pembayaran = 'belum_bayar';
    } elseif ($sisa_tagihan <= 0) {
        $status_pembayaran = 'lunas';
    } else {
        $status_pembayaran = 'dp';
    }

    if ($status === 'selesai' && $sisa_tagihan > 0) {
        $error = 'Gagal menyimpan! Pelanggan harus melunasi sisa tagihan (Rp ' . number_format($sisa_tagihan, 0, ',', '.') . ') sebelum pesanan bisa diselesaikan.';
    } elseif (empty($jenis_pakaian) || $total_harga <= 0) {
        $error = 'Jenis pakaian dan total harga wajib diisi dengan benar.';
    } else {
        $update_query = "UPDATE pesanan SET 
            jenis_pakaian=?, jumlah=?, deskripsi=?, ukuran=?, warna=?, bahan=?, 
            total_harga=?, dp_dibayar=?, sisa_tagihan=?, status_pembayaran=?, 
            tanggal_deadline=?, status=?, tanggal_selesai=? WHERE id=?";
            
        $stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($stmt, 'sissssiiissssi', 
            $jenis_pakaian, $jumlah, $deskripsi, $ukuran, $warna, $bahan,
            $total_harga, $dp_dibayar, $sisa_tagihan, $status_pembayaran,
            $tanggal_deadline, $status, $tanggal_selesai, $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $baru_selesai = ($status === 'selesai' && $pesanan['status'] !== 'selesai');

            if ($baru_selesai && !empty($pesanan['id_pelanggan'])) {
                $uid_pelanggan = $pesanan['id_pelanggan'];
                $kode          = $pesanan['kode_pesanan'];
                $judul_notif   = 'Pesanan Selesai!';
                $pesan_notif   = "Yeay! Pesanan Anda dengan kode $kode sudah selesai dikerjakan dan siap diambil. Silakan datang ke Jashit untuk mengambilnya ya!";
                mysqli_query($koneksi, "INSERT INTO notifikasi (user_id, judul, pesan) VALUES ($uid_pelanggan, '$judul_notif', '$pesan_notif')");
            }

            if ($status === 'selesai' && $status_pembayaran === 'lunas' && $pesanan['poin_diberikan'] == 0) {
                $poin_didapat = floor($total_harga / 10000); 
                
                if ($poin_didapat > 0 && !empty($pesanan['id_pelanggan'])) {
                    $id_pelanggan = $pesanan['id_pelanggan'];
                    mysqli_query($koneksi, "UPDATE users SET poin = poin + $poin_didapat WHERE id = '$id_pelanggan'");
                    mysqli_query($koneksi, "UPDATE pesanan SET poin_diberikan = 1 WHERE id = '$id'");
                    
                    $_SESSION['flash_success'] = "Pesanan Selesai! Pelanggan mendapatkan $poin_didapat Poin Reward.";
                } else {
                    $_SESSION['flash_success'] = 'Pesanan selesai diperbarui! (Walk-in tidak mendapat poin)';
                }
            } else {
                $_SESSION['flash_success'] = 'Data pesanan berhasil diperbarui!';
            }

            header('Location: pesanan.php');
            exit();
        } else {
            $error = 'Gagal mengupdate pesanan di database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pesanan — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/pesanan_admin.css" rel="stylesheet">
</head>
<body class="pesanan-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_admin.php'; ?>

        <div class="dashboard-content pesanan-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="pesanan-page-title">Edit Pesanan</h1>
                <a href="pesanan.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-pesanan"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="section-card shadow-sm p-4 mb-4 pesanan-section-card">
                            <h5 class="mb-4 pesanan-section-title">Informasi Pakaian</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Kode Pesanan</label>
                                    <input type="text" class="form-control bg-light fw-bold text-primary" value="<?= htmlspecialchars($pesanan['kode_pesanan']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Nama Pelanggan</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($pesanan['nama_pelanggan'] ?? 'Walk-in (' . $pesanan['user_id'] . ')') ?>" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Jenis Pakaian / Layanan <span class="text-danger">*</span></label>
                                <select name="jenis_pakaian" id="jenis_pakaian" class="form-select fw-bold border-secondary" required onchange="cekCustomJenis()">
                                    <option value="">-- Pilih Dari Katalog --</option>
                                    <?php 
                                    $jenis_ditemukan = false;
                                    while($l = mysqli_fetch_assoc($layanan_res)): 
                                        $selected = ($pesanan['jenis_pakaian'] == $l['nama_layanan']) ? 'selected' : '';
                                        if ($selected) $jenis_ditemukan = true;
                                    ?>
                                        <option value="<?= htmlspecialchars($l['nama_layanan']) ?>" <?= $selected ?>><?= htmlspecialchars($l['nama_layanan']) ?></option>
                                    <?php endwhile; ?>
                                    
                                    <?php if(!$jenis_ditemukan && !empty($pesanan['jenis_pakaian'])): ?>
                                        <option value="<?= htmlspecialchars($pesanan['jenis_pakaian']) ?>" selected><?= htmlspecialchars($pesanan['jenis_pakaian']) ?> (Layanan Lama)</option>
                                    <?php endif; ?>
                                    
                                    <option value="custom_manual_input">-- Ketik Manual Lainnya --</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 d-none" id="area_jenis_manual">
                                <input type="text" name="jenis_pakaian_manual" class="form-control" placeholder="Ketik jenis pakaian...">
                            </div>

                            <hr class="my-4">

                            <div class="p-3 mb-4 pesanan-subcard-alt">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 pesanan-subcard-title">Detail Ukuran & Bahan</h6>
                                    <?php if($is_massal): ?>
                                        <span class="badge bg-secondary badge-xs">PRODUKSI MASSAL</span>
                                    <?php elseif($is_bahan): ?>
                                        <span class="badge bg-info text-dark badge-xs">MATERIAL/BAHAN</span>
                                    <?php elseif($is_aksesoris): ?>
                                        <span class="badge bg-warning text-dark badge-xs">AKSESORIS</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Jumlah (<?= $is_bahan ? 'Meter' : 'Pcs' ?>)</label>
                                        <input type="number" name="jumlah" class="form-control" value="<?= htmlspecialchars($pesanan['jumlah']) ?>" min="1">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold small text-primary">Data Ukuran</label>
                                        <?php 
                                        $ukuran_edit = str_replace(' | ', "\n", $pesanan['ukuran']); 
                                        ?>
                                        <textarea name="ukuran" class="form-control textarea-mono" rows="6" placeholder="S, M, L atau LD: 100cm..."><?= htmlspecialchars($ukuran_edit) ?></textarea>
                                        <small class="text-muted helper-text-xs">Form ini merangkum detail individu maupun rincian warna/qty produksi massal.</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <?php if (!$is_massal): ?>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Warna Kain / Corak</label>
                                            <input type="text" name="warna" class="form-control" value="<?= htmlspecialchars($pesanan['warna']) ?>" placeholder="Contoh: Putih">
                                        </div>
                                        <div class="col-md-6">
                                    <?php else: ?>
                                        <div class="col-md-12">
                                    <?php endif; ?>
                                        <label class="form-label fw-bold small">Bahan Kain / Spesifikasi</label>
                                        <input type="text" name="bahan" class="form-control" value="<?= htmlspecialchars($pesanan['bahan']) ?>" placeholder="Contoh: Katun">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Deskripsi / Detail Request Tambahan</label>
                                <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($pesanan['deskripsi']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="section-card shadow-sm p-4 mb-4 pesanan-section-card pesanan-section-card-sticky">
                            <h5 class="mb-4 pesanan-section-title">Pembayaran & Progres</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Total Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="total_harga" id="total_harga" class="form-control form-control-lg fw-bold text-primary" value="<?= $pesanan['total_harga'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">DP / Total Dibayar (Rp)</label>
                                <input type="number" name="dp_dibayar" id="dp_dibayar" class="form-control" value="<?= $pesanan['dp_dibayar'] ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-danger">Sisa Tagihan (Rp)</label>
                                <input type="number" id="sisa_tagihan" class="form-control bg-light fw-bold text-danger" value="<?= $pesanan['sisa_tagihan'] ?>" readonly>
                            </div>

                            <hr class="hr-pesanan">

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Tanggal Deadline</label>
                                <input type="date" name="tanggal_deadline" class="form-control" value="<?= $tgl_deadline_tampil ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small">Progres Pengerjaan (Status) <span class="text-danger">*</span></label>
                                <select name="status" class="form-select fw-bold border-primary select-status-bg" required>
                                    <option value="menunggu_konfirmasi" <?= $pesanan['status'] == 'menunggu_konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                    <option value="dikonfirmasi" <?= $pesanan['status'] == 'dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                    
                                    <?php if($is_bahan): ?>
                                        <option value="proses_cutting" <?= $pesanan['status'] == 'proses_cutting' ? 'selected' : '' ?>>Penyiapan Bahan</option>
                                    <?php elseif($is_aksesoris): ?>
                                        <option value="proses_cutting" <?= $pesanan['status'] == 'proses_cutting' ? 'selected' : '' ?>>Penyiapan Aksesoris</option>
                                    <?php else: ?>
                                        <option value="proses_cutting" <?= $pesanan['status'] == 'proses_cutting' ? 'selected' : '' ?>>Proses Cutting</option>
                                        <option value="proses_jahit" <?= $pesanan['status'] == 'proses_jahit' ? 'selected' : '' ?>>Proses Jahit</option>
                                        <option value="proses_finishing" <?= $pesanan['status'] == 'proses_finishing' ? 'selected' : '' ?>>Proses Finishing</option>
                                        <option value="quality_check" <?= $pesanan['status'] == 'quality_check' ? 'selected' : '' ?>>Quality Check (QC)</option>
                                    <?php endif; ?>
                                    
                                    <option value="selesai" <?= $pesanan['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan" <?= $pesanan['status'] == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                            </div>

                            <button type="submit" class="btn w-100 py-3 fw-bold shadow-sm btn-simpan-pesanan">
                                <i class="bi bi-save me-1"></i> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/pesanan_admin.js"></script>
</body>
</html>