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
    <style>
        .card-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 24px; }
        table.dataTable thead th { background-color: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #e2e8f0 !important; padding: 14px 15px; }
        table.dataTable tbody td { vertical-align: middle; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; padding: 16px 15px; }
        .dataTables_wrapper .row:first-child { align-items: center; margin-bottom: 20px; }
        div.dataTables_filter { text-align: left !important; }
        div.dataTables_filter input { width: 250px; display: inline-block; margin-left: 0; margin-right: 10px; }
        div.dataTables_length { text-align: right !important; }
        div.dataTables_length select { width: auto; display: inline-block; margin: 0 8px; }
        .badge-status { font-size: 10px; letter-spacing: 0.5px; padding: 5px 8px; text-transform: uppercase; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Kelola Pesanan</h1>
                <a href="pesanan_tambah.php" class="btn btn-sm" style="background-color: var(--navy-dark); color: #fff; border: none; padding: 8px 16px; font-weight: 600;">
                    <i class="bi bi-plus-lg me-1"></i> Buat Pesanan Baru
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 14px;">
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
                                    <div style="font-weight: 700; color: var(--navy-dark); font-family: monospace; font-size: 15px;">
                                        <?= htmlspecialchars($row['kode_pesanan']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                        <?= date('d M Y', strtotime($row['tanggal_pesan'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155; font-size: 14px;">
                                        <?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Walk-in') ?>
                                    </div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                        <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($row['no_hp'] ?? $row['user_id']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px; font-weight: 600; color: #475569;">
                                        <?= htmlspecialchars($row['jenis_pakaian']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                        Qty: <?= htmlspecialchars($row['jumlah']) ?> <?= $is_bahan ? 'Meter' : 'pcs' ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--navy-dark); font-size: 14px;">
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
                                            <div style="font-size: 11px; color: #ef4444; font-weight: 600; margin-top: 4px;">
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
                                                echo '<div style="font-size: 10px; color: #10b981; margin-top: 4px; font-weight: bold;"><i class="bi bi-calendar-check"></i> ' . date('d M Y', strtotime($row['tanggal_selesai'])) . '</div>';
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
                                        <a href="pesanan_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="Lihat Detail" style="padding: 4px 8px;">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="pesanan_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Pesanan" style="padding: 4px 8px;">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="pesanan.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus" title="Hapus Permanen" style="padding: 4px 8px;">
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

<script>
    $(document).ready(function() {
        $('#tabelPesanan').DataTable({
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": { "search": "", "searchPlaceholder": "Cari pesanan..." },
            "ordering": false 
        });

        $(document).on('click', '.btn-hapus', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Hapus Pesanan?',
                text: "Yakin ingin menghapus data pesanan ini? Aksi ini tidak dapat dibatalkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) window.location.href = href;
            });
        });
    });
</script>
</body>
</html>