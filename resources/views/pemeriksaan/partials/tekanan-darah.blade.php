@php
    $wajib = $wajib ?? false;
    $galat = $errors->first("bagian.{$kunci}.td_sistolik") ?: $errors->first("bagian.{$kunci}.td_diastolik");
@endphp

<fieldset @class(['isian', 'isian-td', 'salah' => $galat])>
    <legend class="label">
        Tekanan Darah (mmHg)
        @if ($wajib)<span class="wajib" aria-hidden="true">*</span>@endif
    </legend>

    <div class="baris-td">
        <input type="number"
               id="isian-{{ $kunci }}-td_sistolik"
               name="bagian[{{ $kunci }}][td_sistolik]"
               value="{{ old("bagian.$kunci.td_sistolik", $isi['td_sistolik'] ?? null) }}"
               inputmode="numeric" min="50" max="300" step="1" placeholder="Sistolik"
               aria-label="Sistolik (angka atas)"
               @if ($wajib) data-wajib @endif
               autocomplete="off" />

        <span aria-hidden="true">/</span>

        <input type="number"
               id="isian-{{ $kunci }}-td_diastolik"
               name="bagian[{{ $kunci }}][td_diastolik]"
               value="{{ old("bagian.$kunci.td_diastolik", $isi['td_diastolik'] ?? null) }}"
               inputmode="numeric" min="30" max="200" step="1" placeholder="Diastolik"
               aria-label="Diastolik (angka bawah)"
               @if ($wajib) data-wajib @endif
               autocomplete="off" />
    </div>

    @if ($galat)
        <p class="galat-isian">{{ $galat }}</p>
    @endif
</fieldset>