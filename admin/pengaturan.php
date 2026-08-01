<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$user_id = $_SESSION['user_id'];
$error = '';

// Ambil data admin saat ini
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query);

// --- PROSES UPDATE DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Proses Update Profil
    if (isset($_POST['update_profil'])) {
        $nama  = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $no_hp = trim($_POST['no_hp']);

        if (empty($nama) || empty($email)) {
            $error = 'Nama dan Email tidak boleh kosong!';
        } else {
            $update = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, no_hp=? WHERE id=?");
            mysqli_stmt_bind_param($update, 'sssi', $nama, $email, $no_hp, $user_id);
            
            if (mysqli_stmt_execute($update)) {
                // Update session nama agar langsung berubah di topbar/sidebar tanpa harus relogin
                $_SESSION['user_nama'] = $nama;
                $_SESSION['flash_success'] = 'Profil berhasil diperbarui!';
                header("Location: pengaturan.php");
                exit;
            } else {
                $error = 'Gagal memperbarui profil.';
            }
        }
    }

    // 2. Proses Ganti Password
    if (isset($_POST['update_password'])) {
        $pass_lama    = $_POST['pass_lama'];
        $pass_baru    = $_POST['pass_baru'];
        $pass_konfirm = $_POST['pass_konfirm'];

        if (empty($pass_lama) || empty($pass_baru) || empty($pass_konfirm)) {
            $error = 'Semua kolom password wajib diisi!';
        } elseif ($pass_baru !== $pass_konfirm) {
            $error = 'Password baru dan konfirmasi tidak cocok!';
        } else {
            // Verifikasi password lama
            if (password_verify($pass_lama, $user['password'])) {
                // Hash password baru
                $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
                $upd_pass = mysqli_query($koneksi, "UPDATE users SET password='$hash_baru' WHERE id=$user_id");
                
                if ($upd_pass) {
                    $_SESSION['flash_success'] = 'Password berhasil diubah! Silakan gunakan password baru pada login berikutnya.';
                    header("Location: pengaturan.php");
                    exit;
                } else {
                    $error = 'Gagal mengubah password.';
                }
            } else {
                $error = 'Password lama yang Anda masukkan salah!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Akun — Admin JASHIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .settings-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); height: 100%; }
        .settings-header { font-size: 16px; font-weight: 700; color: var(--navy-dark); border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0; color: var(--navy-dark);">Pengaturan Akun</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Kelola informasi profil dan keamanan akun Admin Anda.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>
            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="bi bi-person-lines-fill me-2 text-primary"></i> Informasi Profil
                        </div>
                        <form action="" method="POST" id="formUpdateProfil">
                            <input type="hidden" name="update_profil" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control fw-bold" value="<?= htmlspecialchars($user['nama']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Nomor WhatsApp / HP</label>
                                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="Contoh: 08123456789">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background-color: var(--navy-dark); border: none;">
                                Simpan Perubahan Profil
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="bi bi-shield-lock-fill me-2 text-danger"></i> Keamanan & Password
                        </div>
                        <form action="" method="POST" id="formUpdatePassword">
                            <input type="hidden" name="update_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Password Lama</label>
                                <input type="password" name="pass_lama" class="form-control" placeholder="Masukkan password saat ini" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Password Baru</label>
                                <input type="password" name="pass_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Konfirmasi Password Baru</label>
                                <input type="password" name="pass_konfirm" class="form-control" placeholder="Ketik ulang password baru" required>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    // Intercept form submission untuk Password
    document.getElementById('formUpdatePassword').addEventListener('submit', function(e) {
        e.preventDefault(); // Tahan dulu submit form-nya
        
        Swal.fire({
            title: 'Update Password?',
            text: "Yakin ingin mengganti password Anda?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545', // Warna merah Bootstrap (btn-danger)
            cancelButtonColor: '#6c757d', // Warna abu-abu Bootstrap (btn-secondary)
            confirmButtonText: 'Ya, Update!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika user klik 'Ya', lanjutkan submit form
                this.submit();
            }
        });
    });

    // Opsional: Intercept juga untuk Profil biar sekalian cantik
    document.getElementById('formUpdateProfil').addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Simpan Profil?',
            text: "Perubahan profil akan segera diterapkan.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e293b', // Warna navy-dark
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
</script>
</body>
</html>