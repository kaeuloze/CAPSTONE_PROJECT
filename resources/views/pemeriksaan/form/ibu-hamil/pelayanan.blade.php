{{-- "Pelayanan Kesehatan" (kartu kertas: jumlah TTD/MMS = angka, MT Bumil KEK = teks; disetujui). --}}
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Centang untuk "Ya"; jika tidak dicentang maka otomatis "Tidak" saat tersimpan.</p>
@include('pemeriksaan.partials.isian', ['kolom' => 'jumlah_ttd_mms', 'label' => 'Jumlah TTD/MMS yang diberikan nakes (butir)', 'min' => 0, 'max' => 500, 'step' => '1'])
@include('pemeriksaan.partials.centang', ['kolom' => 'status_konsumsi_ttd_rutin', 'label' => 'Konsumsi TTD/MMS rutin (1 butir setiap hari selama kehamilan)'])
<div class="isian" style="max-width:none">
    @include('pemeriksaan.partials.isian', ['kolom' => 'komposisi_mt_bumil_kek', 'label' => 'Jika nakes memberikan MT Bumil KEK, tuliskan komposisi dan jumlah porsi', 'tipe' => 'text', 'placeholder' => 'mis. biskuit MT 2 keping/hari'])
</div>
@include('pemeriksaan.partials.centang', ['kolom' => 'status_rutin_mt_kek', 'label' => 'Rutin konsumsi MT Bumil KEK'])
