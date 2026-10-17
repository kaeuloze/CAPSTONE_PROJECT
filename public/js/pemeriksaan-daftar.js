// Daftar Kelola Pemeriksaan: diperbarui otomatis (polling) agar warga yang baru Hadir
// di meja presensi dan status draft dari meja lain langsung terlihat (keputusan 23).
(function () {
    var akar = document.getElementById('antrean');
    if (!akar) { return; }

    var urlStatus = akar.getAttribute('data-url-status');
    var idKegiatan = akar.getAttribute('data-id-kegiatan');
    var jeda = parseInt(akar.getAttribute('data-jeda'), 10) || 5000;
    var isi = document.getElementById('isi-antrean');
    var indikator = document.getElementById('indikator-langsung');
    var sedangMengambil = false;

    function sel(teks, kelas) {
        var td = document.createElement('td');
        if (kelas) { td.className = kelas; }
        td.textContent = teks; // textContent: aman dari HTML berbahaya
        return td;
    }

    function gambarUlang(baris) {
        var potongan = document.createDocumentFragment();
        if (baris.length === 0) {
            var tr = document.createElement('tr');
            var td = sel(isi.getAttribute('data-pesan-kosong'), 'kosong');
            td.colSpan = 5;
            tr.appendChild(td);
            potongan.appendChild(tr);
        }
        baris.forEach(function (b) {
            var tr = document.createElement('tr');
            tr.appendChild(sel(String(b.no)));
            tr.appendChild(sel(b.nama));
            tr.appendChild(sel(b.nik));
            var tdKategori = document.createElement('td');
            var badge = document.createElement('span');
            badge.className = 'badge';
            badge.textContent = b.kategori;
            tdKategori.appendChild(badge);
            tr.appendChild(tdKategori);
            var tdAksi = document.createElement('td');
            var tautan = document.createElement('a');
            tautan.href = b.url;
            tautan.className = 'tombol kecil ' + (b.draft ? 'draft' : 'abu');
            tautan.textContent = b.draft ? 'Pemeriksaan Belum Selesai' : 'Mulai Pemeriksaan';
            tdAksi.appendChild(tautan);
            tr.appendChild(tdAksi);
            potongan.appendChild(tr);
        });
        isi.replaceChildren(potongan);
    }

    function ambil() {
        if (sedangMengambil || document.hidden) { return; }
        sedangMengambil = true;
        fetch(urlStatus + window.location.search, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) {
                if (r.status === 401 || r.status === 419) { window.location.reload(); return null; }
                if (!r.ok) { throw new Error('status ' + r.status); }
                return r.json();
            })
            .then(function (data) {
                if (!data) { return; }
                // Kegiatan selesai / berganti → muat ulang agar tampilan sesuai (mis. halaman "belum ada kegiatan aktif").
                if (!data.aktif || String(data.id_kegiatan) !== idKegiatan) { window.location.reload(); return; }
                gambarUlang(data.baris);
                indikator.classList.remove('mati');
                indikator.textContent = 'Diperbarui otomatis setiap ' + (jeda / 1000) + ' detik';
            })
            .catch(function () {
                indikator.classList.add('mati');
                indikator.textContent = 'Koneksi terputus, mencoba lagi...';
            })
            .finally(function () { sedangMengambil = false; });
    }

    setInterval(ambil, jeda);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { ambil(); } });
})();
