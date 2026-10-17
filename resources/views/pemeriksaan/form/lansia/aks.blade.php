{{--
    "Pemeriksaan tahunan Skrining Aktifitas Kehidupan Sehari-hari (AKS)" — Kartu Bantu Pemeriksaan Lansia (C2), Figma 430:683.
    Rentang skor per item mengikuti formulir kertas (disetujui). Bila diisi, ke-10 item wajib lengkap sebelum Submit.
    Variabel: $kunci, $isi, $bagian.
--}}
@php
    $item = [
        'skor_bab' => ['Mengendalikan rangsang Buang Air Besar (BAB)', ['Tidak terkendali / tak teratur (perlu pencahar)', 'Kadang-kadang tak terkendali (1x / minggu)', 'Terkendali teratur']],
        'skor_bak' => ['Mengendalikan rangsang Buang Air Kecil (BAK)', ['Tidak terkendali atau pakai kateter', 'Kadang-kadang tak terkendali (1x / 24 jam)', 'Mandiri']],
        'skor_kebersihan_diri' => ['Membersihkan diri (mencuci wajah, menyikat rambut, mencukur kumis, sikat gigi)', ['Butuh pertolongan orang lain', 'Mandiri']],
        'skor_penggunaan_wc' => ['Penggunaan WC (keluar masuk WC, melepas/memakai celana, cebok, menyiram)', ['Tergantung pertolongan orang lain', 'Perlu pertolongan pada beberapa kegiatan tetapi dapat mengerjakan sendiri beberapa kegiatan yang lain', 'Mandiri']],
        'skor_makan_minum' => ['Makan minum (jika makan harus berupa potongan, dianggap dibantu)', ['Tidak mampu', 'Perlu ditolong memotong makanan', 'Mandiri']],
        'skor_transfer_kursi' => ['Bergerak dari kursi roda ke tempat tidur dan sebaliknya (termasuk duduk di tempat tidur)', ['Tidak mampu', 'Perlu bantuan untuk bisa duduk (2 orang)', 'Bantuan minimal 1 orang', 'Mandiri']],
        'skor_berjalan' => ['Berjalan di tempat rata (atau jika tidak bisa berjalan, menjalankan kursi roda)', ['Tidak mampu', 'Bisa (pindah) dengan kursi roda', 'Berjalan dengan bantuan 1 orang', 'Mandiri']],
        'skor_berpakaian' => ['Berpakaian (termasuk memasang tali sepatu, mengencangkan sabuk)', ['Tergantung orang lain', 'Sebagian dibantu (misal mengancing baju)', 'Mandiri']],
        'skor_naik_tangga' => ['Naik turun tangga', ['Tidak mampu', 'Butuh pertolongan', 'Mandiri']],
        'skor_mandi' => ['Mandi', ['Tergantung orang lain', 'Mandiri']],
    ];
    $batasAks = config('posyandu.ambang.aks');
@endphp
<h2>{{ $bagian->judul }}</h2>
<p class="ket">Pilih skor setiap item. Bila AKS dikerjakan, ke-10 item wajib dipilih sebelum Submit.</p>
<div class="kisi-aks">
    @foreach ($item as $kolom => [$label, $keterangan])
        @include('pemeriksaan.partials.isian', [
            'kolom' => $kolom, 'label' => $label, 'tipe' => 'select', 'placeholder' => 'Pilih skor',
            'pilihan' => collect($keterangan)->mapWithKeys(fn ($teks, $skor) => [(string) $skor => "Skor {$skor} — {$teks}"])->all(),
        ])
    @endforeach
</div>
<div class="info hasil-otomatis" id="hasil-aks" aria-live="polite" data-batas="{{ json_encode($batasAks) }}">
    <div><strong>Total AKS</strong> <span id="hasil-aks-isi">-</span><br>
        <small>Tingkat Ketergantungan: Mandiri (M={{ $batasAks['mandiri'] }}) · Ringan (R={{ $batasAks['ringan_mulai'] }}–{{ $batasAks['mandiri'] - 1 }}) · Sedang (S={{ $batasAks['sedang_mulai'] }}–{{ $batasAks['ringan_mulai'] - 1 }}) · Berat (B={{ $batasAks['berat_mulai'] }}–{{ $batasAks['sedang_mulai'] - 1 }}) · Total (T=0–{{ $batasAks['berat_mulai'] - 1 }}). Jika skor &lt; {{ $batasAks['mandiri'] }} → rujuk ke Pustu/Puskesmas (centang Rujukan di kartu Penyuluhan).</small></div>
</div>
