<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => Brand::orderBy('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_brand' => 'required|string|unique:brand,nama_brand'
        ]);

        if ($validator->fails()) return response()->json([
            'success' => false,
            'message' => 'Brand Dengan Nama Tersebut Sudah Ada',
            'errors' => $validator->errors()
        ], 422);

        $brand = Brand::create($request->all());

        return response()->json(['success' => true, 'data' => $brand], 201);
    }

    public function show($id)
    {
        $brand = Brand::find($id);
        if (!$brand) return response()->json(['success' => false, 'message' => 'Brand Tidak Ditemukan'], 404);
        return response()->json(['success' => true, 'data' => $brand]);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);
        if (!$brand) return response()->json(['success' => false, 'message' => 'Brand Tidak Ditemukan'], 404);

        $brand->update($request->all());
        return response()->json(['success' => true, 'data' => $brand]);
    }

    public function destroy($id)
    {
        $brand = Brand::find($id);
        if (!$brand) return response()->json(['success' => false, 'message' => 'Brand Tidak Ditemukan'], 404);

        $brand->delete();
        return response()->json(['success' => true, 'message' => 'Brand Berhasil Dihapus']);
    }
}
