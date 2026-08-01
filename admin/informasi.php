<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $del = mysqli_query($koneksi, "DELETE FROM informasi_diskon WHERE id = $id_hapus");
    if ($del) {
        $_SESSION['flash_success'] = 'Informasi diskon berhasil dihapus.';
    }
    header("Location: informasi.php");
    exit;
}

$query = "SELECT * FROM informasi_diskon ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Informasi Diskon — Admin JASHIT</title>
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
        div:where(.swal2-container) { font-family: 'Segoe UI', system-ui, sans-serif; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Informasi Diskon</h1>
                <a href="informasi_tambah.php" class="btn btn-sm" style="background-color: var(--navy-dark); color: #fff; border: none; padding: 8px 16px; font-weight: 600;">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Diskon
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-table-wrap shadow-sm">
                <table id="tabelDiskon" class="table table-hover w-100 mb-0">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th width="35%">JUDUL</th>
                            <th width="30%">PERIODE BERLAKU</th>
                            <th width="15%" class="text-center">STATUS</th>
                            <th width="15%" class="text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result && mysqli_num_rows($result) > 0): 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--navy-dark); margin-bottom: 6px;">
                                        <?= htmlspecialchars($row['judul']) ?>
                                    </div>
                                    <?php if($row['target_pelanggan'] == 'pengguna_baru'): ?>
                                        <span class="badge bg-info text-dark" style="font-size: 10px; letter-spacing: 0.5px;"><i class="bi bi-stars me-1"></i> PENGGUNA BARU</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border" style="font-size: 10px; letter-spacing: 0.5px;">SEMUA PELANGGAN</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px; color: #475569;">
                                        <i class="bi bi-calendar-event me-1"></i> 
                                        <?php 
                                        if (empty($row['tgl_mulai']) && empty($row['tgl_selesai'])) {
                                            echo '<span style="font-weight: 600;">Berlaku Selamanya</span>';
                                        } else {
                                            $mulai = !empty($row['tgl_mulai']) ? date('d M Y', strtotime($row['tgl_mulai'])) : '...';
                                            $selesai = !empty($row['tgl_selesai']) ? date('d M Y', strtotime($row['tgl_selesai'])) : 'Seterusnya';
                                            echo $mulai . ' <span class="text-muted mx-1">-</span> ' . $selesai;
                                        }
                                        ?>
                                    </div>
                                    <?php 
                                        if (!empty($row['tgl_selesai'])) {
                                            $sekarang = date('Y-m-d');
                                            if ($sekarang > $row['tgl_selesai']) {
                                                echo '<small class="text-danger mt-1 d-block" style="font-size:11px; font-weight:600;">(Sudah Kedaluwarsa)</small>';
                                            }
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if($row['status'] == 'aktif'): ?>
                                        <span class="badge bg-success" style="font-size: 11px; padding: 5px 10px; letter-spacing: 0.5px;">AKTIF</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 11px; padding: 5px 10px; letter-spacing: 0.5px;">NONAKTIF</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="informasi_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="informasi.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus" title="Hapus Permanen">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
        $('#tabelDiskon').DataTable({
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": { "search": "", "searchPlaceholder": "Cari diskon..." },
            "ordering": false 
        });

        $(document).on('click', '.btn-hapus', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Hapus Diskon?',
                text: "Data promo yang dihapus tidak bisa dikembalikan.",
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