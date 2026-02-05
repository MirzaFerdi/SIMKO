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
            $table->decimal('harga', 15, 2);
            $table->integer('stok');
            $table->timestamps();
        });

        // 7. TABEL TRANSAKSI
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();

            // Relasi Foreign Key (semua merujuk ke nama tabel baru)
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->foreignId('brand_id')->constrained('brand');
            $table->foreignId('produk_id')->constrained('produk');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran');

            $table->string('nama_pelanggan')->nullable();
            $table->date('tanggal');
            $table->decimal('harga', 15, 2);
            $table->integer('qty');
            $table->decimal('total', 15, 2);
            $table->decimal('kembalian', 15, 2)->default(0);
            $table->string('status')->default('selesai');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('produk');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('user');
        Schema::dropIfExists('kategori');
        Schema::dropIfExists('brand');
        Schema::dropIfExists('role');
    }
};
