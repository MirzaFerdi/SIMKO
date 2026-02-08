<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Pastikan baris ini ada
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    // REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:user',
            'password' => 'required|min:6',
            'role_id'  => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id
        ]);

        return response()->json([
            'message' => 'User berhasil didaftarkan',
            'user'    => $user
        ], 201);
    }

    // LOGIN
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
        return response()->json(auth()->user());
    }

    // LOGOUT
    public function logout()
    {
        auth()->logout();

        $cookie = cookie()->forget('token');

        return response()->json(['message' => 'Logout berhasil'])
            ->withCookie($cookie);
    }

    // REFRESH
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    // protected function respondWithToken($token)
    // {
    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type'   => 'bearer',
    //         'expires_in'   => auth()->factory()->getTTL() * 99999,
    //         'user'         => auth()->user()
    //     ]);
    // }

    protected function respondWithToken($token)
    {
        $cookie = cookie(
            'token',
            $token,
            60 * 24,
            '/',
            null,
            false,
            true
        );

        return response()->json([
            'success' => true,
            'user'    => auth()->user(),
        ])->withCookie($cookie);
    }
}
