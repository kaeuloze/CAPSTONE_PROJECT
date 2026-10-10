// Halaman Presensi: simpan tiap klik tanpa memuat ulang + pembaruan otomatis (polling) antar-meja.
(function () {
    var akar = document.getElementById('presensi');
    if (!akar) { return; }

    var berlangsung = akar.getAttribute('data-berlangsung') === '1';
    var urlStatus = akar.getAttribute('data-url-status');
    var jeda = parseInt(akar.getAttribute('data-jeda'), 10) || 5000;
    var notifikasi = document.getElementById('notifikasi');
    var indikator = document.getElementById('indikator-langsung');
    var pengatur = null;
    var sedangMengambil = false;

    function tampilkanPesan(teks, jenis) {
        var kotak = document.createElement('div');
        kotak.className = 'pesan ' + (jenis || 'galat');
        kotak.setAttribute('role', jenis === 'sukses' ? 'status' : 'alert');
        kotak.textContent = teks; // textContent: aman dari HTML berbahaya
        notifikasi.appendChild(kotak);
        setTimeout(function () { kotak.remove(); }, 4000);
    }

    // Mengubah tampilan satu baris sesuai status hadir.
    function aturBaris(nik, hadir) {
        var baris = akar.querySelector('tr[data-nik="' + nik + '"]');
        if (!baris) { return; }
        var badge = baris.querySelector('.status-hadir');
        badge.classList.remove('hadir', 'belum', 'tidak');
        badge.classList.add(hadir ? 'hadir' : (berlangsung ? 'belum' : 'tidak'));
        badge.textContent = hadir ? 'Hadir' : (berlangsung ? 'Belum' : 'Tidak Hadir');

        var form = baris.querySelector('.form-presensi');
        if (form && !form.dataset.mengirim) {
            form.querySelector('input[name=hadir]').value = hadir ? '0' : '1';
            var tombol = form.querySelector('button');
            tombol.textContent = hadir ? 'Batalkan' : 'Tandai Hadir';
            tombol.classList.toggle('abu', !hadir);
        }
    }

    function aturRingkasan(r) {
        document.getElementById('jumlah-total').textContent = r.total;
        document.getElementById('jumlah-hadir').textContent = r.hadir;
        document.getElementById('jumlah-tidak').textContent = r.tidak_hadir;
    }

    // Kegiatan diselesaikan oleh kader lain / otomatis 24 jam: kunci semua tombol.
    function kegiatanSelesai() {
        berlangsung = false;
        document.getElementById('pesan-selesai').hidden = false;
        document.getElementById('label-status').textContent = 'Selesai';
        akar.querySelectorAll('.form-presensi button').forEach(function (b) { b.disabled = true; });
        var tombolSelesai = document.getElementById('tombol-selesaikan');
        if (tombolSelesai) { tombolSelesai.disabled = true; }
        if (indikator) { indikator.classList.add('mati'); indikator.textContent = 'Kegiatan sudah selesai'; }
        clearInterval(pengatur);
    }

    function tanganiSesiHabis(respon) {
        if (respon.status === 401 || respon.status === 419) {
            tampilkanPesan('Sesi Anda telah berakhir. Halaman akan dimuat ulang.');
            setTimeout(function () { window.location.reload(); }, 1500);
            return true;
        }
        return false;
    }

    // ---- Polling: ambil status terbaru setiap beberapa detik ----
    // paksa = true: tetap ambil walau tab tidak terlihat (dipakai setelah kader menyimpan).
    function ambilStatus(paksa) {
        if (sedangMengambil || (document.hidden && paksa !== true)) { return; }
        sedangMengambil = true;
        fetch(urlStatus + window.location.search, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (respon) {
                if (tanganiSesiHabis(respon)) { return null; }
                if (!respon.ok) { throw new Error('status ' + respon.status); }
                return respon.json();
            })
            .then(function (data) {
                if (!data) { return; }
                if (indikator && berlangsung) { indikator.classList.remove('mati'); indikator.textContent = 'Diperbarui otomatis setiap ' + (jeda / 1000) + ' detik'; }
                aturRingkasan(data.ringkasan);
                Object.keys(data.kehadiran).forEach(function (nik) { aturBaris(nik, data.kehadiran[nik]); });
                if (!data.berlangsung && berlangsung) { kegiatanSelesai(); }
            })
            .catch(function () {
                if (indikator) { indikator.classList.add('mati'); indikator.textContent = 'Koneksi terputus, mencoba lagi...'; }
            })
            .finally(function () { sedangMengambil = false; });
    }

    // ---- Simpan tiap klik (tanpa memuat ulang halaman) ----
    akar.querySelectorAll('.form-presensi').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.dataset.mengirim) { return; }
            var tombol = form.querySelector('button');
            var teksAsli = tombol.textContent;
            form.dataset.mengirim = '1';
            tombol.disabled = true;
            tombol.textContent = 'Menyimpan...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (respon) {
                    if (tanganiSesiHabis(respon)) { return null; }
                    return respon.json().then(function (data) { return { ok: respon.ok, data: data }; });
                })
                .then(function (hasil) {
                    delete form.dataset.mengirim;
                    tombol.disabled = !berlangsung;
                    if (!hasil) { return; }
                    if (hasil.ok) {
                        tombol.textContent = teksAsli;
                        aturBaris(hasil.data.nik, hasil.data.hadir);
                    } else {
                        tombol.textContent = teksAsli;
                        tampilkanPesan(hasil.data.pesan || (hasil.data.message) || 'Presensi gagal disimpan.');
                    }
                    ambilStatus(true); // segarkan ringkasan
                })
                .catch(function () {
                    delete form.dataset.mengirim;
                    tombol.disabled = false;
                    tombol.textContent = teksAsli;
                    tampilkanPesan('Presensi gagal disimpan. Periksa koneksi lalu coba lagi.');
                });
        });
    });

    if (berlangsung) {
        pengatur = setInterval(function () { ambilStatus(false); }, jeda);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { ambilStatus(false); } });
    }
})();
