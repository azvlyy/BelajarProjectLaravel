<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdukSize extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'size',
        'produk_id',
    ];

    protected static function getSize($produkId = null) {
        // cek data
        if (!$produkId) {
            return[];
        }

        // ambil data size dari tabel produk_size yg punya produk_id tersebut
        return self::where('produk_id', (int) $produkId)
            ->pluck('size', 'id')
            ->toArray();
    }
}
