<?php

namespace App\Models;

use App\Enums\KategoriUsia;
use App\Enums\StatusKondisi;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Kategori usia selalu dihitung ulang saat warga baru dibuat atau tanggal lahirnya berubah
     * (Activity Diagram 2.30 & 2.31). Perubahan karena bertambah umur ditangani perintah harian
     * `warga:perbarui-kategori`.
     */
    protected static function booted(): void
    {
        static::saving(function (Warga $warga) {
            if (! $warga->exists || $warga->isDirty('tanggal_lahir') || $warga->kategori_usia === null) {
                $warga->kategori_usia = KategoriUsia::dariTanggalLahir($warga->tanggal_lahir);
            }
        });
    }

    /**
     * Kategori usia per hari ini, dihitung langsung dari tanggal lahir (untuk badge).
     * Selalu tepat walaupun kolom kategori_usia belum diperbarui perintah harian.
     */
    protected function kategoriUsiaTerkini(): Attribute
    {
        return Attribute::get(fn () => KategoriUsia::dariTanggalLahir($this->tanggal_lahir));
    }

    /** Hanya warga perempuan non-balita yang boleh ditandai hamil/nifas/menyusui. */
    public function bolehDitandaiKondisiKhusus(): bool
    {
        return $this->jenis_kelamin === false && $this->kategori_usia_terkini !== KategoriUsia::Balita;
    }

    /** True jika warga sudah punya data kegiatan (presensi, pemeriksaan, vaksinasi, ASI). */
    public function sudahDipakai(): bool
    {
        return $this->kehadiran()->exists()
            || $this->pemeriksaan()->exists()
            || $this->vaksinasi()->exists()
            || $this->asiEksklusif()->exists();
    }

    /** Pencarian cepat berdasarkan nama (mengandung kata) atau NIK (diawali angka) — SKF-04. */
    #[Scope]
    protected function cari(Builder $query, ?string $kata): void
    {
        $kata = trim((string) $kata);
        if ($kata === '') {
            return;
        }

        $aman = addcslashes($kata, '\\%_'); // karakter % dan _ dicari apa adanya, bukan wildcard
        $query->where(function (Builder $q) use ($aman) {
            $q->where('nama_warga', 'like', "%{$aman}%")
                ->orWhere('nik', 'like', "{$aman}%");
        });
    }

    /** Filter kategori usia berdasarkan tanggal lahir (selalu tepat per hari ini). */
    #[Scope]
    protected function kategori(Builder $query, ?KategoriUsia $kategori): void
    {
        if ($kategori === null) {
            return;
        }

        $rentang = $kategori->rentangTanggalLahir();
        $query->where('tanggal_lahir', '<=', $rentang['sampai']);
        if ($rentang['setelah'] !== null) {
            $query->where('tanggal_lahir', '>', $rentang['setelah']);
        }
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
