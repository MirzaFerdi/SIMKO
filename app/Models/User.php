<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // Import ini

class User extends Authenticatable implements JWTSubject // Tambahkan implements
{
    use Notifiable;

    protected $table = 'user';
    protected $guarded = ['id'];

    // Karena tabel Anda pakai 'username', bukan 'email', kita tidak perlu ubah apa-apa disini
    // selama di controller kita mengirim key 'username'.

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function role(){
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Wajib: Method 1 dari Interface JWTSubject
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Wajib: Method 2 dari Interface JWTSubject
    public function getJWTCustomClaims()
    {
        return [
            'role_id' => $this->role_id, // Custom claim (opsional), berguna untuk frontend
        ];
    }
}
