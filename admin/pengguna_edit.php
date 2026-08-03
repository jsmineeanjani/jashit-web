<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

if (!isset($_GET['id'])) {
    header('Location: pengguna.php');
    exit;
}

$id = (int)$_GET['id'];
$error = '';

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    header('Location: pengguna.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $no_hp    = trim($_POST['no_hp']);
    $role     = $_POST['role'];
    $password = $_POST['password'];

    if (empty($nama) || empty($no_hp)) {
        $error = 'Nama dan No. HP wajib diisi.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($id == $_SESSION['user_id'] && $role !== 'admin') {
        $error = 'Anda tidak dapat menurunkan hak akses akun Anda sendiri!';
    } else {
        $cek_hp = mysqli_query($koneksi, "SELECT id FROM users WHERE no_hp = '$no_hp' AND id != $id");
        if (mysqli_num_rows($cek_hp) > 0) {
            $error = 'Nomor HP sudah dipakai user lain.';
        } else {
            $email_val = empty($email) ? NULL : $email;
            
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, no_hp=?, role=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($update, 'sssssi', $nama, $email_val, $no_hp, $role, $hashed, $id);
            } else {
                $update = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, no_hp=?, role=? WHERE id=?");
                mysqli_stmt_bind_param($update, 'ssssi', $nama, $email_val, $no_hp, $role, $id);
            }

            if (mysqli_stmt_execute($update)) {
                $_SESSION['flash_success'] = 'Data user berhasil diperbarui!';
                header('Location: pengguna.php');
                exit();
            } else {
                $error = 'Gagal mengupdate data.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit User — JASHIT</title>
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
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Edit Data User</h1>
                <a href="pengguna.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <div class="section-card" style="max-width: 600px; background: #fff; border: 1px solid #e2e8f0; padding: 30px;">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="font-size: 14px;"><?= $error ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Hak Akses (Role)</label>
                        <select name="role" class="form-select" required <?= $id == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <option value="pelanggan" <?= $user['role'] == 'pelanggan' ? 'selected' : '' ?>>Pelanggan</option>
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="owner" <?= $user['role'] == 'owner' ? 'selected' : '' ?>>Owner</option>
                        </select>
                        <?php if ($id == $_SESSION['user_id']): ?>
                            <input type="hidden" name="role" value="admin">
                            <small class="text-muted" style="font-size: 11px;">Anda tidak bisa mengubah role Anda sendiri.</small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? $user['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? $user['no_hp']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Email <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>">
                    </div>
                    <hr class="my-4">
                    <div class="mb-4">
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Reset Password</label>
                        <input type="text" name="password" class="form-control" placeholder="Ketik jika ingin mereset password user ini...">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="background-color: #ea580c; border: none; padding: 10px; font-weight: 600;">UPDATE DATA USER</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>