<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    public function index()
    {
        // Menampilkan semua data transaksi beserta relasinya
        $data = Transaksi::with(['user', 'produk', 'brand', 'kategori', 'metodePembayaran'])
                ->latest()
                ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        // 1. Validasi Input (Pastikan nama tabel singular: user, produk, metode_pembayaran)
        $validator = Validator::make($request->all(), [
            'user_id'              => 'required|exists:user,id',
            'produk_id'            => 'required|exists:produk,id',
            'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
            'qty'                  => 'required|integer|min:1',
            'bayar'                => 'required|numeric',
            'nama_pelanggan'       => 'nullable|string',
            'tanggal'              => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Gunakan DB Transaction untuk keamanan data stok
        try {
            DB::beginTransaction();

            // 2. Cek Stok Produk
            $produk = Produk::findOrFail($request->produk_id);

            if ($produk->stok < $request->qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak cukup. Sisa: ' . $produk->stok
                ], 400);
            }

            // 3. Hitung Total & Kembalian
            $total_belanja = $produk->harga * $request->qty;
            $kembalian = $request->bayar - $total_belanja;

            if ($kembalian < 0) {
                return response()->json(['success' => false, 'message' => 'Uang pembayaran kurang'], 400);
            }

            // 4. Simpan Transaksi
            $transaksi = Transaksi::create([
                'user_id'              => $request->user_id,
                'kategori_id'          => $produk->kategori_id, // Ambil otomatis dari produk
                'brand_id'             => $produk->brand_id,    // Ambil otomatis dari produk
                'produk_id'            => $produk->id,
                'metode_pembayaran_id' => $request->metode_pembayaran_id,
                'nama_pelanggan'       => $request->nama_pelanggan ?? 'Umum',
                'tanggal'              => $request->tanggal,
                'harga'                => $produk->harga,
                'qty'                  => $request->qty,
                'total'                => $total_belanja,
                'kembalian'            => $kembalian,
                'status'               => 'selesai'
            ]);

            // 5. Kurangi Stok
            $produk->decrement('stok', $request->qty);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi Berhasil',
                'data'    => $transaksi
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $transaksi = Transaksi::with(['user', 'produk', 'metodePembayaran'])->find($id);

        if (!$transaksi) return response()->json(['success'=>false, 'message'=>'Data not found'], 404);

        return response()->json(['success'=>true, 'data'=>$transaksi]);
    }
}
