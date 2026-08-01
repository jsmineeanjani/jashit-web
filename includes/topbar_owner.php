<header class="admin-topbar">
    <div class="topbar-left">
        <div class="topbar-brand">
            <div class="brand-icon">
                <i class="bi bi-scissors"></i>
            </div>
            <div class="brand-text">
                <strong>Jashit</strong>
                <span>Jasa Jahit</span>
            </div>
        </div>
    </div>
    
    <div class="topbar-right">
        <div class="dropdown">
            <a href="#" class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <?= htmlspecialchars($_SESSION['user_nama'] ?? 'Owner') ?>
                <i class="bi bi-chevron-down ms-1" style="font-size: 11px; color: #94a3b8;"></i>
            </a>
            
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" style="border-radius: 8px; min-width: 200px;">
                <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL ?>/owner/pengaturan_owner.php" style="font-size: 13.5px; font-weight: 500; color: #475569;">
                        <i class="bi bi-gear me-2 text-secondary"></i> Pengaturan Akun
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a href="#" class="dropdown-item py-2 text-danger" data-bs-toggle="modal" data-bs-target="#modalLogoutOwner" style="font-size: 13.5px; font-weight: 600;">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<style>
    .admin-topbar {
        height: 70px;
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 32px;
        position: sticky;
        top: 0;
        z-index: 99;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-icon {
        width: 38px; height: 38px;
        background-color: #ffedd5;
        color: #ea580c;
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .brand-text { display: flex; flex-direction: column; line-height: 1.2; }
    .brand-text strong { font-size: 16px; color: #1e293b; font-weight: 700; letter-spacing: 0.5px; }
    .brand-text span { font-size: 11.5px; color: #64748b; }

    .user-avatar {
        width: 32px; height: 32px;
        background: #f1f5f9;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; color: #64748b;
    }
    .user-dropdown {
        font-size: 14px; font-weight: 600; color: #475569; text-decoration: none;
        display: flex; align-items: center; gap: 8px; transition: color 0.3s;
    }
    .user-dropdown:hover { color: #1e293b; }
    .user-dropdown::after { display: none; }
    .dropdown-menu { box-shadow: 0 10px 30px rgba(30,41,59,0.08) !important; }
    .dropdown-item:hover { background-color: #f8fafc; }
</style>

<!-- MODAL LOGOUT OWNER -->
<div class="modal fade" id="modalLogoutOwner" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
      <div class="modal-body text-center p-4">
        <div style="width: 70px; height: 70px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        <h4 class="fw-bold mb-2 text-dark">Apakah Anda ingin keluar?</h4>
        <p class="text-muted mb-4" style="font-size: 14px;">
            Sesi Anda akan diakhiri dan Anda harus login kembali untuk masuk ke dalam sistem.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <button type="button" class="btn w-50 py-2 fw-bold" data-bs-dismiss="modal"
                style="background-color: #f1f5f9; color: #475569; border: none; border-radius: 8px;">
                Batal
            </button>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn w-50 py-2 fw-bold"
                style="background-color: #ef4444; color: white; border: none; border-radius: 8px;">
                Ya, Keluar
            </a>
        </div>
      </div>
    </div>
  </div>
</div>