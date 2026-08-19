document.addEventListener('DOMContentLoaded', function () {
    const bellBtn  = document.getElementById('btnNotifBell');
    const dropdown = document.getElementById('notifDropdownPanel');
    const badge    = document.getElementById('notifBadgeCount');
    const listWrap = document.getElementById('notifListWrap');

    if (!bellBtn || typeof NOTIF_BASE_URL === 'undefined') return;

    function formatWaktu(tanggalStr) {
        const tgl = new Date(tanggalStr.replace(' ', 'T'));
        if (isNaN(tgl.getTime())) return tanggalStr;
        return tgl.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
    }

    function renderList(list) {
        if (!list || list.length === 0) {
            listWrap.innerHTML = '<div class="notif-empty">Belum ada notifikasi.</div>';
            return;
        }

        listWrap.innerHTML = list.map(function (n) {
            const belumBaca = n.is_read == 0 ? 'notif-item-baru' : '';
            const targetUrl = n.url
                ? (NOTIF_BASE_URL + '/pelanggan/notifikasi_klik.php?id=' + n.id + '&url=' + encodeURIComponent(n.url))
                : (NOTIF_BASE_URL + '/pelanggan/notifikasi.php');

            return '' +
                '<a href="' + targetUrl + '" class="notif-item ' + belumBaca + '">' +
                    '<div class="notif-item-icon ' + n.icon_class + '"><i class="bi ' + n.icon_bi + '"></i></div>' +
                    '<div class="notif-item-body">' +
                        '<div class="notif-item-title">' + n.judul + '</div>' +
                        '<div class="notif-item-desc">' + n.pesan + '</div>' +
                        '<div class="notif-item-time">' + formatWaktu(n.created_at) + '</div>' +
                    '</div>' +
                '</a>';
        }).join('');
    }

    function muatNotifikasi() {
        fetch(NOTIF_BASE_URL + '/pelanggan/notifikasi_ajax.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }
                }
                renderList(data.list);
            })
            .catch(function () {
                listWrap.innerHTML = '<div class="notif-empty">Gagal memuat notifikasi.</div>';
            });
    }

    bellBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            muatNotifikasi();
        }
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Cek badge saat halaman dibuka, lalu polling tiap 20 detik
    muatNotifikasi();
    setInterval(muatNotifikasi, 20000);
});