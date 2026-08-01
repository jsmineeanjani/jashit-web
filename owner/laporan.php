<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
// Proteksi halaman, hanya owner yang bisa masuk
requireRole('owner');

// =========================================================================
// LOGIKA FILTER RENTANG TANGGAL (TANGGAL AWAL & TANGGAL AKHIR)
// =========================================================================
// Jika belum ada filter yang disubmit, defaultnya adalah bulan ini (tanggal 1 sampai tanggal terakhir bulan ini)
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

// Filter: Status tidak batal DAN tanggal_pesan berada di antara tgl_awal dan tgl_akhir
$where_clause = "p.status != 'dibatalkan' AND DATE(p.tanggal_pesan) BETWEEN '$tgl_awal' AND '$tgl_akhir'";

// Format judul untuk kop surat saat dicetak (Misal: 01 Jan 2025 - 31 May 2025)
$judul_periode = date('d M Y', strtotime($tgl_awal)) . " s/d " . date('d M Y', strtotime($tgl_akhir));

// =========================================================================
// QUERY AMBIL DATA PESANAN BERDASARKAN PERIODE
// =========================================================================
$query = "SELECT p.*, u.nama AS nama_pelanggan, u.no_hp 
          FROM pesanan p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE $where_clause 
          ORDER BY p.tanggal_pesan ASC, p.id ASC";
$result = mysqli_query($koneksi, $query);

