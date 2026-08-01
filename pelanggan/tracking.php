<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];
$error = '';

// --- PROSES PEMBATALAN PESANAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan_pesanan'])) {
    $id_batal = (int)$_POST['pesanan_id_batal'];
    $q_cek = mysqli_query($koneksi, "SELECT status FROM pesanan WHERE id = $id_batal AND user_id = $user_id");
    if ($row_cek = mysqli_fetch_assoc($q_cek)) {
        if ($row_cek['status'] === 'menunggu_konfirmasi') {
            $update = mysqli_query($koneksi, "UPDATE pesanan SET status = 'dibatalkan' WHERE id = $id_batal");
            if ($update) {
                $_SESSION['flash_success'] = 'Pesanan berhasil dibatalkan.';
                header("Location: tracking.php");
                exit;
            }
        }
    }
}

$tanggal_hari_ini = date('Y-m-d');
$query = "SELECT p.*, 
          (SELECT status_verifikasi FROM transaksi t WHERE t.pesanan_id = p.id ORDER BY t.id DESC LIMIT 1) as status_transaksi 
          FROM pesanan p 
          WHERE p.user_id = $user_id 
          AND p.status != 'dibatalkan'
          AND (p.status != 'selesai' OR (p.status = 'selesai' AND DATE(p.tanggal_selesai) = '$tanggal_hari_ini'))
          ORDER BY p.id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesanan Saya — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .track-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 25px; overflow: hidden; }
        .track-header { background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; }
        .track-body { padding: 30px 24px; }
        .timeline-steps { display: flex; justify-content: space-between; position: relative; margin-bottom: 40px; padding: 0 10px; }
        .timeline-steps::before { content: ''; position: absolute; top: 18px; left: 0; width: 100%; height: 2px; background: #e2e8f0; z-index: 1; }
        .step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .step-icon { width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #cbd5e1; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 14px; border: 2px solid #e2e8f0; }
        .step.done .step-icon { background: #10b981; color: #fff; border-color: #10b981; }
        .step.active .step-icon { background: var(--navy-dark); color: #fff; border-color: var(--navy-dark); }
        .step-text { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
        .step.done .step-text { color: #10b981; }
        .step.active .step-text { color: var(--navy-dark); }
        .status-badge-realtime { font-size: 12px; padding: 6px 12px; font-weight: 700; border-radius: 50px; text-transform: uppercase; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>
        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 800; color: var(--navy-dark); margin: 0;">Pesanan Aktif Saya</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Pantau proses pesanan jahitan, bahan, atau aksesoris Anda saat ini</p>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $s    = $row['status'];
                    $sisa = $row['sisa_tagihan'];
                    
                    // --- DETEKSI PROMO / DISKON (Hanya Voucher) ---
                    $ada_diskon = false;
                    if (!empty($row['promo_dipakai'])) {
                        $ada_diskon = true;
                    } elseif (preg_match('/\[SYSTEM INFO:\s*(.*?)\]/i', $row['deskripsi'])) {
                        $ada_diskon = true;
                    } elseif (strpos($row['deskripsi'], '[Mendapat Diskon Pengguna Baru 10%]') !== false) {
                        $ada_diskon = true;
                    }

                    // --- AMBIL PERSENTASE DISKON SECARA DINAMIS ---
                    $persen_diskon = 10; // default fallback
                    $promo_str     = $row['promo_dipakai'] ?? '';
                    if (preg_match('/\[Total:\s*(\d+)%\]/i', $promo_str, $m_persen)) {
                        $persen_diskon = (int)$m_persen[1];
                    } elseif (preg_match('/\((\d+)%\)/i', $promo_str, $m_persen)) {
                        $persen_diskon = (int)$m_persen[1];
                    }
                    $divisor_tracking = 1 - ($persen_diskon / 100);

                    // Deteksi jenis pesanan
                    $is_bahan           = (strpos($row['ukuran'], 'Pembelian Bahan') !== false);
                    $is_aksesoris       = (strpos($row['ukuran'], 'Pembelian Aksesoris') !== false);
                    $is_barang_langsung = ($is_bahan || $is_aksesoris); 
                    
                    if ($is_bahan)           { $satuan = 'Meter'; }
                    elseif ($is_aksesoris)   { $satuan = 'Pcs';   }
                    else                     { $satuan = 'Pcs';   }
                    
                    // Logika Progress Bar
                    $step1 = ($s != 'menunggu_konfirmasi') ? 'done' : 'active';
                    $step2 = (in_array($s, ['dikonfirmasi','proses_cutting','proses_jahit','proses_finishing','quality_check','selesai'])) ? (($s == 'dikonfirmasi') ? 'active' : 'done') : '';
                    
                    if ($is_barang_langsung) {
                        $step3 = (in_array($s, ['proses_cutting','proses_jahit','proses_finishing','quality_check','selesai'])) ? ((in_array($s, ['proses_cutting','proses_jahit','proses_finishing','quality_check'])) ? 'active' : 'done') : '';
                    } else {
                        $step3 = (in_array($s, ['proses_cutting','proses_jahit','proses_finishing','quality_check','selesai'])) ? ((in_array($s, ['proses_cutting','proses_jahit','proses_finishing'])) ? 'active' : 'done') : '';
                    }
                    
                    $step4 = (in_array($s, ['quality_check','selesai'])) ? (($s == 'quality_check') ? 'active' : 'done') : '';
                    $step5 = ($s == 'selesai' && $sisa <= 0) ? 'active' : '';
                    
                    $transaksi_status = strtolower($row['status_transaksi'] ?? '');
                ?>
                    <div class="track-card shadow-sm">
                        <div class="track-header">
                            <div>
                                <span class="text-muted small fw-bold">KODE PESANAN</span>
                                <div style="font-family: monospace; font-size: 18px; font-weight: 800; color: var(--navy-dark);"><?= $row['kode_pesanan'] ?></div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small fw-bold d-block mb-1">STATUS SAAT INI</span>
                                <span class="status-badge-realtime bg-primary text-white shadow-sm">
                                    <?php 
                                        if ($is_barang_langsung && in_array($s, ['proses_cutting','proses_jahit','proses_finishing','quality_check'])) {
                                            echo $is_aksesoris ? 'PENYIAPAN AKSESORIS' : 'PENYIAPAN BAHAN';
                                        } else {
                                            echo str_replace('_', ' ', strtoupper($s));
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="track-body">
                            <div class="timeline-steps">
                                <div class="step <?= $step1 ?>"><div class="step-icon"><i class="bi bi-file-earmark"></i></div><div class="step-text">Antrean</div></div>
                                <div class="step <?= $step2 ?>"><div class="step-icon"><i class="bi bi-check2"></i></div><div class="step-text">Konfirmasi</div></div>
                                
                                <?php if ($is_barang_langsung): ?>
                                    <div class="step <?= $step3 ?>"><div class="step-icon"><i class="bi bi-box-seam"></i></div><div class="step-text"><?= $is_aksesoris ? 'Penyiapan Aksesoris' : 'Penyiapan Bahan' ?></div></div>
                                <?php else: ?>
                                    <div class="step <?= $step3 ?>"><div class="step-icon"><i class="bi bi-scissors"></i></div><div class="step-text">Produksi</div></div>
                                    <div class="step <?= $step4 ?>"><div class="step-icon"><i class="bi bi-search"></i></div><div class="step-text">Quality Control</div></div>
                                <?php endif; ?>
                                
                                <div class="step <?= $step5 ?>"><div class="step-icon"><i class="bi bi-check-circle"></i></div><div class="step-text">Selesai</div></div>
                            </div>

                            <div class="row align-items-center mt-3 pt-3 border-top">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($row['jenis_pakaian']) ?> (<?= $row['jumlah'] ?> <?= $satuan ?>)</h6>
                                    
                                    <?php if ($ada_diskon && $row['total_harga'] > 0):
                                        // Hitung harga asli sebelum diskon voucher secara dinamis
                                        $harga_asli_tracking = $divisor_tracking > 0
                                            ? ($row['total_harga'] / $divisor_tracking)
                                            : $row['total_harga'];

                                        // Buat label badge
                                        $label_badge = 'Promo';
                                        if (!empty($promo_str)) {
                                            if (stripos($promo_str, 'Voucher') !== false) {
                                                $label_badge = 'Voucher ' . $persen_diskon . '%';
                                            } else {
                                                $label_badge = 'Diskon ' . $persen_diskon . '%';
                                            }
                                        }
                                    ?>
                                        <div class="mt-1">
                                            <span class="text-muted text-decoration-line-through small me-1">Rp <?= number_format($harga_asli_tracking, 0, ',', '.') ?></span>
                                            <span class="badge bg-success" style="font-size: 10px; padding: 3px 6px;">
                                                <i class="bi bi-tags-fill me-1"></i><?= $label_badge ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($row['total_harga'] > 0): ?>
                                        <div class="text-danger fw-bold mt-1" style="font-size: 14px;">Sisa Tagihan: Rp <?= number_format($row['sisa_tagihan'], 0, ',', '.') ?></div>
                                    <?php else: ?>
                                        <div class="text-muted fw-bold mt-1" style="font-size: 13px; font-style: italic;"><i class="bi bi-hourglass-split me-1"></i> Menunggu Kalkulasi Admin</div>
                                    <?php endif; ?>
                                    
                                    <?php if($transaksi_status === 'menunggu'): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-warning text-dark px-2 py-1 shadow-sm" style="font-size: 11px;">
                                                <i class="bi bi-hourglass-split me-1"></i> Menunggu Konfirmasi Admin
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($s === 'quality_check' && $row['sisa_tagihan'] > 0 && $transaksi_status !== 'menunggu' && !$is_barang_langsung): ?>
                                        <div class="alert alert-danger mt-3 mb-0 py-2" style="font-size: 13px; border-radius: 6px;">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> <strong>Wajib Pelunasan!</strong> Pesanan Anda telah memasuki tahap QC. Mohon segera lunasi sisa tagihan agar pesanan bisa Selesai.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_barang_langsung && in_array($s, ['proses_cutting','proses_jahit','proses_finishing','quality_check']) && $row['sisa_tagihan'] > 0 && $transaksi_status !== 'menunggu'): ?>
                                        <div class="alert alert-danger mt-3 mb-0 py-2" style="font-size: 13px; border-radius: 6px;">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> <strong>Wajib Pelunasan!</strong> <?= $is_aksesoris ? 'Aksesoris' : 'Bahan' ?> pesanan Anda siap dikirim/diambil. Mohon segera lunasi sisa tagihan.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                    <div class="d-flex gap-2 justify-content-md-end">
                                        <?php if($row['sisa_tagihan'] > 0 && $transaksi_status !== 'menunggu'): ?>
                                            <a href="pembayaran.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning fw-bold px-3 shadow-sm"><i class="bi bi-wallet2 me-1"></i> Bayar</a>
                                        <?php elseif($transaksi_status === 'menunggu'): ?>
                                            <button class="btn btn-sm btn-secondary fw-bold px-3 shadow-sm" disabled><i class="bi bi-clock me-1"></i> Sedang Diproses</button>
                                        <?php endif; ?>
                                        
                                        <a href="pesanan_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary fw-bold px-3"><i class="bi bi-info-circle me-1"></i> Detail</a>
                                        
                                        <?php if($s === 'menunggu_konfirmasi'): ?>
                                            <form action="" method="POST" class="d-inline form-batal-pesanan">
                                                <input type="hidden" name="batalkan_pesanan" value="1">
                                                <input type="hidden" name="pesanan_id_batal" value="<?= $row['id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold px-3 btn-batal"><i class="bi bi-x-circle me-1"></i> Batal</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert text-center py-5" style="background-color: #fff; border: 1px dashed #cbd5e1; border-radius: 12px;">
                    <i class="bi bi-box-seam fs-1 text-muted d-block mb-3"></i>
                    <h6 class="fw-bold text-dark">Tidak Ada Pesanan Aktif</h6>
                    <p class="text-muted small mb-0">Kamu belum memiliki pesanan yang sedang diproses. <br>Pesanan yang sudah selesai dapat dilihat di menu <strong>Riwayat</strong>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
    $(document).ready(function() {
        $('.btn-batal').on('click', function(e) {
            e.preventDefault();
            let form = $(this).closest('.form-batal-pesanan');
            Swal.fire({
                title: 'Batalkan Pesanan?',
                text: "Apakah Anda yakin ingin membatalkan pesanan ini? Aksi ini akan menghapus pesanan Anda permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', 
                cancelButtonColor: '#64748b', 
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) { form.submit(); }
            });
        });
    });
</script>
</body>
</html>