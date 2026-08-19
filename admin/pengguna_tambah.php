<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'pelanggan';

    if (empty($nama) || empty($no_hp) || empty($password)) {
        $error = 'Nama, No. HP, dan Password wajib diisi.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE no_hp = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $no_hp);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Nomor HP/WhatsApp sudah terdaftar!';
        } else {
            if (empty($error)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $email_val = empty($email) ? NULL : $email;

                $stmt2 = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role, no_hp) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, 'sssss', $nama, $email_val, $hashed, $role, $no_hp);

                if (mysqli_stmt_execute($stmt2)) {
                    $_SESSION['flash_success'] = 'User baru berhasil ditambahkan!';
                    header('Location: pengguna.php');
                    exit();
                } else {
                    $error = 'Gagal menyimpan data.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah User — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
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
                <h1 class="pengguna-page-title">Tambah User Baru</h1>
                <a href="pengguna.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <div class="form-card-pengguna shadow-sm">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-pengguna"><?= $error ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Hak Akses (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="pelanggan">Pelanggan</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Email <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label form-label-custom">Password Default <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <button type="submit" class="btn w-100 btn-submit-pengguna">SIMPAN USER</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>