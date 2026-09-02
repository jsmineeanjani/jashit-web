<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('owner');

// =========================================================================
// LOGIKA FILTER RENTANG TANGGAL (TANGGAL AWAL & TANGGAL AKHIR)
// =========================================================================
// Jika belum ada filter yang disubmit, defaultnya adalah bulan ini (tanggal 1 sampai tanggal terakhir bulan ini)
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');


$where_clause = "p.status != 'dibatalkan' AND DATE(p.tanggal_pesan) BETWEEN '$tgl_awal' AND '$tgl_akhir'";

// Format judul untuk kop surat saat dicetak 
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
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/laporan.css" rel="stylesheet">
</head>
<body class="laporan-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_owner.php'; ?>

    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_owner.php'; ?>

        <div class="dashboard-content laporan-content">
            
            <div class="mb-4 page-title-wrap">
                <h1 class="laporan-page-title">Daftar Laporan Penjualan</h1>
                <p class="text-muted small mb-0 mt-1">Daftar laporan digunakan sebagai acuan analisis performa penjualan.</p>
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
                <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" class="laporan-logo">
                
                <h2 class="laporan-judul">LAYANAN KONVEKSI JASA JAHIT</h2>
                <p class="laporan-subjudul">Laporan Rekapitulasi Penjualan & Pendapatan Konveksi</p>
                <h5 class="laporan-periode">PERIODE: <?= $judul_periode ?></h5>
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
                                        <div class="fw-bold text-dark laporan-kode"><?= htmlspecialchars($row['kode_pesanan']) ?></div>
                                        <div class="text-muted laporan-meta-xs"><?= date('d-m-Y', strtotime($row['tanggal_pesan'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Walk-in') ?></div>
                                        <div class="text-muted laporan-meta-xs"><?= htmlspecialchars($row['no_hp'] ?? $row['user_id']) ?></div>
                                    </td>
                                    <td class="fw-bold text-secondary"><?= htmlspecialchars($row['jenis_pakaian']) ?></td>
                                    <td>
                                        <div class="laporan-rincian">
                                            <?php 
                                            $ukuran_text = htmlspecialchars($row['ukuran'] ?: '-');
                                            
                                            // Format List untuk [PRODUKSI MASSAL]
                                            if (strpos($ukuran_text, '[PRODUKSI MASSAL]') !== false) {
                                                $ukuran_text = str_replace('[PRODUKSI MASSAL] ', '', $ukuran_text);
                                                $ukuran_text = str_replace('[PRODUKSI MASSAL]', '', $ukuran_text); // Jaga-jaga jika tidak ada spasi
                                                $items = explode('|', $ukuran_text);
                                                
                                                echo '<div class="laporan-massal-header">[PRODUKSI MASSAL]</div>';
                                                echo '<ul class="laporan-massal-list">';
                                                foreach ($items as $item) {
                                                    if (trim($item) !== '') {
                                                        echo '<li class="laporan-massal-li">' . trim($item) . '</li>';
                                                    }
                                                }
                                                echo '</ul>';
                                            } 
                                            // Format Break Line untuk koma yang ada LD:
                                            elseif (strpos($ukuran_text, ', ') !== false && strpos(strtoupper($ukuran_text), 'LD:') !== false) {
                                                echo '<span class="laporan-text-dark">' . str_replace(', ', '<br>', $ukuran_text) . '</span>';
                                            } 
                                            // Default
                                            else {
                                                echo '<span class="laporan-text-dark">' . nl2br($ukuran_text) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($row['jumlah'], 0, ',', '.') ?> <?= $satuan ?></td>
                                    <td class="text-end fw-bold text-dark">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                                <tr class="fw-bold laporan-total-row">
                                    <td colspan="5" class="text-end text-uppercase laporan-total-label">GRAND TOTAL:</td>
                                    <td class="text-end laporan-total-cell"><?= number_format($total_pcs, 0, ',', '.') ?> Item</td>
                                    <td class="text-end text-primary laporan-total-omset">Rp <?= number_format($total_omset, 0, ',', '.') ?></td>
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