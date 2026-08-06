document.addEventListener("DOMContentLoaded", function() {
    const targetSelect = document.querySelector('select[name="target_pelanggan"]');
    const kodePromoInput = document.querySelector('input[name="kode_promo"]');

    // Fungsi untuk mengunci/membuka kolom kode promo
    function toggleKodePromo() {
        if (targetSelect.value === 'pengguna_baru') {
            kodePromoInput.value = ''; // Kosongkan isinya
            kodePromoInput.setAttribute('disabled', 'true');
            kodePromoInput.setAttribute('placeholder', 'Tidak perlu kode untuk pengguna baru');
        } else {
            kodePromoInput.removeAttribute('disabled');
            kodePromoInput.setAttribute('placeholder', 'Cth: LEBARAN10');
        }
    }

    // Jalankan saat halaman pertama kali dimuat
    if (targetSelect && kodePromoInput) {
        toggleKodePromo();

        // Jalankan setiap kali pilihan dropdown berubah
        targetSelect.addEventListener('change', toggleKodePromo);
    }
});