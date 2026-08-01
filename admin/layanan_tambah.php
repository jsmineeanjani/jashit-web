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
    
    // PERBAIKAN: Pastikan estimasi_hari dikonversi menjadi integer (angka)
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
            
            // PERBAIKAN: Ubah bind_param menjadi 'sssiiss' karena estimasi_hari sekarang pasti Integer (i)
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
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Tambah Layanan / Item</h1>
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
                            <input type="text" name="nama_layanan" id="input_nama" class="form-control" placeholder="Contoh: Jahit Kemeja Batik Pria" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="kategori" class="form-select" required onchange="sesuaikanForm()">
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
                            <div class="form-text" style="font-size: 11px;">Format: JPG, PNG, WEBP. Maks: 2MB.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" class="form-select fw-bold">
                                <option value="aktif" class="text-success">Aktif (Tampil)</option>
                                <option value="nonaktif" class="text-danger">Nonaktif (Sembunyikan)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="background-color: var(--navy-dark); border:none; letter-spacing:0.5px;">
                        <i class="bi bi-save me-1"></i> SIMPAN DATA
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
            
            inputEstimasi.value = '0';

        } else if (kategori === 'Aksesoris') {
            labelNama.innerHTML = 'Nama Aksesoris <span class="text-danger">*</span>';
            inputNama.placeholder = 'Contoh: Tote Bag, Topi Custom';
            
            labelHarga.innerHTML = 'Harga Satuan (Rp)';
            kolomEstimasi.classList.add('d-none');
            kolomHarga.classList.remove('col-md-6');
            kolomHarga.classList.add('col-md-12');
            
            inputEstimasi.value = '0';

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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>