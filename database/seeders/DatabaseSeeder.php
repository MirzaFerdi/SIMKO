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
            $khusus = Kategori::create(['nama_kategori' => 'Khusus (Karyawan)']);

            // Buat Brand sesuai Gambar
            $djarum      = Brand::create(['nama_brand' => 'Djarum']);
            $gudangGaram = Brand::create(['nama_brand' => 'Gudang Garam']);
            $sampoerna   = Brand::create(['nama_brand' => 'Sampoerna']);
            $additional  = Brand::create(['nama_brand' => 'Additional Cigarettes']);

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

            $umumCash = MetodePembayaran::create(['kategori_id' => $umum->id, 'nama_metode' => 'Tunai / Cash']);
            $umumQris = MetodePembayaran::create(['kategori_id' => $umum->id, 'nama_metode' => 'QRIS']);
            $khususBon = MetodePembayaran::create(['kategori_id' => $khusus->id, 'nama_metode' => 'BON']);

            // ------------------------------------------------------------------
            // 4. DATA PRODUK & RIWAYAT STOK (Sistem Looping dari Array)
            // ------------------------------------------------------------------
            $stokAwalDefault = 50; // Set default stok semua rokok 50 bungkus

            // A. LIST HARGA DJARUM
            $listDjarum = [
                ['nama' => 'LA LIGHTS 16', 'umum' => 40000, 'karyawan' => 36000],
                ['nama' => 'D. SUPER MLD FRESH COL', 'umum' => 35000, 'karyawan' => 33000],
                ['nama' => 'D. SUPER MILD 20', 'umum' => 45000, 'karyawan' => 41000],
                ['nama' => 'LA BOLD 16', 'umum' => 35000, 'karyawan' => 33000],
                ['nama' => 'LA BOLD 20', 'umum' => 45000, 'karyawan' => 41000],
                ['nama' => 'LA ICE 16', 'umum' => 40000, 'karyawan' => 36000],
                ['nama' => 'LA ICE PURPLE BOOST 16', 'umum' => 40000, 'karyawan' => 36000],
                ['nama' => 'LA ICE MANGO BOOST 16', 'umum' => 40000, 'karyawan' => 36000],
                ['nama' => 'D. SUPER 12', 'umum' => 30000, 'karyawan' => 25000],
                ['nama' => 'D. 76 12', 'umum' => 20000, 'karyawan' => 18000],
                ['nama' => 'D. 76 ROYAL 12', 'umum' => 20000, 'karyawan' => 18000],
                ['nama' => 'D. 76 APEL 12', 'umum' => 20000, 'karyawan' => 17000],
                ['nama' => 'D. MANGGA ROYAL 12', 'umum' => 20000, 'karyawan' => 18000],
                ['nama' => 'D. SUPER ESPRESSO GOLD', 'umum' => 20000, 'karyawan' => 21000],
                ['nama' => 'D. SUPER KRETEK WRAPS', 'umum' => 20000, 'karyawan' => 20000],
                ['nama' => 'DJARUM D WRAPS 12', 'umum' => 20000, 'karyawan' => 17000],
                ['nama' => 'RAPTOR 12', 'umum' => 20000, 'karyawan' => 18000],
                ['nama' => 'GEO MILD 16', 'umum' => 30000, 'karyawan' => 26000],
                ['nama' => 'FERRO 16', 'umum' => 25000, 'karyawan' => 23000],
                ['nama' => 'GEO KRETEK 12', 'umum' => 15000, 'karyawan' => 13000],
                ['nama' => 'FILASTA KRETEK 16', 'umum' => 15000, 'karyawan' => 12000],
            ];

            // B. LIST HARGA GUDANG GARAM
            $listGudangGaram = [
                ['nama' => 'SURYA 12', 'umum' => 30000, 'karyawan' => 28000],
                ['nama' => 'GG SIGNATURE COKLAT', 'umum' => 30000, 'karyawan' => 28000],
                ['nama' => 'SURYA PRO MERAH', 'umum' => 38000, 'karyawan' => 35000],
                ['nama' => '16 SURYA PRO MILD', 'umum' => 38000, 'karyawan' => 35000],
                ['nama' => 'GG SIGNATURE BIRU 12', 'umum' => 30000, 'karyawan' => 25000],
                ['nama' => 'GG MILD 16', 'umum' => 38000, 'karyawan' => 35000],
                ['nama' => 'GG MILD SHIVER 16', 'umum' => 38000, 'karyawan' => 35000],
            ];

            // C. LIST HARGA SAMPOERNA
            $listSampoerna = [
                ['nama' => 'SAMPOERNA MILD 16', 'umum' => 40000, 'karyawan' => 37000],
                ['nama' => 'SAMPOERNA MILD MENTHOL 16', 'umum' => 40000, 'karyawan' => 37000],
                ['nama' => 'DJISAMSOE REFIL', 'umum' => 28000, 'karyawan' => 25000],
                ['nama' => 'DJISAMSOE POLOS', 'umum' => 28000, 'karyawan' => 25000],
                ['nama' => 'MARLBORO MERAH', 'umum' => 55000, 'karyawan' => 55000],
                ['nama' => 'MARLBORO PUTIH', 'umum' => 60000, 'karyawan' => 55000],
                ['nama' => 'MARLBORO ICE BURST', 'umum' => 60000, 'karyawan' => 55000],
                ['nama' => 'SAMPOERNA PRIMA KRETEK', 'umum' => 20000, 'karyawan' => 20000],
                ['nama' => 'SAMPOERNA KRETEK 12', 'umum' => 20000, 'karyawan' => 20000],
                ['nama' => 'MARLBORO BOLONG 20', 'umum' => 45000, 'karyawan' => 42000],
                ['nama' => 'MARLBORO BOLONG 12', 'umum' => 30000, 'karyawan' => 26000],
                ['nama' => 'MARLBORO KRETEK MERAH', 'umum' => 20000, 'karyawan' => 15000],
                ['nama' => 'TWIST 16 ROYAL', 'umum' => 30000, 'karyawan' => 28000],
                ['nama' => 'TWIST 16 PRIME MILD', 'umum' => 30000, 'karyawan' => 25000],
            ];

            // D. LIST HARGA ADDITIONAL CIGARETTES
            $listAdditional = [
                ['nama' => 'CAMEL PURPLE 12', 'umum' => 25000, 'karyawan' => 22000],
                ['nama' => 'DUNHIL HITAM 16', 'umum' => 35000, 'karyawan' => 33000],
                ['nama' => 'DUNHIL HITAM 12', 'umum' => 30000, 'karyawan' => 25000],
                ['nama' => 'GALANG BARU 12', 'umum' => 25000, 'karyawan' => 20000],
                ['nama' => 'SAMSOE MAGNUM BLACK', 'umum' => 30000, 'karyawan' => 28000],
            ];

            // Fungsi Helper untuk Insert Produk & Riwayat Sekaligus
            $insertProducts = function ($listData, $brandId) use ($stokAwalDefault) {
                foreach ($listData as $item) {
                    $prod = Produk::create([
                        'brand_id'     => $brandId,
                        'nama_produk'  => $item['nama'],
                        'harga_umum'   => $item['umum'],
                        'harga_khusus' => $item['karyawan'],
                        'stok'         => $stokAwalDefault
                    ]);

                    RiwayatStok::create([
                        'produk_id'  => $prod->id,
                        'stok_awal'  => 0,
                        'stok_masuk' => $stokAwalDefault,
                        'stok_akhir' => $stokAwalDefault,
                        'keterangan' => 'Stok Awal System',
                        'created_at' => now()->subDays(2)
                    ]);
                }
            };

            // Eksekusi Insert
            $insertProducts($listDjarum, $djarum->id);
            $insertProducts($listGudangGaram, $gudangGaram->id);
            $insertProducts($listSampoerna, $sampoerna->id);
            $insertProducts($listAdditional, $additional->id);

            $this->command->info('✅ Seluruh Produk & Riwayat Stok dari Excel berhasil diinput.');

            // ------------------------------------------------------------------
            // 5. DATA TRANSAKSI (Simulasi)
            // ------------------------------------------------------------------

            // Ambil sample produk dari DB untuk simulasi transaksi
            $sampleProdKhusus = Produk::where('nama_produk', 'DJISAMSOE POLOS')->first();
            $sampleProdUmum   = Produk::where('nama_produk', 'LA BOLD 20')->first();

            // --- Transaksi 1: Karyawan Beli DJISAMSOE POLOS ---
            $qty1 = 5;
            $harga1 = $sampleProdKhusus->harga_khusus; // Ambil harga karyawan
            $subtotal1 = $harga1 * $qty1;
            $uangBayar1 = 150000;

            $transaksi1 = Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => $khusus->id, // Karyawan
                'metode_pembayaran_id' => $umumCash->id,
                'nama_pelanggan'       => 'Budi Santoso (Karyawan)',
                'tanggal'              => now(),
                'total'                => $subtotal1,
                'bayar'                => $uangBayar1,
                'kembalian'            => $uangBayar1 - $subtotal1,
                'status'               => 'success'
            ]);

            TransaksiDetail::create([
                'transaksi_id' => $transaksi1->id,
                'produk_id'    => $sampleProdKhusus->id,
                'brand_id'     => $sampleProdKhusus->brand_id,
                'harga'        => $harga1,
                'qty'          => $qty1,
                'subtotal'     => $subtotal1
            ]);

            $sampleProdKhusus->decrement('stok', $qty1);

            // --- Transaksi 2: Pelanggan Umum Beli LA BOLD 20 via QRIS (Kemarin) ---
            $qty2 = 1;
            $harga2 = $sampleProdUmum->harga_umum; // Ambil harga umum
            $subtotal2 = $harga2 * $qty2;

            $transaksi2 = Transaksi::create([
                'user_id'              => $userKasir->id,
                'kategori_id'          => $umum->id, // Umum
                'metode_pembayaran_id' => $umumQris->id,
                'nama_pelanggan'       => 'Siti Aminah',
                'tanggal'              => now()->subDay(),
                'total'                => $subtotal2,
                'bayar'                => $subtotal2,
                'kembalian'            => 0,
                'status'               => 'success'
            ]);

            TransaksiDetail::create([
                'transaksi_id' => $transaksi2->id,
                'produk_id'    => $sampleProdUmum->id,
                'brand_id'     => $sampleProdUmum->brand_id,
                'harga'        => $harga2,
                'qty'          => $qty2,
                'subtotal'     => $subtotal2
            ]);

            $sampleProdUmum->decrement('stok', $qty2);

            DB::commit();
            $this->command->info('🚀 SEMUA SEEDER BERHASIL DIJALANKAN DENGAN DATA BARU!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Gagal seeding: ' . $e->getMessage());
        }
    }
}
