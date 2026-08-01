<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['identitas'] ?? '');
    $password_baru = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (empty($identitas) || empty($password_baru) || empty($konfirmasi)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif ($password_baru !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        // Cek apakah email atau no hp terdaftar di database
        $stmt = mysqli_prepare($koneksi, "SELECT id, nama FROM users WHERE email = ? OR no_hp = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $identitas, $identitas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $error = 'Akun tidak ditemukan. Pastikan Email / No. HP sudah benar.';
        } else {
            // Enkripsi password baru
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

            // Update password langsung di tabel users
            $update_stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user['id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = "Password untuk akun <strong>" . htmlspecialchars($user['nama']) . "</strong> berhasil diubah! Silakan login.";
            } else {
                $error = "Terjadi kesalahan sistem. Gagal mengubah password.";
            }
            mysqli_stmt_close($update_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — JASHIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/login.css" rel="stylesheet">
</head>
<body>

    <div class="login-left">
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-back-desktop"><i class="bi bi-arrow-left"></i> KEMBALI KE LOGIN</a>
        <div class="login-left-bg"></div>
        <div class="login-left-overlay"></div>
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-circle deco-3"></div>
        <div class="login-left-content">
            <div class="login-left-logo">JASHIT.</div>
            <div class="login-left-tagline">Silakan perbarui akses akun Anda dengan cepat dan mudah.</div>        
        </div>
    </div>

    <div class="login-right">
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-back-mobile"><i class="bi bi-arrow-left"></i> KEMBALI</a>
        <div class="login-form-wrap">
            <div class="login-avatar"><i class="bi bi-shield-lock"></i></div>
            <div class="login-title" style="font-size: 18px;">Ubah Password</div>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center" style="font-size: 13px; border-radius: 6px;">
                    <i class="bi bi-check-circle-fill d-block mb-2" style="font-size: 24px;"></i>
                    <?= $success ?>
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-login" style="text-decoration:none; display:inline-block;">MASUK SEKARANG</a>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align: center; font-size: 13px; color: #64748b; margin-bottom: 24px;">
                    Masukkan Email atau No HP yang terdaftar beserta password baru Anda.
                </p>
                <form method="POST" action="">
                    
                    <div class="input-icon-wrap">
                        <i class="bi bi-person-badge"></i>
                        <input
                            type="text"
                            name="identitas"
                            class="input-line"
                            placeholder="Email atau Nomor HP"
                            value="<?= htmlspecialchars($_POST['identitas'] ?? '') ?>"
                            required
                            autofocus
                        >
                    </div>

                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input
                            type="password"
                            name="password"
                            id="password_baru"
                            class="input-line"
                            placeholder="Password Baru"
                            required
                        >
                        <i class="bi bi-eye-slash toggle-password" data-target="password_baru"></i>
                    </div>

                    <div class="input-icon-wrap">
                        <i class="bi bi-shield-check"></i>
                        <input
                            type="password"
                            name="konfirmasi_password"
                            id="konfirmasi"
                            class="input-line"
                            placeholder="Ulangi Password Baru"
                            required
                        >
                        <i class="bi bi-eye-slash toggle-password" data-target="konfirmasi"></i>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn-login" style="width: 100%;">SIMPAN PASSWORD BARU</button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const togglePasswords = document.querySelectorAll('.toggle-password');
            
            togglePasswords.forEach(function(toggle) {
                toggle.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const inputField = document.getElementById(targetId);
                    
                    if (inputField) {
                        const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
                        inputField.setAttribute('type', type);
                        this.classList.toggle('bi-eye-slash');
                        this.classList.toggle('bi-eye');
                    }
                });
            });
        });
    </script>
</body>
</html>