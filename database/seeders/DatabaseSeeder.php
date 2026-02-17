<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Role;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\User;
use App\Models\RiwayatStok;
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
        DB::beginTransaction();

        try {
            // ------------------------------------------------------------------
            // 1. DATA MASTER UTAMA (Role, Kategori, Brand)
            // ------------------------------------------------------------------

            $roleAdmin = Role::create(['nama_role' => 'admin']);
            $roleKasir = Role::create(['nama_role' => 'Kasir']);

            $this->command->info('✅ Role berhasil dibuat.');

            $umum   = Kategori::create(['nama_kategori' => 'Umum']);
            $khusus = Kategori::create(['nama_kategori' => 'Khusus']);

            $sampoerna   = Brand::create(['nama_brand' => 'Sampoerna']);
            $gudangGaram = Brand::create(['nama_brand' => 'Gudang Garam']);
            $dJarum      = Brand::create(['nama_brand' => 'Djarum']);

            $this->command->info('✅ Data Master (Kategori & Brand) berhasil dibuat.');

            // ------------------------------------------------------------------
            // 2. DATA USER (Login)
            // ------------------------------------------------------------------

            User::create([
                'role_id'  => $roleAdmin->id,
                'username' => 'admin',
                'nama'     => 'Admin System',
                'password' => Hash::make('admin123'),
            ]);

            $userKasir = User::create([
                'role_id'  => $roleKasir->id,
                'username' => 'kasir',
                'nama'     => 'Kasir Utama',
                'password' => Hash::make('kasir123'),
            ]);

            $this->command->info('✅ User Admin & Kasir berhasil dibuat.');

            // ------------------------------------------------------------------
            // 3. DATA PENDUKUNG (Metode Pembayaran)
            // ------------------------------------------------------------------

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
            // 4. DATA PRODUK & RIWAYAT STOK AWAL
            // ------------------------------------------------------------------

            // --- PRODUK 1 ---
            $prod1 = Produk::create([
                'brand_id'     => $gudangGaram->id,
                'nama_produk'  => 'Gudang Garam International',
                'harga_umum'   => 28000,
                'harga_khusus' => 27000,
                'stok'         => 10
            ]);

            // Catat Riwayat Stok Awal
            RiwayatStok::create([
                'produk_id'  => $prod1->id,
                'stok_awal'  => 0,
                'stok_masuk' => 10,
                'stok_akhir' => 10,
                'keterangan' => 'Stok Awal System',
                'created_at' => now()->subDays(2) // Seolah-olah stok masuk 2 hari lalu
            ]);


            // --- PRODUK 2 ---
            $prod2 = Produk::create([
                'brand_id'     => $sampoerna->id,
                'nama_produk'  => 'Dji Sam Soe',
                'harga_umum'   => 15000,
                'harga_khusus' => 14000,
                'stok'         => 25
            ]);

            RiwayatStok::create([
                'produk_id'  => $prod2->id,
                'stok_awal'  => 0,
                'stok_masuk' => 25,
                'stok_akhir' => 25,
                'keterangan' => 'Stok Awal System',
                'created_at' => now()->subDays(2)
            ]);


            // --- PRODUK 3 ---
            $prod3 = Produk::create([
                'brand_id'     => $dJarum->id,
                'nama_produk'  => 'LA Bold',
                'harga_umum'   => 40000,
                'harga_khusus' => 38000,
                'stok'         => 16
            ]);

            RiwayatStok::create([
                'produk_id'  => $prod3->id,
                'stok_awal'  => 0,
                'stok_masuk' => 16,
                'stok_akhir' => 16,
                'keterangan' => 'Stok Awal System',
                'created_at' => now()->subDays(2)
            ]);

            $this->command->info('✅ Produk & Riwayat Stok berhasil dibuat.');

            // ------------------------------------------------------------------
            // 5. DATA TRANSAKSI (Simulasi)
            // ------------------------------------------------------------------

            // --- Transaksi 1: Kasir menjual 5 Dji Sam Soe ---
            $qty1 = 5;
            $harga1 = $prod2->harga_khusus; // Ambil harga khusus
            $subtotal1 = $harga1 * $qty1;
            $uangBayar1 = 100000;

            // A. Header
            $transaksi1 = Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => 2,
                'metode_pembayaran_id' => $umumCash->id,
                'nama_pelanggan'       => 'Budi Santoso',
                'tanggal'              => now(),
                'total'                => $subtotal1,
                'bayar'                => $uangBayar1,
                'kembalian'            => $uangBayar1 - $subtotal1,
                'status'               => 'success'
            ]);

            // B. Detail
            TransaksiDetail::create([
                'transaksi_id' => $transaksi1->id,
                'produk_id'    => $prod2->id,
                'brand_id'     => $prod2->brand_id,
                'harga'        => $harga1,
                'qty'          => $qty1,
                'subtotal'     => $subtotal1
            ]);

            // C. Kurangi Stok Master (Simulasi barang keluar)
            $prod2->decrement('stok', $qty1);


            // --- Transaksi 2: Kasir menjual 1 LA Bold (Kemarin) ---
            $qty2 = 1;
            $harga2 = $prod3->harga_umum; // Ambil harga umum
            $subtotal2 = $harga2 * $qty2;

            // A. Header
            $transaksi2 = Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => 1,
                'metode_pembayaran_id' => $umumQris->id,
                'nama_pelanggan'       => 'Siti Aminah',
                'tanggal'              => now()->subDay(),
                'total'                => $subtotal2,
                'bayar'                => $subtotal2,
                'kembalian'            => 0,
                'status'               => 'success'
            ]);

            // B. Detail
            TransaksiDetail::create([
                'transaksi_id' => $transaksi2->id,
                'produk_id'    => $prod3->id,
                'brand_id'     => $prod3->brand_id,
                'harga'        => $harga2,
                'qty'          => $qty2,
                'subtotal'     => $subtotal2
            ]);

            // C. Kurangi Stok Master
            $prod3->decrement('stok', $qty2);

            DB::commit();
            $this->command->info('🚀 SEMUA SEEDER BERHASIL DIJALANKAN!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Gagal seeding: ' . $e->getMessage());
        }
    }
}
