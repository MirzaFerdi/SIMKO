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
        // 1. TABEL ROLE
        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('nama_role');
            $table->timestamps();
        });

        // 2. TABEL BRAND
        Schema::create('brand', function (Blueprint $table) {
            $table->id();
            $table->string('nama_brand');
            $table->timestamps();
        });

        // 3. TABEL KATEGORI
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->timestamps();
        });

        // 4. TABEL USER
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            // Perhatikan constrained merujuk ke tabel 'role'
            $table->foreignId('role_id')->constrained('role')->onDelete('cascade');
            $table->string('nama');
            $table->string('username')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // 5. TABEL METODE PEMBAYARAN
        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('cascade');
            $table->string('nama_metode');
            $table->timestamps();
        });

        // 6. TABEL PRODUK
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brand')->onDelete('cascade');
            $table->foreignId('kategori_id')->constrained('kategori')->onDelete('cascade');
            $table->string('nama_produk');
            $table->decimal('harga_umum', 15, 2);
            $table->decimal('harga_khusus', 15, 2);
            $table->integer('stok');
            $table->timestamps();
        });

        // 7. TABEL TRANSAKSI
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user'); // Kasir

            // Kategori Pelanggan (Umum/Khusus) disimpan di Header
            // Karena biasanya 1 struk berlaku untuk 1 jenis pelanggan
            $table->foreignId('kategori_id')->constrained('kategori');

            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran');

            $table->string('nama_pelanggan')->nullable();
            $table->dateTime('tanggal');
            $table->decimal('total', 15, 2);    // Total Belanja
            $table->decimal('bayar', 15, 2);    // Uang Diterima
            $table->decimal('kembalian', 15, 2); // Kembalian
            $table->string('status')->default('success');
            $table->timestamps();
        });

        Schema::create('transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->onDelete('cascade');

            $table->foreignId('produk_id')->constrained('produk');

            // KOLOM BRAND DIPERTAHANKAN DI SINI
            // Tujuannya: Agar saat rekap "Berapa penjualan Brand Samsung?",
            // kita tinggal query tabel ini tanpa join berat ke tabel produk.
            $table->foreignId('brand_id')->constrained('brand');

            $table->decimal('harga', 15, 2); // Harga Final (sesuai kategori pelanggan)
            $table->integer('qty');
            $table->decimal('subtotal', 15, 2); // harga * qty
            $table->timestamps();
        });

        Schema::create('riwayat_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->onDelete('cascade');

            $table->integer('stok_awal');
            $table->integer('stok_masuk');
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
        Schema::dropIfExists('produk');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('user');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('brand');
        Schema::dropIfExists('role');
        Schema::dropIfExists('riwayat_stok');
    }
};
