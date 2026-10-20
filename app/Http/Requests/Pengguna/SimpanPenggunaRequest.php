<?php

namespace App\Http\Requests\Pengguna;

use App\Models\Kader;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi formulir Tambah / Edit Akun Kader (Figma 141:2478, Gambar 2.68; Activity Diagram 2.10 & 2.12).
 *
 * - Nama kader wajib, maks 100 karakter (aturan sama dengan nama warga).
 * - Username 3–50 karakter: huruf kecil, angka, titik, garis bawah; unik tanpa membedakan huruf besar/kecil.
 * - Kata sandi 8–64 karakter tanpa paksaan komposisi (OWASP), tidak boleh sama dengan username,
 *   bukan sandi umum, dan bukan sandi yang pernah bocor. Pada Edit, kata sandi boleh dikosongkan (tidak diubah).
 */
class SimpanPenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dibatasi middleware auth
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_kader' => ($nama = $this->input('nama_kader')) === null ? null : trim(preg_replace('/\s+/u', ' ', (string) $nama)),
            'username' => ($user = $this->input('username')) === null ? null : Str::lower(trim((string) $user)),
        ]);
    }

    public function rules(): array
    {
        /** @var Kader|null $kader */
        $kader = $this->route('pengguna');
        $modeEdit = $kader !== null;

        return [
            'nama_kader' => ['required', 'string', 'max:100', "regex:/^[\\pL\\s.,'\\-]+$/u"],
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._]+$/',
                Rule::unique('kader', 'username')->ignore($kader?->getKey(), 'id_kader'),
            ],
            // Edit: kosong = kata sandi tidak diubah (Activity Diagram 2.12 "apakah password diubah").
            // bail: cukup satu pesan kata sandi yang ditampilkan.
            'password' => ['bail', $modeEdit ? 'nullable' : 'required', 'string', 'confirmed', $this->aturanSandi(), $this->bukanSandiUmum()],
            'password_confirmation' => ['nullable', 'string'],
            'versi' => [$modeEdit ? 'required' : 'prohibited', 'nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        $min = (int) config('posyandu.pengguna.sandi_min');
        $maks = (int) config('posyandu.pengguna.sandi_maks');

        return [
            'required' => ':attribute wajib diisi.',
            'nama_kader.max' => 'Nama kader maksimal :max karakter.',
            'nama_kader.regex' => 'Nama kader hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung.',
            'username.min' => 'Username minimal :min karakter.',
            'username.max' => 'Username maksimal :max karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik (.), dan garis bawah (_), tanpa spasi.',
            'username.unique' => 'Username ini sudah dipakai akun lain.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama dengan kata sandi.',
            'password.min' => "Kata sandi minimal {$min} karakter.",
            'password.max' => "Kata sandi maksimal {$maks} karakter.",
            'password.uncompromised' => 'Kata sandi ini pernah bocor di internet sehingga mudah ditebak. Gunakan kata sandi lain.',
            'versi.required' => 'Formulir tidak lengkap. Muat ulang halaman lalu coba lagi.',
            'versi.prohibited' => 'Formulir tidak valid.',
            'versi.date_format' => 'Formulir tidak lengkap. Muat ulang halaman lalu coba lagi.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_kader' => 'Nama kader',
            'username' => 'Username',
            'password' => 'Kata sandi',
        ];
    }

    /** @return array{nama_kader: string, username: string} */
    public function dataAkun(): array
    {
        return $this->safe()->only(['nama_kader', 'username']);
    }

    /** Kata sandi baru, atau null bila tidak diubah (Edit). */
    public function sandiBaru(): ?string
    {
        $sandi = $this->validated('password');

        return $sandi === null || $sandi === '' ? null : (string) $sandi;
    }

    private function aturanSandi(): Password
    {
        $aturan = Password::min((int) config('posyandu.pengguna.sandi_min'))
            ->max((int) config('posyandu.pengguna.sandi_maks'));

        return config('posyandu.pengguna.cek_sandi_bocor') ? $aturan->uncompromised() : $aturan;
    }

    /** Menolak sandi yang sama dengan username atau termasuk daftar sandi umum. */
    private function bukanSandiUmum(): Closure
    {
        return function (string $atribut, mixed $nilai, Closure $gagal) {
            $sandi = Str::lower((string) $nilai);
            $username = Str::lower((string) $this->input('username'));

            if ($username !== '' && $sandi === $username) {
                $gagal('Kata sandi tidak boleh sama dengan username.');

                return;
            }
            if (in_array($sandi, array_map('strtolower', config('posyandu.pengguna.sandi_umum', [])), true)) {
                $gagal('Kata sandi ini terlalu umum dan mudah ditebak. Gunakan kata sandi lain.');
            }
        };
    }
}
