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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f6f9; }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .filter-bar .date-range {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-bar input[type="date"] {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            background: #f8fafc;
        }
        .filter-bar input[type="date"]:focus { outline: none; border-color: #94a3b8; }
        .sep { color: #94a3b8; font-size: 13px; }

        /* ===== SUMMARY CARDS ===== */
        .stat-card {
            background: #fff;
            border: 1px solid #e8ecf0;
            border-radius: 10px;
            padding: 20px 22px;
            height: 100%;
            transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,0.07); }
        .stat-card .stat-title {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .stat-card .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            line-height: 1;
        }
        .stat-card .stat-value.money { font-size: 18px; }
        .stat-card .stat-sub {
            font-size: 11px;
            color: #b0bec5;
            margin-top: 6px;
        }

        /* ===== CHART CARDS ===== */
        .chart-card {
            background: #fff;
            border: 1px solid #e8ecf0;
            border-radius: 10px;
            padding: 22px 24px;
            height: 100%;
        }
        .chart-card .chart-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
        }

        .btn-export:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }

        .btn-filter-apply {
            background: #1e293b;
            color: #fff;
            border: none;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 7px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-filter-apply:hover { background: #0f172a; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_owner.php'; ?>

    <div class="dashboard-main">
        <?php require_once '../includes/topbar_owner.php'; ?>

        <div class="dashboard-content" style="padding: 24px 28px;">

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
                        <div style="height: 280px;">
                            <canvas id="chartMonth"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="chart-card">
                        <div class="chart-title">Transaction Per Year</div>
                        <div style="height: 280px;">
                            <canvas id="chartYear"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end dashboard-content -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function rpFormat(val) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
}

// ===== GRAFIK 1: PER BULAN (LINE) =====
const ctxMonth = document.getElementById('chartMonth').getContext('2d');
const gradMonth = ctxMonth.createLinearGradient(0, 0, 0, 300);
gradMonth.addColorStop(0, 'rgba(99, 179, 237, 0.25)');
gradMonth.addColorStop(1, 'rgba(99, 179, 237, 0)');

new Chart(ctxMonth, {
    type: 'line',
    data: {
        labels: <?= $json_label_month ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?= $json_data_month ?>,
            borderColor: '#38bdf8',
            backgroundColor: gradMonth,
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#38bdf8',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12, usePointStyle: true, pointStyle: 'line' }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + rpFormat(ctx.parsed.y)
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', borderDash: [4, 4] },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    callback: val => val >= 1000000
                        ? 'Rp ' + (val / 1000000).toFixed(1) + 'M'
                        : 'Rp ' + (val / 1000).toFixed(0) + 'K'
                }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11 } }
            }
        }
    }
});

// ===== GRAFIK 2: PER TAHUN (BAR) =====
const ctxYear = document.getElementById('chartYear').getContext('2d');
new Chart(ctxYear, {
    type: 'bar',
    data: {
        labels: <?= $json_label_year ?>,
        datasets: [{
            label: 'total',
            data: <?= $json_data_year ?>,
            backgroundColor: 'rgba(129, 118, 194, 0.75)',
            borderColor: 'rgba(129, 118, 194, 1)',
            borderWidth: 1,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12 }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + rpFormat(ctx.parsed.y)
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', borderDash: [4, 4] },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    callback: val => val >= 1000000
                        ? 'Rp ' + (val / 1000000).toFixed(1) + 'M'
                        : 'Rp ' + (val / 1000).toFixed(0) + 'K'
                }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11 } }
            }
        }
    }
});
</script>
</body>
</html>