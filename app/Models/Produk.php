<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // fungsinya untuk memungkinkan model untuk menggunakan Factory
// factory adalah fitur Laravel untuk membuat data palsu (dummy data) dalam jumlah banyak secara otomatis untuk keperluan testing

use Illuminate\Database\Eloquent\Model; // fungsinya untuk memanggil class induk (base class) yang memberikan fitur database pada model.
use Illuminate\Database\Eloquent\Relations\BelongsTo; // representasi relasi Many-to-One (Produk terhubung ke satu Parent)
use Illuminate\Database\Eloquent\SoftDeletes; // fungsinya untuk file yg dihapus tidak hilang permanen dari database, tapi sebenarnya masih ada di database sebagai arsip
use Illuminate\Support\Str; // fungsinya untuk memanggil kumpulan alat manipulasi string (teks)
use Illuminate\Database\Eloquent\Relations\HasMany; // fungsinya untuk memanggil relasi One-to-Many (contohnya produk memiliki banyak photos)

class Produk extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'thumbnail',
        'about',
        'price',
        'stock',
        'is_populer',
        'category_id',
        'brand_id',
    ];

    public function setNameAttribute($value):void
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function photos()
    {
        return $this->hasMany(ProdukPhoto::class, 'produk_id');
    }

    public function sizes()
    {
        return $this->hasMany(ProdukSize::class);
    }
}
