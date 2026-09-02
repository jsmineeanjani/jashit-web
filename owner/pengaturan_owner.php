<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('owner'); 

$user_id  = $_SESSION['user_id'];
$success  = '';
$error    = '';

// Ambil data owner saat ini
$q_user  = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($q_user, 'i', $user_id);
mysqli_stmt_execute($q_user);
$owner = mysqli_fetch_assoc(mysqli_stmt_get_result($q_user));

// =========================================================================
// PROSES SIMPAN PERUBAHAN
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- GANTI NAMA & EMAIL ---
    if (isset($_POST['simpan_profil'])) {
        $nama_baru  = trim($_POST['nama']);
        $email_baru = trim($_POST['email']);
        
        if (empty($nama_baru) || empty($email_baru)) {
            $error = 'Nama dan Email tidak boleh kosong.';
        } else {
            // Cek apakah email sudah digunakan oleh user lain (mencegah duplikasi)
            $cek_email = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($cek_email, 'si', $email_baru, $user_id);
            mysqli_stmt_execute($cek_email);
            $hasil_cek = mysqli_stmt_get_result($cek_email);
            
            if (mysqli_num_rows($hasil_cek) > 0) {
                $error = 'Email tersebut sudah terdaftar pada akun lain. Gunakan email yang berbeda.';
            } else {
                // Lakukan proses update jika email aman
                $upd = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, email = ? WHERE id = ?");
                mysqli_stmt_bind_param($upd, 'ssi', $nama_baru, $email_baru, $user_id);
                
                if (mysqli_stmt_execute($upd)) {
                    $_SESSION['user_nama'] = $nama_baru; // Update session nama
                    $success = 'Profil berhasil diperbarui.';
                    
                    // Refresh data agar langsung berubah di layar
                    $q_user2 = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($q_user2, 'i', $user_id);
                    mysqli_stmt_execute($q_user2);
                    $owner = mysqli_fetch_assoc(mysqli_stmt_get_result($q_user2));
                } else {
                    $error = 'Gagal memperbarui profil.';
                }
            }
        }
    }

    // --- GANTI PASSWORD ---
    if (isset($_POST['ganti_password'])) {
        $pw_lama  = $_POST['password_lama']     ?? '';
        $pw_baru  = $_POST['password_baru']     ?? '';
        $pw_ulang = $_POST['password_konfirmasi'] ?? '';

        if (empty($pw_lama) || empty($pw_baru) || empty($pw_ulang)) {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($pw_baru !== $pw_ulang) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (!password_verify($pw_lama, $owner['password'])) {
            $error = 'Password lama tidak sesuai.';
        } else {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $upd  = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'si', $hash, $user_id);
            if (mysqli_stmt_execute($upd)) {
                $success = 'Password berhasil diubah. Silakan login ulang menggunakan password baru.';
            } else {
                $error = 'Gagal mengubah password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Akun — JASHIT Owner</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/pengaturan.css" rel="stylesheet">
</head>
<body class="pengaturan-owner-page">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_owner.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/layouts/topbar_owner.php'; ?>

        <div class="dashboard-content pengaturan-owner-content">
            <div class="mb-4">
                <h1 class="pengaturan-owner-title">
                    Pengaturan Akun
                </h1>
                <p class="text-muted small mt-1 mb-0">Kelola informasi profil dan keamanan akun Anda.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 rounded-3">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $success ?></span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 rounded-3">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= $error ?></span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-6">

                    <!-- KARTU INFO PROFIL -->
                    <div class="setting-card shadow-sm">
                        <h6><i class="bi bi-person-circle me-2 text-secondary"></i>Informasi Profil</h6>
                        <form method="POST">
                            <input type="hidden" name="simpan_profil" value="1">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control"
                                       value="<?= htmlspecialchars($owner['nama'] ?? '') ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Email</label>
                                <!-- Form input email diaktifkan dan ditambahkan name="email" -->
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($owner['email'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control input-role-disabled" value="Owner" disabled>
                            </div>
                            <button type="submit" class="btn btn-save mt-2">
                                <i class="bi bi-floppy me-1"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>

                </div>
                <div class="col-lg-6">

                    <!-- KARTU GANTI PASSWORD -->
                    <div class="setting-card shadow-sm">
                        <h6><i class="bi bi-shield-lock me-2 text-secondary"></i>Ganti Password</h6>
                        <form method="POST" id="formPassword">
                            <input type="hidden" name="ganti_password" value="1">
                            <div class="mb-3">
                                <label class="form-label">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" name="password_lama" id="pw_lama"
                                           class="form-control" placeholder="Masukkan password saat ini" required>
                                    <button type="button" class="btn pw-toggle" onclick="togglePw('pw_lama', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password_baru" id="pw_baru"
                                           class="form-control" placeholder="Min. 6 karakter" required minlength="6">
                                    <button type="button" class="btn pw-toggle" onclick="togglePw('pw_baru', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password_konfirmasi" id="pw_konfirmasi"
                                           class="form-control" placeholder="Ulangi password baru" required>
                                    <button type="button" class="btn pw-toggle" onclick="togglePw('pw_konfirmasi', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div id="pw_match_msg" class="form-text" style="display:none;"></div>
                            </div>

                            <!-- Indikator kekuatan password -->
                            <div class="mb-3" id="pw_strength_wrap" style="display:none;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="font-size:12px; color:#64748b;">Kekuatan Password</span>
                                    <span id="pw_strength_label" style="font-size:12px; font-weight:700;"></span>
                                </div>
                                <div class="progress" style="height: 5px; border-radius: 10px;">
                                    <div id="pw_strength_bar" class="progress-bar" style="width:0%; transition:.3s;"></div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-save w-100">
                                <i class="bi bi-key me-1"></i> Ubah Password
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Script -->
<script src="<?= BASE_URL ?>/assets/js/pengaturan.js"></script>
</body>
</html>