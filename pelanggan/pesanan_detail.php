<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$current_page = 'tracking.php'; 
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'riwayat.php') !== false) {
    $current_page = 'riwayat.php'; 
}

if (!isset($_GET['id'])) {
    header('Location: tracking.php');
    exit;
}

$id      = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$error   = '';

// --- PROSES PEMBATALAN PESANAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan_pesanan'])) {
    $q_cek = mysqli_query($koneksi, "SELECT status FROM pesanan WHERE id = $id AND user_id = $user_id");
    if ($row_cek = mysqli_fetch_assoc($q_cek)) {
        if ($row_cek['status'] === 'menunggu_konfirmasi') {
            $update = mysqli_query($koneksi, "UPDATE pesanan SET status = 'dibatalkan' WHERE id = $id");
            if ($update) {
                $_SESSION['flash_success'] = 'Pesanan berhasil dibatalkan.';
                header("Location: tracking.php");
                exit;
            } else {
                $error = 'Gagal membatalkan pesanan.';
            }
        } else {
            $error = 'Pesanan sudah diproses dan tidak dapat dibatalkan secara sepihak.';
        }
    }
}

// Ambil detail pesanan beserta status transaksi terakhir
$query  = "SELECT p.*, 
           (SELECT status_verifikasi FROM transaksi t WHERE t.pesanan_id = p.id ORDER BY t.id DESC LIMIT 1) as status_transaksi 
           FROM pesanan p WHERE p.id = $id AND p.user_id = $user_id LIMIT 1";
$result = mysqli_query($koneksi, $query);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    header('Location: tracking.php');
    exit;
}

// --- LOGIKA DETEKSI DISKON (HANYA VOUCHER) ---
$deskripsi_tampil = $pesanan['deskripsi'];
$ada_diskon       = false;
$teks_promo       = '';

if (!empty($pesanan['promo_dipakai'])) {
    $ada_diskon = true;
    $teks_promo = preg_replace('/\s*\[Total:\s*\d+%\]/i', '', $pesanan['promo_dipakai']);
    $teks_promo = trim($teks_promo);
} elseif (preg_match('/\[SYSTEM INFO:\s*(.*?)\]/i', $deskripsi_tampil, $matches)) {
    $ada_diskon       = true;
    $teks_promo       = trim($matches[1]);
    $deskripsi_tampil = trim(str_replace($matches[0], '', $deskripsi_tampil));
} elseif (strpos($deskripsi_tampil, '[Mendapat Diskon Pengguna Baru 10%]') !== false) {
    $ada_diskon       = true;
    $teks_promo       = 'Diskon Pengguna Baru 10%';
    $deskripsi_tampil = trim(str_replace('[Mendapat Diskon Pengguna Baru 10%]', '', $deskripsi_tampil));
}

// --- AMBIL PERSENTASE DISKON SECARA DINAMIS ---
$persen_diskon = 10; // default fallback
$promo_str     = $pesanan['promo_dipakai'] ?? '';
if (preg_match('/\[Total:\s*(\d+)%\]/i', $promo_str, $m_persen)) {
    $persen_diskon = (int)$m_persen[1];
} elseif (preg_match('/\((\d+)%\)/i', $promo_str, $m_persen)) {
    $persen_diskon = (int)$m_persen[1];
}
$divisor_detail = 1 - ($persen_diskon / 100);

// Format Status Real-time
$st       = $pesanan['status'];
$badge_bg = 'bg-primary';
if ($st === 'menunggu_konfirmasi')                              { $badge_bg = 'bg-warning text-dark'; }
elseif ($st === 'dibatalkan')                                   { $badge_bg = 'bg-danger'; }
elseif (in_array($st, ['selesai','siap_kirim','diambil']))      { $badge_bg = 'bg-success'; }
elseif (in_array($st, ['proses_cutting','proses_jahit','proses_finishing','quality_check'])) { $badge_bg = 'bg-info text-dark'; }

// DETEKSI LOGIKA BAHAN/AKSESORIS
$is_bahan     = (strpos($pesanan['ukuran'], 'Pembelian Bahan') !== false);
$is_aksesoris = (strpos($pesanan['ukuran'], 'Pembelian Aksesoris') !== false);
$satuan       = $is_bahan ? 'Meter' : 'Pcs';

$label_status = ($is_bahan || $is_aksesoris) && in_array($st, ['proses_cutting','proses_jahit','proses_finishing','quality_check'])
                ? ($is_aksesoris ? 'PENYIAPAN AKSESORIS' : 'PENYIAPAN BAHAN')
                : strtoupper(str_replace('_', ' ', $st));

// --- LOGIKA PERBAIKAN EKSTRAKSI WARNA PRODUKSI MASSAL ---
$warna_tampil = !empty($pesanan['warna']) && $pesanan['warna'] !== '-' ? $pesanan['warna'] : '';

