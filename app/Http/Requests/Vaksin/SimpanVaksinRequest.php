<?php

namespace App\Http\Requests\Vaksin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi popup Tambah / Edit Vaksin (Activity Diagram 2.22 & 2.23). */
class SimpanVaksinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vaksin = $this->route('vaksin');

        return [
            'nama_vaksin' => [
                'required', 'string', 'max:100', 'not_regex:/[<>]/',
                Rule::unique('vaksin', 'nama_vaksin')->ignore($vaksin?->id_vaksin, 'id_vaksin'),
            ],
            'deskripsi' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'versi' => [$vaksin ? 'required' : 'nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_vaksin.required' => 'Nama vaksin harus diisi.',
            'nama_vaksin.unique' => 'Nama vaksin telah ada di daftar.',
            'nama_vaksin.max' => 'Nama vaksin maksimal :max karakter.',
            'deskripsi.max' => 'Deskripsi maksimal :max karakter.',
            'not_regex' => 'Isian tidak boleh memuat karakter < atau >.',
        ];
    }

    /** @return array{nama_vaksin: string, deskripsi: ?string} */
    public function dataVaksin(): array
    {
        return ['nama_vaksin' => $this->validated('nama_vaksin'), 'deskripsi' => $this->validated('deskripsi')];
    }
}
