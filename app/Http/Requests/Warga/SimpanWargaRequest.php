<?php

namespace App\Http\Requests\Warga;

use App\Enums\KategoriUsia;
use App\Enums\StatusKondisi;
use App\Models\Warga;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi formulir Tambah / Edit Warga (wireframe Gambar 2.44).
 * Input wajib: Nama, NIK, Tanggal Lahir, Jenis Kelamin, Alamat. Nomor HP opsional.
 */
class SimpanWargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dibatasi middleware auth
    }

    /** Merapikan input sebelum divalidasi (spasi berlebih, tanda baca di nomor HP). */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_warga' => $this->rapikanSpasi($this->input('nama_warga')),
            'nik' => preg_replace('/\s+/', '', (string) $this->input('nik')),
            'no_hp' => ($hp = preg_replace('/[\s\-().]/', '', (string) $this->input('no_hp'))) === '' ? null : $hp,
            'alamat' => $this->rapikanSpasi($this->input('alamat')),
            'status_kondisi' => $this->input('status_kondisi') ?: StatusKondisi::Normal->value,
            'penyakit' => array_values((array) $this->input('penyakit', [])),
        ]);
    }

    public function rules(): array
    {
        /** @var Warga|null $warga */
        $warga = $this->route('warga');

        return [
            'nama_warga' => ['required', 'string', 'max:100', "regex:/^[\\pL\\s.,'\\-]+$/u"],
            'nik' => [
                'required', 'string', 'regex:/^[0-9]{16}$/',
                Rule::unique('warga', 'nik')->ignore($warga?->getKey(), 'nik'),
            ],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d', 'after:1900-01-01', 'before_or_equal:today'],
            'no_hp' => ['nullable', 'string', 'max:15', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'jenis_kelamin' => ['required', Rule::in(['0', '1'])],
            'alamat' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'status_kondisi' => ['required', Rule::enum(StatusKondisi::class)],
            'penyakit' => ['array', 'max:20'],
            'penyakit.*' => ['integer', 'distinct', Rule::exists('penyakit', 'id_penyakit')],
            // Penanda versi untuk pengaman edit bersamaan (hanya pada Edit).
            'versi' => [$warga ? 'required' : 'prohibited', 'nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    /** Aturan tambahan: kondisi hamil/nifas/menyusui hanya untuk perempuan non-balita. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['jenis_kelamin', 'tanggal_lahir', 'status_kondisi'])) {
                    return;
                }
                if ($this->input('status_kondisi') === StatusKondisi::Normal->value) {
                    return;
                }
                $lakiLaki = (string) $this->input('jenis_kelamin') === '1';
                $balita = KategoriUsia::dariTanggalLahir($this->input('tanggal_lahir')) === KategoriUsia::Balita;
                if ($lakiLaki || $balita) {
                    $validator->errors()->add('status_kondisi', 'Status hamil/nifas/menyusui hanya untuk warga perempuan yang bukan balita.');
                }
            },
        ];
    }

    /** Data identitas warga siap disimpan (tanpa penyakit & versi). */
    public function dataWarga(): array
    {
        $data = $this->safe()->only(['nama_warga', 'nik', 'tanggal_lahir', 'no_hp', 'jenis_kelamin', 'alamat', 'status_kondisi']);
        $data['jenis_kelamin'] = (string) $data['jenis_kelamin'] === '1';

        return $data;
    }

    /** @return array<int, int> */
    public function idPenyakit(): array
    {
        return array_map('intval', $this->safe()->input('penyakit', []));
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'nama_warga.max' => 'Nama lengkap maksimal :max karakter.',
            'alamat.max' => 'Alamat maksimal :max karakter.',
            'no_hp.max' => 'Nomor HP tidak valid. Contoh: 081234567890 atau +6281234567890.',
            'penyakit.max' => 'Pilihan riwayat penyakit terlalu banyak.',
            'nama_warga.regex' => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung.',
            'nik.regex' => 'NIK harus terdiri dari tepat 16 angka.',
            'nik.unique' => 'NIK ini sudah terdaftar atas nama warga lain.',
            'tanggal_lahir.date_format' => 'Format tanggal lahir tidak valid.',
            'tanggal_lahir.after' => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melewati hari ini.',
            'no_hp.regex' => 'Nomor HP tidak valid. Contoh: 081234567890 atau +6281234567890.',
            'jenis_kelamin.in' => 'Pilih jenis kelamin.',
            'alamat.not_regex' => 'Alamat tidak boleh mengandung karakter < atau >.',
            'status_kondisi.enum' => 'Status kondisi khusus tidak valid.',
            'penyakit.*.exists' => 'Pilihan riwayat penyakit tidak valid.',
            'penyakit.*.distinct' => 'Pilihan riwayat penyakit tidak boleh ganda.',
            'penyakit.*.integer' => 'Pilihan riwayat penyakit tidak valid.',
            'versi.required' => 'Formulir tidak lengkap. Muat ulang halaman lalu coba lagi.',
            'versi.prohibited' => 'Formulir tidak valid.',
            'versi.date_format' => 'Formulir tidak lengkap. Muat ulang halaman lalu coba lagi.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_warga' => 'Nama lengkap',
            'nik' => 'NIK',
            'tanggal_lahir' => 'Tanggal lahir',
            'no_hp' => 'Nomor HP',
            'jenis_kelamin' => 'Jenis kelamin',
            'alamat' => 'Alamat',
            'status_kondisi' => 'Status kondisi khusus',
        ];
    }

    private function rapikanSpasi(mixed $teks): ?string
    {
        if ($teks === null) {
            return null;
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $teks));
    }
}
