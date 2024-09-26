<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'password_reset_tokens';

    // Menentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    // Menonaktifkan timestamps otomatis
    public $timestamps = false;

    // Menggunakan 'email' sebagai primary key karena tabel tidak memiliki kolom 'id'
    protected $primaryKey = 'email';

    // Menonaktifkan auto increment karena 'email' bukan auto increment
    public $incrementing = false;

    // Menentukan tipe primary key sebagai string
    protected $keyType = 'string';
}
