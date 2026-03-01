<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\RiwayatStok;

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

    public function showPaginate()
    {
        $data = Transaksi::with(['user', 'detail.produk', 'detail.brand', 'kategori', 'metodePembayaran'])
            ->where('status', 'BON(pending)')
            ->orderBy('id')
            ->paginate(6);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function searchPending($keyword)
    {
        $data = Transaksi::with(['user', 'detail.produk', 'detail.brand'])
            ->where('status', 'BON(pending)')
            ->where('nama_pelanggan', 'like', "%{$keyword}%")
            ->orderByDesc('tanggal')
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Daftar Transaksi BON (Pending) dengan keyword: {$keyword}",
            'data'    => $data
        ]);
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
            $statusTransaksi = $isBon ? 'BON(pending)' : 'Selesai';

            // 3. HITUNG TOTAL & CEK STOK
            $total_belanja = 0;
            $list_barang_fix = [];

            $kategoriPelanggan = Kategori::find($request->kategori_id);
            $isKhusus = $kategoriPelanggan && stripos($kategoriPelanggan->nama_kategori, 'Khusus') !== false;

            // --- LOOPING PERTAMA: Validasi dan Persiapan Data ---
            foreach ($request->items as $item) {
                $produk = Produk::lockForUpdate()->find($item['produk_id']);

                if ($produk->stok < $item['qty']) {
                    return response()->json(['success' => false, 'message' => "Stok {$produk->nama_produk} tidak mencukupi. Sisa: {$produk->stok}"], 400);
                }

                $harga_final = $isKhusus ? $produk->harga_khusus : $produk->harga_umum;
                $subtotal = $harga_final * $item['qty'];
                $total_belanja += $subtotal;

                $list_barang_fix[] = [
                    'produk_id'  => $produk->id,
                    'brand_id'   => $produk->brand_id,
                    'harga'      => $harga_final,
                    'qty'        => $item['qty'],
                    'subtotal'   => $subtotal,

                    // --- TAMBAHAN UNTUK RIWAYAT STOK ---
                    'stok_awal'  => $produk->stok, // Catat stok sebelum transaksi
                    'stok_akhir' => $produk->stok - $item['qty'] // Hitung sisa stok
                ];
            }

            // 4. VALIDASI PEMBAYARAN
            if ($statusTransaksi == 'Selesai') {
                if ($request->bayar < $total_belanja) {
                    return response()->json(['success' => false, 'message' => 'Uang pembayaran kurang'], 400);
                }
            }

            // 5. SIMPAN HEADER TRANSAKSI
            $transaksi = Transaksi::create([
                'user_id'              => $request->user_id,
                'kategori_id'          => $request->kategori_id,
                'metode_pembayaran_id' => $request->metode_pembayaran_id,
                'nama_pelanggan'       => $request->nama_pelanggan ?? 'Umum',
                'tanggal'              => now(),
                'total'                => $total_belanja,
                'bayar'                => $request->bayar,
                'kembalian'            => $request->bayar - $total_belanja,
                'status'               => $statusTransaksi,
            ]);

            // 6. SIMPAN DETAIL, KURANGI STOK, & CATAT RIWAYAT
            // --- LOOPING KEDUA: Eksekusi Database ---
            foreach ($list_barang_fix as $barang) {

                // A. Simpan Detail
                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id'    => $barang['produk_id'],
                    'brand_id'     => $barang['brand_id'],
                    'harga'        => $barang['harga'],
                    'qty'          => $barang['qty'],
                    'subtotal'     => $barang['subtotal']
                ]);

                // B. Update Stok Master Produk
                Produk::where('id', $barang['produk_id'])->decrement('stok', $barang['qty']);

                // C. Simpan ke Riwayat Stok (Fitur Baru)
                RiwayatStok::create([
                    'produk_id'   => $barang['produk_id'],
                    'stok_awal'   => $barang['stok_awal'],
                    'stok_masuk'  => 0, // 0 karena ini transaksi keluar
                    'stok_keluar' => $barang['qty'], // Jumlah yang dibeli
                    'stok_akhir'  => $barang['stok_akhir'], // Sisa stok
                    'keterangan'  => 'Penjualan Transaksi #' . $transaksi->id
                ]);
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

    public function updateStatus(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'transaksi_ids' => 'required|array',
            'transaksi_ids.*' => 'required|exists:transaksi,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $affectedRows = Transaksi::whereIn('id', $request->transaksi_ids)
                ->where('status', 'BON(pending)')
                ->update([
                    'status'    => 'Selesai',
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
            ->map(fn($item) => [
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

    public function pending()
    {
        $data = Transaksi::with(['user', 'detail.produk', 'detail.brand', 'kategori', 'metodePembayaran'])
            ->where('status', 'BON(pending)')
            ->orderByDesc('tanggal')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Transaksi BON (Pending)',
            'data'    => $data
        ]);
    }

    public function show($id)
    {
        $transaksi = Transaksi::with(['user', 'detail.produk', 'detail.brand', 'metodePembayaran'])->find($id);

        if (!$transaksi) return response()->json(['success' => false, 'message' => 'Transaksi Tidak Ditemukan'], 404);

        return response()->json(['success' => true, 'data' => $transaksi]);
    }
}
