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
            $roleAdmin = Role::create(['nama_role' => 'Administrator']);
            $roleKasir = Role::create(['nama_role' => 'Kasir']);

            $this->command->info('✅ Role berhasil dibuat.');

            // Buat Kategori
            $catElektronik = Kategori::create(['nama_kategori' => 'Elektronik']);
            $catFnb        = Kategori::create(['nama_kategori' => 'Makanan & Minuman']);
            $catFashion    = Kategori::create(['nama_kategori' => 'Fashion']);
            $catKeuangan   = Kategori::create(['nama_kategori' => 'Keuangan Digital']); // Kategori khusus metode bayar

            // Buat Brand
            $brandSamsung  = Brand::create(['nama_brand' => 'Samsung']);
            $brandIndofood = Brand::create(['nama_brand' => 'Indofood']);
            $brandNike     = Brand::create(['nama_brand' => 'Nike']);
            $brandApple    = Brand::create(['nama_brand' => 'Apple']); // Brand tanpa produk (untuk tes)

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
            $payCash = MetodePembayaran::create([
                'kategori_id' => $catKeuangan->id,
                'nama_metode' => 'Tunai / Cash'
            ]);

            $payQris = MetodePembayaran::create([
                'kategori_id' => $catKeuangan->id,
                'nama_metode' => 'QRIS'
            ]);

            $payTransfer = MetodePembayaran::create([
                'kategori_id' => $catKeuangan->id,
                'nama_metode' => 'Transfer Bank'
            ]);

            // ------------------------------------------------------------------
            // 4. DATA PRODUK
            // ------------------------------------------------------------------

            $prod1 = Produk::create([
                'brand_id'    => $brandSamsung->id,
                'kategori_id' => $catElektronik->id,
                'nama_produk' => 'Samsung Galaxy S24',
                'harga'       => 15000000,
                'stok'        => 10 // Stok awal
            ]);

            $prod2 = Produk::create([
                'brand_id'    => $brandIndofood->id,
                'kategori_id' => $catFnb->id,
                'nama_produk' => 'Indomie Goreng Jumbo',
                'harga'       => 3500,
                'stok'        => 100 // Stok awal
            ]);

            $prod3 = Produk::create([
                'brand_id'    => $brandNike->id,
                'kategori_id' => $catFashion->id,
                'nama_produk' => 'Nike Air Jordan',
                'harga'       => 2500000,
                'stok'        => 5 // Stok awal
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
                'metode_pembayaran_id' => $payCash->id,         // Bayar pakai apa
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
                'metode_pembayaran_id' => $payQris->id,
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
