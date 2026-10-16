{{--
    Tab Tahunan: "Skrining PPOK PUMA (Khusus Usia ≥ 40 tahun)" (Figma Dewasa 430:304; batas usia ≥ 40 dikoreksi pengguna).
    Skor jenis kelamin & usia otomatis dari data warga (disetujui); berisiko PPOK bila skor ≥ 6 (PDPI).
    Rujukan memakai centang bersama di kartu Penyuluhan (disetujui). Variabel: $kunci, $isi, $bagian, $warga, $kegiatan.
--}}
@php
    $usiaPuma = \App\Services\Pemeriksaan\Kalkulator::usia($warga, $kegiatan->tanggal_kegiatan);
    $skorJk = \App\Services\Pemeriksaan\Kalkulator::skorPumaJenisKelamin((bool) $warga->jenis_kelamin);
    $skorUsia = \App\Services\Pemeriksaan\Kalkulator::skorPumaUsia($usiaPuma);
    $batasPuma = config('posyandu.ambang.puma.berisiko_mulai');
@endphp
<h2>{{ $bagian->judul }} (Khusus Usia ≥ {{ config('posyandu.ambang.puma.usia_minimal') }} tahun)</h2>
<p class="ket">Item Pertanyaan. Skor jenis kelamin dan usia dihitung otomatis dari data warga. Centang untuk "Ya"; jika tidak dicentang maka otomatis "Tidak" saat tersimpan.</p>
<dl class="skor-otomatis">
    <dt>Jenis kelamin (perempuan = 0, laki-laki = 1)</dt>
    <dd>{{ $warga->jenis_kelamin ? 'Laki-laki' : 'Perempuan' }} → skor {{ $skorJk }}</dd>
    <dt>Usia (40-49 = 0, 50-59 = 1, &gt;= 60 = 2)</dt>
    <dd>{{ $usiaPuma }} tahun → skor {{ $skorUsia }}</dd>
</dl>
@include('pemeriksaan.partials.isian', [
    'kolom' => 'skor_merokok', 'label' => 'Merokok (Tidak = 0, <20 bks/th = 0, 20-30 bks/th = 1, >30 bks/th = 2)', 'tipe' => 'select',
    'placeholder' => 'Pilih status merokok',
    'pilihan' => ['0' => 'Tidak merokok / < 20 bungkus-tahun (skor 0)', '1' => '20 - 30 bungkus-tahun (skor 1)', '2' => '> 30 bungkus-tahun (skor 2)'],
])
@include('pemeriksaan.partials.centang', ['kolom' => 'gejala_napas_pendek', 'label' => 'Pernah merasa napas pendek ketika berjalan lebih cepat pada jalan yang datar atau pada jalan yang sedikit menanjak?'])
@include('pemeriksaan.partials.centang', ['kolom' => 'gejala_dahak', 'label' => 'Mempunyai dahak yang berasal dari paru atau kesulitan mengeluarkan dahak saat Anda sedang tidak menderita flu?'])
@include('pemeriksaan.partials.centang', ['kolom' => 'gejala_batuk_tanpa_flu', 'label' => 'Batuk walau tidak sedang flu?'])
@include('pemeriksaan.partials.centang', ['kolom' => 'pernah_spirometri', 'label' => 'Pernah diminta nakes melakukan pemeriksaan spirometri atau peakflow meter (meniup ke dalam suatu alat)?'])
<div class="info hasil-otomatis" id="hasil-puma" aria-live="polite" data-skor-tetap="{{ $skorJk + $skorUsia }}" data-batas="{{ $batasPuma }}">
    <div><strong>Skor PUMA</strong> <span id="hasil-puma-isi">Pilih status merokok untuk menghitung skor</span><br>
        <small>Berisiko PPOK bila skor ≥ {{ $batasPuma }} — centang Rujukan ke Puskesmas di kartu Penyuluhan.</small></div>
</div>
