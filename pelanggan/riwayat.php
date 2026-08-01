<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];

// Tangkap parameter filter dan search dari URL
$filter  = $_GET['filter'] ?? 'semua';
$search  = trim($_GET['search'] ?? '');

$halaman = max(1, (int)($_GET['hal'] ?? 1));
$offset  = ($halaman - 1) * DATA_PER_PAGE;
$limit   = DATA_PER_PAGE;

// Susun dasar WHERE Clause
$where = "WHERE user_id = ?";

// Tambahkan kondisi filter status jika dipilih
if ($filter === 'selesai') {
    $where .= " AND status IN ('selesai', 'diambil')";
} elseif ($filter === 'aktif') {
    $where .= " AND status NOT IN ('selesai', 'diambil', 'dibatalkan')";
} elseif ($filter === 'dibatalkan') {
    $where .= " AND status = 'dibatalkan'";
}

// Logika Query dipisah antara yang menggunakan Search dan Tanpa Search
if ($search !== '') {
    // Tambahkan kondisi pencarian (berdasarkan kode pesanan atau jenis pakaian)
    $where .= " AND (kode_pesanan LIKE ? OR jenis_pakaian LIKE ?)";
    $like = "%$search%";

    // Hitung total data dengan search
    $sc = mysqli_prepare($koneksi, "SELECT COUNT(*) as total FROM pesanan $where");
    mysqli_stmt_bind_param($sc, 'iss', $user_id, $like, $like);
    mysqli_stmt_execute($sc);
    $total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($sc))['total'];
    mysqli_stmt_close($sc);

    // Ambil data dengan search & pagination (ORDER BY id DESC lebih aman)
    $stmt = mysqli_prepare($koneksi, 
        "SELECT * FROM pesanan $where ORDER BY id DESC LIMIT ? OFFSET ?"
    );
    mysqli_stmt_bind_param($stmt, 'issii', $user_id, $like, $like, $limit, $offset);

} else {
    // Hitung total data tanpa search
    $sc = mysqli_prepare($koneksi, "SELECT COUNT(*) as total FROM pesanan $where");
    mysqli_stmt_bind_param($sc, 'i', $user_id);
    mysqli_stmt_execute($sc);
    $total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($sc))['total'];
    mysqli_stmt_close($sc);

    // Ambil data tanpa search & pagination
    $stmt = mysqli_prepare($koneksi, 
        "SELECT * FROM pesanan $where ORDER BY id DESC LIMIT ? OFFSET ?"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $limit, $offset);
}

// Eksekusi query
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$riwayat  = [];
while ($row = mysqli_fetch_assoc($result)) {
    $riwayat[] = $row;
}
mysqli_stmt_close($stmt);

