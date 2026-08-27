<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan  = trim($_POST['nama_layanan']);
    $kategori      = trim($_POST['kategori']);
    $deskripsi     = trim($_POST['deskripsi']);
    $harga         = (int)($_POST['harga'] ?? 0);
    
    $estimasi_hari = (int)($_POST['estimasi_hari'] ?? 0);
    $status        = trim($_POST['status']);
    
    // LOGIKA PINTAR: Jika yang diinput adalah Bahan atau Aksesoris, paksa estimasi jadi 0
    if ($kategori === 'Bahan/Material' || $kategori === 'Aksesoris') {
        $estimasi_hari = 0;
    }
    
    $gambar_nama = NULL;

    // --- LOGIKA UPLOAD GAMBAR ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['gambar']['tmp_name'];
        $file_name = $_FILES['gambar']['name'];
        $file_size = $_FILES['gambar']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            $error = 'Format gambar harus JPG, PNG, atau WEBP!';
        } elseif ($file_size > 2000000) { 
            $error = 'Ukuran gambar maksimal 2MB!';
        } else {
            $gambar_nama = 'layanan_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $upload_path = '../assets/img/layanan/' . $gambar_nama;
            
            if (!is_dir('../assets/img/layanan/')) {
                mkdir('../assets/img/layanan/', 0777, true);
            }

            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $error = 'Gagal mengunggah gambar ke folder server.';
            }
        }
    }

    if (empty($error)) {
        if (empty($nama_layanan) || empty($kategori)) {
            $error = 'Nama layanan dan kategori wajib diisi.';
        } else {
            $query = "INSERT INTO layanan (nama_layanan, kategori, deskripsi, harga, estimasi_hari, status, gambar) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $query);
            
            mysqli_stmt_bind_param($stmt, 'sssiiss', $nama_layanan, $kategori, $deskripsi, $harga, $estimasi_hari, $status, $gambar_nama);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_success'] = 'Data baru berhasil ditambahkan!';
                header('Location: layanan.php');
                exit();
            } else {
                $error = 'Gagal menyimpan data ke database.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Layanan — Admin JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/layanan_admin.css" rel="stylesheet">
</head>
<body class="layanan-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
         <?php require_once '../includes/layouts/topbar_admin.php'; ?>

        <div class="dashboard-content layanan-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="layanan-page-title">Tambah Layanan</h1>
                <a href="layanan.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="form-card-layanan shadow-sm mx-auto">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label id="label_nama" class="form-label fw-bold small">Nama Layanan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_layanan" id="input_nama" class="form-control" placeholder="Contoh: Jahit Kemeja Batik Pria" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Kategori <span class="text-danger">*</span></label>
                            <!-- onchange dihapus, sudah dihandle oleh JS -->
                            <select name="kategori" id="kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Jahit Baru">Jahit Baru</option>
                                <option value="Permak">Permak</option>
                                <option value="Bahan/Material">Bahan/Material</option>
                                <option value="Aksesoris">Aksesoris</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6" id="kolom_harga">
                            <label id="label_harga" class="form-label fw-bold small">Harga Mulai (Rp)</label>
                            <input type="number" name="harga" class="form-control" placeholder="Contoh: 150000">
                        </div>
                        <div class="col-md-6" id="kolom_estimasi">
                            <label class="form-label fw-bold small">Estimasi Pengerjaan (Hari)</label>
                            <input type="number" name="estimasi_hari" id="estimasi_hari" class="form-control" placeholder="Contoh: 7" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan detail layanan atau deskripsi spesifikasi bahan/aksesoris..."></textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Gambar Referensi / Contoh</label>
                            <input type="file" name="gambar" class="form-control" accept=".jpg, .jpeg, .png, .webp">
                            <div class="form-text form-text-sm">Format: JPG, PNG, WEBP. Maks: 2MB.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" class="form-select fw-bold">
                                <option value="aktif" class="text-success">Aktif (Tampil)</option>
                                <option value="nonaktif" class="text-danger">Nonaktif (Sembunyikan)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-submit-layanan w-100 py-3 fw-bold">
                        <i class="bi bi-save me-1"></i> SIMPAN DATA
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Eksternal Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/layanan_admin.js"></script>
</body>
</html>