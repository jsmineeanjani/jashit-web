<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (!isset($_GET['id'])) {
    header('Location: pesanan.php');
    exit;
}

$id = (int)$_GET['id'];

$query = "SELECT p.*, u.nama AS nama_pelanggan, u.no_hp, u.email, 
          (SELECT status_verifikasi FROM transaksi t WHERE t.pesanan_id = p.id ORDER BY t.id DESC LIMIT 1) as status_transaksi 
          FROM pesanan p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE p.id = $id LIMIT 1";
$result = mysqli_query($koneksi, $query);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location.href='pesanan.php';</script>";
    exit;
}

// -----------------------------------------------------------------------------
// FALLBACK PELANGGAN WALK-IN: kolom u.nama/u.no_hp akan NULL kalau pesanan
// dibuat lewat pesanan_tambah.php untuk nomor HP yang belum terdaftar sebagai
// user (lihat pesanan_tambah.php: user_id diisi nomor HP langsung untuk kasus ini).
$nama_pelanggan_tampil = $pesanan['nama_pelanggan'];
$deskripsi_tampil      = $pesanan['deskripsi'];
$no_hp_tampil          = $pesanan['no_hp'];

if (empty($nama_pelanggan_tampil)) {
    if (preg_match('/\[Pelanggan Walk-in:\s*(.+?)\]/i', (string)$pesanan['deskripsi'], $m)) {
        $nama_pelanggan_tampil = trim($m[1]);
        // Bersihkan tag walk-in dari deskripsi supaya tidak tampil dobel di nota
        $deskripsi_tampil = trim(preg_replace('/\[Pelanggan Walk-in:.*?\]\s*/i', '', (string)$pesanan['deskripsi']));
    } else {
        $nama_pelanggan_tampil = 'Pelanggan Walk-in';
    }
}

if (empty($no_hp_tampil)) {
    // Untuk walk-in, p.user_id menyimpan nomor HP langsung
    $no_hp_tampil = $pesanan['user_id'];
}

// -----------------------------------------------------------------------------
// LOGIKA PENDETEKSI: JENIS PEMBELIAN
$teks_ukuran  = strtoupper($pesanan['ukuran']);
$is_bahan     = (strpos($teks_ukuran, 'PEMBELIAN BAHAN') !== false);
$is_aksesoris = (strpos($teks_ukuran, 'PEMBELIAN AKSESORIS') !== false);
$satuan       = $is_bahan ? 'Meter' : 'Pcs';

// -----------------------------------------------------------------------------
// LOGIKA EKSTRAKSI WARNA (Khusus Massal yang Warnanya Kosong/Strip di Database)
$warna_tampil = !empty($pesanan['warna']) && $pesanan['warna'] !== '-' ? $pesanan['warna'] : '';

