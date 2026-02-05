<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => Kategori::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kategori' => 'required|string|unique:kategori,nama_kategori'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $kategori = Kategori::create($request->all());

        return response()->json(['success' => true, 'data' => $kategori], 201);
    }

    public function show($id)
    {
        $kategori = Kategori::find($id);
        if (!$kategori) return response()->json(['success' => false, 'message' => 'Data not found'], 404);
        return response()->json(['success' => true, 'data' => $kategori]);
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::find($id);
        if (!$kategori) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $kategori->update($request->all());
        return response()->json(['success' => true, 'data' => $kategori]);
    }

    public function destroy($id)
    {
        $kategori = Kategori::find($id);
        if (!$kategori) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $kategori->delete();
        return response()->json(['success' => true, 'message' => 'Data deleted']);
    }
}
