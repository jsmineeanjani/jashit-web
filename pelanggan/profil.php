<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];
$error   = '';

// ==========================================
// ALUR ACTIVITY DIAGRAM: STATUS MEMBER & POIN
// ==========================================
// 1. Ambil data pelanggan & jumlah poin
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// 2. Kategorikan status member berdasarkan jumlah poin
$poin_saat_ini = (int)($user['poin'] ?? 0);
$status_sekarang = $user['status_member'] ?? 'Classic';
$status_baru = 'Classic'; // Default

if ($poin_saat_ini >= 500) {
    $status_baru = 'Gold';
} elseif ($poin_saat_ini >= 100) {
    $status_baru = 'Silver';
}

// 3. Update status member pelanggan (Jika ada perubahan/naik level)
if ($status_baru !== $status_sekarang) {
    $stmt_update_status = mysqli_prepare($koneksi, "UPDATE users SET status_member = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update_status, 'si', $status_baru, $user_id);
    mysqli_stmt_execute($stmt_update_status);
    mysqli_stmt_close($stmt_update_status);
    
    // Perbarui variabel lokal untuk ditampilkan di layar
    $user['status_member'] = $status_baru;
}
// ==========================================

// PROSES UPDATE PROFIL (FORM SUBMIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $no_hp   = trim($_POST['no_hp'] ?? '');
    $alamat  = trim($_POST['alamat'] ?? '');
    $pw_lama = $_POST['password_lama'] ?? '';
    $pw_baru = $_POST['password_baru'] ?? '';
    
    $foto_final = $user['foto'];

    if (empty($nama)) {
        $error = 'Nama tidak boleh kosong.';
    } elseif (empty($email)) {
        $error = 'Email tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($stmt_check, 'si', $email, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = 'Email sudah digunakan oleh akun lain.';
        }
        mysqli_stmt_close($stmt_check);

        // Upload Foto
        if (empty($error) && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaded_file = $_FILES['foto'];
            $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = 'Format foto harus JPG, PNG, atau WEBP.';
            } elseif ($uploaded_file['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 2MB.';
            } else {
                $new_file_name = 'profil_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_dir = UPLOAD_PATH . 'profil/'; 
                
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (move_uploaded_file($uploaded_file['tmp_name'], $upload_dir . $new_file_name)) {
                    if (!empty($user['foto']) && file_exists($upload_dir . $user['foto'])) {
                        unlink($upload_dir . $user['foto']); 
                    }
                    $foto_final = $new_file_name;
                } else {
                    $error = 'Gagal menyimpan foto.';
                }
            }
        }

        // Simpan Data Profil
        if (empty($error)) {
            $stmt2 = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, no_hp=?, alamat=?, foto=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, 'sssssi', $nama, $email, $no_hp, $alamat, $foto_final, $user_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            if (!empty($pw_lama) && !empty($pw_baru)) {
                if (!password_verify($pw_lama, $user['password'])) {
                    $error = 'Password lama salah. Data lain berhasil disimpan.';
                } elseif (strlen($pw_baru) < 6) {
                    $error = 'Password baru minimal 6 karakter.';
                } else {
                    $hashed = password_hash($pw_baru, PASSWORD_DEFAULT);
                    $stmt3  = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt3, 'si', $hashed, $user_id);
                    mysqli_stmt_execute($stmt3);
                    mysqli_stmt_close($stmt3);
                }
            }

            if (empty($error)) {
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_email'] = $email;
                $_SESSION['flash_success'] = 'Profil berhasil diperbarui!';
                header('Location: ' . BASE_URL . '/pelanggan/profil.php');
                exit();
            }
        }
    }
    // Refresh display
    $user['nama'] = $nama;
    $user['email'] = $email;
    $user['no_hp'] = $no_hp;
    $user['alamat'] = $alamat;
    $user['foto'] = $foto_final;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .avatar-container { cursor: pointer; position: relative; display: inline-block; margin-bottom: 15px; transition: transform 0.2s; }
        .avatar-container:hover { transform: scale(1.02); }
        .camera-icon { position: absolute; bottom: 5px; right: 5px; background: #10b981; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; box-shadow: 0 3px 6px rgba(0,0,0,0.15); transition: 0.2s; }
        .avatar-container:hover .camera-icon { background: #059669; }
        .img-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .initial-avatar { width: 120px; height: 120px; border-radius: 50%; background: #1e293b; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 700; color: #f4d3c2; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .badge-classic { background-color: #64748b; color: white; }
        .badge-silver { background-color: #94a3b8; color: white; }
        .badge-gold { background-color: #fbbf24; color: #78350f; }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>
        
        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 700; color: var(--navy-dark); margin: 0;">Profil & Status Member</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Kelola data diri, kumpulkan poin, dan tingkatkan status member Anda</p>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="font-size: 14px;">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="font-size: 14px;">
                    <i class="bi bi-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4 mt-1">
                    
                    <div class="col-lg-4">
                        <div class="section-card shadow-sm" style="text-align:center; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px 20px;">
                            
                            <div class="avatar-container" onclick="document.getElementById('inputFoto').click();">
                                <?php if(!empty($user['foto']) && file_exists(UPLOAD_PATH . 'profil/' . $user['foto'])): ?>
                                    <img src="<?= UPLOAD_URL . 'profil/' . $user['foto'] ?>" id="previewFoto" class="img-avatar" alt="Foto Profil">
                                    <div id="previewInitials" class="initial-avatar" style="display:none;"><?= strtoupper(mb_substr($user['nama'], 0, 1)) ?></div>
                                <?php else: ?>
                                    <div id="previewInitials" class="initial-avatar"><?= strtoupper(mb_substr($user['nama'], 0, 1)) ?></div>
                                    <img id="previewFoto" class="img-avatar" style="display:none;" alt="Foto Profil">
                                <?php endif; ?>
                                <div class="camera-icon"><i class="bi bi-camera-fill"></i></div>
                            </div>
                            <input type="file" name="foto" id="inputFoto" class="d-none" accept="image/jpeg, image/png, image/webp" onchange="previewImage(this)">
                            
                            <div style="font-size:18px;font-weight:700; color:var(--navy-dark);margin-bottom:4px;">
                                <?= htmlspecialchars($user['nama']) ?>
                            </div>
                            <div style="font-size:13px;color:var(--text-muted); margin-bottom:12px;">
                                <?= htmlspecialchars($user['email']) ?>
                            </div>
                            
                            <?php 
                                $status_badge = 'badge-classic';
                                if($user['status_member'] == 'Silver') $status_badge = 'badge-silver';
                                if($user['status_member'] == 'Gold') $status_badge = 'badge-gold shadow-sm';
                            ?>
                            <div class="mb-4">
                                <span class="badge <?= $status_badge ?> px-3 py-2 text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                                    <i class="bi bi-award-fill me-1"></i> MEMBER <?= htmlspecialchars($user['status_member'] ?? 'CLASSIC') ?>
                                </span>
                                <div class="mt-2" style="font-size: 15px; font-weight: 700; color: #10b981;">
                                    <i class="bi bi-coin text-warning me-1"></i> <?= number_format($user['poin'] ?? 0, 0, ',', '.') ?> Poin Reward
                                </div>
                            </div>

                            <div style="margin-top:8px;padding-top:20px; border-top:1px dashed #e2e8f0; font-size:13px;color:var(--text-muted); text-align:left;line-height:2.2;">
                                <div>
                                    <i class="bi bi-telephone text-primary me-2"></i>
                                    <?= htmlspecialchars($user['no_hp'] ?: '-') ?>
                                </div>
                                <div>
                                    <i class="bi bi-calendar3 text-primary me-2"></i>
                                    Bergabung <?= isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-' ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="section-card shadow-sm" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div class="section-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                                <h5 style="margin: 0; font-weight: 700; color: var(--navy-dark); font-size: 16px;">Detail Akun</h5>
                            </div>
                            <div class="section-card-body" style="padding: 24px;">

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">No. HP / WhatsApp</label>
                                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Alamat Lengkap</label>
                                    <textarea name="alamat" class="form-control" rows="3" style="resize:none;"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                                </div>

                                <div style="border-top:1px dashed #cbd5e1; padding-top:20px;margin-bottom:20px;">
                                    <div style="font-size:13px;font-weight:700; color:var(--navy-dark);margin-bottom:16px;">
                                        <i class="bi bi-shield-lock me-1"></i> Ganti Password <span style="font-weight:400;color:var(--text-muted); font-size: 12px;">(opsional)</span>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <input type="password" name="password_lama" class="form-control" placeholder="Password Lama">
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <input type="password" name="password_baru" class="form-control" placeholder="Password Baru (Min 6 Karakter)">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="background-color: var(--navy-dark); border:none;">
                                    SIMPAN PERUBAHAN
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewInitials').style.display = 'none';
                var img = document.getElementById('previewFoto');
                img.src = e.target.result;
                img.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>