if (empty($warna_tampil) || $warna_tampil === 'Multiwarna') {
    if (strpos($pesanan['ukuran'], '[PRODUKSI MASSAL]') !== false) {
        // Ambil teks warna di dalam tanda kurung () dari kolom ukuran
        if (preg_match_all('/\((.*?)\)/', $pesanan['ukuran'], $matches)) {
            $warna_bersih = array_filter(array_map('trim', $matches[1]));
            $warna_unik   = array_unique($warna_bersih);
            $warna_tampil = !empty($warna_unik) ? implode(', ', $warna_unik) : 'Multiwarna'; 
        } else {
            $warna_tampil = 'Multiwarna';
        }
    } else {
        $warna_tampil = '-';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pesanan — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .detail-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .detail-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .detail-value { font-size: 15px; color: #1e293b; font-weight: 600; margin-bottom: 20px; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Detail Pesanan</h1>
                <a href="<?= $current_page ?>" class="btn btn-outline-secondary btn-sm fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="detail-card">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <div>
                                <h4 style="font-family: monospace; font-weight: 800; color: var(--navy-dark); margin: 0;"><?= $pesanan['kode_pesanan'] ?></h4>
                                <span class="text-muted" style="font-size: 13px;">Dipesan pada: <?= date('d F Y', strtotime($pesanan['tanggal_pesan'])) ?></span>
                            </div>
                            <div>
                                <span class="badge <?= $badge_bg ?> px-3 py-2 shadow-sm" style="font-size: 12px; letter-spacing: 0.5px;">
                                    <?= $label_status ?>
                                </span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-label"><?= $is_bahan ? 'Jenis Bahan' : 'Layanan / Jenis Pakaian' ?></div>
                                <div class="detail-value"><?= htmlspecialchars($pesanan['jenis_pakaian']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-label">Jumlah</div>
                                <div class="detail-value"><?= htmlspecialchars($pesanan['jumlah']) ?> <?= $satuan ?></div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label"><?= $is_bahan ? 'Keterangan Pembelian' : 'Rincian Ukuran & Qty' ?></div>
                                <div class="detail-value p-3 bg-light rounded border" style="font-weight: normal; font-size: 14px; line-height: 1.6;">
                                    <?php 
                                    $ukuran_text = htmlspecialchars($pesanan['ukuran'] ?: '-');
                                    
                                    if (strpos($ukuran_text, '[PRODUKSI MASSAL]') !== false) {
                                        $ukuran_text = str_replace('[PRODUKSI MASSAL] ', '', $ukuran_text);
                                        $items = explode('|', $ukuran_text);
                                        echo '<div class="mb-2"><span class="badge bg-secondary" style="font-size:10px; letter-spacing: 0.5px;">PRODUKSI MASSAL</span></div>';
                                        foreach ($items as $item) {
                                            echo '<div style="padding: 4px 0; border-bottom: 1px dashed #cbd5e1;"><i class="bi bi-arrow-return-right text-muted me-2"></i><span class="fw-bold text-dark">' . trim($item) . '</span></div>';
                                        }
                                    } 
                                    elseif (strpos($ukuran_text, ', ') !== false && strpos($ukuran_text, 'LD:') !== false) {
                                        echo str_replace(', ', '<br><i class="bi bi-dot text-primary"></i> ', '<i class="bi bi-dot text-primary"></i> ' . $ukuran_text);
                                    } 
                                    else {
                                        echo nl2br($ukuran_text);
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-label">Warna / Corak Umum</div>
                                <div class="detail-value text-capitalize"><?= htmlspecialchars($warna_tampil) ?></div>
                            </div>
                            
                            <?php if (!$is_bahan): ?>
                            <div class="col-md-6">
                                <div class="detail-label">Bahan Baku</div>
                                <div class="detail-value"><?= htmlspecialchars($pesanan['bahan'] ?: 'Tidak ada keterangan') ?></div>
                            </div>
                            <?php endif; ?>
                            
                           <div class="col-md-6">
                                <div class="detail-label">Deadline Pesanan</div>
                                <div class="detail-value text-primary fw-bold">
                                    <?php 
                                    if (!empty($pesanan['tanggal_deadline']) && $pesanan['tanggal_deadline'] !== '0000-00-00' && $pesanan['tanggal_deadline'] !== '1970-01-01') {
                                        echo date('d F Y', strtotime($pesanan['tanggal_deadline']));
                                    } elseif (!empty($pesanan['tanggal_selesai']) && !in_array($pesanan['status'], ['selesai', 'siap_kirim', 'diambil'])) {
                                        echo date('d F Y', strtotime($pesanan['tanggal_selesai']));
                                    } else {
                                        echo '<span class="text-muted" style="font-size: 13px; font-weight: normal; font-style: italic;">Tidak ada request</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">Catatan / Deskripsi Tambahan</div>
                                <div class="detail-value p-3 bg-light rounded border" style="font-weight: normal; font-size: 14px;">
                                    <?= nl2br(htmlspecialchars($deskripsi_tampil ?: 'Tidak ada catatan khusus.')) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mt-4 mt-md-0">
                    <div class="detail-card">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Informasi Biaya</h5>
                        
                        <?php if($pesanan['total_harga'] == 0 && $pesanan['status'] != 'dibatalkan'): ?>
                            <div class="alert alert-secondary text-center" style="font-size: 13px;">
                                <i class="bi bi-hourglass-split me-1"></i> Admin sedang menghitung biaya Anda.
                            </div>
                        <?php else: ?>

                            <?php 
                            $harga_asli     = $pesanan['total_harga'];
                            $nominal_diskon = 0;
                            if ($ada_diskon) {
                                $harga_asli     = $divisor_detail > 0 ? ($pesanan['total_harga'] / $divisor_detail) : $pesanan['total_harga'];
                                $nominal_diskon = $harga_asli - $pesanan['total_harga'];
                            }
                            $harga_per_pcs = $pesanan['jumlah'] > 0 ? $harga_asli / $pesanan['jumlah'] : 0;
                            ?>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Harga per <?= $satuan ?>:</span>
                                <span class="text-dark fw-bold">Rp <?= number_format($harga_per_pcs, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal (<?= $pesanan['jumlah'] ?> <?= $satuan ?>):</span>
                                <span class="text-dark fw-bold">Rp <?= number_format($harga_asli, 0, ',', '.') ?></span>
                            </div>
                            <hr class="my-2">

                            <?php if ($ada_diskon): ?>
                                <div class="alert alert-success py-2 px-3 mb-3" style="font-size: 12px; border-radius: 6px;">
                                    <i class="bi bi-stars me-1"></i> <strong>Promo Diterapkan:</strong><br>
                                    <?= htmlspecialchars($teks_promo) ?>
                                    <span class="badge bg-success ms-1" style="font-size: 10px;"><?= $persen_diskon ?>% OFF</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span class="small fw-bold">Potongan Diskon (<?= $persen_diskon ?>%):</span>
                                    <span class="fw-bold">- Rp <?= number_format($nominal_diskon, 0, ',', '.') ?></span>
                                </div>
                                <hr class="my-2">
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Total Tagihan:</span>
                                <strong class="text-primary">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Sudah Dibayar:</span>
                                <strong class="text-primary">Rp <?= number_format($pesanan['dp_dibayar'], 0, ',', '.') ?></strong>
                            </div>
                            
                            <div class="mt-3 mb-2">
                                <?php 
                                $tot              = (int)$pesanan['total_harga'];
                                $dp               = (int)$pesanan['dp_dibayar'];
                                $sisa             = (int)$pesanan['sisa_tagihan'];
                                $transaksi_status = strtolower($pesanan['status_transaksi'] ?? '');

                                if ($transaksi_status === 'menunggu'): ?>
                                    <span class="badge bg-warning text-dark shadow-sm" style="font-size: 12px; padding: 6px 12px; width: 100%;">
                                        <i class="bi bi-hourglass-split me-1"></i> MENUNGGU KONFIRMASI ADMIN
                                    </span>
                                <?php elseif ($tot > 0 && $sisa <= 0): ?>
                                    <span class="badge bg-success" style="font-size: 12px; padding: 6px 12px; width: 100%;"><i class="bi bi-check-circle me-1"></i> PEMBAYARAN LUNAS</span>
                                <?php elseif ($dp > 0 && $sisa > 0): ?>
                                    <span class="badge bg-info text-dark" style="font-size: 12px; padding: 6px 12px; width: 100%;">UANG MUKA (DP) TERBAYAR</span>
                                <?php elseif ($transaksi_status == 'ditolak' || $transaksi_status == 'tidak valid'): ?>
                                    <span class="badge bg-danger" style="font-size: 12px; padding: 6px 12px; width: 100%;"><i class="bi bi-x-circle me-1"></i> BUKTI TERAKHIR DITOLAK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger" style="font-size: 12px; padding: 6px 12px; width: 100%;">BELUM BAYAR</span>
                                <?php endif; ?>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark small">Sisa Tagihan:</span>
                                <strong class="text-danger fs-5">Rp <?= number_format($pesanan['sisa_tagihan'], 0, ',', '.') ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($pesanan['status'] === 'menunggu_konfirmasi'): ?>
                            <form action="" method="POST" class="mt-4" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini? Aksi ini tidak dapat diubah.');">
                                <input type="hidden" name="batalkan_pesanan" value="1">
                                <button type="submit" class="btn btn-outline-danger w-100 fw-bold py-2">
                                    <i class="bi bi-x-circle me-1"></i> Batalkan Pesanan
                                </button>
                            </form>
                            <div class="text-muted text-center mt-2" style="font-size: 11px;">Pesanan hanya bisa dibatalkan sebelum dikonfirmasi Admin.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>