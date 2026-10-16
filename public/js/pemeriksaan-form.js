// Formulir pemeriksaan: tab, penanda bagian yang diubah (simpan per bagian),
// tombol Submit aktif setelah isian wajib (*) terisi, konfirmasi Batal, dan status memuat.
// Dipakai juga di Detail Riwayat (hanya-baca: hanya tab) dan Edit Riwayat.
(function () {
    var form = document.getElementById('form-pemeriksaan');
    if (!form) { return; }

    var daftarDiubah = document.getElementById('daftar-diubah');
    var tombolSubmit = document.getElementById('tombol-submit');
    var adaPerubahan = daftarDiubah ? daftarDiubah.children.length > 0 : false;

    // ---- Tab ----
    var tombolTab = document.querySelectorAll('[data-tab]');
    var catatanAksi = document.getElementById('catatan-aksi');
    function bukaTab(kunci) {
        var catatan = null;
        tombolTab.forEach(function (t) {
            var aktif = t.getAttribute('data-tab') === kunci;
            t.classList.toggle('aktif', aktif);
            t.setAttribute('aria-selected', aktif ? 'true' : 'false');
            if (aktif) { catatan = t.getAttribute('data-catatan'); }
        });
        document.querySelectorAll('.panel-tab').forEach(function (p) {
            p.hidden = p.id !== 'panel-' + kunci;
        });
        // Elemen khusus tab tertentu (mis. kotak "Hasil dihitung otomatis" hanya di Rutin Bulanan).
        document.querySelectorAll('[data-hanya-tab]').forEach(function (el) {
            el.hidden = el.getAttribute('data-hanya-tab') !== kunci;
        });
        if (catatanAksi) { catatanAksi.textContent = catatan || catatanAksi.getAttribute('data-bawaan'); }
    }

    // Peringatan interval 6 bulan / 1 tahun (Activity Diagram 2.17 & 2.18): pop-up sekali per tab.
    var dialogPeringatan = document.getElementById('dialog-peringatan');
    var tabMenunggu = null;
    tombolTab.forEach(function (t) {
        t.addEventListener('click', function () {
            var pesan = t.getAttribute('data-peringatan');
            if (pesan && !t.dataset.sudahDiingatkan) {
                tabMenunggu = t;
                document.getElementById('isi-peringatan').textContent = pesan;
                dialogPeringatan.showModal();
                return;
            }
            bukaTab(t.getAttribute('data-tab'));
        });
    });
    if (dialogPeringatan) {
        document.getElementById('peringatan-lanjut').addEventListener('click', function () {
            tabMenunggu.dataset.sudahDiingatkan = '1';
            dialogPeringatan.close();
            bukaTab(tabMenunggu.getAttribute('data-tab'));
        });
        document.getElementById('peringatan-kembali').addEventListener('click', function () { dialogPeringatan.close(); });
    }
    var tabPertama = document.querySelector('[data-tab].aktif');
    if (tabPertama) { bukaTab(tabPertama.getAttribute('data-tab')); }
    // Bila ada isian salah di tab lain, langsung buka tab tersebut.
    var salahPertama = form.querySelector('.salah');
    if (salahPertama) {
        var panel = salahPertama.closest('.panel-tab');
        if (panel) { bukaTab(panel.id.replace('panel-', '')); }
    }

    if (!daftarDiubah) { return; } // halaman hanya-baca (Detail Riwayat): cukup tab

    // ---- Penanda bagian yang diubah ----
    function tandaiDiubah(bagian) {
        var kunci = bagian.getAttribute('data-bagian');
        adaPerubahan = true;
        if (daftarDiubah.querySelector('input[value="' + kunci + '"]')) { return; }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'diubah[]';
        input.value = kunci;
        daftarDiubah.appendChild(input);
    }
    form.querySelectorAll('[data-bagian]').forEach(function (bagian) {
        ['input', 'change'].forEach(function (jenis) {
            bagian.addEventListener(jenis, function () { tandaiDiubah(bagian); perbaruiSubmit(); });
        });
    });

    // ---- Submit aktif setelah semua isian wajib terisi ----
    function perbaruiSubmit() {
        var lengkap = Array.prototype.every.call(form.querySelectorAll('[data-wajib]'), function (el) {
            return el.value.trim() !== '';
        });
        tombolSubmit.disabled = !lengkap;
        tombolSubmit.title = lengkap ? '' : 'Lengkapi semua isian bertanda * terlebih dahulu';
    }
    perbaruiSubmit();

    // ---- Batal / Kembali: konfirmasi bila ada perubahan ----
    document.querySelectorAll('[data-batal]').forEach(function (tautan) {
        tautan.addEventListener('click', function (e) {
            if (!adaPerubahan) { return; }
            e.preventDefault();
            document.getElementById('dialog-batal').showModal();
        });
    });

    // ---- Status memuat: kirim nilai tombol yang ditekan, lalu kunci tombol ----
    form.addEventListener('submit', function (e) {
        var penekan = e.submitter;
        if (penekan && penekan.name) {
            var aksi = document.createElement('input');
            aksi.type = 'hidden';
            aksi.name = penekan.name;
            aksi.value = penekan.value;
            form.appendChild(aksi);
            penekan.textContent = penekan.value === 'submit' ? 'Mengirim...' : 'Menyimpan...';
        }
        adaPerubahan = false;
        setTimeout(function () {
            form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
        }, 0);
    });
})();
