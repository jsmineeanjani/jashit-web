<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

// Jika sudah login, arahkan kembali sesuai role
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? ''); 
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirmasi_password'] ?? '';

    // Validasi Dasar
    if (empty($nama) || empty($password) || empty($no_hp)) {
        $error = 'Nama, No. HP/WhatsApp, dan password wajib diisi.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek apakah No HP sudah terdaftar
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE no_hp = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $no_hp);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Nomor HP/WhatsApp sudah terdaftar. Gunakan nomor lain atau silakan login.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            
            // Cek Email jika diisi
            if (!empty($email)) {
                $stmt_email = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt_email, 's', $email);
                mysqli_stmt_execute($stmt_email);
                mysqli_stmt_store_result($stmt_email);
                
                if (mysqli_stmt_num_rows($stmt_email) > 0) {
                    $error = 'Email sudah terdaftar. Gunakan email lain.';
                    mysqli_stmt_close($stmt_email);
                }
            }

            // Lanjut ke penyimpanan jika tidak ada error
            if (empty($error)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $email_val = empty($email) ? NULL : $email;

                // Query INSERT disesuaikan (tanpa alamat)
                $stmt2 = mysqli_prepare($koneksi,
                    "INSERT INTO users (nama, email, password, role, no_hp)
                     VALUES (?, ?, ?, 'pelanggan', ?)"
                );
                mysqli_stmt_bind_param($stmt2, 'ssss',
                    $nama, $email_val, $hashed, $no_hp
                );

                if (mysqli_stmt_execute($stmt2)) {
                    
                    // =====================================================================
                    // LOGIKA KLAIM PESANAN WALK-IN OTOMATIS
                    // =====================================================================
                    $new_user_id = mysqli_insert_id($koneksi); // Ambil ID akun yang baru saja dibuat
                    
                    if (!empty($no_hp)) {
                        $no_hp_aman = mysqli_real_escape_string($koneksi, $no_hp);
                        $query_klaim = "UPDATE pesanan SET user_id = '$new_user_id' WHERE user_id = '$no_hp_aman'";
                        mysqli_query($koneksi, $query_klaim);
                    }
                    // =====================================================================

                    $_SESSION['flash_success'] = 'Registrasi berhasil! Silakan login menggunakan No. HP Anda.';
                    mysqli_stmt_close($stmt2);
                    header('Location: ' . BASE_URL . '/auth/login.php');
                    exit();
                } else {
                    $error = 'Terjadi kesalahan sistem. Coba lagi nanti.';
                    mysqli_stmt_close($stmt2);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/register.css" rel="stylesheet">
</head>
<body>

    <div class="login-left">
        <a href="<?= BASE_URL ?>/public/index.php" class="btn-back-desktop">
            <i class="bi bi-arrow-left"></i> KEMBALI
        </a>

        <div class="login-left-bg"></div>
        <div class="login-left-overlay"></div>
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-circle deco-3"></div>
        <div class="login-left-content">
            <div class="login-left-logo">JASHIT.</div>
            <div class="login-left-tagline">
                Kami siap melayani Anda dengan cepat, tepat dan profesional<br>
                Buka setiap hari<br>
                Fleksibel untuk berbagai kebutuhan, termasuk kolaborasi dan kerja sama<br>
                Percayakan kebutuhan jahit Anda pada Jashit!
            </div>
        </div>
    </div>

    <div class="login-right">
        
        <a href="<?= BASE_URL ?>/auth/index.php" class="btn-back-mobile">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="login-form-wrap">

            <div class="login-avatar">
                <i class="bi bi-person-plus"></i>
            </div>

            <div class="login-title">Buat Akun</div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="input-group-wrapper">
                    <label class="form-label-custom">Nama Lengkap <span class="req-star">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person icon-left"></i>
                        <input type="text" name="nama" class="input-line" placeholder="Contoh: Jasmine" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="input-group-wrapper">
                    <label class="form-label-custom">No. Telp / WhatsApp <span class="req-star">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-telephone icon-left"></i>
                        <input type="text" name="no_hp" class="input-line" placeholder="Contoh: 08123456789" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="input-group-wrapper">
                    <label class="form-label-custom">Email <span style="font-weight:400; color:#94a3b8; font-size:11px; margin-left:4px;">(Opsional)</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope icon-left"></i>
                        <input type="email" name="email" class="input-line" placeholder="contoh@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="input-group-wrapper">
                    <label class="form-label-custom">Password <span class="req-star">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock icon-left"></i>
                        <input type="password" name="password" id="password" class="input-line" placeholder="Minimal 6 karakter" required>
                        <i class="bi bi-eye-slash toggle-password" data-target="#password"></i>
                    </div>
                </div>

                <div class="input-group-wrapper">
                    <label class="form-label-custom">Konfirmasi Password <span class="req-star">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-shield-lock icon-left"></i>
                        <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="input-line" placeholder="Ketik ulang password" required>
                        <i class="bi bi-eye-slash toggle-password" data-target="#konfirmasi_password"></i>
                    </div>
                </div>

                <div class="text-center mt-2">
                    <button type="submit" class="btn-login">BUAT AKUN SEKARANG</button>
                </div>

            </form>

            <div class="text-center mt-4" style="font-size:13px; color:#64748b;">
                Sudah punya akun?
                <a href="<?= BASE_URL ?>/auth/login.php" style="color:#1e293b; font-weight:700; text-decoration:none;">
                    Login di sini
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?= BASE_URL ?>/assets/js/register.js"></script>
</body>
</html>