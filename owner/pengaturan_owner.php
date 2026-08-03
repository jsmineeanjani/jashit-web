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
    <style>
        .setting-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 24px;
        }
        .setting-card h6 {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy-dark);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .form-label { font-size: 13px; font-weight: 600; color: #475569; }
        .form-control {
            font-size: 14px;
            border-color: #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .form-control:focus {
            border-color: var(--navy-dark);
            box-shadow: 0 0 0 3px rgba(15,23,42,0.08);
        }
        .btn-save {
            background: var(--navy-dark);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 28px;
            border: none;
            border-radius: 8px;
            transition: 0.2s;
        }
        .btn-save:hover { background: #0f172a; color: #fff; }

        .pw-toggle {
            cursor: pointer;
            border-left: none;
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
        }
        .pw-toggle:hover { color: var(--navy-dark); }
        .input-group .form-control { border-right: none; }
        .input-group .form-control:focus { border-right: none; box-shadow: none; }
        .input-group:focus-within .form-control,
        .input-group:focus-within .pw-toggle {
            border-color: var(--navy-dark);
        }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_owner.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_owner.php'; ?>

        <div class="dashboard-content" style="padding: 28px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 800; color: var(--navy-dark); margin: 0;">
                    Pengaturan Akun
                </h1>
                <p class="text-muted small mt-1 mb-0">Kelola informasi profil dan keamanan akun Anda.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" style="border-radius: 10px;">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $success ?></span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" style="border-radius: 10px;">
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
                                <input type="text" class="form-control" value="Owner" disabled
                                       style="background:#f8fafc; color:#94a3b8;">
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
<script>
// Toggle show/hide password
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Cek kecocokan password konfirmasi
document.getElementById('pw_konfirmasi').addEventListener('input', function () {
    const baru     = document.getElementById('pw_baru').value;
    const konfirm  = this.value;
    const msg      = document.getElementById('pw_match_msg');
    msg.style.display = konfirm.length > 0 ? 'block' : 'none';
    if (konfirm === baru) {
        msg.textContent = '✓ Password cocok';
        msg.style.color = '#16a34a';
    } else {
        msg.textContent = '✗ Password tidak cocok';
        msg.style.color = '#dc2626';
    }
});

// Indikator kekuatan password
document.getElementById('pw_baru').addEventListener('input', function () {
    const val  = this.value;
    const wrap = document.getElementById('pw_strength_wrap');
    const bar  = document.getElementById('pw_strength_bar');
    const lbl  = document.getElementById('pw_strength_label');

    wrap.style.display = val.length > 0 ? 'block' : 'none';

    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '20%', color: '#ef4444', label: 'Sangat Lemah' },
        { pct: '40%', color: '#f97316', label: 'Lemah' },
        { pct: '60%', color: '#eab308', label: 'Cukup' },
        { pct: '80%', color: '#22c55e', label: 'Kuat' },
        { pct: '100%', color: '#16a34a', label: 'Sangat Kuat' },
    ];
    const lvl = levels[Math.min(score - 1, 4)] || levels[0];
    bar.style.width           = lvl.pct;
    bar.style.backgroundColor = lvl.color;
    lbl.textContent           = lvl.label;
    lbl.style.color           = lvl.color;
});

// Validasi sebelum submit
document.getElementById('formPassword').addEventListener('submit', function (e) {
    const baru    = document.getElementById('pw_baru').value;
    const konfirm = document.getElementById('pw_konfirmasi').value;
    if (baru !== konfirm) {
        e.preventDefault();
        alert('Konfirmasi password tidak cocok!');
    }
});
</script>
</body>
</html>