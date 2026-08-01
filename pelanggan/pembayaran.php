<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];
$error = '';

// --- PROSES UPLOAD BUKTI PEMBAYARAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bukti'])) {
    $pesanan_id = (int)$_POST['pesanan_id'];
    $jenis_pembayaran = $_POST['jenis_pembayaran'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $nominal = (int)$_POST['nominal'];
    
    // Validasi apakah pesanan ini benar milik user yang login
    $q_valid = mysqli_query($koneksi, "SELECT id FROM pesanan WHERE id = $pesanan_id AND user_id = $user_id");
    if(mysqli_num_rows($q_valid) > 0) {
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
            $nama_file = 'bukti_' . time() . '_' . rand(100,999) . '.' . $ext;
            
            if (move_uploaded_file($_FILES['bukti']['tmp_name'], '../assets/img/bukti/' . $nama_file)) {
                mysqli_query($koneksi, "INSERT INTO transaksi (pesanan_id, jenis_pembayaran, metode_pembayaran, nominal, bukti_pembayaran, status_verifikasi) VALUES ($pesanan_id, '$jenis_pembayaran', '$metode_pembayaran', $nominal, '$nama_file', 'Menunggu')");
                
                $_SESSION['flash_success'] = 'Bukti pembayaran terkirim! Silakan tunggu konfirmasi Admin.';
                header('Location: tracking.php');
                exit();
            } else {
                $error = "Gagal mengunggah file bukti pembayaran.";
            }
        }
    } else {
        $error = "Pesanan tidak valid.";
    }
}

// AMBIL DATA PESANAN BERDASARKAN ID
if (!isset($_GET['id'])) {
    header('Location: tracking.php');
    exit;
}

$id_pesanan = (int)$_GET['id'];
$query = "SELECT * FROM pesanan WHERE id = $id_pesanan AND user_id = $user_id LIMIT 1";
$result = mysqli_query($koneksi, $query);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    header('Location: tracking.php');
    exit;
}

$is_bahan = (strpos($pesanan['ukuran'], 'Pembelian Bahan') !== false);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>
        
        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 800; color: var(--navy-dark); margin: 0;">Pembayaran</h1>
                <a href="tracking.php" class="btn btn-outline-secondary btn-sm fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-7 mx-auto">
                    <div class="section-card shadow-sm p-4" style="background:#fff; border-radius:12px;">
                        
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <div class="text-muted small fw-bold mb-1">KODE PESANAN</div>
                            <h4 style="font-family: monospace; font-weight: 800; color: var(--navy-dark);"><?= $pesanan['kode_pesanan'] ?></h4>
                            <div class="mt-2 text-danger fw-bold">Sisa Tagihan: Rp <?= number_format($pesanan['sisa_tagihan'], 0, ',', '.') ?></div>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="upload_bukti" value="1">
                            <input type="hidden" name="pesanan_id" value="<?= $pesanan['id'] ?>">
                            
                            <div class="row mb-3">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <label class="form-label fw-bold small">Tipe Pembayaran</label>
                                    <select name="jenis_pembayaran" id="jenis_pembayaran" class="form-select" onchange="ubahNominal()"></select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold small">Metode Bayar</label>
                                    <select name="metode_pembayaran" id="pilihMetode" class="form-select" onchange="toggleInfoBayar()">
                                        <option value="Transfer Bank">Transfer Bank</option>
                                        <option value="QRIS">QRIS</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small">Nominal (Rp)</label>
                                <input type="number" name="nominal" id="input_nominal" class="form-control form-control-lg fw-bold text-primary" required>
                                <small id="bantuan_nominal" class="text-muted mt-1 d-block" style="font-size:12px;"></small>
                            </div>

                            <div id="infoTransfer" class="alert p-3 mb-4" style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                                <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Silakan Transfer ke Rekening:</div>
                                <div style="font-size: 20px; font-weight: 800; color: var(--navy-dark); letter-spacing: 1px;">BCA 123 456 7890</div>
                                <div style="font-size: 14px; font-weight: 600; color: #334155;">a.n. JASHIT KONVEKSI</div>
                            </div>

                            <div id="infoQris" class="alert text-center p-3 mb-4" style="display: none; background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 10px; font-weight: bold;">Scan QRIS Jashit di Bawah Ini:</div>
                                <img src="<?= BASE_URL ?>/assets/img/qris.jpg" alt="QRIS Jashit" class="img-fluid shadow-sm" style="max-width: 200px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 8px;">Pastikan nama penerima JASHIT KONVEKSI</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small">Upload Bukti Transfer / Screenshoot <span class="text-danger">*</span></label>
                                <input type="file" name="bukti" class="form-control" accept="image/*" required>
                                <div class="form-text" style="font-size: 11px;">Format JPG/PNG. Pastikan tanggal dan nominal terlihat jelas.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="background-color:var(--navy-dark); border:none; letter-spacing: 0.5px;">
                                <i class="bi bi-send me-2"></i> KIRIM BUKTI PEMBAYARAN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Data dilempar dari PHP ke JavaScript
    let gSisa = <?= $pesanan['sisa_tagihan'] ?>;
    let gTotal = <?= $pesanan['total_harga'] ?>;
    let isBahan = <?= $is_bahan ? 'true' : 'false' ?>;

    document.addEventListener("DOMContentLoaded", function() {
        let selectJenis = document.getElementById('jenis_pembayaran');
        
        if (isBahan) {
            selectJenis.innerHTML = '<option value="Pelunasan">Bayar Lunas (Full)</option>';
            selectJenis.value = 'Pelunasan';
        } else {
            if(gSisa === gTotal) {
                selectJenis.innerHTML = '<option value="DP">DP (Minimal 50%)</option><option value="Pelunasan">Bayar Lunas (Full)</option>';
                selectJenis.value = 'DP';
            } else {
                selectJenis.innerHTML = '<option value="Pelunasan">Pelunasan Sisa Tagihan</option>';
                selectJenis.value = 'Pelunasan';
            }
        }

        ubahNominal();
        toggleInfoBayar();
    });

    function ubahNominal() {
        let jenis = document.getElementById('jenis_pembayaran').value;
        let inputNominal = document.getElementById('input_nominal');
        let bantuan = document.getElementById('bantuan_nominal');
        
        if (jenis === 'DP') {
            let minDp = Math.ceil(gTotal / 2);
            inputNominal.value = minDp;
            inputNominal.min = minDp; 
            inputNominal.max = gSisa; 
            inputNominal.readOnly = false; 
            bantuan.innerHTML = "<i class='bi bi-info-circle text-primary'></i> Minimal DP 50% (Rp " + minDp.toLocaleString('id-ID') + "). Anda bisa mengubah nominal jika ingin DP lebih besar.";
        } else {
            inputNominal.value = gSisa;
            inputNominal.min = gSisa;
            inputNominal.max = gSisa;
            inputNominal.readOnly = true; 
            bantuan.innerHTML = "<i class='bi bi-check-circle text-success'></i> Nominal sudah disesuaikan dengan tagihan Anda.";
        }
    }

    function toggleInfoBayar() {
        var metode = document.getElementById("pilihMetode").value;
        var infoTransfer = document.getElementById("infoTransfer");
        var infoQris = document.getElementById("infoQris");

        if (metode === "Transfer Bank") {
            infoTransfer.style.display = "block";
            infoQris.style.display = "none";
        } else if (metode === "QRIS") {
            infoTransfer.style.display = "none";
            infoQris.style.display = "block";
        }
    }
</script>
</body>
</html>