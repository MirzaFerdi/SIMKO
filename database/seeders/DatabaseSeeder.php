<?php

namespace Database\Seeders;

// Pastikan import model ini sesuai dengan lokasi model Anda
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Role;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Menggunakan Transaction agar jika ada error di tengah, semua data dibatalkan (bersih)
        DB::beginTransaction();

        try {
            // ------------------------------------------------------------------
            // 1. DATA MASTER UTAMA (Role, Kategori, Brand)
            // ------------------------------------------------------------------

            // Buat Role
            $roleAdmin = Role::create(['nama_role' => 'admin']);
            $roleKasir = Role::create(['nama_role' => 'Kasir']);

            $this->command->info('✅ Role berhasil dibuat.');

            // Buat Kategori
            $umum = Kategori::create(['nama_kategori' => 'Umum']);
            $khusus        = Kategori::create(['nama_kategori' => 'Khusus']);

            // Buat Brand
            $sampoerna  = Brand::create(['nama_brand' => 'Sampoerna']);
            $gudangGaram = Brand::create(['nama_brand' => 'Gudang Garam']);
            $dJarum     = Brand::create(['nama_brand' => 'Djarum']);

            $this->command->info('✅ Data Master (Kategori & Brand) berhasil dibuat.');

            // ------------------------------------------------------------------
            // 2. DATA USER (Login)
            // ------------------------------------------------------------------

            // Admin (Username: admin, Pass: admin123)
            User::create([
                'role_id'  => $roleAdmin->id,
                'username' => 'admin',
                'password' => Hash::make('admin123'),
            ]);

            // Kasir (Username: kasir, Pass: kasir123)
            $userKasir = User::create([
                'role_id'  => $roleKasir->id,
                'username' => 'kasir',
                'password' => Hash::make('kasir123'),
            ]);

            $this->command->info('✅ User Admin & Kasir berhasil dibuat.');

            // ------------------------------------------------------------------
            // 3. DATA PENDUKUNG (Metode Pembayaran)
            // ------------------------------------------------------------------

            // Kita hubungkan ke kategori 'Keuangan Digital'
            $umumCash = MetodePembayaran::create([
                'kategori_id' => $umum->id,
                'nama_metode' => 'Tunai / Cash'
            ]);

            $umumQris = MetodePembayaran::create([
                'kategori_id' => $umum->id,
                'nama_metode' => 'QRIS'
            ]);

            $khususBon = MetodePembayaran::create([
                'kategori_id' => $khusus->id,
                'nama_metode' => 'BON'
            ]);

            // ------------------------------------------------------------------
            // 4. DATA PRODUK
            // ------------------------------------------------------------------

            $prod1 = Produk::create([
                'brand_id'    => $gudangGaram->id,
                'kategori_id' => $umum->id,
                'nama_produk' => 'Gudang Garam International',
                'harga'       => 28000,
                'stok'        => 10
            ]);

            $prod2 = Produk::create([
                'brand_id'    => $sampoerna->id,
                'kategori_id' => $khusus->id,
                'nama_produk' => 'Dji Sam Soe',
                'harga'       => 15000,
                'stok'        => 25 // Stok awal
            ]);

            $prod3 = Produk::create([
                'brand_id'    => $dJarum->id,
                'kategori_id' => $umum->id,
                'nama_produk' => 'LA Bold',
                'harga'       => 40000,
                'stok'        => 16 // Stok awal
            ]);

            $this->command->info('✅ Produk berhasil dibuat.');

            // ------------------------------------------------------------------
            // 5. DATA TRANSAKSI (Simulasi Penjualan)
            // ------------------------------------------------------------------

            // --- Transaksi 1: Kasir menjual 5 Indomie secara Tunai ---
            $qty1 = 5;
            $total1 = $prod2->harga * $qty1; // 3500 * 5 = 17500

            Transaksi::create([
                'user_id'              => $userKasir->id,       // Siapa kasirnya
                'kategori_id'          => $prod2->kategori_id,  // Kategori produk saat itu
                'brand_id'             => $prod2->brand_id,     // Brand produk saat itu
                'produk_id'            => $prod2->id,           // Produk apa
                'metode_pembayaran_id' => $umumCash->id,         // Bayar pakai apa
                'nama_pelanggan'       => 'Budi Santoso',
                'tanggal'              => now(),
                'harga'                => $prod2->harga,        // Harga saat transaksi terjadi
                'qty'                  => $qty1,
                'total'                => $total1,
                'kembalian'            => 20000 - $total1,      // Bayar 20.000, hitung kembalian
                'status'               => 'selesai'
            ]);

            // Jangan lupa kurangi stok produk karena ini simulasi transaksi sukses
            $prod2->decrement('stok', $qty1);


            // --- Transaksi 2: Kasir menjual 1 Sepatu Nike via QRIS (Kemarin) ---
            $qty2 = 1;
            $total2 = $prod3->harga * $qty2;

            Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => $prod3->kategori_id,
                'brand_id'             => $prod3->brand_id,
                'produk_id'            => $prod3->id,
                'metode_pembayaran_id' => $umumQris->id,
                'nama_pelanggan'       => 'Siti Aminah',
                'tanggal'              => now()->subDay(),      // Transaksi dibuat kemarin
                'harga'                => $prod3->harga,
                'qty'                  => $qty2,
                'total'                => $total2,
                'kembalian'            => 0,                    // Uang pas kalau QRIS
                'status'               => 'selesai'
            ]);

            $prod3->decrement('stok', $qty2);

            DB::commit();
            $this->command->info('🚀 SEMUA SEEDER BERHASIL DIJALANKAN!');

        } catch (\Exception $e) {
            DB::rollBack();
            // Tampilkan pesan error detail jika gagal
            $this->command->error('❌ Gagal seeding: ' . $e->getMessage());
        }
    }
}
