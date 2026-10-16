// Kotak "Hasil dihitung otomatis (Kalkulator IMT)": dihitung langsung saat kader mengetik.
// Batas sama dengan backend (config/posyandu.php → ambang); hasil akhir tetap dihitung ulang di server saat Simpan.
(function () {
    var kotak = document.getElementById('hasil-otomatis');
    if (!kotak) { return; }
    var batas = JSON.parse(kotak.getAttribute('data-ambang'));
    var lakiLaki = kotak.getAttribute('data-laki-laki') === '1';

    function angka(id) {
        var el = document.getElementById(id);
        var v = el ? parseFloat(el.value) : NaN;
        return isNaN(v) ? null : v;
    }
    function tulis(id, teks) { document.getElementById(id).textContent = teks; }

    function hitung() {
        var bb = angka('isian-pengukuran-berat_badan');
        var tb = angka('isian-pengukuran-tinggi_badan');
        var lp = angka('isian-pengukuran-lingkar_perut');
        var sis = angka('isian-pengukuran-td_sistolik');
        var dia = angka('isian-pengukuran-td_diastolik');

        if (bb && tb) {
            var imt = Math.round(bb / Math.pow(tb / 100, 2) * 10) / 10;
            var status = imt < batas.imt.normal_mulai ? 'Kurus (Risiko)'
                : imt < batas.imt.gemuk_mulai ? 'Normal'
                : imt <= batas.imt.gemuk_sampai ? 'Gemuk (Risiko)' : 'Obesitas (Risiko)';
            tulis('hasil-imt', imt.toFixed(1).replace('.', ',') + ' kg/m² — ' + status);
        } else { tulis('hasil-imt', 'Isi berat & tinggi badan'); }

        if (sis !== null && dia !== null) {
            var tinggi = sis >= batas.tekanan_darah.sistolik_tinggi || dia >= batas.tekanan_darah.diastolik_tinggi;
            tulis('hasil-td', sis + '/' + dia + ' mmHg — ' + (tinggi ? 'Tinggi (Risiko)' : 'Normal'));
        } else { tulis('hasil-td', 'Isi tekanan darah'); }

        if (lp !== null) {
            var risiko = lp > batas.lingkar_perut[lakiLaki ? 'laki_laki' : 'perempuan'];
            tulis('hasil-lp', lp + ' cm — ' + (risiko ? 'Berisiko' : 'Normal'));
        } else { tulis('hasil-lp', 'Isi lingkar perut'); }

        var gejala = document.querySelectorAll('[data-bagian="tbc"] input[type=checkbox]:checked').length;
        tulis('hasil-tbc', gejala + ' gejala "Ya" — ' + (gejala >= batas.tbc_minimal_gejala ? 'Berisiko, rujuk Puskesmas' : 'Normal'));
    }

    document.getElementById('form-pemeriksaan').addEventListener('input', hitung);
    document.getElementById('form-pemeriksaan').addEventListener('change', hitung);
    hitung();
})();
