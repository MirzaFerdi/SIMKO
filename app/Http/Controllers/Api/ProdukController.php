<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProdukController extends Controller
{
    public function index()
    {
        // Load relasi brand dan kategori
        return response()->json([
            'success' => true,
            'data'    => Produk::with(['brand', 'kategori'])->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id'    => 'required|exists:brand,id',
            'kategori_id' => 'required|exists:kategori,id',
            'nama_produk' => 'required|string',
            'harga'       => 'required|numeric',
            'stok'        => 'required|integer',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $produk = Produk::create($request->all());

        return response()->json(['success' => true, 'data' => $produk], 201);
    }

    public function show($id)
    {
        $produk = Produk::with(['brand', 'kategori'])->find($id);
        if (!$produk) return response()->json(['success' => false, 'message' => 'Data not found'], 404);
        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $produk->update($request->all());
        return response()->json(['success' => true, 'data' => $produk]);
    }

    public function destroy($id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $produk->delete();
        return response()->json(['success' => true, 'message' => 'Data deleted']);
    }
}
