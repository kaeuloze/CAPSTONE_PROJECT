// Formulir pemeriksaan: tab, penanda bagian yang diubah (simpan per bagian),
// tombol Submit aktif setelah isian wajib (*) terisi, konfirmasi Batal, dan status memuat.
(function () {
    var form = document.getElementById('form-pemeriksaan');
    if (!form) { return; }

    var daftarDiubah = document.getElementById('daftar-diubah');
    var tombolSubmit = document.getElementById('tombol-submit');
    var adaPerubahan = daftarDiubah.children.length > 0;

    // ---- Tab ----
    var tombolTab = document.querySelectorAll('[data-tab]');
    function bukaTab(kunci) {
        tombolTab.forEach(function (t) {
            var aktif = t.getAttribute('data-tab') === kunci;
            t.classList.toggle('aktif', aktif);
            t.setAttribute('aria-selected', aktif ? 'true' : 'false');
        });
        document.querySelectorAll('.panel-tab').forEach(function (p) {
            p.hidden = p.id !== 'panel-' + kunci;
        });
    }
    tombolTab.forEach(function (t) {
        t.addEventListener('click', function () { bukaTab(t.getAttribute('data-tab')); });
    });
    // Bila ada isian salah di tab lain, langsung buka tab tersebut.
    var salahPertama = form.querySelector('.salah');
    if (salahPertama) {
        var panel = salahPertama.closest('.panel-tab');
        if (panel) { bukaTab(panel.id.replace('panel-', '')); }
    }

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
