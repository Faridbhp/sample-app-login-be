<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    // Jika Anda tidak menggunakan kolom 'id' sebagai primary key, tentukan primary key yang baru
    protected $primaryKey = 'email';

    // Jika primary key bukan auto-incrementing, tentukan properti ini
    public $incrementing = false;

    // Jika primary key bukan integer, tentukan tipe datanya
    protected $keyType = 'string';

    // Tentukan kolom yang dapat diisi (mass assignable)
    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    // Jika tabel tidak memiliki kolom created_at dan updated_at, set properti ini ke false
    public $timestamps = false;

    // Jika nama tabel berbeda dari pluralisasi model, tentukan nama tabel
    protected $table = 'personal_access_tokens';
}
