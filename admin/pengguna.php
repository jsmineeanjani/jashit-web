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
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/pengguna.css" rel="stylesheet">
</head>
<body class="pengguna-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content pengguna-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="pengguna-page-title">Manajemen User</h1>
                <a href="pengguna_tambah.php" class="btn btn-sm btn-tambah-user">
                    <i class="bi bi-plus-lg me-1"></i> Tambah User
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show alert-pengguna" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-pengguna" role="alert">
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
                                <td class="user-name-cell">
                                    <?= htmlspecialchars($row['nama']) ?>
                                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-light text-dark border ms-1 badge-anda">ANDA</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['email'])): ?>
                                        <div class="user-email"><?= htmlspecialchars($row['email']) ?></div>
                                    <?php endif; ?>
                                    <div class="user-phone">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($row['no_hp']) ?>
                                    </div>
                                </td>
                                
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
                                                <span class="badge bg-warning text-dark border border-warning shadow-sm badge-member-status">
                                                    <i class="bi bi-star-fill text-dark me-1"></i> GOLD
                                                </span>
                                            <?php elseif ($status == 'Silver'): ?>
                                                <span class="badge bg-light text-dark border border-secondary shadow-sm badge-member-status">
                                                    <i class="bi bi-star-half text-secondary me-1"></i> SILVER
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border shadow-sm badge-member-status">
                                                    <i class="bi bi-star me-1"></i> CLASSIC
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <a href="pengguna_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-outline-danger disabled btn-hapus-disabled" title="Tidak dapat menghapus akun sendiri">
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

<!-- Eksternal Script -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/pengguna.js"></script>
</body>
</html>