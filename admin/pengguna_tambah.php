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
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Tambah User Baru</h1>
                <a href="pengguna.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <div class="section-card" style="max-width: 600px; background: #fff; border: 1px solid #e2e8f0; padding: 30px;">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="font-size: 14px;"><?= $error ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Hak Akses (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="pelanggan">Pelanggan</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Email <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Password Default <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="background-color: var(--navy-dark); border: none; padding: 10px; font-weight: 600;">SIMPAN USER</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>