<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin'); 

// Aksi Update Status (Ditampilkan / Disembunyikan / Hapus)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_feedback = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'tampil') {
        mysqli_query($koneksi, "UPDATE feedback SET status = 'ditampilkan' WHERE id = $id_feedback");
        $_SESSION['flash_success'] = 'Feedback berhasil ditampilkan di Halaman Utama.';
    } elseif ($action == 'sembunyi') {
        mysqli_query($koneksi, "UPDATE feedback SET status = 'disembunyikan' WHERE id = $id_feedback");
        $_SESSION['flash_success'] = 'Feedback disembunyikan dari Halaman Utama.';
    } elseif ($action == 'hapus') {
        mysqli_query($koneksi, "DELETE FROM feedback WHERE id = $id_feedback");
        $_SESSION['flash_success'] = 'Feedback berhasil dihapus secara permanen.';
    }
    header("Location: feedback.php");
    exit;
}

// JOIN dengan tabel users untuk memunculkan nama_pelanggan
$query = "SELECT f.*, u.nama AS nama_pelanggan 
          FROM feedback f 
          LEFT JOIN users u ON f.user_id = u.id 
          ORDER BY f.id DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Feedback — Admin JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <!-- Memanggil file CSS Feedback Eksternal -->
    <link href="<?= BASE_URL ?>/assets/css/feedback.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Kelola Feedback</h1>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-table-wrap shadow-sm">
                <table id="tabelFeedback" class="table table-hover w-100 mb-0">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th width="20%">NAMA PELANGGAN</th>
                            <th width="40%">ULASAN & RATING</th>
                            <th width="15%" class="text-center">STATUS</th>
                            <th width="20%" class="text-center">ACTION</th>
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
                                <td style="font-weight: 600; color: var(--navy-dark);">
                                    <?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Jashit') ?>
                                    <div style="font-size: 11px; color: #94a3b8; font-weight: 400; margin-top: 3px;">
                                        <?= date('d M Y', strtotime($row['created_at'] ?? 'now')) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="star-rating mb-1">
                                        <?php 
                                        $rating = (int)($row['rating'] ?? 5);
                                        for($i=1; $i<=5; $i++) {
                                            echo $i <= $rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div style="font-size: 13px; line-height: 1.5; color: #475569;">
                                        "<?= htmlspecialchars($row['komentar']) ?>"
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if(($row['status'] ?? 'menunggu') == 'ditampilkan'): ?>
                                        <span class="badge bg-success" style="font-size: 11px; padding: 5px 10px;">DITAMPILKAN</span>
                                    <?php elseif(($row['status'] ?? 'menunggu') == 'disembunyikan'): ?>
                                        <span class="badge bg-secondary" style="font-size: 11px; padding: 5px 10px;">DISEMBUNYIKAN</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark" style="font-size: 11px; padding: 5px 10px;">BARU</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if(($row['status'] ?? 'menunggu') == 'ditampilkan'): ?>
                                        <a href="feedback.php?action=sembunyi&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Sembunyikan dari Landing Page">
                                            <i class="bi bi-eye-slash"></i> Sembunyikan
                                        </a>
                                    <?php else: ?>
                                        <a href="feedback.php?action=tampil&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success me-1" title="Tampilkan di Landing Page">
                                            <i class="bi bi-eye"></i> Tampilkan
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="feedback.php?action=hapus&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-hapus" title="Hapus Permanen">
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

<!-- Memanggil file JS Feedback Eksternal -->
<script src="<?= BASE_URL ?>/assets/js/feedback.js"></script>
</body>
</html>