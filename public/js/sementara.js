// Perilaku umum tampilan SEMENTARA (tanpa library).
document.addEventListener('DOMContentLoaded', function () {
    // Menu sidebar di HP/tablet.
    var tombolMenu = document.querySelector('.tombol-menu');
    if (tombolMenu) {
        tombolMenu.addEventListener('click', function () {
            var sidebar = document.querySelector('.sidebar');
            var terbuka = sidebar.classList.toggle('terbuka');
            tombolMenu.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
        });
    }

    // Status memuat: cegah klik ganda saat formulir dikirim (SOP UI: loading state).
    document.querySelectorAll('form[data-memuat]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var tombol = form.querySelector('[type=submit]');
            if (tombol) {
                tombol.disabled = true;
                tombol.textContent = form.getAttribute('data-memuat');
            }
        });
    });

    // Tombol pembuka dialog: <button data-buka-dialog="id-dialog">.
    document.querySelectorAll('[data-buka-dialog]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var dialog = document.getElementById(tombol.getAttribute('data-buka-dialog'));
            var aksi = tombol.getAttribute('data-aksi');
            if (aksi) { dialog.querySelector('form').setAttribute('action', aksi); }
            var nama = tombol.getAttribute('data-nama');
            var sasaran = dialog.querySelector('[data-isi-nama]');
            if (nama && sasaran) { sasaran.textContent = nama; }
            dialog.showModal();
        });
    });
    document.querySelectorAll('[data-tutup-dialog]').forEach(function (tombol) {
        tombol.addEventListener('click', function () { tombol.closest('dialog').close(); });
    });
});
