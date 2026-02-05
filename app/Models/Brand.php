<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brand';
    protected $guarded = ['id'];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'brand_id');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'brand_id');
    }
}
