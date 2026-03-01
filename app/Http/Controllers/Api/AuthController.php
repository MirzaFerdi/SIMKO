<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        // Middleware auth:api kecuali untuk login & register
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    // REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'nama'     => 'required|string|max:255',
            'username' => 'required|unique:user',
            'password' => 'required|min:6',
            'role_id'  => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'nama'     => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id
        ]);

        return response()->json([
            'message' => 'User berhasil didaftarkan',
            'user'    => $user
        ], 201);
    }

    // LOGIN (Kembali ke Standard)
    public function login()
    {
        $credentials = request(['username', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    // ME
    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Anda sudah logout'], 401);
        }

        $user->load('role');

        return response()->json($user);
    }

    // LOGOUT (Hapus Token di Server)
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    // REFRESH (Generate Token Baru)
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    // HELPER RESPONSE (Tanpa Cookie)
    protected function respondWithToken($token)
    {
        return response()->json([
            'success'      => true,
            'access_token' => $token,           // Token dikirim disini
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'user'         => auth()->user()
        ]);
    }
}
