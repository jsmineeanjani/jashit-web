<?php
// ============================================
// File Auth — Proteksi Halaman
// ============================================

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 * Jika belum, redirect ke halaman login
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Cek apakah user memiliki role tertentu
 * Jika tidak sesuai, redirect ke halaman yang sesuai rolenya
 */
function requireRole($role_dibutuhkan) {
    requireLogin();

    // Bisa menerima string tunggal atau array role
    if (is_string($role_dibutuhkan)) {
        $role_dibutuhkan = [$role_dibutuhkan];
    }

    if (!in_array($_SESSION['user_role'], $role_dibutuhkan)) {
        // Redirect ke dashboard sesuai role user
        redirectByRole();
    }
}

/**
 * Redirect user ke dashboard sesuai rolenya
 */
function redirectByRole() {
    $role = $_SESSION['user_role'] ?? '';
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        case 'owner':
            header('Location: ' . BASE_URL . '/owner/dashboard.php');
            break;
        case 'pelanggan':
            header('Location: ' . BASE_URL . '/pelanggan/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/login.php');
            break;
    }
    exit();
}

/**
 * Cek apakah user sudah login (return true/false)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Ambil data user yang sedang login
 */
function getUser($key = null) {
    if ($key) {
        return $_SESSION[$key] ?? null;
    }
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'nama' => $_SESSION['user_nama'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'email'=> $_SESSION['user_email'] ?? null,
    ];
}

/**
 * Tampilkan flash message lalu hapus dari session
 */
function showFlash() {
    $html = '';
    if (isset($_SESSION['flash_success'])) {
        $html .= '<div class="alert-flash alert-flash-success">'
               . htmlspecialchars($_SESSION['flash_success'])
               . '</div>';
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_error'])) {
        $html .= '<div class="alert-flash alert-flash-error">'
               . htmlspecialchars($_SESSION['flash_error'])
               . '</div>';
        unset($_SESSION['flash_error']);
    }
    return $html;
}