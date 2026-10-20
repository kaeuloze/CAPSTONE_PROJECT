// Dashboard tab Bulan Ini: angka grafik diperbarui otomatis (polling, keputusan 23) agar hasil pemeriksaan
// yang baru di-Submit di meja lain langsung terlihat. Tab Arsip tidak memakai polling.
(function () {
    var akar = document.getElementById('grafik-dashboard');
    if (!akar || !akar.getAttribute('data-url')) { return; }

    var url = akar.getAttribute('data-url');
    var jeda = parseInt(akar.getAttribute('data-jeda'), 10) || 5000;
    var periode = akar.getAttribute('data-periode');
    var indikator = document.getElementById('indikator-langsung');
    var sedangMengambil = false;

    function gambar(grafik) {
        Object.keys(grafik).forEach(function (kunciGrafik) {
            var kartu = akar.querySelector('[data-grafik="' + kunciGrafik + '"]');
            if (!kartu) { return; }
            var nilai = grafik[kunciGrafik];
            var maks = Math.max.apply(null, [1].concat(Object.keys(nilai).map(function (k) { return nilai[k]; })));
            Object.keys(nilai).forEach(function (kunci) {
                var batang = kartu.querySelector('[data-kunci="' + kunci + '"]');
                if (!batang) { return; }
                batang.querySelector('.nilai-batang').textContent = String(nilai[kunci]);
                batang.querySelector('.isi-batang').style.height = Math.round(nilai[kunci] / maks * 100) + '%';
            });
        });
    }

    function ambil() {
        if (sedangMengambil || document.hidden) { return; }
        sedangMengambil = true;
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) {
                if (r.status === 401 || r.status === 419) { window.location.reload(); return null; }
                if (!r.ok) { throw new Error('status ' + r.status); }
                return r.json();
            })
            .then(function (data) {
                if (!data) { return; }
                // Bulan berganti (mis. halaman dibiarkan terbuka melewati tengah malam akhir bulan) → muat ulang.
                if (data.periode !== periode) { window.location.reload(); return; }
                gambar(data.grafik);
                if (indikator) {
                    indikator.classList.remove('mati');
                    indikator.textContent = 'Diperbarui otomatis setiap ' + (jeda / 1000) + ' detik';
                }
            })
            .catch(function () {
                if (indikator) {
                    indikator.classList.add('mati');
                    indikator.textContent = 'Koneksi terputus, mencoba lagi...';
                }
            })
            .finally(function () { sedangMengambil = false; });
    }

    setInterval(ambil, jeda);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { ambil(); } });
})();
