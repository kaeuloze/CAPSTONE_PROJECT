{{--
    "Pemeriksaan Tahunan SKILAS" — Kartu Bantu Pemeriksaan Lansia (C3), Figma 430:683.
    Centang = "Ya" / ditemukan masalah (disetujui). Item COVID ditulis "Belum mendapat imunisasi COVID-19" (disetujui).
    Variabel: $kunci, $isi, $bagian.
--}}
@php
    $kelompok = [
        'Penurunan Kognitif' => ['orientasi_waktu_tempat' => 'Orientasi waktu dan tempat', 'mengulang_tiga_kata' => 'Mengulang ketiga kata'],
        'Keterbatasan Mobilitas' => ['tes_berdiri_kursi' => 'Tes berdiri dari kursi'],
        'Malnutrisi' => ['bb_berkurang' => 'BB berkurang > 3 kg dalam 3 bulan terakhir atau pakaian jadi lebih longgar', 'hilang_nafsu_makan' => 'Hilang nafsu makan / kesulitan makan', 'lila_kurang' => 'LILA < 23,5 cm'],
        'Gangguan Penglihatan' => ['masalah_penglihatan' => 'Masalah pada mata (sulit lihat jauh, membaca, penyakit mata, sedang dalam pengobatan Hipertensi/Diabetes)', 'tes_melihat' => 'Tes Melihat'],
        'Gangguan Pendengaran' => ['tes_berbisik' => 'Tes Berbisik'],
        'Gejala Depresi (dalam 2 minggu terakhir)' => ['perasaan_sedih' => 'Perasaan sedih, tertekan, atau putus asa', 'kurang_minat' => 'Sedikit minat atau kesenangan melakukan sesuatu'],
    ];
@endphp
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Centang bila ditemukan masalah / jawaban "Ya". Jika tidak dicentang maka otomatis "Tidak" saat tersimpan.</p>
@foreach ($kelompok as $namaKelompok => $pertanyaan)
    <div data-kelompok-skilas="{{ \Illuminate\Support\Str::before($namaKelompok, ' (') }}">
        <h3 class="sub-kelompok">{{ $namaKelompok }}</h3>
        @foreach ($pertanyaan as $kolom => $label)
            @include('pemeriksaan.partials.centang', ['kolom' => $kolom, 'label' => $label])
        @endforeach
    </div>
@endforeach
<div data-kelompok-skilas="Belum imunisasi COVID-19">
    <h3 class="sub-kelompok">Imunisasi</h3>
    @include('pemeriksaan.partials.centang', ['kolom' => 'belum_imunisasi_covid', 'label' => 'Belum mendapat imunisasi COVID-19'])
</div>
<div class="info hasil-otomatis" id="hasil-skilas" aria-live="polite">
    <div><strong>Hasil SKILAS</strong> <span id="hasil-skilas-isi">-</span></div>
</div>
