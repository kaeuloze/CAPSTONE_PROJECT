// Daftar Kelola Pemeriksaan: diperbarui otomatis (polling) agar warga yang baru Hadir
// di meja presensi dan status draft dari meja lain langsung terlihat (keputusan 23).
// Lonceng notifikasi: angka + daftar nama, pesan singkat, dan bunyi saat ada warga baru Hadir
// (pengecekan kader 31 Okt 2026).
(function () {
    // Halaman "Belum ada kegiatan aktif": cek berkala, lalu muat ulang begitu kegiatan dimulai di meja lain.
    var menunggu = document.getElementById('menunggu-kegiatan');
    if (menunggu) {
        setInterval(function () {
            if (document.hidden) { return; }
            fetch(menunggu.getAttribute('data-url-status'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) { if (data && data.aktif) { window.location.reload(); } })
                .catch(function () {});
        }, parseInt(menunggu.getAttribute('data-jeda'), 10) || 5000);
        return;
    }

    var akar = document.getElementById('antrean');
    if (!akar) { return; }

    var urlStatus = akar.getAttribute('data-url-status');
    var idKegiatan = akar.getAttribute('data-id-kegiatan');
    var jeda = parseInt(akar.getAttribute('data-jeda'), 10) || 5000;
    var kelasKategori = JSON.parse(akar.getAttribute('data-kelas-kategori') || '{}');
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
            tr.className = 'transition hover:bg-mawar-50/40';
            tr.appendChild(sel(String(b.no)));
            tr.appendChild(sel(b.nama));
            tr.appendChild(sel(b.nik));
            var tdKategori = document.createElement('td');
            var badge = document.createElement('span');
            badge.className = 'badge ' + (kelasKategori[b.kode_kategori] || '');
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

    // ---------- Lonceng notifikasi ----------
    var hadirTerakhir = akar.getAttribute('data-hadir-terakhir') || '';
    var sudahDiketahui = {}; // NIK yang sudah dinotifikasi / sudah hadir saat halaman dibuka
    var tombolLonceng = document.getElementById('tombol-lonceng');
    var angkaLonceng = document.getElementById('angka-lonceng');
    var daftarLonceng = document.getElementById('daftar-lonceng');
    var isiLonceng = document.getElementById('isi-lonceng');
    var wadahPesan = document.getElementById('pesan-lonceng');
    var jumlahBaru = 0;
    var suara = null;

    function perbaruiAngka() {
        angkaLonceng.textContent = jumlahBaru > 99 ? '99+' : String(jumlahBaru);
        angkaLonceng.hidden = jumlahBaru === 0;
        tombolLonceng.setAttribute('aria-label', jumlahBaru > 0
            ? jumlahBaru + ' warga baru hadir' : 'Notifikasi warga baru hadir');
    }

    function isiTemplat(idTemplat, w) {
        var el = document.getElementById(idTemplat).content.firstElementChild.cloneNode(true);
        el.querySelector('[data-nama]').textContent = w.nama;
        el.querySelector('[data-jam]').textContent = w.jam ? '(' + w.jam + ')' : '';
        return el;
    }

    function tampilkanPesan(w) {
        var pesan = isiTemplat('templat-pesan-lonceng', w);
        wadahPesan.appendChild(pesan);
        setTimeout(function () { pesan.remove(); }, 6000);
    }

    // Bunyi singkat dibuat langsung oleh browser (Web Audio), tanpa berkas suara.
    // Browser baru mengizinkan bunyi setelah kader pernah mengeklik / mengetuk halaman.
    function siapkanSuara() {
        if (suara || !(window.AudioContext || window.webkitAudioContext)) { return; }
        suara = new (window.AudioContext || window.webkitAudioContext)();
    }
    document.addEventListener('click', siapkanSuara, { once: true });
    document.addEventListener('keydown', siapkanSuara, { once: true });

    function bunyi() {
        if (!suara) { return; }
        if (suara.state === 'suspended') { suara.resume(); }
        [0, 0.18].forEach(function (mulai, i) {
            var nada = suara.createOscillator();
            var volume = suara.createGain();
            nada.type = 'sine';
            nada.frequency.value = i === 0 ? 880 : 1175;
            volume.gain.setValueAtTime(0.0001, suara.currentTime + mulai);
            volume.gain.exponentialRampToValueAtTime(0.2, suara.currentTime + mulai + 0.02);
            volume.gain.exponentialRampToValueAtTime(0.0001, suara.currentTime + mulai + 0.3);
            nada.connect(volume).connect(suara.destination);
            nada.start(suara.currentTime + mulai);
            nada.stop(suara.currentTime + mulai + 0.32);
        });
    }

    function terimaHadirBaru(daftar, terakhir) {
        var baru = (daftar || []).filter(function (w) { return !sudahDiketahui[w.nik]; });
        baru.forEach(function (w) {
            sudahDiketahui[w.nik] = true;
            var kosong = isiLonceng.querySelector('[data-kosong]');
            if (kosong) { kosong.remove(); }
            isiLonceng.insertBefore(isiTemplat('templat-isi-lonceng', w), isiLonceng.firstChild);
            tampilkanPesan(w);
        });
        if (baru.length > 0) {
            jumlahBaru += baru.length;
            perbaruiAngka();
            bunyi();
        }
        if (terakhir) { hadirTerakhir = terakhir; }
    }

    tombolLonceng.addEventListener('click', function () {
        var buka = daftarLonceng.hidden;
        daftarLonceng.hidden = !buka;
        tombolLonceng.setAttribute('aria-expanded', buka ? 'true' : 'false');
        if (buka) { jumlahBaru = 0; perbaruiAngka(); } // dibaca → angka hilang
    });
    document.addEventListener('click', function (e) {
        if (!daftarLonceng.hidden && !document.getElementById('lonceng').contains(e.target)) {
            daftarLonceng.hidden = true;
            tombolLonceng.setAttribute('aria-expanded', 'false');
        }
    });

    // ---------- Polling ----------
    function alamatStatus() {
        var param = new URLSearchParams(window.location.search);
        if (hadirTerakhir) { param.set('sejak', hadirTerakhir); }
        var q = param.toString();
        return urlStatus + (q ? '?' + q : '');
    }

    var awal = true;
    function ambil() {
        if (sedangMengambil || document.hidden) { return; }
        sedangMengambil = true;
        fetch(alamatStatus(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
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
                if (awal) {
                    // Jawaban pertama: warga yang sudah hadir saat halaman dibuka tidak dianggap baru.
                    awal = false;
                    (data.hadir_baru || []).forEach(function (w) { sudahDiketahui[w.nik] = true; });
                    if (data.hadir_terakhir) { hadirTerakhir = data.hadir_terakhir; }
                } else {
                    terimaHadirBaru(data.hadir_baru, data.hadir_terakhir);
                }
                indikator.classList.remove('mati');
                indikator.textContent = 'Diperbarui otomatis setiap ' + (jeda / 1000) + ' detik';
            })
            .catch(function () {
                indikator.classList.add('mati');
                indikator.textContent = 'Koneksi terputus, mencoba lagi...';
            })
            .finally(function () { sedangMengambil = false; });
    }

    ambil();
    setInterval(ambil, jeda);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { ambil(); } });
})();
