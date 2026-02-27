<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';
    protected $guarded = ['id'];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'produk_id');
    }

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class, 'produk_id');
    }
}