if (empty($warna_tampil) || $warna_tampil === 'Multiwarna') {
    if (strpos($pesanan['ukuran'], '[PRODUKSI MASSAL]') !== false) {
        // Regex cerdas: Ambil teks dalam kurung yang posisinya TEPAT SEBELUM tanda '='
        // Ini mencegah salah ambil jika di ukuran juga ada tanda kurung (cth: Ld 100cm)
        if (preg_match_all('/\(([^)]+)\)\s*=/i', $pesanan['ukuran'], $matches)) {
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
// -----------------------------------------------------------------------------

function formatStatus($status, $is_bahan, $is_aksesoris) {
    switch ($status) {
        case 'menunggu_konfirmasi': return '<span class="badge bg-secondary">Menunggu Konfirmasi</span>';
        case 'dikonfirmasi': return '<span class="badge bg-light text-dark border">Dikonfirmasi</span>';
        case 'proses_cutting': 
            if ($is_bahan) return '<span class="badge bg-primary">Penyiapan Bahan</span>';
            if ($is_aksesoris) return '<span class="badge bg-primary">Penyiapan Aksesoris</span>';
            return '<span class="badge bg-primary">Proses Cutting</span>';
        case 'proses_jahit': 
            if ($is_bahan || $is_aksesoris) return '<span class="badge badge-jahit-proses">Penyiapan (Lanjutan)</span>';
            return '<span class="badge badge-jahit-proses">Proses Jahit</span>';
        case 'proses_finishing': 
            if ($is_bahan || $is_aksesoris) return '<span class="badge badge-finishing-proses">Penyiapan (Lanjutan)</span>';
            return '<span class="badge badge-finishing-proses">Proses Finishing</span>';
        case 'quality_check': 
            if ($is_bahan || $is_aksesoris) return '<span class="badge bg-warning text-dark">Penyiapan (Lanjutan)</span>';
            return '<span class="badge bg-warning text-dark">Quality Check (QC)</span>';
        case 'siap_kirim': return '<span class="badge bg-info text-dark">Siap Kirim / Diambil</span>';
        case 'selesai': return '<span class="badge bg-success">Selesai</span>';
        case 'dibatalkan': return '<span class="badge bg-danger">Dibatalkan</span>';
        default: return '<span class="badge bg-secondary">'.$status.'</span>';
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
    <link href="<?= BASE_URL ?>/assets/css/pesanan_admin.css" rel="stylesheet">
</head>
<body class="pesanan-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_admin.php'; ?>

        <div class="dashboard-content pesanan-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="pesanan-page-title btn-kembali">Detail Pesanan</h1>
                <div>
                    <a href="pesanan.php" class="btn btn-outline-secondary btn-sm me-2 btn-kembali"><i class="bi bi-arrow-left"></i> Kembali</a>
                    <button onclick="window.print()" class="btn btn-sm btn-cetak-nota">
                        <i class="bi bi-printer me-1"></i> Cetak Nota
                    </button>
                </div>
            </div>

            <div class="invoice-card invoice-card-wrap mx-auto">
                <div class="invoice-header d-flex justify-content-between align-items-center">
                    <div>
                        <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" class="invoice-logo">
                        <div class="invoice-subtitle">Layanan Jahit Konveksi Profesional</div>
                    </div>
                    <div class="text-end">
                        <h4 class="invoice-title">INVOICE</h4>
                        <div class="invoice-kode"><?= htmlspecialchars($pesanan['kode_pesanan']) ?></div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="detail-label mb-1">Ditagihkan Kepada:</div>
                        <div class="detail-value fs-16"><?= htmlspecialchars($nama_pelanggan_tampil) ?></div>
                        <div class="invoice-phone"><i class="bi bi-telephone"></i> <?= htmlspecialchars($no_hp_tampil) ?></div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="row text-start text-md-end">
                            <div class="col-4 col-md-12 mb-2">
                                <div class="detail-label">Tanggal Pesan</div>
                                <div class="detail-value fs-14"><?= date('d F Y', strtotime($pesanan['tanggal_pesan'])) ?></div>
                            </div>
                            <div class="col-4 col-md-12 mb-2">
                                <div class="detail-label">Tanggal Diminta</div>
                                <div class="detail-value invoice-tgl-diminta">
                                    <?php 
                                    if (!empty($pesanan['tanggal_deadline']) && $pesanan['tanggal_deadline'] !== '0000-00-00' && $pesanan['tanggal_deadline'] !== '1970-01-01') {
                                        echo date('d F Y', strtotime($pesanan['tanggal_deadline']));
                                    } 
                                    elseif (!empty($pesanan['tanggal_selesai']) && !in_array($pesanan['status'], ['selesai', 'siap_kirim', 'diambil'])) {
                                        echo date('d F Y', strtotime($pesanan['tanggal_selesai']));
                                    } else {
                                        echo '<span class="text-muted invoice-placeholder-text">Tidak ada request</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-4 col-md-12">
                                <div class="detail-label">Tanggal Selesai Pesanan</div>
                                <div class="detail-value text-danger invoice-tgl-selesai">
                                    <?php 
                                    if (!empty($pesanan['tanggal_selesai']) && $pesanan['tanggal_selesai'] !== '0000-00-00' && in_array($pesanan['status'], ['selesai', 'siap_kirim', 'diambil'])) {
                                        echo date('d F Y', strtotime($pesanan['tanggal_selesai']));
                                    } else {
                                        echo '<span class="text-muted invoice-placeholder-text">Belum Selesai</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered mb-0">
                        <thead class="table-head-bg">
                            <tr>
                                <th class="detail-label text-center" width="5%">No</th>
                                <th class="detail-label" width="45%">Deskripsi Layanan / Item</th>
                                <th class="detail-label text-center" width="25%">Spesifikasi</th>
                                <th class="detail-label text-center" width="10%">Qty</th>
                                <th class="detail-label text-end" width="15%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center align-middle">1</td>
                                <td>
                                    <div class="invoice-item-nama"><?= htmlspecialchars($pesanan['jenis_pakaian']) ?></div>
                                    <div class="invoice-item-desc">
                                        "<?= htmlspecialchars($deskripsi_tampil ?: 'Tidak ada catatan tambahan') ?>"
                                    </div>
                                </td>
                                <td class="align-middle fs-13" style="color: #475569;">
                                    <div class="mb-1 text-uppercase invoice-rincian-label">Rincian Ukuran & Qty:</div>
                                    <div class="p-2 mb-2 invoice-rincian-box">
                                        <?php 
                                        $ukuran_text = htmlspecialchars($pesanan['ukuran'] ?: '-');
                                        
                                        if (strpos($ukuran_text, '[PRODUKSI MASSAL]') !== false) {
                                            $ukuran_text = str_replace('[PRODUKSI MASSAL] ', '', $ukuran_text);
                                            $items = explode('|', $ukuran_text);
                                            echo '<div class="invoice-massal-header">[PRODUKSI MASSAL]</div>';
                                            echo '<ul style="margin: 0; padding-left: 16px;" class="invoice-text-dark-bold">';
                                            foreach ($items as $item) {
                                                echo '<li class="invoice-massal-li">' . trim($item) . '</li>';
                                            }
                                            echo '</ul>';
                                        } elseif (strpos($ukuran_text, ', ') !== false && strpos($ukuran_text, 'LD:') !== false) {
                                            echo '<span class="invoice-text-dark-bold">' . str_replace(', ', '<br>', $ukuran_text) . '</span>';
                                        } else {
                                            echo '<span class="invoice-text-dark-bold">' . nl2br($ukuran_text) . '</span>';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Menampilkan variabel $warna_tampil yang sudah diekstrak cerdas -->
                                    <div><strong>Warna:</strong> <span class="text-dark fw-bold text-capitalize"><?= htmlspecialchars($warna_tampil) ?></span></div>
                                    <?php if(!$is_bahan): ?>
                                        <div><strong>Bahan Kain:</strong> <span class="text-dark fw-bold"><?= htmlspecialchars($pesanan['bahan'] ?: '-') ?></span></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle fw-bold"><?= $pesanan['jumlah'] ?> <?= $satuan ?></td>
                                <td class="text-end align-middle fw-bold">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="detail-label mb-2">Status Pesanan:</div>
                        <div class="mb-3"><?= formatStatus($pesanan['status'], $is_bahan, $is_aksesoris) ?></div>
                        
                        <div class="detail-label mb-2">Status Pembayaran:</div>
                        <div class="mb-3">
                            <?php 
                            $tot = (int)$pesanan['total_harga'];
                            $dp  = (int)$pesanan['dp_dibayar'];
                            $sisa = (int)$pesanan['sisa_tagihan'];
                            $transaksi_status = strtolower($pesanan['status_transaksi'] ?? '');

                            if($tot > 0 && $sisa <= 0): ?>
                                <span class="badge bg-success badge-lg-pill"><i class="bi bi-check-circle me-1"></i> LUNAS</span>
                            <?php elseif($dp > 0 && $sisa > 0): ?>
                                <span class="badge bg-info text-dark badge-lg-pill">UANG MUKA (DP)</span>
                            <?php elseif($transaksi_status == 'ditolak' || $transaksi_status == 'tidak valid'): ?>
                                <span class="badge bg-danger badge-lg-pill"><i class="bi bi-x-circle me-1"></i> BUKTI DITOLAK / TIDAK VALID</span>
                            <?php else: ?>
                                <span class="badge bg-danger badge-lg-pill">BELUM BAYAR</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($pesanan['promo_dipakai'])): ?>
                            <div class="detail-label mb-2">Voucher / Promo Digunakan:</div>
                            <div>
                                <span class="badge text-white badge-promo">
                                    <i class="bi bi-ticket-perforated me-1"></i> <?= htmlspecialchars($pesanan['promo_dipakai']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-end detail-label align-middle fs-13">Total Tagihan :</td>
                                <td class="text-end fw-bold invoice-total-tagihan" style="width: 40%;">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <tr style="border-bottom: 2px solid #e2e8f0;">
                                <td class="text-end detail-label align-middle fs-13">Telah Dibayar (DP) :</td>
                                <td class="text-end fw-bold invoice-dp-value">Rp <?= number_format($pesanan['dp_dibayar'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="text-end detail-label align-middle pt-2 invoice-sisa-label">SISA TAGIHAN :</td>
                                <td class="text-end fw-bold pt-2 invoice-sisa-value">Rp <?= number_format($pesanan['sisa_tagihan'], 0, ',', '.') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="text-center mt-5 invoice-footer-note">
                    Terima kasih telah mempercayakan layanan jahit Anda kepada Jashit.<br>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>