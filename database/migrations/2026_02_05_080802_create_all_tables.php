<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('nama_role');
            $table->timestamps();
        });

        Schema::create('brand', function (Blueprint $table) {
            $table->id();
            $table->string('nama_brand');
            $table->timestamps();
        });

        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->timestamps();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('role')->onDelete('cascade');
            $table->string('nama');
            $table->string('username')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('cascade');
            $table->string('nama_metode');
            $table->timestamps();
        });

        Schema::create('kategori_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori_produk');
            $table->timestamps();
        });

        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_produk_id')->constrained('kategori_produk')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained('brand')->onDelete('cascade');
            $table->string('nama_produk');
            $table->decimal('harga_umum', 15, 2);
            $table->decimal('harga_khusus', 15, 2)->default(0);
            $table->integer('stok');
            $table->timestamps();
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user');
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran');
            $table->string('nama_pelanggan')->nullable();
            $table->dateTime('tanggal');
            $table->decimal('total', 15, 2);
            $table->decimal('bayar', 15, 2);
            $table->decimal('kembalian', 15, 2);
            $table->string('status')->default('success');
            $table->timestamps();
        });

        Schema::create('transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('produk');
            $table->foreignId('brand_id')->nullable()->constrained('brand');
            $table->decimal('harga', 15, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('riwayat_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->onDelete('cascade');
            $table->integer('stok_awal');
            $table->integer('stok_masuk');
            $table->integer('stok_keluar');
            $table->integer('stok_akhir');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('transaksi_detail');
        Schema::dropIfExists('kategori_produk');
        Schema::dropIfExists('produk');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('user');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('brand');
        Schema::dropIfExists('role');
        Schema::dropIfExists('riwayat_stok');
    }
};