$total_halaman = $total_data > 0 ? ceil($total_data / DATA_PER_PAGE) : 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .badge-status-custom { font-size: 11px; padding: 6px 12px; font-weight: 700; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>
        
        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 800; color: var(--navy-dark); margin: 0;">Riwayat Pesanan</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Seluruh histori pesanan Anda di Jashit.</p>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
                
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php
                    $tabs = [
                        'semua'      => 'Semua',
                        'aktif'      => 'Aktif',
                        'selesai'    => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ];
                    foreach ($tabs as $key => $lbl): ?>
                        <a href="?filter=<?= $key ?>&search=<?= urlencode($search) ?>"
                           style="padding:7px 18px;font-size:12px;font-weight:600;
                                  text-transform:uppercase;letter-spacing:0.5px;
                                  text-decoration:none;border:1px solid #e2e8f0;
                                  background:<?= $filter === $key ? 'var(--navy-dark)' : '#fff' ?>;
                                  color:<?= $filter === $key ? '#fff' : 'var(--text-main)' ?>;
                                  border-radius:4px; transition: 0.2s;">
                            <?= $lbl ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <form method="GET" action="" style="display:flex; gap:8px; max-width:300px; flex:1;">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    
                    <input type="text" name="search" 
                           class="form-control" 
                           placeholder="Cari kode atau pakaian..." 
                           value="<?= htmlspecialchars($search) ?>"
                           style="font-size:13px; padding:7px 12px; flex:1; border: 1px solid #e2e8f0;">
                    
                    <button type="submit" class="btn btn-dark" style="background-color: var(--navy-dark); padding:7px 15px; font-size:13px; border-radius:4px;">
                        <i class="bi bi-search"></i>
                    </button>
                    
                    <?php if ($search): ?>
                        <a href="?filter=<?= $filter ?>" class="btn btn-outline-secondary" style="padding:7px 12px; font-size:13px; border-radius:4px;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-bottom" style="padding: 16px 24px;">
                    <h5 class="mb-0" style="font-size: 16px; font-weight: 700;">Daftar Pesanan</h5>
                    <span style="font-size:12px;color:#64748b;">
                        Menampilkan <?= $total_data ?> pesanan
                        <?php if ($search): ?>
                            · Hasil untuk: <strong class="text-dark">"<?= htmlspecialchars($search) ?>"</strong>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if (empty($riwayat)): ?>
                    <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
                        <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                        <p style="font-size:14px;">
                            <?= $search ? 'Tidak ada pesanan yang cocok dengan pencarian Anda.' : 'Anda belum memiliki riwayat pesanan.' ?>
                        </p>
                        <?php if (!$search): ?>
                            <a href="<?= BASE_URL ?>/pelanggan/katalog.php" class="btn btn-primary fw-bold" style="background-color: var(--navy-dark); border: none; margin-top:10px;">
                                <i class="bi bi-plus-lg me-1"></i> BUAT PESANAN BARU
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width:700px;">
                            <thead style="background-color: #f8fafc; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                                <tr>
                                    <th style="padding: 16px 24px;">Kode Pesanan</th>
                                    <th>Layanan / Pakaian</th>
                                    <th class="text-center">Qty</th>
                                    <th>Tgl Pesan</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($riwayat as $p): 
                                // Mapping Warna Badge Status Realtime
                                $st = $p['status'];
                                $badge_class = 'bg-primary'; 
                                if ($st === 'menunggu_konfirmasi') { $badge_class = 'bg-warning text-dark'; }
                                elseif ($st === 'dibatalkan') { $badge_class = 'bg-danger'; }
                                elseif (in_array($st, ['selesai', 'diambil'])) { $badge_class = 'bg-success'; }
                                elseif (in_array($st, ['dikonfirmasi', 'siap_kirim'])) { $badge_class = 'bg-info text-dark'; }
                                $status_label = str_replace('_', ' ', strtoupper($st));
                                
                                // LOGIKA MENENTUKAN SATUAN (METER / PCS)
                                $is_bahan = (strpos($p['ukuran'], 'Pembelian Bahan') !== false);
                                $satuan = $is_bahan ? 'Meter' : 'Pcs';
                            ?>
                                <tr>
                                    <td style="padding: 16px 24px;">
                                        <div style="font-weight:800; font-family: monospace; font-size: 15px; color:var(--navy-dark);">
                                            <?= htmlspecialchars($p['kode_pesanan']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 14px;"><?= htmlspecialchars($p['jenis_pakaian']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $p['jumlah'] ?> <?= $satuan ?></span>
                                    </td>
                                    <td style="font-size:13px; color:#64748b; font-weight: 500;">
                                        <?= date('d M Y', strtotime($p['tanggal_pesan'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($p['total_harga'] > 0): ?>
                                            <div style="font-weight: 700; color: #10b981; font-size: 14px;">Rp <?= number_format($p['total_harga'],0,',','.') ?></div>
                                        <?php else: ?>
                                            <div style="font-size: 11px; color: #94a3b8; font-style: italic;">Menunggu Kalkulasi</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-status-custom <?= $badge_class ?>">
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="pesanan_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-dark fw-bold" style="font-size: 11px; padding: 5px 12px;">
                                            <i class="bi bi-eye"></i> DETAIL
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_halaman > 1): ?>
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-center">
                        <div class="d-flex gap-1 flex-wrap">
                            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                <a href="?filter=<?= htmlspecialchars($filter) ?>&search=<?= urlencode($search) ?>&hal=<?= $i ?>"
                                   class="btn btn-sm <?= $i === $halaman ? 'btn-dark' : 'btn-outline-secondary' ?>"
                                   style="<?= $i === $halaman ? 'background-color: var(--navy-dark); border-color: var(--navy-dark);' : '' ?> width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>