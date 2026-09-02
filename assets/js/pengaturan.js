document.addEventListener('DOMContentLoaded', function () {
    const formPassword = document.getElementById('formUpdatePassword');
    const formProfil = document.getElementById('formUpdateProfil');

    // Intercept form submission untuk Password
    if (formPassword) {
        formPassword.addEventListener('submit', function (e) {
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
                    formPassword.submit();
                }
            });
        });
    }

    // Intercept juga untuk Profil biar sekalian cantik
    if (formProfil) {
        formProfil.addEventListener('submit', function (e) {
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
                    formProfil.submit();
                }
            });
        });
    }

    // =====================================================================
    // pengaturan_owner.php
    // =====================================================================
    const formPasswordOwner = document.getElementById('formPassword');

    if (formPasswordOwner) {
        const pwBaru = document.getElementById('pw_baru');
        const pwKonfirmasi = document.getElementById('pw_konfirmasi');

        // Toggle show/hide password - dipanggil lewat onclick="togglePw(...)" di HTML
        window.togglePw = function (id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        };

        // Cek kecocokan password konfirmasi
        pwKonfirmasi.addEventListener('input', function () {
            const baru = pwBaru.value;
            const konfirm = this.value;
            const msg = document.getElementById('pw_match_msg');
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
        pwBaru.addEventListener('input', function () {
            const val = this.value;
            const wrap = document.getElementById('pw_strength_wrap');
            const bar = document.getElementById('pw_strength_bar');
            const lbl = document.getElementById('pw_strength_label');

            wrap.style.display = val.length > 0 ? 'block' : 'none';

            let score = 0;
            if (val.length >= 6) score++;
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
            bar.style.width = lvl.pct;
            bar.style.backgroundColor = lvl.color;
            lbl.textContent = lvl.label;
            lbl.style.color = lvl.color;
        });

        // Validasi sebelum submit
        formPasswordOwner.addEventListener('submit', function (e) {
            const baru = pwBaru.value;
            const konfirm = pwKonfirmasi.value;
            if (baru !== konfirm) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
            }
        });
    }
});