<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (!isset($_GET['id'])) {
    header('Location: layanan.php');
    exit;
}

$id = (int)$_GET['id'];
$error = '';

// Ambil data layanan yang mau diedit
$query_select = "SELECT * FROM layanan WHERE id = $id LIMIT 1";
$result = mysqli_query($koneksi, $query_select);
$layanan = mysqli_fetch_assoc($result);

if (!$layanan) {
    header('Location: layanan.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan  = trim($_POST['nama_layanan']);
    $kategori      = trim($_POST['kategori']);
    $deskripsi     = trim($_POST['deskripsi']);
    $harga         = (int)($_POST['harga'] ?? 0); 
    $estimasi_hari = (int)($_POST['estimasi_hari'] ?? 0); 
    $status        = trim($_POST['status']);
    
    // LOGIKA PINTAR: Jika yang diinput bahan atau aksesoris, paksa estimasi jadi 0
    if ($kategori === 'Bahan/Material' || $kategori === 'Aksesoris') {
        $estimasi_hari = 0;
    }
    
    $gambar_nama = $layanan['gambar']; // Default pakai gambar lama

    // --- LOGIKA UPLOAD GAMBAR BARU (JIKA ADA) ---
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
            $gambar_baru = 'layanan_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $upload_path = '../assets/img/layanan/' . $gambar_baru;
            
            if (!is_dir('../assets/img/layanan/')) {
                mkdir('../assets/img/layanan/', 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Hapus gambar lama jika ada dan bukan gambar default
                if (!empty($layanan['gambar']) && file_exists('../assets/img/layanan/' . $layanan['gambar'])) {
                    unlink('../assets/img/layanan/' . $layanan['gambar']);
                }
                $gambar_nama = $gambar_baru; // Pakai nama gambar baru
            } else {
                $error = 'Gagal mengunggah gambar ke folder server.';
            }
        }
    }

    if (empty($error)) {
        if (empty($nama_layanan) || empty($kategori)) {
            $error = 'Nama layanan dan kategori wajib diisi.';
        } else {
            $query = "UPDATE layanan SET nama_layanan=?, kategori=?, deskripsi=?, harga=?, estimasi_hari=?, status=?, gambar=? WHERE id=?";
            $stmt = mysqli_prepare($koneksi, $query);
            
            mysqli_stmt_bind_param($stmt, 'sssiissi', $nama_layanan, $kategori, $deskripsi, $harga, $estimasi_hari, $status, $gambar_nama, $id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_success'] = 'Data layanan/item berhasil diperbarui!';
                header('Location: layanan.php');
                exit();
            } else {
                $error = 'Gagal memperbarui data di database.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Layanan — Admin JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Edit Layanan / Item</h1>
                <a href="layanan.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="section-card shadow-sm p-4" style="background:#fff; border-radius:8px; max-width: 900px;">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label id="label_nama" class="form-label fw-bold small">Nama Layanan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_layanan" id="input_nama" class="form-control" value="<?= htmlspecialchars($layanan['nama_layanan']) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="kategori" class="form-select" required onchange="sesuaikanForm()">
                                <option value="Jahit Baru" <?= $layanan['kategori'] === 'Jahit Baru' ? 'selected' : '' ?>>Jahit Baru</option>
                                <option value="Permak" <?= $layanan['kategori'] === 'Permak' ? 'selected' : '' ?>>Permak</option>
                                <option value="Bahan/Material" <?= $layanan['kategori'] === 'Bahan/Material' ? 'selected' : '' ?>>Bahan/Material</option>
                                <option value="Aksesoris" <?= $layanan['kategori'] === 'Aksesoris' ? 'selected' : '' ?>>Aksesoris</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6" id="kolom_harga">
                            <label id="label_harga" class="form-label fw-bold small">Harga Mulai (Rp)</label>
                            <input type="number" name="harga" class="form-control" value="<?= htmlspecialchars($layanan['harga']) ?>">
                        </div>
                        <div class="col-md-6" id="kolom_estimasi">
                            <label class="form-label fw-bold small">Estimasi Pengerjaan (Hari)</label>
                            <input type="number" name="estimasi_hari" id="estimasi_hari" class="form-control" value="<?= htmlspecialchars($layanan['estimasi_hari']) ?>" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($layanan['deskripsi']) ?></textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Ganti Gambar (Opsional)</label>
                            <input type="file" name="gambar" class="form-control" accept=".jpg, .jpeg, .png, .webp">
                            <div class="form-text" style="font-size: 11px;">Biarkan kosong jika tidak ingin mengubah gambar. Format: JPG, PNG, WEBP. Maks: 2MB.</div>
                            
                            <?php if (!empty($layanan['gambar'])): ?>
                                <div class="mt-2">
                                    <span class="badge bg-secondary">Gambar saat ini:</span><br>
                                    <img src="<?= BASE_URL ?>/assets/img/layanan/<?= $layanan['gambar'] ?>" alt="Gambar Layanan" style="max-height: 80px; border-radius: 6px; margin-top: 5px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" class="form-select fw-bold">
                                <option value="aktif" class="text-success" <?= $layanan['status'] === 'aktif' ? 'selected' : '' ?>>Aktif (Tampil)</option>
                                <option value="nonaktif" class="text-danger" <?= $layanan['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (Sembunyikan)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="background-color: var(--navy-dark); border:none; letter-spacing:0.5px;">
                        <i class="bi bi-save me-1"></i> SIMPAN PERUBAHAN
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function sesuaikanForm() {
        const kategori = document.getElementById('kategori').value;
        
        const labelNama = document.getElementById('label_nama');
        const inputNama = document.getElementById('input_nama');
        const labelHarga = document.getElementById('label_harga');
        const kolomHarga = document.getElementById('kolom_harga');
        const kolomEstimasi = document.getElementById('kolom_estimasi');
        const inputEstimasi = document.getElementById('estimasi_hari');

        if (kategori === 'Bahan/Material') {
            labelNama.innerHTML = 'Nama Bahan <span class="text-danger">*</span>';
            inputNama.placeholder = 'Contoh: Kain Katun Jepang';
            
            labelHarga.innerHTML = 'Harga Per Meter (Rp)';
            kolomEstimasi.classList.add('d-none');
            kolomHarga.classList.remove('col-md-6');
            kolomHarga.classList.add('col-md-12');
            
            if(inputEstimasi.value === '' || inputEstimasi.value > 0) {
                 inputEstimasi.value = '0';
            }
            
        } else if (kategori === 'Aksesoris') {
            labelNama.innerHTML = 'Nama Aksesoris <span class="text-danger">*</span>';
            inputNama.placeholder = 'Contoh: Tote Bag, Topi Custom';
            
            labelHarga.innerHTML = 'Harga Satuan (Rp)';
            kolomEstimasi.classList.add('d-none');
            kolomHarga.classList.remove('col-md-6');
            kolomHarga.classList.add('col-md-12');
            
            if(inputEstimasi.value === '' || inputEstimasi.value > 0) {
                 inputEstimasi.value = '0';
            }
            
        } else {
            labelNama.innerHTML = 'Nama Layanan <span class="text-danger">*</span>';
            inputNama.placeholder = 'Contoh: Jahit Kemeja Batik Pria';
            
            labelHarga.innerHTML = 'Harga Mulai (Rp)';
            kolomEstimasi.classList.remove('d-none');
            kolomHarga.classList.remove('col-md-12');
            kolomHarga.classList.add('col-md-6');
            
            if (inputEstimasi.value === '0') {
                inputEstimasi.value = '';
            }
        }
    }

    // Jalankan otomatis saat halaman pertama kali diload agar menyesuaikan kategori bawaan dari database
    document.addEventListener("DOMContentLoaded", function() {
        sesuaikanForm();
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>