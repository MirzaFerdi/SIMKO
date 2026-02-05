<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategori';
    protected $guarded = ['id'];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    public function metodePembayaran()
    {
        return $this->hasMany(MetodePembayaran::class, 'kategori_id');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'kategori_id');
    }
}
