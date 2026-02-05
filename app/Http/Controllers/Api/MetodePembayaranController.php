<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetodePembayaranController extends Controller
{
    public function index()
    {
        // Include relasi kategori
        return response()->json([
            'success' => true,
            'data'    => MetodePembayaran::with('kategori')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori_id' => 'required|exists:kategori,id',
            'nama_metode' => 'required|string'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $data = MetodePembayaran::create($request->all());

        return response()->json(['success' => true, 'data' => $data], 201);
    }

    public function show($id)
    {
        $data = MetodePembayaran::with('kategori')->find($id);
        if (!$data) return response()->json(['success' => false, 'message' => 'Data not found'], 404);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = MetodePembayaran::find($id);
        if (!$data) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $validator = Validator::make($request->all(), [
            'kategori_id' => 'exists:kategori,id',
            'nama_metode' => 'string'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $data->update($request->all());
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function destroy($id)
    {
        $data = MetodePembayaran::find($id);
        if (!$data) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data deleted']);
    }
}
