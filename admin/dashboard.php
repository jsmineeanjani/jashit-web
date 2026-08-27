<?php
/* =============================================================
   JASHIT — Dashboard Admin
   File: admin/dashboard.php
   ============================================================= */

require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

// ── Jumlah bukti pembayaran menunggu verifikasi ────────────────
$q_verifikasi    = mysqli_query($koneksi, "SELECT COUNT(id) AS total_menunggu FROM transaksi WHERE status_verifikasi = 'Menunggu'");
$total_verifikasi = (int)(mysqli_fetch_assoc($q_verifikasi)['total_menunggu'] ?? 0);

// ── Jumlah feedback baru (belum dimoderasi) ───────────────────
$q_feedback        = mysqli_query($koneksi, "SELECT COUNT(id) AS total_baru FROM feedback WHERE status = 'menunggu'");
$total_feedback_baru = (int)(mysqli_fetch_assoc($q_feedback)['total_baru'] ?? 0);

// ── Statistik ringkasan ────────────────────────────────────────
$stats = [];

$r = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users WHERE role = 'pelanggan'");
$stats['pelanggan'] = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);

$r = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pesanan");
$stats['pesanan'] = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);

$r = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pesanan WHERE status NOT IN ('selesai','dibatalkan')");
$stats['aktif'] = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);

// ── Pesanan terbaru (10 terakhir) ─────────────────────────────
$pesanan_terbaru = [];
$r = mysqli_query($koneksi,
    "SELECT p.*, u.nama AS nama_pelanggan
     FROM pesanan p
     JOIN users u ON p.user_id = u.id
     ORDER BY p.created_at DESC
     LIMIT 10"
);
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $pesanan_terbaru[] = $row;
    }
}

// ── Helper: label status berdasarkan jenis pesanan ────────────
function getLabelStatus(string $status, string $ukuran = ''): string {
    $u = strtoupper($ukuran);
    $isBahan     = str_contains($u, 'PEMBELIAN BAHAN');
    $isAksesoris = str_contains($u, 'PEMBELIAN AKSESORIS');

    $map = [
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'dikonfirmasi'        => 'Dikonfirmasi',
        'proses_cutting'      => $isBahan    ? 'Penyiapan Bahan'
                               : ($isAksesoris ? 'Penyiapan Aksesoris'
                               : 'Proses Cutting'),
        'proses_jahit'        => 'Proses Jahit',
        'proses_finishing'    => 'Proses Finishing',
        'quality_check'       => 'Quality Check (QC)',
        'siap_kirim'          => 'Siap Diambil / Kirim',
        'selesai'             => 'Selesai',
        'dibatalkan'          => 'Dibatalkan',
    ];

    return $map[$status] ?? ucwords(str_replace('_', ' ', $status));
}

// ── Helper: badge & label status pembayaran ───────────────────
function getBadgePembayaran(string $status): array {
    return match(true) {
        in_array($status, ['menunggu', 'menunggu_verifikasi']) => ['bg-warning text-dark', 'Cek Bukti'],
        in_array($status, ['dp', 'dp_lunas'])                  => ['bg-info text-dark',    'DP'],
        $status === 'lunas'                                     => ['bg-success',            'Lunas'],
        default                                                 => ['bg-secondary',          'Belum Bayar'],
    };
}

$page_title = 'Dashboard Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — JASHIT</title>

    <!-- Bootstrap & Icons -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Global & Page CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css"           rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard_admin.css" rel="stylesheet">
</head>
<body class="dash-body-bg">

