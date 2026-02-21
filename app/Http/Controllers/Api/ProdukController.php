<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\RiwayatStok;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function index()
    {
        // Load relasi brand dan kategori
        return response()->json([
            'success' => true,
            'data'    => Produk::with(['brand', 'kategori'])->orderBy('id')->get()
        ]);
    }

    public function showPaginate()
    {
        return response()->json([
            'success' => true,
            'data'    => Produk::with(['brand', 'kategori'])->orderBy('id')->paginate(6)
        ]);
    }

    public function search($keyword){
        $produk = Produk::with(['brand', 'kategori'])
            ->where('nama_produk', 'like', "%$keyword%")
            ->orWhereHas('brand', function($query) use ($keyword) {
                $query->where('nama_brand', 'like', "%$keyword%");
            })
            ->orWhereHas('kategori', function($query) use ($keyword) {
                $query->where('nama_kategori', 'like', "%$keyword%");
            })
            ->orderBy('id')
            ->paginate(6);

        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id'    => 'required|exists:brand,id',
            'nama_produk' => 'required|string',
            'harga_umum'   => 'required|numeric',
            'harga_khusus' => 'required|numeric',
            'stok'        => 'required|integer',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $produk = Produk::create($request->all());

        return response()->json(['success' => true, 'data' => $produk], 201);
    }

    public function tambahStok(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'qty'        => 'required|integer|min:1',
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $produk = Produk::lockForUpdate()->find($id);

            if (!$produk) {
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
            }

            $stokAwal  = $produk->stok;
            $stokMasuk = $request->qty;
            $stokAkhir = $stokAwal + $stokMasuk;

            $produk->update(['stok' => $stokAkhir]);

            RiwayatStok::create([
                'produk_id'  => $produk->id,
                'stok_awal'  => $stokAwal,
                'stok_masuk' => $stokMasuk,
                'stok_akhir' => $stokAkhir,
                'keterangan' => $request->keterangan ?? 'Penambahan Stok Manual',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil ditambahkan',
                'data' => [
                    'nama_produk' => $produk->nama_produk,
                    'stok_sebelumnya' => $stokAwal,
                    'stok_ditambahkan' => $stokMasuk,
                    'stok_sekarang' => $stokAkhir
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function riwayatStok($id)
    {
        $riwayat = RiwayatStok::with('produk:id,nama_produk')
            ->where('produk_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $riwayat
        ]);
    }

    public function showAllRiwayatStok()
    {
        $riwayat = RiwayatStok::with('produk:id,nama_produk')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $riwayat
        ]);
    }

    public function show($id)
    {
        $produk = Produk::with(['brand', 'kategori'])->find($id);
        if (!$produk) return response()->json(['success' => false, 'message' => 'Produk Tidak Ditemukan'], 404);
        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function showLowStock(){
        $produk = Produk::with(['brand', 'kategori'])->orderBy('stok', 'asc')->limit(3)->get();
        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function showBrand($brand_id)
    {
        $produk = Produk::with(['brand', 'kategori'])->where('brand_id', $brand_id)->get();
        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['success' => false, 'message' => 'Produk Tidak Ditemukan'], 404);

        $produk->update($request->all());
        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function destroy($id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['success' => false, 'message' => 'Produk Tidak Ditemukan'], 404);

        $produk->delete();
        return response()->json(['success' => true, 'message' => 'Produk Berhasil Dihapus']);
    }
}
