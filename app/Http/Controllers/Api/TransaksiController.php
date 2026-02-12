<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\MetodePembayaran;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    public function index()
    {
        // Menampilkan semua data transaksi beserta relasinya
        $data = Transaksi::with(['user', 'detail.produk', 'detail.brand', 'kategori', 'metodePembayaran'])
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        // 1. VALIDASI INPUT
        $validator = Validator::make($request->all(), [
            'user_id'              => 'required|exists:user,id',
            'kategori_id'          => 'required|exists:kategori,id',
            'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
            'items'                => 'required|array',
            'items.*.produk_id'    => 'required|exists:produk,id',
            'items.*.qty'          => 'required|integer|min:1',
            'bayar'                => 'required|numeric',
            'nama_pelanggan'       => 'nullable|string', // Hapus closure dari sini
        ]);

        // 2. VALIDASI LOGIC KHUSUS (Dipindah kesini agar PASTI JALAN)
        $validator->after(function ($validator) use ($request) {
            $kategori = Kategori::find($request->kategori_id);
            $metode   = MetodePembayaran::find($request->metode_pembayaran_id);

            // Pastikan data master ditemukan dulu
            if ($kategori && $metode) {
                $isKhusus = stripos($kategori->nama_kategori, 'Khusus') !== false;
                $isBon    = stripos($metode->nama_metode, 'BON') !== false;

                // Ambil nilai nama pelanggan
                $namaPelanggan = $request->nama_pelanggan;

                // Cek Logic: Khusus + BON + Nama Kosong/Null
                if ($isKhusus && $isBon && empty($namaPelanggan)) {
                    // Tambahkan error manual ke field 'nama_pelanggan'
                    $validator->errors()->add(
                        'nama_pelanggan',
                        'Harap Masukkan Nama Pelanggan Jika Pembayaran Menggunakan BON .'
                    );
                }
            }
        });

        // Cek apakah ada error (baik dari validasi standar maupun logic khusus)
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Mulai Transaksi Database
        DB::beginTransaction();
        try {
            // 2. CEK STATUS TRANSAKSI (Selesai vs Pending)
            $metodeBayar = MetodePembayaran::find($request->metode_pembayaran_id);
            $isBon = stripos($metodeBayar->nama_metode, 'BON') !== false;
            $statusTransaksi = $isBon ? 'pending' : 'selesai';

            // 3. HITUNG TOTAL & CEK STOK
            $total_belanja = 0;
            $list_barang_fix = [];

            $kategoriPelanggan = Kategori::find($request->kategori_id);
            $isKhusus = $kategoriPelanggan && stripos($kategoriPelanggan->nama_kategori, 'Khusus') !== false;

            foreach ($request->items as $item) {
                $produk = Produk::lockForUpdate()->find($item['produk_id']); // Lock baris agar tidak race condition

                // Cek Stok
                if ($produk->stok < $item['qty']) {
                    return response()->json(['success' => false, 'message' => "Stok {$produk->nama_produk} tidak mencukupi. Sisa: {$produk->stok}"], 400);
                }

                // Tentukan Harga (Bisa dikembangkan logic diskon disini berdasarkan kategori_id)
                $harga_final = $isKhusus ? $produk->harga_khusus : $produk->harga_umum;
                $subtotal = $harga_final * $item['qty'];
                $total_belanja += $subtotal;

                // Masukkan ke array sementara
                $list_barang_fix[] = [
                    'produk_id' => $produk->id,
                    'brand_id'  => $produk->brand_id, // Ambil otomatis dari master produk
                    'harga'     => $harga_final,
                    'qty'       => $item['qty'],
                    'subtotal'  => $subtotal
                ];
            }

            // 4. VALIDASI PEMBAYARAN (Khusus jika bukan BON)
            // Jika status Selesai (Cash/QRIS), uang bayar harus >= total
            // Jika status Pending (BON), uang bayar boleh 0 atau DP (terserah kebijakan toko)
            if ($statusTransaksi == 'selesai') {
                if ($request->bayar < $total_belanja) {
                    return response()->json(['success' => false, 'message' => 'Uang pembayaran kurang'], 400);
                }
            }

            // 5. SIMPAN HEADER TRANSAKSI
            $transaksi = Transaksi::create([
                'user_id'              => $request->user_id, // Atau auth()->id()
                'kategori_id'          => $request->kategori_id,
                'metode_pembayaran_id' => $request->metode_pembayaran_id,
                'nama_pelanggan'       => $request->nama_pelanggan ?? 'Umum', // Jika null diisi 'Umum'
                'tanggal'              => now(),
                'total'                => $total_belanja,
                'bayar'                => $request->bayar,
                'kembalian'            => $request->bayar - $total_belanja, // Minus berarti hutang (jika BON)
                'status'               => $statusTransaksi, // <--- ISI STATUS DISINI
            ]);

            // 6. SIMPAN DETAIL & KURANGI STOK
            foreach ($list_barang_fix as $barang) {
                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id'    => $barang['produk_id'],
                    'brand_id'     => $barang['brand_id'],
                    'harga'        => $barang['harga'],
                    'qty'          => $barang['qty'],
                    'subtotal'     => $barang['subtotal']
                ]);

                // Update Stok (Tetap dikurangi meskipun BON/Pending, karena barang sudah keluar)
                Produk::where('id', $barang['produk_id'])->decrement('stok', $barang['qty']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi Berhasil',
                'data'    => $transaksi
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus()
    {
        try {
            $affectedRows = Transaksi::where('status', 'pending')->update([
                'status'    => 'success',

                'bayar'     => DB::raw('total'),

                'kembalian' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil melunasi $affectedRows transaksi pending.",
                'data'    => [
                    'jumlah_terupdate' => $affectedRows
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getProdukTerlaris()
    {
        $produkTerlaris = TransaksiDetail::select('produk_id', DB::raw('SUM(qty) as total_terjual'))
            ->groupBy('produk_id')
            ->orderByDesc('total_terjual')
            ->with('produk:id,nama_produk')
            ->with('produk:id,nama_produk,brand_id', 'produk.brand:id,nama_brand')
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $produkTerlaris
        ]);
    }

    public function getProdukTerjualPerBulanByKategori($kategori_id)
    {
        $produkTerlaris = TransaksiDetail::select(
                DB::raw('SUM(qty) as total_terjual'),
                DB::raw('MONTH(transaksi.tanggal) as bulan'),
                DB::raw('YEAR(transaksi.tanggal) as tahun')
            )
            ->join('transaksi', 'transaksi_detail.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.kategori_id', $kategori_id)
            ->groupBy('bulan', 'tahun')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get()
            ->map(fn ($item) => [
                'bulan' => $item->bulan,
                'tahun' => $item->tahun,
                'total_terjual' => $item->total_terjual
            ]);

        return response()->json([
            'success' => true,
            'kategori_id' => $kategori_id,
            'data' => $produkTerlaris
        ]);
    }


    // public function store(Request $request)
    // {
    //     // 1. Validasi Input (Pastikan nama tabel singular: user, produk, metode_pembayaran)
    //     $validator = Validator::make($request->all(), [
    //         'user_id'              => 'required|exists:user,id',
    //         'produk_id'            => 'required|exists:produk,id',
    //         'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
    //         'qty'                  => 'required|integer|min:1',
    //         'bayar'                => 'required|numeric',
    //         'nama_pelanggan'       => 'nullable|string',
    //         'tanggal'              => 'required|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    //     }

    //     // Gunakan DB Transaction untuk keamanan data stok
    //     try {
    //         DB::beginTransaction();

    //         // 2. Cek Stok Produk
    //         $produk = Produk::findOrFail($request->produk_id);

    //         if ($produk->stok < $request->qty) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Stok tidak cukup. Sisa: ' . $produk->stok
    //             ], 400);
    //         }

    //         // 3. Hitung Total & Kembalian
    //         $total_belanja = $produk->harga * $request->qty;
    //         $kembalian = $request->bayar - $total_belanja;

    //         if ($kembalian < 0) {
    //             return response()->json(['success' => false, 'message' => 'Uang pembayaran kurang'], 400);
    //         }

    //         // 4. Simpan Transaksi
    //         $transaksi = Transaksi::create([
    //             'user_id'              => $request->user_id,
    //             'kategori_id'          => $produk->kategori_id, // Ambil otomatis dari produk
    //             'brand_id'             => $produk->brand_id,    // Ambil otomatis dari produk
    //             'produk_id'            => $produk->id,
    //             'metode_pembayaran_id' => $request->metode_pembayaran_id,
    //             'nama_pelanggan'       => $request->nama_pelanggan ?? 'Umum',
    //             'tanggal'              => $request->tanggal,
    //             'harga'                => $produk->harga,
    //             'qty'                  => $request->qty,
    //             'total'                => $total_belanja,
    //             'kembalian'            => $kembalian,
    //             'status'               => 'selesai'
    //         ]);

    //         // 5. Kurangi Stok
    //         $produk->decrement('stok', $request->qty);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Transaksi Berhasil',
    //             'data'    => $transaksi
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function show($id)
    {
        $transaksi = Transaksi::with(['user', 'detail.produk', 'detail.brand', 'metodePembayaran'])->find($id);

        if (!$transaksi) return response()->json(['success' => false, 'message' => 'Transaksi Tidak Ditemukan'], 404);

        return response()->json(['success' => true, 'data' => $transaksi]);
    }
}
