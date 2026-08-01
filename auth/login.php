<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['identitas'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($identitas) || empty($password)) {
        $error = 'Email/No. HP dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id, nama, email, no_hp, password, role, status 
             FROM users WHERE email = ? OR no_hp = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $identitas, $identitas);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $error = 'Akun tidak ditemukan. Periksa kembali Email / No. HP Anda.';
        } elseif ($user['status'] === 'nonaktif') {
            $error = 'Akun Anda telah dinonaktifkan. Hubungi admin.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Password yang Anda masukkan salah.';
        } else {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_nama']  = $user['nama'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['flash_success'] = 'Selamat datang, ' . $user['nama'] . '!';
            redirectByRole();
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/login.css" rel="stylesheet">
</head>
<body>

    <div class="login-left">
        <a href="<?= BASE_URL ?>/public/index.php" class="btn-back-desktop"><i class="bi bi-arrow-left"></i> KEMBALI</a>
        <div class="login-left-bg"></div>
        <div class="login-left-overlay"></div>
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-circle deco-3"></div>
        <div class="login-left-content">
            <div class="login-left-logo">JASHIT.</div>
            <div class="login-left-tagline">Dari kain biasa menjadi karya luar biasa,<br>dijahit dengan presisi di setiap detail,<br>untuk hasil rapi dan profesional.</div>        
        </div>
    </div>

    <div class="login-right">
        <a href="<?= BASE_URL ?>/public/index.php" class="btn-back-mobile"><i class="bi bi-arrow-left"></i> KEMBALI</a>
        <div class="login-form-wrap">
            <div class="login-avatar"><i class="bi bi-person"></i></div>
            <div class="login-title">Login</div>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="input-icon-wrap">
                    <i class="bi bi-person-badge"></i>
                    <input
                        type="text"
                        name="identitas"
                        class="input-line"
                        placeholder="Email atau Nomor Telp"
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
                        id="password"
                        class="input-line"
                        placeholder="Password"
                        required
                    >
                    <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
                </div>

                <div class="d-flex justify-content-end align-items-center mb-4 mt-1">
                     <a href="<?= BASE_URL ?>/auth/lupa_password.php" style="font-size:12px; color:#1e293b; text-decoration:none; font-weight:600; transition: color 0.2s;">Lupa Password?</a>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn-login">LOGIN</button>
                </div>

            </form>

            <div class="text-center mt-4" style="font-size:13px; color:#94a3b8;">
                Belum punya akun?
                <a href="<?= BASE_URL ?>/auth/register.php" style="color:#1e293b; font-weight:600;">Daftar di sini</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?= BASE_URL ?>/assets/js/login.js"></script>
</body>
</html>