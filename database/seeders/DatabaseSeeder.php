<?php

namespace Database\Seeders;

// Pastikan import model ini sesuai dengan lokasi model Anda
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Role;
use App\Models\Transaksi;
use App\Models\TransaksiDetail; // Tambahkan Model Ini
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
            $umum   = Kategori::create(['nama_kategori' => 'Umum']);
            $khusus = Kategori::create(['nama_kategori' => 'Khusus']);

            // Buat Brand
            $sampoerna   = Brand::create(['nama_brand' => 'Sampoerna']);
            $gudangGaram = Brand::create(['nama_brand' => 'Gudang Garam']);
            $dJarum      = Brand::create(['nama_brand' => 'Djarum']);

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

            // Kita hubungkan ke kategori 'Umum' (sebagai contoh kategori keuangan)
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
            // 5. DATA TRANSAKSI (Simulasi Penjualan Header & Detail)
            // ------------------------------------------------------------------

            // --- Transaksi 1: Kasir menjual 5 Dji Sam Soe secara Tunai ---
            $qty1 = 5;
            $harga1 = $prod2->harga; // 15.000
            $subtotal1 = $harga1 * $qty1; // 75.000

            // Anggap user bayar 100.000 (disesuaikan agar kembalian positif)
            $uangBayar1 = 100000;

            // A. Buat Header Transaksi
            $transaksi1 = Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => $prod2->kategori_id, // Mengambil kategori pelanggan dari produk (Khusus)
                'metode_pembayaran_id' => $umumCash->id,
                'nama_pelanggan'       => 'Budi Santoso',
                'tanggal'              => now(),
                'total'                => $subtotal1,
                'bayar'                => $uangBayar1,
                'kembalian'            => $uangBayar1 - $subtotal1,
                'status'               => 'success'
            ]);

            // B. Buat Detail Transaksi
            TransaksiDetail::create([
                'transaksi_id' => $transaksi1->id,
                'produk_id'    => $prod2->id,
                'brand_id'     => $prod2->brand_id, // PENTING: Brand disimpan di detail untuk rekap
                'harga'        => $harga1,
                'qty'          => $qty1,
                'subtotal'     => $subtotal1
            ]);

            // C. Kurangi Stok
            $prod2->decrement('stok', $qty1);


            // --- Transaksi 2: Kasir menjual 1 LA Bold via QRIS (Kemarin) ---
            $qty2 = 1;
            $harga2 = $prod3->harga; // 40.000
            $subtotal2 = $harga2 * $qty2;

            // A. Buat Header Transaksi
            $transaksi2 = Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => $prod3->kategori_id, // Kategori Umum
                'metode_pembayaran_id' => $umumQris->id,
                'nama_pelanggan'       => 'Siti Aminah',
                'tanggal'              => now()->subDay(),
                'total'                => $subtotal2,
                'bayar'                => $subtotal2, // Uang pas QRIS
                'kembalian'            => 0,
                'status'               => 'success'
            ]);

            // B. Buat Detail Transaksi
            TransaksiDetail::create([
                'transaksi_id' => $transaksi2->id,
                'produk_id'    => $prod3->id,
                'brand_id'     => $prod3->brand_id,
                'harga'        => $harga2,
                'qty'          => $qty2,
                'subtotal'     => $subtotal2
            ]);

            // C. Kurangi Stok
            $prod3->decrement('stok', $qty2);

            DB::commit();
            $this->command->info('🚀 SEMUA SEEDER BERHASIL DIJALANKAN!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Gagal seeding: ' . $e->getMessage());
        }
    }
}
