<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin'); 

// Hapus Pengguna
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Proteksi: Admin tidak boleh menghapus akunnya sendiri
    if ($id_hapus == $_SESSION['user_id']) {
        $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri!';
    } else {
        $del = mysqli_query($koneksi, "DELETE FROM users WHERE id = $id_hapus");
        if ($del) {
            $_SESSION['flash_success'] = 'Data pengguna berhasil dihapus.';
        }
    }
    header("Location: pengguna.php");
    exit;
}

// Ambil semua data pengguna (termasuk status_member)
$query = "SELECT * FROM users ORDER BY FIELD(role, 'admin', 'owner', 'pelanggan'), id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen User — Admin JASHIT</title>
    <!-- Favicon -->
     <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .card-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 24px; }
        
        table.dataTable thead th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 14px 15px;
        }
        
        table.dataTable tbody td {
            vertical-align: middle;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 15px;
        }

        .dataTables_wrapper .row:first-child { align-items: center; margin-bottom: 20px; }
        div.dataTables_filter { text-align: left !important; }
        div.dataTables_filter input { width: 250px; display: inline-block; margin-left: 0; margin-right: 10px; }
        div.dataTables_length { text-align: right !important; }
        div.dataTables_length select { width: auto; display: inline-block; margin: 0 8px; }
        
        .badge-role { font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase; padding: 5px 10px; font-weight: 600; }
        
        /* Kustomisasi font SweetAlert agar senada dengan Jashit */
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
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Manajemen User</h1>
                <a href="pengguna_tambah.php" class="btn btn-sm" style="background-color: var(--navy-dark); color: #fff; border: none; padding: 8px 16px; font-weight: 600;">
                    <i class="bi bi-plus-lg me-1"></i> Tambah User
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-exclamation-triangle me-2"></i> <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card-table-wrap shadow-sm">
                <table id="tabelPengguna" class="table table-hover w-100 mb-0">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th>NAMA</th>
                            <th>EMAIL / KONTAK</th>
                            <th>ROLE</th>
                            <th width="15%" class="text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0): 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td style="font-weight: 600; color: var(--navy-dark);">
                                    <?= htmlspecialchars($row['nama']) ?>
                                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-light text-dark border ms-1" style="font-size: 9px;">ANDA</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['email'])): ?>
                                        <div style="margin-bottom: 3px;"><?= htmlspecialchars($row['email']) ?></div>
                                    <?php endif; ?>
                                    <div style="font-size: 12px; color: #64748b;">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($row['no_hp']) ?>
                                    </div>
                                </td>
                                
                                <!-- Modifikasi Bagian Role & Status Member -->
                                <td>
                                    <?php if ($row['role'] == 'admin'): ?>
                                        <span class="badge bg-danger badge-role">ADMIN</span>
                                    <?php elseif ($row['role'] == 'owner'): ?>
                                        <span class="badge bg-primary badge-role">OWNER</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary badge-role">PELANGGAN</span>
                                        
                                        <!-- Menampilkan Badge Status Member Khusus Pelanggan -->
                                        <div class="mt-2">
                                            <?php 
                                            // Cek status member dari database
                                            $status = $row['status_member'] ?? 'Classic'; 
                                            
                                            if ($status == 'Gold'): ?>
                                                <span class="badge bg-warning text-dark border border-warning shadow-sm" style="font-size: 10px;">
                                                    <i class="bi bi-star-fill text-dark me-1"></i> GOLD
                                                </span>
                                            <?php elseif ($status == 'Silver'): ?>
                                                <span class="badge bg-light text-dark border border-secondary shadow-sm" style="font-size: 10px;">
                                                    <i class="bi bi-star-half text-secondary me-1"></i> SILVER
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border shadow-sm" style="font-size: 10px;">
                                                    <i class="bi bi-star me-1"></i> CLASSIC
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <!-- Selesai Modifikasi -->
                                
                                <td class="text-center">
                                    <a href="pengguna_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-outline-danger disabled" title="Tidak dapat menghapus akun sendiri" style="cursor: not-allowed; opacity: 0.5;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="pengguna.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi DataTables
        $('#tabelPengguna').DataTable({
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": {
                "search": "",
                "searchPlaceholder": "Search...",
                "lengthMenu": "_MENU_ entries per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "infoEmpty": "Menampilkan 0 data",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            },
            "ordering": false 
        });

        // Script SweetAlert2 untuk konfirmasi hapus
        $(document).on('click', '.btn-hapus', function(e) {
            e.preventDefault(); // Mencegah link langsung berpindah
            const href = $(this).attr('href'); // Ambil link hapus dari tombol

            Swal.fire({
                title: 'Hapus User?',
                text: "Yakin ingin menghapus user ini secara permanen? Data yang dihapus tidak dapat dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // Warna merah untuk aksi hapus
                cancelButtonColor: '#64748b',  // Warna abu-abu untuk batal
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true // Menukar posisi tombol (Batal di kiri, Hapus di kanan)
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika diklik 'Ya', arahkan ke link hapus
                    window.location.href = href;
                }
            })
        });
    });
</script>
</body>
</html>