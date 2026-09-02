<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('owner');

// =========================================================================
// FILTER RENTANG TANGGAL
// =========================================================================
$tgl_awal  = $_GET['tgl_awal']  ?? date('Y-m-d', strtotime('-7 days'));
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

$where_range = "p.status != 'dibatalkan' AND DATE(p.tanggal_pesan) BETWEEN '$tgl_awal' AND '$tgl_akhir'";

// =========================================================================
// KARTU 1 — TOTAL THIS YEAR
// =========================================================================
$tahun_ini = date('Y');
$q_year = mysqli_query($koneksi, "
    SELECT SUM(CASE WHEN status_pembayaran='lunas' THEN total_harga ELSE dp_dibayar END) as val
    FROM pesanan WHERE status != 'dibatalkan' AND YEAR(tanggal_pesan) = $tahun_ini
");
$total_year = mysqli_fetch_assoc($q_year)['val'] ?? 0;

// =========================================================================
// KARTU 2 — TOTAL THIS MONTH
// =========================================================================
$q_month = mysqli_query($koneksi, "
    SELECT SUM(CASE WHEN status_pembayaran='lunas' THEN total_harga ELSE dp_dibayar END) as val
    FROM pesanan WHERE status != 'dibatalkan'
    AND MONTH(tanggal_pesan) = MONTH(CURDATE()) AND YEAR(tanggal_pesan) = YEAR(CURDATE())
");
$total_month = mysqli_fetch_assoc($q_month)['val'] ?? 0;

// =========================================================================
// KARTU 3 — TOTAL TODAY
// =========================================================================
$q_today = mysqli_query($koneksi, "
    SELECT SUM(CASE WHEN status_pembayaran='lunas' THEN total_harga ELSE dp_dibayar END) as val
    FROM pesanan WHERE status != 'dibatalkan' AND DATE(tanggal_pesan) = CURDATE()
");
$total_today = mysqli_fetch_assoc($q_today)['val'] ?? 0;

// =========================================================================
// KARTU 4 — TOTAL TRANSFER TODAY (dari tabel transaksi metode transfer)
// =========================================================================
$q_transfer = mysqli_query($koneksi, "
    SELECT SUM(t.nominal) as val
    FROM transaksi t
    JOIN pesanan p ON t.pesanan_id = p.id
    WHERE p.status != 'dibatalkan'
    AND DATE(t.tanggal_bayar) = CURDATE()
    AND t.status_verifikasi = 'Terverifikasi'
    AND t.metode_pembayaran = 'Transfer Bank'
");
$total_transfer_today = mysqli_fetch_assoc($q_transfer)['val'] ?? 0;

// =========================================================================
// KARTU 5 — TOTAL CASH TODAY
// =========================================================================
$q_cash = mysqli_query($koneksi, "
    SELECT SUM(t.nominal) as val
    FROM transaksi t
    JOIN pesanan p ON t.pesanan_id = p.id
    WHERE p.status != 'dibatalkan'
    AND DATE(t.tanggal_bayar) = CURDATE()
    AND t.status_verifikasi = 'Terverifikasi'
    AND t.metode_pembayaran = 'Cash'
");
$total_cash_today = mysqli_fetch_assoc($q_cash)['val'] ?? 0;

// =========================================================================
// KARTU 6 — TOTAL TRANSAKSI TODAY (jumlah pesanan masuk hari ini)
// =========================================================================
$q_trx_today = mysqli_query($koneksi, "
    SELECT COUNT(id) as val FROM pesanan
    WHERE status != 'dibatalkan' AND DATE(tanggal_pesan) = CURDATE()
");
$total_trx_today = mysqli_fetch_assoc($q_trx_today)['val'] ?? 0;

// =========================================================================
// GRAFIK 1 — TRANSACTION PER MONTH (tahun ini, per bulan)
// =========================================================================
$q_per_month = mysqli_query($koneksi, "
    SELECT MONTH(tanggal_pesan) as bln,
           SUM(CASE WHEN status_pembayaran='lunas' THEN total_harga ELSE dp_dibayar END) as val
    FROM pesanan
    WHERE status != 'dibatalkan'
    AND DATE(tanggal_pesan) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY MONTH(tanggal_pesan)
    ORDER BY bln ASC
");

$nama_bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$data_per_month = array_fill(0, 12, 0);
while ($r = mysqli_fetch_assoc($q_per_month)) {
    $data_per_month[(int)$r['bln'] - 1] = (int)$r['val'];
}
$json_label_month = json_encode($nama_bulan);
$json_data_month  = json_encode($data_per_month);

// =========================================================================
// GRAFIK 2 — TRANSACTION PER YEAR (5 tahun terakhir)
// =========================================================================
$q_per_year = mysqli_query($koneksi, "
    SELECT YEAR(tanggal_pesan) as thn,
           SUM(CASE WHEN status_pembayaran='lunas' THEN total_harga ELSE dp_dibayar END) as val
    FROM pesanan
    WHERE status != 'dibatalkan'
    GROUP BY YEAR(tanggal_pesan)
    ORDER BY thn ASC
    LIMIT 5
");

$label_year = [];
$data_year  = [];
while ($r = mysqli_fetch_assoc($q_per_year)) {
    $label_year[] = (string)$r['thn'];
    $data_year[]  = (int)$r['val'];
}
$json_label_year = json_encode($label_year);
$json_data_year  = json_encode($data_year);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Owner — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard_owner.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard-owner-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_owner.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_owner.php'; ?>

        <div class="dashboard-content dashboard-owner-content">

            <!-- FILTER BAR -->
            <form method="GET" action="">
                <div class="filter-bar">
                    <div class="date-range">
                        <i class="bi bi-calendar3 text-muted"></i>
                        <input type="date" name="tgl_awal"  value="<?= $tgl_awal ?>">
                        <span class="sep">→</span>
                        <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                        <button type="submit" class="btn-filter-apply ms-2">Terapkan</button>
                    </div>
                </div>
            </form>

            <!-- ROW 1: 3 KARTU ATAS -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Total This Year</div>
                        <div class="stat-value money">Rp <?= number_format($total_year, 0, ',', '.') ?></div>
                        <div class="stat-sub">Pendapatan masuk tahun <?= $tahun_ini ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Total This Month</div>
                        <div class="stat-value money">Rp <?= number_format($total_month, 0, ',', '.') ?></div>
                        <div class="stat-sub">Pendapatan bulan <?= date('F Y') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Today</div>
                        <div class="stat-value money">Rp <?= number_format($total_today, 0, ',', '.') ?></div>
                        <div class="stat-sub">Pendapatan hari ini · <?= date('d M Y') ?></div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: 3 KARTU BAWAH -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Transfer Today</div>
                        <div class="stat-value money">Rp <?= number_format($total_transfer_today, 0, ',', '.') ?></div>
                        <div class="stat-sub">Pembayaran via transfer hari ini</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Cash Today</div>
                        <div class="stat-value money">Rp <?= number_format($total_cash_today, 0, ',', '.') ?></div>
                        <div class="stat-sub">Pembayaran tunai hari ini</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Transaction Today</div>
                        <div class="stat-value"><?= number_format($total_trx_today, 0, ',', '.') ?></div>
                        <div class="stat-sub">Pesanan masuk hari ini</div>
                    </div>
                </div>
            </div>

            <!-- ROW 3: 2 GRAFIK -->
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="chart-card">
                        <div class="chart-title">Transaction Per Month (<?= $tahun_ini ?>)</div>
                        <div class="chart-canvas-wrap">
                            <canvas id="chartMonth"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="chart-card">
                        <div class="chart-title">Transaction Per Year</div>
                        <div class="chart-canvas-wrap">
                            <canvas id="chartYear"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end dashboard-content -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Data chart dari PHP (hasil query dinamis) dikirim ke JS lewat variabel global -->
<script>
    window.dashboardChartData = {
        labelMonth: <?= $json_label_month ?>,
        dataMonth: <?= $json_data_month ?>,
        labelYear: <?= $json_label_year ?>,
        dataYear: <?= $json_data_year ?>
    };
</script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/dashboard_owner.js"></script>
</body>
</html>