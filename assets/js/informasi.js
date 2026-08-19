$(document).ready(function() {

    // Live Search Kartu Diskon (pengganti search bawaan DataTables)
    $('#cariDiskon').on('keyup', function() {
        const keyword = $(this).val().toLowerCase().trim();
        let adaHasil = false;

        $('.diskon-coupon-card').each(function() {
            const teks = $(this).text().toLowerCase();
            const cocok = teks.includes(keyword);
            $(this).toggle(cocok);
            if (cocok) adaHasil = true;
        });

        $('#diskonNoResult').toggle(!adaHasil && keyword !== '');
    });

    // Konfirmasi Hapus Data dengan SweetAlert2
    $(document).on('click', '.btn-hapus', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        
        Swal.fire({
            title: 'Hapus Diskon?',
            text: "Data diskon yang dihapus tidak bisa dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

});