<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="dashboard-sidebar" style="width: 260px; background-color: #1e293b; color: #fff; height: 100vh; position: sticky; top: 0; display: flex; flex-direction: column;">
    
    <div class="sidebar-brand" style="text-align: center; padding: 25px 20px 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="JASHIT Logo" style="max-width: 120px; height: auto; margin-bottom: 8px; filter: invert(1) brightness(1.5);">
    </div>
    
    <div class="sidebar-menu flex-grow-1 px-3 mt-3">
        <span style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 1.5px; margin-bottom: 12px; display: block; padding-left: 12px;">
            Owner Menu
        </span>
        
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link text-white <?= $current_page == 'dashboard_laporan.php' ? 'active' : '' ?>" 
                   style="padding: 12px 16px; font-weight: 600; display: flex; align-items: center; gap: 12px; border-radius: 8px; transition: all 0.3s; <?= $current_page == 'dashboard.php' ? 'background-color: rgba(255,255,255,0.1);' : 'opacity: 0.7;' ?>">
                    <i class="bi bi-grid-1x2-fill" style="font-size: 18px; <?= $current_page == 'dashboard_laporan.php' ? 'color: #f4d3c2;' : '' ?>"></i> 
                    Overview Bisnis
                </a>
            </li>
            
            <li class="nav-item">
                <a href="laporan.php" class="nav-link text-white <?= $current_page == 'laporan.php' ? 'active' : '' ?>" 
                   style="padding: 12px 16px; font-weight: 600; display: flex; align-items: center; gap: 12px; border-radius: 8px; transition: all 0.3s; <?= $current_page == 'laporan.php' ? 'background-color: rgba(255,255,255,0.1);' : 'opacity: 0.7;' ?>">
                    <i class="bi bi-file-earmark-bar-graph-fill" style="font-size: 18px; <?= $current_page == 'laporan.php' ? 'color: #f4d3c2;' : '' ?>"></i> 
                    Laporan Penjualan
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer p-3" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="d-flex align-items-center gap-3 px-2 mb-3">
            <div class="avatar-circle" style="width: 40px; height: 40px; border-radius: 50%; background: #f4d3c2; color: #1e293b; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">
                O
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 700; color: #fff;">Owner</div>
                <div style="font-size: 11px; color: #94a3b8;">Panel Owner</div>
            </div>
        </div>
        
        <a href="#" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalLogout"
           style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; padding: 10px; font-size: 13px; border-radius: 8px;">
            <i class="bi bi-box-arrow-right"></i> KELUAR
        </a>
    </div>
</div>

<style>
    .nav-link:not(.active):hover {
        opacity: 1 !important;
        background-color: rgba(255,255,255,0.05);
    }
</style>

<div class="modal fade" id="modalLogout" tabindex="-1" aria-labelledby="modalLogoutLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content text-dark" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
      <div class="modal-body text-center p-4">
        <div style="width: 70px; height: 70px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        <h4 class="fw-bold mb-2 text-dark">Apakah Anda ingin keluar?</h4>
        <p class="text-muted mb-4" style="font-size: 14px;">
            Sesi Anda akan diakhiri dan Anda harus login kembali untuk masuk ke dalam sistem.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <button type="button" class="btn w-50 py-2 fw-bold" data-bs-dismiss="modal" style="background-color: #f1f5f9; color: #475569; border: none; border-radius: 8px;">
                Batal
            </button>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn w-50 py-2 fw-bold" style="background-color: #ef4444; color: white; border: none; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);">
                Ya, Keluar
            </a>
        </div>
      </div>
    </div>
  </div>
</div>