<div class="dashboard-wrapper">

    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>

    <div class="dashboard-main">

        <?php require_once '../includes/layouts/topbar_admin.php'; ?>

        <!-- ── Page Header ──────────────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center dash-header-row">
            <h1 class="dash-page-heading">Dashboard Overview</h1>
            <span class="dash-date-label"><?= date('l, d M Y') ?></span>
        </div>

        <!-- ── Konten Utama ──────────────────────────────────── -->
        <div class="dashboard-content dashboard-content-dash">

            <?php if (function_exists('showFlash')) echo showFlash(); ?>

            <!-- Alert Feedback Baru -->
            <?php if ($total_feedback_baru > 0): ?>
            <div id="alertFeedbackBaru"
                 class="alert alert-feedback-baru alert-dismissible fade show d-flex align-items-center shadow-sm mb-4"
                 role="alert">
                <i class="bi bi-star-fill me-3 pulse-icon alert-feedback-icon"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1 alert-feedback-title">Ada Ulasan Baru!</h6>
                    <span class="alert-feedback-text">
                        Anda memiliki <strong><?= $total_feedback_baru ?> feedback pelanggan baru</strong>
                        yang menunggu untuk dimoderasi.
                    </span>
                </div>
                <a href="<?= BASE_URL ?>/admin/feedback.php"
                   class="btn btn-sm ms-3 btn-feedback-action">
                    Tinjau Sekarang
                </a>
                <button type="button" class="btn-close ms-3 btn-close-static" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- ── Statistik Cards ───────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-12">
                    <div class="stat-card-clean">
                        <div class="stat-label-clean">Total Pelanggan</div>
                        <div class="stat-value-clean"><?= $stats['pelanggan'] ?></div>
                        <div class="stat-sub-clean">
                            <i class="bi bi-people icon-blue"></i> Kelola data pelanggan
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="stat-card-clean">
                        <div class="stat-label-clean">Pesanan Aktif</div>
                        <div class="stat-value-clean"><?= $stats['aktif'] ?></div>
                        <div class="stat-sub-clean">
                            <i class="bi bi-clock-history icon-cyan"></i> Sedang diproses
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="stat-card-clean accent-orange">
                        <div class="stat-label-clean">Perlu Verifikasi</div>
                        <div class="stat-value-clean"><?= $total_verifikasi ?></div>
                        <div class="stat-sub-clean">
                            <i class="bi bi-wallet2 icon-red"></i> Cek bukti transfer
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Tabel Pesanan Terbaru ─────────────────────── -->
            <div class="row">
                <div class="col-12">
                    <div class="dash-table-card">

                        <div class="d-flex justify-content-between align-items-center dash-table-card-header">
                            <h5 class="dash-table-card-title">
                                Pesanan Terbaru & Butuh Tindakan
                            </h5>
                            <a href="<?= BASE_URL ?>/admin/pesanan.php" class="dash-link-view-all">
                                Lihat semua &rarr;
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-dark-header mb-0 dash-table-min-width">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Pelanggan</th>
                                        <th>Tgl Pesan</th>
                                        <th>Total Biaya</th>
                                        <th>Pembayaran</th>
                                        <th>Status Pesanan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($pesanan_terbaru)): ?>
                                    <tr>
                                        <td colspan="7" class="dash-empty-state">
                                            <i class="bi bi-inbox dash-empty-icon"></i>
                                            Belum ada data pesanan
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pesanan_terbaru as $p):
                                        $label          = getLabelStatus($p['status'], $p['ukuran'] ?? '');
                                        [$bayarBadge, $bayarLabel] = getBadgePembayaran($p['status_pembayaran'] ?? 'belum_bayar');

                                        $statusClass = [
                                            'menunggu_konfirmasi' => 'status-menunggu',
                                            'dikonfirmasi'        => 'status-konfirmasi',
                                            'proses_cutting'      => 'status-cutting',
                                            'proses_jahit'        => 'status-jahit',
                                            'proses_finishing'    => 'status-finishing',
                                            'quality_check'       => 'status-qc',
                                            'siap_kirim'          => 'status-siap_kirim',
                                            'selesai'             => 'status-selesai',
                                            'dibatalkan'          => 'status-dibatalkan',
                                        ][$p['status']] ?? 'status-menunggu';

                                        $perluVerifikasi = in_array($p['status_pembayaran'] ?? '', ['menunggu', 'menunggu_verifikasi']);
                                    ?>
                                    <tr class="<?= $perluVerifikasi ? 'row-perlu-verifikasi' : '' ?>">
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/pesanan_detail.php?id=<?= $p['id'] ?>"
                                               class="dash-order-code-link">
                                                <?= htmlspecialchars($p['kode_pesanan']) ?>
                                            </a>
                                            <div class="dash-order-subtext">
                                                <?= htmlspecialchars($p['jenis_pakaian']) ?>
                                            </div>
                                        </td>
                                        <td class="dash-cell-name">
                                            <?= htmlspecialchars($p['nama_pelanggan']) ?>
                                        </td>
                                        <td class="dash-cell-muted-sm">
                                            <?= date('d M Y', strtotime($p['tanggal_pesan'])) ?>
                                        </td>
                                        <td class="dash-cell-price">
                                            Rp <?= number_format($p['total_harga'] ?? 0, 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-payment <?= $bayarBadge ?>">
                                                <?= $bayarLabel ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= strtoupper($label) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($perluVerifikasi): ?>
                                                <a href="<?= BASE_URL ?>/admin/transaksi.php"
                                                   class="btn btn-sm btn-primary btn-action-admin">
                                                    Verifikasi
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/admin/pesanan.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-sm btn-outline-dark btn-action-admin">
                                                    Kelola
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Dashboard JS -->
<script src="<?= BASE_URL ?>/assets/js/dashboard_admin.js"></script>

</body>
</html>