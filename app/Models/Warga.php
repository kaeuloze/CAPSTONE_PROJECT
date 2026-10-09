<?php

namespace App\Models;

use App\Enums\KategoriUsia;
use App\Enums\StatusKondisi;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitas WARGA: data induk warga sasaran posyandu. Primary key = NIK.
 */
class Warga extends Model
{
    protected $table = 'warga';

    protected $primaryKey = 'nik';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nik', 'nama_warga', 'jenis_kelamin', 'tanggal_lahir', 'no_hp',
        'alamat', 'status_kondisi', 'kategori_usia',
    ];

    protected function casts(): array
    {
        return [
            'jenis_kelamin' => 'boolean', // true = Laki-laki, false = Perempuan
            'tanggal_lahir' => 'date',
            'status_kondisi' => StatusKondisi::class,
            'kategori_usia' => KategoriUsia::class,
        ];
    }

    /** Teks jenis kelamin untuk ditampilkan: "Laki-laki" / "Perempuan". */
    protected function jenisKelaminLabel(): Attribute
    {
        return Attribute::get(fn () => $this->jenis_kelamin ? 'Laki-laki' : 'Perempuan');
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'nik', 'nik');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(Pemeriksaan::class, 'nik', 'nik');
    }

    public function vaksinasi(): HasMany
    {
        return $this->hasMany(Vaksinasi::class, 'nik', 'nik');
    }

    public function asiEksklusif(): HasMany
    {
        return $this->hasMany(AsiEksklusif::class, 'nik', 'nik');
    }

    public function riwayatPtm(): HasMany
    {
        return $this->hasMany(RiwayatPtm::class, 'nik', 'nik');
    }

    /** Daftar penyakit PTM warga (melalui tabel riwayat_ptm). */
    public function penyakit(): BelongsToMany
    {
        return $this->belongsToMany(Penyakit::class, 'riwayat_ptm', 'nik', 'id_penyakit', 'nik', 'id_penyakit')
            ->withPivot(['id_riwayat', 'sumber_riwayat', 'tanggal_dicatat'])
            ->withTimestamps();
    }
}