// =========================================================================
// KALKULASI TOTAL METRIK UNTUK SUMMARY LAPORAN
// =========================================================================
$total_omset = 0;
$total_pesanan = 0;
$total_pcs = 0;
$data_laporan = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_laporan[] = $row;
        $total_omset += (int)$row['total_harga'];
        $total_pcs += (int)$row['jumlah'];
        $total_pesanan++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan — JASHIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .report-header { display: none; } /* Hanya muncul saat dicetak */
        .card-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; }
        
        table.table th { background-color: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; padding: 12px 10px; }
        table.table td { vertical-align: top; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; padding: 12px 10px; }
        
        .metric-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; text-align: center; }
        .metric-val { font-size: 18px; font-weight: 700; color: var(--navy-dark); }
        .metric-lbl { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; }

       /* Custom warna tombol  */
        .btn-cari { background-color: #1e293b; border-color: #1e293b; color: #fff; transition: all 0.3s ease; }
        .btn-cari:hover { background-color: #0f172a; color: #f4d3c2; }
        .btn-cetak { background-color: #f4d3c2; border-color: #f4d3c2; color: #1e293b; transition: all 0.3s ease; }
        .btn-cetak:hover { background-color: #e8b89e; color: #0f172a; }
        /* =========================================================================
           CSS KHUSUS CETAK (PRINT MEDIA)
           ========================================================================= */
        @media print {
            body { background: #fff !important; color: #000 !important; }
            /* Tambahkan .topbar, header, dan nav agar menu atas benar-benar hilang */
            .dashboard-sidebar, .topbar-wrapper, .topbar, header, nav, .filter-section, .page-title-wrap { display: none !important; } 
            .dashboard-main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .dashboard-content { padding: 0 !important; }
            .card-table-wrap { border: none !important; padding: 0 !important; box-shadow: none !important; }
            .report-header { display: block !important; text-align: center; margin-bottom: 30px; border-bottom: 3px double #000; padding-bottom: 15px; margin-top: 20px; }
            .metric-box { border: 1px solid #000 !important; background: transparent !important; }
            table.table th { background-color: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
            table.table td { border-bottom: 1px solid #ccc !important; color: #000 !important; }
        }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_owner.php'; ?>
    
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_owner.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            
            <div class="mb-4 page-title-wrap">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0; color: var(--navy-dark);">Daftar Laporan</h1>
                <p class="text-muted small mb-0 mt-1">Gunakan laporan ini sebagai acuan analisis performa berkala.</p>
            </div>

            <div class="filter-section mb-4">
                <form action="" method="GET" class="d-flex align-items-end gap-3 flex-wrap">
                    <div>
                        <label class="form-label fw-bold small text-muted mb-1">Tanggal Awal</label>
                        <input type="date" name="tgl_awal" class="form-control text-secondary fw-bold" value="<?= $tgl_awal ?>" required>
                    </div>
                    <div>
                        <label class="form-label fw-bold small text-muted mb-1">Tanggal Akhir</label>
                        <input type="date" name="tgl_akhir" class="form-control text-secondary fw-bold" value="<?= $tgl_akhir ?>" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-cari fw-bold px-4">
                            Cari
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-cetak fw-bold px-4">
                            Cetak
                        </button>
                    </div>
                </form>
            </div>

            <div class="report-header">
                <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" style="max-width: 140px; margin-bottom: 15px;">
                
                <h2 style="margin: 0 0 5px 0; font-weight: 800; letter-spacing: 1px;">LAYANAN KONVEKSI JASA JAHIT</h2>
                <p style="margin: 0 0 5px 0; font-size: 13px; color: #333;">Laporan Rekapitulasi Penjualan & Pendapatan Konveksi</p>
                <h5 style="margin: 10px 0 0 0; font-weight: 700; text-transform: uppercase;">PERIODE: <?= $judul_periode ?></h5>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="metric-box shadow-sm">
                        <div class="metric-lbl">Total Omset Bruto</div>
                        <div class="metric-val">Rp <?= number_format($total_omset, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-box shadow-sm">
                        <div class="metric-lbl">Volume Pesanan</div>
                        <div class="metric-val"><?= $total_pesanan ?> Transaksi</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-box shadow-sm">
                        <div class="metric-lbl">Total Produksi</div>
                        <div class="metric-val"><?= $total_pcs ?> Item</div>
                    </div>
                </div>
            </div>

            <div class="card-table-wrap shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th width="5%">NO</th>
                                <th width="15%">KODE & TANGGAL</th>
                                <th width="20%">PELANGGAN</th>
                                <th width="15%">LAYANAN</th>
                                <th width="25%">RINCIAN PRODUKSI (UKURAN/WARNA)</th>
                                <th width="10%" class="text-end">QTY</th>
                                <th width="10%" class="text-end">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data_laporan)): $no = 1; foreach ($data_laporan as $row): 
                                // Pendeteksi satuan bahan atau pakaian
                                $teks_ukuran = strtoupper($row['ukuran']);
                                $is_bahan = (strpos($teks_ukuran, 'PEMBELIAN BAHAN') !== false);
                                $satuan = $is_bahan ? 'Meter' : 'pcs';
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-family: monospace; font-size: 13px;"><?= htmlspecialchars($row['kode_pesanan']) ?></div>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 2px;"><?= date('d-m-Y', strtotime($row['tanggal_pesan'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Walk-in') ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($row['no_hp'] ?? $row['user_id']) ?></div>
                                    </td>
                                    <td class="fw-bold text-secondary"><?= htmlspecialchars($row['jenis_pakaian']) ?></td>
                                    <td>
                                        <div style="font-size: 12px; line-height: 1.5; color: #475569;">
                                            <?php 
                                            $ukuran_text = htmlspecialchars($row['ukuran'] ?: '-');
                                            
                                            // Format List untuk [PRODUKSI MASSAL]
                                            if (strpos($ukuran_text, '[PRODUKSI MASSAL]') !== false) {
                                                $ukuran_text = str_replace('[PRODUKSI MASSAL] ', '', $ukuran_text);
                                                $ukuran_text = str_replace('[PRODUKSI MASSAL]', '', $ukuran_text); // Jaga-jaga jika tidak ada spasi
                                                $items = explode('|', $ukuran_text);
                                                
                                                echo '<div style="font-size: 10px; font-weight: 700; color: #161111; margin-bottom: 4px;">[PRODUKSI MASSAL]</div>';
                                                echo '<ul style="margin: 0; padding-left: 16px; color: #1e293b; font-weight: 500;">';
                                                foreach ($items as $item) {
                                                    if (trim($item) !== '') {
                                                        echo '<li style="padding: 2px 0;">' . trim($item) . '</li>';
                                                    }
                                                }
                                                echo '</ul>';
                                            } 
                                            // Format Break Line untuk koma yang ada LD:
                                            elseif (strpos($ukuran_text, ', ') !== false && strpos(strtoupper($ukuran_text), 'LD:') !== false) {
                                                echo '<span style="color: #1e293b; font-weight: 500;">' . str_replace(', ', '<br>', $ukuran_text) . '</span>';
                                            } 
                                            // Default
                                            else {
                                                echo '<span style="color: #1e293b; font-weight: 500;">' . nl2br($ukuran_text) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($row['jumlah'], 0, ',', '.') ?> <?= $satuan ?></td>
                                    <td class="text-end fw-bold text-dark">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                                <tr class="fw-bold" style="background-color: #f8fafc; color: #000;">
                                    <td colspan="5" class="text-end text-uppercase" style="font-size: 12px; padding: 14px 10px; border-top: 2px solid #1e293b;">GRAND TOTAL:</td>
                                    <td class="text-end" style="padding: 14px 10px; border-top: 2px solid #1e293b;"><?= number_format($total_pcs, 0, ',', '.') ?> Item</td>
                                    <td class="text-end text-primary" style="padding: 14px 10px; font-size: 14px; border-top: 2px solid #1e293b;">Rp <?= number_format($total_omset, 0, ',', '.') ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data penjualan pada rentang tanggal ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>