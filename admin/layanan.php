<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

// Aksi Hapus Layanan
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Cari nama file gambar sebelum dihapus dari database
    $q_gambar = mysqli_query($koneksi, "SELECT gambar FROM layanan WHERE id = $id_hapus");
    if ($row_gambar = mysqli_fetch_assoc($q_gambar)) {
        if (!empty($row_gambar['gambar'])) {
            $file_path = '../assets/img/layanan/' . $row_gambar['gambar'];
            if (file_exists($file_path)) {
                unlink($file_path); // Hapus file fisik dari folder
            }
        }
    }

    $del = mysqli_query($koneksi, "DELETE FROM layanan WHERE id = $id_hapus");
    if ($del) {
        $_SESSION['flash_success'] = 'Layanan dan gambarnya berhasil dihapus.';
    }
    header("Location: layanan.php");
    exit;
}

$query = "SELECT * FROM layanan ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Layanan Jashit — Admin JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/layanan_admin.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Layanan Jashit</h1>
                <a href="layanan_tambah.php" class="btn btn-sm" style="background-color: var(--navy-dark); color: #fff; border: none; padding: 8px 16px; font-weight: 600;">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Layanan
                </a>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-table-wrap shadow-sm">
                <table id="tabelLayanan" class="table table-hover w-100 mb-0">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th width="35%">GAMBAR & NAMA LAYANAN</th>
                            <th width="15%">KATEGORI</th>
                            <th width="20%">HARGA</th>
                            <th width="15%" class="text-center">STATUS</th>
                            <th width="10%" class="text-center">AKSI</th>
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
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if(!empty($row['gambar'])): ?>
                                            <img src="<?= BASE_URL ?>/assets/img/layanan/<?= $row['gambar'] ?>" class="img-thumb" alt="Layanan">
                                        <?php else: ?>
                                            <div class="img-placeholder"><i class="bi bi-image"></i></div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div style="font-weight: 700; color: var(--navy-dark); font-size: 15px;">
                                                <?= htmlspecialchars($row['nama_layanan']) ?>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                                <?= htmlspecialchars(substr($row['deskripsi'], 0, 40)) ?>...
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['kategori']) ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #10b981; font-size: 14px;">
                                        Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                                        <?php if ($row['kategori'] === 'Bahan/Material'): ?>
                                            <span style="font-size: 11px; font-weight: 600; color: #64748b;">/ Meter</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($row['kategori'] !== 'Bahan/Material' && $row['kategori'] !== 'Aksesoris'): ?>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                            <i class="bi bi-clock-history"></i> <?= htmlspecialchars($row['estimasi_hari']) ?> Hari
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if(strtolower($row['status']) == 'aktif'): ?>
                                        <span class="badge bg-success" style="font-size: 11px;">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 11px;">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="layanan_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit" style="padding: 4px 8px;">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="layanan.php?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus" title="Hapus" style="padding: 4px 8px;">
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

<!-- Eksternal Script -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/layanan_admin.js"></script>

</body>
</html>