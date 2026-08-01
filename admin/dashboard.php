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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Global & Page CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css"           rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard_admin.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">

<div class="dashboard-wrapper">

    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>

    <div class="dashboard-main">

        <?php require_once '../includes/topbar_admin.php'; ?>

        <!-- ── Page Header ──────────────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center" style="padding: 24px 32px 10px;">
            <h1 style="font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;">Dashboard Overview</h1>
            <span style="font-size: 14px; color: #94a3b8;"><?= date('l, d M Y') ?></span>
        </div>

        <!-- ── Konten Utama ──────────────────────────────────── -->
        <div class="dashboard-content" style="padding: 15px 32px 30px;">

            <?php if (function_exists('showFlash')) echo showFlash(); ?>

            <!-- Alert Feedback Baru -->
            <?php if ($total_feedback_baru > 0): ?>
            <div id="alertFeedbackBaru"
                 class="alert alert-feedback-baru alert-dismissible fade show d-flex align-items-center shadow-sm mb-4"
                 role="alert">
                <i class="bi bi-star-fill me-3 pulse-icon" style="font-size: 24px; color: #f59e0b;"></i>
                <div style="flex-grow: 1;">
                    <h6 class="mb-1" style="font-weight: 700; color: #b45309;">Ada Ulasan Baru!</h6>
                    <span style="font-size: 14px;">
                        Anda memiliki <strong><?= $total_feedback_baru ?> feedback pelanggan baru</strong>
                        yang menunggu untuk dimoderasi.
                    </span>
                </div>
                <a href="<?= BASE_URL ?>/admin/feedback.php"
                   class="btn btn-sm ms-3"
                   style="background-color: #f59e0b; color: white; font-weight: 600; padding: 8px 16px; white-space: nowrap;">
                    Tinjau Sekarang
                </a>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"
                        style="position: static;"></button>
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
                        <div class="stat-label-clean" style="color: #ea580c;">Perlu Verifikasi</div>
                        <div class="stat-value-clean" style="color: #ea580c;"><?= $total_verifikasi ?></div>
                        <div class="stat-sub-clean">
                            <i class="bi bi-wallet2 icon-red"></i> Cek bukti transfer
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Tabel Pesanan Terbaru ─────────────────────── -->
            <div class="row">
                <div class="col-12">
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">

                        <div class="d-flex justify-content-between align-items-center" style="padding: 20px;">
                            <h5 style="margin: 0; font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                Pesanan Terbaru & Butuh Tindakan
                            </h5>
                            <a href="<?= BASE_URL ?>/admin/pesanan.php"
                               style="font-size: 13px; color: var(--navy-dark); text-decoration: none; font-weight: 600;">
                                Lihat semua &rarr;
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-dark-header mb-0" style="min-width: 900px;">
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
                                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 60px 20px;">
                                            <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
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
                                               style="color: var(--navy-dark); font-weight: 700; font-size: 13px; text-decoration: none;">
                                                <?= htmlspecialchars($p['kode_pesanan']) ?>
                                            </a>
                                            <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                                                <?= htmlspecialchars($p['jenis_pakaian']) ?>
                                            </div>
                                        </td>
                                        <td style="font-weight: 600;">
                                            <?= htmlspecialchars($p['nama_pelanggan']) ?>
                                        </td>
                                        <td style="font-size: 13px; color: #64748b;">
                                            <?= date('d M Y', strtotime($p['tanggal_pesan'])) ?>
                                        </td>
                                        <td style="font-size: 13px; font-weight: 600;">
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

        </div><!-- end dashboard-content -->
    </div><!-- end dashboard-main -->
</div><!-- end dashboard-wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Dashboard JS -->
<script src="<?= BASE_URL ?>/assets/js/dashboard_admin.js"></script>

</body>
</html>