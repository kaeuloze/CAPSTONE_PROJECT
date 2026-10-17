<?php

namespace App\Http\Requests\Kegiatan;

use App\Models\Kegiatan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir Buat Kegiatan (wireframe 2.40) dan Detail/Edit Kegiatan (wireframe 2.41).
 */
class SimpanKegiatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dibatasi middleware auth
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_kegiatan' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('nama_kegiatan'))),
        ]);
    }

    public function rules(): array
    {
        /** @var Kegiatan|null $kegiatan */
        $kegiatan = $this->route('kegiatan');

        return [
            'nama_kegiatan' => ['required', 'string', 'max:100', 'not_regex:/[<>]/'],
            // Hari ini atau sebelumnya (disetujui): kegiatan langsung dimulai saat dibuat.
            'tanggal_kegiatan' => ['required', 'date_format:Y-m-d', 'after:2000-01-01', 'before_or_equal:today'],
            'status_kegiatan' => [$kegiatan ? 'required' : 'prohibited', Rule::in(['0', '1'])],
            'versi' => [$kegiatan ? 'required' : 'prohibited', 'nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    /** Data siap disimpan. */
    public function dataKegiatan(): array
    {
        $data = $this->safe()->only(['nama_kegiatan', 'tanggal_kegiatan', 'status_kegiatan']);
        if (array_key_exists('status_kegiatan', $data)) {
            $data['status_kegiatan'] = (string) $data['status_kegiatan'] === '1';
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'nama_kegiatan.max' => 'Nama kegiatan maksimal :max karakter.',
            'nama_kegiatan.not_regex' => 'Nama kegiatan tidak boleh mengandung karakter < atau >.',
            'tanggal_kegiatan.date_format' => 'Format tanggal pelaksanaan tidak valid.',
            'tanggal_kegiatan.after' => 'Tanggal pelaksanaan tidak valid.',
            'tanggal_kegiatan.before_or_equal' => 'Tanggal pelaksanaan tidak boleh melewati hari ini.',
            'status_kegiatan.in' => 'Pilih status Selesai atau Berlangsung.',
            'status_kegiatan.prohibited' => 'Formulir tidak valid.',
            'versi.required' => 'Formulir tidak lengkap. Muat ulang halaman lalu coba lagi.',
            'versi.prohibited' => 'Formulir tidak valid.',
            'versi.date_format' => 'Formulir tidak lengkap. Muat ulang halaman lalu coba lagi.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_kegiatan' => 'Nama kegiatan',
            'tanggal_kegiatan' => 'Tanggal pelaksanaan',
            'status_kegiatan' => 'Status kegiatan',
        ];
    }
}
