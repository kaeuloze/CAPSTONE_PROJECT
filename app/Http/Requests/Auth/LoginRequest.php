<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi & proses login kader.
 * - Pesan gagal dibuat sama untuk username tidak ada / sandi salah (OWASP).
 * - Dikunci sementara setelah beberapa kali salah per username + IP (mitigasi brute force).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal :max karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.max' => 'Kata sandi maksimal :max karakter.',
        ];
    }

    /**
     * Mencoba login. Gagal → menambah hitungan percobaan & melempar pesan generik.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->pastikanTidakTerkunci();

        if (! Auth::attempt($this->only('username', 'password'))) {
            RateLimiter::hit($this->kunciPembatas(), (int) config('posyandu.login.durasi_kunci_detik'));

            throw ValidationException::withMessages([
                'username' => 'Username atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($this->kunciPembatas());
    }

    /**
     * @throws ValidationException
     */
    private function pastikanTidakTerkunci(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciPembatas(), (int) config('posyandu.login.maks_percobaan'))) {
            return;
        }

        event(new Lockout($this));

        $detik = RateLimiter::availableIn($this->kunciPembatas());

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$detik} detik.",
        ]);
    }

    /** Kunci hitungan percobaan: username (huruf kecil) + alamat IP. */
    private function kunciPembatas(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
