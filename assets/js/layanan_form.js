function sesuaikanForm() {
    const kategoriSelect = document.getElementById('kategori');
    if (!kategoriSelect) return;
    
    const kategori = kategoriSelect.value;
    const labelNama = document.getElementById('label_nama');
    const inputNama = document.getElementById('input_nama');
    const labelHarga = document.getElementById('label_harga');
    const kolomHarga = document.getElementById('kolom_harga');
    const kolomEstimasi = document.getElementById('kolom_estimasi');
    const inputEstimasi = document.getElementById('estimasi_hari');

    if (kategori === 'Bahan/Material') {
        labelNama.innerHTML = 'Nama Bahan <span class="text-danger">*</span>';
        if(inputNama.value === '') inputNama.placeholder = 'Contoh: Kain Katun Jepang';
        
        labelHarga.innerHTML = 'Harga Per Meter (Rp)';
        kolomEstimasi.classList.add('d-none');
        kolomHarga.classList.remove('col-md-6');
        kolomHarga.classList.add('col-md-12');
        
        if (inputEstimasi && (inputEstimasi.value === '' || inputEstimasi.value > 0)) {
            inputEstimasi.value = '0';
        }
        
    } else if (kategori === 'Aksesoris') {
        labelNama.innerHTML = 'Nama Aksesoris <span class="text-danger">*</span>';
        if(inputNama.value === '') inputNama.placeholder = 'Contoh: Tote Bag, Topi Custom';
        
        labelHarga.innerHTML = 'Harga Satuan (Rp)';
        kolomEstimasi.classList.add('d-none');
        kolomHarga.classList.remove('col-md-6');
        kolomHarga.classList.add('col-md-12');
        
        if (inputEstimasi && (inputEstimasi.value === '' || inputEstimasi.value > 0)) {
            inputEstimasi.value = '0';
        }
        
    } else {
        labelNama.innerHTML = 'Nama Layanan <span class="text-danger">*</span>';
        if(inputNama.value === '') inputNama.placeholder = 'Contoh: Jahit Kemeja Batik Pria';
        
        labelHarga.innerHTML = 'Harga Mulai (Rp)';
        kolomEstimasi.classList.remove('d-none');
        kolomHarga.classList.remove('col-md-12');
        kolomHarga.classList.add('col-md-6');
        
        if (inputEstimasi && inputEstimasi.value === '0') {
            inputEstimasi.value = '';
        }
    }
}

// Event Listener agar berjalan otomatis tanpa atribut onchange di HTML
document.addEventListener("DOMContentLoaded", function() {
    const kategoriSelect = document.getElementById('kategori');
    if (kategoriSelect) {
        sesuaikanForm(); // Jalankan saat pertama kali diload
        kategoriSelect.addEventListener('change', sesuaikanForm); // Jalankan tiap kali dropdown diubah
    }
});