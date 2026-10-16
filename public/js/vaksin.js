// Master Vaksin: popup Tambah / Edit memakai satu dialog (Figma 208:894).
(function () {
    var dialog = document.getElementById('dialog-vaksin');
    if (!dialog) { return; }
    var form = dialog.querySelector('form');

    function isi(nama, nilai) { form.querySelector('[name="' + nama + '"]').value = nilai || ''; }
    function bersihkanGalat() {
        form.querySelectorAll('.galat-isian').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.salah').forEach(function (el) { el.classList.remove('salah'); });
    }

    document.querySelector('[data-vaksin-tambah]').addEventListener('click', function () {
        bersihkanGalat();
        form.action = form.getAttribute('data-aksi-tambah');
        isi('_method', 'POST'); isi('_mode', 'tambah'); isi('_id', ''); isi('versi', '');
        isi('nama_vaksin', ''); isi('deskripsi', '');
        dialog.showModal();
        form.querySelector('[name=nama_vaksin]').focus();
    });

    document.querySelectorAll('[data-vaksin-edit]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            bersihkanGalat();
            var aksi = tombol.getAttribute('data-aksi');
            form.action = aksi;
            isi('_method', 'PUT'); isi('_mode', 'edit'); isi('_id', tombol.getAttribute('data-id')); isi('versi', tombol.getAttribute('data-versi'));
            isi('nama_vaksin', tombol.getAttribute('data-nama')); isi('deskripsi', tombol.getAttribute('data-deskripsi'));
            dialog.showModal();
        });
    });

    if (dialog.hasAttribute('data-buka-otomatis')) { dialog.showModal(); }
})();
