<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entitas VAKSIN: master jenis vaksin (dicatat per dosis).
 */
class Vaksin extends Model
{
    protected $table = 'vaksin';

    protected $primaryKey = 'id_vaksin';

    protected $fillable = ['nama_vaksin', 'deskripsi'];

    public function vaksinasi(): HasMany
    {
        return $this->hasMany(Vaksinasi::class, 'id_vaksin', 'id_vaksin');
    }
}
