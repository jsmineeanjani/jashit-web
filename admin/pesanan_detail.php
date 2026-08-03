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
          JOIN users u ON p.user_id = u.id 
          WHERE p.id = $id LIMIT 1";
$result = mysqli_query($koneksi, $query);
$pesanan = mysqli_fetch_assoc($result);

if (!$pesanan) {
    echo "<script>alert('Data pesanan tidak ditemukan!'); window.location.href='pesanan.php';</script>";
    exit;
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
            if ($is_bahan || $is_aksesoris) return '<span class="badge" style="background-color:#3b82f6;">Penyiapan (Lanjutan)</span>';
            return '<span class="badge" style="background-color:#3b82f6;">Proses Jahit</span>';
        case 'proses_finishing': 
            if ($is_bahan || $is_aksesoris) return '<span class="badge" style="background-color:#6366f1;">Penyiapan (Lanjutan)</span>';
            return '<span class="badge" style="background-color:#6366f1;">Proses Finishing</span>';
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
    <style>
        .invoice-card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .invoice-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; }
        .detail-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-size: 15px; color: #1e293b; font-weight: 600; }
        
        @media print {
            body * { visibility: hidden; }
            .invoice-card, .invoice-card * { visibility: visible; }
            .invoice-card { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; padding: 20px; }
            .btn-cetak, .btn-kembali, .sidebar, .topbar { display: none !important; }
            body { background-color: #fff !important; }
        }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title btn-kembali" style="font-size: 22px; font-weight: 700; margin: 0;">Detail Pesanan</h1>
                <div>
                    <a href="pesanan.php" class="btn btn-outline-secondary btn-sm me-2 btn-kembali"><i class="bi bi-arrow-left"></i> Kembali</a>
                    <button onclick="window.print()" class="btn btn-sm btn-cetak" style="background-color: var(--navy-dark); color: #fff; border: none; font-weight: 600;">
                        <i class="bi bi-printer me-1"></i> Cetak Nota
                    </button>
                </div>
            </div>

            <div class="invoice-card mx-auto" style="max-width: 850px;">
                <div class="invoice-header d-flex justify-content-between align-items-center">
                    <div>
                          <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" style="max-width: 120px; height: auto; margin-bottom: 8px;">
                        <div style="font-size: 13px; color: #64748b; margin-top: 5px;">Layanan Jahit Konveksi Profesional</div>
                    </div>
                    <div class="text-end">
                        <h4 style="font-weight: 700; color: #334155; margin: 0;">INVOICE</h4>
                        <div style="font-size: 14px; font-weight: 600; color: #ea580c; font-family: monospace;"><?= htmlspecialchars($pesanan['kode_pesanan']) ?></div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="detail-label mb-1">Ditagihkan Kepada:</div>
                        <div class="detail-value" style="font-size: 16px;"><?= htmlspecialchars($pesanan['nama_pelanggan']) ?></div>
                        <div style="font-size: 13px; color: #475569;"><i class="bi bi-telephone"></i> <?= htmlspecialchars($pesanan['no_hp']) ?></div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="row text-start text-md-end">
                            <div class="col-4 col-md-12 mb-2">
                                <div class="detail-label">Tanggal Pesan</div>
                                <div class="detail-value" style="font-size: 14px;"><?= date('d F Y', strtotime($pesanan['tanggal_pesan'])) ?></div>
                            </div>
                            <div class="col-4 col-md-12 mb-2">
                                <div class="detail-label">Tanggal Diminta</div>
                                <div class="detail-value" style="font-size: 14px; color: #0284c7;">
                                    <?php 
                                    if (!empty($pesanan['tanggal_deadline']) && $pesanan['tanggal_deadline'] !== '0000-00-00' && $pesanan['tanggal_deadline'] !== '1970-01-01') {
                                        echo date('d F Y', strtotime($pesanan['tanggal_deadline']));
                                    } 
                                    elseif (!empty($pesanan['tanggal_selesai']) && !in_array($pesanan['status'], ['selesai', 'siap_kirim', 'diambil'])) {
                                        echo date('d F Y', strtotime($pesanan['tanggal_selesai']));
                                    } else {
                                        echo '<span class="text-muted" style="font-size:12px; font-style:italic;">Tidak ada request</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-4 col-md-12">
                                <div class="detail-label">Tanggal Selesai Pesanan</div>
                                <div class="detail-value text-danger" style="font-size: 14px;">
                                    <?php 
                                    if (!empty($pesanan['tanggal_selesai']) && $pesanan['tanggal_selesai'] !== '0000-00-00' && in_array($pesanan['status'], ['selesai', 'siap_kirim', 'diambil'])) {
                                        echo date('d F Y', strtotime($pesanan['tanggal_selesai']));
                                    } else {
                                        echo '<span class="text-muted" style="font-size:12px; font-style:italic;">Belum Selesai</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered mb-0">
                        <thead style="background-color: #f8fafc;">
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
                                    <div style="font-weight: 700; color: var(--navy-dark); font-size: 15px;"><?= htmlspecialchars($pesanan['jenis_pakaian']) ?></div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px; font-style: italic;">
                                        "<?= htmlspecialchars($pesanan['deskripsi'] ?: 'Tidak ada catatan tambahan') ?>"
                                    </div>
                                </td>
                                <td class="align-middle" style="font-size: 13px; color: #475569;">
                                    <div class="mb-1 text-uppercase" style="font-size: 11px; font-weight: 700; color: #94a3b8;">Rincian Ukuran & Qty:</div>
                                    <div class="p-2 mb-2" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        <?php 
                                        $ukuran_text = htmlspecialchars($pesanan['ukuran'] ?: '-');
                                        
                                        if (strpos($ukuran_text, '[PRODUKSI MASSAL]') !== false) {
                                            $ukuran_text = str_replace('[PRODUKSI MASSAL] ', '', $ukuran_text);
                                            $items = explode('|', $ukuran_text);
                                            echo '<div style="font-size: 10px; font-weight: 700; color: #161111; margin-bottom: 4px;">[PRODUKSI MASSAL]</div>';
                                            echo '<ul style="margin: 0; padding-left: 16px; color: #1e293b; font-weight: 600;">';
                                            foreach ($items as $item) {
                                                echo '<li style="padding: 2px 0;">' . trim($item) . '</li>';
                                            }
                                            echo '</ul>';
                                        } elseif (strpos($ukuran_text, ', ') !== false && strpos($ukuran_text, 'LD:') !== false) {
                                            echo '<span style="color: #1e293b; font-weight: 600;">' . str_replace(', ', '<br>', $ukuran_text) . '</span>';
                                        } else {
                                            echo '<span style="color: #1e293b; font-weight: 600;">' . nl2br($ukuran_text) . '</span>';
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Menampilkan variabel $warna_tampil yang sudah diekstrak cerdas -->
                                    <div><strong>Warna Umum:</strong> <span class="text-dark fw-bold text-capitalize"><?= htmlspecialchars($warna_tampil) ?></span></div>
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
                                <span class="badge bg-success" style="font-size: 12px; padding: 6px 12px;"><i class="bi bi-check-circle me-1"></i> LUNAS</span>
                            <?php elseif($dp > 0 && $sisa > 0): ?>
                                <span class="badge bg-info text-dark" style="font-size: 12px; padding: 6px 12px;">UANG MUKA (DP)</span>
                            <?php elseif($transaksi_status == 'ditolak' || $transaksi_status == 'tidak valid'): ?>
                                <span class="badge bg-danger" style="font-size: 12px; padding: 6px 12px;"><i class="bi bi-x-circle me-1"></i> BUKTI DITOLAK / TIDAK VALID</span>
                            <?php else: ?>
                                <span class="badge bg-danger" style="font-size: 12px; padding: 6px 12px;">BELUM BAYAR</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($pesanan['promo_dipakai'])): ?>
                            <div class="detail-label mb-2">Voucher / Promo Digunakan:</div>
                            <div>
                                <span class="badge text-white" style="background-color: #8b5cf6; font-size: 12px; padding: 6px 12px; border-radius: 4px; font-weight: 600;">
                                    <i class="bi bi-ticket-perforated me-1"></i> <?= htmlspecialchars($pesanan['promo_dipakai']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-end detail-label align-middle" style="font-size: 13px;">Total Tagihan :</td>
                                <td class="text-end fw-bold" style="font-size: 16px; color: var(--navy-dark); width: 40%;">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <tr style="border-bottom: 2px solid #e2e8f0;">
                                <td class="text-end detail-label align-middle" style="font-size: 13px;">Telah Dibayar (DP) :</td>
                                <td class="text-end fw-bold" style="font-size: 15px; color: #10b981;">Rp <?= number_format($pesanan['dp_dibayar'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="text-end detail-label align-middle pt-2" style="font-size: 14px; color: #ef4444;">SISA TAGIHAN :</td>
                                <td class="text-end fw-bold pt-2" style="font-size: 18px; color: #ef4444;">Rp <?= number_format($pesanan['sisa_tagihan'], 0, ',', '.') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="text-center mt-5" style="border-top: 1px dashed #cbd5e1; padding-top: 20px; font-size: 12px; color: #94a3b8;">
                    Terima kasih telah mempercayakan layanan jahit Anda kepada Jashit.<br>
                    Harap bawa nota ini saat pengambilan barang.
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>