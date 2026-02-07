<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => Role::orderBy('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_role' => 'required|string|unique:role,nama_role'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $role = Role::create($request->all());

        return response()->json(['success' => true, 'data' => $role], 201);
    }

    public function show($id)
    {
        $role = Role::find($id);
        if (!$role) return response()->json(['success' => false, 'message' => 'Role Tidak Ditemukan'], 404);
        return response()->json(['success' => true, 'data' => $role]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) return response()->json(['success' => false, 'message' => 'Role Tidak Ditemukan'], 404);

        $role->update($request->all());
        return response()->json(['success' => true, 'data' => $role]);
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) return response()->json(['success' => false, 'message' => 'Role Tidak Ditemukan'], 404);

        $role->delete();
        return response()->json(['success' => true, 'message' => 'Role Berhasil Dihapus']);
    }
}
