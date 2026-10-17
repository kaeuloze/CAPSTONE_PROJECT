// Vaksinasi Balita: centang dosis → kotak tanggal aktif (bawaan tanggal kegiatan, bisa diubah sesuai Buku KIA).
(function () {
    var form = document.getElementById('form-vaksinasi');
    if (!form) { return; }
    var tanggalKegiatan = form.getAttribute('data-tanggal-kegiatan');

    form.querySelectorAll('[data-baris-vaksin]').forEach(function (baris) {
        var centang = baris.querySelector('input[type=checkbox]');
        var tanggal = baris.querySelector('input[type=date]');
        var label = baris.querySelector('[data-label-status]');
        var tersimpan = baris.getAttribute('data-sudah-tersimpan') === '1';

        centang.addEventListener('change', function () {
            tanggal.disabled = !centang.checked;
            if (centang.checked && !tanggal.value) { tanggal.value = tanggalKegiatan; }
            label.textContent = centang.checked ? (tersimpan ? 'Sudah' : 'Sudah (baru)') : (tersimpan ? 'Belum (dibatalkan)' : 'Belum');
        });
    });
})();
