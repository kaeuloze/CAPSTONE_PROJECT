<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Entitas KADER: akun pengguna sistem (dipakai untuk login).
 *
 * @property int $id_kader
 * @property string $username
 * @property string $nama_kader
 */
class Kader extends Authenticatable
{
    protected $table = 'kader';

    protected $primaryKey = 'id_kader';

    /** Kolom password mengikuti ERD (password_hash), bukan "password" bawaan Laravel. */
    protected $authPasswordName = 'password_hash';

    /** Fitur "ingat saya" tidak ada di proposal, sehingga kolom remember_token tidak dipakai. */
    protected $rememberTokenName = '';

    protected $fillable = ['username', 'password_hash', 'nama_kader'];

    /** Hash password tidak pernah ikut terkirim ke tampilan/JSON. */
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed', // otomatis di-hash bila diisi teks biasa
        ];
    }
}
