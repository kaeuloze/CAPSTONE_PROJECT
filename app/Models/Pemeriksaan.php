<?php

namespace App\Models;

use App\Enums\JenisPemeriksaan;
use App\Enums\KategoriPemeriksaan;
use App\Enums\StatusPemeriksaan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Entitas PEMERIKSAAN: satu kunjungan pemeriksaan warga pada satu kegiatan.
 * Isi pemeriksaan tersimpan di tabel detail (relasi hasOne di bawah).
 */
class Pemeriksaan extends Model
{
    protected $table = 'pemeriksaan';

    protected $primaryKey = 'id_pemeriksaan';

    protected $fillable = [
        'nik', 'id_kegiatan', 'kategori_usia_saat_periksa', 'jenis_pemeriksaan',
        'tanggal_pemeriksaan', 'status_pemeriksaan',
    ];

    protected function casts(): array
    {
        return [
            'kategori_usia_saat_periksa' => KategoriPemeriksaan::class,
            'tanggal_pemeriksaan' => 'date',
            'status_pemeriksaan' => StatusPemeriksaan::class,
        ];
    }

    /**
     * Kolom SET MySQL "rutin,enam_bulanan" <-> array Enum JenisPemeriksaan.
     */
    protected function jenisPemeriksaan(): Attribute
    {
        return Attribute::make(
            get: fn (?string $nilai) => blank($nilai)
                ? []
                : array_map(fn (string $j) => JenisPemeriksaan::from($j), explode(',', $nilai)),
            set: fn ($nilai) => blank($nilai)
                ? null
                : implode(',', array_map(
                    fn ($j) => $j instanceof JenisPemeriksaan ? $j->value : JenisPemeriksaan::from($j)->value,
                    (array) $nilai
                )),
        );
    }

    /** Pemeriksaan yang baru disimpan (belum Submit) — tetap tampil di menu Pemeriksaan. */
    public function scopeDraft(Builder $query): void
    {
        $query->where('status_pemeriksaan', StatusPemeriksaan::Draft->value);
    }

    /** Pemeriksaan yang sudah Submit — tampil di Riwayat Pemeriksaan. */
    public function scopeSelesai(Builder $query): void
    {
        $query->where('status_pemeriksaan', StatusPemeriksaan::Selesai->value);
    }

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'nik', 'nik');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }

    public function pengukuranDasar(): HasOne
    {
        return $this->hasOne(PengukuranDasar::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function skriningTbc(): HasOne
    {
        return $this->hasOne(SkriningTbc::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function skriningSensorik(): HasOne
    {
        return $this->hasOne(SkriningSensorik::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function skriningPuma(): HasOne
    {
        return $this->hasOne(SkriningPuma::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function skriningAks(): HasOne
    {
        return $this->hasOne(SkriningAks::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function skriningSkilas(): HasOne
    {
        return $this->hasOne(SkriningSkilas::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function pemeriksaanBumil(): HasOne
    {
        return $this->hasOne(PemeriksaanBumil::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }

    public function penyuluhanRujukan(): HasOne
    {
        return $this->hasOne(PenyuluhanRujukan::class, 'id_pemeriksaan', 'id_pemeriksaan');
    }
}
