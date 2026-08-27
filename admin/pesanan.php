<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $del = mysqli_query($koneksi, "DELETE FROM pesanan WHERE id = $id_hapus");
    if ($del) {
        $_SESSION['flash_success'] = 'Data pesanan berhasil dihapus.';
    }
    header("Location: pesanan.php");
    exit;
}

$query = "SELECT p.*, u.nama AS nama_pelanggan, u.no_hp, 
          (SELECT status_verifikasi FROM transaksi t WHERE t.pesanan_id = p.id ORDER BY t.id DESC LIMIT 1) as status_transaksi 
          FROM pesanan p 
          LEFT JOIN users u ON p.user_id = u.id 
          ORDER BY p.id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesanan — Admin JASHIT </title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
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
                <h1 class="pesanan-page-title">Kelola Pesanan</h1>
                <a href="pesanan_tambah.php" class="btn btn-sm btn-tambah-pesanan">
                    <i class="bi bi-plus-lg me-1"></i> Buat Pesanan Baru
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show alert-pesanan" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-table-wrap shadow-sm">
                <table id="tabelPesanan" class="table table-hover w-100 mb-0">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th width="15%">KODE & TGL</th>
                            <th width="20%">PELANGGAN</th>
                            <th width="15%">ITEM</th>
                            <th width="20%">TOTAL & BAYAR</th>
                            <th width="15%" class="text-center">PROGRES</th>
                            <th width="10%" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result && mysqli_num_rows($result) > 0): 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                                // DETEKSI JENIS PEMBELIAN UNTUK STATUS DINAMIS
                                $teks_ukuran = strtoupper($row['ukuran']);
                                $is_bahan = (strpos($teks_ukuran, 'PEMBELIAN BAHAN') !== false);
                                $is_aksesoris = (strpos($teks_ukuran, 'PEMBELIAN AKSESORIS') !== false);
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="pesanan-kode">
                                        <?= htmlspecialchars($row['kode_pesanan']) ?>
                                    </div>
                                    <div class="pesanan-tgl">
                                        <?= date('d M Y', strtotime($row['tanggal_pesan'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="pesanan-nama">
                                        <?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Walk-in') ?>
                                    </div>
                                    <div class="text-meta">
                                        <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($row['no_hp'] ?? $row['user_id']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="pesanan-item-nama">
                                        <?= htmlspecialchars($row['jenis_pakaian']) ?>
                                    </div>
                                    <div class="text-meta">
                                        Qty: <?= htmlspecialchars($row['jumlah']) ?> <?= $is_bahan ? 'Meter' : 'pcs' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="pesanan-total">
                                        Total: Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                    </div>
                                    <div class="mt-1">
                                        <?php 
                                        $tot = (int)$row['total_harga'];
                                        $dp_b = (int)$row['dp_dibayar'];
                                        $sisa_b = (int)$row['sisa_tagihan'];
                                        $transaksi_status = strtolower($row['status_transaksi'] ?? '');

                                        if($tot > 0 && $sisa_b <= 0): ?>
                                            <span class="badge bg-success badge-status"><i class="bi bi-check-all"></i> LUNAS</span>
                                        <?php elseif($dp_b > 0 && $sisa_b > 0): ?>
                                            <span class="badge bg-info text-dark badge-status border border-info">DP: Rp <?= number_format($dp_b, 0, ',', '.') ?></span>
                                            <div class="pesanan-sisa-warn">
                                                <i class="bi bi-exclamation-circle"></i> Sisa: Rp <?= number_format($sisa_b, 0, ',', '.') ?>
                                            </div>
                                        <?php elseif($transaksi_status == 'ditolak' || $transaksi_status == 'tidak valid'): ?>
                                            <span class="badge bg-danger badge-status"><i class="bi bi-x-circle me-1"></i>BUKTI DITOLAK</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-status">BELUM BAYAR</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $status_jahit = $row['status'];
                                    
                                    switch ($status_jahit) {
                                        case 'menunggu_konfirmasi':
                                            echo '<span class="badge bg-warning text-dark badge-status">MENUNGGU KONFIRMASI</span>';
                                            break;
                                        case 'dikonfirmasi':
                                            echo '<span class="badge bg-info text-dark badge-status">DIKONFIRMASI</span>';
                                            break;
                                        case 'proses_cutting':
                                            if ($is_bahan) echo '<span class="badge bg-primary badge-status">PENYIAPAN BAHAN</span>';
                                            elseif ($is_aksesoris) echo '<span class="badge bg-primary badge-status">PENYIAPAN AKSESORIS</span>';
                                            else echo '<span class="badge bg-primary badge-status">PROSES CUTTING</span>';
                                            break;
                                        case 'proses_jahit':
                                            if ($is_bahan || $is_aksesoris) echo '<span class="badge badge-status" style="background-color:#3b82f6;">PENYIAPAN (LANJUTAN)</span>';
                                            else echo '<span class="badge badge-status" style="background-color:#3b82f6;">PROSES JAHIT</span>';
                                            break;
                                        case 'proses_finishing':
                                            if ($is_bahan || $is_aksesoris) echo '<span class="badge badge-status" style="background-color:#6366f1;">PENYIAPAN (LANJUTAN)</span>';
                                            else echo '<span class="badge badge-status" style="background-color:#6366f1;">PROSES FINISHING</span>';
                                            break;
                                        case 'quality_check':
                                            if ($is_bahan || $is_aksesoris) echo '<span class="badge bg-secondary badge-status">PENYIAPAN (LANJUTAN)</span>';
                                            else echo '<span class="badge bg-secondary badge-status">QUALITY CHECK (QC)</span>';
                                            break;
                                        case 'selesai':
                                            echo '<span class="badge bg-success badge-status">SELESAI</span>';
                                            if (!empty($row['tanggal_selesai'])) {
                                                echo '<div class="pesanan-selesai-tgl"><i class="bi bi-calendar-check"></i> ' . date('d M Y', strtotime($row['tanggal_selesai'])) . '</div>';
                                            }
                                            break;
                                        case 'dibatalkan':
                                            echo '<span class="badge bg-danger badge-status">DIBATALKAN</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary badge-status">'.strtoupper($status_jahit).'</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="pesanan_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info btn-aksi-sm" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="pesanan_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary btn-aksi-sm" title="Edit Pesanan">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="pesanan.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus btn-aksi-sm" title="Hapus Permanen">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/pesanan_admin.js"></script>
</body>
</html>